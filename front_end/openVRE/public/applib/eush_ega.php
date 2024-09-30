<?php
header('Content-Type: application/json');

require __DIR__."/../../config/bootstrap.php";

if($_REQUEST) {
    // Get list of eurobioimaging projects
    if (isset($_REQUEST['action']) && $_REQUEST['action'] == "getEGAFiles"){
        $var = $_REQUEST['file_id'];
        echo getEGAFiles($var);
        exit;
    
    // Get user info
    } elseif(isset($_REQUEST['action']) && $_REQUEST['action'] == "getUser") {
        echo getUser($_SESSION['User']['id']);
        exit;
    }

    if (isset($_REQUEST['action']) && $_REQUEST['action'] == "getEGADatasets"){
        $var = $_REQUEST['file_id'];
        echo getEGADatasets($var);
        exit;
    
    // Get user info
    } elseif(isset($_REQUEST['action']) && $_REQUEST['action'] == "getUser") {
        echo getUser($_SESSION['User']['id']);
        exit;
    }
    // EGA outbox: sFTP requests    
    if (isset($_REQUEST['action']) && $_REQUEST['action'] == "listDatasets"){
        echo listEGADatasets();
	exit;
    }elseif (isset($_REQUEST['action']) && $_REQUEST['action'] == "listFiles" && $_REQUEST['ds_id'] ){
        echo listEGAFilesFromDataset($_REQUEST['ds_id']);
        exit;
    }


    if (isset($_REQUEST['action']) && $_REQUEST['action'] == "getEGADatasetsFromDSid"){
        $var = $_REQUEST['ds_id'];
        echo getEGADatasetsFromDSid($var);
        exit;

    // Get user info
    } elseif(isset($_REQUEST['action']) && $_REQUEST['action'] == "getUser") {
        echo getUser($_SESSION['User']['id']);
        exit;
    }

    if (isset($_REQUEST['action']) && $_REQUEST['action'] == "getEGAFilesFromDSid"){
        $var = $_REQUEST['ds_id'];
        echo getEGAFilesFromDSid($var);
        exit;

    // Get user info
    } elseif(isset($_REQUEST['action']) && $_REQUEST['action'] == "getUser") {
        echo getUser($_SESSION['User']['id']);
        exit;
    }

}
echo '{}';
exit;

