<?php

require dirname(__FILE__) . "/../../config/globals.inc.php";


function checkStatus($pid)
{
    $interactiveToolprefix = "/interactive-tool/";

    $login = $_SESSION['User']['_id'];
    $jobs  = getUserJobPid($login, $pid);

    if (!isset($jobs[$pid])) {
        return [
            "ready"   => false,
            "reload"  => false,
            "title"   => "Job not found",
            "message" => "The requested interactive session could not be found."
        ];
    }

    $job = $jobs[$pid];

    /*----------------------------------------------------
     * Job state
     *---------------------------------------------------*/

    if ($job['state'] == "PENDING") {
        return [
            "ready"   => false,
            "reload"  => true,
            "title"   => "Waiting for scheduler",
            "message" => "The interactive session is waiting for compute resources."
        ];
    }

    if ($job['state'] != "RUNNING") {
        return [
            "ready"   => false,
            "reload"  => false,
            "title"   => "Session finished",
            "message" => "The interactive session is no longer running."
        ];
    }

    /*----------------------------------------------------
     * Wait until stdout exists
     *---------------------------------------------------*/

    if (!is_file($job['stdout_file'])) {
        return [
            "ready"   => false,
            "reload"  => true,
            "title"   => "Starting container",
            "message" => "Waiting for launcher output..."
        ];
    }

    $stdout = file_get_contents($job['stdout_file']);

    /*----------------------------------------------------
     * Save metadata in the session (KEEP THIS)
     *---------------------------------------------------*/

    /*
    if (preg_match('/ExposedPort: (\d+)/', $stdout, $matches)) {
        $_SESSION['User']['lastjobs'][$pid]['interactive_tool']['port'] = $matches[1];
    }

    if (preg_match('/ContainerID: (\w+)/', $stdout, $matches)) {
        $_SESSION['User']['lastjobs'][$pid]['interactive_tool']['container_id'] = $matches[1];
    }

    if (preg_match('/ContainerName: (\S+)/', $stdout, $matches)) {
        $_SESSION['User']['lastjobs'][$pid]['interactive_tool']['containerName'] = $matches[1];
    }
    */

    /*----------------------------------------------------
     * Has the service started?
     *---------------------------------------------------*/

    if (strpos($stdout, "Service UP") === false) {

        return [
            "ready"   => false,
            "reload"  => true,
            "title"   => "Preparing interactive session",
            "message" => "The container is running. Waiting for the application to become available..."
        ];
    }

    /*----------------------------------------------------
     * Mark service ready
     *---------------------------------------------------*/

    $url = $GLOBALS['SERVER']
        . $interactiveToolprefix
        . $job['containerName']
        . "/";

    return [
        "ready"   => true,
        "reload"  => false,
        "title"   => "Interactive session ready",
        "message" => "Your session is ready.",
        "url"     => $url,
        "job"     => $job
    ];
}
