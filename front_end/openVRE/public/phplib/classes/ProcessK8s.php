<?php

/**
 * ProcessK8s — Kubernetes Job manager for OpenVRE tool execution.

 *
 *
 * Environment variables consumed (required — set defaults in deployment env / .env):
 *   OPENVRE_K8S_NAMESPACE       — target namespace for Jobs
 *   OPENVRE_K8S_JOB_IMAGE       — fallback container image for the Job pod (may be empty if set per-tool)
 *   OPENVRE_K8S_SHARED_PVC      — PVC name for /shared_data volume
 *   OPENVRE_K8S_TOOLS_PVC       — PVC name for /var/www/html/openVRE/public/tools volume
 *   OPENVRE_K8S_SCHEDULER_URL   — base URL of the scheduler service
 *   OPENVRE_K8S_SCHEDULER_TOKEN — Bearer token for scheduler authentication (may be empty)
 *   OPENVRE_K8S_RUN_AS_UID      — UID the Job pod runs as
 *   OPENVRE_K8S_RUN_AS_GID      — GID / fsGroup for the Job pod
 *   OPENVRE_K8S_JOB_TTL         — seconds to keep a finished Job before auto-deletion
 *   OPENVRE_K8S_JOB_DEADLINE    — max seconds a Job is allowed to run before being killed
 */
class ProcessK8s
{

    /** Kubernetes Job name, also used as the OpenVRE "pid" */
    private string $pid = "";

    /** Path to the submission script inside the Job pod */
    private string $command = "";

    /** Working directory inside the Job pod */
    private string $workDir = "";

    /** Human-readable job identifier (used to derive the K8s name) */
    private string $jobname = "";

    /** Full API URL used for submission (for logging/debugging) */
    private string $fullcommand = "";

    /** Captured stdout from the submission response */
    private string $stdout = "";

    /** Captured stderr / error message from submission */
    private string $stderr = "";

    /** Kubernetes namespace where Jobs are created */
    private string $namespace;

    /** Container image used for the Job pod */
    private string $jobImage;

    /** PVC name mounted at /shared_data (shared between frontend, sge, and jobs) */
    private string $sharedPvc;

    /** PVC name mounted at the tools directory inside the Job pod */
    private string $toolsPvc;

    /** Base URL of the scheduler HTTP service */
    private string $schedulerUrl;

    /** Bearer token sent to the scheduler for authentication */
    private string $schedulerToken;

    /** UID the Job pod container runs as */
    private int $runAsUid;

    /** GID / fsGroup for the Job pod */
    private int $runAsGid;

    /** Seconds to keep a finished Job before auto-deletion */
    private int $jobTtl;

    /** Max seconds a Job is allowed to run before being killed */
    private int $jobDeadline;

    /** Additional environment variables to inject into the Job pod (from tool Mongo definition) */
    private array $jobEnv = [];

    /** Maps Kubernetes Job condition strings to OpenVRE internal state labels */
    private array $jobState = [
        "Running"   => "RUNNING",
        "Pending"   => "PENDING",
        "Succeeded" => "FINISHING",
        "Failed"    => "ERROR",
        "NotFound"  => "FINISHING",
    ];

    // ---------------------------------------------------------------
    // Constructor
    // ---------------------------------------------------------------

    /**
     * When $cl is a script path, builds and submits a Kubernetes Job immediately.
     * When $cl is false, creates a status-only instance (getRunningJobInfo, status, stop).
     *
     */
    public function __construct(false|string $cl = false, string $workDir = "", string $jobname = "", int $cpu = 1, int $mem = 0, array $jobOptions = [])
    {
        $this->namespace = $this->env("OPENVRE_K8S_NAMESPACE");
        $this->jobImage = $this->env("OPENVRE_K8S_JOB_IMAGE");
        $this->sharedPvc = $this->env("OPENVRE_K8S_SHARED_PVC");
        $this->toolsPvc = $this->env("OPENVRE_K8S_TOOLS_PVC");
        $this->schedulerUrl = rtrim($this->env("OPENVRE_K8S_SCHEDULER_URL"), "/");
        $this->schedulerToken = $this->env("OPENVRE_K8S_SCHEDULER_TOKEN");
        $this->runAsUid = (int)$this->env("OPENVRE_K8S_RUN_AS_UID");
        $this->runAsGid = (int)$this->env("OPENVRE_K8S_RUN_AS_GID");
        $this->jobTtl = (int)$this->env("OPENVRE_K8S_JOB_TTL");
        $this->jobDeadline = (int)$this->env("OPENVRE_K8S_JOB_DEADLINE");

        // Allow per-tool overrides: the tool's Mongo document can specify a custom
        // container image and extra environment variables via $jobOptions.
        if (!empty($jobOptions["image"])) {
            $this->jobImage = $jobOptions["image"];
        }
        if (!empty($jobOptions["env"]) && is_array($jobOptions["env"])) {
            $this->jobEnv = $jobOptions["env"];
        }

        // If a submission script path was provided, immediately create and submit
        // the Kubernetes Job. When $cl is false, this instance is used only for
        // status queries (getRunningJobInfo, status, stop).
        if ($cl) {
            $this->workDir = $workDir;
            $this->command = $cl;
            $this->jobname = $jobname ? $jobname : basename($cl);
            $this->runCom($cpu, $mem);
        }
    }



    private function env(string $name): string
    {
        $value = getenv($name);
        if ($value === false) {
            throw new \RuntimeException("Environment variable {$name} is not set");
        }
        return $value;
    }

    private function sanitizeName(string $name): string
    {
        $name = strtolower($name);
        $name = preg_replace('/[^a-z0-9-]+/', '-', $name);
        $name = trim($name, '-');
        if ($name === "") {
            $name = "openvre-job";
        }
        if (strlen($name) > 40) {
            $name = substr($name, 0, 40);
            $name = rtrim($name, '-');
        }
        return $name . "-" . substr(md5(uniqid("", true)), 0, 8);
    }


    /** Builds a Kubernetes Job manifest and submits it to the scheduler. */
    private function runCom(int $cpu, int $mem): void
    {
        // Abort if no container image is configured — there's nothing to run.
        if ($this->jobImage === "") {
            $this->stderr = "OPENVRE_K8S_JOB_IMAGE is not set";
            $_SESSION['errorData']['Error'][] = $this->stderr;
            return;
        }

        if ($this->schedulerUrl === "") {
            $this->stderr = "OPENVRE_K8S_SCHEDULER_URL is not set";
            $_SESSION['errorData']['Error'][] = $this->stderr;
            return;
        }

        // Generate a unique, K8s-safe Job name and use it as the OpenVRE "pid".
        $jobName = $this->sanitizeName($this->jobname);
        $this->pid = $jobName;

        $yaml = $this->arrayToYaml($this->buildJobManifest($jobName, $cpu, $mem));

        // Submit Job via the scheduler service.
        $this->fullcommand = "POST " . $this->schedulerUrl . "/jobs";
        logger("K8s job submission via scheduler '" . $this->fullcommand . "'");
        $response = $this->schedulerRequest("POST", "/jobs", array(
            "namespace" => $this->namespace,
            "manifest" => $yaml
        ));

        // Handle submission result.
        if ($response["ok"] !== true) {
            $this->stderr = $response["error"];
            $this->stdout = "";
            $msg = "K8s job submission failed: " . trim($this->stderr);
            logger($msg);
            $_SESSION['errorData']['Error'][] = $msg;
            $this->pid = "";
        } else {
            $this->stdout = isset($response["data"]["stdout"]) ? (string)$response["data"]["stdout"] : "";
            $this->stderr = isset($response["data"]["stderr"]) ? (string)$response["data"]["stderr"] : "";
            logger("K8s job submitted: " . $this->pid . ". Output: " . trim($this->stdout));
        }
    }


    private function buildJobManifest(string $jobName, int $cpu, int $mem): array
    {
        $cpuRequest = max(1, $cpu);
        $cpuLimit = max(1, $cpu);
        $memLimit = ($mem > 0 ? ($mem . "Gi") : "4Gi");

        $manifest = array(
            "apiVersion" => "batch/v1",
            "kind" => "Job",
            "metadata" => array(
                "name" => $jobName,
                "namespace" => $this->namespace,
                "labels" => array(
                    "app.kubernetes.io/managed-by" => "openvre",
                    "openvre-job-id" => $jobName
                )
            ),
            "spec" => array(
                "ttlSecondsAfterFinished" => $this->jobTtl,
                "activeDeadlineSeconds" => $this->jobDeadline,
                "backoffLimit" => 0,
                "template" => array(
                    "spec" => array(
                        "restartPolicy" => "Never",
                        "containers" => array(
                            array(
                                "name" => "tool-runner",
                                "image" => $this->jobImage,
                                "command" => array("bash", "-lc", "cd \"\$OPENVRE_WORKDIR\" && bash \"\$OPENVRE_SUBMIT_SCRIPT\""),
                                "env" => array(
                                    array("name" => "OPENVRE_WORKDIR", "value" => $this->workDir),
                                    array("name" => "OPENVRE_SUBMIT_SCRIPT", "value" => $this->command),
                                ),
                                "resources" => array(
                                    "requests" => array("cpu" => (string)$cpuRequest),
                                    "limits" => array("cpu" => (string)$cpuLimit, "memory" => $memLimit)
                                ),
                                "securityContext" => array(
                                    "allowPrivilegeEscalation" => false,
                                ),
                                "volumeMounts" => array(
                                    array("name" => "shared-data", "mountPath" => $GLOBALS['shared']),
                                    array("name" => "tools", "mountPath" => "/var/www/html/openVRE/public/tools")
                                )
                            )
                        ),
                        "securityContext" => array(
                            "runAsUser" => max(1, $this->runAsUid),
                            "runAsGroup" => max(1, $this->runAsGid),
                            "fsGroup" => max(1, $this->runAsGid)
                        ),
                        "volumes" => array(
                            array("name" => "shared-data", "persistentVolumeClaim" => array("claimName" => $this->sharedPvc)),
                            array("name" => "tools", "persistentVolumeClaim" => array("claimName" => $this->toolsPvc))
                        )
                    )
                )
            )
        );

        if (!empty($this->jobEnv)) {
            $envList =& $manifest["spec"]["template"]["spec"]["containers"][0]["env"];
            foreach ($this->jobEnv as $envName => $envValue) {
                if (empty($envName) || empty($envValue)) {
                    continue;
                }
                $envList[] = array(
                    "name" => (string)$envName,
                    "value" => (string)$envValue
                );
            }
        }

        return $manifest;
    }


    private function schedulerRequest(string $method, string $path, ?array $payload = null): array
    {
        if ($this->schedulerUrl === "") {
            return array("ok" => false, "error" => "OPENVRE_K8S_SCHEDULER_URL is not set");
        }
        if (!function_exists("curl_init")) {
            return array("ok" => false, "error" => "PHP curl extension is required");
        }

        $url = $this->schedulerUrl . $path;
        $ch = curl_init($url);
        $headers = array("Content-Type: application/json");

        // Authenticate to the scheduler with a Bearer token.
        if ($this->schedulerToken !== "") {
            $headers[] = "Authorization: Bearer " . $this->schedulerToken;
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        }

        $raw = curl_exec($ch);
        if ($raw === false) {
            $err = curl_error($ch);
            curl_close($ch);
            return array("ok" => false, "error" => "Scheduler request failed: " . $err);
        }
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = json_decode($raw, true);

        // Non-2xx status from the scheduler indicates an error.
        if ($code < 200 || $code >= 300) {
            $msg = is_array($json) && isset($json["error"]) ? $json["error"] : ("HTTP " . $code . " from scheduler");
            return array("ok" => false, "error" => $msg);
        }
        if (!is_array($json)) {
            return array("ok" => false, "error" => "Invalid JSON response from scheduler");
        }
        // The scheduler returns {"ok": false, "error": "..."} on application-level errors.
        if (isset($json["ok"]) && !$json["ok"]) {
            return array("ok" => false, "error" => isset($json["error"]) ? $json["error"] : "Scheduler error");
        }
        return array("ok" => true, "data" => $json);
    }

    /** Serializes the Job manifest array to YAML before sending it to the scheduler. */
    private function arrayToYaml(array $data, int $indent = 0): string
    {
        $yaml = "";
        $spaces = str_repeat("  ", $indent);
        $isAssoc = function ($arr) {
            if (!is_array($arr)) return false;
            return array_keys($arr) !== range(0, count($arr) - 1);
        };

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if ($isAssoc($value)) {
                    // Associative array → nested YAML object.
                    $yaml .= $spaces . $key . ":\n" . $this->arrayToYaml($value, $indent + 1);
                } else {
                    // Sequential array → YAML list with "- " prefix.
                    $yaml .= $spaces . $key . ":\n";
                    foreach ($value as $item) {
                        if (is_array($item)) {
                            $yaml .= $spaces . "  -\n" . $this->arrayToYaml($item, $indent + 2);
                        } else {
                            $yaml .= $spaces . "  - " . $this->yamlScalar($item) . "\n";
                        }
                    }
                }
            } else {
                $yaml .= $spaces . $key . ": " . $this->yamlScalar($value) . "\n";
            }
        }
        return $yaml;
    }


    private function yamlScalar(mixed $value): string
    {
        if (is_bool($value)) return $value ? "true" : "false";
        if (is_numeric($value)) return (string)$value;
        $escaped = str_replace('"', '\"', (string)$value);
        return '"' . $escaped . '"';
    }


    public function getRunningJobInfo(string $pid): array
    {
        $job = array();
        if (!$pid) return $job;

        // Query job status via the scheduler.
        $response = $this->schedulerRequest("GET", "/jobs/" . rawurlencode($pid) . "?namespace=" . rawurlencode($this->namespace));

        if ($response["ok"] !== true) {
            return array();
        }

        $exists = isset($response["data"]["exists"]) ? (bool)$response["data"]["exists"] : false;
        if (!$exists) {
            return array();
        }

        // Parse the Job JSON to determine its current condition.
        $jsonRaw = isset($response["data"]["job"]) ? $response["data"]["job"] : "";
        $json = json_decode($jsonRaw, true);
        if (!is_array($json)) {
            return array();
        }

        $status = $json['status'] ?? array();
        $state = "Pending";
        if (!empty($status['active'])) {
            $state = "Running";
        } elseif (!empty($status['succeeded'])) {
            $state = "Succeeded";
        } elseif (!empty($status['failed'])) {
            $state = "Failed";
        }

        // Once the Job is finished (succeeded or failed), return empty so
        // OpenVRE's workspace loop finalizes outputs and stops polling.
        if ($state === "Succeeded" || $state === "Failed") {
            return array();
        }

        $job['pid'] = $pid;
        $job['state'] = $this->jobState[$state];
        $job['job_name'] = $pid;
        return $job;
    }


    /** Returns the full API URL used for the last submission (for debug logging). */
    public function getFullCommand()
    {
        return $this->fullcommand;
    }

    public function getPid()
    {
        return $this->pid;
    }

    public function getErr()
    {
        if ($this->stderr) {
            return trim($this->stdout . " " . $this->stderr);
        }
        return null;
    }


    public function status()
    {
        if (!$this->pid) return false;

        $response = $this->schedulerRequest("GET", "/jobs/" . rawurlencode($this->pid) . "?namespace=" . rawurlencode($this->namespace));

        if ($response["ok"] !== true) {
            return false;
        }

        $exists = isset($response["data"]["exists"]) ? (bool)$response["data"]["exists"] : false;
        if (!$exists) {
            return false;
        }

        $jsonRaw = isset($response["data"]["job"]) ? $response["data"]["job"] : "";
        $json = json_decode($jsonRaw, true);
        if (!is_array($json)) {
            return true;
        }

        $status = isset($json["status"]) && is_array($json["status"]) ? $json["status"] : array();
        // Job is no longer active if it has succeeded or failed.
        if (!empty($status["succeeded"]) || !empty($status["failed"])) {
            return false;
        }

        return true;
    }


    public function stop(?string $pid = null): array
    {
        if (!$pid) {
            return array(false, "No job id '$pid' given");
        }

        $response = $this->schedulerRequest("DELETE", "/jobs/" . rawurlencode($pid) . "?namespace=" . rawurlencode($this->namespace));

        if ($response["ok"] === true) {
            $res = isset($response["data"]["stdout"]) ? trim((string)$response["data"]["stdout"]) : "";
            return array(true, $res);
        }
        return array(false, $response["error"] ?: "Failed to delete kubernetes job");
    }
}
