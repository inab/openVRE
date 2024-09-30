<?php
header('Content-Type: application/json');

require __DIR__."/../../config/bootstrap.php";

// Allow only registered users
//if(!checkLoggedIn()){
//    return '{}';
//}

if($_REQUEST) {
    // Get list of EGA datasets
    
    if (isset($_REQUEST['action']) && $_REQUEST['action'] == "listDatasets"){
        echo listEGADatasets();
	exit;

	// Get list of EGA files belonging to the given datasets
    }elseif (isset($_REQUEST['action']) && $_REQUEST['action'] == "listFiles" && $_REQUEST['dataset_id'] ){
        echo listEGAFilesFromDataset($_REQUEST['dataset_id']);
	exit;

    }
}
echo '{}';
exit;

