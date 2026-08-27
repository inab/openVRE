<?php
header('Content-Type: application/json');

require __DIR__ . "/../../config/bootstrap.php";

use OpenVRE\LoggerFactory;


function getObjectStorageOpenstackLogger()
{
    static $logger = null;

    if ($logger === null) {
        $logger = LoggerFactory::getLogger('Object storage OpenStack interface');
    }

    return $logger;
}


function logError($errorMessage, $responseText = '')
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (is_null($_SESSION['errorData']['Error'])) {
        $_SESSION['errorData']['Error'] = [];
    }

    $_SESSION['errorData']['Error'][] = $errorMessage;

    if (!empty($responseText)) {
        $_SESSION['errorData']['Error'][] = 'Response: ' . $responseText;
    }
    header('Content-Type: application/json');
    // Also echo JSON so JS can see it instantly
    echo json_encode([
        'error' => true,
        'message' => $errorMessage,
        'response' => $responseText
    ]);
    exit;
}


// Get user openstack credentials.
if ($_REQUEST['action'] == "getOpenstackUser") {
    $swiftClient = getSwiftClient();
    if (!$swiftClient) {
        logError('Failed to obtain Swift client.');
        echo json_encode(array('error' => 'Failed to obtain Swift client.'));
        exit;
    }
    $_SESSION['swiftClient'] = $swiftClient;
    $containers = getContainers($swiftClient);
    echo json_encode($containers);
    exit;
}

// Get container files
if (isset($_REQUEST['action']) && $_REQUEST['action'] == "getContainerFiles" && isset($_POST['container'])) {
    $container = $_POST['container'];
    getObjectStorageOpenstackLogger()->info("Main script - received container: $container");
    $swiftClient = getSwiftClient();

    if (!$swiftClient) {
        logError('Failed to obtain Swift client.');
        echo json_encode(array('error' => 'Failed to obtain Swift client.'));
        exit;
    }
    $files = getContainerFiles($container, $swiftClient);
    getObjectStorageOpenstackLogger()->info("Main script - files: " . print_r($files, true));

    echo json_encode($files);
    exit;
}

// Download file
if (isset($_REQUEST['action']) && $_REQUEST['action'] === 'downloadFile' && isset($_POST['fileName'])) {
    $fileName = $_POST['fileName']; // Get the file URL (container/filename)
    $container = $_POST['container'];
    $swiftClient = getSwiftClient();

    if (!$swiftClient) {
        logError('Failed to obtain Swift client.');
        echo json_encode(array('error' => 'Failed to obtain Swift client.'));
        exit;
    }

    try {
        $fileId = initiateFileDownload($swiftClient, $fileName, $container);
        getObjectStorageOpenstackLogger()->info("File downloaded successfully. File ID: " . $fileId . " is present in the workspace.");
        echo json_encode(array('status' => 'success', 'fileId' => $fileId));
    } catch (Exception $e) {
        getObjectStorageOpenstackLogger()->error("File download failed: " . $e->getMessage());
        echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
    }
}
