<?php

use OpenVRE\LoggerFactory;
use OpenVRE\RemoteSSH;
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


function getOpenstackUser()
{
	$vaultClient = VaultClientFactory::create();

	$credentials = $vaultClient->retrieveDatafromVault('Swift');
	if ($credentials) {
		$appId = $credentials['app_id'];
		$appSecret = $credentials['app_secret'];
		$projectName = $credentials['projectName'];
		$userDomainName = $credentials['domainName'];
		$projectDomainName = $credentials['projectId'];

		$swiftClient = new SwiftClient($appId, $appSecret, $projectName, $userDomainName, $projectDomainName, 'public', 'https://ncloud.bsc.es:5000/v3/');
		var_dump($swiftClient);
		$lista = $swiftClient->runList();
	}
}


function getSwiftClient()
{

	$vaultClient = VaultClientFactory::create();
	$credentials = $vaultClient->retrieveDatafromVault('Swift');

	if ($credentials) {
		$appId = $credentials['app_id'];
		$appSecret = $credentials['app_secret'];
		$projectName = $credentials['projectName'];
		$userDomainName = $credentials['domainName'];
		$projectDomainName = $credentials['projectId'];

		$swiftClient = new SwiftClient($appId, $appSecret, $projectName, $userDomainName, $projectDomainName, 'public', 'https://ncloud.bsc.es:5000/v3/');
		return $swiftClient;
	} else {
		return array('error' => 'Failed to retrieve Swift credentials from Vault');
	}
}



function getSSHClient($remote_dir, $siteId)
{

	$vaultClient = VaultClientFactory::create();
	$credentials = $vaultClient->retrieveDatafromVault('SSH');

	if ($credentials) {
		$sshPrivateKey = $credentials['private_key'];
		$sshPublicKey = $credentials['public_key'];
		$sshUsername = $credentials['username'];
		$sshId = $credentials['_id'];

		// Set up the credentials array for the RemoteSSH class
		$sshCredentials = [
			'private_key' => $sshPrivateKey,
			'public_key' => $sshPublicKey,
			'username' => $sshUsername
		];

		// Retrieve site info from the sites collection
		$siteDocument = $GLOBALS['sitesCol']->findOne(['_id' => $siteId]);
		// Assuming the site document exists, update the launcher section with SSH credentials
		if ($siteDocument) {
			$siteDocument['launcher']['access_credentials']['username'] = $sshUsername;
			$siteDocument['launcher']['access_credentials']['private_key'] = $sshPrivateKey;
			$siteDocument['launcher']['access_credentials']['public_key'] = $sshPublicKey;

			// Save the updated site document back to the collection
			$GLOBALS['sitesCol']->updateOne(['_id' => $siteId], ['$set' => $siteDocument]);
			// Initialize the SSH client with retrieved credentials and site details
			$remoteSSH = new RemoteSSH($sshCredentials, $remote_dir, 22, $siteDocument['launcher']['http_server']);
			return $remoteSSH;
		} else {
			return array('error' => 'Site document not found for site ID: ' . $siteId);
		}
	} else {
		return array('error' => 'Failed to retrieve SSH credentials from Vault');
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
	if (is_null($container) || is_null($swiftClient)) {
		return array('error' => 'Container or Swift client is null');
	}

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



function initiateFileDownload($swiftClient, $fileUrl, $container)
{
	// Set destination working directory/uploads
	$dataDirPath = getAttr_fromGSFileId($_SESSION['User']['dataDir'], "path");
	$wd = $dataDirPath . "/uploads";
	$wdP = $GLOBALS['dataDir'] . "/" . $wd;

	// Log paths for debugging
	getObjectStorageLogger()->debug("Data directory: $dataDirPath");
	getObjectStorageLogger()->debug("Working directory (wd): $wd");
	getObjectStorageLogger()->debug("Working directory path (wdP): $wdP");
	getObjectStorageLogger()->debug("File URL: $fileUrl");

	// Ensure the output directory exists
	if (!is_dir($wdP) && !mkdir($wdP, 0775, true)) {
		getObjectStorageLogger()->error("Failed to create working directory: $wdP.");
		throw new UnexpectedValueException("Failed to create working directory: $wdP");
	}

	$fileName = basename($fileUrl);
	$fullPath = $wdP . '/' . $fileName;
	$fileUrl = ltrim($fileUrl, '/');
	$downloadSuccess = $swiftClient->runDownloadFile($wdP . '/', $container, $fileUrl);
	getObjectStorageLogger()->debug("Download success: $downloadSuccess");

	if (!$downloadSuccess) {
		getObjectStorageLogger()->error("Failed to download file: $fileName");
		throw new UnexpectedValueException("Failed to download file: $fileName");
	}

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
	$fnId = uploadGSFileBNS("$wd/$fileName", $fullPath, $insertData, $metaData);
	getObjectStorageLogger()->info("File registered successfully with ID: $fnId");
	return $fnId;
}
