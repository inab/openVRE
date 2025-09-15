<?php


function getOpenstackUser($vaultUrl, $accessToken, $vaultRolename, $username)
{

	$vaultClient = new VaultClient($vaultUrl, $accessToken, $vaultRolename, $username);
	$vaultKey = $_SESSION['userVaultInfo']['vaultKey'];

	$credentials = $vaultClient->retrieveDatafromVault($vaultKey, $vaultUrl, $GLOBALS['secretPath'], $_SESSION['User']['secretsId'], 'Swift');
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
