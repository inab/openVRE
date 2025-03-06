<?php

require dirname(__FILE__) . "/../../config/globals.inc.php";


function shouldAutorefresh($pid): bool
{
        $interactiveToolprefix = "/interactive-tool/";
        $ok_service = false;
        $login = $_SESSION['User']['_id'];
        $jobs = getUserJobPid($login, $pid);
        $job = $jobs[$pid];

        if (! $job['state'] == "RUNNING") {
                if ($job['state'] == "PENDING") {
                        $_SESSION['errorData']['Info'] = "Please, wait. The tool session is not yet accessible. Job petition status: PENDING.\n Page is going to be automatically reloaded.";
                } else {
                        $_SESSION['errorData']['Info'] = "Tool session is not accessible anymore. Please, check the execution status.\n Page is going to be automatically reloaded.";
                }

                return true;
        }

        $stdout = "";
        $tool_port = 0;

        if (is_file($job['stdout_file'])) {
                $stdout = file_get_contents($job['stdout_file']);
                if (preg_match_all('/ExposedPort: (\d+)/', $stdout, $matches)) {
                        $tool_port = $matches[1][0];
                        $_SESSION['User']['lastjobs'][$pid]['interactive_tool']['port'] = $tool_port;
                }
                if (preg_match_all('/ContainerID: (\w+)/', $stdout, $matches)) {
                        $tool_container_id = $matches[1][0];
                        $_SESSION['User']['lastjobs'][$pid]['interactive_tool']['container_id'] =  $tool_container_id;
                }
                if (preg_match_all('/ContainerName: (\w+)/', $stdout, $matches)) {
                        $tool_container_name = $matches[1][0];
                        $_SESSION['User']['lastjobs'][$pid]['interactive_tool']['containerName'] =  $tool_container_name;
                }

                if (preg_match_all('/Service UP/', $stdout, $matches)) {
                        $_SESSION['User']['lastjobs'][$_REQUEST['pid']]['interactive_tool']['service_up'] = true;
                        $ok_service  = true;
                } else {
                        $_SESSION['errorData']['Info'][] = "Interactive session successfully established. Waiting for the service to respond...<br/>Page is going to be automatically reloaded.";

                        return true;
                }
        } else {
                $_SESSION['errorData']['Error'][] = "Execution has produced no STDOUT. Please, double check log data";

                return false;
        }

        if ($ok_service) {
                $toolContainerName = $_SESSION['User']['lastjobs'][$_REQUEST['pid']]['containerName'];
                $proxy_tool_url = $GLOBALS['SERVER'] . $interactiveToolprefix . $toolContainerName . "/";
                $_SESSION['errorData']['Info'][] = "Interactive session successfully established. Active session accessible at URL = <a target=_blank href='$proxy_tool_url'>$proxy_tool_url</a> .";
        }

        return false;
}
