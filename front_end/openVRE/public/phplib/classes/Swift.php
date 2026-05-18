<?php

namespace OpenVRE;

class SwiftClient
{
	private string $app_id;
	private string $app_secret;


	public function __construct(string $app_id, string $app_secret)
	{
		$this->app_id = $app_id;
		$this->app_secret = $app_secret;
	}


	public function runList()
	{
		$credentialsCommand = "export OS_AUTH_TYPE=v3applicationcredential && " .
			"export OS_AUTH_URL=https://ncloud.bsc.es:5000/v3/ && " .
			"export OS_IDENTITY_API_VERSION=3 && " .
			"export OS_INTERFACE=public && " .
			"export OS_APPLICATION_CREDENTIAL_ID={$this->app_id} && " .
			"export OS_APPLICATION_CREDENTIAL_SECRET={$this->app_secret}";

		$listCommand = "openstack container list";
		$fullCommand = "$credentialsCommand && $listCommand";
		
		return shell_exec($fullCommand);
	}
}
