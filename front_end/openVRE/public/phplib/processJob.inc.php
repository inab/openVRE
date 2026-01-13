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
    getJobProcessLogger()->info("Start job submission via SGE");

    if (is_null($_SESSION['User']['id'])) {
        getJobProcessLogger()->error("User ID not found in session.");
        throw new NotFoundException("User ID not found in session.");
    }

    if (!file_exists($shFile)) {
        getJobProcessLogger()->error("Shell script file does not exist: $shFile");
        throw new NotFoundException("Shell script file does not exist: $shFile");
    }

    if (!is_dir($workDir)) {
        getJobProcessLogger()->error("Working directory does not exist: $workDir");
        throw new NotFoundException("Working directory does not exist: $workDir");
    }

    $queue = $queue ?: $GLOBALS['queueTask'];
    if (empty($queue)) {
        getJobProcessLogger()->error("Queue not provided.");
        throw new NotFoundException("Queue not provided.");
    }

    $jobname = $_SESSION['User']['id'] . "#" . basename($shFile);
    $process = new ProcessSGE($shFile, $workDir, $queue, $jobname, $cpus, $mem, $logFile, $errFile);

    if (!$process->status()) {
        $errMesg = "Job submission failed. ErrorSGE: '" . $process->getErr() . "'";
        getJobProcessLogger()->error($errMesg);
        throw new UnexpectedValueException($errMesg);
    }

    $pid = $process->getPid();
    getJobProcessLogger()->info("Process started successfully: PID = $pid");

    return $pid;
}


function getRunningJobInfo($pid, $launcherType = null)
{
    if (is_null($pid)) {
        getJobProcessLogger()->error("Job ID not found in session.");
        throw new NotFoundException("Job ID not found in session.");
    }

    if (is_null($launcherType) && is_numeric($pid)) {
        $launcherType = "SGE";
    }

    if (!in_array($launcherType, array("SGE", "docker_SGE"))) {
        getJobProcessLogger()->error("Cannot monitor job '$pid' of type '$launcherType'. Launcher not implemented.");
        throw new UnexpectedValueException("Cannot monitor job '$pid' of type '$launcherType'. Launcher not implemented.");
    }

    $process = new ProcessSGE();
    return $process->getRunningJobInfo($pid);
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
                try {
                    delJob($pid);
                } catch (Exception $e) {
                    $_SESSION['errorData']['Error'][] = "Cannot delete " . basename($outfile) . " task.";
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
    if (empty($pid)) {
        getJobProcessLogger()->error("Job ID not provided.");
        throw new NotFoundException("Job ID not provided.");
    }

    // guess launcher
    if (!$launcherType && is_numeric($pid)) {
        $launcherType = "docker_SGE";
    }

    // cancel job
    $r_sge = false;
    if ($launcherType == "SGE" || $launcherType == "docker_SGE") {
        $processSGE = new ProcessSGE();
        list($r_sge, $msg_sge) = $processSGE->stop($pid);
    } else {
        getJobProcessLogger()->error("Cannot delete job of type '$launcherType' [id = $pid]. Launcher not implemented.");
        throw new UnexpectedValueException("Cannot delete job of type '$launcherType' [id = $pid]. Launcher not implemented.");
    }

    $jobUser = $_SESSION['User']['lastjobs'][$pid];

    if ($jobUser && $jobUser['job_type'] == "interactive") {
        return false;
    }

    if ($r_sge === false) {
        getProcessValidationLogger()->error("Cannot delete $launcherType job [id = $pid].<br/> SGE Error: $msg_sge<br/>Docker Error");
        throw new UnexpectedValueException("Cannot delete $launcherType job [id = $pid].<br/> SGE Error: $msg_sge<br/>Docker Error");
    }

    $_SESSION['errorData']['Info'][] = "Job successfully cancelled";

    // wait to make qdel/terminateActivity effective
    sleep(15);

    if (!$login) {
        $login = $_SESSION['User']['_id'];
    }
}
