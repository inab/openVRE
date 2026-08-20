<?php

use OpenVRE\LoggerFactory;
use OpenVRE\NotFoundException;
use OpenVRE\ProcessK8s;
use OpenVRE\ProcessSGE;
use OpenVRE\ProcessSlurm;


function getJobProcessLogger()
{
    static $logger = null;

    if ($logger === null) {
        $logger = LoggerFactory::getLogger('Job process interface');
    }

    return $logger;
}


function execJob($workDir, $shFile, $queue, $cpus = 1, $mem = 0, $logFile = "job_output.log", $errFile = "job_error.log", $jobManager = "docker_SGE", $toolId = "", $jobOptions = array())
{
    getJobProcessLogger()->info("Start job submission via SGE");

    if (is_null($_SESSION['User']['id'])) {
        getJobProcessLogger()->error("User ID not found in session.");
        LoggerFactory::getPersistentLogger()->error("User ID {userId} not found in session.", array('userId' => $_SESSION['User']['id']));
        throw new NotFoundException("User ID not found in session.");
    }

    if (!file_exists($shFile)) {
        getJobProcessLogger()->error("Shell script file does not exist: $shFile");
        LoggerFactory::getPersistentLogger()->error("Shell script file {shFile} does not exist", array('shFile' => $shFile));
        throw new NotFoundException("Shell script file does not exist: $shFile");
    }

    if (!is_dir($workDir)) {
        getJobProcessLogger()->error("Working directory does not exist: $workDir");
        LoggerFactory::getPersistentLogger()->error("Working directory {workDir} does not exist", array('workDir' => $workDir));
        throw new NotFoundException("Working directory does not exist: $workDir");
    }

    $queue = $queue ?: $GLOBALS['queueTask'];
    if (empty($queue)) {
        getJobProcessLogger()->error("Queue not provided.");
        throw new NotFoundException("Queue not provided.");
    }

    $jobname = $_SESSION['User']['id'] . "#" . basename($shFile);

    switch ($jobManager) {
        case "docker_SGE":
            getJobProcessLogger()->info("Submitting job via docker_SGE. Parameters: shFile=$shFile, workDir=$workDir, queue=$queue, jobname=$jobname, cpus=$cpus, mem=$mem, logFile=$logFile, errFile=$errFile");
            $process = new ProcessSGE($shFile, $workDir, $queue, $jobname, $cpus, $mem, $logFile, $errFile);
            break;
        case "Slurm_Singularity":
            $remote_system = $_REQUEST['sites']['site_list'][0];
            getJobProcessLogger()->info("Submitting job via Slurm_Singularity to $remote_system. Parameters: shFile=$shFile, workDir=$workDir, logFile=$logFile, errFile=$errFile");
            $process = new ProcessSlurm($shFile, $workDir, $logFile, $errFile, $remote_system);
            break;
        case "kubernetes_native":
            $schedUrl = getenv("OPENVRE_K8S_SCHEDULER_URL") ?: "";
            $schedHost = $schedUrl !== ""
                ? (string)(parse_url($schedUrl, PHP_URL_HOST) ?: "(parse_failed)")
                : "(not_set)";
            $k8sNs = getenv("OPENVRE_K8S_NAMESPACE") ?: "(env_unset)";
            $jobOptKeys = is_array($jobOptions) && count($jobOptions)
                ? implode(",", array_keys($jobOptions))
                : "(none)";
            getJobProcessLogger()->info(
                "Submitting job via kubernetes_native. Parameters: shFile=$shFile, workDir=$workDir, "
                . "jobname=$jobname, cpus=$cpus, mem=$mem, "
                . "namespace=$k8sNs, scheduler_host=$schedHost, jobOptions_keys=$jobOptKeys"
            );
            $process = new ProcessK8s($shFile,$workDir,$jobname,(int)$cpus,(int)$mem,is_array($jobOptions) ? $jobOptions : []);
            break;
        default:
            $process = new ProcessSGE($shFile, $workDir, $queue, $jobname, $cpus, $mem, $logFile, $errFile);
            break;
    }

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
    } elseif (strpos((string)$pid, "-") !== false) {
        $launcherType = "kubernetes_native";
    }

    if ($launcherType == "SGE" || $launcherType == "docker_SGE") {
        $process = new ProcessSGE();
        $job = $process->getRunningJobInfo($pid);
    } elseif ($launcherType == "kubernetes_native") {
        $process = new ProcessK8s();
        $job = $process->getRunningJobInfo($pid);
    } elseif ($launcherType == "Slurm_Singularity") {
        $process = new ProcessSlurm();
        $job = $process->getRunningJobInfo($pid);
    } else {
        getJobProcessLogger()->error("Cannot monitor job '$pid' of type '$launcherType'. Launcher not implemented.");
        throw new UnexpectedValueException("Cannot monitor job '$pid' of type '$launcherType'. Launcher not implemented.");
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
    } elseif (strpos((string)$pid, "-") !== false) {
        $launcherType = "kubernetes_native";
    }

    // cancel job
    $r_sge = false;
    if ($launcherType == "SGE" || $launcherType == "docker_SGE") {
        $processSGE = new ProcessSGE();
        list($r_sge, $msg_sge) = $processSGE->stop($pid);
    } elseif ($launcherType == "kubernetes_native") {
        getJobProcessLogger()->debug("delJob kubernetes_native pid=$pid calling ProcessK8s::stop");
        $processK8s = new ProcessK8s();
        list($r_sge, $msg_sge) = $processK8s->stop($pid);
        getJobProcessLogger()->debug(
            "delJob kubernetes_native pid=$pid stop_ok=" . ($r_sge ? "1" : "0")
                . " msg=" . $msg_sge
        );
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
    LoggerFactory::getPersistentLogger()->info("Job {pid} successfully cancelled", array('pid' => $pid));

    // wait to make qdel/terminateActivity effective
    sleep(15);

    if (!$login) {
        $login = $_SESSION['User']['_id'];
    }
}
