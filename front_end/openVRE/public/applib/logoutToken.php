<?php

require __DIR__."/../../config/bootstrap.php";

use MuG_Oauth2Provider\MuG_Oauth2Provider;

if($_REQUEST){

    // End oauth2 session
    $provider = new MuG_Oauth2Provider(['redirectUri'=> $GLOBALS['URL'] . $_SERVER['PHP_SELF']]);

    try{
        $refresh_token = $_SESSION['User']['Token']['refresh_token'];
        $r = $provider->logoutSession($refresh_token);
    } catch (\Exception $e){
#	print "ERROR LOGOUT"
#	print $e;
#	exit(1);
	redirect($GLOBALS['URL']);
    }
    #print "KC SESSION END!";
    // End php session
    if ($r)
        logoutUser();
    
    echo '1';

}else{
   # echo "REDIRECTING";
   # exit(0);
    redirect($GLOBALS['URL']);
}
