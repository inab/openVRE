<?php

require __DIR__."/../../config/bootstrap.php";

if($_REQUEST){

	$data_json = json_decode($_REQUEST['json_tool'], true);
	
	if(!isset($data_json["_id"])) {
		$_SESSION['errorData']['Error'][] = "You are not allowed to remove '_id' field.";
		redirect($GLOBALS['BASEURL'].'admin/jsonTestValidator.php?id='.$_REQUEST['toolid']);
	}

	if($data_json["_id"] != $_REQUEST['toolid']) {
		$_SESSION['errorData']['Error'][] = "You are not allowed to change '_id' value.";
		redirect($GLOBALS['BASEURL'].'admin/jsonTestValidator.php?id='.$_REQUEST['toolid']);
	}

	$data = $GLOBALS['toolsDevMetaCol']->findOne(array('_id' => $_REQUEST['toolid']));

	if(!isset($data)) {
		$_SESSION['errorData']['Error'][] = "Tool id unexisting.";
		redirect($GLOBALS['BASEURL'].'admin/jsonTestValidator.php?id='.$_REQUEST['toolid']);
	}

	// Validate
	$validator = new JsonSchema\Validator();
	$validator->check(json_decode($_REQUEST['json_tool']), (object) array('$ref' => 'file://'.$GLOBALS['tool_io_json_schema']));

	if ($validator->isValid()) {
		$json_validated = true;
		$msg = "JSON validated and saved, please complete the form to finish this step.";
	} else {
		$json_validated = false;
		$msg = "Tool specification saved but it doesn't validate against our JSON Schema.";
	}

	//var_dump($validated);die();

	/*$GLOBALS['toolsDevCol']->remove(array('_id'=> $_REQUEST["toolid"]));
	$GLOBALS['toolsDevCol']->insert($data_json);*/

	$GLOBALS['toolsDevMetaCol']->updateOne(array('_id' => $_REQUEST['toolid']),
		array('$set'   => array(
			'last_status_date' => date('Y/m/d H:i:s'), 
			'step1.tool_io' => $data_json, 
			'step1.date' => date('Y/m/d H:i:s'), 
			'step1.tool_io_validated' => $json_validated, 
			'step1.tool_io_saved' => true,
			'step3.tool_spec.input_files' => $data_json["input_files"],
			'step3.tool_spec.input_files_public_dir' => $data_json["input_files_public_dir"],
			'step3.tool_spec.input_files_combinations' => $data_json["input_files_combinations"],
			'step3.tool_spec.arguments' => $data_json["arguments"],
			'step3.tool_spec.output_files' => $data_json["output_files"]
		)));


	$_SESSION['errorData']['Info'][] = $msg;

	if ($validator->isValid()) {
		redirect($GLOBALS['BASEURL'].'admin/createTest.php?id='.$_REQUEST['toolid']);
	} else {
		redirect($GLOBALS['BASEURL'].'admin/myNewTools.php');
	}

	

}else{
	redirect($GLOBALS['BASEURL']);
}
