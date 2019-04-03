<?php
##
## Set of functions to manage the internal keycloak user db
## The db stores users authorized via ouath2 by both, LDAP and external providers
##

function get_keycloak_admintoken(){
     $confFile = $GLOBALS['authAdmin_credentials'];
     $conf = array();
     if (($F = fopen($confFile, "r")) !== FALSE) {
        while (($data = fgetcsv($F, 1000, ";")) !== FALSE) {
    	    foreach ($data as $a){
               $r = explode(":",$a);
               if (isset($r[1])){array_push($conf,$r[1]);}
	    }
        }
        fclose($F);
    }   

    $clientUser   = $conf[0];
    $clientSecret = $conf[1];
    $clientId     = trim($conf[2]);

    $url     = $GLOBALS['authServer']."/realms/master/protocol/openid-connect/token";
    $headers = array("Content-Type: application/x-www-form-urlencoded" );
    $data    = array("username"   => $clientUser,
                     "password"   => $clientSecret,
                     "grant_type" => "password",
                     "client_id"  => $clientId
                 );
    $data = http_build_query($data);

    #curl -X POST 'https://inb.bsc.es/auth/realms/master/protocol/openid-connect/token'  -H "Content-Type: application/x-www-form-urlencoded" -d "username=admin"  -d 'password=XXX'  -d 'grant_type=password'  -d 'client_id=admin-cli'
    list($resp,$info) =post($data,$url,$headers);

    if ($info['http_code'] != 200 && $info['http_code'] != 204){
        if ($resp){
            $err = json_decode($resp,TRUE);
            $_SESSION['errorData']['Warning'][]="Admin access to MuG Auth Server unauthorized. [".$err['error']."]: ".$err['error_description'];
        }else{
            $_SESSION['errorData']['Warning'][]="Admin access to MuG Auth Server unauthorized.";
        }
        return false;
    }

    // parse token result
    return json_decode($resp,TRUE);
}

function get_keycloak_user($username,$token){
        
    $url     =   $GLOBALS['authServer']."/admin/realms/mug/users?username=$username";
    $headers = array("Content-Type: application/json" , "Authorization: Bearer $token");

    #curl -v  -X GET -H "Authorization: Bearer  $token" -H "Accept: application/json" https://inb.bsc.es/auth/admin/realms/mug/users?username=user@mail.com
    list($resp,$info) =get($url,$headers);

    if ($info['http_code'] != 200 && $info['http_code'] != 204){
            if ($resp){
                $err = json_decode($resp,TRUE);
                $_SESSION['errorData']['Warning'][]="Admin access to MuG Auth Server users unauthorized. [".$err['error']."]: ".$err['error_description'];
            }else{
                $_SESSION['errorData']['Warning'][]="Admin access to MuG Auth Server users unauthorized.";
            }
            return false;
    }
    $resp = json_decode($resp,TRUE);
    return $resp[0];
}

function update_keycloak_user($userId,$userData,$token){
    $url     =   $GLOBALS['authServer']."/admin/realms/mug/users/$userId";
    $headers = array("Content-Type: application/json" , "Authorization: Bearer $token");

    #curl -v  -X PUT -H "Authorization: Bearer  $1" -H "Accept: application/json" -H "Content-Type: application/json" -d '{ "attributes": { "vre_id": ["MuGUSER59647a6c60244"] }}' https://inb.bsc.es/auth/admin/realms/mug/users/ad9ced86-38f5-4027-8338-d18a3b08990e    
    
    list($resp,$info) =put($userData,$url,$headers);

    if ($info['http_code'] != 200 && $info['http_code'] != 204){
        if ($resp){
            $err = json_decode($resp,TRUE);
            $_SESSION['errorData']['Warning'][]="Admin access to MuG Auth Server for user update unauthorized. [".$err['error']."]: ".$err['error_description'];
        }else{
           $_SESSION['errorData']['Warning'][]="Admin access to MuG Auth Server for user update unauthorized.";
        }
        return false;
    }
    return true;
}

function update_keycloak_userPass($userId,$token){

    $url     =   $GLOBALS['authServer']."/admin/realms/mug/users/$userId/execute-actions-email";
    $headers = array("Content-Type: application/json" , "Authorization: Bearer $token");

    $data = json_encode(array("UPDATE_PASSWORD"));

    list($resp,$info) =put($data,$url,$headers);

    if ($info['http_code'] != 200 && $info['http_code'] != 204){
        if ($resp){
            $err = json_decode($resp,TRUE);
            $_SESSION['errorData']['Warning'][]="Admin access to MuG Auth Server for user password update unauthorized. [".$err['error']."]: ".$err['error_description'];
        }else{
           $_SESSION['errorData']['Warning'][]="Admin access to MuG Auth Server for user password update unauthorized.";
        }
        return false;
    }
    return true;

}

