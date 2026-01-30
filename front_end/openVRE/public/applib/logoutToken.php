<?php

require __DIR__ . "/../../config/bootstrap.php";

use OpenVRE\Oauth2Provider;

if ($_REQUEST) {
    // End oauth2 session
    $provider = new Oauth2Provider(['redirectUri' => $GLOBALS['URL'] . $_SERVER['PHP_SELF']]);

    try {
        $refresh_token = $_SESSION['userToken']->getRefreshToken();
        $r = $provider->logoutSession($refresh_token);
    } catch (\Exception $e) {
        redirect($GLOBALS['URL'] . 'home');
    }

    if ($r) {
        logoutUser();
    }

    echo '1';
} else {
    redirect($GLOBALS['URL']);
}
