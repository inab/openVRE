<?php

define ("SRUN", "srun" );
define ("SBATCH", "sbatch");
define ("SCANCEL", "scancel ");
define ("SQUEUE", "squeue ");

class ProcessSLURM{
        private $pid;
        private $command;
        private $workDir;
	private $queue;
	private $partition="default";
        private $cpu=1;
        private $mem=0;
        private $ssh_session;
        private $username;
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
		// cl is command line
        public function __construct($cl=false,$workDir="",$partition="default", $jobname="",$cpu=1,$mem=0){
                // Assumptions:
                // user has to set up mn account at vre
                // write down info into YML file and store it in $hpc_credentials userdata/$USER_ID/.dev/MN_account.yml
                // contents: host, userid, remote_dir, public_key, queue, queue_type if not slurm
                //
                // TODO
                // load yaml_parse_file php function --> $data
                // start ssh session with phpsecli or ssh2_auth_pukbkey_file()
                // $this->ssh_session=$ssh store the ssh session

//              $current_user = posix_getpwuid(posix_geteuid()); //user executing the php and same in the remote --> no!
                // read it from the yaml
                //$this->username  = $current_user['name'];
                $this->username  = $credentials['userid'];


                if ($cl != false){    //if the command is given
                        $this->workDir = $credentials['remote_workdir'];
                        $this->command = $cl;
                        $this->queue   = $credentials['remote_workdir'];
                        $this->cpu     = $cpu; //from Mongo
                        $this->mem     = $mem; //from Mongo

                        if ($jobname)
                                $this->jobname = $jobname;
                        else
                                $this->jobname = basename($cl);

            // $this->ssh_session=$ssh; 
                        $this->runCom();
        }
        return $this;
        }


        // execute srun command
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
        // build Submit (srun) command
        public function setFullCommand(){
                $workDir = $this->workDir;
                $command = SRUN.$script.$this->queue."\n";
                // if ($this->cpu > 1)
                //      $command .= " -l cpu=". $this->cpu;
                $command .= " ".$this->command;
                $this->fullcommand = $command;
        }

        public function checkAcceSSH(){
                // here function to check the ssh connection? or before?
        }


        //list all VRE Jobs  (not used anymore)
        public function getRunningJobs(){
                $jobs=Array();
                $command = SQUEUE." -u $this->username | awk '$1 ~ /[0-9]+/ {print $1\"\t\"$5\"\t\"$6 $7}'";
                exec($command,$queueJobs);

                if (!isset($queueJobs[0])){
            log_addInfo($jobid,"Job not running anymore");
                        return $jobs;
        }else{
                        foreach ($queueJobs as $jobLine){
                                list($pid,$state,$start)=explode("\t",$jobLine);
                                $cmd = SQUEUE. " -j $pid | grep job_name | cut -d: -f2 | tr -d \" \"";
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
                $cmd = SQUEUE. " -j $pid | awk '$0~/:/ {print $0}'";
                exec($cmd,$jobInfo);

                if(count($jobInfo) == 0 )
                return $job;

                foreach ($jobInfo as $line){
                        $fields =explode(":",$line);
                        $k = trim(array_shift($fields));
                        $v = trim(implode(":",$fields));
                        $job[$k]=$v;
                }
                $cmd = SQUEUE." -u $this->username | grep $pid | awk '$1 ~ /[0-9]+/ {print $1\"\t\"$5\"\t\"$6 $7}'";
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
                $command = SQUEUE.' -u '.$this->username.' | grep "^'.$pidForm.'"';
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
                $command = SCANCEL.' '.$pid;
        exec($command,$r);
        $res = join(" ",$r);
        log_addInfo($jobid,"SLURM/scancel: ".$res);
        if (preg_match('/has deleted/i',$res) || preg_match('/registered the job \d+ for deletion/',$res))
            return array(true,$res);
        else
            return array(false,$res);
        }
}
