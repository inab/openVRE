<?php

require __DIR__."/../../config/bootstrap.php";

if(!$_REQUEST){
    redirect($GLOBALS['URL']);
}

if (!isset($_REQUEST['op'])){
    $_SESSION['errorData']['Internal'][]="Error. Cannot manage project. No 'op' set.";
    redirect($GLOBALS['BASEURL']."workspace/");
}

$dataDir_ant      = $_SESSION['User']['dataDir'];
$dataDir_ant_name = getAttr_fromGSFileId($dataDir_ant,"name");


//
// set project data from form

$projData=array();
if ($_REQUEST['op'] == "new" || $_REQUEST['op'] == "edit"){
    $projData  = array(
        "name"         => $_REQUEST['pr_name'],
        "description"  => $_REQUEST['pr_ldesc'],
        "keywords"     => $_REQUEST['pr_keywords']
    );
}

//
// create project folder
 
if ($_REQUEST['op'] == "new"){

    // create project folder
    $proj_code = createLabel_proj();
    $proj_sd   = $GLOBALS['sampleData_default'];

    $proj_id   = prepUserWorkSpace($_SESSION['User']['id'],$proj_code,$proj_sd,$projData);

    if (!$proj_id){
        // return error
        $_SESSION['errorData']['Error'][] = "Project not created";
    	redirect($GLOBALS['BASEURL']."workspace/");
    }

    $_REQUEST['pr_id']   = $proj_id;
    $_REQUEST['pr_code'] = $proj_code;
    $_SESSION['errorData']['Info'][] = "Done! New project '".$projData['name']."' created.";


//
// edit project

}elseif($_REQUEST['op'] == "edit"){

    $r = updateProject($_REQUEST['pr_id'],$projData);
    if (!$r){
        // return error
        $_SESSION['errorData']['Error'][] = "Project not edited";
    	redirect($GLOBALS['BASEURL']."workspace/");
    }
    $_SESSION['errorData']['Info'][] = "Done! Project '".$projData['name']."' successfully edited.";


//
// delete project
}elseif($_REQUEST['op'] == "deleteMsg"){ 

    print printProjectContent($_REQUEST['pr_id'],TRUE);
    die(0);

 
}elseif($_REQUEST['op'] == "delete"){ 

    $projs = getProjects_byOwner();
    if (count($projs) < 2){
        // return error
        $_SESSION['errorData']['Error'][] = "Cannot delete project. User needs at least one project to work with. Please, create a new one before deleting this.";
    	redirect($GLOBALS['BASEURL']."workspace/");
    }
    $r = deleteProject($_REQUEST['pr_id']);
    if (!$r){
        // return error
        $_SESSION['errorData']['Error'][] = "Project cannot be deleted";
    	redirect($GLOBALS['BASEURL']."workspace/");
    }
    $projs = getProjects_byOwner();
    $_REQUEST['pr_id'] = array_keys($projs)[0];

    $_SESSION['errorData']['Info'][] = "Done! Project successfully deleted";
}


//
// set active project (in SESSION and DB)
 
if ($_REQUEST['pr_id']){
    $proj_code="";
    if (!isset($_REQUEST['pr_code'])){
        $proj_fn   = getAttr_fromGSFileId($_REQUEST['pr_id'],"path");
        $_REQUEST['pr_code']= basename($proj_fn);

    }
    // update session
    $_SESSION['User']['activeProject']= $_REQUEST['pr_code'];
    $_SESSION['User']['dataDir']      = $_REQUEST['pr_id'];

    // update User in mongo
    modifyUser($_SESSION['User']['_id'],"activeProject",$_SESSION['User']['activeProject']);
    modifyUser($_SESSION['User']['_id'],"dataDir"      ,$_SESSION['User']['dataDir']);

    // print info message
    if ($_SESSION['User']['dataDir'] != $dataDir_ant){
        if (!isset($_REQUEST['pr_name'])){$_REQUEST['pr_name']= getAttr_fromGSFileId($_SESSION['User']['dataDir'],"name");}
        $_SESSION['errorData']['Info'][] = "Moving displayed workspace from project <b>'$dataDir_ant_name'</b> to project <b>'".$_REQUEST['pr_name']."'</b>";
    }
}

redirect($GLOBALS['BASEURL']."workspace/");

?>
