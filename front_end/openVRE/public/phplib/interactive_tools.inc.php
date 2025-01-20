<?php
function get_url_interactive_tool($pid, $login="session") {

        $proxy_tool_url     = "";
        $proxy_tool_headers = array();
        $message            = "";
        $autorefresh        = true;

        $ok_service    = false;
        $ok_stdout     = false;
        $ok_stderr     = false;

        // Get job info
        $login = ($login == "session"? $_SESSION['User']['_id'] : $login);
        $jobs = getUserJobPid($login,$pid);
        $job = $jobs[$pid];
        // Check job status

        if (! $job['state'] == "RUNNING"){
                if ($job['state'] == "PENDING"){
                        $_SESSION['errorData']['Info'] = "Please, wait. The tool session is not yet accessible. Job petition status: PENDING.\n Page is going to be automatically reloaded.";
                }else{
                        $_SESSION['errorData']['Info'] = "Tool session is not accessible anymore. Please, check the execution status.\n Page is going to be automatically reloaded.";
                }
                return array($proxy_tool_url, $proxy_tool_headers, $autorefresh);
        }

        // Check session progress

        $stdout    = "";
        $tool_port = 0;

        if (is_file($job['stdout_file'])){
                $ok_stdout  =  true;
                $stdout = file_get_contents($job['stdout_file']);

                // parse port number
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
                        $_SESSION['User']['lastjobs'][$pid]['interactive_tool']['container_name'] =  $tool_container_name;
                }


                // check service is UP
                if (preg_match_all('/Service UP/', $stdout, $matches)) {
                        $_SESSION['User']['lastjobs'][$_REQUEST['pid']]['interactive_tool']['service_up'] = true;
                        $ok_service  = true;
                        $autorefresh = false;
                }else{
                        $_SESSION['errorData']['Info'][]="Interactive session successfully established. Waiting for the service to respond...<br/>Page is going to be automatically reloaded.";
                        return array($proxy_tool_url, $proxy_tool_headers, $autorefresh);
                }

        // No stdout
        }else{
                $_SESSION['errorData']['Error'][]="Execution has produced no STDOUT. Please, double check log data";
                $autorefresh = false;
                return array($proxy_tool_url, $proxy_tool_headers, $autorefresh);
        }

        // If service ready, find out public url

        if ($ok_service){
                // Build IP from port (md5)
                $url_proxy_path = 'rstudio_'.md5($tool_port);
                $proxy_tool_url = "http://vre.disc4all.eu/$url_proxy_path/";

                // TODO: set gdx proxy headers
                $_SESSION['errorData']['Info'][]="Interactive session successfully established. Active session accessible at URL = <a target=_blank href='$proxy_tool_url'>$proxy_tool_url</a> .";

                // Set custom headers

		$proxy_tool_headers= array('"X-RStudio-Root-Path": "/'.$url_proxy_path.'"');
		//$proxy_tool_headers= array('"X-Root-Path": "/'.$url_proxy_path.'"');
        }
        return array($proxy_tool_url, $proxy_tool_headers, $autorefresh);

}

?>
