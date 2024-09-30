<?php

namespace MuG_Oauth2Provider;
use League\OAuth2\Client\Provider\GenericProvider;

class MuG_Oauth2Provider extends GenericProvider {

    protected $urlLogout;

    public function __construct(array $options = [], array $collaborators = [])
    {
        // set openID endpoints from global app conf
        if (!isset($options['urlAuthorize']) && $GLOBALS['urlAuthorize'])
             $options['urlAuthorize'] = $GLOBALS['urlAuthorize'];
        if (!isset($options['urlAccessToken']) && $GLOBALS['urlAccessToken'])
             $options['urlAccessToken'] = $GLOBALS['urlAccessToken'];
        if (!isset($options['urlResourceOwnerDetails']) && $GLOBALS['urlResourceOwnerDetails'])
             $options['urlResourceOwnerDetails'] = $GLOBALS['urlResourceOwnerDetails'];
        if (!isset($options['urlLogout']) && $GLOBALS['urlLogout'])
             $options['urlLogout'] = $GLOBALS['urlLogout'];
        
        // set VRE as openID client
        if (!isset($options['clientId']) && !isset($options['clientSecret'])){
	    $confFile = $GLOBALS['auth_credentials'];
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
            if ($conf[0])
                $options['clientId']     = $conf[0];
            if ($conf[1])
                $options['clientSecret'] = $conf[1];
        }

        // add urlLogout property
        if ($options['urlLogout'])
            $this->urlLogout = $options['urlLogout'];

        parent::__construct($options, $collaborators);
    }

    public function logoutSession($refresh_token){

        if (!$refresh_token){
            return true;
        }

        $post_data    = "refresh_token=$refresh_token";
        $headers      = array("Content-Type: application/x-www-form-urlencoded");
        $basic_auth   = array(  "user" => $this->clientId,
                                "pass" => $this->clientSecret
                        );
        #print "CMD: curl -v -X POST -H \"Content-Type: application/x-www-form-urlencoded\" --user $this->clientId:$this->clientSecret --data \"$post_data\" --url ".$this->urlLogout. "</br/>";
        list($resp,$info) =post($post_data,$GLOBALS['urlLogout'],$headers,$basic_auth);

        if ($info['http_code'] == 400){
            if ($resp){
                $err = json_decode($resp,TRUE);
                throw new Exception("Logout client session unauthorized. [".$err['error']."]: ".$err['error_description']);
            }else{
                throw new Exception("Logout client session unauthorized.");
            }
        }
        return true;
    }

}
