<?php

require_once __DIR__ . "/../../config/bootstrap.php";

use League\OAuth2\Client\Token\AccessToken;
use OpenVRE\LoggerFactory;
use OpenVRE\Oauth2Provider;


function getLoginLogger()
{
    static $logger = null;

    if ($logger === null) {
        $logger = LoggerFactory::getLogger('Login interface');
    }

    return $logger;
}


if (isset($_SERVER['OIDC_access_token'])) {
    getLoginLogger()->info("Get OIDC claims.");
    $userInfo = [];
    foreach ($_SERVER as $key => $value) {
        if (strpos($key, 'OIDC_CLAIM_') === 0) {
            $claim = substr($key, strlen('OIDC_CLAIM_'));
            $userInfo[$claim] = $value;
        }
    }

    $userToken = [];
    $userToken['access_token'] = $_SERVER['OIDC_access_token'];
    $userToken['expires'] = $_SERVER['OIDC_access_token_expires'];
    $accessToken = new AccessToken($userToken);

    $user = getUserById(sanitizeString($_SERVER['OIDC_CLAIM_email']));
    if (is_null($user)) {
        try {
            $user = createUserFromToken($_SERVER['OIDC_CLAIM_email'], $accessToken, $userInfo, false);
            getLoginLogger()->info("Created new user from user access token.");
        } catch (\Exception $e) {
            exit('Login error: failed to create local VRE user: ' . $e->getMessage());
        }
    }

    $user = loadUserWithToken($user, $userInfo, $accessToken);
    getLoginLogger()->info("Loaded existing user from access token.");

    if ($user) {
        redirect("../home/redirect.php");
    } else {
        redirect($GLOBALS['URL']);
    }
} elseif (!isset($_GET['code'])) {
    $provider = new Oauth2Provider(['redirectUri' => $GLOBALS['URL'] . "applib/loginToken.php"]);
    // Fetch the authorization URL from the provider; returns urlAuthorize and generates state
    $authorizationUrl = $provider->getAuthorizationUrl();

    header('Location: ' . $authorizationUrl);
    exit;

    // Check given state against previously stored one to mitigate CSRF attack
} elseif (empty($_GET['state']) || (isset($_SESSION['oauth2state']) && $_GET['state'] !== $_SESSION['oauth2state'])) {

    if (isset($_SESSION['oauth2state'])) {
        unset($_SESSION['oauth2state']);
    }
    exit('Login error: invalid state. Start login process again, please.');
} else {

    // Get an access token using the authorization code grant.
    try {
        $accessToken = $provider->getAccessToken('authorization_code', ['code' => $_GET['code']]);
        getLoginLogger()->info("Successfully obtained user access token from authorization code.");
    } catch (\Exception $e) {  # (IdentityProviderException $e)
        exit("Internal login service error: cannot obtain user access token from authorization code: " . $e->getMessage());
    }

    // Look up user name and other metadata
    try {
        $resourceOwner = $provider->getResourceOwner($accessToken);
        $userInfo  = array_map('trim', $resourceOwner->toArray());
        getLoginLogger()->info("Successfully obtained resource owner from user access token from authorization code.");
        getLoginLogger()->info(json_encode($userInfo));
    } catch (\Exception $e) {
        exit("Internal login service error: cannot obtain resource owner from user access token from authorization code: " . $e->getMessage());
    }

    // Check received token claims
    if (is_null($userInfo['email'])) {
        $_SESSION['errorData']['Error'][] = "User is authentified, but the claims on the received OIDC token are not correct. At least 'email' attribute is expected.";
        redirect("../home/redirect.php");
    }

    // Check if user exists.
    $user = getUserById(sanitizeString($userInfo['email']));

    // If new user, create or import from anon
    if (is_null($user)) {
        try {
            $user = createUserFromToken($userInfo['email'], $accessToken, $userInfo, false);
            getLoginLogger()->info("Created new user from user access token.");
        } catch (\Exception $e) {
            exit('Login error: failed to create local VRE user: ' . $e->getMessage());
        }
    }

    $user = loadUserWithToken($user, $userInfo, $accessToken);
    getLoginLogger()->info("Loaded existing user from access token.");

    if ($user) {
        redirect("../home/redirect.php");
    } else {
        redirect($GLOBALS['URL']);
    }
}
