<?php

namespace OpenVRE;

use Monolog\Logger;
use OpenVRE\LoggerFactory;


const QSUB = "qsub -S /bin/bash";
const QDEL = "qdel ";
const QSTAT = "qstat ";

class ProcessSGE
{
	private $pid;
	private $command;
	private $workDir;
	private $queue = "srv.q";
	private $cpu = 1;
	private $mem = 0;
	private $logFile = "job_output.log";
	private $errFile = "job_error.log";

	private $jobname;

	private $fullcommand;

	private $stdout;

	private $stderr;

	private $username; //may change depending on FS needs. IRB=www-data. BSC=vre

	private $jobState = array(
		'r'  => "RUNNING",
		't'  => "TRANSFERING",
		'qw' => "PENDING",
		'hqw' => "HOLD",
		'dr' => "DELETING",
		'Eqw' => "ERROR"
	);

	private Logger $logger;

	public function __construct($cl = false, $workDir = "", $queue = "srv.q", $jobname = "", $cpu = 1, $mem = 0, $logFile = "job_output.log", $errFile = "job_error.log")
	{
		$this->logger = LoggerFactory::getLogger("Process SGE interface");
		$current_user = posix_getpwuid(posix_geteuid());
		$this->username  = $current_user['name'];

		if ($cl) {
			$this->workDir = $workDir;
			$this->command = $cl;
			$this->queue   = $queue;
			$this->cpu     = $cpu;
			$this->mem     = $mem;
			$this->logFile = $logFile;
			$this->errFile = $errFile;
			$this->jobname = $jobname ?? basename($cl);
			
			$this->runCom();
		}
	}


	// execute SGE command
	private function runCom()
	{
		$this->setFullCommand();
		$this->logger->info("SGE job submission has CML = '$this->fullcommand'");

		$proc = proc_open($this->fullcommand, [
			1 => ['pipe', 'w'],
			2 => ['pipe', 'w'],
		], $pipes, $this->workDir);
		$this->stdout = stream_get_contents($pipes[1]);
		fclose($pipes[1]);
		$this->stderr = stream_get_contents($pipes[2]);
		fclose($pipes[2]);
		proc_close($proc);

		if (preg_match('/job (\d+)/', $this->stdout, $m)) {
			$this->pid = (int)$m[1];
			$msg = trim(preg_replace('/\s\s+/', ' ', "Job STDOUT returns: " . $this->stdout));
			$this->logger->info($msg);
		} else {
			$msg = trim(preg_replace('/\s\s+/', ' ', "Job STDERR returns: " . $this->stdout . " Error: " . $this->stderr));
			$this->logger->error($msg);
			throw new UnexpectedValueException($msg);
		}
	}


	// build Submit (qsub) command
	public function setFullCommand()
	{
		$workDir = $this->workDir;
		$command = QSUB . " -N '" . $this->jobname . "' -wd $workDir -q " . $this->queue . " -o " . $this->logFile . " -e " . $this->errFile;
		if ($this->cpu > 1) {
			$command .= " -l cpu=" . $this->cpu;
		}

		$command .= " " . $this->command;
		$this->fullcommand = $command;
	}


	//list user Jobs
	public function getRunningJobInfo($pid)
	{
		$job = array();
		if (! $this->pid)
			$this->pid = $pid;
		$cmd = QSTAT . " -j $pid | awk '$0~/:/ {print $0}'";
		exec($cmd, $jobInfo);

		if (count($jobInfo) == 0)
			return $job;

		foreach ($jobInfo as $line) {
			$fields = explode(":", $line);
			$k = trim(array_shift($fields));
			$v = trim(implode(":", $fields));
			$job[$k] = $v;
		}
		$cmd = QSTAT . " -u $this->username | grep $pid | awk '$1 ~ /[0-9]+/ {print $1\"\t\"$5\"\t\"$6 $7}'";
		exec($cmd, $jobState);

		if (is_null($jobState[0])) {
			$job['state'] = "FINISHING";
		} else {
			list($pid, $state) = explode("\t", $jobState[0]);
			$job['state'] = $this->jobState[$state];
		}
		$job['pid'] = $pid;

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
		return $this->stdout . $this->stderr;
	}

	public function status()
	{
		# No need to specify a queue, pids are unique in the same SGE system.
		$pidForm = sprintf("%7s", $this->pid);
		$command = QSTAT . ' -u ' . $this->username . ' | grep "^' . $pidForm . '"';
		exec($command, $op);

		return isset($op[0]);
	}


	public function stop($pid = null)
	{
		if (!$pid) {
			return array(false, "No job id '$pid' given");
		}
		$command = QDEL . ' ' . $pid;
		exec($command, $r);
		$res = join(" ", $r);
		log_addInfo($jobid, "SGE/qdel: " . $res);
		if (preg_match('/has deleted/i', $res) || preg_match('/registered the job \d+ for deletion/', $res)) {
			return array(true, $res);
		} else {
			return array(false, $res);
		}
	}
}
