<?php

use OpenVRE\SwiftClient;
use OpenVRE\VaultClient;


function getOpenstackUser($accessToken)
{
	$vaultClient = new VaultClient($accessToken);

	$credentials = $vaultClient->retrieveDatafromVault('Swift');
	if ($credentials) {
		$appId = $credentials['app_id'];
		$appSecret = $credentials['app_secret'];
		$projectName = $credentials['projectName'];
		$userDomainName = $credentials['domainName'];
		$projectDomainName = $credentials['projectId'];

		$swiftClient = new SwiftClient($appId, $appSecret, $projectName, $userDomainName, $projectDomainName, 'public', 'https://ncloud.bsc.es:5000/v3/');
		var_dump($swiftClient);
		$lista = $swiftClient->runList();
	}
}
