<?php

define ("QSUB", "qsub -S /bin/bash" );
define ("QDEL", "qdel ");
define ("QSTAT", "qstat ");

class ProcessSGE{
	private $pid;
	private $command;
	private $workDir;
	private $queue="srv.q";
	private $cpu=1;
	private $mem=0;

	private $username; //may change depending on FS needs. IRB=www-data. BSC=vre

	private $jobState = Array (
			'r'  => "RUNNING",
			't'  => "TRANSFERING",
			'qw' => "PENDING",
			'hqw'=> "HOLD",
			'dr' => "DELETING",
			'Eqw'=> "ERROR");

	public function __construct($cl=false,$workDir="",$queue="srv.q",$jobname="",$cpu=1,$mem=0){

		$current_user = posix_getpwuid(posix_geteuid());
        $this->username  = $current_user['name']; 

		if ($cl != false){
			$this->workDir = $workDir;
			$this->command = $cl;
			$this->queue   = $queue;
			$this->cpu	 = $cpu;
			$this->mem	 = $mem; 
	
			if ($jobname)
				$this->jobname = $jobname;
			else
				$this->jobname = basename($cl);
            
            $this->runCom();
        }
        return $this;
	}


	// execute SGE command
	private function runCom(){
		$this->setFullCommand();
        $command = $this->fullcommand;
		logger("SGE job submission has CML = '$command'");

        //chdir($this->workDir);
        //exec($command,$op);

        $proc = proc_open($command,[
			 1 => ['pipe','w'],
			 2 => ['pipe','w'],
			],$pipes, $this->workDir);
		$this->stdout = stream_get_contents($pipes[1]);
		fclose($pipes[1]);
		$this->stderr = stream_get_contents($pipes[2]);
		fclose($pipes[2]);
        proc_close($proc);

        if (preg_match('/job (\d+)/',$this->stdout,$m)){
				$this->pid=(int)$m[1];
				$msg = trim(preg_replace('/\s\s+/', ' ', "Job STDOUT returns: ".$this->stdout));
				logger($msg);
		}else{
				$this->pid=0;
				$msg = trim(preg_replace('/\s\s+/', ' ', "Job STDERR returns: ".$this->stdout." Error: ". $this->stderr));
				logger($msg);
				$_SESSION['errorData']['Error'][] = $this->stdout." Error: ". $this->stderr;
		}
	}

	// build Submit (qsub) command
	public function setFullCommand(){
		$workDir = $this->workDir;
		$command = QSUB." -N '".$this->jobname."' -wd $workDir -q ".$this->queue;
		if ($this->cpu > 1)
			$command .= " -l cpu=". $this->cpu;
		$command .= " ".$this->command;
		$this->fullcommand = $command;
	}


	//list all VRE Jobs  (not used anymore)
	public function getRunningJobs(){
		$jobs=Array();
		$command = QSTAT." -u $this->username | awk '$1 ~ /[0-9]+/ {print $1\"\t\"$5\"\t\"$6 $7}'";
		exec($command,$queueJobs);

		if (!isset($queueJobs[0])){
            log_addInfo($jobid,"Job not running anymore");
			return $jobs;
        }else{
			foreach ($queueJobs as $jobLine){
				list($pid,$state,$start)=explode("\t",$jobLine);
				$cmd = QSTAT. " -j $pid | grep job_name | cut -d: -f2 | tr -d \" \"";
				exec($cmd,$jobName);
				$jobs[$pid]=Array(
					'name'  => $jobName[0],
					'start' => $start,
					'state' => $this->jobState[$state]
		   		);
			}
		}
	}

	//list user Jobs
	public function getRunningJobInfo($pid){
		$job=Array();
		if (! $pid)
		return $job;
		if (! $this->pid)
			$this->pid = $pid;
		$cmd = QSTAT. " -j $pid | awk '$0~/:/ {print $0}'";
		exec($cmd,$jobInfo);

		if(count($jobInfo) == 0 )
		return $job;

		foreach ($jobInfo as $line){
			$fields =explode(":",$line);
			$k = trim(array_shift($fields));
			$v = trim(implode(":",$fields));
			$job[$k]=$v;
		}
		$cmd = QSTAT." -u $this->username | grep $pid | awk '$1 ~ /[0-9]+/ {print $1\"\t\"$5\"\t\"$6 $7}'";
		exec($cmd,$jobState);

		if (!isset($jobState[0]) ){
			$job['state']="FINISHING";
            log_addInfo($jobid,"Job not running anymore. State: ".$job['state']);
		}else{
			list($pid,$state,$start) = explode("\t",$jobState[0]);
			$job['state']= $this->jobState[$state];
		}
		$job['pid']=$pid;
	
	    return $job;
	}


	public function getFullCommand(){
		return $this->fullcommand;
	}

	public function getPid(){
		return $this->pid;
	}

	public function getErr(){
		if ($this->stderr)
			return $this->stout.$this->stderr;
		else
			return NULL;
	}
	public function status(){
		# No need to specify a queue, pids are unique in the same SGE system.
		$pidForm = sprintf("%7s",$this->pid);
		$command = QSTAT.' -u '.$this->username.' | grep "^'.$pidForm.'"';
		exec($command,$op);

		if (!isset($op[0]))return false;
		else return true;
	}

	public function start(){
		if ($this->command != '')$this->runCom();
		else return true;
	}

    public function stop($pid=NULL){
        if (!$pid){
            return array(false,"No job id '$pid' given");
        }
		$command = QDEL.' '.$pid;
        exec($command,$r);
        $res = join(" ",$r);
        log_addInfo($jobid,"SGE/qdel: ".$res);
        if (preg_match('/has deleted/i',$res) || preg_match('/registered the job \d+ for deletion/',$res))
            return array(true,$res);
        else
            return array(false,$res);
	}
}
?>
