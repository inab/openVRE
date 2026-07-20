<?php

require __DIR__."/../../config/bootstrap.php";

redirectOutside();

if(! $_REQUEST['op']){
	$_SESSION['errorData']['getData'][]="Please specify an operation";
	redirect($GLOBALS['BASEURL']."/workspace/");
}

/**
 * Collect all directories under a project (depth-first) for move targets.
 */
function collectMoveTargetDirsRecursive($dirId, $projectPath, &$dirs)
{
	$dir = getGSFile_fromId($dirId);
	if (!$dir || !isset($dir['files'])) {
		return;
	}

	if (isset($dir['path']) && $dir['path'] == $_SESSION['User']['id']) {
		return;
	}

	$relPath = substr($dir['path'], strlen($projectPath) + 1);

	$dirs[] = [
		"id"   => $dir["_id"],
		"name" => $relPath,
		"path" => $dir["path"],
	];

	foreach ($dir['files'] as $childId) {
		$child = getGSFile_fromId($childId);
		if ($child && isset($child['files'])) {
			collectMoveTargetDirsRecursive($childId, $projectPath, $dirs);
		}
	}
}

function collectMoveTargetDirs($projectId, $projectPath)
{
	$dirs = [];
	$project = getGSFile_fromId($projectId);
	if (!isset($project['files'])) {
		return $dirs;
	}

	foreach ($project['files'] as $childId) {
		collectMoveTargetDirsRecursive($childId, $projectPath, $dirs);
	}

	return $dirs;
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
	case 'move':
		$file_raw = getGSFile_fromId($_REQUEST['id']);
		$file = formatData($file_raw);
		$parentPath = ($file['type'] == 'file' && !empty($file['parentDir']))
			? $file['parentDir']
			: '';
		$parentRelPath = '';
		if ($parentPath !== '' && preg_match('/(.*\/__PROJ[^\/]*)/', $parentPath, $match)) {
			$parentRelPath = substr($parentPath, strlen($match[1]) + 1);
		}

		$prjData = [
			"name"            => $file['longfilename'],
			"execution"       => $file['longexecutionname'],
			"project"         => $file['project'],
			"type"            => $file['type'],
			"parent_path"     => $parentPath,
			"parent_rel_path" => $parentRelPath,
			"projects"        => [],
		];
		$projects = getProjects_byOwner();
		foreach($projects as $pr) {
			$excData = collectMoveTargetDirs($pr['_id'], $pr['path']);
			$prjData["projects"][] = [
				"id"         => $pr["_id"],
				"name"       => $pr["name"],
				"path"       => $pr["path"],
				"executions" => $excData,
			];
		}
		print(json_encode($prjData, JSON_PRETTY_PRINT));
		break;
	default:
		die(0);
}
?>
