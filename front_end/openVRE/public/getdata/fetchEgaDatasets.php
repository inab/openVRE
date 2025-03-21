<?php

require __DIR__ . "/../../config/globals.inc.php";
require __DIR__ . "/../../public/phplib/session.inc";


try {
    // Determine the current page and offset for datasets
    $currentPage = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    $offset = ($currentPage - 1) * 10;

    // Define the Vault token and address
    $userEmail = $_SESSION['User']['Email'];
    $vaultToken = $_SESSION['userVaultInfo']['vaultKey'];
    $vaultAddress = $GLOBALS['vaultUrl'] . "/" . $GLOBALS['secretPath'] . $userEmail . $GLOBALS['vaultCredentialsSuffix'];

    // Function to fetch data from the Vault
    function fetchVaultData($vaultAddress, $vaultToken)
    {
        // Initialize a cURL session
        $ch = curl_init($vaultAddress);

        // Set cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "X-Vault-Token: $vaultToken"
        ]);

        // Execute the cURL request
        $response = curl_exec($ch);

        // Check for cURL errors
        if (curl_errno($ch)) {
            throw new Exception('cURL error: ' . curl_error($ch));
        }

        // Close the cURL session
        curl_close($ch);

        // Decode the JSON response
        $responseData = json_decode($response, true);

        // Check for JSON decode errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Error decoding JSON data: ' . json_last_error_msg());
        }

        return $responseData;
    }

    // Fetch data from Vault
    $data = fetchVaultData($vaultAddress, $vaultToken);

    // Extract the username and password from the response data
    $egaUsername = $data['data']['data']['EGA']['username'] ?? null;
    $egaPassword = $data['data']['data']['EGA']['password'] ?? null;

    if ($egaUsername === null || $egaPassword === null) {
        throw new Exception('Username or password not found in the response');
    }

    // Parameters to pass in the request body
    $params = [
        'client_id' => 'metadata-api',
        'username' => $egaUsername,
        'password' => $egaPassword,
        'grant_type' => 'password'
    ];

    // Initialize cURL session
    $ch = curl_init($GLOBALS['EGA_METADATA_TOKEN_ENDPOINT']);

    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));

    // Execute cURL session and get JSON data
    $jsonData = curl_exec($ch);

    // Check for cURL errors
    if (curl_errno($ch)) {
        throw new Exception('cURL error: ' . curl_error($ch));
    }

    // Close cURL session
    curl_close($ch);

    // Decode the JSON data
    $tokenDataArray = json_decode($jsonData, true);

    // Check for JSON decode errors
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error decoding JSON data: ' . json_last_error_msg());
    }

    // Extract the access token
    $accessToken = $tokenDataArray['access_token'] ?? null;

    // Check if the access token was successfully retrieved
    if ($accessToken === null) {
        throw new Exception('Error fetching EGA token. Check your credentials and try again.');
    }

    // Function to fetch files for a specific dataset with pagination
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
    // Handle or log the error as needed
    throw $e;
}
