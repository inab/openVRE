<?php

require __DIR__ . "/../../config/bootstrap.php";

redirectToolDevOutside();

$toolid = $_GET["toolid"];
generateLogo($toolid);
redirect($GLOBALS['BASEURL'] . 'admin/myNewTools.php');
