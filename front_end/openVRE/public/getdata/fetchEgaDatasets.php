<?php

require __DIR__ . "/../../config/globals.inc.php";


use OpenVRE\LoggerFactory;
use OpenVRE\VaultClientFactory;


$logger = LoggerFactory::getLogger('Fetch EGA datasets interface');

$currentPage = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($currentPage - 1) * 10;

$userEmail = $_SESSION['User']['Email'];
$vaultToken = $_SESSION['userVaultInfo']['vaultKey'];
$vaultAddress = $GLOBALS['vaultUrl'] . "/" . $GLOBALS['secretPath'] . $_SESSION['User']['secretsId'] . '/EGA';

$vaultClient = VaultClientFactory::create();
$data = $vaultClient->retrieveDatafromVault('EGA');

$egaUsername = $data['username'] ?? null;
$egaPassword = $data['password'] ?? null;

if ($egaUsername === null || $egaPassword === null) {
    $logger->error('EGA credentials not found in Vault.');
    throw new UnexpectedValueException('EGA credentials not found. Try to link your EGA account again.');
}

$logger->info('EGA credentials loaded from Vault.');

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
    throw new UnexpectedValueException('cURL error: ' . curl_error($ch));
}

$tokenDataArray = json_decode($jsonData, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    throw new UnexpectedValueException('Error decoding JSON data: ' . json_last_error_msg());
}

$accessToken = $tokenDataArray['access_token'] ?? null;

if ($accessToken === null) {
    $logger->error('Error fetching EGA token.');
    throw new UnexpectedValueException('Error fetching EGA token.');
}

$logger->info('EGA token fetched.');

function fetchDatasetFiles($logger, $dataset_id, $accessToken, $offset = 0, $limit = 10)
{
    $egaDatasetFilesEndpoint = $GLOBALS['EGA_METADATA_API'] . '/datasets/' . $dataset_id . '/files?offset=' . $offset . '&limit=' . $limit;
    $context = stream_context_create([
        "http" => [
            "header" => "Authorization: Bearer $accessToken"
        ]
    ]);

    $jsonData = file_get_contents($egaDatasetFilesEndpoint, false, $context);
    if ($jsonData === false) {
        $logger->error('Error fetching files for dataset ' . $dataset_id . '.');
        throw new UnexpectedValueException('Error fetching files for dataset ' . $dataset_id . '.');
    }

    return json_decode($jsonData, true);
}

// Check if we're fetching files for a specific dataset
if (isset($_GET['action']) && $_GET['action'] === 'fetch_files') {
    $accession_id = htmlspecialchars($_GET['accession_id']);
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    $files = fetchDatasetFiles($logger, $accession_id, $accessToken, $offset, $limit);
    $logger->info('Fetched files for dataset ' . $accession_id);
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
    throw new UnexpectedValueException('Error decoding JSON data: ' . json_last_error_msg());
}

$total_count = count($dataArray);
$total_pages = ceil($total_count / 10);
