<?php

namespace OpenVRE;

use Monolog\Logger;
use UnexpectedValueException;


class VaultClient
{

	private $jwtToken;
	private Logger $logger;


	public function __construct($jwtToken)
	{
		$this->logger = LoggerFactory::getLogger("Vault interface");
		$this->jwtToken = $jwtToken;
	}


	private function fetchVaultToken()
	{
		$headers = array("Content-Type: application/json",);
		$url = $GLOBALS['vaultUrl'] . "/auth/jwt/login";

		$data = [
			'role' => $GLOBALS['vaultRolename'],
			'jwt' => $this->jwtToken,
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
			throw new UnexpectedValueException("Failed to send the JWT login request: $error");
		}

		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$response = json_decode($response, true);

		if ($httpCode >= 400) {
			$this->logger->error("Failed to fetch the Vault token: HTTP $httpCode");
			foreach ($response["errors"] as $error) {
				$this->logger->error($error);
			}

			throw new UnexpectedValueException("Failed to fetch the Vault token.");
		}

		return $response["auth"]["client_token"];
	}


	public function isValidSSHPublicKey($key)
	{
		// Define a regular expression pattern for SSH public keys
		$pattern1 = '/^ssh-(rsa|ed25519|ecdsa-[a-z0-9-]+) [A-Za-z0-9+\/=]+ ?(?:\S+)?$/';
		$pattern2 = '/^-----BEGIN PUBLIC KEY-----[A-Za-z0-9+\/=\s]+-----END PUBLIC KEY-----/';



		// Check if the key matches the pattern
		return preg_match($pattern1, $key) === 1 || preg_match($pattern2, $key) === 1;
	}


	private function validateOpenSSHPrivateKey($key)
	{
		// Check for OpenSSH Private Key headers

		if (
			strpos($key, '-----BEGIN OPENSSH PRIVATE KEY-----') === false ||
			strpos($key, '-----END OPENSSH PRIVATE KEY-----') === false
		) {
			$this->logger->error("Invalid OpenSSH private key headers.");
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
			$this->logger->error("Base64 decoding failed. The key body might be corrupted.");
			return false;
		}

		// Check if the decoded key starts with the OpenSSH magic header
		if (substr($decodedKey, 0, 15) !== "openssh-key-v1\0") {
			$this->logger->error("Invalid OpenSSH key format.");
			return false;
		}

		$this->logger->info("The key is a valid OpenSSH private key.");
		return true;
	}


	public function uploadFileToVault($secretName, $data)
	{
		$vaultUrl = $GLOBALS['vaultUrl'] . "/" . $GLOBALS['secretPath'] . $_SESSION['User']['secretsId'] . '/' . $secretName;
		$token = $this->fetchVaultToken();
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

		if (curl_errno($curl)) {
			$this->logger->error("Failed to send the JWT login request:" . curl_error($curl));
			throw new UnexpectedValueException("Error saving secrets.");
		}

		$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$response = json_decode($response, true);

		if ($httpCode >= 400) {
			$this->logger->error("Failed to upload file to Vault: HTTP $httpCode");
			$this->logger->error("Request url is: " . $vaultUrl);
			$this->logger->error("Request token is: " . $token);
			$this->logger->error("Request data is: " . json_encode($data));
			foreach ($response["errors"] as $error) {
				$this->logger->error($error);
			}

			throw new UnexpectedValueException("Failed to upload file to Vault.");
		}

		$_SESSION['userVaultInfo']['vaultKey'] = $token;

		$this->logger->info("Vault file uploaded successfully.");
		$this->logger->info($response);
	}


	// Function to retrieve token lookup response from Vault
	public function retrieveTokenLookup($vaultToken)
	{
		$url = $GLOBALS['vaultUrl'] . 'auth/token/lookup-self';
		$headers = ['X-Vault-Token: ' . $vaultToken];

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$response = curl_exec($ch);
		if (curl_errno($ch)) {
			$this->logger->error('Error retrieving token lookup: ' . curl_error($ch));
			return null;
		}

		return json_decode($response, true);
	}


	//Function using the loookup to see if the token has expired and needs a refresh
	public function isTokenExpired($vaultToken)
	{
		date_default_timezone_set('UTC');
		$tokenLookup = $this->retrieveTokenLookup($vaultToken);
		if ($tokenLookup && isset($tokenLookup['data']['expire_time'])) {
			$ttl = $tokenLookup['data']['ttl'];
			$currentTimestamp = time();
			$expireTimestamp = $currentTimestamp + $ttl;
			$remainingTimeInMinutes = ceil(($expireTimestamp - $currentTimestamp) / 60);

			return $remainingTimeInMinutes <= 0;
		}

		return true;
	}


	public function getTokenExpirationTime()
	{
		date_default_timezone_set('UTC');
		$tokenLookup = $this->retrieveTokenLookup($_SESSION['userVaultInfo']['vaultKey']);
		if ($tokenLookup && isset($tokenLookup['data']['expire_time'])) {
			$ttl = $tokenLookup['data']['ttl'];
			$currentTimestamp = time();
			$expireTimestamp = $currentTimestamp + $ttl;
			// Return the expiration time in a human-readable format
			return $expireTimestamp;
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
				$this->logger->error("Invalid SSH public key format.");
			}

			// Validate the private key
			if (!$this->validateOpenSSHPrivateKey($privateKey)) {
				$this->logger->error("Invalid SSH private key format.");
			}

			if ($this->isValidSSHPublicKey($publicKey) && $this->validateOpenSSHPrivateKey($privateKey)) {
				$this->logger->info("SSH keys are set and have the correct format.");
				$this->uploadFileToVault("SSH", $data);
			}
		} elseif (isset($data['data']['Swift'])) {
			$this->uploadFileToVault("Swift", $data);
		} elseif (isset($data['data']['EGA'])) {
			$this->uploadFileToVault("EGA", $data);
		} else {
			$_SESSION['errorData']['Error'][] = "Invalid data format or system type";
		}
	}


	function renewVaultToken($vaultToken)
	{
		// Specify the endpoint for token renewal
		$renewEndpoint = $GLOBALS['vaultUrl'] . 'auth/token/renew-self';
		// Prepare the cURL request
		$ch = curl_init($renewEndpoint);
		// Set cURL option
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
			$this->logger->error('Curl error: ' . curl_error($ch));
		}

		$responseData = json_decode($response, true);

		// Check if the response contains a new token
		if (isset($responseData['auth']['client_token'])) {
			// Return the new token
			return $responseData['auth']['client_token'];
		}

		return false;
	}


	public function retrieveDatafromVault($system)
	{
		$vaultUrl = $GLOBALS['vaultUrl'] . "/" . $GLOBALS['secretPath'] . $_SESSION['User']['secretsId'] . '/' . $system;
		$vaultToken = $_SESSION['userVaultInfo']['vaultKey'];

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $vaultUrl);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'X-Vault-Token: ' . $vaultToken,
		]);

		$response = curl_exec($ch);

		if (curl_errno($ch)) {
			$this->logger->error('Error retrieving data from Vault: ' . curl_error($ch));
			return null;
		}

		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if ($httpCode === 403) {
			if ($this->isTokenExpired($vaultToken)) {
				$_SESSION['errorData']['Error'][] = "The Vault token has expired, need to refresh it in the User section.";
			} else {
				$_SESSION['errorData']['Error'][] = "The Vault token is still valid.";
			}
		}

		$data = json_decode($response, true);
		if ($data === null) {
			return null;
		}
		if ($system == 'Swift') {
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
			$username = $data['data']['data']['SSH']['username'];

			return [
				'user_id' => $user_id,
				'pub_key' => $pub_key,
				'priv_key' => $priv_key,
				'hpc_username' => $username,
			];
		} elseif ($system == 'ega') {
			$user_id = $data['data']['data']['EGA']['_id'];
			$username = $data['data']['data']['EGA']['username'];

			return [
				'user_id' => $user_id,
				'username' => $username,
			];
		}
	}
}
