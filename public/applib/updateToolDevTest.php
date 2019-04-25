<?php

require __DIR__."/../../config/bootstrap.php";

redirectOutside();

$debug=0;

if ($debug){
	print "</br>RAW ARGUMENTS ARE<br/>";
	var_dump($_REQUEST['arguments']);
	print "<br/>";
	print "</br>RAW  INPUT_FILES ARE<br/>";
	var_dump($_REQUEST['input_files']);
	print "<br/>";
	print "</br>RAW  INPUT_FILES_PUBLIC ARE<br/>";
	var_dump($_REQUEST['input_files_public_dir']);
	print "<br/>";
	print "</br>METADATA<br/>";
	var_dump($_REQUEST['metadata']);
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

$tooldev = getToolDev_fromId($_REQUEST['toolid'],1);
$tool_io = $tooldev['step1']["tool_io"];

if (empty($tool_io)){
	$_SESSION['errorData']['Error'][]="Given tool 'id' (".$_REQUEST['toolid'].") has not definitions for input and output. Please, set a new one! <a href=\"admin/updateToolDevSpec.php?id=".$_REQUEST['toolid']."\">Update I/O definition again</a>";
    if ($debug){ exit(0);}
    ?><script type="text/javascript">window.history.go(-1);</script><?php
    exit(0);
}

//
// Start RegisterTool process to generate test files

if (!isset($_REQUEST['execution']) ){
    $_SESSION['errorData']['Internal'][]="Error creating test data for tool '".$_REQUEST['toolid']."'. 'execution' not received";
    if ($debug){ exit(0);}
    ?><script type="text/javascript">window.history.go(-1);</script><?php
    exit(0);
}

if (!isset($_REQUEST['tool_lib']) ){
    $_REQUEST['tool_lib']=0;
}

$submitTool  = new RegisterTool($tool_io,$_REQUEST['execution'],$_REQUEST['tool_executable'],$_REQUEST['tool_lib']);

if ($debug){
	print "<br/>NEW TOOL SUBMIT:</br>";
	var_dump($submitTool);
}

//
// Check input file requirements

if (!isset($_REQUEST['input_files'])){
    $_SESSION['errorData']['Error'][]="Tool is not receiving input files. Please, select them from your workspace table.";
    if ($debug){ exit(0);}
    ?><script type="text/javascript">window.history.go(-1);</script><?php
    exit(0);
}



//
// Get input_files medatada (TODO with associated_files)

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


$r = $submitTool->setMetadata_fromTool($_REQUEST['input_files'],$_REQUEST['metadata']);

$files = $submitTool->metadata;

if ($debug){
	print "<br/></br>TOTAL number of FILES given as params : ".count($filesId);
    print "<br/></br>TOTAL number of FILES (including associated) : ".count(array_keys($files))."</br>";
	//print "<br/></br>METADATA  for given files is :<br/>"; var_dump($submitTool->metadata);
}


//
// Get input_files medatada (from input_files_public_dir)

if (count($_REQUEST['input_files_public_dir'])){
    $r = $submitTool->setMetadata_fromTool($_REQUEST['input_files_public_dir'],$_REQUEST['metadata'],true);

    if ($debug){
	    print "<br/></br>METADATA PUBLIC for given files is :<br/>"; var_dump($submitTool->metadata_pub);
    }
}



// Set Arguments
if (!$_REQUEST['arguments']){
    $_REQUEST['arguments']=array();
}
$submitTool->setArguments($_REQUEST['arguments']);



//
// Check InputFiles

$r = $submitTool->setInput_files($_REQUEST['input_files']);
if ($debug){
	print "<br/>TOOL Input_files are (R=$r):</br>";
    var_dump($submitTool->input_files);
}

if ($r == "0"){
    if ($debug){ exit(0);}
    ?><script type="text/javascript">window.history.go(-1);</script><?php
    exit(0);
}


//
// Check InputFiles (from input_files_public_dir)

if (count($_REQUEST['input_files_public_dir'])){
    $r = $submitTool->setInput_files($_REQUEST['input_files_public_dir'],true);
    if ($debug){
	    print "<br/><br/>TOOL Public Input_files are (R=$r):</br>";
        var_dump($submitTool->input_files_pub);
    }

    if ($r == "0"){
        if ($debug){ exit(0);}
        ?><script type="text/javascript">window.history.go(-1);</script><?php
        exit(0);
    }
}


//
// Creating Config files

$r  = $submitTool->setConfiguration_files();#$_REQUEST['execution'],$_REQUEST['project']);

if ($debug){
    echo "<br/></br>Configuration Files are <br/>";
    var_dump($r);
}
if($r == "0"){
        if ($debug){ exit(0);}
        ?><script type="text/javascript">window.history.go(-1);</script><?php
        exit(0);
}

//
// Creating Metadata files

$r  = $submitTool->setMetadata_files();

if ($debug){
    echo "<br/></br>Metadata Files are. <br/>";
    var_dump($r);
}

if($r == "0"){
        if ($debug){ exit(0);}
        ?><script type="text/javascript">window.history.go(-1);</script><?php
        exit(0);
}

//
// Creating Bash Test files

$r  = $submitTool->setBash_files($_REQUEST['workflowtype']);

if ($debug){
    echo "<br/></br>Test Files are. <br/>";
    var_dump($r);
}

if($r == "0"){
        if ($debug){ exit(0);}
        ?><script type="text/javascript">window.history.go(-1);</script><?php
        exit(0);
}

//
// Creating TAR file with configuration files + metadata files + bash files 

$r  = $submitTool->tar_test_files();

if ($debug){
    echo "<br/></br>TAR Files returns: <br/>";
    var_dump($r);
}

if($r == "0"){
        if ($debug){ exit(0);}
        ?><script type="text/javascript">window.history.go(-1);</script><?php
        exit(0);
}

//
// Register generated test files 

$r  = $submitTool->save_test_files();

if ($debug){
    echo "<br/></br>Save te4st files returns:<br/>";
    var_dump($r);
}

if($r == "0"){
        if ($debug){ exit(0);}
        ?><script type="text/javascript">window.history.go(-1);</script><?php
        exit(0);
}

// Save REQUEST data to allow edit/update form data
//
$r  = $submitTool->save_form_data("step1",$_REQUEST);

if (!isset($_SESSION['errorData']['Error'])){
    $_SESSION['errorData']['Info'][]="Test files successfully generated! </br>";
}

if ($debug){
    print "<br/>#############################<br/>";
    var_dump($_SESSION['errorData']);
    unset($_SESSION['errorData']);
    print "<br/>#############################<br/>";
    exit(0);
}


redirect($GLOBALS['BASEURL'].'admin/myNewTools.php');

?>
