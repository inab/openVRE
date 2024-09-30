<?php


function getOpenstackUser($vaultUrl, $vaultToken, $accessToken, $vaultRolename, $username) {

	$vaultClient = new VaultClient($vaultUrl, $vaultToken, $accessToken, $vaultRolename, $username);
	$vaultKey = $_SESSION['User']['Vault']['vaultKey'];
	
	$credentials = $vaultClient->retrieveDatafromVault('Swift', $vaultKey, $vaultUrl, 'secret/mysecret/data/', $_SESSION['User']['_id'] . '_credentials.txt');
	if ($credentials) {
		//$openstackCredentials = json_decode($credentials, true);
		$appId = $credentials['app_id'];
		#echo "<br> . $appId . </br>";
		$appSecret = $credentials['app_secret'];
		$projectName = $credentials['projectName'];
		$userDomainName = $credentials['domainName'];
		$projectDomainName = $credentials['projectId'];

		$swiftClient = new SwiftClient($appId, $appSecret, $projectName, $userDomainName, $projectDomainName, 'public', 'https://ncloud.bsc.es:5000/v3/');
		var_dump($swiftClient);
		$lista=$swiftClient->runList();
		#var_dump($lista);
	}
}



?>
