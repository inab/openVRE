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

class SwiftClient { 

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
	
	#var_dump($authData);
	#echo ("OK");
	//$this->openstack = new OpenStack\OpenStack($options);
        //$this->objectStore = $this->openstack->objectStoreV1();
	
	//$this->client = new SwiftClient();

	return $this;
	}



	public function generateCredentialsCommand() {
	
	// Construct the credentials command with $app_secret
		//
        	$credentialsCommand = "export OS_AUTH_TYPE=v3applicationcredential && " .
            		"export OS_AUTH_URL={$this->authUrl} && " .
            		"export OS_IDENTITY_API_VERSION=3 && " .
            		"export OS_REGION_NAME=\"{$this->RegionName}\" && " .
            		"export OS_INTERFACE={$this->interface} && " .
            		"export OS_APPLICATION_CREDENTIAL_ID={$this->app_id} && " .
            		"export OS_APPLICATION_CREDENTIAL_SECRET={$this->app_secret}";

        	return $credentialsCommand;
	
	}


	public function downlFileCommand($localPath, $containerName, $fileName) {

		$path = $localPath . basename($fileName);

		//echo "<br></br> Local Path constructed $path";

		$downCommand = "openstack object save --file $path $containerName $fileName";
		return $downCommand;

	}


	public function listContCommand($containerName){

		error_log("listContCommand - containerName: $containerName");
		$listCommand = "openstack object list --all $containerName -f json";

		error_log("listContCommand - listCommand: $listCommand");

		return $listCommand;
	
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


	public function runList() {

                $credentialsCommand = $this->generateCredentialsCommand();

                //$openstackCommand = "openstack token issue -f json";

                $listCommand = "openstack container list";

                //echo $listCommand;

                //$downCommand = $this->downlFileCommand($localPath, $containerName, $fileName);

                $fullCommand = "$credentialsCommand && $listCommand"; // Final combined command

                //echo "<br></br> Command finale:<br></br>  $fullCommand";
                $output = shell_exec($fullCommand);

		//var_dump($output);
		return $output;
	
	}

	public function runListContainer($containerName) {
		error_log("runListContainer - containerName: $containerName");
		$credentialsCommand = $this->generateCredentialsCommand();

		$listCommand = $this->listContCommand($containerName);

		$fullCommand = "$credentialsCommand && $listCommand";

		error_log("Running command: $fullCommand");
		$output = shell_exec($fullCommand);

		error_log("runListContainer - command output: " . implode("\n", $output));
	    	error_log("runListContainer - return value: $return_var");

		return $output;

		//return $fullCommand;

	}



	public function runDownloadFile($localPath, $containerName, $fileName) {
	
		error_log("runDownloadFile - containerName: $containerName, fileName: $fileName");

		error_log("runDownloadFile - containerName: $localPath");
		
		$credentialsCommand = $this->generateCredentialsCommand();
		$downloadCommand = $this->downlFileCommand($localPath, $containerName, $fileName);
		$fullCommand = "$credentialsCommand && $downloadCommand";

	    	error_log("Running command: $fullCommand");
		shell_exec("$fullCommand 2>&1");

		$fullFilePath = $localPath . basename($fileName);
		if (file_exists($fullFilePath)) {
			error_log("File exists at: $fullFilePath");
			return true;
		} else {
			error_log("File does not exist at: $fullFilePath. Command output: $output");
			return false;
		}
	
	}

	public function runAll($localPath, $containerName){
		$credentialsCommand = $this->generateCredentialsCommand();

                //$openstackCommand = "openstack token issue -f json";

		$listCommand = $this->listContCommand($containerName);

		echo $listCommand;

		//$downCommand = $this->downlFileCommand($localPath, $containerName, $fileName);
                
                $fullCommand = "$credentialsCommand && $listCommand"; // Final combined command

                echo "<br></br> Command finale:<br></br>  $fullCommand";
		$output = shell_exec($fullCommand);
	
		var_dump($output);

		
		$filteredArray = [];
		
		if (is_array($output)) {
		foreach ($output as $element) {
			$name = $element["Name"];	
			if (strpos($name, "obj") !== false) {
        				$filteredArray[] = $element;
    			}
		}
		} else {
			//$inputArray = json_decode($output, true);
			$type = gettype($output);
			echo "THE OUTPUT IS NOT AN ARRAY OR IS NULL, is $type";
			$inputArray = json_decode($output, true);
			foreach ($inputArray as $element) {
				$name = $element["Name"];
				if (strpos($name, "obj") !== false) {
					echo "Found 'obj' in: $name\n";
					$filteredArray[] = $element;

				}
			}
		}

		$fileCommand = "$credentialsCommand && ";

		foreach ($filteredArray as $element) {
			$dowloadCommand = $this-> downlFileCommand($localPath, $containerName, $element["Name"]);

			$fileCommand .= $dowloadCommand . " && " ;

		}

		$fileCommand = rtrim($fileCommand, " && ");

		$output1 = shell_exec($fileCommand);

		//var_dump($output1);

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
		$fileList = json_decode($response, true);

    		return $fileList;
	
	}
	
	
	public function downloadFile($authToken, $containerName, $localDirectory, $fileName) {
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
    		$fileList = json_decode($response, true); // Decode the JSON response

    		if ($fileList) {
			if (!is_dir($localDirectory)) {
				mkdir($localDirectory, 0777, true); // Create the local directory if it doesn't exist
				}
			
			foreach ($fileList as $fileInfo) {
            			if (basename($fileInfo['name']) === $fileName) {
                			echo "<br></br>";
                			echo "new";
                			var_dump($fileInfo);

                			$objectName = $fileInfo['name'];
                			$downloadUrl = "$storageUrl/$objectName";
                			$localFilePath = "$localDirectory/$objectName";

                			echo $objectName;
                			echo "<br></br>";
                			echo "PATH";
                			echo "<br></br>";
                			echo $localFilePath;

                			file_put_contents($localFilePath, fopen($downloadUrl, 'r'));
            			}
        		}	
			
			
			return true; // Download complete
			} else {
				return false; // Unable to decode JSON response
				}
	}


	public function SwiftPython() {

		$pythonScriptPath = 'path/to/your/swift_client.py'; // Replace with the actual path to the Python script
		$command = "python3 $pythonScriptPath";

		$output = array();
		$returnValue = null;
		exec($command, $output, $returnValue);

		if ($returnValue === 0) {
    			$result = implode("\n", $output);
    			echo $result;
		} else {
    			echo "Error executing Python script.";
		}

	}



	public function listSwiftContainerContents($authToken, $containerName) {
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



	public function __destruct() {
        // Close SSH session when object is destroyed
		//$this->openstack->disconnect();
		if ($this->keystoneSession instanceof Token) {
            		$this->keystoneSession->getHttpClient()->close();
        	}
	}

}
