<?php

require __DIR__."/../../config/bootstrap.php";

if($_REQUEST){

	$data = $GLOBALS['toolsDevMetaCol']->findOne(array('_id' => $_REQUEST['toolid']));

	if(!isset($data)) {
		$_SESSION['errorData']['Error'][] = "Tool id unexisting.";
		redirect($GLOBALS['BASEURL'].'admin/vmURL.php?id='.$_REQUEST['toolid']);
	}

	$GLOBALS['toolsDevMetaCol']->updateOne(array('_id' => $_REQUEST['toolid']),
                                 array('$set'   => array('last_status_date' => date('Y/m/d H:i:s'), 'step2.date' => date('Y/m/d H:i:s'), 'step2.status' => true, 'step2.type' => $_REQUEST['type'], 'step2.tool_code' => $_REQUEST['vm-code'])));

	$_SESSION['errorData']['Info'][] = "Tool code path successfully saved, please go to next step (tool specification).";
	redirect($GLOBALS['BASEURL'].'admin/myNewTools.php');

}else{
	redirect($GLOBALS['BASEURL']);
}
