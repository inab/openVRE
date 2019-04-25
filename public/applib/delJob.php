<?php

require __DIR__."/../../config/bootstrap.php";

redirectAdminOutside();


if($_REQUEST["pid"]){

    //delete job
    $r = delJob($_REQUEST["pid"],NULL,"local",$_REQUEST["user"]);
    if (!$r){
        $_SESSION['errorData']['Error'][]= "Cannot cancel task. Unsuccessfully exit of 'deljob' for job $pid.";
    }
    redirect($GLOBALS['URL'].'/admin/adminJobs.php');
}else{
	redirect($GLOBALS['URL']);
}
?>
