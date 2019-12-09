<?php

require __DIR__."/../../config/bootstrap.php";

if($_REQUEST){

	$data_json = json_decode($_REQUEST['json_tool'], true);
	
	if(!isset($data_json["_id"])) {
		$_SESSION['errorData']['Error'][] = "You are not allowed to remove '_id' field.";
		redirect($GLOBALS['BASEURL'].'admin/jsonSpecValidator.php?id='.$_REQUEST['toolid']);
	}

	if($data_json["_id"] != $_REQUEST['toolid']) {
		$_SESSION['errorData']['Error'][] = "You are not allowed to change '_id' value.";
		redirect($GLOBALS['BASEURL'].'admin/jsonSpecValidator.php?id='.$_REQUEST['toolid']);
	}

	$data = $GLOBALS['toolsDevMetaCol']->findOne(array('_id' => $_REQUEST['toolid']));

	if(!isset($data)) {
		$_SESSION['errorData']['Error'][] = "Tool id unexisting.";
		redirect($GLOBALS['BASEURL'].'admin/jsonSpecValidator.php?id='.$_REQUEST['toolid']);
	}

	// Validate
	$validator = new JsonSchema\Validator();
	$validator->check(json_decode($_REQUEST['json_tool']), (object) array('$ref' => 'file://'.$GLOBALS['tool_json_schema']));

	if ($validator->isValid()) {
		$validated = true;
		$msg = "Tool specification complete, please submit tool.";
	} else {
		$validated = false;
		$msg = "Tool specification saved but it doesn't validate against our JSON Schema.";
	}

	$GLOBALS['toolsDevMetaCol']->updateOne(array('_id' => $_REQUEST['toolid']),
                                 array('$set'   => array('last_status_date' => date('Y/m/d H:i:s'), 'step3.tool_spec' => $data_json, 'step3.date' => date('Y/m/d H:i:s'), 'step3.status' => $validated, 'step3.tool_spec_validated' => $validated, 'step3.tool_spec_saved' => true)));
	
	//$data_json["name"]
	$working_dir = $GLOBALS['dataDir']."/".$_SESSION['User']['id']."/.dev/".$data_json["_id"];
	$working_dir = preg_replace('#/+#','/',$working_dir);
	if (!is_dir($working_dir)){
		mkpath($working_dir);
		generateLogo($_REQUEST['toolid']);
	}

	$_SESSION['errorData']['Info'][] = $msg;
	redirect($GLOBALS['BASEURL'].'admin/myNewTools.php');

}else{
	redirect($GLOBALS['BASEURL']);
}
