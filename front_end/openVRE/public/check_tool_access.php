<?php

require_once __DIR__ . "/../config/globals.inc.php";

if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1') {
    http_response_code(403);
    exit;
}

$project_id = $_GET['project'] ?? '';
$user_email = $_GET['user']    ?? '';

if (!$project_id || !$user_email) {
    http_response_code(400);
    exit;
}


$userDoc = $GLOBALS['usersCol']->findOne(['_id' => $user_email], ['projection' => ['id' => 1]]);
if (is_null($userDoc)) {
    http_response_code(403);
}

$userInternalId = $userDoc['id'];

$projectExists = $GLOBALS['filesCol']->findOne(['owner' => $userInternalId, 'project' => $project_id], ['projection' => ['_id' => 1]]) !== null;

if ($projectExists) {
    http_response_code(200);
} else {
    http_response_code(403);
}
