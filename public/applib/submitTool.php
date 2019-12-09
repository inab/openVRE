<?php

require __DIR__."/../../config/bootstrap.php";
//require "../phplib/admin.inc.php";

$data = $GLOBALS['toolsDevMetaCol']->findOne(array('_id' => $_REQUEST['toolid']));

if(!isset($data)) {
    $_SESSION['errorData']['Error'][] = "Tool id unexisting.";
    redirect($GLOBALS['BASEURL'].'admin/myNewTools.php?id='.$_REQUEST['toolid']);
}

if($data["user_id"] != $_SESSION["User"]["id"] && ($_SESSION['User']['Type'] != 0)) {
    $_SESSION['errorData']['Error'][] = "The tool id <strong>".$_REQUEST['toolid']."</strong> you are trying to edit doesn't belong to you.";
    redirect($GLOBALS['BASEURL'].'admin/myNewTools.php');
}

$ticketnumber = 'VRE-'.rand(1000, 9999);
$subject = 'New tool';

$message = '
    Ticket ID: '.$ticketnumber.'<br>
    User name: '.$_SESSION["User"]["Name"].' '.$_SESSION["User"]["Surname"].'<br>
    User email: '.$_SESSION["User"]["Email"].'<br>
    Request type: '.$subject.'<br>
    Request subject: Creation of new tool <strong>'.$_REQUEST['toolid'].'</strong><br>
    Comments: '.$_REQUEST['comments'];

$messageUser = '
    Copy of the message sent to our technical team:<br><br>
    Ticket ID: '.$ticketnumber.'<br>
    User name: '.$_SESSION["User"]["Name"].' '.$_SESSION["User"]["Surname"].'<br>
    User email: '.$_SESSION["User"]["Email"].'<br>
    Request type: '.$subject.'<br>
    Request subject: Creation of new tool <strong>'.$_REQUEST['toolid'].'</strong><br>
    Comments: '.$_REQUEST['comments'].'<br><br>
    MuG VRE Technical Team';

if(sendEmail($GLOBALS['ADMINMAIL'], "[".$ticketnumber."]: ".$subject, $message, $_SESSION["User"]["Email"])) {

    sendEmail($_SESSION["User"]["Email"], "[".$ticketnumber."]: ".$subject, $messageUser, $_SESSION["User"]["Email"]);

    $GLOBALS['toolsDevMetaCol']->updateOne(array('_id' => $_REQUEST['toolid']),
                                 array('$set'   => array('last_status_date' => date('Y/m/d H:i:s'), 'last_status' => 'submitted')));

    $_SESSION['errorData']['Info'][] = "Tool successfully submitted, we will check it and give you an answer as soon as possible.";
    redirect($GLOBALS['BASEURL'].'admin/myNewTools.php');

} else {

    $_SESSION['errorData']['Error'][] = "Error Submitting tool, please try later.";
    redirect($GLOBALS['BASEURL'].'admin/myNewTools.php');

}
