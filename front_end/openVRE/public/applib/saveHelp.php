<?php

require __DIR__."/../../config/bootstrap.php";

if($_POST){
	$newdata = array('$set' => array('content' => $_REQUEST["content"], 'title' => $_REQUEST["title"]));
	$GLOBALS['helpsCol']->updateOne(array('help' => $_REQUEST["help"], 'tool' => $_REQUEST["tool"]), $newdata);

	echo '{"ok":true}';

}else{
	redirect($GLOBALS['URL']);
}



