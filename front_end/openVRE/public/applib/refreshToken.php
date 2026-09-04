<?php

require __DIR__."/../../config/bootstrap.php";

$referer = $_SERVER['HTTP_REFERER'] ?? '/';
if (strpos($referer, 'refreshToken.php') !== false) {
    $referer = $_SESSION['lastSafePage'] ?? '/';
}

$force = isset($_REQUEST['force']);

$r = refresh_token($force);
if (!$r) {
    $_SESSION['errorData']['Error'][] = "An error occurred while refreshing access token. Sorry, try it again.";
    // don't propagate error_code params forward — strip query string before redirecting
    $referer = strtok($referer, '?') . '#tab_1_4';
}

redirect($referer);
