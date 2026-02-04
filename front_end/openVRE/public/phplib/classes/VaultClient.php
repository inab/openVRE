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


	public function uploadFileToVault($system, $data)
	{
		$this->logger->info("Uploading file to $system Vault path");
		$url = $this->url . "/" . $this->secretPath . $this->secretId . '/' . $system;
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


	public function retrieveDatafromVault($system)
	{
		$this->logger->info("Retrieving $system data from Vault");
		$url = $this->url . "/" . $this->secretPath . $this->secretId . '/' . $system;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'X-Vault-Token: ' . $this->token,
		]);

		$response = curl_exec($ch);

		if (curl_errno($ch)) {
			$this->logger->error('Failed to request data from Vault: ' . curl_error($ch));
			throw new UnexpectedValueException('Failed to request data from Vault.');
		}

		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$response = json_decode($response, true);

		if ($httpCode >= 400) {
			$this->logger->error("Failed to retrieve data from Vault: HTTP $httpCode");
			foreach ($response["errors"] as $error) {
				$this->logger->error($error);
			}

			throw new UnexpectedValueException("Failed to retrieve data from Vault.");
		}

		if (json_last_error() !== JSON_ERROR_NONE) {
			throw new UnexpectedValueException('Error decoding JSON data: ' . json_last_error_msg());
		}

		return $response['data']['data'][$system];
	}
}
