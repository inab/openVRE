<?php

require __DIR__."/../../config/bootstrap.php";

redirectOutside();

$debug=0;

$SGE_updated = getUserJobs($_SESSION['User']['id']);

if ($debug){
	print "</br>REQUEST FILES ARE [FN]:</br>";
	var_dump($_REQUEST['fn']);
	print "<br/>";
	print "</br>RAW ARGUMENTS ARE<br/>";
	var_dump($_REQUEST['arguments']);
	print "<br/>";
	print "</br>RAW  INPUT_FILES ARE<br/>";
	var_dump($_REQUEST['input_files']);
	print "<br/>";
	print "</br>RAW  INPUT_FILES PUBLIC ARE<br/>";
	var_dump($_REQUEST['input_files_public_dir']);
	print "<br/>";
	foreach ($_REQUEST as $k=>$v){
	if ($k!="arguments" && $k!="input_files" && $k!="input_files_public_dir" && $k!="fn"){
		print "<br/><br/>REQUEST[$k]</br>";
		var_dump($v);
	}
	}
}
//
// Get tool.

$tool = getTool_fromId($_REQUEST['tool'],1);
if (empty($tool)){
	$_SESSION['errorData']['Error'][]="Tool not specified or not registered. Please, register '".$_REQUEST['tool']."'";
  	redirect($GLOBALS['BASEURL']."workspace/");
}

if (!isset($_REQUEST['execution']) || !isset($_REQUEST['project'])){
    $_SESSION['errorData']['Internal'][]="Error launching tool. 'execution' or 'project' are not received";
    redirect($GLOBALS['BASEURL']."workspace/");
}

if ($debug){
	print "<br/>SYSTEM FOR JOB EXECUTION</br>";
	var_dump($_REQUEST['arguments_exec']);
}
// Check input file requirements

if (!isset($_REQUEST['input_files']) && !isset($_REQUEST['input_files_public_dir'])){
    $_SESSION['errorData']['Error'][]="Tool is not receiving input files. Please, select them from your workspace table.";
    redirect($GLOBALS['BASEURL']."workspace/");
}

// Get input_files medatada (with associated_files)

$files   = Array(); // distinct file Objs to stage in 

$filesId = Array();
foreach($_REQUEST['input_files'] as $input_file){
    if (is_array($input_file)){
	    $filesId = array_merge($filesId,$input_file);
    }else{
        if ($input_file)
            array_push($filesId,$input_file);
    }
}
$filesId=array_unique($filesId);

if ($debug){
        print "<br/></br> FILES ID";
        var_dump($filesId);
}


foreach ($filesId as $fnId){
    $file = getGSFile_fromId($fnId);

    if (!$file){
        $_SESSION['errorData']['Error'][]="Input file $fnId does not belong to current user or has been not properly registered. Stopping execution";
    	redirect($GLOBALS['BASEURL']."workspace/");
    }
    $files[$file['_id']]=$file;
    $associated_files = getAssociatedFiles_fromId($fnId);
    foreach ($associated_files as $assocId){
    	$assocFile = getGSFile_fromId($assocId);
    	if (!$assocFile){
        	$_SESSION['errorData']['Error'][]="File associated to ".basename($file['path'])." ($assocId) does not belong to current user or has been not properly registered. Stopping execution";
  		redirect($GLOBALS['BASEURL']."workspace/");
	    }
    	$files[$assocFile['_id']]=$assocFile;
    }
}

if ($debug){
	print "<br/></br> INPUT FILES";
	var_dump($files);
	print "<br/></br> INPUT FILES";
	var_dump($filesId);
	print "<br/></br>TOTAL number of FILES given as params : ".count($filesId);
	print "<br/></br>TOTAL number of FILES (including associated) : ".count(array_keys($files))."</br>";
}


//
// Checking input_files locally

foreach ($files as $fnId => $file) {
    $fn   = getAttr_fromGSFileId($fnId,'path');

    // Check if the file is a directory
    $isDir = getAttr_fromGSFileId($fnId, 'is_dir');

    // If it's not a directory, proceed with file-specific checks


    $rfn  = $GLOBALS['dataDir']."/$fn";

    if (!is_file($rfn)){
	    $_SESSION['errorData']['Error'][]="File '".basename($fn)."' is not found or has size zero. Checking other locations available.";
	}

}

// Asyncronous

$pipeline = new Pipeline($tool, $_REQUEST['execution'], $_REQUEST['project'], $_REQUEST['description'], $_REQUEST['arguments_exec']);
$pipelineDirId = $pipeline->createWorkingDir();
if ($debug){
	echo "<br/></br>JDIR ID = $pipelineDirId<br/>";
}
 // Create pipeline-level directory first
// Define and add stages
$pipeline->addStage(new StageInData($pipeline, $files, $tool, $_REQUEST['execution'], $_REQUEST['arguments_exec'],  $pipelineDirId, ));
// output de esos tiene que ser submission_file 
//$pipeline->addStage(new StageInJobConfigs($pipeline, $containerImage,  $pipelineDirId,)); //config jsons too 
//$pipeline->addStage(new JobExecution($pipeline, $tool, $_REQUEST['execution'], $_REQUEST['project'], $_REQUEST['description'], $_REQUEST['arguments_exec'], $_REQUEST['input_files'],$pipelineDirId )); // Assuming $files_pub is empty
//$pipeline->addStage(new StageOutData($pipeline, $outputFiles, $destinationPath, $pipelineDirId)); // data_trasfer_diff x MongoDB, log, output_metadata, 

// Run all stages
//$pipeline->run(); //sge define dependencies for each stage --> run as a new ProcessSGE()
// qsub -hold_jid job_id of the dependecies job  

//$pid = $jobMeta->submit($tool);	


if ($debug){
	echo "<br/></br>JOB SUBMITTED. PID = $pid<br/>";
}
if(!$pid)
  	redirect($GLOBALS['BASEURL']."workspace/");


if ($debug){
	print "<br/>ERROR_DATA<br/>";
	var_dump($_SESSION['errorData']);
	unset($_SESSION['errorData']);
	print "</br><br/>JOB_META END<br/>";
	var_dump((array)$jobMeta);
}

if ($debug)
	echo "<br/>Saving JOB MEDATA  USER <br/>";

addUserJob($_SESSION['User']['_id'],(array)$jobMeta,$jobMeta->pid);

if ($debug)
	exit(0);

if (!isset($_SESSION['errorData']['Error'])){
    $proj = getProject($jobMeta->project);
    $_SESSION['errorData']['Info'][]="Job successfully sent! Monitor it at <b>".$proj['name']." &rsaquo; ".$jobMeta->execution." &rsaquo; ".$jobMeta->title."</b>.";
    if ($_SESSION['User']['activeProject'] != $jobMeta->project){
        $projWS = getProject($_SESSION['User']['activeProject']);
        $_SESSION['errorData']['Info'][]="Notice that your current workspace belongs to project '".$projWS['name']."'. Move to '".$proj['name']."' to check out your job.";
    }
}

redirect($GLOBALS['BASEURL']."workspace/");
/*

// Create working_dir
$pipelineDirId = $pipeline->createWorking_dir();  //run33

// Stage in 1 (input_files)

$dataMeta = new DataTransfer(
    $files, 
    'async', 
    $tool['_id'], 
   // $workDirHost,  cambiando y ponerlo en la funcion SyncFiles --> como stageinDataDir
    workingDirPath: $_REQUEST['execution'], 
	$_REQUEST['arguments_exec']
);

$stageinDataDir = $DataTransfer->createWorking_dir($pipelineDirId); 	
list($execCommand, $inputLocations) = $dataMeta->syncFiles(); //hasta el prepare linea 99 
  
$result = $dataMeta->prepareRSyncExecution(); //add con 
if ($debug) {		
	print "<br/>Data Transfer Locations:</br>";	
	var_dump($dataLocations); // This will show where the files will be transferre
}
	

// Tooljob

$jobMeta  = new Tooljob($tool,$_REQUEST['execution'],$_REQUEST['project'],$_REQUEST['description'], $_REQUEST['arguments_exec']); 

if ($debug){
	print "<br/>NEW TOOLJOB SET:</br>";
	var_dump($jobMeta);
}

//
// Set Arguments
if (!$_REQUEST['arguments']){
    $_REQUEST['arguments']=array();
}
$jobMeta->setArguments($_REQUEST['arguments'],$tool);

//
// Set InputFiles
$r = $jobMeta->setInput_files($_REQUEST['input_files'],$tool,$files);

if ($debug){
	print "<br/>TOOL Input_files are:</br>";
    var_dump($jobMeta->input_files);
}

if ($r == "0"){
    redirect($_SERVER['HTTP_REFERER']);
}


$workDirId = $jobMeta->createWorking_dir($pipelineDirId); //run33/id_tool para casa fase 

if ($debug){
	echo $workDirId;
	echo "<br/></br>WD CREATED SCCESSFULLY AT: $jobMeta->working_dir<br/>";
}

if (!$workDirId){
    	redirect($_SERVER['HTTP_REFERER']);
}

//
// Setting Command line. Adding parameters
// 
//$r = $jobMeta->prepareExecution($tool,$files,$files_pub);
$r = $jobMeta->prepareExecution($tool,$files,$files_pub);
if ($debug)
	echo "<br/></br>PREPARE EXECUTION RETURNS ($r). <br/>";
if($r == 0){
    	redirect($_SERVER['HTTP_REFERER']);
}
// Set InputFilesPublic from public directory

$files_pub = array();
if ($_REQUEST['input_files_public_dir']){

    //Get input_file public data  medatadata
    $files_pub  = $jobMeta->createMetadata_from_Input_files_public($_REQUEST['input_files_public_dir'],$tool);
    if ($debug){
    	print "<br/>TOOL METADATA for Input_files_public are:</br>";
    	var_dump($files_pub);
    }
    if (!count($files_pub)){
    	redirect($_SERVER['HTTP_REFERER']);
    }

    // Set InputFiles public dir
    $r = $jobMeta->setInput_files_public($_REQUEST['input_files_public_dir'],$tool,$files_pub);
    if ($debug){
    	print "<br/>TOOL Input_files public are:</br>";
    	var_dump($jobMeta->input_files_pub);
    }
    if ($r == "0"){
	print "<br/>TOOL Input_files ZERO</br>";
    	redirect($_SERVER['HTTP_REFERER']);
    }
}

// Stageout -- missing 
// Launching Tooljob
// once $jobId_data  
// ----


//Calling pipeline to execute ---missing
//Somewhere after this stageout
// return jobId in async mode --> prepare
*/



