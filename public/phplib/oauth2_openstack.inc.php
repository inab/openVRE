<?php 

function openstack_getAccessToken(){

        if ( !isset($this->cloud['auth']) ){
            $_SESSION['errorData']['Error'][]="No authorization data set. Cannot connect to openstack cloud.";
            return 0;
        }

        // request openstack token via REST
        //
        $auth_data = $this->cloud['auth'];

        $data = array ("auth" => array( "tenantName"          => "'".$auth_data["OS_TENANT_NAME"]."'",
                                        "passwordCredentials" => array ( "username" => "'".$auth_data["OS_USERNAME"]."'",
                                                                         "password" => "'".$auth_data["OS_PASSWORD"]."'")));

        $url = $auth_data["OS_AUTH_URL"]."/tokens";

        $data_string = json_encode($data);

        if (!strlen($data_string)){
            $_SESSION['errorData']['Error'][]="Curl: cannot POST openstack token request. Data to send is empty";
            return 0;
        }
        logger("OS TOKEN request: ".$cmd);

        $cmd = "curl -H \"Content-Type: application/json\" -X POST -d '".json_encode($data)."'  $url";
        subprocess($cmd,$r_str,$stdErr);

        $r = json_decode($r_str, true);

        if (!isset($r['access']["token"])){
            if ($_SESSION['errorData']['Error']){
                $_SESSION['errorData']['Error'] = "Cannot access to the requestes cloud. Authorization failed.";
                logger("ERROR: Cannot access to the requestes cloud. Authorization failed. Response: $r");
            }
            return 0;
        }
        return $r['access']["token"];
    }

function openstack_isTokenExpired($token){
        $token_expires = strtotime($token["expires"]);
        date_default_timezone_set("UTC");
        return $token_expires < time();
    }
