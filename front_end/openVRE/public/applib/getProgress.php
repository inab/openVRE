<?php
require __DIR__."/../../config/bootstrap.php";

redirectOutside();

$job = getUserJobPid($_SESSION['User']['_id'],$_REQUEST["pid"]);
$mt = $job[$_REQUEST["pid"]];


/***********************************/
/****** FAKE LOG FILE **************/
//$fake_log_file = "/var/www/html/files/MuGUSER5a0c19c86901e/__PROJ5b51b5d4d86f59.95768162/testProgress1/.tool.log";
/***********************************/

/*
 * POSSIBLE STATES:
 *
 * DEBUG (fa-bug)
 * INFO (fa-info)
 * WARNING (fa-exclamation-triangle)
 * ERROR (fa-times)
 * FATAL (fa-bomb) 
 * PROGRESS (fa-check)
 * IN-PROGRESS (fa-spinner fa-spin fa-fw)
 *
 */



if(file_exists($mt['log_file'])) {
        //$log = nl2br(file_get_contents($mt['log_file']));
        // RAW LOG
        $log = preg_replace( "/\r|\n/", "<br>", file_get_contents($mt['log_file']) );
        $log = preg_replace('/\s+/', ' ', $log);

        $rmv[] = "'";
        $rmv[] = '"';
        $rmv[] = "‘"; 
        $rmv[] = "’";
        $log = str_replace( $rmv, "", $log );
        // PROGRESS
        $currentLog = trim(shell_exec("egrep -E '\| DEBUG|\| INFO|\| WARNING|\| ERROR|\| FATAL|\| PROGRESS' ".$mt['log_file']));
        $arraylog = explode("\n", $currentLog);

        if(sizeof($arraylog) == 1 && $arraylog[0] == "") {

                $progress = '<div  style=\"text-align:left;\">'.
                        '<ul class=\"progress-tracker progress-tracker--vertical\">';

                        $progress .= '<li class=\"progress-step is-complete\">'.
                                '<span class=\"progress-marker progress-progress\">'.
                                        '<i class=\"fa fa-spinner fa-spin fa-fw\" aria-hidden=\"true\"></i>'.
                                '</span>'.
                                '<span class=\"progress-text bold progress-msg-progress\">'.
                                        'Running job (Go to View Raw Log for more details)'
                                .'</span>'.
                        '</li>';

                $progress .= '</ul>'.
                '</div>';

        } else{

        $progress = '<div  style=\"text-align:left;\">'.
                        '<ul class=\"progress-tracker progress-tracker--vertical\">';

        $total = sizeof($arraylog);
        $c = 1;
        foreach($arraylog as $al) {

                $p = explode("|", $al);
                $date = trim($p[0]);
                $np = explode(":", $p[1]);
                $state = trim($np[0]);
                $msg = trim($np[1]);

                switch($state) {

                        case "PROGRESS":
                                $icon = ($c == $total) ? "fa-spinner fa-spin fa-fw" : "fa-check";
                                $ballclass = "progress-progress";
                                $msgclass = ($c == $total) ? "bold progress-msg-progress" : "progress-msg-progress";
                                break;

                        case "INFO":
                                $icon = ($c == $total) ? "fa-spinner fa-spin fa-fw" : "fa-info";
                                $ballclass = "progress-info";
                                $msgclass = ($c == $total) ? "bold progress-msg-info" : "progress-msg-info";
                                break;

                        case "DEBUG":
                                $icon = ($c == $total) ? "fa-spinner fa-spin fa-fw" : "fa-bug";
                                $ballclass = "progress-bug";
                                $msgclass = ($c == $total) ? "bold progress-msg-bug" : "progress-msg-bug";
                                break;

                        case "WARNING":
                                $icon = ($c == $total) ? "fa-spinner fa-spin fa-fw" : "fa-exclamation-triangle";
                                $ballclass = "progress-warning";
                                $msgclass = ($c == $total) ? "bold progress-msg-warning" : "progress-msg-warning";
                                break;

                        case "ERROR":
                                $icon = ($c == $total) ? "fa-spinner fa-spin fa-fw" : "fa-times";
                                $ballclass = "progress-error";
                                $msgclass = ($c == $total) ? "bold progress-msg-error" : "progress-msg-error";
                                break;

                        case "FATAL":
                                $icon = ($c == $total) ? "fa-spinner fa-spin fa-fw" : "fa-bomb";
                                $ballclass = "progress-fatal";
                                $msgclass = ($c == $total) ? "bold progress-msg-fatal" : "progress-msg-fatal";
                                break;

                }

                $progress .= '<li class=\"progress-step is-complete\">'.
                                '<span class=\"progress-marker '.$ballclass.'\">'.
                                        '<i class=\"fa '.$icon.'\" aria-hidden=\"true\"></i>'.
                                '</span>'.
                                '<span class=\"progress-text '.$msgclass.'\">'.
                                        $msg.' <span>[ '.$date.' ]</span>'
                                .'</span>'.
                        '</li>';

                $c ++;

        }

        $progress .= '</ul>'.
                '</div>';

        }

} else {
        $log = "Log file not created yet, please wait.";
        $progress = '<span class=\"font-green bold\">Queueing process <i class=\"fa fa-spinner fa-pulse\"></i></span>';
}

//*************** ****************
// TODO: put that on first part of if
// ************** ****************


echo '{"log":"'.$log.'", "progress":"'.$progress.'"}';
