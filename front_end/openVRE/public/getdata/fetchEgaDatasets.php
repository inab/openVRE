<?php

require __DIR__ . "/../../config/globals.inc.php";
require __DIR__ . "/../../public/phplib/session.inc";


try {
    $currentPage = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    $offset = ($currentPage - 1) * 10;

    $userEmail = $_SESSION['User']['Email'];
    $vaultToken = $_SESSION['userVaultInfo']['vaultKey'];
    $vaultAddress = $GLOBALS['vaultUrl'] . "/" . $GLOBALS['secretPath'] . $_SESSION['User']['secretsId'] . '/EGA';

    function fetchVaultData($vaultAddress, $vaultToken)
    {
        $ch = curl_init($vaultAddress);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "X-Vault-Token: $vaultToken"
        ]);

        curl_setopt($ch, CURLOPT_CAINFO, getenv('VAULT_CERT_CAFILE'));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_3);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception('cURL error: ' . curl_error($ch));
        }

        curl_close($ch);

        $responseData = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Error decoding JSON data: ' . json_last_error_msg());
        }

        return $responseData;
    }

    $data = fetchVaultData($vaultAddress, $vaultToken);

    $egaUsername = $data['data']['data']['EGA']['username'] ?? null;
    $egaPassword = $data['data']['data']['EGA']['password'] ?? null;

    if ($egaUsername === null || $egaPassword === null) {
        throw new Exception('EGA credentials not found. Try to link your EGA account again.');
    }

    $params = [
        'client_id' => 'metadata-api',
        'username' => $egaUsername,
        'password' => $egaPassword,
        'grant_type' => 'password'
    ];

    $ch = curl_init($GLOBALS['EGA_METADATA_TOKEN_ENDPOINT']);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));

    $jsonData = curl_exec($ch);

    if (curl_errno($ch)) {
        throw new Exception('cURL error: ' . curl_error($ch));
    }

    curl_close($ch);

    $tokenDataArray = json_decode($jsonData, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error decoding JSON data: ' . json_last_error_msg());
    }

    $accessToken = $tokenDataArray['access_token'] ?? null;

    if ($accessToken === null) {
        throw new Exception('Error fetching EGA token. Check your credentials and try again.');
    }

    function fetch_dataset_files($dataset_id, $offset = 0, $limit = 10, $accessToken)
    {
        $egaDatasetFilesEndpoint = $GLOBALS['EGA_METADATA_API'] . '/datasets/' . $dataset_id . '/files?offset=' . $offset . '&limit=' . $limit;
        $context = stream_context_create([
            "http" => [
                "header" => "Authorization: Bearer $accessToken"
            ]
        ]);

        $jsonData = file_get_contents($egaDatasetFilesEndpoint, false, $context);
        return json_decode($jsonData, true);
    }

    // Check if we're fetching files for a specific dataset
    if (isset($_GET['action']) && $_GET['action'] === 'fetch_files') {
        $accession_id = htmlspecialchars($_GET['accession_id']);
        $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
        $files = fetch_dataset_files($accession_id, $offset, $limit, $accessToken);
        header('Content-Type: application/json');
        echo json_encode($files);
        exit;
    }

    $egaDatasetsEndpoint = $GLOBALS['EGA_METADATA_API'] . '/datasets';

    $context = stream_context_create([
        "http" => [
            "header" => "Authorization: Bearer $accessToken"
        ]
    ]);

    $dataArray = [];
    $jsonData = file_get_contents($egaDatasetsEndpoint, false, $context);
    $dataArray = json_decode($jsonData, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error decoding JSON data: ' . json_last_error_msg());
    }

    $total_count = count($dataArray);
    $total_pages = ceil($total_count / 10);
} catch (Exception $e) {
    throw $e;
}
