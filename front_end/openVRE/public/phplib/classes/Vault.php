<?php

namespace OpenVRE\SSH;
use GuzzleHttp\Client;

class VaultClient
{

	private $vaultUrl;
	private $httpClient;
	private $roleName;
	private $jwtToken;
	private $username;



	public function __construct($vaultUrl, $jwtToken, $roleName, $username)
	{

		$this->vaultUrl = $vaultUrl;
		$this->jwtToken = $jwtToken;
		$this->roleName = $roleName;
		$this->username = $username;
		$this->httpClient = new Client();
	}


	public function checkToken($vaultUrl, $jwtToken, $roleName)
	{
		$headers = array("Content-Type: application/json",);
		$url = $vaultUrl . "/auth/jwt/login";

		$data = [
			'role' => $roleName,
			'jwt' => $jwtToken,
			'ttl' => '15m',
			'renewable' => true,
		];

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$response = curl_exec($ch);
		if ($response === false) {
			$error = curl_error($ch);
			curl_close($ch);
			throw new Exception("Failed to send the JWT login request: $error");
		}

		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		return array(
			'statusCode' => $httpCode,
			'response' => $response
		);
	}


	public function pre_sendJwtLoginRequest($url, $role, $jwtToken)
	{
		$data = array(
			'role' => $role,
			'jwt' => $jwtToken
		);

		$jsonData = json_encode($data);

		$options = array(
			'http' => array(
				'header' => "Content-Type: application/json\r\n",
				'method' => 'POST',
				'content' => $jsonData
			)
		);

		$context = stream_context_create($options);

		//echo "JSON  \n";
		//var_dump($context);
		//echo "END \n";
		$url1 = $this->$url . "/auth/jwt/login";
		//echo "url" . $url1;
		$response = file_get_contents($url1, false, $context);

		if ($response === false) {
			throw new Exception("Failed to send the JWT login request: " . error_get_last()['message']);
		}

		return $response;
	}

	public function isValidSSHPublicKey($key)
	{
		// Define a regular expression pattern for SSH public keys
		$pattern1 = '/^ssh-(rsa|ed25519|ecdsa-[a-z0-9-]+) [A-Za-z0-9+\/=]+ ?(?:\S+)?$/';
		$pattern2 = '/^-----BEGIN PUBLIC KEY-----[A-Za-z0-9+\/=\s]+-----END PUBLIC KEY-----/';



		// Check if the key matches the pattern
		return preg_match($pattern1, $key) === 1 || preg_match($pattern2, $key) === 1;
	}


	public function isValidSSHPrivateKey($key)
	{


		$key = $this->formatKey($key);

		// Check for the PKCS#1 header and footer

		$header = '-----BEGIN OPENSSH PRIVATE KEY-----';
		$footer = '-----END OPENSSH PRIVATE KEY-----';


		if (strpos($key, $header) !== 0 || strpos($key, $footer) === false) {
			echo "Missing or incorrect header.\n";
			return false;
		}

		// Remove the header and footer for further validation

		$keyBody = str_replace([$header, $footer], '', $key);
		$keyBody = trim($keyBody);

        // Check if the body is base64 encoded
	    if (!$this->isBase64($keyBody)) {
		    echo "Key body is not valid base64.\n";
	//	    return false;
	    }

        // Decode the base64 key body
	    $decodedKey = base64_decode($keyBody, true); 
        // Ensure the decoded key is in valid DER format
	    if (!$this->isValidDERFormat($decodedKey)) {
		    echo "Key is not in valid DER format.\n";
		    return false;
	    }
	    
	    echo "Key is valid.\n";
	    return true;    
    
    }

	private function validateOpenSSHPrivateKey($key)
	{
		// Check for OpenSSH Private Key headers

		if (
			strpos($key, '-----BEGIN OPENSSH PRIVATE KEY-----') === false ||
			strpos($key, '-----END OPENSSH PRIVATE KEY-----') === false
		) {
			echo "Invalid OpenSSH private key headers.\n";
			return false;
		}

		$keyBody = str_replace(
			["-----BEGIN OPENSSH PRIVATE KEY-----", "-----END OPENSSH PRIVATE KEY-----", "\r", "\n"],
			"",
			$key
		);

		// Decode the Base64 body

		$decodedKey = base64_decode($keyBody, true);

		if ($decodedKey === false) {
			echo "Base64 decoding failed. The key body might be corrupted.\n";
			return false;
		}

		// Check if the decoded key starts with the OpenSSH magic header
		if (substr($decodedKey, 0, 15) !== "openssh-key-v1\0") {
			echo "Invalid OpenSSH key format.\n";
			return false;
		}
		echo "The key is a valid OpenSSH private key.\n";
		return true;
	}

	// Helper method to format the key
	private function formatKey($key)
	{

		$key = trim($key);
		$lines = explode("\n", $key);
		$formattedLines = [];

		foreach ($lines as $line) {
			$line = trim($line);
			if (!empty($line)) {
				$formattedLines[] = $line;
			}
		}
		return implode("\n", $formattedLines);
	}

	// Helper method to check if a string is base64 encoded
	private function isBase64($str)
	{
		// Check if the string matches base64 encoding
		return base64_encode(base64_decode($str, true)) === $str;
	}

    // Helper method to check if a DER encoded key is valid
    private function isValidDERFormat($decodedKey) {
        // Perform basic DER format validation
        // PKCS#1 DER format starts with 0x30 (SEQUENCE)

		return substr($decodedKey, 0, 1) === "\x30";
		// More advanced checks can be added here
		//return true;

	}

	function uploadFileToVault($url, $secretPath, $userSecretsId, $secretName, $token, $data)
	{
		$vaultUrl = $url . "/" . $secretPath . $userSecretsId . '/' . $secretName;
		$headers = [
			'X-Vault-Token: ' . $token,
			'Content-Type: application/json'
		];

		$curl = curl_init($vaultUrl);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		$response = curl_exec($curl);

		$response = curl_exec($curl);
		if (curl_errno($curl)) {
			throw new Exception("Failed to send the JWT login request:" . curl_error($curl));
		}

		curl_close($curl);

		return $response;
	}


	function listSecretsInVault($token, $url, $secretPath, $userName)
	{
		$headers = [
			'X-Vault-Token: ' . $token
		];

		$vaultUrl = $url . $secretPath . $userName;
		
		$curl = curl_init($vaultUrl);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);  // Ignore SSL certificate verification
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);  // Ignore host verification
		$response = curl_exec($curl);

		if (curl_errno($curl)) {
			echo "Error occurred: " . curl_error($curl) . "\n";
		} else {

			echo "Secrets in Vault:\n";
			echo $response . "\n";
		}
		curl_close($curl);
	}





	// Function to retrieve token lookup response from Vault
	public function retrieveTokenLookup($vaultUrl, $vaultToken)
	{

		$url = $vaultUrl . 'auth/token/lookup-self';
		$headers = ['X-Vault-Token: ' . $vaultToken];
		$data = json_encode(['token' => 'ClientToken']);

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);  // Ignore SSL certificate verification
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);  // Ignore host verification

		$response = curl_exec($curl);
		if (curl_errno($curl)) {
			error_log('Error: ' . curl_error($curl));
			return null;
		}

		curl_close($curl);

		$tokenLookup = json_decode($response, true);
		error_log('Token Lookup Response: ' . json_encode($tokenLookup, JSON_PRETTY_PRINT));

		return $tokenLookup;
	}


	//Function using the loookup to see if the token has expired and needs a refresh
	public function isTokenExpired($vaultUrl, $vaultToken)
	{
		date_default_timezone_set('UTC');

		$tokenLookup = $this->retrieveTokenLookup($vaultUrl, $vaultToken);
	
		if ($tokenLookup && isset($tokenLookup['data']['expire_time'])) {
			return true;
		}
		return false;
	}


	public function getTokenExpirationTime($vaultUrl, $vaultToken)
	{

		date_default_timezone_set('UTC');
		$tokenLookup = $this->retrieveTokenLookup($vaultUrl, $vaultToken);
		if ($tokenLookup && isset($tokenLookup['data']['expire_time'])) {
			$ttl = $tokenLookup['data']['ttl'];
			$currentTimestamp = time();
			$expireTimestamp = $currentTimestamp + $ttl;
			// Return the expiration time in a human-readable format
			return ($expireTimestamp);
		}
		// Return false if token lookup or expire_time is not available
		return false;
	}



	public function uploadKeystoVault($data)
	{
		if (isset($data['data']['SSH'])) {
			$publicKey = $data['data']['SSH']['public_key'];
			$privateKey = $data['data']['SSH']['private_key'];
			// Validate the public key
			if (!$this->isValidSSHPublicKey($publicKey)) {
				echo "Invalid SSH public key format.";
			}
			// Validate the private key
			//if (!$this->isValidSSHPrivateKey($privateKey)) {
			if (!$this->validateOpenSSHPrivateKey($privateKey)) {
				echo "Invalid SSH private key format.";
			}

			if ($this->isValidSSHPublicKey($publicKey) && $this->validateOpenSSHPrivateKey($privateKey)) {
				echo "SSH keys are set and have the correct format.";

				try {

					// First access the Vault with the Token provided by Keycloak
					$token = $this->checkToken($this->vaultUrl, $this->jwtToken, $this->roleName);

					$responseArray = json_decode($token["response"], true);					
				
					if ($token["statusCode"] !== 200) {
						$errorMessage = isset($responseArray['errors']) ? ($responseArray['errors'][0]) : "Unknown error";
						$_SESSION['errorData']['Error'][] = "Vault request failed with status: ". print_r($errorMessage, true); 
						exit;
					}
					
					$vaultToken = $responseArray["auth"]["client_token"];

					if (empty($vaultToken)) {
						$_SESSION['errorData']['Error'][] = " Vault authentication failed. No client token received.";
						error_log("Vault Error: Vault authentication failed. No client token received.");
						return false;
					}
				
					if ($this->isTokenExpired($this->vaultUrl, $vaultToken)) {
						$_SESSION['errorData']['Error'][] = "The Vault token has expired.";
					} else {
						$_SESSION['errorData']['Info'][] = "The Vault token is valid.";
					}


					$secretPath = $GLOBALS['secretPath'];
					// Calling the function to actually wrote the $data in the Vault using the Token obtained after Keycloak identification
				
					$rz = $this->uploadFileToVault($this->vaultUrl, $secretPath, $_SESSION['User']['secretsId'], "SSH", $vaultToken, $data);
					
					return $vaultToken;

				} catch (Exception $e) {
					echo "Error: " . $e->getMessage();
				}
			} else {
				//SSH Key do not have the correct format
				//	echo "SSH keys are set but do not have the correct format.";
			}
		} elseif (isset($data['data']['Swift'])) {
			try {
	
				$token = $this->checkToken($this->vaultUrl, $this->jwtToken, $this->roleName);


				$responseArray = $token["response"];
				$respondeData = json_decode($responseArray, true);

				$vaultToken = $respondeData["auth"]["client_token"];

				$secretPath = $GLOBALS['secretPath'];
				if (isset($data['data']['Swift']['_id'])) {
					$filename = $data['data']['Swift']['_id'] . '_credentials.txt';
				} elseif (isset($data['data']['Swift']['_id'])) {
					$filename = $data['data']['Swift']['_id'] . '_credentials.txt';
				}

				$rz = $this->uploadFileToVault($this->vaultUrl, $secretPath, $_SESSION['User']['secretsId'], "Swift", $vaultToken, $data);
				return $vaultToken;
			} catch (Exception $e) {
				echo "Error: " . $e->getMessage();
			}
		} elseif (isset($data['data']['EGA'])) {
			try {
				$token = $this->checkToken($this->vaultUrl, $this->jwtToken, $this->roleName);
				$responseArray = $token["response"];
				$respondeData = json_decode($responseArray, true);
				if ($token["statusCode"] != 200) {
					error_log("Error: " . $respondeData["error"]);
				}

				$vaultToken = $respondeData["auth"]["client_token"];
				$secretPath = $GLOBALS['secretPath'];

				// Calling the function to actually wrote the $data in the Vault using the Token obtained after Keycloak identification
				$this->uploadFileToVault($this->vaultUrl, $secretPath, $_SESSION['User']['secretsId'], "EGA", $vaultToken, $data);
				return $vaultToken;
			} catch (Exception $e) {
				error_log("Error: " . $e->getMessage());
			}
		} else {
			$_SESSION['errorData']['Error'][] = "Invalid data format or system type";
		}
	}


	function renewVaultToken($vaultUrl, $vaultToken)
	{
		// Specify the endpoint for token renewal

		$renewEndpoint = $vaultUrl . 'auth/token/renew-self';
		// Prepare the cURL request
		$curl = curl_init($renewEndpoint);

		$headers = [
			'X-Vault-Token: ' . $vaultToken,
			'Content-Type: application/json',
		];

		$postData = json_encode(['increment' => '10m']);

		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);  // Ignore SSL certificate verification
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);  // Ignore host verification

		// Execute the cURL request
		$response = curl_exec($curl);

		// Check for cURL errors
		if (curl_errno($curl)) {
			echo 'Curl error: ' . curl_error($curl);
		}

		$responseData = json_decode($response, true);
		// Close cURL session
		curl_close($curl);

		if (isset($responseData['auth']['client_token'])) {
			// Return the new token
			return $responseData['auth']['client_token'];
		}
		return false;
	}


	public function retrieveDatafromVault($vaultToken, $url, $secretPath, $userSecretsId, $system)
	{
		$vaultUrl = $url . "/" . $secretPath . $userSecretsId . '/' . $system;

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $vaultUrl);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);  // Ignore SSL certificate verification
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);  // Ignore host verification
		curl_setopt($curl, CURLOPT_HTTPHEADER, [
			'X-Vault-Token: ' . $vaultToken,
		]);

		$response = curl_exec($curl);

		if (curl_errno($curl)) {
			echo 'Error: ' . curl_error($curl);
			return null;
		}

		$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		if ($httpCode === 403) {
			if ($this->isTokenExpired($url, $vaultToken)) {
				$_SESSION['errorData']['Error'][] = "The Vault token has expired, need to refresh it in the User section.";
			} else {
				$_SESSION['errorData']['Error'][] = "The Vault token is still valid.";
			}
		}

		curl_close($curl);
		$data = json_decode($response, true);
		if ($data === null) {
			return null;
		}
		if ($system == 'Swift') {
			// Extract app_id and app_secret from the JSON data
			
			$user_id = $data['data']['data']['Swift']['_id'];
			$app_id = $data['data']['data']['Swift']['app_id'];
			$app_secret = $data['data']['data']['Swift']['app_secret'];
			$domainName = $data['data']['data']['Swift']['domainName'];
			$projectName = $data['data']['data']['Swift']['projectName'];
			$interface = $data['data']['data']['Swift']['interface'];
			$projectDomainId = $data['data']['data']['Swift']['projectDomainId'];
			$projectId = $data['data']['data']['Swift']['projectId'];
			$projectDomainName = $data['data']['data']['Swift']['projectDomainName'];

			return [
				'user_id' => $user_id,
				'app_id' => $app_id,
				'app_secret' => $app_secret,
				'domainName' => $domainName,
				'projectDomainName' => $projectDomainName,
				'interface' => $interface,
				'projectDomainId' => $projectDomainId,
				'projectId' => $projectId,
				'projectName' => $projectName,
			];
		} elseif ($system == 'SSH') {
			$user_id = $data['data']['data']['SSH']['_id'];
			$pub_key = $data['data']['data']['SSH']['public_key'];
			$priv_key = $data['data']['data']['SSH']['private_key'];
			$username = $data['data']['data']['SSH']['user_key'];

			return [
				'user_id' => $user_id,
				'pub_key' => $pub_key,
				'priv_key' => $priv_key,
				'hpc_username' => $username,
			];
		} elseif ($system == 'ega') {
			
			if ($filename == $GLOBALS['bscEgaCredentialsFilename']) {
				$username = $data['data']['data']['username'];
				$password = $data['data']['data']['password'];

				return [
					'username' => $username,
					'password' => $password,
				];
			}

			$user_id = $data['data']['data']['EGA']['_id'];
			$username = $data['data']['data']['EGA']['username'];

			return [
				'user_id' => $user_id,
				'username' => $username,
			];
		}
	}

	public function renewToken($currentToken, $url)
	{

		$renewPath = "token/renew";
		$vaultUrl = $url . $renewPath;

		//$payload = json_encode(['increment' => '15m']); // You can modify the increment as needed

		$payload = json_encode(['clientToken' => $currentToken]);
		// Set up cURL options
		$curl = curl_init($vaultUrl);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);  // Ignore SSL certificate verification
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);  // Ignore host verification
		curl_setopt($curl, CURLOPT_HTTPHEADER, [
			'X-Vault-Token: ' . $currentToken,
			'Content-Type: application/json',
		]);
		// Execute cURL request and store the response
		$response = curl_exec($curl);
		// Close cURL resource
		curl_close($curl);
		// Check for cURL errors
		if (curl_errno($curl)) {
			echo 'Error: ' . curl_error($curl);
			return null;
		}
		$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		if ($httpCode === 200) {
			// Extract and return the renewed token from the response
			$responseData = json_decode($response, true);
			if (isset($responseData['auth']['client_token'])) {
				// Return the renewed token
				$_SESSION['errorData']['Warning'][] = "Needing to renew the token.";
				return $responseData['auth']['client_token'];
			} else {
				echo 'Error: Unable to extract renewed token from response';
				return null;
			}
		} else {
			// Handle other HTTP response codes or set an error message
			echo 'Error: Token renewal failed. HTTP Code: ' . $httpCode;
			return null;
		}
	}

	public function getSSHCredentials($vaultUrl, $vaultKey)
	{
		if (!$vaultKey) {
			$_SESSION['errorData']['Error'][] = "Vault Key is empty, are you sure you saved your credentials?";
			exit;
		}
		$credentials = $this->retrieveDatafromVault($vaultKey, $vaultUrl, $GLOBALS['secretPath'], $_SESSION['User']['secretsId'], 'SSH');
		if (!$credentials) {
			$_SESSION['errorData']['Error'][] = "Failed to retrieve SSH credentials from Vault, not present.";
			return 0;
		}
		// Extract SSH credentials
		$sshPrivateKey = $credentials['priv_key'];
		$sshPublicKey = $credentials['pub_key'];
		$sshUsername = $credentials['hpc_username'];
		// Store credentials in class properties instead of database
		return [
			'private_key' => $sshPrivateKey,
			'public_key' => $sshPublicKey,
			'username' => $sshUsername
		];
	}
}
