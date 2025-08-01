<?php

function getEgaAuthToken($egaUsername, $egaPassword)
{
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

    return $accessToken;
}
