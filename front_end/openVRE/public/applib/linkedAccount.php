<?php

require __DIR__."/../../config/bootstrap.php";
#require_once __DIR__."/../../public/phplib/classes/Vault.php";

redirectOutside();


// Check query
if(!$_REQUEST){
	redirect($GLOBALS['URL']);

}elseif (!isset($_REQUEST['account'])) {
	redirect($_SERVER['HTTP_REFERER']);
}



//echo ($_REQUEST['account']);
//var_dump($_REQUEST['action']);
//var_dump($_POST); 

addUserLinkedAccount($_REQUEST['account'], $_REQUEST['action'], $_REQUEST['site_id'], $_POST);

?>
