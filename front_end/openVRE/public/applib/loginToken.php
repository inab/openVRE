<?php

require __DIR__ . "/../../config/bootstrap.php";

use MuG_Oauth2Provider\MuG_Oauth2Provider;


// Setting auth server
$provider = new MuG_Oauth2Provider(['redirectUri' => $GLOBALS['URL'] . "applib/loginToken.php"]);

// Get auth code. Redirect user to the authorization URL
if (!isset($_GET['code'])) {

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
        $accessTokenO = $provider->getAccessToken('authorization_code', ['code' => $_GET['code']]);
        $jwt          = json_encode($accessTokenO);
        $accessToken  = json_decode($jwt, true);

    } catch (\Exception $e) {  # (IdentityProviderException $e)
        exit("Internal login service error: cannot obtain user access token from authorization code: " . $e->getMessage());
    }

    // Look up user name and other metadata
    try {
        $resourceOwnerO = $provider->getResourceOwner($accessTokenO);
        $resourceOwner  = array_map('trim', $resourceOwnerO->toArray());
    } catch (\Exception $e) {
        exit("Internal login service error: cannot obtain resource owner from user access token from authorization code: " . $e->getMessage());
    }

    // Check received token claims
    if (!isset($resourceOwner['email'])) {
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
    if (isset($resourceOwner['ga4gh_passport_v1'])) {
        $gh4ghPassport = $resourceOwner['ga4gh_passport_v1'];

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
    }

    // Check if user exists.
    $u = getUserById(sanitizeString($resourceOwner['email']));

    // If new user, create or import from anon 
    if (!isset($u) || !$u) {
        // create new user
        $r = createUserFromToken($resourceOwner['email'], $accessToken, $jwt, $resourceOwner, false);
        if (!$r)
            exit('Login error: cannot create local VRE user');
        $u = getUserById(sanitizeString($resourceOwner['email']));
        if (!isset($u))
            exit('Login error: failed to create local VRE user');
    }

    // load user
    $user = loadUserWithToken($resourceOwner, $accessToken, $jwt);

    if ($user) {
        redirect("../home/redirect.php");
    } else {
        redirect($GLOBALS['URL']);
    }
}
