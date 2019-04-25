<?php

require __DIR__."/../../config/bootstrap.php";

redirectToolDevOutside();

if(!isset($_REQUEST['id'])) {

	$_SESSION['errorData']['Error'][] = "Please provide a tool id.";
	redirect($GLOBALS['BASEURL'].'admin/myNewTools.php');
	
}

$toolDevJSON = $GLOBALS['toolsDevMetaCol']->findOne(array('_id' => $_REQUEST['id']));

if(!isset($toolDevJSON)) {
	$_SESSION['errorData']['Error'][] = "The tool id <strong>".$_REQUEST['toolid']."</strong> doesn't exist in our database.";
	redirect($GLOBALS['BASEURL'].'admin/myNewTools.php');
}

$toolDevMetaJSON = $GLOBALS['toolsDevMetaCol']->findOne(array('_id' => $_REQUEST['id'], 'user_id' => $_SESSION['User']['id']));

if(!isset($toolDevMetaJSON) && ($_SESSION['User']['Type'] != 0)) {
		$_SESSION['errorData']['Error'][] = "The tool id <strong>".$_REQUEST['toolid']."</strong> you are trying to edit doesn't belong to you.";
			redirect($GLOBALS['BASEURL'].'admin/myNewTools.php');
}

switch($_REQUEST["type"]) {

	case "io": echo '<pre>'.json_encode($toolDevJSON["step1"]["tool_io"], JSON_PRETTY_PRINT).'</pre>';
						break;

	case "sp": echo '<pre>'.json_encode($toolDevJSON["step3"]["tool_spec"], JSON_PRETTY_PRINT).'</pre>';
						break;

}

?>
