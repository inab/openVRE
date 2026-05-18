<?php

namespace OpenVRE;

use Exception;
use Monolog\Logger;
use OpenVRE\VaultClientFactory;

const SRUN_STATUS = "srun -p %s -n %s --mem=%s -q %s --pty bash";
const SRUN = "srun";
const SBATCH = "sbatch";
const SCANCEL = "scancel ";
const SQUEUE = "squeue ";

use phpseclib3\Net\SSH2;
use phpseclib3\Crypt\PublicKeyLoader;
use UnexpectedValueException;

class ProcessSlurm
{
    private $pid;
    private $fullCommand;
    private $sshHost;
    private $sshUsername;
    private $sshCredentials;
    private $sshRemotePath;
    private $shFile;
    private $workDir;
    private $logFile;
    private $errFile;
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
    private Logger $logger;

    public function __construct($shFile = "", $workDir = "", $logFile = "job_output.log", $errFile = "job_error.log", $remote_system = "marenostrum")
    {
        $this->logger = LoggerFactory::getLogger("Tool job");

        // Set class properties
        $this->shFile = $shFile;
        $this->workDir = $workDir;
        $this->logFile = $logFile;
        $this->errFile = $errFile;

        // Create Vault client
        $vaultClient = VaultClientFactory::create();
        $sshCredentials = $vaultClient->retrieveDatafromVault(Site::SSH);
        if (empty($sshCredentials) || empty($sshCredentials['private_key']) || empty($sshCredentials['username'])) {
            $this->logger->error("Incomplete SSH credentials retrieved.");
            throw new UnexpectedValueException("Empty SSH credentials from Vault.");
        }

        $this->sshCredentials = [
            "private_key" => $sshCredentials['private_key'],
            "username"    => $sshCredentials['username']
        ];
        if (
            empty($this->sshCredentials['private_key']) ||
            empty($this->sshCredentials['username'])
        ) {
            $this->logger->error("Incomplete SSH credentials retrieved.");
            throw new UnexpectedValueException("Incomplete SSH credentials.");
        }

        $remote_details = Tooljob::getLauncher_SlurmInfo($remote_system);
        $this->sshHost = $remote_details['server'];
        $this->sshRemotePath = $remote_details['root_path'];
        $this->sshUsername = $this->sshCredentials['username'];

        if (!empty($this->shFile) && !empty($this->workDir)) {
            $this->submitJob();
        }
    }


    public function setFullCommand($remoteSh, $workDir, $remoteOut, $remoteErr): void
    {
        $this->logger->debug("ProcessSlurm: setFullCommand - remoteSh = $remoteSh, workDir = $workDir, remoteOut = $remoteOut, remoteErr = $remoteErr");

        $remoteWorkDir = DataTransfer::synchronizeDestinationDir_MN($this->sshRemotePath, $this->sshUsername);
        $cmd  = "cd \"$remoteWorkDir\" && ";
        $cmd .= "module load singularity/4.1.5  && ";
        $cmd .= 'sbatch --output="' . rtrim($remoteWorkDir, '/') . $remoteOut . '" --error="' . rtrim($remoteWorkDir, '/') . $remoteErr . '" "' . rtrim($remoteWorkDir, '/') . $remoteSh . '"';

        $this->logger->debug("ProcessSlurm: setFullCommand - fullCommand = $cmd");
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
            $this->logger->debug("ProcessSlurm: submitJob: fullCommand = $this->fullCommand");
            $this->logger->debug("ProcessSlurm: submitJob: output = $output");
            // Extract SLURM Job ID
            preg_match('/Submitted batch job (\d+)/', $output, $matches);
            $pid = $matches[1] ?? null;
            $this->logger->debug("ProcessSlurm: submitJob: pid = $pid");
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
            $this->logger->error("ProcessSlurm: connectSSH: SSH authentication failed. Username: " . $this->sshCredentials['username']);
            throw new UnexpectedValueException("SSH authentication failed.");
        }

        return $ssh;
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
                $this->logger->debug("ProcessSlurm: getRunningJobInfo: no such job $pid");
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
                $this->logger->debug("ProcessSlurm: getRunningJobInfo: job not running anymore. State: FINISHING");
                $job['state'] = "FINISHING";
                // log message like SGE version:
                $this->logger->debug("ProcessSlurm: getRunningJobInfo: log message added");
                // sync remote dir to local if job state is Completed
                if ($job['state'] == "COMPLETED") {
                    $remoteDir = DataTransfer::synchronizeDestinationDir_MN($this->sshRemotePath, $this->sshUsername);
                    $this->logger->debug("ProcessSlurm: getRunningJobInfo: syncing remote dir $remoteDir to local dir $this->workDir");
                    RemoteSSH::executeRsyncCommandForWorkingDir($this->sshCredentials, $this->workDir, $remoteDir, $this, null, "download");
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


    public function __destruct()
    {
        unset($this->sshCredentials);
        unset($this->sshHost);
        unset($this->sshUsername);
        unset($this->sshRemotePath);
    }
}
