<?php

/*
 * linkedaccounts.inc.php
 * 
 */

//require_once "classes/User.php";

// Usage example:
// addUserLinkedAccount($_REQUEST['account'], $_REQUEST['action'], $_POST);

function addUserLinkedAccount($accountType, $action, $site_id, $postData) {
	switch ($accountType) {
        	case "euBI":
           		handleEuBIAccount($action, $postData);
            		break;
		case "SSH":
			if (isset($_POST["submitOption"])) {
                                $submitOption = $_POST["submitOption"];
                                if ($submitOption === "clearAccount") {
					// Handle clearing account
					echo $site_id;
					var_dump($postData);
					handleSSHAccount("delete", $site_id, $postData);
					break;
				} elseif ($submitOption === "updateAccount") {
					handleSSHAccount("update", $site_id, $postData);
					break;
				} else {
		            		handleSSHAccount($action, $site_id, $postData);
					break;
				}
			} else {
				handleSSHAccount($action, $site_id, $postData);
				break;
			}
		case "objectstorage":
			if (isset($_POST["submitOption"])) {
				$submitOption = $_POST["submitOption"];
				//var_dump($postData);
				if ($submitOption === "clearAccount") {
					// Handle clearing account
					handleObjectStorageAccount("delete", $postData);
					break;
				} elseif ($submitOption === "updateAccount") {
					// Handle updating account
					handleObjectStorageAccount("update", $postData);
					break;
				} else {
					handleObjectStorageAccount($action, $postData);
					break;
				}
			} else {
	    			handleObjectStorageAccount($action, $postData);	
				break;
			}
		case "molgenis":
            		handleMolgenisAccount($action, $postData);
            		break;
case "ega":
			if (isset($_POST["submitOption"])) {
				$submitOption = $_POST["submitOption"];
				//var_dump($postData);
				if ($submitOption === "clearAccount") {
					// Handle clearing account
					handleEGAAccount("delete", $postData);
					break;
				} elseif ($submitOption === "updateAccount") {
					// Handle updating account
					handleEGAAccount("update", $postData);
					break;
				} else {
					handleEGAAccount($action, $postData);
					break;
				}
			} else {
				handleEGAAccount($action, $postData);
				break;
			}
        	default:
            		$_SESSION['errorData']['Error'][] = "Account of type '$accountType' is not yet supported.";
            		redirect($_SERVER['HTTP_REFERER']);
    		}
}


function handleEuBIAccount($action, $postData) {
    switch ($action) {
        case "update":
        case "new":
            if (!isset($postData['alias_token']) || !isset($postData['secret'])) {
                handleInvalidData();
            }

            $r = addUserLinkedAccount_euBI($postData['alias_token'], $postData['secret']);
            handleResult($r);
            break;

        case "delete":
            $r = deleteUserLinkedAccount($_SESSION['User']['_id'], 'euBI');
            handleResult($r);
            break;

        default:
            handleInvalidAction();
    }
}

/*
function handleMNAccount($action, $postData) {
	if ($action === "new") {
		$data = [];
		
		// Check if the keys are already provided
        	if (isset($postData['priv_key'], $postData['pub_key'])) {
            	
		// If keys are provided, use them directly
            		$accessToken = json_decode($_SESSION['User']['JWT'], true)["access_token"];

            		$data['data']['SSH'] = [];
            		$data['data']['SSH']['private_key'] = $postData['priv_key'];
            		$data['data']['SSH']['public_key'] = $postData['pub_key'];
            		$data['data']['SSH']['username'] = $postData['username'];

            		$_SESSION['errorData']['Info'][] = "Credentials are already saved.";
        	
		} elseif (isset($postData['save_credential']) && $postData['save_credential'] == 'true') {
           
		// Check compulsory fields
		// If generate_keys is true, meaning we have to generate the keys
        //	if (isset($postData['generate_keys']) && $postData['generate_keys'] == 'true') {
			$keys = generate_RSA_keys($postData['username'],'mn1.bsc.es',$_REQUEST['account']);

			$accessToken = json_decode($_SESSION['User']['JWT'], true)["access_token"];
			$data['data']['SSH'] = [];
			$data['data']['SSH']['private_key'] = $keys['private_key'];
			$data['data']['SSH']['public_key'] = $keys['public_key'];
			$data['data']['SSH']['username'] = $postData['username'];

			$_SESSION['errorData']['Info'][] = "Here is the new pub key:" . $keys['public_key'] . "   Take care to save it somewhere!";



		// If save_credentials is true, means the credentials are provided
        	} elseif (isset($postData['save_credential']) && $postData['save_credential'] == 'true') {
		   
		// Add logic for handling MN account and uploading keys to Vault

	            	$accessToken = json_decode($_SESSION['User']['JWT'], true)["access_token"];
	
        	    	$data['data']['SSH'] = [];
	        	$data['data']['SSH']['private_key'] = $postData['priv_key'];
        		$data['data']['SSH']['public_key'] = $postData['pub_key'];
			$data['data']['SSH']['username'] = $postData['username'];
		}

//		echo $data;

            	$vaultClient = new VaultClient(
                	$GLOBALS['vaultUrl'],
                	$GLOBALS['vaultToken'],
                	$accessToken,
                	$GLOBALS['vaultRolename'],
                	$postData['username']
            	);

            	$key = $vaultClient->uploadKeystoVault_check($data);

            	// Update user data with vault key
            	$_SESSION['User']['linked_accounts']['Vault']['vaultKey'] = $key;
		updateUser($_SESSION['User']);

		//var_dump($key);

		//var_dump($r);

            	if (!$key) {
                	$_SESSION['errorData']['Error'][] = "Failed to link MN account";
                	$_SESSION['formData'] = $postData;
                	redirect($_SERVER['HTTP_REFERER']);
            	}

            	$_SESSION['errorData']['Info'][] = "MN account successfully linked.";
		//redirect($GLOBALS['BASEURL'] . "user/usrProfile.php#tab_1_4");
		redirect($_SERVER['HTTP_REFERER']);

    	} else {
        	handleInvalidAction();
    	};
}

 */


function handleSSHAccount($action, $site_id, $postData){
        //echo '<>pre<>';
	//VAR_DUMP($site_id);	
	$data = [];
	if ($action === "new") {
                // Check if the credentials are already saved
		//echo $action;
		//var_dump($postData);
		//echo $postData['save_credential'];
                if (isset($postData['private_key'], $postData['public_key'])) {
			// If credentials are provided, use them directly
			if (isset($_SESSION['User']['credentials']['timestamp'])) {
				$savedTime = $_SESSION['User']['credentials']['timestamp'];
				$currentTime = time();


				// Check if the timestamp is more than 2 hours old (validity check)
				if (($currentTime - $savedTime) > 7200) {
					$_SESSION['errorData']['Warning'][] = "Credentials were saved more than 2 hours ago. Please update them.";
					$accessToken = $_SESSION['User']['Token']['access_token'];
				} else {
					$_SESSION['errorData']['Info'][] = "Credentials are already saved and still valid.";
					$accessToken = $_SESSION['User']['Token']['access_token'];
					return; 
				}
			}
			
                } elseif (isset($postData["save_credential"]) && $postData["save_credential"] == "true") {


			$accessToken = $_SESSION['User']['Token']['access_token'];

                        $data['data']['SSH'] = [];
                        $data['data']['SSH']['private_key'] = $postData['private_key'];
			$data['data']['SSH']['public_key'] = $postData['public_key']; 
			$data['data']['SSH']['username'] = $postData['username'];
			$data['data']['SSH']['_id'] = $postData['_id'];

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
		#echo "DATA??";
		#echo var_dump($postData);
                if (!empty($postData['private_key']) && !empty($postData['public_key'])) {
			
			#echo var_dump($_SESSION['User']['Token']);
			#$accessToken = json_decode($_SESSION['User']['Token'], true)["access_token"];
			$accessToken = $_SESSION['User']['Token']['access_token'];
			#echo $accessToken;
			
			$data['data']['SSH'] = [];
		        $data['data']['SSH']['private_key'] = $postData['private_key'];
            		$data['data']['SSH']['public_key'] = $postData['public_key'];
			$data['data']['SSH']['username'] = $postData['username'];
			$data['data']['SSH']['_id'] = $postData['_id'];
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
			$postData['username'] = null;
		}
		$postData['timestamp'] = null;
		$postData['_id'] = null;

		#var_dump($postData);
		$_SESSION['errorData']['Info'][] = "Credentials for user erased, please provide new ones.";

		if (isset($site_id)) {  
		    	#echo 'aoooooooooooooooo';	
			$updateResult = $GLOBALS['sitesCol']->updateOne(
				['_id' => $site_id],  // Match document by siteId    
				['$set' => [  // Use the $unset operator to remove fields
					'launcher.access_credentials.private_key' => null,
					'launcher.access_credentials.public_key' => null,
					'launcher.access_credentials.username' => null
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

        //var_dump($data);
	$postData['username'] = $postData['username'] . '_' . $site_id;
        $vaultClient = new VaultClient(
                        $_SESSION['User']['Vault']['vaultUrl'],
                        $_SESSION['User']['Vault']['vaultToken'],
                        $accessToken,
                        $_SESSION['User']['Vault']['vaultRolename'],
                        $postData['username']
        );
	//var_dump($data);
        $key = $vaultClient->uploadKeystoVault($data);
	//echo ("key");
	//var_dump($key);
	$tokenTime = $vaultClient->getTokenExpirationTime($_SESSION['User']['Vault']['vaultUrl'], $key);
	//echo ("TOKEN TIME" . $tokenTime);
	if ($tokenTime !== false) {
		$_SESSION['User']['Vault']['expires_in'] = $tokenTime;
	}
	// Update user data with vault key
        $_SESSION['User']['Vault']['vaultKey'] = $key;
        updateUser($_SESSION['User']);
        if (!$key) {
                $_SESSION['errorData']['Error'][] = "Failed to link SSH account";
                $_SESSION['formData'] = $postData;
                redirect($_SERVER['HTTP_REFERER']);
        }

        $_SESSION['errorData']['Info'][] = "SSH account successfully linked.";
        redirect($_SERVER['HTTP_REFERER']);
}




function handleObjectStorageAccount($action, $postData){
	$data = [];
	//echo $action;
	//var_dump($postData);
	if ($action === "new") {
        	//$data = [];	
		// Check if the credentials are already saved
		
		if (isset($postData['app_id'], $postData['app_secret'])) {
	            // If credentials are provided, use them directly
			$accessToken = $_SESSION['User']['Token']['access_token'];
			$_SESSION['errorData']['Info'][] = "Credentials are already saved, update the credentials if needed.";
		
		} elseif (isset($postData['save_credential']) && $postData['save_credential'] == 'true') {

            	// Add logic for handling MN account and uploading credentials to Vault
			$accessToken = $_SESSION['User']['Token']['access_token'];
		// You can customize this part based on how you obtain Swift credentials
            		$data['data']['Swift'] = [];
	    		$data['data']['Swift']['app_id'] = $postData['app_id']; // Modify this
			$data['data']['Swift']['app_secret'] = $postData['app_secret'];
		        $data['data']['Swift']['projectName'] = $postData['projectName'];	// Modify this
			$data['data']['Swift']['projectId'] = $postData['projectId'];
			$data['data']['Swift']['domainName'] = $postData['domainName'];
			$data['data']['Swift']['projectDomainId'] = $postData['projectDomainId'];
			$data['data']['Swift']['_id'] = $postData['_id'];
		}

		//ECHO "	VIOLETA "	;
		//var_dump($postData);

	} elseif ($action === "update") {
        	// Add logic for handling MN account and uploading credentials to Vault for "update" action

		if (!empty($postData['app_id']) && !empty($postData['app_secret'])) {
			
			var_dump($_SESSION['User']);
			
			$accessToken = $_SESSION['User']['Token']['access_token'];
			#$accessToken = json_decode($Token, true);
			
			$data['data']['Swift'] = [];
        		$data['data']['Swift']['app_id'] = $postData['app_id']; // Modify this
			$data['data']['Swift']['app_secret'] = $postData['app_secret']; // Modify this
			$data['data']['Swift']['projectName'] = $postData['projectName'];     // Modify this
                        $data['data']['Swift']['projectId'] = $postData['projectId'];
                        $data['data']['Swift']['domainName'] = $postData['domainName'];
			$data['data']['Swift']['projectDomainId'] = $postData['projectDomainId'];
			$data['data']['Swift']['_id'] = $postData['_id'];
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
		$_SESSION['errorData']['Info'][] = "Credentials for user erased, please provide new ones.";
	} else {
        	handleInvalidAction();
    	}
		
	//var_dump($data);
	#echo 'Okay';
	#var_dump($accessToken);
	#echo 'Vabbuo';
	#var_dump($_SESSION['User']);
	$vaultClient = new VaultClient(
                	$_SESSION['User']['Vault']['vaultUrl'],
                	$_SESSION['User']['Vault']['vaultToken'],
                	$accessToken,
                	$_SESSION['User']['Vault']['vaultRolename'],
                	$postData['username']
	);
	#echo 'Vault';
	#var_dump($vaultClient);
	#var_dump($data);
	$key = $vaultClient->uploadKeystoVault($data);
	var_dump($key);
	// Update user data with vault key
        $_SESSION['User']['Vault']['vaultKey'] = $key;
      	updateUser($_SESSION['User']);
	if (!$key) {
		$_SESSION['errorData']['Error'][] = "Failed to link Swift account";
		$_SESSION['formData'] = $postData;
		redirect($_SERVER['HTTP_REFERER']);
	}

	$_SESSION['errorData']['Info'][] = "Swift account successfully linked.";
	redirect($_SERVER['HTTP_REFERER']);		
}

function handleEGAAccount($action, $postData) {
	$data = [];
	if ($action === "new") {
		$accessToken = json_decode($_SESSION['User']['JWT'], true)["access_token"];
		if (isset($postData['username'], $postData['password'])) {
			$_SESSION['errorData']['Info'][] = "Credentials are already saved, update the credentials if needed.";

		} elseif (isset($postData['save_credential']) && $postData['save_credential'] == 'true') {
			$data['data']['EGA'] = [];
			$data['data']['EGA']['username'] = $postData['username']; // Modify this
			$data['data']['EGA']['password'] = $postData['password']; // Modify this

			$data['data']['EGA']['_id'] = $postData['_id'];

		}

	} elseif ($action === "update") {
		$accessToken = json_decode($_SESSION['User']['JWT'], true)["access_token"];
		// Add logic for handling MN account and uploading credentials to Vault for "update" action


		if (!empty($postData['username']) && !empty($postData['password'])) {
			$data['data']['EGA'] = [];
			$data['data']['EGA']['username'] = $postData['username']; // Modify this
			$data['data']['EGA']['password'] = $postData['password']; // Modify this


			$data['data']['EGA']['_id'] = $postData['_id'];
			$_SESSION['errorData']['Info'][] = "Credentials updated!";
		} else {
			// Handle the case where app_id or app_secret is empty
			$_SESSION['errorData']['Error'][] = "Please provide both username and password.";
			$_SESSION['formData'] = $postData;
			redirect($_SERVER['HTTP_REFERER']);
		}

	} elseif ($action === "delete") {
		// Reset data for "delete" action
		$data = [];
		$_SESSION['errorData']['Info'][] = "Credentials for user erased, please provide new ones.";
	} else {
		handleInvalidAction();
	}

	//var_dump($_SESSION['User']['Vault']['vaultKey']);

	//var_dump($data);
	//var_dump($accessToken);

	
	$vaultClient = new VaultClient(
		$_SESSION['User']['Vault']['vaultUrl'],
		$_SESSION['User']['Vault']['vaultToken'],
		$accessToken,
		$_SESSION['User']['Vault']['vaultRolename'],
		$postData['username']
	);

	//var_dump($data);
	$key = $vaultClient->uploadKeystoVault($data);
	//var_dump($key);
	// Update user data with vault key
	$_SESSION['User']['Vault']['vaultKey'] = $key;
	updateUser($_SESSION['User']);
	if (!$key) {
		$_SESSION['errorData']['Info'][] = "";
		$_SESSION['errorData']['Error'][] = "Failed to link EGA account";
		$_SESSION['formData'] = $postData;
		redirect($_SERVER['HTTP_REFERER']);
	}

	$_SESSION['errorData']['Info'][] = "EGA account successfully linked.";
	redirect($_SERVER['HTTP_REFERER']);
}


function handleInvalidData() {
    $_SESSION['errorData']['Error'][] = "Not receiving expected fields. Please submit the data again.";
    $_SESSION['formData'] = $postData;
    redirect($_SERVER['HTTP_REFERER']);
}

function handleResult($result) {
    if (!$result) {
        $_SESSION['errorData']['Error'][] = "Failed to perform the requested action.";
        $_SESSION['formData'] = $postData;
        redirect($_SERVER['HTTP_REFERER']);
    }

    $_SESSION['errorData']['Info'][] = "Action successfully completed.";
    redirect($GLOBALS['BASEURL'] . "user/usrProfile.php#tab_1_4");
}

function handleInvalidAction() {
    $_SESSION['errorData']['Error'][] = "Invalid action for the specified account type.";
    redirect($_SERVER['HTTP_REFERER']);
}


function generate_RSA_keys($username, $server, $account) {
	$config = array(
		"digest_alg" => "sha512",
		"private_key_bits" => 2048,
		"private_key_type" => OPENSSL_KEYTYPE_RSA,
	);
	
	// Generate the key pair
	$res = openssl_pkey_new($config);

    	// Extract the private key
	openssl_pkey_export($res, $privateKey);
	$formattedPrivateKey = "-----BEGIN RSA PRIVATE KEY-----\n";
    	$formattedPrivateKey .= wordwrap($privateKey, 64, "\n", true);
    	$formattedPrivateKey .= "\n-----END RSA PRIVATE KEY-----";

    	// Extract the public key
	$publicKeyDetails = openssl_pkey_get_details($res);
	$publicKey = $publicKeyDetails['key'];
	$publicKey = str_replace(["-----BEGIN PUBLIC KEY-----", "-----END PUBLIC KEY-----", "\n", "\r"], '', $publicKey);
	$publicKey = "ssh-rsa " . trim($publicKey) . " " . $username . "@" . $server;

    	// Save or use the keys as needed
    	// For example, you might want to save them in your database
//	var_dump($privateKey);
//	var_dump($publicKey);

    	// Return the keys along with the account
    	return array(
        	'account' => $account,
        	'private_key' => $formattedPrivateKey,
        	'public_key' => $publicKey,
    	);
}


function generateSSHKeyPair($username) {
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


function registerEgaPubKey($pubKey, $username, $vaultClient, $vaultKey) {
	$egaTempKeysEndpoint = $GLOBALS['EGA_EPHEMERAL_KEYS_ENDPOINT'] . "/$username";
	$validFor = "1 hour";

	$data = json_encode([
		"pubkey" => $pubKey,
		"valid_for" => $validFor
        ]);

	// Initialize cURL session
	$ch = curl_init();

	// Set cURL options
	curl_setopt($ch, CURLOPT_URL, $egaTempKeysEndpoint);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, [
		'Content-Type: application/json'
	]);


	$vaultUrl = $_SESSION['User']['Vault']['vaultUrl'];
	$credentials = $vaultClient->retrieveDatafromVault('ega', $vaultKey, $vaultUrl, 'secret/mysecret/data/', $GLOBALS['bscEgaCredentialsFilename']);
	if (is_null($credentials)) {
		$_SESSION['errorData']['Error'][] = "Internal error. Failed to retrieve BSC-EGA credentials.";
		return false;
	}
	
	$egaKeyEndpointUser = $credentials['username'];
	$egaKeyEndpointPassword = $credentials['password'];
	curl_setopt($ch, CURLOPT_USERPWD, "$egaKeyEndpointUser:$egaKeyEndpointPassword");
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	
	$response = curl_exec($ch);

	if (curl_errno($ch)) {
		$error_msg = curl_error($ch);
	}

	curl_close($ch);

	if (isset($error_msg)) {
		$_SESSION['errorData']['Error'][] = "Error: $error_msg";
		return false;
	}

	$responseData = json_decode($response, true);

	if (isset($responseData['result']) && $responseData['result'] === 'ok') {
		return true;
    } else {
		$_SESSION['errorData']['Error'][] = "Request failed. Response: " . $response;
		return false;
        }
}

function generateSSHButtons() {
    // Check if $GLOBALS['sitesCol'] is set
    if (isset($GLOBALS['sitesCol'])) {
        // Debugging: Add a log statement to confirm the function is called
        $_SESSION['errorData']['Debug'][] = "generateSSHButtons() function called.";

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
	    // Debugging: Log the site ID and name being processed
            $_SESSION['errorData']['Debug'][] = "Processing site: $siteId - $siteName";

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


function getSiteDetails($siteId) {
    // Check if the siteId is valid
    if (isset($siteId)) {
        // Fetch site details from the database
        $site = $GLOBALS['sitesCol']->findOne(['_id' =>$siteId]);

        // If the site is found, return the necessary details
        if ($site) {
            $siteName = htmlspecialchars($site['name']);
            $siteAcronym = isset($site['hpc_acronym']) ? htmlspecialchars($site['hpc_acronym']) : 'N/A';
            return ['siteName' => $siteName, 'siteAcronym' => $siteAcronym];
        }
    }
    // Return default values if site not found
    return ['siteName' => 'Unknown Site', 'siteAcronym' => 'N/A'];
}


function getSiteCredentials($siteId) {
    if (isset($siteId)) {
        // Fetch site details from the database
        $site = $GLOBALS['sitesCol']->findOne(['_id' =>$siteId]);

        if ($site) {
            // Return the credentials if they exist, else return default empty values
            $credentials = isset($site['launcher']['access_credentials']) ? $site['launcher']['access_credentials'] : null;
            return $credentials;
        }
    }
    return null;
}


?>
