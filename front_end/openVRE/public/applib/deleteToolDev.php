<?php

require __DIR__."/../../config/bootstrap.php";

redirectOutside();

//
// Delete tool

$r = deleteToolDev($_REQUEST['toolid']);

if ($r == "0"){
    ?><script type="text/javascript">window.history.go(-1);</script><?php
    exit(0);
}

redirect($GLOBALS['BASEURL'].'admin/myNewTools.php');

?>
