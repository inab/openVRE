<?php
require __DIR__."/../../config/bootstrap.php";

redirectToolDevOutside();

$data = $GLOBALS['toolsDevMetaCol']->findOne(array('_id' => $_REQUEST['toolid']));

if(!isset($data)) {
        $_SESSION['errorData']['Error'][] = "Tool id unexisting.";
        redirect($GLOBALS['BASEURL'].'admin/myNewTools.php?id='.$_REQUEST['toolid']);
}

if($data["user_id"] != $_SESSION["User"]["id"] && ($_SESSION['User']['Type'] != 0)) {
        $_SESSION['errorData']['Error'][] = "The tool id <strong>".$_REQUEST['toolid']."</strong> you are trying to edit doesn't belong to you.";
        redirect($GLOBALS['BASEURL'].'admin/myNewTools.php');
}

$GLOBALS['toolsDevMetaCol']->updateOne(array('_id' => $_REQUEST['toolid']),
   array('$set'   => array('last_status_date' => date('Y/m/d H:i:s'), 'last_status' => $_REQUEST['status'])));

$_SESSION['errorData']['Info'][] = "Status for tool <strong>".$_REQUEST['toolid']."</strong> successfully changed to <strong>".$_REQUEST['status']."</strong>.";
        redirect($GLOBALS['BASEURL'].'admin/myNewTools.php');

