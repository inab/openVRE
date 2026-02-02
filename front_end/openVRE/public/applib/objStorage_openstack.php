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
    session_start();
    if (is_null($_SESSION['errorData']['error'])) {
        $_SESSION['errorData']['error'] = ['Info' => []];
    }
    $_SESSION['errorData']['error'][] = $errorMessage;
    if (!empty($responseText)) {
        $_SESSION['errorData']['error'][] = 'Response: ' . $responseText;
    }
}

function logSuccess($successMessage)
{
    session_start();
    if (is_null($_SESSION['errorData']['Info'])) {
        $_SESSION['errorData']['Info'] = ['Info' => []];
    }
    $_SESSION['errorData']['Info'][] = $successMessage;
}

if ($_REQUEST) {
    if ($_REQUEST['action'] == "logError" && isset($_POST['errorMessage'])) {
        $errorMessage = $_POST['errorMessage'];
        $responseText = isset($_POST['responseText']) ? $_POST['responseText'] : '';
        logError($errorMessage, $responseText);
        echo json_encode(array('status' => 'error logged'));
        exit;
    }

    if ($_REQUEST['action'] == "logSuccess" && isset($_POST['successMessage'])) {
        $successMessage = $_POST['successMessage'];
        logSuccess($successMessage);
        echo json_encode(array('status' => 'success logged'));
        exit;
    }

    // Get user openstack credentials.
    if ($_REQUEST['action'] == "getOpenstackUser") {
        $accessToken = $_SESSION['userToken']->getToken();

        // Obtain the SwiftClient directly:
        $swiftClient = getSwiftClient($accessToken);

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

    // Get user info
    if (isset($_REQUEST['action']) && $_REQUEST['action'] == "getUser") {
        echo getUser($_SESSION['User']['id']);
        exit;
    }

    // Get container files
    if (isset($_REQUEST['action']) && $_REQUEST['action'] == "getContainerFiles" && isset($_POST['container'])) {
        $container = $_POST['container'];
        getObjectStorageOpenstackLogger()->info("Main script - received container: $container");
        $accessToken = $_SESSION['userToken']->getToken();

        $swiftClient = getSwiftClient($accessToken);

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
        $accessToken = $_SESSION['userToken']->getToken();

        // Obtain the SwiftClient directly:
        $swiftClient = getSwiftClient($accessToken);

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
}
