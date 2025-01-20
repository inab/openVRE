<?php

require __DIR__."/../../config/bootstrap.php";

redirectOutside();


// Check query
if(!$_REQUEST){
	redirect($GLOBALS['URL']);

}elseif (!isset($_REQUEST['account'])) {
	redirect($_SERVER['HTTP_REFERER']);
}

addUserLinkedAccount($_REQUEST['account'], $_REQUEST['action'], $_REQUEST['site_id'], $_POST);

?>
