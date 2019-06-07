<?php

require __DIR__."/../../config/bootstrap.php";

redirectOutside();

if(! $_REQUEST['op']){
	$_SESSION['errorData']['getData'][]="Please specify an operation";
	redirect($GLOBALS['BASEURL']."/workspace/");
}

switch ($_REQUEST['op']){
	case 'rename':
		$file_raw = getGSFile_fromId($_REQUEST['id']);
		$file = formatData($file_raw);
	 	$p = explode("/",$file["path"]);
	 	$name = array_pop($p);	 
	 	$path = implode("/", $p);
	 	$returnData = ["path"	=> $path, "name" => $name, "type" => $file["type"]];
	 	print(json_encode($returnData));
	 	break;
	case 'move': 	$file_raw = getGSFile_fromId($_REQUEST['id']);
		$file = formatData($file_raw);
		$prjData = ["name" => $file['longfilename'], "execution" => $file['longexecutionname'], "project" => $file['project'], "type" => $file['type'], "projects" => []];
		$projects = getProjects_byOwner();
		foreach($projects as $pr) {
			$excData = [];
			foreach($pr["files"] as $execution) {
				$exc = getGSFile_fromId($execution);
				$p = explode("/",$exc["path"]);
				$name = array_pop($p);
				$excData[] = ["id" => $exc["_id"], "name" => $name, "path" => $exc["path"]];
			}
			$prjData["projects"][] = ["id" => $pr["_id"], "name" => $pr["name"], "path" => $pr["path"], "executions" => $excData];
		}
		print(json_encode($prjData, JSON_PRETTY_PRINT));
		break;
	default:
		die(0);
}
?>
