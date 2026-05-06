<?php

class ProcessK8s
{
    private $pid = "";
    private $command = "";
    private $workDir = "";
    private $jobname = "";
    private $fullcommand = "";
    private $stdout = "";
    private $stderr = "";
    private $namespace = "fedcomp";
    private $jobImage = "";
    private $sharedPvc = "dashboard-frontend-sgecore-shareddata";
    private $toolsPvc = "dashboard-frontend-tools";
    private $launcherUrl = "";
    private $launcherToken = "";
    private $k8sApiServer = "";
    private $k8sApiToken = "";
    private $k8sApiCaFile = "/var/run/secrets/kubernetes.io/serviceaccount/ca.crt";
    private $runAsUid = 1000;
    private $runAsGid = 1000;
    private $jobEnv = array();

    private $jobState = array(
        "Running" => "RUNNING",
        "Pending" => "PENDING",
        "Succeeded" => "FINISHING",
        "Failed" => "ERROR",
        "NotFound" => "FINISHING",
    );

    public function __construct($cl = false, $workDir = "", $queue = "", $jobname = "", $cpu = 1, $mem = 0, $logFile = "job_output.log", $errFile = "job_error.log", $jobOptions = array())
    {
        $this->namespace = getenv("OPENVRE_K8S_NAMESPACE") ?: "fedcomp";
        $this->jobImage = getenv("OPENVRE_K8S_JOB_IMAGE") ?: "";
        $this->sharedPvc = getenv("OPENVRE_K8S_SHARED_PVC") ?: "dashboard-frontend-sgecore-shareddata";
        $this->toolsPvc = getenv("OPENVRE_K8S_TOOLS_PVC") ?: "dashboard-frontend-tools";
        $this->launcherUrl = rtrim(getenv("OPENVRE_K8S_LAUNCHER_URL") ?: "", "/");
        $this->launcherToken = getenv("OPENVRE_K8S_LAUNCHER_TOKEN") ?: "";
        $this->runAsUid = (int)(getenv("OPENVRE_K8S_RUN_AS_UID") ?: 1000);
        $this->runAsGid = (int)(getenv("OPENVRE_K8S_RUN_AS_GID") ?: 1000);
        $k8sHost = getenv("KUBERNETES_SERVICE_HOST") ?: "";
        $k8sPort = getenv("KUBERNETES_SERVICE_PORT_HTTPS") ?: (getenv("KUBERNETES_SERVICE_PORT") ?: "443");
        if ($k8sHost !== "") {
            $this->k8sApiServer = "https://" . $k8sHost . ":" . $k8sPort;
        }
        $tokenPath = "/var/run/secrets/kubernetes.io/serviceaccount/token";
        if (is_readable($tokenPath)) {
            $this->k8sApiToken = trim((string)file_get_contents($tokenPath));
        }

        if (is_array($jobOptions)) {
            if (!empty($jobOptions["image"])) {
                $this->jobImage = (string)$jobOptions["image"];
            }
            if (!empty($jobOptions["env"]) && is_array($jobOptions["env"])) {
                $this->jobEnv = $jobOptions["env"];
            }
        }

        if ($cl !== false) {
            $this->workDir = $workDir;
            $this->command = $cl;
            $this->jobname = $jobname ? $jobname : basename($cl);
            $this->runCom($cpu, $mem);
        }
    }

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

    private function runCom($cpu, $mem)
    {
        if ($this->jobImage === "") {
            $this->stderr = "OPENVRE_K8S_JOB_IMAGE is not set";
            $_SESSION['errorData']['Error'][] = $this->stderr;
            return;
        }

        $jobName = $this->sanitizeName($this->jobname);
        $this->pid = $jobName;

        $scriptPath = $this->command;
        $workDir = $this->workDir;
        $cpuRequest = max(1, (int)$cpu);
        $cpuLimit = max(1, (int)$cpu);
        $memLimit = ((int)$mem > 0 ? ((int)$mem . "Gi") : "4Gi");

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
                "ttlSecondsAfterFinished" => (int)(getenv("OPENVRE_K8S_JOB_TTL") ?: 120),
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
                                "volumeMounts" => array(
                                    array("name" => "shared-data", "mountPath" => "/shared_data"),
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

        // Inject tool runtime environment variables (e.g. FEM_ACCESS_TOKEN) into the pod.
        // This is critical for kubernetes_native where we execute directly from the tool image.
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

        $yaml = $this->arrayToYaml($manifest);
        if ($this->launcherUrl !== "") {
            $this->fullcommand = "POST " . $this->launcherUrl . "/jobs";
            logger("K8s job submission via launcher endpoint '" . $this->fullcommand . "'");
            $response = $this->launcherRequest("POST", "/jobs", array(
                "namespace" => $this->namespace,
                "manifest" => $yaml
            ));
        } else {
            $this->fullcommand = "POST " . $this->k8sApiServer . "/apis/batch/v1/namespaces/" . $this->namespace . "/jobs";
            logger("K8s job submission via in-cluster API '" . $this->fullcommand . "'");
            $response = $this->k8sRequest("POST", "/apis/batch/v1/namespaces/" . rawurlencode($this->namespace) . "/jobs", $yaml, "application/yaml");
        }
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
            return array("ok" => false, "error" => "Launcher request failed: " . $err);
        }
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = json_decode($raw, true);
        if ($code < 200 || $code >= 300) {
            $msg = is_array($json) && isset($json["error"]) ? $json["error"] : ("HTTP " . $code . " from launcher");
            return array("ok" => false, "error" => $msg);
        }
        if (!is_array($json)) {
            return array("ok" => false, "error" => "Invalid JSON response from launcher");
        }
        if (isset($json["ok"]) && !$json["ok"]) {
            return array("ok" => false, "error" => isset($json["error"]) ? $json["error"] : "Launcher error");
        }
        return array("ok" => true, "data" => $json);
    }

    private function k8sRequest($method, $path, $payload = null, $contentType = "application/json")
    {
        // Fallback refresh: in some PHP-FPM request contexts the constructor may
        // run before projected service-account files/env are ready. Re-read just
        // before Kubernetes API calls.
        if ($this->k8sApiServer === "") {
            $k8sHost = getenv("KUBERNETES_SERVICE_HOST") ?: "";
            $k8sPort = getenv("KUBERNETES_SERVICE_PORT_HTTPS") ?: (getenv("KUBERNETES_SERVICE_PORT") ?: "443");
            if ($k8sHost !== "") {
                $this->k8sApiServer = "https://" . $k8sHost . ":" . $k8sPort;
            }
        }
        if ($this->k8sApiToken === "") {
            $tokenPath = "/var/run/secrets/kubernetes.io/serviceaccount/token";
            if (is_readable($tokenPath)) {
                $this->k8sApiToken = trim((string)file_get_contents($tokenPath));
            }
        }
        if ($this->k8sApiServer === "" || $this->k8sApiToken === "") {
            return array("ok" => false, "error" => "Kubernetes in-cluster API/token not available");
        }
        if (!function_exists("curl_init")) {
            return array("ok" => false, "error" => "PHP curl extension is required");
        }

        $url = $this->k8sApiServer . $path;
        $ch = curl_init($url);
        $headers = array(
            "Authorization: Bearer " . $this->k8sApiToken
        );
        if ($payload !== null) {
            $headers[] = "Content-Type: " . $contentType;
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        if (is_file($this->k8sApiCaFile)) {
            curl_setopt($ch, CURLOPT_CAINFO, $this->k8sApiCaFile);
        }
        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }

        $raw = curl_exec($ch);
        if ($raw === false) {
            $err = curl_error($ch);
            curl_close($ch);
            return array("ok" => false, "error" => "Kubernetes API request failed: " . $err);
        }
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = json_decode($raw, true);
        if ($code < 200 || $code >= 300) {
            $msg = "";
            if (is_array($json) && isset($json["message"])) {
                $msg = (string)$json["message"];
            }
            if ($msg === "") {
                $msg = "HTTP " . $code . " from Kubernetes API";
            }
            return array("ok" => false, "error" => $msg);
        }

        return array("ok" => true, "data" => array(
            "stdout" => $raw,
            "stderr" => "",
            "job" => $raw,
            "exists" => true
        ));
    }

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
                    $yaml .= $spaces . $key . ":\n" . $this->arrayToYaml($value, $indent + 1);
                } else {
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

    private function yamlScalar($value)
    {
        if (is_bool($value)) return $value ? "true" : "false";
        if (is_numeric($value)) return (string)$value;
        $escaped = str_replace('"', '\"', (string)$value);
        return '"' . $escaped . '"';
    }

    public function getRunningJobInfo($pid)
    {
        $job = array();
        if (!$pid) return $job;
        if ($this->launcherUrl !== "") {
            $response = $this->launcherRequest("GET", "/jobs/" . rawurlencode($pid) . "?namespace=" . rawurlencode($this->namespace));
        } else {
            $response = $this->k8sRequest("GET", "/apis/batch/v1/namespaces/" . rawurlencode($this->namespace) . "/jobs/" . rawurlencode($pid), null);
            if ($response["ok"] !== true && strpos((string)$response["error"], "not found") !== false) {
                return array();
            }
            if ($response["ok"] === true) {
                $response["data"]["exists"] = true;
                $response["data"]["job"] = isset($response["data"]["stdout"]) ? $response["data"]["stdout"] : "";
            }
        }
        if ($response["ok"] !== true) {
            // Treat lookup errors as "not running" to avoid infinite FINISHING state.
            return array();
        }
        $exists = isset($response["data"]["exists"]) ? (bool)$response["data"]["exists"] : false;
        if (!$exists) {
            // Job already removed (e.g. TTL cleanup) => finalize in OpenVRE.
            return array();
        }
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

        // Important: OpenVRE's workspace polling treats "empty job info" as
        // "job not running anymore" (to finalize outputs). For Kubernetes,
        // return an empty array once the Job is finished.
        if ($state === "Succeeded" || $state === "Failed") {
            return array();
        }

        $job['pid'] = $pid;
        $job['state'] = $this->jobState[$state];
        $job['job_name'] = $pid;
        return $job;
    }

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
        if ($this->launcherUrl !== "") {
            $response = $this->launcherRequest("GET", "/jobs/" . rawurlencode($this->pid) . "?namespace=" . rawurlencode($this->namespace));
        } else {
            $response = $this->k8sRequest("GET", "/apis/batch/v1/namespaces/" . rawurlencode($this->namespace) . "/jobs/" . rawurlencode($this->pid), null);
            if ($response["ok"] !== true && strpos((string)$response["error"], "not found") !== false) {
                return false;
            }
            if ($response["ok"] === true) {
                $response["data"]["exists"] = true;
                $response["data"]["job"] = isset($response["data"]["stdout"]) ? $response["data"]["stdout"] : "";
            }
        }
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
        if (!empty($status["succeeded"]) || !empty($status["failed"])) {
            return false;
        }

        return true;
    }

    public function stop($pid = null)
    {
        if (!$pid) {
            return array(false, "No job id '$pid' given");
        }
        if ($this->launcherUrl !== "") {
            $response = $this->launcherRequest("DELETE", "/jobs/" . rawurlencode($pid) . "?namespace=" . rawurlencode($this->namespace));
        } else {
            $deletePayload = json_encode(array(
                "apiVersion" => "batch/v1",
                "kind" => "DeleteOptions",
                "propagationPolicy" => "Background"
            ));
            $response = $this->k8sRequest("DELETE", "/apis/batch/v1/namespaces/" . rawurlencode($this->namespace) . "/jobs/" . rawurlencode($pid), $deletePayload, "application/json");
        }
        if ($response["ok"] === true) {
            $res = isset($response["data"]["stdout"]) ? trim((string)$response["data"]["stdout"]) : "";
            return array(true, $res);
        }
        return array(false, $response["error"] ?: "Failed to delete kubernetes job");
    }
}

