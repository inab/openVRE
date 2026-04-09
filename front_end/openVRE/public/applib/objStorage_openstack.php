<?php
header('Content-Type: application/json');

require __DIR__."/../../config/bootstrap.php";

function logError($errorMessage, $responseText = '') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['errorData']['Error'])) {
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

function logSuccess($successMessage) {
    session_start();
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
        $vaultUrl = $GLOBALS['vaultUrl'];
        $accessToken = json_decode($_SESSION['userVaultInfo']['jwt'], true)["access_token"];
        $vaultRolename = $_SESSION['userVaultInfo']['vaultRolename'];
        $username = $_POST['username'];
        try {
            $swiftClient = getSwiftClient($vaultUrl, $accessToken, $vaultRolename, $username);
        }
        catch (Throwable $e) {
            http_response_code(500);
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
	    error_log("Main script - received container: $container");
	    $vaultUrl = $GLOBALS['vaultUrl'];
	    $accessToken = $_SESSION['userToken']['access_token'];
	    $vaultRolename = $_SESSION['userVaultInfo']['vaultRolename'];
	    $username = $_POST['username'];
        try {
            $swiftClient = getSwiftClient($vaultUrl, $accessToken, $vaultRolename, $username);
        }
        catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(array('error' => 'Failed to obtain Swift client.'));
            exit;
        }
	    try {
            $files = getContainerFiles($container, $swiftClient);
            error_log("Main script - files: " . print_r($files, true));
            if (!$files) {
                throw new Exception('No files found or failed to fetch container files.');
            }
            echo json_encode([
                'status' => 'success',
                'message' => 'Files retrieved successfully.',
                'files' => $files
            ]);
            exit;
        } catch (Throwable $e) {
            http_response_code(500);
            error_log("Error fetching container files: " . $e->getMessage());
        
            echo json_encode([
                'error' => 'Failed to fetch container files: ' . $e->getMessage()
            ]);
            exit;
        }  
    }

    // Download file
    if (isset($_REQUEST['action']) && $_REQUEST['action'] === 'downloadFile' && isset($_POST['fileName'])) {
        $fileName = $_POST['fileName']; // Get the file URL (container/filename)
        $container = $_POST['container'];
        $vaultUrl = $GLOBALS['vaultUrl'];
        $accessToken = $_SESSION['userToken']['access_token'];
        $vaultRolename = $_SESSION['userVaultInfo']['vaultRolename'];
        $username = $_POST['username'];
        try {
            $swiftClient = getSwiftClient($vaultUrl, $accessToken, $vaultRolename, $username);
        }
        catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(array('error' => 'Failed to obtain Swift client.'));
            exit;
        }
        try {
            $success = initiateFileDownload($swiftClient, $fileName, $container);
            if (!$success) {
                throw new Exception('File download failed.');
            }
            echo json_encode([
                'status' => 'success',
                'message' => 'File downloaded successfully.',
                'fileId' => $success['fileId'],
                'fileName' => $success['fileName']
            ]);
            exit;
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'File download failed: ' . $e->getMessage()
            ]);
            exit;
        }
    }
}

echo '{}';
exit;
?>

