<?php

function getOpenstackUser($vaultUrl, $vaultToken, $accessToken, $vaultRolename, $username) {

        $vaultClient = new VaultClient($vaultUrl, $vaultToken, $accessToken, $vaultRolename, $username);
        $vaultKey = $_SESSION['User']['Vault']['vaultKey'];

        $credentials = $vaultClient->retrieveDatafromVault('Swift', $vaultKey, $vaultUrl, 'secret/mysecret/data/', $_SESSION['User']['_id'] . '_credentials.txt');
        if ($credentials) {
                //$openstackCredentials = json_decode($credentials, true);
                $appId = $credentials['app_id'];
                #echo "<br> . $appId . </br>";
                $appSecret = $credentials['app_secret'];
                $projectName = $credentials['projectName'];
                $userDomainName = $credentials['domainName'];
                $projectDomainName = $credentials['projectId'];

                $swiftClient = new SwiftClient($appId, $appSecret, $projectName, $userDomainName, $projectDomainName, 'public', 'https://ncloud.bsc.es:5000/v3/');
                var_dump($swiftClient);
                $lista=$swiftClient->runList();
                #var_dump($lista);
        }
}


function getSwiftClient($vaultUrl, $vaultToken, $accessToken, $vaultRolename, $username) {

	$vaultClient = new VaultClient($vaultUrl, $vaultToken, $accessToken, $vaultRolename, $username);
	$vaultKey = $_SESSION['User']['Vault']['vaultKey'];
	$credentials = $vaultClient->retrieveDatafromVault('Swift', $vaultKey, $vaultUrl, 'secret/mysecret/data/', $_SESSION['User']['_id'] . '_credentials.txt');
	error_log($vaultKey, $credentials);
	
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



function getSSHClient($vaultUrl, $vaultToken, $accessToken, $vaultRolename, $username, $remote_dir, $siteId) {

	$vaultClient = new VaultClient($vaultUrl, $vaultToken, $accessToken, $vaultRolename, $username);
	$vaultKey = $_SESSION['User']['Vault']['vaultKey'];
	$credentials = $vaultClient->retrieveDatafromVault('SSH', $vaultKey, $vaultUrl, 'secret/mysecret/data/', $_SESSION['User']['_id'] . '_credentials.txt');
	//        error_log($vaultKey, $credentials);
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



function getContainers($swiftClient) {
	#echo $vaultUrl;
	$lista=$swiftClient->runList();
	$lista = json_encode($lista);
	if (json_last_error() !== JSON_ERROR_NONE) {
		$error_message = json_last_error_msg();
		return array('error' => "JSON encoding failed: $error_message");

	}

	return $lista;
}



function getContainerFiles($container, $swiftClient) {

	if ($container !== null && $swiftClient !== null) {

		error_log("getContainerFiles - container: $container");
		$containerList=$swiftClient->runListContainer($container);

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



function initiateFileDownload($swiftClient, $fileUrl, $container) {
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
    if (!is_dir($wdP)) {
        if (!mkdir($wdP, 0775, true)) {
            error_log("Failed to create working directory: $wdP");
            return false;
        }
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







?>
