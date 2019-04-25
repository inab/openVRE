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

//
// Set Tooljob

/*
    if (!isset($_REQUEST['execution']) ){ ## TODO: TEMPORAL HACK TO ENSURE WE HAVE EXECUTION VAR. WAIT UNTIL GENIS MODERNIZE ALL FORMS
    $_REQUEST['execution'] = $_REQUEST['project'];
    $_REQUEST['project'] = "99";
    }
 */

if (!isset($_REQUEST['execution']) || !isset($_REQUEST['project'])){
    $_SESSION['errorData']['Internal'][]="Error launching tool. 'execution' or 'project' are not received";
    redirect($GLOBALS['BASEURL']."workspace/");
}

$jobMeta  = new Tooljob($tool,$_REQUEST['execution'],$_REQUEST['project'],$_REQUEST['description']); 

if ($debug){
	print "<br/>NEW TOOLJOB SET:</br>";
	var_dump($jobMeta);
}

//
// Check input file requirements

if (!isset($_REQUEST['input_files'])){
    $_SESSION['errorData']['Error'][]="Tool is not receiving input files. Please, select them from your workspace table.";
    redirect($GLOBALS['BASEURL']."workspace/");
}



//
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
	print "<br/></br>TOTAL number of FILES given as params : ".count($filesId);
	print "<br/></br>TOTAL number of FILES (including associated) : ".count(array_keys($files))."</br>";
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
//
// Checking input_files locally

foreach ($files as $fnId => $file) {

    $fn   = getAttr_fromGSFileId($fnId,'path');
    $rfn  = $GLOBALS['dataDir']."/$fn";
    if (! is_file($rfn)){
        $_SESSION['errorData']['Error'][]="File '".basename($fn)."' is not found or has size zero. Stopping execution";
        ?><script type="text/javascript">//window.history.go(-1);</script><?php
        //exit(0);
    	redirect($_SERVER['HTTP_REFERER']);
    }

}

//
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
    	redirect($_SERVER['HTTP_REFERER']);
    }
}

// Stage in (fake)  TODO

//
// Create working_dir
$jobId = $jobMeta->createWorking_dir();

if ($debug)
	echo "<br/></br>WD CREATED SCCESSFULLY AT: $jobMeta->working_dir<br/>";

if (!$jobId){
    	redirect($_SERVER['HTTP_REFERER']);
}

//
// Setting Command line. Adding parameters


$r = $jobMeta->prepareExecution($tool,$files,$files_pub);

if ($debug)
	echo "<br/></br>PREPARE EXECUTION RETURNS ($r). <br/>";
if($r == 0){
    	redirect($_SERVER['HTTP_REFERER']);
}

//
// Launching Tooljob

$pid = $jobMeta->submit($tool);		

if ($debug)
	echo "<br/></br>JOB SUBMITTED. PID = $pid<br/>";

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

?>
