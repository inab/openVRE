<?php

const SRUN_STATUS = "srun -p %s -n %s --mem=%s -q %s --pty bash";
const SRUN = "srun";
const SBATCH = "sbatch";
const SCANCEL = "scancel ";
const SQUEUE = "squeue ";

use OpenVRE\SSH\RemoteSSH;
use OpenVRE\SSH\VaultClient;
use phpseclib3\Net\SSH2;
use phpseclib3\Crypt\PublicKeyLoader;

class ProcessSlurm {
        private $pid;
        private $fullCommand;
	private $sshHost;
	private $sshUsername;
        private $username;
        private $sshCredentials;
        private $sshRemotePath;
        private $shFile;
        private $workDir;
        private $logFile;
        private $errFile;
        private $remote_system;
        private $jobState = [
                "R"  => "RUNNING",
                "PD" => "PENDING",
                "CG" => "COMPLETING",
                "CD" => "COMPLETED",
                "F"  => "FAILED",
                "TO" => "TIMEOUT",
                "CA" => "CANCELLED",
                "NF" => "NODE_FAIL",
                "S"  => "SUSPENDED",
                "PR" => "PREEMPTED",
                "ST" => "STOPPED"
            ];

        public function __construct($shFile="", $workDir="", $logFile="job_output.log", $errFile="job_error.log", $remote_system="marenostrum") {
                error_log("ProcessSlurm: __construct called with remote_system = $remote_system");

                // Set class properties
                $this->shFile = $shFile;
                $this->workDir = $workDir;
                $this->logFile = $logFile;
                $this->errFile = $errFile;
                $this->remote_system = $remote_system;

                // Retrieve all the Vault/session and ssh credentials
                $vaultUrl      = $_SESSION['userVaultInfo']['vaultUrl']     ?? $GLOBALS['vaultUrl'] ?? null;
                $accessToken   = $_SESSION['userToken']['access_token']  ?? null;
                $vaultRolename = $_SESSION['userVaultInfo']['vaultRolename'] ?? null;
                $username      = $_SESSION['User']['_id'] ?? null;
                $vaultKey      = $_SESSION['userVaultInfo']['vaultKey'] ?? null;

                //error_log("ProcessSlurm: __construct has vaultUrl = $vaultUrl, accessToken = $accessToken, vaultRolename = $vaultRolename, username = $username, vaultKey = $vaultKey");
                
                if (!$vaultUrl || !$vaultKey || !$vaultRolename || !$accessToken || !$username) {
                        $_SESSION['errorData']['Error'][] =
                            "ProcessSlurm: Missing required Vault or session credentials.";
                        return;
                }

                // Create Vault client
                $vaultClient = new VaultClient($vaultUrl, $accessToken, $vaultRolename, $username);
                // Retrieve SSH credentials
                $sshCredentials = $vaultClient->getSSHcredentials($vaultUrl, $vaultKey);
                if (!$sshCredentials || !is_array($sshCredentials)) {
                        $_SESSION['errorData']['Error'][] = "ProcessSlurm: Failed to retrieve SSH credentials from Vault.";
                        return;
                    }

                    $this->sshCredentials = [
                        "private_key" => $sshCredentials['private_key'],
                        "public_key"  => $sshCredentials['public_key'],
                        "username"    => $sshCredentials['username']
                    ];
                    if (
                        empty($this->sshCredentials['private_key']) ||
                        empty($this->sshCredentials['username'])
                    ) {
                        $_SESSION['errorData']['Error'][] = "ProcessSlurm: Incomplete SSH credentials retrieved.";
                        throw new Exception("Incomplete SSH credentials.");
                    }

                    //error_log("ProcessSlurm: SSH credentials retrieved: " . json_encode($this->sshCredentials));

                    $remote_details = Tooljob::getLauncher_SlurmInfo($remote_system);
                    $this->sshHost = $remote_details['server'];
                    $this->sshRemotePath = $remote_details['root_path'];
                    $this->sshUsername = $this->sshCredentials['username'];

                    error_log("ProcessSlurm: SSH connection details: sshHost = $this->sshHost, sshUsername = $this->sshUsername, sshRemotePath = $this->sshRemotePath");

                    if (!empty($this->shFile) && !empty($this->workDir)) {
                        $this->submitJob();
                    }

                }


        public function setFullCommand($remoteSh, $workDir, $remoteOut, $remoteErr): void
        {
                // Remote paths
                error_log("ProcessSlurm: setFullCommand - remoteSh = $remoteSh, workDir = $workDir, remoteOut = $remoteOut, remoteErr = $remoteErr");
                $remoteWorkDir = DataTransfer::synchronizeDestinationDir_MN($this->sshRemotePath, $this->sshUsername);
                $cmd  = "cd \"$remoteWorkDir\" && ";
                $cmd .= "module load singularity/4.1.5  && ";
                $cmd .= 'sbatch --output="' . rtrim($remoteWorkDir, '/') . $remoteOut . '" --error="' . rtrim($remoteWorkDir, '/') . $remoteErr . '" "' . rtrim($remoteWorkDir, '/') . $remoteSh . '"';
                error_log("ProcessSlurm: setFullCommand - fullCommand = $cmd");
        
                $this->fullCommand = $cmd;
        }

        public function submitJob(): array
        {
                if (empty($this->fullCommand)) {
                        $this->setFullCommand($this->shFile, $this->workDir, $this->logFile, $this->errFile);
        }

        try {
                // Execute SLURM job submission command on remote cluster
                $ssh = $this->connectSSH();
                $output = $ssh->exec($this->fullCommand);
                error_log("ProcessSlurm: submitJob: fullCommand = $this->fullCommand");
                error_log("ProcessSlurm: submitJob: output = $output");
                // Extract SLURM Job ID
                preg_match('/Submitted batch job (\d+)/', $output, $matches);
                $pid = $matches[1] ?? null;
                error_log("ProcessSlurm: submitJob: pid = $pid");
                // Store in class property for later use
                $this->pid = $pid;

                return [
                        'success' => true,
                        'pid'     => $pid,
                        'output'  => $output
                ];

        } catch (Exception $e) {
                return [
                        'success' => false,
                        'error'   => $e->getMessage()
                ];
        }
        }

        /**
         * Get the full command that will be executed on the remote cluster.
         * This command string is built by calling setFullCommand() and is
         * used to submit the job to the remote cluster.
         *
         * @return string The full command that will be executed on the remote cluster.
         */
        public function getFullCommand()
        {
                return $this->fullCommand ?? "";
        }

        public function getPid()
        {
                return $this->pid ?? 0;
        }  
        
        public function getErr()
        {
            return $this->err ?? "";
        }

        public function status()
        {
                if (!$this->pid) {
                        return "UNKNOWN";
        }
        $info = $this->getRunningJobInfo($this->pid);
        if (!$info || !isset($info['state'])) {
                return "UNKNOWN";
        }
        return $info['state'];
        }

        private function connectSSH()
        {
                $ssh = new SSH2($this->sshHost);
                $ssh->setTimeout(30);

                $formattedKey = RemoteSSH::formatSSHPrivateKey($this->sshCredentials['private_key']);
                $key = PublicKeyLoader::load($formattedKey);

                if (!$ssh->login($this->sshCredentials['username'], $key)) {
                        error_log("ProcessSlurm: connectSSH: SSH authentication failed. Username: " . $this->sshCredentials['username']);
                        throw new Exception("SSH authentication failed.");
                }

                return $ssh;
        }

        public function getRunningJobs()
        {
        try {
                $ssh = $this->connectSSH();
                $cmd = "squeue -u " . escapeshellarg($this->sshCredentials['username']) . " -o \"%i|%t|%j\"";
                $output = $ssh->exec($cmd);
                if (!$output) return [];
                $lines = explode("\n", trim($output));
                array_shift($lines); // remove header
                $jobs = [];
                foreach ($lines as $line) {
                        if (!trim($line)) continue;
                        list($id, $state, $name) = explode("|", $line);
                        $jobs[] = [
                                "job_id" => trim($id),
                                "state"  => $this->jobState[$state] ?? $state,
                                "name"   => trim($name)
                        ];
                } 
                return $jobs;

        } catch (\Exception $e) {
                return [];
        }
        }
        public function getRunningJobInfo($pid)
        {
                $job = [];
                if (!$pid) {
                        return $job;
                }

                try {
                        $ssh = $this->connectSSH();
                        $cmd = "scontrol show job " . escapeshellarg($pid);
                        $raw = trim($ssh->exec($cmd));

                        if (!$raw || strpos($raw, "JobId=") === false) {
                                logger("ProcessSlurm: getRunningJobInfo: no such job $pid");
                                return $job;   // no such job
                        }

                        // Parse Key=Value fields
                        preg_match_all('/(\w+)=(".*?"|\S+)/', $raw, $matches, PREG_SET_ORDER);

                        foreach ($matches as $m) {
                                $key   = $m[1];
                                $value = trim($m[2], '"');
                                $job[$key] = $value;
                        }

                        $cmd = "squeue -j " . escapeshellarg($pid) . " -h -o \"%i|%t|%M|%D\"";
                        $line = trim($ssh->exec($cmd));

                        if (!$line) {
                                logger("ProcessSlurm: getRunningJobInfo: job not running anymore. State: FINISHING");
                                $job['state'] = "FINISHING";
                                // log message like SGE version:
                                log_addInfo($pid, "Job not running anymore. State: " . $job['state']);
                                // sync remote dir to local if job state is Completed
                                if ($job['state'] == "COMPLETED") {
                                    $remoteDir = DataTransfer::synchronizeDestinationDir_MN($this->sshRemotePath, $this->sshUsername);
                                    logger("ProcessSlurm: getRunningJobInfo: syncing remote dir $remoteDir to local dir $this->workDir");
                                    $sync = RemoteSSH::executeRsyncCommandForWorkingDir($this->sshCredentials, $this->workDir, $remoteDir, $this, null, "download");
                                }
                        } else {
                                list($id, $state, $time, $nodes) = explode("|", $line);
                                // Map SLURM state → readable state
                                $job['state']     = $this->jobState[$state] ?? $state;
                                $job['raw_state'] = $state;
                                $job['time']      = $time;
                                $job['nodes']     = $nodes;
                        }
                        $job['pid'] = $pid;
                        return $job;

                } catch (\Exception $e) {
                        logger("ProcessSlurm: getRunningJobInfo: Exception: " . $e->getMessage());
                        return $job;
                }
        }

        public function cancelJob($pid)
        {
        try {
                $ssh = $this->connectSSH();
                $cmd = "scancel " . escapeshellarg($pid);
                $ssh->exec($cmd);
                return true;

        } catch (\Exception $e) {
                return false;
        }
        }


        public function __destruct(){
                unset($this->sshCredentials);
                unset($this->sshHost);
                unset($this->sshUsername);
                unset($this->sshRemotePath);
        }
}
