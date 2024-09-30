<?php

/*
 * users.inc.php
 * 
 */

function check_password($password, $hash){
    if ($hash == ''){
        return FALSE;
    }
 
    if (substr($hash,0,7) == '{crypt}'){
        if (crypt($password, substr($hash,7)) == substr($hash,7))
            return TRUE;
            return FALSE;

    }elseif (substr($hash,0,4) == '$2y$'){
        if (password_verify($password,$hash))
            return TRUE;
            return FALSE;

    }elseif (substr($hash,0,5) == '{MD5}'){
        $encrypted_password = '{MD5}' . base64_encode(md5( $password,TRUE));
    
    }elseif (substr($hash,0,6) == '{SHA1}'){
        $encrypted_password = '{SHA}' . base64_encode(sha1( $password, TRUE ));

    }elseif (substr($hash,0,6) == '{SSHA}'){
        $salt = substr(base64_decode(substr($hash,6)),20);
        $encrypted_password = '{SSHA}' . base64_encode(sha1( $password.$salt, TRUE ). $salt);

    }else {
        $_SESSION['ErrorData']['Error'][] = "Unsupported password hash format ".substr($hash,0,9)."...";
        return FALSE;
    }
 
    if ($hash == $encrypted_password)
        return TRUE;
        return FALSE;
}

function fromMongoToLdap($UserMongo){

    $info['objectclass'][0] = "inetOrgPerson";
    $info['objectclass'][1] = "posixAccount";
    $info['objectclass'][2] = "shadowAccount";

    $info['cn']           = $UserMongo['_id'];
    $info['uid']          = $UserMongo['_id'];
    $info['mail']         = $UserMongo['Email'];
    $info['userPassword'] = $UserMongo['crypPassword'];

    if ($UserMongo['Name'])
        $info['givenName'] = $UserMongo['Name'];
    if ($UserMongo['Surname'])
        $info['sn']= $UserMongo['Name'];
    if ($UserMongo['Country']){
        $countries = array();
        foreach (array_values(iterator_to_array($GLOBALS['countriesCol']->find(array(),array('country'=>1)))) as $v)
        	$countries[$v['_id']] = $v['country'];
        $info['l'] = $countries[$UserMongo['Country']];
    }

    $info['uidNumber']    = getLastUidNumber()+1;
    $info['gidNumber']    = '8901'; // mug posixGroup 
    $info['loginShell' ]  = '/bin/bash';
    $info['homeDirectory']= "/home/".$UserMongo['id'];
    $info['description']  = 'MuG user created from VRE';
/*  
    $info['shadowExpire']='-1';
    $info['shadowFlag']='0';
    $info['shadowWarning']='7';
    $info['shadowMin']='8';
    $info['shadowMax']='999999';
 */
    return $info;
}

function saveNewUser_ldap($mongoObj){

    //create ldap record
    $ldif = fromMongoToLdap($mongoObj);

    //add entry
    $r  = ldap_add($GLOBALS['ldap'],"uid=".$ldif['uid'].",ou=People,dc=cloud,dc=local", $ldif);
    if (!$r){
        $_SESSION['errorData']['Error'][] = "Cannot create new user. LDAP says: [".ldap_errno($GLOBALS['ldap'])."] ".ldap_error($GLOBALS['ldap']);
        return false;
    }

    //check new entry
    $sr = ldap_search($GLOBALS['ldap'],$GLOBALS['ldap_dn'],"cn=".$ldif['cn']);
    $info = ldap_get_entries($GLOBALS['ldap'],$sr);
    
    if ($info[0]["dn"])
        return true;
        return false;
}

// search entry
function checkUserLoginExists_ldap($login) {

    $sr = ldap_search($GLOBALS['ldap'],$GLOBALS['ldap_dn'],"uid=".$login);
    $info = ldap_get_entries($GLOBALS['ldap'],$sr);
    
    if ($info[0]["dn"])
        return true;
        return false;
}

// delete entry
function delUser_ldap($login) {
  if (checkUserLoginExists_ldap($login)){
    $r = ldap_delete($GLOBALS['ldap'],"uid=".$login.",".$GLOBALS['ldap_dn']);
    if (!$r){
        $_SESSION['errorData']['Error'][] = "Cannot delete user. LDAP says: [".ldap_errno($GLOBALS['ldap'])."] ".ldap_error($GLOBALS['ldap']);
        return false;
    }
  }    
  return true;
}


function getLastUidNumber(){

    $s = ldap_search($GLOBALS['ldap'], $GLOBALS['ldap_dn'], 'uidNumber=*');
    if ($s){
        ldap_sort($GLOBALS['ldap'], $s, "uidNumber");
        $result  = ldap_get_entries($GLOBALS['ldap'], $s);
        $count   = $result['count'];
        $big_uid = $result[$count-1]['uidnumber'][0];
        return $big_uid;
    }else{
        return 9998;
    }
}
