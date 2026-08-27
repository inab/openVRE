<?php

session_start();

require __DIR__ . "/../../config/bootstrap.php";


redirectOutside();
header('Content-Type: application/json');
// Read inputs from the form
$siteList = $_REQUEST['sites']['site_list'] ?? [];
$files = $_REQUEST['files'] ?? [];
$tool = $_REQUEST['tool'] ?? [];
$workDirHost = $_REQUEST['workDirHost'] ?? null;
$execution = $_REQUEST['execution'] ?? [];
$arguments_exec = $_REQUEST['arguments_exec'] ?? [];

// Only sync if the site requires it
if (!in_array('MareNostrum', $siteList)) {
    echo json_encode(['status' => 'error', 'message' => 'Selected site does not require syncing']);
    exit;
}
try {
    $dataMeta = new DataTransfer($files, 'async', $tool, $workDirHost, $execution, $arguments_exec);
    $dataLocations = $dataMeta->syncFiles();

    // Store for later job submission
    $_SESSION['syncedDataLocations'] = $dataLocations;

    echo json_encode(['status' => 'success', 'dataLocations' => $dataLocations]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
exit;
