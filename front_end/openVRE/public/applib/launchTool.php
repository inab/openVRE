<?php

require __DIR__ . "/../../config/bootstrap.php";

redirectOutside();

function internalErrorRedirect()
{
	$_SESSION['errorData']['Internal'][] = "There was an internal error launching the tool.";
	redirect($GLOBALS['BASEURL'] . "workspace/");
}

$logger = LoggerFactory::getLogger('Tool launcher');

$logger->info("Tool launcher started.");
$logger->debug("Tool: " . $_REQUEST['tool']);
$logger->debug("Project: " . $_REQUEST['project']);
$logger->debug("Tool arguments: ", $_REQUEST['arguments'] ?? []);
$logger->debug("Tool input files: ", $_REQUEST['input_files'] ?? []);
$logger->debug("Tool input files from public directory: ", $_REQUEST['input_files_public_dir'] ?? []);
$logger->debug("Execution: " . $_REQUEST['execution']);
$logger->debug("Description: " . $_REQUEST['description']);

$tool = getTool_fromId($_REQUEST['tool'], true);

if (is_null($tool)) {
	$logger->error("Tool not found: " . $_REQUEST['tool']);
	internalErrorRedirect();
}

if (empty($_REQUEST['execution'])) {
	$logger->error("Execution is missing");
	internalErrorRedirect();
}

if (empty($_REQUEST['project'])) {
	$logger->error("Project is missing");
	internalErrorRedirect();
}

if (empty($tool['infrastructure']['interactive']) && empty($_REQUEST['input_files']) && empty($_REQUEST['input_files_public_dir'])) {
	$logger->error("Input files are missing");
	internalErrorRedirect();
}

$jobMeta  = new Tooljob($tool, $_REQUEST['execution'], $_REQUEST['project'], $_REQUEST['description'], $_REQUEST['arguments_exec']);

$logger->debug("Tool job metadata: ", ['jobMeta' => $jobMeta]);

$filesId = array();
foreach ($_REQUEST['input_files'] as $input_file) {
	if (is_array($input_file)) {
		$filesId = array_merge($filesId, $input_file);
	} else {
		if ($input_file) {
			array_push($filesId, $input_file);
		}
	}
}

$filesId = array_unique($filesId);
$files = array();
foreach ($filesId as $fileId) {
	$file = getGSFile_fromId($fileId);

	if (is_null($file)) {
		$logger->error("File not found: " . $fileId);
		internalErrorRedirect();
	}

	$files[$file['_id']] = $file;
	$associated_files = getAssociatedFiles_fromId($fileId);
	foreach ($associated_files as $assocId) {
		$assocFile = getGSFile_fromId($assocId);
		if (is_null($assocFile)) {
			$logger->error("Associated file " . $assocId . " not found");
			internalErrorRedirect();
		}
		$files[$assocFile['_id']] = $assocFile;
	}
}

$logger->debug("Input files with associated metadata: ", ['files' => $files]);

if (!empty($_REQUEST['arguments'])) {
	$jobMeta->setArguments($_REQUEST['arguments'], $tool);
}

$jobMeta->setInput_files($_REQUEST['input_files'], $tool, $files);

$logger->debug("Processed input files: ", ['input_files' => $jobMeta->input_files]);

foreach ($files as $fnId => $file) {
	$fn = getAttr_fromGSFileId($fnId, 'path');
	$rfn  = $GLOBALS['dataDir'] . "/$fn";

	$fileType = getAttr_fromGSFileId($fnId, 'type');
	if ($fileType === 'dir') {
		$logger->info("Directory '" . basename($fn) . "' detected. Skipping file-specific checks");
		continue;
	}

	if (!is_file($rfn)) {
		$logger->info("File '" . basename($fn) . "' is not found or has size zero.");
		$jobData = new DataTransfer($tool, $filesId, $_REQUEST['execution'], $_REQUEST['project'], $_REQUEST['description']);
	}
}

$files_pub = array();
if ($_REQUEST['input_files_public_dir']) {
	$files_pub = $jobMeta->createMetadata_from_Input_files_public($_REQUEST['input_files_public_dir'], $tool);
	$logger->debug("Input files public metadata: ", ['files_pub' => $files_pub]);
	if (!count($files_pub)) {
		redirect($GLOBALS['BASEURL'] . "workspace/");
	}

	$jobMeta->setInput_files_public($_REQUEST['input_files_public_dir'], $tool, $files_pub);
	$logger->debug("Processed input files public: ", ['input_files_public' => $jobMeta->input_files_pub]);
}

try {
	$jobMeta->createWorking_dir();
} catch (Exception $e) {
	$logger->error("Cannot create working directory.");
	redirect($GLOBALS['BASEURL'] . "workspace/");
}

$logger->debug("Working directory created at: ", ['working_dir' => $jobMeta->working_dir]);

try {
	$jobMeta->prepareExecution($tool, $files, $files_pub);
} catch (Exception $e) {
	$logger->error("Cannot prepare execution. " . $e->getMessage());
	redirect($GLOBALS['BASEURL'] . "workspace/");
}

try {
	$pid = $jobMeta->submit($tool);
} catch (Exception $e) {
	$logger->error("Cannot submit job. " . $e->getMessage());
	redirect($GLOBALS['BASEURL'] . "workspace/");
}

$logger->debug("Job submitted. PID = $pid");
addUserJob($_SESSION['User']['_id'], (array)$jobMeta, $jobMeta->pid);

redirect($GLOBALS['BASEURL'] . "workspace/");
