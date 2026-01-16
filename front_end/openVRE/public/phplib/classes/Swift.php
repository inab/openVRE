<?php

#require_once 'vendor/autoload.php';

//use OpenStack\OpenStack;
//
use OpenStack\Identity\v3\Models\Token;

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

class SwiftClient
{

	private $interface;
	private $keystoneSession;
	private $authUrl;
	private $app_id;
	private $app_secret;


	public function __construct($app_id, $app_secret, $projectName, $userDomainName, $projectDomainName, $interface, $authUrl)
	{
		$this->app_id = $app_id;
		$this->app_secret = $app_secret;
		$this->interface = $interface;
		$this->authUrl = $authUrl;

		return $this;
	}


	public function generateCredentialsCommand()
	{

		// Construct the credentials command with $app_secret
		//
		$credentialsCommand = "export OS_AUTH_TYPE=v3applicationcredential && " .
			"export OS_AUTH_URL={$this->authUrl} && " .
			"export OS_IDENTITY_API_VERSION=3 && " .
			"export OS_INTERFACE={$this->interface} && " .
			"export OS_APPLICATION_CREDENTIAL_ID={$this->app_id} && " .
			"export OS_APPLICATION_CREDENTIAL_SECRET={$this->app_secret}";

		return $credentialsCommand;
	}


	public function runList()
	{
		$credentialsCommand = $this->generateCredentialsCommand();
		$listCommand = "openstack container list";
		$fullCommand = "$credentialsCommand && $listCommand"; // Final combined command
		$output = shell_exec($fullCommand);
		return $output;
	}


	public function __destruct()
	{
		// Close SSH session when object is destroyed
		if ($this->keystoneSession instanceof Token) {
			$this->keystoneSession->getHttpClient()->close();
		}
	}
}
