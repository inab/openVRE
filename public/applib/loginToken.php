<?php

require __DIR__."/../../config/bootstrap.php";

use MuG_Oauth2Provider\MuG_Oauth2Provider;


// Setting auth server
$provider = new MuG_Oauth2Provider(['redirectUri'=> $GLOBALS['URL'] . $_SERVER['PHP_SELF']]);

// Get auth code. Redirect user to the authorization URL
if (!isset($_GET['code'])) {

    // Fetch the authorization URL from the provider; returns urlAuthorize and generates state
    $authorizationUrl = $provider->getAuthorizationUrl();

    header('Location: ' . $authorizationUrl);
    exit;
	
// Check given state against previously stored one to mitigate CSRF attack
}elseif (empty($_GET['state']) || (isset($_SESSION['oauth2state']) && $_GET['state'] !== $_SESSION['oauth2state'])) {
	
    if (isset($_SESSION['oauth2state'])) {
	    unset($_SESSION['oauth2state']);
    }
    exit('Login error: invalid state. Start login process again, please.');

	
} else {


    // Get an access token using the authorization code grant.
    try {
        $accessTokenO = $provider->getAccessToken('authorization_code', ['code' => $_GET['code']]);
        $accessToken  = json_decode(json_encode($accessTokenO),true);
   
    } catch (\Exception $e) {  # (IdentityProviderException $e)
		exit("Internal login service error: cannot obtain user access token from authorization code: ".$e->getMessage());
	}
        
    // Look up user name and other metadata
    try {
        $resourceOwnerO = $provider->getResourceOwner($accessTokenO);
        $resourceOwner  = array_map('trim', $resourceOwnerO->toArray());

    } catch (\Exception $e) {
		exit("Internal login service error: cannot obtain user access token from authorization code: ".$e->getMessage());
    }
    //print "<br/>USERINFO HAS:<br>\n";
    //var_dump($resourceOwner);
    //exit(0);

    // Check if user exists.
    $u = checkUserLoginExists(sanitizeString($resourceOwner['username']));


    // If new user, create or import from anon 
    if (!isSet($u)){
        // create new user
    	if (1){
        //if (!$_SESSION['anonID']){
            $r = createUserFromToken($resourceOwner['username'],$accessToken,$resourceOwner,false);
            if (!$r)
                exit('Login error: cannot create local VRE user');
    	    $u = checkUserLoginExists(sanitizeString($resourceOwner['username']));
            if (!isSet($u))
                exit('Login error: failed to create local VRE user');

        // import user from anon    
        }else{
            $r = createUserFromToken($resourceOwner['username'],$accessToken,$resourceOwner,$_SESSION['anonID']);
        }
    }

    // load user
    $user = loadUserWithToken($resourceOwner,$accessToken);


    if($user){
        // remediate resource user, if needed 
        if (!$resourceOwner['vre_id']){
            // inject user['id'] into auth server (keycloak) as 'vre_id' (so APIs will find it in /openid-connect/userinfo endpoint)
            $r = injectMugIdToKeycloak($resourceOwner['username'],$user['id']);
            if (!$r)
                $_SESSION['errorData']['Error'][] = "Central authorization Server has no 'vre_id' for '".$resourceOwner['username'];
        }
        redirect("../home/redirect.php");
    }else{
	redirect($GLOBALS['URL']);
	}
}
