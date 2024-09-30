<?php

require __DIR__."/../../config/bootstrap.php";


if($_REQUEST){

	$inTools = $GLOBALS['toolsCol']->findOne(array('_id' => $_REQUEST['toolid']));
	$inToolsDev = $GLOBALS['toolsDevMetaCol']->findOne(array('_id' => $_REQUEST['toolid']));

	if(isset($inTools) || isset($inToolsDev)) {
		$_SESSION['errorData']['Error'][] = "The tool id <strong>".$_REQUEST['toolid']."</strong> is already chosen, please try with another.";
		redirect($GLOBALS['BASEURL'].'admin/newTool.php');
	} else {
		// insert in tools_dev and tools_dev_meta
		$spec = file_get_contents($GLOBALS['tool_dev_sample']);
		$spec = str_replace("my_tool_id", $_REQUEST['toolid'], $spec);
		$spec = json_decode($spec);

		$io = file_get_contents($GLOBALS['tool_io_dev_sample']);
		$io = str_replace("my_tool_id", $_REQUEST['toolid'], $io);
		$io = json_decode($io);	

		$meta = [
			"_id" => $_REQUEST['toolid'],
			"user_id" => $_SESSION['User']['id'],
			"step1" => [
				"status" => false,
				"date" => date('Y/m/d H:i:s'),
				"tool_io" => $io,
				"tool_io_validated" => false,
				"tool_io_saved" => false,
				"tool_io_files" => false,
				"input_files_combinations" => []
			],
			"step2" => [
				"status" => false,
				"date" => "",
				"type" => "", 
				"tool_code" => ""
			],
			"step3" => [
				"status" => false,
				"date" => "",
				"tool_spec" => $spec,
				"tool_spec_validated" => false,
				"tool_spec_saved" => false
			],
			// status:
			// in_preparation
			// submitted
			// to be revised
			// rejected
			// registered
			"last_status" => "in_preparation",
			"last_status_date" => date('Y/m/d H:i:s'),
			"status_history" => []
		];

		$GLOBALS['toolsDevMetaCol']->insertOne($meta);

		/*$working_dir = $GLOBALS['dataDir']."/".$_SESSION['User']['id']."/.dev/".$_REQUEST['toolid'];
    $working_dir = preg_replace('#/+#','/',$working_dir);
		if (!is_dir($working_dir)){
			mkpath($working_dir);
		}*/

		generateLogo($_REQUEST['toolid']);

		$_SESSION['errorData']['Info'][] = "The tool <strong>".$_REQUEST['toolid']."</strong> has been created, please check the steps.";
		redirect($GLOBALS['BASEURL'].'admin/myNewTools.php');

	}

}else{
	redirect($GLOBALS['BASEURL']);
}
