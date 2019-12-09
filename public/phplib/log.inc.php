<?php # log.inc.php

###### LOG INFO WRITTEN INTO LOGFILE

function timestamp() {
    return date("d/m/y : H:i:s", time());
}

function logger($entry) {
    if (!is_file($GLOBALS['logFile']) ||  !is_writable($GLOBALS['logFile']))  {
    	    $_SESSION['errorData']['Error'][]="Internal error. Cannot log application's action into ".$GLOBALS['logFile'].". File not found or not writtable";
    	    return false;
    }

    $entry = timestamp()." | $entry \n";
    file_put_contents($GLOBALS['logFile'], $entry, FILE_APPEND | LOCK_EX);
}


###### JOB EXECUTION LOGS STORED AT MONGO DB

// Add log entry defining job sumbission

function log_addSubmission($pid,$toolId,$cloudName,$launcher,$cpus,$memory,$wd,$test=FALSE){

    // set main log info for a submitted job
    $log_exec = array(
        "log_type"  => "Submission",
        "date"      => new MongoDB\BSON\UTCDateTime(strtotime("now")*1000),
        "user"      => $_SESSION['User']['id'],
        "job_id"    => $pid,
        "work_dir"  => $wd,
        "toolId"    => $toolId,
        "cloudName" => $cloudName,
        "launcher"  => $launcher,
        "cpus"      => $cpus,
        "memory"    => $memory,
        "test"      => $test
    );

    // register to mongo
    $r = $GLOBALS['logExecutionsCol']->insertOne($log_exec);
    if (!$r)
        return false;

    return 1;
}

// Add log entry defining execution error

function log_addError($pid,$msg,$errCode=0, $toolId=FALSE,$cloudName=FALSE,$launcher=FALSE,$cpus=FALSE,$memory=FALSE,$test=FALSE){

    // if no job_id, set dummy
    if (!$pid)
        $pid = uniqid("dummy_");
    // Add extra info
    $log_exec_extra = array();
    if ($toolId)   {$log_exec_extra['toolId']   = $toolId;}
    if ($cloudName){$log_exec_extra['cloudName']= $cloudName;}
    if ($launcher) {$log_exec_extra['launcher'] = $launcher;}
    if ($cpus)     {$log_exec_extra['cpus']     = $cpus;}
    if ($memory)   {$log_exec_extra['memory']   = $memory;}

    // set main log info for an error
    $log_exec = array(
        "log_type"  => "Error",
        "date"      => new MongoDB\BSON\UTCDateTime(strtotime("now")*1000),
        "user"      => $_SESSION['User']['id'],
        "job_id"    => $pid,
        "msg"       => $msg,
        "errCode"   => $errCode,
        "test"      => $test
    );
    // push extra info
    if ($log_exec_extra){
        $log_exec = $log_exec + $log_exec_extra;
    }
    // register to mongo
    $r = $GLOBALS['logExecutionsCol']->insertOne($log_exec);
    if (!$r)
        return false;

    return 1;
}

// Add log entry describing output file registration process

function log_addOutregister($pid,$msg="",$success=NULL,$test=FALSE){

    // if no job_id, set dummy
    if (!$pid)
        $pid = uniqid("dummy_");

    // set main log info
    $log_exec = array(
        "log_type"  => "Outfile register",
        "date"      => new MongoDB\BSON\UTCDateTime(strtotime("now")*1000),
        "user"      => $_SESSION['User']['id'],
        "job_id"    => $pid,
        "test"      => $test
    );
    // add job success status if available
    if (! is_null($success)){$log_exec["success"]=$success;}
    // add msg if available
    if ($msg){$log_exec["msg"]=$msg;}

    // register to mongo
    $r = $GLOBALS['logExecutionsCol']->insertOne($log_exec);
    if (!$r)
        return false;
    return 1;
}

// Add log entry describing when a job is not in the running anymore

function log_addFinish($pid,$msg="",$test=FALSE){

    // if no job_id, set dummy
    if (!$pid)
        $pid = uniqid("dummy_");

    // set main log info
    $log_exec = array(
        "log_type"  => "Finished",
        "date"      => new MongoDB\BSON\UTCDateTime(strtotime("now")*1000),
        "user"      => $_SESSION['User']['id'],
        "job_id"    => $pid,
        "test"      => $test
    );
    // add msg if available
    if ($msg){$log_exec["msg"]=$msg;}

    // register to mongo
    $r = $GLOBALS['logExecutionsCol']->insertOne($log_exec);
    if (!$r)
        return false;
    return 1;
}

// Add log entry info line

function log_addInfo($pid,$msg="",$test=FALSE){

    // if no job_id, set dummy
    if (!$pid)
        $pid = uniqid("dummy_");

    // set main log info
    $log_exec = array(
        "log_type"  => "Info",
        "date"      => new MongoDB\BSON\UTCDateTime(strtotime("now")*1000),
        "user"      => $_SESSION['User']['id'],
        "job_id"    => $pid,
        "test"      => $test
    );
    // add msg if available
    if ($msg){$log_exec["msg"]=$msg;}

    // register to mongo
    $r = $GLOBALS['logExecutionsCol']->insertOne($log_exec);
    if (!$r)
        return false;
    return 1;
}

// Query job log events in mongo and creates job log info data grouped by job_id

function aggregateJobLogs($filters=array()){

    $jobs = array();
    // adding log_type to user filters
    $filters["log_type"] = "Submission";

    //find in mongo log execution collection jobs with at least
    //a "sumbission" entry and grouped by job_id

    $aggregate = array(
    array(
        '$match' => $filters, 
    ),
    array(
        '$lookup' => array(
            'localField' => 'job_id',
            'from' => 'log_executions',
            'foreignField' => 'job_id',
            'as' => 'jobLog'
        ),
    ),
    array(
        '$sort' => array("jobLog.date" => -1)
    ),
    array(
        '$project' => array("job_id" => 1, "jobLog" => 1)
    )
    );
    $options = array("explain" => false);

    // do aggregate
    if ($GLOBALS['logExecutionsCol']){
	//$result = $GLOBALS['logExecutionsCol']->aggregateCursor($aggregate,$options);
	$result = $GLOBALS['logExecutionsCol']->aggregate($aggregate,$options);
	//$result = $result['cursor']['firstBatch']; // default batchsize used. Version do not allow to set it up
    }
    // format aggregate result
    if($result){
      foreach ($result as $j){
        $pid = $j["job_id"];

        // extract common data from jobLog events
        foreach ($j["jobLog"] as $logEvent){

            // format 'success'
            if ($logEvent['log_type'] == "Submission"){
                $jobs[$pid] = $logEvent;
                unset($jobs[$pid]["_id"]);
                unset($jobs[$pid]["log_type"]);
                unset($jobs[$pid]["date"]);

                $jobs[$pid]["date_start"] = strftime('%d/%m/%Y %H:%M', $logEvent['date']->toDateTime()->format('U'));
                $jobs[$pid]["timestamp_start"] = $logEvent['date']->toDateTime()->format('U');
                $jobs[$pid]["work_dir"] = fromAbsPath_toPath($logEvent['work_dir']);

            // from 'outfile register'
            }elseif (isset($logEvent['success'])){
                $jobs[$pid]["success"] = ($logEvent['success']? "TRUE" : "ERR" );
    
            // from 'finished'
            }elseif ($logEvent['log_type'] == "Finished"){
                $jobs[$pid]["date_end"] = strftime('%d/%m/%Y %H:%M', $logEvent['date']->toDateTime()->format('U'));
                $jobs[$pid]["timestamp_end"] = $logEvent['date']->toDateTime()->format('U');
            }
        }

        // extract and internally sort jobLog events
        usort($j['jobLog'], function ($a, $b){ return ($a['date']->toDateTime()->format('U') - $b['date']->toDateTime()->format('U')); });
        $jobs[$pid]["logs"]     = $j['jobLog'];

        //ensure success is there
        if (!isset($jobs[$pid]["success"])) {$jobs[$pid]["success"] = "";}
      }
    }
    return $jobs;
}


// Generates some statistics from the aggreated job log info data, ignoring log events

function getStatsFromJobLogs($jobs,$byTools=array("all")){

    // set array of months for histograms
    for ($i = 0; $i <= 11; $i++) {
        $months_list[date("Y/m", strtotime( date( 'Y-m' )." -$i months"))] = 0;
    }
    $months_list = array_reverse($months_list);

    // initialize
    $stats = array();
    foreach ($byTools as $toolId){
	    $stats[$toolId] = array("jobs_total" => 0,
    	            "jobs_finished_success" => 0,
    	            "jobs_finished_err"     => 0,
    	            "jobs_finished_unk"     => 0,
			        "distinct_users"        => array(),
			        "avg_duration"          => array(),
			        "freq_executions"       => $months_list
                );
    }

    // count stats for each job
    foreach ($jobs as $pid => $j){
    
        //filter by toolId
        $toolId = $j['toolId'];
        if (!in_array($toolId,$byTools) && $byTools[0] != "all")
                continue;
        if ($byTools[0] == "all" && count($byTools)==1 ){
            $toolId = "all";
        }

        // count total jobs
        $stats[$toolId]["jobs_total"]++;

        // count err/success jobs 
        if ($j["success"] == "TRUE"){ $stats[$toolId]['jobs_finished_success']++; }
        if ($j["success"] == "ERR") { $stats[$toolId]['jobs_finished_err']++ ; }
        if ($j["success"] == "")    { $stats[$toolId]['jobs_finished_unk']++ ; }

        // count distinct user
        $stats[$toolId]['distinct_users'][$j['user']]=1;

        // comp average execution time
        $duration = number_format(($j["timestamp_end"] - $j["timestamp_start"])/60,2); 
        $stats[$toolId]['avg_duration'] = ( $stats[$toolId]['avg_duration']? ($stats[$toolId]['avg_duration'] + $duration)/2 : $duration);

        // count last mouth frequency execution
        if(isset($j["date_start"])){
            $monthyear = date( 'Y/m' ,$j['timestamp_start']);
            if(array_key_exists($monthyear, $stats[$toolId]["freq_executions"])) $stats[$toolId]["freq_executions"][$monthyear]++;
    	}
    }

    return $stats;
    
}

// Writes in a file handler the aggreated job log info data, ignoring log events

function printCSVJobLogs($jobs,$csvfile="php://output"){

    // open CSV
    $F = fopen($csvfile, 'w'); // if no fn, print STDOUT

    // foreach job entry
    $cols=array();
    foreach ($jobs as $pid => $j){
        // do not include log events
        unset($j['logs']);
        // print header
        if (! $cols){
            $cols = array_keys($j);
            fputcsv($F, $cols,";",'"');
        }
        $line="";
        // check field values
        foreach($cols as $colname){
            if (is_bool($j[$colname])){ $j[$colname] = ($j[$colname]) ? 'true' : 'false' ;}
            if (! isset($j[$colname])){$j[$colname] = "";}
            $line .= $j[$colname].";";
        }
        // print CSV line
        $line = rtrim($line,";");
        fwrite($F,$line."\n");
    }
    fclose($F);

    return 1;
}
