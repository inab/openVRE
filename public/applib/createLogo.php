<?php

require __DIR__."/../../config/bootstrap.php";

redirectToolDevOutside();

// Set the content-type
//header('Content-Type: image/png');

$toolid = $_GET["toolid"];

generateLogo($toolid);

redirect($GLOBALS['BASEURL'].'admin/myNewTools.php');
