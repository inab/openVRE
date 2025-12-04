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
        private $jobState = Array (
		
		'PD' => "PENDING",
        	'R'  => "RUNNING", 
		'CG' => "COMPLETING",
	        'CD' => "COMPLETED",
	        'F'  => "FAILED",
	        'TO' => "TIMEOUT",
	        'NF' => "NODE_FAIL",
	        'CA' => "CANCELLED",
	        'RE' => "REQUEUED",
	        'S'  => "SUSPENDED",
    
	);
        public function __construct($shFile, $workDir, $logFile, $errFile, $remote_system){
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

                error_log("ProcessSlurm: __construct has vaultUrl = $vaultUrl, accessToken = $accessToken, vaultRolename = $vaultRolename, username = $username, vaultKey = $vaultKey");
                
                if (!$vaultUrl || !$vaultKey || !$vaultRolename || !$accessToken || !$username) {
                        $_SESSION['errorData']['Error'][] =
                            "ProcessSlurm: Missing required Vault or session credentials.";
                        throw new Exception("Missing Vault/session parameters.");
                }

                // Create Vault client
                $vaultClient = new VaultClient($vaultUrl, $accessToken, $vaultRolename, $username);
                // Retrieve SSH credentials
                $sshCredentials = $vaultClient->getSSHcredentials($vaultUrl, $vaultKey);
                if (!$sshCredentials || !is_array($sshCredentials)) {
                        $_SESSION['errorData']['Error'][] = "ProcessSlurm: Failed to retrieve SSH credentials from Vault.";
                        throw new Exception("SSH credentials not found.");
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

                    error_log("ProcessSlurm: SSH credentials retrieved: " . json_encode($this->sshCredentials));

                    $remote_details = Tooljob::getLauncher_SlurmInfo($remote_system);
                    $this->sshHost = $remote_details['server'];
                    $this->sshRemotePath = $remote_details['root_path'];
                    $this->sshUsername = $this->sshCredentials['username'];

                    error_log("ProcessSlurm: SSH connection details: sshHost = $this->sshHost, sshUsername = $this->sshUsername, sshRemotePath = $this->sshRemotePath");


                    $this->submitJob();

                }


        public function setFullCommand($remoteSh, $workDir, $remoteOut, $remoteErr): void
        {
                // Remote paths
                error_log("ProcessSlurm: setFullCommand - remoteSh = $remoteSh, workDir = $workDir, remoteOut = $remoteOut, remoteErr = $remoteErr");
                $remoteWorkDir = DataTransfer::synchronizeDestinationDir_MN($this->sshRemotePath, $this->sshUsername);
                $cmd  = "cd \"$remoteWorkDir\" && ";
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
        try {
                $ssh = $this->connectSSH();

                $cmd = "squeue -j " . escapeshellarg($pid) . " -h -o \"%i|%t|%j|%u|%M|%D\"";
                $output = trim($ssh->exec($cmd));

                if (!$output) {
                        return null; // job not found
                }

                list($id, $state, $name, $user, $time, $nodes) = explode("|", $output);

                return [
                        "job_id" => trim($id),
                        "state"  => $this->jobState[$state] ?? $state,
                        "raw_state" => $state,
                        "name"   => trim($name),
                        "user"   => trim($user),
                        "time"   => trim($time),
                        "nodes"  => trim($nodes)
                ];

        } catch (\Exception $e) {
                return null;
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
