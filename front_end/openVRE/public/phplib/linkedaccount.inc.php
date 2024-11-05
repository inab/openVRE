<?php

/*
 * linkedaccounts.inc.php
 * 
 */

//require_once "classes/User.php";

// Usage example:
// addUserLinkedAccount($_REQUEST['account'], $_REQUEST['action'], $_POST);

function addUserLinkedAccount($accountType, $action, $postData) {
	//echo $accountType;
	//echo $action;
	//echo  $postData;
	switch ($accountType) {
        	case "euBI":
           		handleEuBIAccount($action, $postData);
            		break;
		case "SSH":
			if (isset($_POST["submitOption"])) {
                                $submitOption = $_POST["submitOption"];
                                if ($submitOption === "clearAccount") {
                                        // Handle clearing account
					handleSSHAccount("delete",$postData);
					break;
				} elseif ($submitOption === "updateAccount") {
					handleSSHAccount("update", $postData);
					break;
				} else {
		            		handleSSHAccount($action, $postData);
					break;
				}
			} else {
				handleSSHAccount($action, $postData);
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


function handleSSHAccount($action, $postData){
	
	$data = [];
	if ($action === "new") {
                // Check if the credentials are already saved
		//echo $action;
		//var_dump($postData);
		//echo $postData['save_credential'];
                if (isset($postData['priv_key'], $postData['pub_key'])) {
                    // If credentials are provided, use them directly
                        $accessToken = json_decode($_SESSION['User']['Token'], true)["access_token"];
                        $_SESSION['errorData']['Info'][] = "Credentials are already saved, update the credentials if needed.";

                } elseif (isset($postData["save_credential"]) && $postData["save_credential"] == "true") {
			
			$accessToken = json_decode($_SESSION['User']['Token'], true)["access_token"];

                // You can customize this part based on how you obtain Swift credentials
                        $data['data']['SSH'] = [];
                        $data['data']['SSH']['private_key'] = $postData['priv_key'];
			$data['data']['SSH']['public_key'] = $postData['pub_key']; 
			$data['data']['SSH']['username'] = $postData['hpc_username'];
			$data['data']['SSH']['_id'] = $postData['_id'];
			
			$_SESSION['errorData']['Info'][] = "Credentials saved!";
                }

        } elseif ($action === "update") {
                // Add logic for handling MN account and uploading credentials to Vault for "update" action
		//echo $data;
                if (!empty($postData['priv_key']) && !empty($postData['pub_key'])) {

                        $accessToken = json_decode($_SESSION['User']['Token'], true)["access_token"];
                        $data['data']['SSH'] = [];
		        $data['data']['SSH']['private_key'] = $postData['priv_key'];
            		$data['data']['SSH']['public_key'] = $postData['pub_key'];
			$data['data']['SSH']['username'] = $postData['hpc_username'];
			$data['data']['SSH']['_id'] = $postData['_id'];


			$_SESSION['errorData']['Info'][] = "Credentials updated!";
                } else {
                        // Handle the case where app_id or app_secret is empty
                        $_SESSION['errorData']['Error'][] = "Please provide both keys.";
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

        var_dump($data);
        var_dump($accessToken);
        $vaultClient = new VaultClient(
                        $_SESSION['User']['Vault']['vaultUrl'],
                        $_SESSION['User']['Vault']['vaultToken'],
                        $accessToken,
                        $_SESSION['User']['Vault']['vaultRolename'],
                        $postData['hpc_username']
        );
        var_dump($data);
        $key = $vaultClient->uploadKeystoVault($data);
	echo ("key");
	var_dump($key);
	$tokenTime = $vaultClient->getTokenExpirationTime($_SESSION['User']['Vault']['vaultUrl'], $key);
	echo ("TOKEN TIME" . $tokenTime);
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
			$accessToken = json_decode($_SESSION['User']['Token'], true)["access_token"];
            		$_SESSION['errorData']['Info'][] = "Credentials are already saved, update the credentials if needed.";
		
		} elseif (isset($postData['save_credential']) && $postData['save_credential'] == 'true') {

            	// Add logic for handling MN account and uploading credentials to Vault
			$accessToken = json_decode($_SESSION['User']['Token'], true)["access_token"];
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
	$publicKey =  $publicKeyDetails['key'];
	$publicKey = str_replace(["-----BEGIN PUBLIC KEY-----", "-----END PUBLIC KEY-----", "\n", "\r"], '', $publicKey);
    	$publicKey = "ssh-rsa " . trim($publicKey) . " " . $username. "@". $server;

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




?>
