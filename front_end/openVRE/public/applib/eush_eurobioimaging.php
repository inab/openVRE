<?php
header('Content-Type: application/json');

require __DIR__."/../../config/bootstrap.php";

// Allow only registered users
//if(!checkLoggedIn()){
//    return '{}';
//}

if($_REQUEST) {
	// Get list of eurobioimaging projects
    if (isset($_REQUEST['action']) && $_REQUEST['action'] == "getProjects"){
        echo getEuroBioImagingProjects();
        exit;
	// Get user info
    } elseif(isset($_REQUEST['action']) && $_REQUEST['action'] == "getUser") {
        echo getUser($_SESSION['User']['id']);
	exit;
    }

    if ((isset($_REQUEST['action']) && $_REQUEST['action'] == "getSubjects" && $_REQUEST['project_id'])){
        $var = $_REQUEST['project_id'];
        echo getEuroBioImagingSubjects($var);
        exit;
    } elseif(isset($_REQUEST['action']) && $_REQUEST['action'] == "getUser") {
        echo getUser($_SESSION['User']['id']);
	exit;

    }

    if ((isset($_REQUEST['action']) && $_REQUEST['action'] == "getExperiments" && $_REQUEST['subject_id'] && $_REQUEST['project_id'])){
        $var = $_REQUEST['project_id'];
        $var2 = $_REQUEST['subject_id'];
        echo getEuroBioImagingExperiments($var, $var2);
        exit;
    } elseif(isset($_REQUEST['action']) && $_REQUEST['action'] == "getUser") {
        echo getUser($_SESSION['User']['id']);
	exit;

    }

    if ((isset($_REQUEST['action']) && $_REQUEST['action'] == "getExperimentsFormat" && $_REQUEST['subject_id'] && $_REQUEST['project_id'] && $_REQUEST['experiment_id'])){
        $var = $_REQUEST['project_id'];
        $var2 = $_REQUEST['subject_id'];
        $var3 = $_REQUEST['experiment_id'];
        echo getEuroBioImagingExperimentsFormat($var, $var2, $var3);
        exit;
    } elseif(isset($_REQUEST['action']) && $_REQUEST['action'] == "getUser") {
        echo getUser($_SESSION['User']['id']);
	exit;

    }

    if ((isset($_REQUEST['action']) && $_REQUEST['action'] == "getExperimentsFiles" && $_REQUEST['subject_id'] && $_REQUEST['project_id'] && $_REQUEST['experiment_id'])){
        $var = $_REQUEST['project_id'];
        $var2 = $_REQUEST['subject_id'];
        $var3 = $_REQUEST['experiment_id'];
        echo getEuroBioImagingExperimentsFiles($var, $var2, $var3);
        exit;
    } elseif(isset($_REQUEST['action']) && $_REQUEST['action'] == "getUser") {
        echo getUser($_SESSION['User']['id']);
	exit;

    }

    // Get list of eurobioimaging projects
    if (isset($_REQUEST['action']) && $_REQUEST['action'] == "getAuthorizedProjects"){
        //echo getUser($_SESSION['User']['id']);
        echo getEuroBioImagingAuthorizedProjects();
        exit;
    // Get user info
    } elseif(isset($_REQUEST['action']) && $_REQUEST['action'] == "getUser") {
        echo getUser($_SESSION['User']['id']);
    exit;
    }
        
    if ((isset($_REQUEST['action']) && $_REQUEST['action'] == "getAuthorizedSubjects" && $_REQUEST['project_id'])){
        $var = $_REQUEST['project_id'];
        echo getEuroBioImagingAuthorizedSubjects($var);
        exit;
    } elseif(isset($_REQUEST['action']) && $_REQUEST['action'] == "getUser") {
        echo getUser($_SESSION['User']['id']);
	exit;

    }

    if ((isset($_REQUEST['action']) && $_REQUEST['action'] == "getAuthorizedExperiments" && $_REQUEST['subject_id'] && $_REQUEST['project_id'])){
        $var = $_REQUEST['project_id'];
        $var2 = $_REQUEST['subject_id'];
        echo getEuroBioImagingAuthorizedExperiments($var, $var2);
        exit;
    } elseif(isset($_REQUEST['action']) && $_REQUEST['action'] == "getUser") {
        echo getUser($_SESSION['User']['id']);
	exit;

    }

    if ((isset($_REQUEST['action']) && $_REQUEST['action'] == "getAuthorizedExperimentsFormat" && $_REQUEST['subject_id'] && $_REQUEST['project_id'] && $_REQUEST['experiment_id'])){
        $var = $_REQUEST['project_id'];
        $var2 = $_REQUEST['subject_id'];
        $var3 = $_REQUEST['experiment_id'];
        echo getEuroBioImagingAuthorizedExperimentsFormat($var, $var2, $var3);
        exit;
    } elseif(isset($_REQUEST['action']) && $_REQUEST['action'] == "getUser") {
        echo getUser($_SESSION['User']['id']);
	exit;

    }
}
echo '{}';
exit;

