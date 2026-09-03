<?php

namespace OpenVRE;

use Monolog\Logger;
use UnexpectedValueException;


class VaultTokenProvider
{
    private string $jwt;
    private Logger $logger;
    private string $rolename;
    private string $url;


    public function __construct(string $jwt, string $rolename, string $url)
    {
        $this->jwt = $jwt;
        $this->logger = LoggerFactory::getLogger("Vault token interface");
        $this->rolename = $rolename;
        $this->url = $url;
    }


    private function fetchVaultToken(): array
    {
        $this->logger->info("Fetching new Vault token");
        $headers = array("Content-Type: application/json",);
        $url = $this->url . "/auth/jwt/login";

        $data = [
            'role' => $this->rolename,
            'jwt' => $this->jwt
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if (getenv('VAULT_CERT_CAFILE') !== false) {
            curl_setopt($ch, CURLOPT_CAINFO, getenv('VAULT_CERT_CAFILE'));
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_3);
        }

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


        return $response;
    }


    public function getToken(): string
    {
        if ($this->hasValidToken()) {
            return $_SESSION['userVaultInfo']['vaultKey'];
        }

        $tokenData = $this->fetchVaultToken();

        $_SESSION['userVaultInfo']['vaultKey'] = $tokenData['auth']['client_token'];
        $_SESSION['userVaultInfo']['expires_in'] = time() + $tokenData['lease_duration'];

        return $_SESSION['userVaultInfo']['vaultKey'];
    }


    private function hasValidToken(): bool
    {
        return isset($_SESSION['userVaultInfo']['vaultKey'], $_SESSION['userVaultInfo']['expires_in'])
            && $_SESSION['userVaultInfo']['expires_in'] > time();
    }
}
