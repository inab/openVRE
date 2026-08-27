<?php

use OpenVRE\LoggerFactory;
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

		return new SwiftClient($appId, $appSecret);
	} catch (Throwable $e) {
		http_response_code(500);
		echo json_encode([
			'error' => 'Failed to initialize Swift client: ' . $e->getMessage()
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
		getObjectStorageLogger()->debug("getContainerFiles - container: $container");
		$containerList = $swiftClient->runListContainer($container);
		getObjectStorageLogger()->debug("getContainerFiles - containerList: " . print_r($containerList, true));
		$containerList = json_encode($containerList);
		if (json_last_error() !== JSON_ERROR_NONE) {
			$error_message = json_last_error_msg();
			return array('error' => "JSON encoding failed: $error_message");
		}

		return $containerList;
	}

	return array('error' => 'Container or Swift client is null');
}


function initiateFileDownload($swiftClient, $fileUrl, $container)
{
	// Set destination working directory/uploads
	$dataDirPath = getAttr_fromGSFileId($_SESSION['User']['dataDir'], "path");
	$wd = $dataDirPath . "/uploads";
	$wdP = $GLOBALS['dataDir'] . "/" . $wd;
	// Log paths for debugging
	getObjectStorageLogger()->debug("Data directory path: $dataDirPath");
	getObjectStorageLogger()->debug("Working directory (wd): $wd");
	getObjectStorageLogger()->debug("Working directory path (wdP): $wdP");
	getObjectStorageLogger()->debug("File URL: $fileUrl");

	// Ensure the output directory exists
	if (!is_dir($wdP) && !mkdir($wdP, 0775, true)) {
		getObjectStorageLogger()->error("Failed to create working directory: $wdP.");
		throw new UnexpectedValueException("Failed to create working directory: $wdP");
	}

	// Extract file name and relative path
	$fileName = basename($fileUrl);

	// Full path to save the file
	$fullPath = $wdP . '/' . $fileName;

	// Adjust fileUrl to remove any leading slashes if necessary
	$fileUrl = ltrim($fileUrl, '/');
	$downloadSuccess = $swiftClient->runDownloadFile($wdP . '/', $container, $fileUrl);
	getObjectStorageLogger()->debug("Command output: $downloadSuccess");

	getObjectStorageLogger()->debug("basename: $fileName");
	getObjectStorageLogger()->debug("Full path: $fullPath");
	if ($downloadSuccess) {
		// Handle successful download
		getObjectStorageLogger()->debug("File downloaded successfully to $fullPath");

		chmod($fullPath, 0666);
		$insertData = array(
			'owner' => $_SESSION['User']['id'],
			'size' => filesize($fullPath),
			'mtime' => new MongoDB\BSON\UTCDateTime(filemtime($fullPath) * 1000)
		);
		$metaData = array(
			'validated' => false
		);

		// Save the path with the directory structure in the database
		$fnId = uploadGSFileBNS("$wd/$fileName", $fullPath, $insertData, $metaData, false);

		if ($fnId == "0") {
			$errorMsg = "Error occurred while registering the downloaded file";
			getObjectStorageLogger()->error($errorMsg);
			return array('status' => 'error', 'message' => $errorMsg);
		} else {
			getObjectStorageLogger()->info("File registered successfully with ID: $fnId");
			return json_encode(array('status' => 'success', 'fileId' => $fnId));
		}
	} else {
		$errorMsg = "Failed to download file: $fileName";
		getObjectStorageLogger()->error($errorMsg);
		return array('status' => 'error', 'message' => $errorMsg);
	}
}
