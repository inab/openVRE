<?php

function addUserLinkedAccount($accountType, $action, $userId, $site_id, $postData)
{
	switch ($accountType) {
		case "SSH":
			if (isset($_POST["submitOption"])) {
				$submitOption = $_POST["submitOption"];
				if ($submitOption === "clearAccount") {
					handleSSHAccount("delete", $userId, $site_id, $postData);
					break;
				} elseif ($submitOption === "updateAccount") {
					handleSSHAccount("update", $userId, $site_id, $postData);
					break;
				} else {
					handleSSHAccount($action, $userId, $site_id, $postData);
					break;
				}
			} else {
				handleSSHAccount($action, $userId, $site_id, $postData);
				break;
			}
		case "objectstorage":
			if (isset($_POST["submitOption"])) {
				$submitOption = $_POST["submitOption"];
				if ($submitOption === "clearAccount") {
					handleObjectStorageAccount("delete", $userId, $postData);
					break;
				} elseif ($submitOption === "updateAccount") {
					handleObjectStorageAccount("update", $userId, $postData);
					break;
				} else {
					handleObjectStorageAccount($action, $userId, $postData);
					break;
				}
			} else {
				handleObjectStorageAccount($action, $userId, $postData);
				break;
			}
		case "ega":
			if (isset($_POST["submitOption"])) {
				$submitOption = $_POST["submitOption"];
				if ($submitOption === "clearAccount") {
					handleEgaAccount("delete", $userId, $postData);
					break;
				} elseif ($submitOption === "updateAccount") {
					handleEgaAccount("update", $userId, $postData);
					break;
				} else {
					handleEgaAccount($action, $userId, $postData);
					break;
				}
			} else {
				handleEgaAccount($action, $userId, $postData);
				break;
			}
		default:
			$_SESSION['errorData']['Error'][] = "Account of type '$accountType' is not yet supported.";
			redirect($_SERVER['HTTP_REFERER']);
	}
}


function handleSSHAccount($action, $userId, $site_id, $postData)
{
	$data = [];
	if ($action === "new") {
		// Check if the credentials are already saved

		if (isset($postData['private_key'], $postData['public_key'])) {
			// If credentials are provided, use them directly
			if (isset($_SESSION['User']['credentials']['timestamp'])) {
				$savedTime = $_SESSION['User']['credentials']['timestamp'];
				$currentTime = time();


				// Check if the timestamp is more than 2 hours old (validity check)
				if (($currentTime - $savedTime) > 7200) {
					$_SESSION['errorData']['Warning'][] = "Credentials were saved more than 2 hours ago. Please update them.";
					$accessToken = $_SESSION['userToken']['access_token'];
				} else {
					$_SESSION['errorData']['Info'][] = "Credentials are already saved and still valid.";
					$accessToken = $_SESSION['userToken']['access_token'];
					return;
				}
			}
		} elseif (isset($postData["save_credential"]) && $postData["save_credential"] == "true") {

			$accessToken = $_SESSION['userToken']['access_token'];
			$data['data']['SSH'] = [];
			$data['data']['SSH']['private_key'] = $postData['private_key'];
			$data['data']['SSH']['public_key'] = $postData['public_key'];
			$data['data']['SSH']['user_key'] = $postData['user_key'];
			$data['data']['SSH']['_id'] = $userId;
			$_SESSION['User']['credentials'] = [
				'timestamp' => time()  // Only store the timestamp
			];



			$_SESSION['errorData']['Info'][] = "Credentials in the system, saving to Vault...";
		} else {
			// Handle the case where app_id or app_secret is empty
			$_SESSION['errorData']['Error'][] = "Please provide both keys.";
			$_SESSION['formData'] = $postData;
			redirect($_SERVER['HTTP_REFERER']);
		}
	} elseif ($action === "update") {
		// Add logic for handling MN account and uploading credentials to Vault for "update" action

		if (!empty($postData['private_key']) && !empty($postData['public_key'])) {
			$accessToken = $_SESSION['userToken']['access_token'];

			$data['data']['SSH'] = [];
			$data['data']['SSH']['private_key'] = $postData['private_key'];
			$data['data']['SSH']['public_key'] = $postData['public_key'];
			$data['data']['SSH']['user_key'] = $postData['user_key'];
			$data['data']['SSH']['_id'] = $userId;
			$_SESSION['User']['credentials'] = [
				'timestamp' => time()  // Only store the timestamp
			];

			$_SESSION['errorData']['Info'][] = "Credentials in the system, saving to Vault...";
		} else {
			// Handle the case where app_id or app_secret is empty
			$_SESSION['errorData']['Error'][] = "Please provide both keys.";
			$_SESSION['formData'] = $postData;
			redirect($_SERVER['HTTP_REFERER']);
		}
	} elseif ($action === "delete") {
		// Reset data for "delete" action
		$data = [];
		if (isset($postData['private_key'], $postData['public_key'])) {
			$postData['private_key'] = null;
			$postData['public_key'] = null;
			$postData['user_key'] = null;
		}
		$postData['timestamp'] = null;
		$userId = null;

		#var_dump($postData);
		$_SESSION['errorData']['Info'][] = "Credentials for user erased, please provide new ones.";

		if (isset($site_id)) {
			#echo 'aoooooooooooooooo';	
			$updateResult = $GLOBALS['sitesCol']->updateOne(
				['_id' => $site_id],  // Match document by siteId    
				['$set' => [  // Use the $unset operator to remove fields
					'launcher.access_credentials.private_key' => null,
					'launcher.access_credentials.public_key' => null,
					'launcher.access_credentials.user_key' => null
				]]
			);

			// Check if the update was successful
			if ($updateResult->getModifiedCount() > 0) {
				$_SESSION['errorData']['Info'][] = "Credentials removed from the database.";
			} else {
				$_SESSION['errorData']['Error'][] = "Failed to remove credentials from the database.";
			}
		}
		redirect($_SERVER['HTTP_REFERER']);
	} else {
		handleInvalidAction();
	}

	if (empty($postData['user_key'])) {
		error_log("Error: 'username' is missing in postData.");
		throw new Exception("Username is required.");
		exit;
	}

	$postData['user_key'] = $postData['user_key'] . '_' . $site_id;
	$vaultClient = new VaultClient(
		$GLOBALS['vaultUrl'],
		$_SESSION['userVaultInfo']['vaultToken'],
		$accessToken,
		$_SESSION['userVaultInfo']['vaultRolename'],
		$postData['username']
	);
	//var_dump($data);
	$key = $vaultClient->uploadKeystoVault($data);
	//echo ("key");
	//var_dump($key);
	$tokenTime = $vaultClient->getTokenExpirationTime($GLOBALS['vaultUrl'], $key);
	//echo ("TOKEN TIME" . $tokenTime);
	if ($tokenTime !== false) {
		$_SESSION['userVaultInfo']['expires_in'] = $tokenTime;
	}
	// Update user data with vault key
	$_SESSION['userVaultInfo']['vaultKey'] = $key;
	updateUser($_SESSION['User']);
	if (!$key) {
		$_SESSION['errorData']['Error'][] = "Failed to link SSH account";
		$_SESSION['formData'] = $postData;
		redirect($_SERVER['HTTP_REFERER']);
	}

	$_SESSION['errorData']['Info'][] = "SSH account successfully linked.";
	redirect($_SERVER['HTTP_REFERER']);
}




function handleObjectStorageAccount($action, $userId, $postData)
{
	$data = [];
	echo $action;
	var_dump($postData);
	if ($action === "new") {
		//$data = [];	
		// Check if the credentials are already saved

		if (isset($postData['app_id'], $postData['app_secret'])) {
			// If credentials are provided, use them directly
			$accessToken = $_SESSION['userToken']['access_token'];
			$_SESSION['errorData']['Info'][] = "Credentials are already saved, update the credentials if needed.";
		} elseif (isset($postData['save_credential']) && $postData['save_credential'] == 'true') {

			// Add logic for handling MN account and uploading credentials to Vault
			$accessToken = $_SESSION['userToken']['access_token'];
			// You can customize this part based on how you obtain Swift credentials
			$data['data']['Swift'] = [];
			$data['data']['Swift']['app_id'] = $postData['app_id']; // Modify this
			$data['data']['Swift']['app_secret'] = $postData['app_secret'];
			$data['data']['Swift']['projectName'] = $postData['projectName'];	// Modify this
			$data['data']['Swift']['projectId'] = $postData['projectId'];
			$data['data']['Swift']['domainName'] = $postData['domainName'];
			$data['data']['Swift']['projectDomainId'] = $postData['projectDomainId'];
			$data['data']['Swift']['user_key'] = $postData['user_key'];
			$data['data']['Swift']['_id'] = $userId;
		}
	} elseif ($action === "update") {
		// Add logic for handling MN account and uploading credentials to Vault for "update" action

		if (!empty($postData['app_id']) && !empty($postData['app_secret'])) {

			$accessToken = $_SESSION['userToken']['access_token'];

			$data['data']['Swift'] = [];
			$data['data']['Swift']['app_id'] = $postData['app_id']; // Modify this
			$data['data']['Swift']['app_secret'] = $postData['app_secret']; // Modify this
			$data['data']['Swift']['projectName'] = $postData['projectName'];     // Modify this
			$data['data']['Swift']['projectId'] = $postData['projectId'];
			$data['data']['Swift']['domainName'] = $postData['domainName'];
			$data['data']['Swift']['projectDomainId'] = $postData['projectDomainId'];
			$data['data']['Swift']['user_key'] = $postData['user_key'];
			$data['data']['Swift']['_id'] = $userId;
			#$_SESSION['errorData']['Info'][] = "Credentials updated!";
		} else {
			// Handle the case where app_id or app_secret is empty
			$_SESSION['errorData']['Error'][] = "Please provide both app_id and app_secret.";
			$_SESSION['formData'] = $postData;
			redirect($_SERVER['HTTP_REFERER']);
		}
	} elseif ($action === "delete") {
		// Reset data for "delete" action
		$data = [];
		if (isset($postData['app_id'], $postData['app_secret'])) {
			$postData['app_id'] = null;
			$postData['app_secret'] = null;
			$postData['projectName'] = null;
			$postData['projectId'] = null;
			$postData['domainName'] = null;
			$postData['projectDomainId'] = null;
			$postData['user_key'] = null;
			$postData['_id'] = null;
		}
		$postData['timestamp'] = null;
		$userId = null;

		#var_dump($postData);
		$_SESSION['errorData']['Info'][] = "Credentials for user erased, please provide new ones.";

		if (isset($site_id)) {
			$updateResult = $GLOBALS['sitesCol']->updateOne(
				['_id' => $site_id],  // Match document by siteId    
				['$set' => [  // Use the $unset operator to remove fields
					'access_credentials.app_id' => null,
					'access_credentials.app_secret' => null,
					'access_credentials.user_key' => null,
					'access_credentials.projectName' => null,
					'access_credentials.domainName' => null,
					'access_credentials.projectDomainId' => null,
					'access_credentials.projectId' => null,
				]]
			);

			// Check if the update was successful
			if ($updateResult->getModifiedCount() > 0) {
				$_SESSION['errorData']['Info'][] = "Credentials removed from the database.";
			} else {
				$_SESSION['errorData']['Error'][] = "Failed to remove credentials from the database.";
			}
		}
		redirect($_SERVER['HTTP_REFERER']);


		$_SESSION['errorData']['Info'][] = "Credentials for user erased, please provide new ones.";
	} else {
		handleInvalidAction();
	}

	if (empty($postData['user_key'])) {
		error_log("Error: 'username' is missing in postData.");
		throw new Exception("Username is required.");
		exit;
	}
	$postData['user_key'] = $postData['user_key'] . '_' . $site_id;

	$vaultClient = new VaultClient(
		$GLOBALS['vaultUrl'],
		$_SESSION['userVaultInfo']['vaultToken'],
		$accessToken,
		$_SESSION['userVaultInfo']['vaultRolename'],
		$postData['user_key']
	);
	#echo 'Vault';
	#var_dump($vaultClient);
	#var_dump($data);
	$key = $vaultClient->uploadKeystoVault($data);
	// Update user data with vault key
	$_SESSION['userVaultInfo']['vaultKey'] = $key;
	updateUser($_SESSION['User']);
	if (!$key) {
		$_SESSION['errorData']['Error'][] = "Failed to link Swift account";
		$_SESSION['formData'] = $postData;
		redirect($_SERVER['HTTP_REFERER']);
	}

	$_SESSION['errorData']['Info'][] = "Swift account successfully linked.";
	redirect($_SERVER['HTTP_REFERER']);
}

function handleEgaAccount($action, $userId, $postData)
{
	if ($action === "update") {
		try {
			$egaAuthToken = getEgaAuthToken($postData['username'], $postData['password']);
			$_SESSION['User']['EGA']['token'] = $egaAuthToken;
		} catch (Exception $e) {
			error_log("Could not connect to EGA: " . $e->getMessage());
			$_SESSION['errorData']['Error'][] = "Could not connect to EGA. Please check your credentials.";
			redirect($_SERVER['HTTP_REFERER']);
		}
	}

	$data = [];
	if ($action === "update") {
		if (empty($postData['username'])  || empty($postData['password'])) {
			$_SESSION['errorData']['Error'][] = "Please provide username and password.";
			redirect($_SERVER['HTTP_REFERER']);
		} else {
			$data['data']['EGA'] = [];
			$data['data']['EGA']['username'] = $postData['username'];
			$data['data']['EGA']['password'] = $postData['password'];
			$data['data']['EGA']['privateKey'] = $postData['privateKey'];
			$data['data']['EGA']['_id'] = $userId;
		}
	} elseif ($action === "delete") {
		clearLinkedAccount("EGA");
		redirect($_SERVER['HTTP_REFERER']);
	} else {
		handleInvalidAction();
	}

	$accessToken = json_decode($_SESSION['User']['JWT'], true)["access_token"];
	$vaultClient = new VaultClient(
		$GLOBALS['vaultUrl'],
		$_SESSION['userVaultInfo']['vaultToken'],
		$accessToken,
		$_SESSION['userVaultInfo']['vaultRolename'],
		$postData['username']
	);

	$key = $vaultClient->uploadKeystoVault($data);
	$_SESSION['userVaultInfo']['vaultKey'] = $key;
	if (!$key) {
		$_SESSION['errorData']['Info'][] = "";
		$_SESSION['errorData']['Error'][] = "Failed to link EGA account";
		error_log("Failed to link EGA account");
		error_log("Vault key: " . $key);
		redirect($_SERVER['HTTP_REFERER']);
	}

	$_SESSION['errorData']['Info'][] = "EGA account successfully linked.";
	redirect($_SERVER['HTTP_REFERER']);
}


function clearLinkedAccount($account)
{
	// TODO: To be implemented
	$_SESSION['errorData']['Info'][] = "Credentials for user erased, please provide new ones.";
}


function handleInvalidAction()
{
	$_SESSION['errorData']['Error'][] = "Action not recognized.";
	redirect($_SERVER['HTTP_REFERER']);
}


function generateSSHKeyPair($username)
{
	// Temporary directory to store key files
	$tempDir = sys_get_temp_dir();
	$privateKeyPath = $tempDir . '/id_ed25519_' . $username;
	$publicKeyPath = $tempDir . '/id_ed25519_' . $username . '.pub';

	// Generate the key pair using ssh-keygen and store it temporarily
	shell_exec("ssh-keygen -t ed25519 -f " . escapeshellarg($privateKeyPath) . " -N ''");

	$privateKey = file_get_contents($privateKeyPath);
	$publicKey = file_get_contents($publicKeyPath);

	// Clean up the temporary key files
	unlink($privateKeyPath);
	unlink($publicKeyPath);

	return [
		'privateKey' => base64_encode($privateKey),
		'publicKey' => $publicKey
	];
}


function generateSSHButtons()
{
	// Check if $GLOBALS['sitesCol'] is set
	if (isset($GLOBALS['sitesCol'])) {
		// Fetch the documents that have "SSH" in the "accessible_via" array
		$documents = $GLOBALS['sitesCol']->find([
			'launcher.accessible_via' => 'SSH'  // Filter condition
		]);

		// Initialize HTML output and results flag
		$buttonsHTML = '';
		$hayResults = false;

		// Iterate through the documents
		foreach ($documents as $document) {
			$hayResults = true;

			// Prepare the data to fill up the buttons
			$siteName = htmlspecialchars($document['name']);
			$siteId = (string) $document['_id'];
			//$siteSigla = isset($document['sigla']) ? htmlspecialchars($document['_id']) : 'N/A'; 
			$siteSigla = (string) $document['sigla'];

			// Create the button for each site
			$buttonsHTML .= '
                <div class="row" style="margin-left:0px;margin-bottom:5px">
                    <div class="col-md-6">
                        <a href="' . $GLOBALS['BASEURL'] . 'user/linkedAccount.php?account=SSH&action=new&site_id=' . $siteId . '" class="btn green" data-site-id="' . $siteId . '">
                            <i class="fa fa-plus"></i> &nbsp; Link your account (' . $siteSigla . ')
                        </a>
                    </div>
                </div>';
		}

		// Check if no documents were found
		if (!$hayResults) {
			$_SESSION['errorData']['Error'][] = "No SSH-accessible sites found.";
			$buttonsHTML = '<p>No HPC accounts found with SSH access.</p>';
		}

		return $buttonsHTML;
	} else {
		// Log if $GLOBALS['sitesCol'] is not set
		$_SESSION['errorData']['Error'][] = "Sites collection is not available.";
		return '<p>Sites collection is not available.</p>';
	}
}
