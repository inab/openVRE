<?php

#require_once 'vendor/autoload.php';

//use OpenStack\OpenStack;
//
use OpenStack\Common\Transport\Utils;
use OpenStack\OpenStack;
use OpenStack\Identity\v3\Models\Token;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;

// Access to Swift Object Storage to copy the data.
// Inputs: 
//  - Application Credential ID
//  - Application Credential Secret 
//  - Domain of the User 
//  - Project Name
//  - Auth Url and/or Type
//
//
//  >> Define the conf for the template in the Swift case
//  >> Fill up the template with the rest of the credentials needed
//  >> Open the Swift Obj Storage session
//  >> Check the content of the object storage 
//  >> Download the file in a tmp dir 
//
//

class SwiftClientaaa { 

	private $openstack; //session
	private $objectStorage; //obj storage session
	//private $client; 
	private $interface;
	private $token;
	private $keystoneSession;
	private $authUrl;
	private $containerName;
	private $app_id;
    	private $app_secret;
	//
	
	
	public function __construct($app_id, $app_secret, $projectName, $userDomainName, $projectDomainName, $interface, $authUrl,  $execution="",$project="",$descrip="",$output_dir="") {
	    
	//$this->root_dir_fs = $GLOBALS['clouds'][$this->cloudName]['mn_dir']. "/".$_SESSION['User']['linked_accounts']['MN']['username']. "/".$GLOBALS['clouds'][$this->cloudName]['dataDir_fs'];
	// "/home/\$REMOTE_USERNAME/openvre/userdata/"
        //$this->pub_dir_fs = $GLOBALS['clouds'][$this->cloudName]['mn_dir']. "/".$_SESSION['User']['linked_accounts']['MN']['username']."/".$GLOBALS['clouds'][$this->cloudName]['pubDir_fs'];
	//$this->auth = $GLOBALS['clouds'][$this->cloudName]['auth'];
		
	$this->app_id = $app_id;
	$this->app_secret = $app_secret;
	$this->projectName = $projectName;
	$this->userDomainName = $userDomainName;
	$this->projectDomainName = $projectDomainName;
	$this->interface = $interface;
	$this->authUrl = $authUrl;

	$options = [
            'authUrl' => 'https://ncloud.bsc.es:5000/v3/', // OpenStack Identity service endpoint
	    'region' => 'RegionOne', // Swift region
	    'identityApiVersion' => 'v3', 
            'user' => [
                'id' => $app_id, // Application credential ID
                'password' => $app_secret, // Application credential secret
                'domain' => [
                    'name' => $userDomainName, // Name of the user domain
                ],
            ],
            'scope' => [
                'project' => [
                    'name' => $projectName, // Name of the project
                    'domain' => [
                        'name' => $projectDomainName, // Name of the project domain
                    ],
                ],
	    ],
	];


	$authData = [
		'auth' => [
        	'identity' => [
            	'methods' => ['application_credential'],
            	'application_credential' => [
                	'id' => $app_id,
                	'secret' => $app_secret
            	]
        	],
        	'scope' => [
            	'project' => [
                	'name' => $projectName,
                	'domain' => ['id' => 'default']
            	]
        	]
    	]
	];

	//$this->openstack = new OpenStack\OpenStack($options);
        //$this->objectStore = $this->openstack->objectStoreV1();
	
	//$this->client = new SwiftClient();

	return $this;
	}

	public function authenticate() {


		$authData = [
                'auth' => [
                'identity' => [
                'methods' => ['application_credential'],
                'application_credential' => [
                        'id' => $this->app_id,
                        'secret' => $this->app_secret
                ]
                ]
		]];

		echo "<br></br> DATA ";
		var_dump($authData);
		echo "<br></br> ";
		
		$client = new Client([
        		'debug' => true, // Enable debug option to print the request details
    			]);
		echo "Url" . $this->authUrl . '/auth/tokens';
		try {
			$response = $client->post($this->authUrl . '/auth/tokens', [
				'json' => $authData
			]);
		} catch (ClientException $e) {
			echo 'Error: ' . $e->getMessage();
			exit;
		} catch (GuzzleException $e) {
			echo 'Guzzle Exception: ' . $e->getMessage();
			echo 'Response Body: ' . $e->getResponse()->getBody()->getContents() . PHP_EOL;
			exit;
		}
		$this->token = $response->getHeaderLine('X-Subject-Token');
		$this->keystoneSession = $response;

	}


	public function generateCredentialsCommand() {
	
	// Construct the credentials command with $app_secret
        	$credentialsCommand = "export OS_AUTH_TYPE=v3applicationcredential && " .
            		"export OS_AUTH_URL={$this->authUrl} && " .
            		"export OS_IDENTITY_API_VERSION=3 && " .
            		"export OS_REGION_NAME=\"{$this->RegionName}\" && " .
            		"export OS_INTERFACE={$this->interface} && " .
            		"export OS_APPLICATION_CREDENTIAL_ID={$this->app_id} && " .
            		"export OS_APPLICATION_CREDENTIAL_SECRET={$this->app_secret}";

        	return $credentialsCommand;
	
	}
	

	public function runToken() {
        	$credentialsCommand = $this->generateCredentialsCommand();

        	$openstackCommand = "openstack token issue -f json";
        	$fullCommand = "$credentialsCommand && $openstackCommand"; // Final combined command

        	echo "Command finale:\n$fullCommand";
        	$output = shell_exec($fullCommand);

		echo "Command output:\n$output";
		return $output;
	}
 
	
	public function extractTokenFromJson($jsonString) {
	    	// Decode the JSON string
    		$jsonObject = json_decode($jsonString);

    		// Check if decoding was successful
    		if ($jsonObject === null) {
        		return null; // Decoding failed
    		}

   	 	// Extract and return the token (id) value
    		return $jsonObject->id;
	}
	


	public function authenticateCURL() {
		$authData = [
        		'auth' => [
            		'identity' => [
                	'methods' => ['application_credential'],
                	'application_credential' => [
                    		'id' => $this->app_id,
                    		'secret' => $this->app_secret
                		]
            		]
        		]
    		];

    		$authUrl = $this->authUrl . '/auth/tokens';
    		$headers = [
        		'Content-Type: application/json',
        		'Accept: application/json',
    		];
    		$ch = curl_init();
    		curl_setopt($ch, CURLOPT_URL, $authUrl);
    		curl_setopt($ch, CURLOPT_POST, true);
    		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($authData));
    		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    		curl_setopt($ch, CURLOPT_VERBOSE, true); // Enable verbose mode for logging
    		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST'); // Set the request method to POST
    		curl_setopt($ch, CURLOPT_HEADER, true);
    		$response = curl_exec($ch);

    		if (curl_errno($ch)) {
        		echo 'Curl error: ' . curl_error($ch) . PHP_EOL;
    		}

    		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    		echo "HTTP Code: $httpCode\n";
    		echo "Response:\n$response\n"; // Output the full response for debugging

    		//if ($httpCode === 201) {
   		 //    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		//	$header = substr($response, 0, $headerSize);
		//	echo "HEDAER". $header;
    		preg_match('/X-Subject-Token: (.*)\r\n/', $response, $matches);
    		echo $matches[1];
    		var_dump($matches);
    		if (isset($matches[1])) {
	    		$this->token = $matches[1];
	    		echo "Token: " . $this->token . PHP_EOL;
        	}
    		curl_close($ch);
	}
	


	public function setContainer($token, $containerName)
	{
		echo "<br></br> TOKEN TOKEN <br></br>";
		echo $token;
        	$this->containerName = $containerName;
        	$this->objectStorage = $this->openstack->objectStoreV1(['token' => $token]);
	}

	

	public function prova($authToken, $containerName){
		$storageUrl = "https://swift.bsc.es/v1/AUTH_f785b84b836f407ca888dd00876e9150/$containerName";  // Replace 'account' and 'containerName'

    		$url = "$storageUrl?format=json";

    		$headers = [
        		"X-Auth-Token: $authToken",
    		];

    		$ch = curl_init();
   	 	curl_setopt($ch, CURLOPT_URL, $url);
    		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    		$response = curl_exec($ch);

    		if (curl_errno($ch)) {
        		echo 'Error:' . curl_error($ch);
    		}

    		curl_close($ch);

    		return $response;
	
	}


	function listSwiftContainerContents($authToken, $containerName) {
   		$options = [
        		'authUrl' => $this->authUrl,
        		'region' => $this->RegionName,
        		'token' => new Token(['id' => $authToken]),
    		];
		
		var_dump($options); 
			
    		$openstack = new OpenStack($options);

    		$objectStore = $openstack->objectStoreV1();

    		$container = $objectStore->getContainer($containerName);

    		$objects = $container->listObjects();

    		$contents = [];
    		foreach ($objects as $object) {
        		$contents[] = $object->getName();
    		}

    		return $contents;
}



	public function listObjects($containerName)
    	{
        	$container = $this->objectStorage->getContainer($containerName);
        	$objects = $container->listObjects();
        	$objectNames = [];

        	foreach ($objects as $object) {
            		$objectNames[] = $object->getName();
        		}

        	return $objectNames;
	}



	public function retrieveEndpoints()
	{
		$endpoints = $this->keystoneSession->auth['catalog'];
		foreach ($endpoints as $service) {
			$serviceType = $service['type'];
			$endpoints = $service['endpoints'];
			echo "Service Type: $serviceType\n";
			foreach ($endpoints as $endpoint) {
				$interface = $endpoint['interface'];
				$url = $endpoint['url'];
				$region = $endpoint['region'];
				echo "  Interface: $interface | URL: $url | Region: $region\n";
			}
		}
	}



	public function getToken() {
        	return $this->token;
    	}

	public function createSwiftConnection($token){
		$client = new Client();

    		// Set the token received from the authenticateCURL function
    		$this->keystoneSession->token = $token;
    		$this->keystoneSession->user = ['id' => null];

    		echo "TOKEN: " . $token . "<br></br>";
    		var_dump($this->keystoneSession);

    		try {
        		$response = $client->get($this->authUrl . '/auth/tokens', [
            			'headers' => [
                		'X-Auth-Token' => $token, // Use the provided token here
            			],
        		]);
    		} catch (ClientException $e) {
        		echo 'Error: ' . $e->getMessage();
        		exit;
    		} catch (GuzzleException $e) {
        		echo 'Guzzle Exception: ' . $e->getMessage();
        		exit;
    		}
    		$swiftToken = $response->getHeaderLine('X-Subject-Token');
    		$swiftStorageUrl = $response->getHeaderLine('X-Storage-Url');

    		echo "Retrieving account information...\n";
   	 	try {
        		$response = $client->get($swiftStorageUrl, [
            			'headers' => [
                		'X-Auth-Token' => $swiftToken,
            		],
        		]);
        		echo $response->getBody();
    		} catch (ClientException $e) {
        		echo 'Error: ' . $e->getMessage();
        		exit;
    		} catch (GuzzleException $e) {
        		echo 'Guzzle Exception: ' . $e->getMessage();
        		exit;
    		}
	}

	public function getObjectContent($containerName, $objectName)
	{
        	$container = $this->objectStore->getContainer($containerName);
        	$object = $container->getObject($objectName);
        	return $object->getContent();
	}

    	
	public function __destruct() {
        // Close SSH session when object is destroyed
		//$this->openstack->disconnect();
		if ($this->keystoneSession instanceof Token) {
            		$this->keystoneSession->getHttpClient()->close();
        	}
	}

}
