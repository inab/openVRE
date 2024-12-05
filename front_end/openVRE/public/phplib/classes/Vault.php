<?php


use GuzzleHttp\Client;

class VaultClient {

    private $vaultUrl;
    private $vaultToken;
    private $httpClient;
    private $roleName;
    private $data;



    public function __construct($vaultUrl, $vaultToken, $jwtToken, $roleName, $username) {
	
	$this->vaultUrl = $vaultUrl;
	$this->vaultToken = $vaultToken;
	$this->jwtToken = $jwtToken;
	$this->roleName = $roleName;
	$this->credentials = $data;
	$this->username = $username;
	$this->httpClient = new Client();

    }


    public function checkToken ($vaultUrl, $jwtToken, $roleName){
	// $data = json_encode(array(
	//	"jwt" => $jwtToken));
	$headers = array(
		"Content-Type: application/json",
	);

	$url = $this->vaultUrl . "auth/jwt/login";
	//$url = $this->vaultUrl . "vault/auth/oidc/oidc/callback";

	echo "Authentication URL: " . $url . "\n";
	echo "Request Payload: " . $jwtToken . "\n";
 	echo "Role" .$roleName . "\n";

	$data = [
            'role' => $roleName,
	    'jwt' => $jwtToken,
	    'ttl' => '15m',
	    'renewable' => true, 
        ];
	
	// Constructing the curl command
	$curlCommand = "curl -X POST \"$url\" ";
	$curlCommand .= "-H 'Content-Type: application/json' ";
	$curlCommand .= "-d '" . json_encode($data) . "' ";

	// Outputting the curl command
	echo "Curl Command: $curlCommand\n";

	//echo "VABBE   \n";
	$ch = curl_init($url);
	// curl_setopt($ch, CURLOPT_URL, $url);
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
	
	$requestDetails = [
        'URL' => $url,
        'Method' => 'POST',
        'Headers' => $headers,
        'Data' => $data,
        'HTTP Code' => $httpCode,
        'Response' => $response,
    	];
    
    	echo "<pre>";
        print_r($requestDetails);
    	echo "</pre>";
	
	curl_close($ch);
	echo "<br>TOKEN TOKEN</br>";
	echo $httpCode;
	return array(
		'statusCode' => $httpCode,
		'response' => $response
	);

    }
 
    
    public function pre_sendJwtLoginRequest($url, $role, $jwtToken) {
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

    	echo "JSON  \n";
    	var_dump($context);
    	echo "END \n";
    	$url1 = $this->$url . "auth/jwt/login";
    	echo "url" . $url1;
    	$response = file_get_contents($url1, false, $context);
    
    	if ($response === false) {
        	throw new Exception("Failed to send the JWT login request: " . error_get_last()['message']);
    	}
    
    	return $response;
}

    public function isValidSSHPublicKey($key) {
    // Define a regular expression pattern for SSH public keys
	    $pattern1 = '/^ssh-(rsa|ed25519|ecdsa-[a-z0-9-]+) [A-Za-z0-9+\/=]+ ?(?:\S+)?$/';
	    $pattern2 = '/^-----BEGIN PUBLIC KEY-----[A-Za-z0-9+\/=\s]+-----END PUBLIC KEY-----/';
	    


    // Check if the key matches the pattern
    	return preg_match($pattern1, $key) === 1 || preg_match($pattern2, $key) === 1;
    
    }


    public function isValidSSHPrivateKey($key) {
    
	   
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

	    echo ("keyBody"); 
	    echo ($keyBody);
        // Check if the body is base64 encoded
	    if (!$this->isBase64($keyBody)) {
		    echo "Key body is not valid base64.\n";
	//	    return false;
	    }

        // Decode the base64 key body
	    $decodedKey = base64_decode($keyBody, true);
	    echo ("Decode");
	    echo ($decodedKey);   
        // Ensure the decoded key is in valid DER format
	    if (!$this->isValidDERFormat($decodedKey)) {
		    echo "Key is not in valid DER format.\n";
		    return false;
	    }
	    
	    echo "Key is valid.\n";
	    return true;    
    
    }

    private function validateOpenSSHPrivateKey($key) {
	    // Check for OpenSSH Private Key headers
	    
	    if (strpos($key, '-----BEGIN OPENSSH PRIVATE KEY-----') === false ||
		    strpos($key, '-----END OPENSSH PRIVATE KEY-----') === false) {
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
    private function formatKey($key) {

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
    private function isBase64($str) {
        // Check if the string matches base64 encoding
        return base64_encode(base64_decode($str, true)) === $str;
    }

    // Helper method to check if a DER encoded key is valid
    private function isValidDERFormat($der) {
        // Perform basic DER format validation
        // PKCS#1 DER format starts with 0x30 (SEQUENCE)
        // if (ord($der[0]) != 0x30) {
	//	echo "DER format does not start with 0x30.\n";
	//	return false;
        //}
	
	return substr($decodedKey, 0, 1) === "\x30";
        // More advanced checks can be added here
        //return true;
    
    }
    
    function uploadFileToVault($url, $secretPath, $username, $token, $data) {
	    $vaultUrl = $url . $secretPath . $username;
	    //echo $username; 
	    echo "<br></br> Data to be uploaded  <br></br>";
	    var_dump($data);

	    echo  "<br></br> Data to be uploaded " .  $vaultUrl .  "</br>" ;
	    echo  "<br></br> Data to be uploaded " .  $username .  "</br>" ;
	    //
	    //
	    echo "<br></br> Token to be uploaded <br></br>";
	    var_dump($token);

	    $headers = [
		    'X-Vault-Token: ' . $token,
		    'Content-Type: application/json'
	    ];

	    var_dump($headers);
	    $data_json = json_encode($data);
	    var_dump($data_json);
	    
	    $curl = curl_init($vaultUrl);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
	    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
	    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	    $response = curl_exec($curl);
	    
	    if (curl_errno($curl)) {
		    throw new Exception("Failed to send the JWT login request:" . curl_error($curl));
		    echo "Error occurred: " . curl_error($curl) . "\n";
	    } else {
		    echo "File uploaded to Vault successfully!\n";
		    echo "Response: " . $response . "\n";
	    }
	    curl_close($curl);
	    return $response;
    }	




    public function pre_checkFileInVault($filePath) {
        $endpoint = $this->vaultUrl . 'secret/' . $filePath;

        try {
            $response = $this->httpClient->get($endpoint, [
                'headers' => [
                    'X-Vault-Token' => $this->vaultToken
                ]
            ]);

            if ($response->getStatusCode() === 200) {
		    echo "File exists in Vault.\n";
		    echo "File path:" . $endpoint;

            } else {
                echo "File does not exist in Vault.\n";
            }
        } catch (Exception $e) {
            echo "Error occurred: " . $e->getMessage() . "\n";
        }
    }

	
    function listSecretsInVault($token, $url, $secretPath, $userName) {
	    $headers = [
		    'X-Vault-Token: ' . $token
	    ];

	    $vaultUrl = $url . $secretPath . $userName;
	    //echo ' BHOOOOOO';
	    //echo $vaultUrl;

	    $curl = curl_init($vaultUrl);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
	    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	    
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
    public function retrieveTokenLookup($vaultUrl, $vaultToken){

	    $url = $vaultUrl . 'auth/token/lookup-self';
	    $headers = ['X-Vault-Token: ' . $vaultToken];
	    $data = json_encode(['token' => 'ClientToken']);

	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL, $url);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
	    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

	    $response = curl_exec($ch);
	    if (curl_errno($ch)) {
		    echo 'Error: ' . curl_error($ch);
		    return null;
	    }
	    $requestDetails = [
                'URL' => $url,
                'Method' => 'GET',
                'Headers' => $headers,
                'Data' => $data,
                'HTTP Code' => $httpCode,
                'Response' => $response,
	    ];

	    
	    //echo "<pre>";
	    //print_r($requestDetails);
	    //echo "</pre>";

	    curl_close($ch);
	    return json_decode($response, true);
    
    }


    //Function using the loookup to see if the token has expired and needs a refresh
    public function isTokenExpired($vaultUrl, $vaultToken) {
	    date_default_timezone_set('UTC');

	    $tokenLookup = $this->retrieveTokenLookup($vaultUrl, $vaultToken);
	    //echo "tokenÑLookip";
	    //var_dump($tokenLookup);
	    if ($tokenLookup && isset($tokenLookup['data']['expire_time'])) {
		//echo "tokenLookup_datatime";
		//var_dump($tokenLookup['data']['expire_time']);    
		$ttl = $tokenLookup['data']['ttl'];
    		$currentTimestamp = time();
		$expireTimestamp = $currentTimestamp + $ttl;
		
		//echo 'Expire Time: ' . date('Y-m-d H:i:s', $expireTimestamp) . PHP_EOL;
		
		$remainingTimeInMinutes = ceil(($expireTimestamp - $currentTimestamp) / 60);
		
		//echo 'Remaining Time until Expiration: ' . $remainingTimeInMinutes . ' minutes' . PHP_EOL;		
		
		return $remainingTimeInMinutes <= 0;
	    }
	    
	    //echo "vale?";
	    return true;
    }


    public function getTokenExpirationTime($vaultUrl, $vaultToken) {
	  
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



    public function uploadKeystoVault($data){
	    var_dump($data);
	    if (isset($data['data']['SSH'])){
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
				$responseArray = $token["response"];
				$respondeData = json_decode($responseArray, true);
				var_dump($respondeArray);
				$vaultToken = $respondeData["auth"]["client_token"];
//				$tokenTime = $this->getTokenExpirationTime($vaultUrl, $vaultToken);	
					
			//	echo "client token:";
			//	echo  $responseArray;	

				if ($this->isTokenExpired($this->vaultUrl,$vaultToken)) {
					echo 'The Vault token has expired.';
					$_SESSION['errorData']['Error'][] = "The Vault token has expired.";

				} else {
					echo 'The Vault token is still valid.';
					$_SESSION['errorData']['Error'][] = "The Vault token is still valid.";

				}

					
				$secretPath = 'secret/mysecret/data/';
				if (isset($data['data']['SSH']['_id'])) {
					$fileName = $data['data']['SSH']['_id'] . '_credentials.txt';
				} elseif (isset($data['data']['Swift']['_id'])) {
					$fileName =$data['data']['Swift']['_id'] . '_credentials.txt' ;
				}
				// Calling the function to actually wrote the $data in the Vault using the Token obtained after Keycloak identification
			//	var_dump($rz);
			//	echo json_encode($rz, JSON_PRETTY_PRINT);
				//$rx = $this->listSecretsInVault($clientToken, $this->vaultUrl, $secretPath, $fileName);
			//	echo json_encode($rx, JSON_PRETTY_PRINT);
				//$system = 'SSH';
				$rz = $this->uploadFileToVault($this->vaultUrl, $secretPath, $fileName, $vaultToken, $data);
			//	echo 'BHOOOOOOOOOO';
				//$xx = $this->retrieveDatafromVault($system, $clientToken, $this->vaultUrl, $secretPath, $fileName);
				//var_dump($xx);
				//
			//	var_dump($rz);
		//		echo json_encode($rz, JSON_PRETTY_PRINT);
				return $vaultToken;

			} catch (Exception $e) {
				echo "Error: " . $e->getMessage();
			}

		} else {
			//SSH Key do not have the correct format
			//echo "PUB" . $data['data']['SSH']['public_key'];
			//echo "PRIV" . $data['data']['SSH']['private_key'];
			echo "SSH keys are set but do not have the correct format.";
		}
	    } elseif (isset($data['data']['Swift'])) {
		    try {
			    echo "hello?";
                                // First access the Vault with the Token provided by Keycloak
			    echo $this->vaultUrl;
			    echo $this->jwtToken;
			    echo $this->roleName;
			    echo "token";
			    $token = $this->checkToken($this->vaultUrl, $this->jwtToken, $this->roleName);
			    print "Token1";
			    echo $token;
			    	$responseArray = $token["response"];
                                $respondeData = json_decode($responseArray, true);
                                $vaultToken = $respondeData["auth"]["client_token"];

                  //              echo "client token:";
                   //             echo  $responseArray;

                                $secretPath = 'secret/mysecret/data/';
                                if (isset($data['data']['Swift']['_id'])) {
                                        $fileName = $data['data']['Swift']['_id'] . '_credentials.txt';
                                } elseif (isset($data['data']['Swift']['_id'])) {
                                        $fileName =$data['data']['Swift']['_id'] . '_credentials.txt' ;
				}

				// Calling the function to actually wrote the $data in the Vault using the Token obtained after Keycloak identification
				// uploadFileToVault($url, $secretPath, $username, $token, $data)
				
				echo 'Ora mettiamo nel Vault';
                                $rz = $this->uploadFileToVault($this->vaultUrl, $secretPath, $fileName, $vaultToken, $data);
                                var_dump($rz);
                                echo json_encode($rz, JSON_PRETTY_PRINT);
                                //$rx = $this->listSecretsInVault($clientToken, $this->vaultUrl, $secretPath, $fileName);
                                //echo json_encode($rx, JSON_PRETTY_PRINT);
                                //$system = 'SSH';
                                //echo 'BHOOOOOOOOOO';
                                //$xx = $this->retrieveDatafromVault($system, $clientToken, $this->vaultUrl, $secretPath, $fileName);
                                //var_dump($xx);
                                return $vaultToken;

                        } catch (Exception $e) {
                                echo "Error: " . $e->getMessage();
                        }
	    } else {
	     	    // Invalid data format or system type
		    //echo "Invalid data format or system type.";
		    $_SESSION['errorData']['Error'][] = "Invalid data format or system type";
                    return;
	    }
    }


    function renewVaultToken($vaultUrl, $vaultToken) {
    // Specify the endpoint for token renewal
	
	    $renewEndpoint = $vaultUrl . 'auth/token/renew-self';
	    // Prepare the cURL request
	    $ch = curl_init($renewEndpoint);
	    // Set cURL option 
	    echo "renewVaultToken_1";
	    echo $renewEndpoint;
	    echo $vaultToken;
	    $headers = [
		    'X-Vault-Token: ' . $vaultToken,
        	    'Content-Type: application/json',
    	    ];

    	    $postData = json_encode(['increment' => '10m']);

	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($ch, CURLOPT_POST, true);
	    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
	    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

	    // Execute the cURL request
	    $response = curl_exec($ch);

	    // Check for cURL errors
	    if (curl_errno($ch)) {
	        echo 'Curl error: ' . curl_error($ch);
    	    }
		
	    $requestDetails = [
                'URL' => $renewEndpoint,
                'Headers' => $headers,
                'Data' => $data,
                'HTTP Code' => $httpCode,
                'Response' => $response,
            ];


            //echo "<pre>";
            //print_r($requestDetails);
            //echo "</pre>";

	
	    $responseData = json_decode($response, true);
	    // Close cURL session
	    curl_close($ch);

	    // Output the response
	    //echo $response;
	    // Check if the response contains a new token
	    
	    if (isset($responseData['auth']['client_token'])) {
		    // Return the new token
		    return $responseData['auth']['client_token'];
	    }
	    return false;
    }



    public function uploadKeystoVault_check($data){
            if (isset($data['data']['SSH'])){
                if ($this->isValidSSHPublicKey($data['data']['SSH']['public_key'])){
                        //echo "SSH keys are set and have the correct format.";
			if ($this->validateOpenSSHPrivateKey($data['data']['SSH']['private_key'])) {
                        	try {
                                // First access the Vault with the Token provided by Keycloak
                                	$token = $this->checkToken($this->vaultUrl, $this->jwtToken, $this->roleName);
                                	$responseArray = $token["response"];
	                                $respondeData = json_decode($responseArray, true);
        	                        $clientToken = $respondeData["auth"]["client_token"];
	
                                //echo "client token:";
                                //echo  $responseArray;

	                                $secretPath = 'secret/mysecret/data/';
        	                        if (isset($data['data']['SSH']['_id'])) {
                	                        $fileName = $data['data']['SSH']['_id'] . '_credentials.txt';
                        	        } elseif (isset($data['data']['Swift']['_id'])) {
                                	        $fileName =$data['data']['Swift']['_id'] . '_credentials.txt' ;
                                	}
                                // Calling the function to actually wrote the $data in the Vault using the Token obtained after Keycloak identification
	                                $rz = $this->uploadFileToVault($this->vaultUrl, $secretPath, $fileName, $clientToken, $data);
                                //var_dump($rz);
                                //echo json_encode($rz, JSON_PRETTY_PRINT);
                                //$rx = $this->listSecretsInVault($clientToken, $this->vaultUrl, $secretPath, $fileName);
                                //echo json_encode($rx, JSON_PRETTY_PRINT);
                                //$system = 'SSH';
                                //echo 'BHOOOOOOOOOO';
                                //$xx = $this->retrieveDatafromVault($system, $clientToken, $this->vaultUrl, $secretPath, $fileName);
                                //var_dump($xx);
        	                        return $clientToken;
	
        	                } catch (Exception $e) {
                	                echo "Error: " . $e->getMessage();
				}
			} else {
        // SSH private key is invalid
				echo "Error: Invalid SSH private key format.";
    			}

                } else {
                        //SSH Key do not have the correct format
                        //echo "PUB" . $data['data']['SSH']['public_key'];
			//echo "PRIV" . $data['data']['SSH']['private_key'];
			echo "Error: Invalid SSH public key format.";
                        echo "SSH keys are set but do not have the correct format.";
                }
            }

    }


    public function retrieveDatafromVault($system, $vaultToken, $url, $secretPath, $fileName){ 
	   // Set up cURL options
	
	$vaultUrl = $url . $secretPath . $fileName;

	$ch = curl_init();
    	curl_setopt($ch, CURLOPT_URL, $vaultUrl);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    	curl_setopt($ch, CURLOPT_HTTPHEADER, [
        	'X-Vault-Token: ' . $vaultToken,
    	]);

    	// Execute cURL request and store the response
    	$response = curl_exec($ch);

    	// Check for cURL errors
    	if (curl_errno($ch)) {
        	echo 'Error: ' . curl_error($ch);
        	return null;
    	}

	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Log the request details for debugging purposes
	    $requestDetails = [
        	'URL' => $url,
	        'Method' => 'POST',
	        'Headers' => $headers,
	        'Data' => $data,
	        'HTTP Code' => $httpCode,
        	'Response' => $response,
    	];
    
    	//echo "<pre>";
   	//print_r($requestDetails);
	//echo "</pre>";
	
	if ($httpCode === 403) {
		// Call the function to renew the token
		//$_SESSION['errorData']['Error'][] = "Check the Token User permission.";
		if ($this->isTokenExpired($url, $vaultToken)) {
			echo 'The Vault token has expired.';
			$_SESSION['errorData']['Error'][] = "The Vault token has expired, need to refresh it in the User section.";
		}else {
			echo 'The Vault token is still valid.';
			$_SESSION['errorData']['Error'][] = "The Vault token is still valid.";
		}

    		//$this->retrieveDatafromVault($system, $newToken, $url, $secretPath, $fileName);
	} else {
    	// Handle other HTTP response codes or set an error message in session data
    	// For example, you can use session_start() if not already started
    		//$_SESSION['errorData']['Error'][] = "Something is wrong with the credentials, check the User permission.";
	}

    	// Close cURL resource
    	curl_close($ch);

    	// Parse JSON response
	$data = json_decode($response, true);
    	// Check if the JSON decoding was successful
    	if ($data === null) {
        	echo 'Error decoding JSON';
        	return null;
	}
	if ($system == 'Swift') {
		// Extract app_id and app_secret from the JSON data
		//
		//
		//echo ("COLOR VIOLET");
		//var_dump($data);
		$user_id = $data['data']['data']['Swift']['_id'];
    		$app_id = $data['data']['data']['Swift']['app_id'];
		$app_secret = $data['data']['data']['Swift']['app_secret'];
		$domainName = $data['data']['data']['Swift']['domainName'];
		$projectName = $data['data']['data']['Swift']['projectName'];
		$interface = $data['data']['data']['Swift']['interface'];
		$projectDomainId = $data['data']['data']['Swift']['projectDomainId'];
		$projectId =  $data['data']['data']['Swift']['projectId'];
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
		$username = $data['data']['data']['SSH']['username'];
		
		return [
			'user_id' => $user_id,
			'pub_key' => $pub_key,
			'priv_key' => $priv_key,
			'hpc_username' => $username,
		];

		}
    }

    public function renewToken ($currentToken, $url) {

	    $renewPath = "token/renew"; 
	    $vaultUrl = $url . $renewPath;

	    //$payload = json_encode(['increment' => '15m']); // You can modify the increment as needed
		
	    $payload = json_encode(['clientToken' => $currentToken]);
	    // Set up cURL options
	    $ch = curl_init($vaultUrl);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    	    curl_setopt($ch, CURLOPT_POST, true);
    	    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    	    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'X-Vault-Token: ' . $currentToken,
        	    'Content-Type: application/json',
    	    ]);

    		// Execute cURL request and store the response
    	    $response = curl_exec($ch);


	    // Check for cURL errors
	    
	    if (curl_errno($ch)) {
		    echo 'Error: ' . curl_error($ch);
        	    return null;
    		}

    	    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    		// Log the request details for debugging purposes
    	    $requestDetails = [
        	'URL' => $vaultUrl,
        	'Method' => 'POST',
        	'Headers' => ['X-Vault-Token: ' . $currentToken, 'Content-Type: application/json'],
        	'Data' => $payload,
        	'HTTP Code' => $httpCode,
        	'Response' => $response,
   	     ];

      	    //echo "<pre>";
    	    //print_r($requestDetails);
    	    //echo "</pre>";

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

    // Close cURL resource
    curl_close($ch);



    }


}
