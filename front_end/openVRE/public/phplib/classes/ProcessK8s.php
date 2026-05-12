<?php

/**
 * ProcessK8s — Kubernetes Job manager for OpenVRE tool execution.
 *
 * This class is responsible for the full lifecycle of Kubernetes Jobs that run
 * OpenVRE tools: creation, status polling, and deletion/cancellation.
 *
 * All communication with Kubernetes goes through the kubectl-runner proxy — a
 * dedicated HTTP microservice deployed as a sidecar or separate pod that holds
 * the Kubernetes service-account credentials. The frontend pod itself does NOT
 * need a mounted service-account token or direct K8s API access, which improves
 * security by limiting the blast radius of a frontend compromise.
 *
 * Environment variables consumed (all optional, with defaults):
 *   OPENVRE_K8S_NAMESPACE       — target namespace for Jobs (default: "bsctre-v2")
 *   OPENVRE_K8S_JOB_IMAGE       — fallback container image for the Job pod
 *   OPENVRE_K8S_SHARED_PVC      — PVC name for /shared_data volume
 *   OPENVRE_K8S_TOOLS_PVC       — PVC name for /var/www/html/openVRE/public/tools volume
 *   OPENVRE_K8S_LAUNCHER_URL    — base URL of kubectl-runner (REQUIRED)
 *   OPENVRE_K8S_LAUNCHER_TOKEN  — Bearer token for kubectl-runner authentication
 *   OPENVRE_K8S_RUN_AS_UID      — UID the Job pod runs as (default: 1000)
 *   OPENVRE_K8S_RUN_AS_GID      — GID / fsGroup for the Job pod (default: 1000)
 *   OPENVRE_K8S_JOB_TTL         — seconds to keep a finished Job before auto-deletion (default: 120)
 *   OPENVRE_K8S_JOB_DEADLINE    — max seconds a Job is allowed to run before being killed (default: 86400 = 24h)
 */
class ProcessK8s
{
    // ---------------------------------------------------------------
    // Instance properties
    // ---------------------------------------------------------------

    /** @var string  Kubernetes Job name, also used as the OpenVRE "pid" */
    private $pid = "";

    /** @var string  Path to the submission script inside the Job pod */
    private $command = "";

    /** @var string  Working directory inside the Job pod */
    private $workDir = "";

    /** @var string  Human-readable job identifier (used to derive the K8s name) */
    private $jobname = "";

    /** @var string  Full API URL used for submission (for logging/debugging) */
    private $fullcommand = "";

    /** @var string  Captured stdout from the submission response */
    private $stdout = "";

    /** @var string  Captured stderr / error message from submission */
    private $stderr = "";

    /** @var string  Kubernetes namespace where Jobs are created */
    private $namespace = "bsctre-v2";

    /** @var string  Container image used for the Job pod */
    private $jobImage = "";

    /** @var string  PVC name mounted at /shared_data (shared between frontend, sge, and jobs) */
    private $sharedPvc = "dashboard-frontend-sgecore-shareddata";

    /** @var string  PVC name mounted at the tools directory inside the Job pod */
    private $toolsPvc = "dashboard-frontend-tools";

    /** @var string  Base URL of the kubectl-runner HTTP proxy (REQUIRED) */
    private $launcherUrl = "";

    /** @var string  Bearer token sent to kubectl-runner for authentication */
    private $launcherToken = "";

    /** @var int  UID the Job pod container runs as */
    private $runAsUid = 1000;

    /** @var int  GID / fsGroup for the Job pod */
    private $runAsGid = 1000;

    /** @var array  Additional environment variables to inject into the Job pod (from tool Mongo definition) */
    private $jobEnv = array();

    /**
     * Maps Kubernetes Job condition strings to OpenVRE's internal state labels
     * used by the workspace polling / UI display logic.
     */
    private $jobState = array(
        "Running"   => "RUNNING",
        "Pending"   => "PENDING",
        "Succeeded" => "FINISHING",
        "Failed"    => "ERROR",
        "NotFound"  => "FINISHING",
    );

    // ---------------------------------------------------------------
    // Constructor
    // ---------------------------------------------------------------

    /**
     * @param string|false $cl         Path to submission script (false = status-only instance)
     * @param string       $workDir    Working directory path inside the pod
     * @param string       $queue      Queue name (unused for K8s, kept for interface compatibility with ProcessSGE)
     * @param string       $jobname    Human-readable job name
     * @param int          $cpu        CPU cores requested/limited
     * @param int          $mem        Memory in GB (0 = default 4Gi)
     * @param string       $logFile    Stdout log filename (unused for K8s, kept for interface compatibility)
     * @param string       $errFile    Stderr log filename (unused for K8s, kept for interface compatibility)
     * @param array        $jobOptions Options from Tooljob: "image" overrides container image,
     *                                 "env" provides extra environment variables for the pod
     */
    public function __construct($cl = false, $workDir = "", $queue = "", $jobname = "", $cpu = 1, $mem = 0, $logFile = "job_output.log", $errFile = "job_error.log", $jobOptions = array())
    {
        // Read all configuration from environment variables (set via ConfigMap/Secret).
        $this->namespace = getenv("OPENVRE_K8S_NAMESPACE") ?: "bsctre-v2";
        $this->jobImage = getenv("OPENVRE_K8S_JOB_IMAGE") ?: "";
        $this->sharedPvc = getenv("OPENVRE_K8S_SHARED_PVC") ?: "dashboard-frontend-sgecore-shareddata";
        $this->toolsPvc = getenv("OPENVRE_K8S_TOOLS_PVC") ?: "dashboard-frontend-tools";
        $this->launcherUrl = rtrim(getenv("OPENVRE_K8S_LAUNCHER_URL") ?: "", "/");
        $this->launcherToken = getenv("OPENVRE_K8S_LAUNCHER_TOKEN") ?: "";
        $this->runAsUid = (int)(getenv("OPENVRE_K8S_RUN_AS_UID") ?: 1000);
        $this->runAsGid = (int)(getenv("OPENVRE_K8S_RUN_AS_GID") ?: 1000);

        // Allow per-tool overrides: the tool's Mongo document can specify a custom
        // container image and extra environment variables via $jobOptions.
        if (is_array($jobOptions)) {
            if (!empty($jobOptions["image"])) {
                $this->jobImage = (string)$jobOptions["image"];
            }
            if (!empty($jobOptions["env"]) && is_array($jobOptions["env"])) {
                $this->jobEnv = $jobOptions["env"];
            }
        }

        // If a submission script path was provided, immediately create and submit
        // the Kubernetes Job. When $cl is false, this instance is used only for
        // status queries (getRunningJobInfo, status, stop).
        if ($cl !== false) {
            $this->workDir = $workDir;
            $this->command = $cl;
            $this->jobname = $jobname ? $jobname : basename($cl);
            $this->runCom($cpu, $mem);
        }
    }

    // ---------------------------------------------------------------
    // Job name generation
    // ---------------------------------------------------------------

    /**
     * Converts a human-readable job name into a Kubernetes-safe DNS name.
     * Kubernetes Job names must be lowercase, alphanumeric + hyphens, max 63 chars.
     * Appends a random 8-char suffix to guarantee uniqueness across runs.
     */
    private function sanitizeName($name)
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

    // ---------------------------------------------------------------
    // Job creation and submission
    // ---------------------------------------------------------------

    /**
     * Builds a Kubernetes Job manifest and submits it to kubectl-runner.
     *
     * @param int $cpu  Number of CPU cores (request and limit)
     * @param int $mem  Memory in GB (0 = default 4Gi)
     */
    private function runCom($cpu, $mem)
    {
        // Abort if no container image is configured — there's nothing to run.
        if ($this->jobImage === "") {
            $this->stderr = "OPENVRE_K8S_JOB_IMAGE is not set";
            $_SESSION['errorData']['Error'][] = $this->stderr;
            return;
        }

        // Abort if kubectl-runner URL is not configured — we cannot submit jobs.
        if ($this->launcherUrl === "") {
            $this->stderr = "OPENVRE_K8S_LAUNCHER_URL is not set";
            $_SESSION['errorData']['Error'][] = $this->stderr;
            return;
        }

        // Generate a unique, K8s-safe Job name and use it as the OpenVRE "pid".
        $jobName = $this->sanitizeName($this->jobname);
        $this->pid = $jobName;

        $scriptPath = $this->command;
        $workDir = $this->workDir;
        $cpuRequest = max(1, (int)$cpu);
        $cpuLimit = max(1, (int)$cpu);
        $memLimit = ((int)$mem > 0 ? ((int)$mem . "Gi") : "4Gi");

        // Build the full Kubernetes Job manifest as a PHP associative array.
        // This will be serialized to YAML before submission to kubectl-runner.
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
                // Auto-delete the Job object N seconds after it finishes (success or failure).
                "ttlSecondsAfterFinished" => (int)(getenv("OPENVRE_K8S_JOB_TTL") ?: 120),

                // Kill the Job if it runs longer than this (prevents stuck/hung jobs).
                "activeDeadlineSeconds"  => (int)(getenv("OPENVRE_K8S_JOB_DEADLINE") ?: 86400),

                // Do not retry on failure — mark as Failed immediately.
                "backoffLimit" => 0,

                "template" => array(
                    "spec" => array(
                        // Pod should not restart — one attempt only.
                        "restartPolicy" => "Never",

                        "containers" => array(
                            array(
                                "name" => "tool-runner",
                                "image" => $this->jobImage,

                                // The pod runs the submission script (generated by Tooljob)
                                // from the working directory. Both are passed as env vars.
                                "command" => array("bash", "-lc", "cd \"\$OPENVRE_WORKDIR\" && bash \"\$OPENVRE_SUBMIT_SCRIPT\""),

                                "env" => array(
                                    array("name" => "OPENVRE_WORKDIR", "value" => $workDir),
                                    array("name" => "OPENVRE_SUBMIT_SCRIPT", "value" => $scriptPath),
                                ),

                                "resources" => array(
                                    "requests" => array("cpu" => (string)$cpuRequest),
                                    "limits" => array("cpu" => (string)$cpuLimit, "memory" => $memLimit)
                                ),

                                "securityContext" => array(
                                    "allowPrivilegeEscalation" => false,
                                ),

                                // Mount shared storage so the Job can read inputs and write outputs
                                // to the same PVCs used by the frontend and SGE pods.
                                "volumeMounts" => array(
                                    array("name" => "shared-data", "mountPath" => "/shared_data"),
                                    array("name" => "tools", "mountPath" => "/var/www/html/openVRE/public/tools")
                                )
                            )
                        ),

                        // Run the container as a non-root user with a fixed UID/GID
                        // to match file ownership on the shared PVCs.
                        "securityContext" => array(
                            "runAsUser" => max(1, $this->runAsUid),
                            "runAsGroup" => max(1, $this->runAsGid),
                            "fsGroup" => max(1, $this->runAsGid)
                        ),

                        // Attach the two shared PVCs as volumes.
                        "volumes" => array(
                            array("name" => "shared-data", "persistentVolumeClaim" => array("claimName" => $this->sharedPvc)),
                            array("name" => "tools", "persistentVolumeClaim" => array("claimName" => $this->toolsPvc))
                        )
                    )
                )
            )
        );

        // Inject tool-specific environment variables (e.g. FEM_ACCESS_TOKEN, FEM_API_PREFIX)
        // from the tool's Mongo definition into the Job pod's container env list.
        if (!empty($this->jobEnv)) {
            $envList =& $manifest["spec"]["template"]["spec"]["containers"][0]["env"];
            if (is_array($envList)) {
                foreach ($this->jobEnv as $k => $v) {
                    if (!is_string($k) || $k === "") {
                        continue;
                    }
                    if ($v === null || $v === "") {
                        continue;
                    }
                    $envList[] = array(
                        "name" => (string)$k,
                        "value" => (string)$v
                    );
                }
            }
        }

        // Serialize the manifest array to YAML for submission to kubectl-runner.
        $yaml = $this->arrayToYaml($manifest);

        // Submit Job via kubectl-runner proxy.
        $this->fullcommand = "POST " . $this->launcherUrl . "/jobs";
        logger("K8s job submission via kubectl-runner '" . $this->fullcommand . "'");
        $response = $this->launcherRequest("POST", "/jobs", array(
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

    // ---------------------------------------------------------------
    // HTTP client: kubectl-runner proxy
    // ---------------------------------------------------------------

    /**
     * Sends an HTTP request to the kubectl-runner proxy service.
     * This is the sole communication channel with Kubernetes.
     *
     * @param string     $method   HTTP method (GET, POST, DELETE)
     * @param string     $path     API path (e.g. "/jobs", "/jobs/{name}")
     * @param array|null $payload  JSON-serializable body (for POST)
     * @return array     ["ok" => bool, "error" => string, "data" => array]
     */
    private function launcherRequest($method, $path, $payload = null)
    {
        if ($this->launcherUrl === "") {
            return array("ok" => false, "error" => "OPENVRE_K8S_LAUNCHER_URL is not set");
        }
        if (!function_exists("curl_init")) {
            return array("ok" => false, "error" => "PHP curl extension is required");
        }

        $url = $this->launcherUrl . $path;
        $ch = curl_init($url);
        $headers = array("Content-Type: application/json");

        // Authenticate to kubectl-runner with a Bearer token.
        if ($this->launcherToken !== "") {
            $headers[] = "Authorization: Bearer " . $this->launcherToken;
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
            return array("ok" => false, "error" => "kubectl-runner request failed: " . $err);
        }
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = json_decode($raw, true);

        // Non-2xx status from kubectl-runner indicates an error.
        if ($code < 200 || $code >= 300) {
            $msg = is_array($json) && isset($json["error"]) ? $json["error"] : ("HTTP " . $code . " from kubectl-runner");
            return array("ok" => false, "error" => $msg);
        }
        if (!is_array($json)) {
            return array("ok" => false, "error" => "Invalid JSON response from kubectl-runner");
        }
        // kubectl-runner returns {"ok": false, "error": "..."} on application-level errors.
        if (isset($json["ok"]) && !$json["ok"]) {
            return array("ok" => false, "error" => isset($json["error"]) ? $json["error"] : "kubectl-runner error");
        }
        return array("ok" => true, "data" => $json);
    }

    // ---------------------------------------------------------------
    // YAML serializer (PHP array -> YAML string)
    // ---------------------------------------------------------------

    /**
     * Converts a nested PHP associative array into a YAML string.
     * Used to serialize the Job manifest before sending it to kubectl-runner.
     * This avoids requiring the Symfony YAML or ext-yaml extension.
     */
    private function arrayToYaml($data, $indent = 0)
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
                // Scalar value.
                $yaml .= $spaces . $key . ": " . $this->yamlScalar($value) . "\n";
            }
        }
        return $yaml;
    }

    /**
     * Formats a single scalar value for YAML output.
     * Booleans become true/false, numbers stay numeric, strings are double-quoted.
     */
    private function yamlScalar($value)
    {
        if (is_bool($value)) return $value ? "true" : "false";
        if (is_numeric($value)) return (string)$value;
        $escaped = str_replace('"', '\"', (string)$value);
        return '"' . $escaped . '"';
    }

    // ---------------------------------------------------------------
    // Job status and lifecycle queries
    // ---------------------------------------------------------------

    /**
     * Retrieves the current state of a running Kubernetes Job by its name (pid).
     *
     * Called by OpenVRE's workspace polling loop to determine whether a tool
     * execution is still in progress. Returns an array with "pid", "state",
     * and "job_name" if the job is still active, or an empty array if the
     * job is finished/deleted (which tells OpenVRE to finalize outputs).
     *
     * @param  string $pid  Kubernetes Job name
     * @return array        Job info array, or empty if job is done/not found
     */
    public function getRunningJobInfo($pid)
    {
        $job = array();
        if (!$pid) return $job;

        // Query job status via kubectl-runner.
        $response = $this->launcherRequest("GET", "/jobs/" . rawurlencode($pid) . "?namespace=" . rawurlencode($this->namespace));

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

    // ---------------------------------------------------------------
    // Simple accessors (ProcessSGE interface compatibility)
    // ---------------------------------------------------------------

    /** Returns the full API URL used for the last submission (for debug logging). */
    public function getFullCommand()
    {
        return $this->fullcommand;
    }

    /** Returns the Kubernetes Job name (used as OpenVRE's "pid"). */
    public function getPid()
    {
        return $this->pid;
    }

    /** Returns a combined error string if submission failed, or null on success. */
    public function getErr()
    {
        if ($this->stderr) {
            return trim($this->stdout . " " . $this->stderr);
        }
        return null;
    }

    /**
     * Checks whether the Job associated with this instance is still active.
     * Returns true if the Job exists and is running/pending, false otherwise.
     * Used by OpenVRE to decide whether to keep polling.
     */
    public function status()
    {
        if (!$this->pid) return false;

        // Query job status via kubectl-runner.
        $response = $this->launcherRequest("GET", "/jobs/" . rawurlencode($this->pid) . "?namespace=" . rawurlencode($this->namespace));

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

    // ---------------------------------------------------------------
    // Job cancellation / deletion
    // ---------------------------------------------------------------

    /**
     * Deletes a Kubernetes Job (and its pod) by name via kubectl-runner.
     * kubectl-runner uses "Background" propagation policy so the API call
     * returns immediately and Kubernetes garbage-collects the pod asynchronously.
     *
     * @param  string|null $pid  Kubernetes Job name to delete
     * @return array       [bool success, string message]
     */
    public function stop($pid = null)
    {
        if (!$pid) {
            return array(false, "No job id '$pid' given");
        }

        $response = $this->launcherRequest("DELETE", "/jobs/" . rawurlencode($pid) . "?namespace=" . rawurlencode($this->namespace));

        if ($response["ok"] === true) {
            $res = isset($response["data"]["stdout"]) ? trim((string)$response["data"]["stdout"]) : "";
            return array(true, $res);
        }
        return array(false, $response["error"] ?: "Failed to delete kubernetes job");
    }
}
