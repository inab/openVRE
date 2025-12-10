<?php

#
# Job management functions : SGE
#


function getJobProcessLogger()
{
    static $logger = null;

    if ($logger === null) {
        $logger = LoggerFactory::getLogger('Job process interface');
    }

    return $logger;
}


function execJob($workDir, $shFile, $queue, $cpus = 1, $mem = 0, $logFile = "job_output.log", $errFile = "job_error.log")
{
    logger("Start job submission via SGE");

    if (is_null($_SESSION['User']['id'])) {
        $_SESSION['errorData']['Error'][] = "User ID not found in session.";
        return [0, "User ID not found in session."];
    }

    // Validate shell script file
    if (!file_exists($shFile)) {
        $_SESSION['errorData']['Error'][] = "Shell script file does not exist: $shFile";
        return [0, "Shell script file does not exist: $shFile"];
    }

    // Validate working directory
    if (!is_dir($workDir)) {
        $_SESSION['errorData']['Error'][] = "Working directory does not exist: $workDir";
        return [0, "Working directory does not exist: $workDir"];
    }

    // Validate queue
    $queue = $queue ?: ($GLOBALS['queueTask'] ?? null);
    if (!$queue) {
        $_SESSION['errorData']['Error'][] = "Queue not provided.";
        return [0, "Queue not provided."];
    }


    $queue   = (isset($queue) ? $queue : $GLOBALS['queueTask']);
    $jobname = $_SESSION['User']['id'] . "#" . basename($shFile);

    //
    // Start SGE process
    $process = new ProcessSGE($shFile, $workDir, $queue, $jobname, $cpus, $mem, $logFile, $errFile);

    $pid = $process->getPid();

    if (!$process->status()) {
        $_SESSION['errorData']['Error'][] = "Job submission failed.<br/>" . $process->getFullCommand . "<br/>" . $process->getErr();
        $errMesg = "ERROR: Job submission failed. FullCommand: '" . $process->getFullCommand . "'. ErrorSGE: '" . $process->getErr() . "'";
        logger($errMesg);
        return array(0, $errMesg);
    }

    error_log("Process started successfully: PID = $pid");
    logger("The process $cmd is currently running PID = $pid");
    return array($pid, "");
}


function getRunningJobInfo($pid, $launcherType = null)
{
    $job = array();
    if (!$pid) {
        return $job;
    }

    if (!$launcherType && is_numeric($pid)) {
        $launcherType = "SGE";
    }

    // create new jobProcess
    if ($launcherType == "SGE" || $launcherType == "docker_SGE") {
        $process = new ProcessSGE();
        $job = $process->getRunningJobInfo($pid);
    } else {
        $_SESSION['errorData']['Error'][] = "Cannot monitor job '$pid' of type '$launcherType'. Launcher not implemented.";
        return $job;
    }

    return $job;
}

function getPidFromOutfile($outfile)
{
    $pid = 0;
    $SGE_updated = getUserJobs($_SESSION['userId']);
    foreach ($SGE_updated as $data) {
        $outs = is_array($data['out']) ? $data['out'] : array($data['out']);
        if (in_array($outfile, $outs)) {
            return $data['_id'];
        }
    }

    return $pid;
}

// cancel job given its output file
function delJobFromOutfiles($outfiles)
{
    if (!is_array($outfiles)) {
        $outfiles = array($outfiles);
    }
    if (count($outfiles) == 0)
        return 1;

    $SGE_updated = getUserJobs($_SESSION['userId']);

    foreach ($outfiles as $outfile) {
        $pid = getPidFromOutfile($outfile);
        if ($pid) {
            //get dependencies of the selected job
            $pids = array($pid);
            $jobInfo =  getRunningJobInfo($pid);
            if (isset($jobInfo['jid_successor_list'])) {
                foreach (explode(",", $jobInfo['jid_successor_list']) as $pidSucc) {
                    $succInfo = getRunningJobInfo($pidSucc);
                    if ($succInfo)
                        array_push($pids, $pidSucc);
                }
            }
            //foreach job, cancel and delete associated files
            foreach ($pids as $pid) {
                //delete job
                $ok = delJob($pid);
                if (!$ok) {
                    $_SESSION['errorData']['Error'][] = "Cannot delete " . basename($outfile) . " task. Unsuccessfully exit of 'deljob' for job $pid.";
                    continue;
                }
                //delete job associated files
                $files = array();
                $jobType = (isset($SGE_updated[$pid]['log']) ? basename($SGE_updated[$pid]['log']) : "");
                if (preg_match('/^PP_/', $jobType)) {
                    $files[] = $SGE_updated[$pid]['log'];
                } else {
                    if (!is_array($SGE_updated[$pid]['out']))
                        $files[] = $SGE_updated[$pid]['out'];
                    else
                        $files  = $SGE_updated[$pid]['out'];
                    $files[] = $SGE_updated[$pid]['log'];
                }

                foreach ($files as $fn) {
                    $rfn = $GLOBALS['dataDir'] . "/$fn";
                    $ofn = $GLOBALS['filesCol']->findOne(array('_id' => $fn));
                    if (!empty($ofn)) {
                        try {
                            deleteGSFileBNS($fn);
                        } catch (Exception $e) {
                            getJobProcessLogger()->error("Job " . basename($outfile) . " deleted. But errors occured while cleaning temporal files." . $e);
                            continue;
                        }
                    }
                    if (is_file($rfn) && !unlink($rfn)) {
                        $_SESSION['errorData']['SGE'][] = "Cannot unlink $rfn" . error_get_last()["message"];
                    }
                }
                //update pending jobs
                //unset($SGE_updated[$pid]);
                //delUserJob($_SESSION['userId'],$pid);
            }
        } else {
            $_SESSION['errorData']['SGE'][] = "Cannot find job information for '" . basename($outfile) . "'.  &nbsp;<a href=\"workspace/workspace.php\">[ OK ]</a>";
        }
    }
    return 1;
}

function delJob($pid, $launcherType = null, $login = null)
{
    if (!$pid) {
        return false;
    }

    // guess launcher
    if (!$launcherType && is_numeric($pid)) {
        $launcherType = "docker_SGE";
    }

    // cancel job
    $r_sge = false;
    $r_docker = false;
    if ($launcherType == "SGE" || $launcherType == "docker_SGE") {
        $processSGE = new ProcessSGE();
        list($r_sge, $msg_sge) = $processSGE->stop($pid);
    } else {
        $_SESSION['errorData']['Error'][] = "Cannot delete job of type '$launcherType' [id = $pid]. Launcher not implemented.";
        return false;
    }

    $jobUser = $_SESSION['User']['lastjobs'][$pid];

    if ($jobUser && $jobUser['job_type'] == "interactive") {
        return false;
    }

    if (!$r_sge || !$r_docker) {
        $_SESSION['errorData']['Error'][] = "Cannot delete $launcherType job [id = $pid].<br/> SGE Error: $msg_sge<br/>Docker Error";
    }

    $_SESSION['errorData']['Info'][] = "Job successfully cancelled";
    logger("JOB $pid FINISHED. HAS BEEN CANCELLED");
    log_addFinish($pid, "Job has been cancelled");

    // wait to make qdel/terminateActivity effective
    sleep(15);

    // check job status and register output files, if required
    if ($r_sge) {
        if (!$login) {
            $login = $_SESSION['User']['_id'];
        }
        //$filesPending= processPendingFiles($login);
        //delUserJob($login,$pid); // directly deleting job entry leds to no output registration! 
    } else {
        $_SESSION['errorData']['Internal Error'][] = "Error while cancelling $launcherType job [id = $pid].<br>Job deleted from the system, but not from user metadata";
        return false;
    }
    return true;
}
