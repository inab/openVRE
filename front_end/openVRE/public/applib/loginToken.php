<?php

require __DIR__ . "/../../config/bootstrap.php";

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


// Setting auth server
$provider = new Oauth2Provider(['redirectUri' => $GLOBALS['URL'] . "applib/loginToken.php"]);

// Get auth code. Redirect user to the authorization URL
if (!isset($_GET['code'])) {

    // Fetch the authorization URL from the provider; returns urlAuthorize and generates state
    $authorizationUrl = $provider->getAuthorizationUrl();
    getLoginLogger()->info("Redirect user to the authorization URL: " . $authorizationUrl);

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

    function base64UrlDecode($input)
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $input .= str_repeat('=', $padlen);
        }
        return base64_decode(strtr($input, '-_', '+/'));
    }

    $_SESSION['allowedDatasetIds'] = [];
    if (isset($userInfo['ga4gh_passport_v1'])) {
        $gh4ghPassport = $userInfo['ga4gh_passport_v1'];

        foreach ($gh4ghPassport as $gh4ghVisaJwt) {
            $gh4ghVisaTokenParts = explode(".", $gh4ghVisaJwt);
            $gh4ghTokenHeader = base64UrlDecode($gh4ghVisaTokenParts[0]);
            $gh4ghTokenPayload = base64UrlDecode($gh4ghVisaTokenParts[1]);
            $gh4ghJwtHeader = json_decode($gh4ghTokenHeader);
            $gh4ghJwtPayload = json_decode($gh4ghTokenPayload);

            if ($gh4ghJwtPayload->ga4gh_visa_v1->type == "ControlledAccessGrants") {
                array_push($_SESSION['allowedDatasetIds'], $gh4ghJwtPayload->ga4gh_visa_v1->value);
            }
        }

        getLoginLogger()->info("GA4GH passport obtained from user access token and included into user session info.");
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
