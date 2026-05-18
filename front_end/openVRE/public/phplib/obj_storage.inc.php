<?php

use OpenVRE\LoggerFactory;
use OpenVRE\RemoteSSH;
use OpenVRE\Site;
use OpenVRE\SwiftClient;
use OpenVRE\VaultClientFactory;


function getObjectStorageLogger()
{
	static $logger = null;

	if ($logger === null) {
		$logger = LoggerFactory::getLogger('Object storage interface');
	}

	return $logger;
}


function getSwiftClient()
{
	try {
		$vaultClient = VaultClientFactory::create();
		$credentials = $vaultClient->retrieveDatafromVault(Site::Swift);

		$appId = $credentials['app_id'];
		$appSecret = $credentials['app_secret'];

		$swiftClient = new SwiftClient(
			$appId,
			$appSecret
		);
		return $swiftClient;
	} catch (Throwable $e) {
		http_response_code(500);
		echo json_encode([
			'error' => 'Failed to initialize Swift client: ' . $e->getMessage()
		]);
		exit;
	}
}



function getSSHClient($remote_dir, $siteId)
{
	try {
		if (empty($_SESSION['userVaultInfo']['vaultKey'])) {
			throw new Exception('Missing Vault key for user: ' . $username);
		}

		$vaultClient = new VaultClient($vaultUrl, $accessToken, $vaultRolename, $username);
		$vaultKey = $_SESSION['userVaultInfo']['vaultKey'];
		$credentials = $vaultClient->retrieveDatafromVault($vaultKey, $vaultUrl, $GLOBALS['secretPath'], $_SESSION['User']['secretsId'], 'SSH');

		if ($credentials) {
			$sshPrivateKey = $credentials['private_key'];
			$sshUsername = $credentials['username'];
			$sshId = $credentials['_id'];

			// Set up the credentials array for the RemoteSSH class
			$sshCredentials = [
				'private_key' => $sshPrivateKey,
				'username' => $sshUsername
			];

			// Retrieve site info from the sites collection
			$siteDocument = $GLOBALS['sitesCol']->findOne(['_id' => $siteId]);

			if (!$siteDocument) {
				throw new Exception('Site document not found for site ID: ' . $siteId);
			}

			// Initialize the SSH client with retrieved credentials and site details
			$remoteSSH = new RemoteSSH($sshCredentials, $remote_dir, 22, $siteDocument['launcher']['http_server']);
			return $remoteSSH;
		}
	} catch (Throwable $e) {
		http_response_code(500);
		echo json_encode([
			'error' => 'Failed to initialize SSH client: ' . $e->getMessage()
		]);
		exit;
	}
}


function getContainers($swiftClient)
{
	$lista = $swiftClient->runList();
	$lista = json_encode($lista);
	if (json_last_error() !== JSON_ERROR_NONE) {
		$error_message = json_last_error_msg();
		return array('error' => "JSON encoding failed: $error_message");
	}
	return $lista;
}


function getContainerFiles($container, $swiftClient)
{

	if ($container !== null && $swiftClient !== null) {

		error_log("getContainerFiles - container: $container");
		$containerList = $swiftClient->runListContainer($container);

		error_log("getContainerFiles - containerList: " . print_r($containerList, true));

		$containerList = json_encode($containerList);
		if (json_last_error() !== JSON_ERROR_NONE) {
			$error_message = json_last_error_msg();
			return array('error' => "JSON encoding failed: $error_message");
		}

		return $containerList;
	} else {
		return array('error' => 'Container or Swift client is null');
	}
}

function initiateFileDownload($swiftClient, $fileUrl, $container)
{
	// Set destination working directory/uploads
	$dataDirPath = getAttr_fromGSFileId($_SESSION['User']['dataDir'], "path");
	$wd = $dataDirPath . "/uploads";
	$wdP = $GLOBALS['dataDir'] . "/" . $wd;
	// Log paths for debugging
	error_log("Data directory path: $dataDirPath");
	error_log("Working directory (wd): $wd");
	error_log("Working directory path (wdP): $wdP");
	error_log("File URL: $fileUrl");

	// Ensure the output directory exists
	if (!is_dir($wdP) && !mkdir($wdP, 0775, true)) {
		getObjectStorageLogger()->error("Failed to create working directory: $wdP.");
		throw new UnexpectedValueException("Failed to create working directory: $wdP");
	}

	// Extract file name and relative path
	$fileName = basename($fileUrl);
	//$relativePath = dirname($fileUrl);

	// Full path to save the file
	$fullPath = $wdP . '/' . $fileName;

	// Adjust fileUrl to remove any leading slashes if necessary
	$fileUrl = ltrim($fileUrl, '/');
	$downloadSuccess = $swiftClient->runDownloadFile($wdP . '/', $container, $fileUrl);
	error_log("Command output: $downloadSuccess");

	error_log("basename: $fileName");
	error_log("Full path: $fullPath");
	if ($downloadSuccess) {
		// Handle successful download
		error_log("File downloaded successfully to $fullPath");

		chmod($fullPath, 0666);
		$insertData = array(
			'owner' => $_SESSION['User']['id'],
			'size' => filesize($fullPath),
			'mtime' => new MongoDB\BSON\UTCDateTime(filemtime($fullPath) * 1000)
		);
		$metaData = array(
			'validated' => FALSE
		);

		error_log("primo input: $wd/$fileName");
		error_log("fullPath $fullPath");
		error_log("fullPath $fileUrl");

		error_log("fullPath $wd/$fileName");


		// Save the path with the directory structure in the database
		$fnId = uploadGSFileBNS("$wd/$fileName", $fullPath, $insertData, $metaData, FALSE);

		error_log("fnId: $fnId");
		if ($fnId == "0") {

			$errorMsg = "Error occurred while registering the downloaded file";
			$_SESSION['errorData']['upload'] = $errorMsg;
			error_log($errorMsg);
			return array('status' => 'error', 'message' => $errorMsg);
		} else {
			// Successfully registered the file
			error_log("File registered successfully with ID: $fnId");
			return json_encode(array('status' => 'success', 'fileId' => $fnId));
		}
	} else {
		// Handle download failure

		$errorMsg = "Failed to download file: $fileName";
		error_log($errorMsg);
		return array('status' => 'error', 'message' => $errorMsg);
	}
}
