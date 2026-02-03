<?php

namespace OpenVRE;

use Monolog\Logger;
use UnexpectedValueException;


class VaultClient
{
	private Logger $logger;
	private string $secretId;
	private string $secretPath;
	private string $token;
	private string $url;


	public function __construct(string $secretId, string $secretPath, string $token, string $url)
	{
		$this->logger = LoggerFactory::getLogger("Vault interface");
		$this->secretId = $secretId;
		$this->secretPath = $secretPath;
		$this->token = $token;
		$this->url = $url;
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
		$this->logger->info("Uploading file to $secretName Vault path");
		$url = $this->url . "/" . $this->secretPath . $this->secretId . '/' . $secretName;
		$headers = [
			'X-Vault-Token: ' . $this->token,
			'Content-Type: application/json'
		];

		$curl = curl_init($url);
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
			foreach ($response["errors"] as $error) {
				$this->logger->error($error);
			}

			throw new UnexpectedValueException("Failed to upload file to Vault.");
		}

		$this->logger->info("Vault file uploaded successfully.");
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


	public function retrieveDatafromVault($system)
	{
		$url = $this->url . "/" . $this->secretPath . $this->secretId . '/' . $system;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'X-Vault-Token: ' . $this->token,
		]);

		$response = curl_exec($ch);

		if (curl_errno($ch)) {
			$this->logger->error('Error retrieving data from Vault: ' . curl_error($ch));
			return null;
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
