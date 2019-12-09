<?php

/*
 * users.inc.php
 * 
 */

//require_once "classes/User.php";


function checkLoggedIn(){

	if (isset($_SESSION['User']) && isset($_SESSION['User']['_id']))
		$user = $GLOBALS['usersCol']->findOne(array('_id' => $_SESSION['User']['_id']));
	
	if(isset($_SESSION['User']) && ($user['Status'] == 1)) return true;
	else return false;
}

function checkTermsOfUse() {

	if(isset($_SESSION['User']['terms']) and $_SESSION['User']['terms']== 1) return true;
	else return false;
}

function checkAdmin() {

	$user = $GLOBALS['usersCol']->findOne(array('_id' => $_SESSION['User']['_id']));

	if(isset($_SESSION['User']) && ($user['Status'] == 1) && (allowedRoles($user['Type'], $GLOBALS['ADMIN']))) return true;
	else return false;
}

function checkToolDev() {

	$user = $GLOBALS['usersCol']->findOne(array('_id' => $_SESSION['User']['_id']));

	if(isset($_SESSION['User']) && ($user['Status'] == 1) && (allowedRoles($user['Type'], $GLOBALS['TOOLDEV']) || allowedRoles($user['Type'], $GLOBALS['ADMIN']))) return true;
	else return false;
}

// create user - from sign up
function createUser(&$f) {

    // create full user object
   	$objUser = new User($f, True);
	$aux = (array)$objUser;

    //load user in current session
    $_SESSION['userId'] = $aux['id']; //OBSOLETE
	$_SESSION['User']   = $aux;
	unset($_SESSION['crypPassword']);

    // create user directory 
	$dataDirId =  prepUserWorkSpace($aux['id'],$aux['activeProject']);
	if (!$dataDirId){
        $_SESSION['errorData']['Error'][]="Error creating data dir";
        echo "Error creating data dir";
        return false;
    }
    $aux['dataDir']     = $dataDirId;
    $aux['AuthProvider']= "ldap-cloud";
    $_SESSION['User']['dataDir'] = $dataDirId;

    // register user into mongo
    $r = saveNewUser($aux);
    if (!$r){
        $_SESSION['errorData']['Error'][]="User creation failed while registering it into the database. Please, manually clean orphan files for ".$aux['id']. "(".$dataDirId.")";
        echo 'Error saving new user into Mongo database';
	    unset($_SESSION['User']);
        return false;
    }

    /*    
    // register user in MuG ldap
    $r = saveNewUser_ldap($aux);
    if (!$r){
        $_SESSION['errorData']['Error'][]="Failed to register ".$userObj['id']." into LDAP";
        $_SESSION['errorData']['Error'][]="User creation failed while registering it to LDAP. Please, <a href=\"applib/delUser.php?id=".$aux['id']."\">DELETE USER</a>";
	    unset($_SESSION['User']);
        return false;
    }
     */

    // send mail
	sendWelcomeToNewUser($aux['_id'], $aux['Name'], $aux['Surname']);
    return true;
}

// create user - after being authentified by the Auth Server
function createUserFromToken($login,$token,$userinfo=array(),$anonID=false){

    // create full user oject
    if (!$anonID){
        $f = array(
            "Email"        => $login,
            "Token"        => $token,
            "Type"         => 2
        );
    }else{
        $f = checkUserLoginExists($anonID);
        // overwrite currently logged anon user
        if ($f){
            $f["Email"] = $login;
            $f["Token"] = $token;
            $f["Type"]  = 2;
        
        }else{
          $f = array(
            "Email"        => $login,
            "Token"        => $token,
            "Type"         => 2
          );
        }
    }
    if (isset($userinfo) && $userinfo){
        if (isset($userinfo['family_name']))
           $f['Surname'] = $userinfo['family_name'];
        if (isset($userinfo['given_name']))
            $f['Name'] = $userinfo['given_name'];
        if (isset($userinfo['provider']))
            $f['AuthProvider'] = $userinfo['provider'];
    	$f['TokenInfo'] = $userinfo;
    }
    $objUser = new User($f, True);
    if (!$objUser)
        return false;
    $aux = (array)$objUser;

    //load user in current session
    $_SESSION['userId'] = $aux['id']; //OBSOLETE
    $_SESSION['User']   = $aux;
    unset($_SESSION['crypPassword']);

    // create user directory
    if (!$aux['dataDir']){
        // create new workspace
        $dataDirId =  prepUserWorkSpace($aux['id'],$aux['activeProject']);
    	if (!$dataDirId){
            $_SESSION['errorData']['Error'][]="Error creating data dir";
            echo "Error creating data dir";
            return false;
        }
        $aux['dataDir']= $dataDirId;
        $_SESSION['User']['dataDir'] = $dataDirId;
    }else{
        // change ownership for re-used  workspace
        $workspace_files = getGSFileIdsFromDir($aux['dataDir'],1);
        foreach ($workspace_files as $fn){
        }
    }
    // register user in mongo. NOT in ldap, as user exists for a oauth2 provider

  	$r = saveNewUser($aux);
    if (!$r){
        $_SESSION['errorData']['Error'][]="User creation failed while registering it into the database. Please, manually clean orphan files for ".$aux['id']. "(".$dataDirId.")";
        echo 'Error saving new user into Mongo database';
	    unset($_SESSION['User']);
        return false;
    }
    if ($anonID){
    	// if replacing anon user, delete old anon from mongo
//    	$GLOBALS['usersCol']->deleteOne(array('_id'=> $anonID));    
    }
    
    //  inject user['id'] into auth server (keycloak) as 'vre_id' (so APIs will find it in /openid-connect/userinfo endpoint)
    $r = injectMugIdToKeycloak($aux['_id'],$aux['id']);

    // if not all user metadata mapped from oauth2 provider, ask the user
    if (!$aux['Name'] || !$aux['Surname'] || !$aux['Inst'] || !$aux['Country']){
        redirect($GLOBALS['BASEURL'].'user/usrProfile.php');
        exit(0);
    }
    return true;
}


// create anonymous user - without being authentified by the Auth Server
function createUserAnonymous($sampleData=""){

    // create full user oject
    
    $f = array(
        "Email"        => substr(md5(rand()),0,25)."",
        "Type"         => 3,
        "Name"         => "Guest",
        "Surname"      => "",
        "AuthProvider" => "VRE"
    );
    $objUser = new User($f, False);
    if (!$objUser)
        return false;
    $aux = (array)$objUser;

    //load user in current session
    $_SESSION['userId'] = $aux['id']; //OBSOLETE
	$_SESSION['User']   = $aux;
	$_SESSION['anonID'] = $aux['Email'];


    // create user directory
    $dataDirId =  prepUserWorkSpace($aux['id'],$aux['activeProject'],$sampleData);
	if (!$dataDirId){
        $_SESSION['errorData']['Error'][]="Error creating data dir";
        echo "Error creating data dir";
        return false;
    }
    $aux['dataDir']= $dataDirId;
    $aux['terms']  =  "1";
    $_SESSION['User']['dataDir'] = $dataDirId;
    $_SESSION['User']['terms'] = "1";


    // register user in mongo. NOT in ldap nor in the oauth2 provider
  	$r = saveNewUser($aux);
    if (!$r){
        $_SESSION['errorData']['Error'][]="User creation failed while registering it into the database. Please, manually clean orphan files for ".$aux['id']. "(".$dataDirId.")";
        echo 'Error saving new user into Mongo database';
	    unset($_SESSION['User']);
        return false;
    }

    return true;
}


// create user - from Admin section
function createUserFromAdmin(&$f) {

    // create full user object
    $objUser = new User($f, True);
    $aux = (array)$objUser;
    $_SESSION['errorData']['Info'][] = "New user data object created. Login = ".$aux['_id']." Password = ".$f['pass1'];

    // create user directory
    $dataDirId =  prepUserWorkSpace($aux['id'],$aux['activeProject'],$f['DataSample'],array(),TRUE,1);
    if (!$dataDirId){
		$_SESSION['errorData']['Error'][] = "Error creating new user directory with '".$aux['id']."'. If needed <a href=\"applib/delUser.php?id=".$aux['id']."\">delete user</a>";
        echo "Error creating data dir";
        return false;
    }
    $_SESSION['errorData']['Info'][] = "New workspace created at '".$aux['id']."' (id=$dataDirId).";
    $aux['dataDir']= $dataDirId;
    $aux['AuthProvider']= "ldap-cloud";

    // register user in mongo
    $r = saveNewUser($aux);
    if (!$r){
        $_SESSION['errorData']['Error'][]="User creation failed while registering it into the database. Please, manually clean orphan files for ".$aux['id']. "(".$dataDirId.")";
        echo 'Error saving new user into Mongo database';
    }
    $_SESSION['errorData']['Info'][] = "New user successfuly created";

    /*
    // register user in MuG ldap
    $r = saveNewUser_ldap($aux);
    if (!$r){
        $_SESSION['errorData']['Error'][]="Failed to register ".$userObj['id']." into LDAP";
        $_SESSION['errorData']['Error'][]="User creation failed while registering it to LDAP. Please, <a href=\"applib/delUser.php?id=".$aux['id']."\">DELETE USER</a>";
        return false;
    }*/

    // send mail to user, if selected
	if($f['sendEmail'] == 1) sendPasswordToNewUser($f['Email'], $f['Name'], $f['Surname'], $f['pass1']);
    
    return true;
}


// load user to SESSION
function setUser($f,$lastLogin=FALSE) {
    $aux = (array)$f;
	unset($aux['crypPassword']);
	//unset($aux['lastLogin']);
    $_SESSION['User']   = $aux;
	$_SESSION['curDir'] = $_SESSION['User']['id'];

	if(!isset($_SESSION['lastUserLogin']) && $lastLogin) $_SESSION['lastUserLogin'] = $lastLogin;
}

function delUser($id, $asRoot=1, $force=false){

    //delete data from Mongo and disk
    
    $homePath =  $id;
    $homeId = getGSFileId_fromPath($homePath,$asRoot);
    if (!$homeId){
        $homePath =  "$id/";
        $homeId = getGSFileId_fromPath($homePath,$asRoot);
    }

    if ($homeId){
        $home   = getGSFile_fromId($homeId,"all",$asRoot);
    
        $r = deleteGSDirBNS($homeId,$asRoot,$force);
        if ($r == 0){
	        $_SESSION['errorData']['Error'][]="Cannot delete $homeId directory from database.";
            if (!$force){return 0;}
        }
    }else{
        if (!$force){
	        $_SESSION['errorData']['Error'][]="Cannot delete user. It has no data registered, at least homeDir '$id/' is not found in DB";
		return 0;
	}
    }

    $rfn =  $GLOBALS['dataDir']."/".$homePath;
    if (is_dir($rfn)){
	exec ("rm -r \"$rfn\" 2>&1",$output);
    }

    /*
    //delete user from KC
    $user = $GLOBALS['usersCol']->findOne(array('id' => $id));
    $r = delUser_ldap($user['_id']);
     */

    //delete user from mongo
    $GLOBALS['usersCol']->deleteOne(array('id'=> $id));

    return 1;
}


function injectMugIdToKeycloak($login,$id){

    $kc_token = get_keycloak_admintoken();

    if ($kc_token  && isset($kc_token['access_token'])){
        $kc_user = get_keycloak_user($login,$kc_token['access_token']);
print "\n\n\nKC USER\n";
var_dump($kc_user);
        if ($kc_user && isset($kc_user['id'])){
            $attributes = array();
            if ($kc_user['attributes'])
                $attributes = $kc_user['attributes'];
            $attributes['vre_id'] = array($id);
            $data = array("attributes" => $attributes); 
print "\nPOST DATA\n";
var_dump(json_encode($data));
            $r = update_keycloak_user($kc_user['id'],json_encode($data),$kc_token['access_token']);

            if (!$r){
                $_SESSION['errorData']['Warning'][]="User not valid to be used outside VRE. Could not inject 'vre_id' into Auth Server. Cannot update ".$aux['_id']." in its registry";
                return false;
            }else{
                return true;
            }
        }else{
            $_SESSION['errorData']['Warning'][]="User not valid to be used outside VRE. Could not inject 'vre_id' into Auth Server. Cannot get ".$aux['_id']." from its registry";
            return false;
        }
    }else{
        $_SESSION['errorData']['Warning'][]="User not valid to be used outside VRE. Could not inject 'vre_id' into Auth Server. Token not created";
        return false;
    }
}

function resetPasswordViaKeycloak($login,$id){

    $kc_token = get_keycloak_admintoken();

    if ($kc_token  && isset($kc_token['access_token'])){
        $kc_user = get_keycloak_user($login,$kc_token['access_token']);
        if ($kc_user && isset($kc_user['id'])){

            $r = update_keycloak_userPass($kc_user['id'],$kc_token['access_token']);

            print "AQUI ESTA!!";
            var_dump($r);
            die();

            if (!$r){
                $_SESSION['errorData']['Warning'][]="User not valid to be used outside VRE. Could not inject 'vre_id' into Auth Server. Cannot update ".$aux['_id']." in its registry";
                return false;
            }else{
                return true;
            }
        }else{
            $_SESSION['errorData']['Warning'][]="User not valid to be used outside VRE. Could not inject 'vre_id' into Auth Server. Cannot get ".$aux['_id']." from its registry";
            return false;
        }
    }else{
        $_SESSION['errorData']['Warning'][]="User not valid to be used outside VRE. Could not inject 'vre_id' into Auth Server. Token not created";
        return false;
    }
}
    
    
function logoutUser() {
    session_unset();
}

function logoutAnon() {
    unset($_SESSION['User']);
}

function saveNewUser($userObj) {
    $r = $GLOBALS['usersCol']->insertOne($userObj);
    if (!$r)
        return false;

    return true;
}

// update user document in  Mongo

function updateUser($f) {
    $GLOBALS['usersCol']->updateOne(array('_id' => $f['_id']), array('$set'=>$f), array('upsert=>true'));
}

// update attribute user document in Mongo

function modifyUser($login,$attribute,$value) {
    $GLOBALS['usersCol']->updateOne(array('_id'   => $login ),
                                 array('$set'  => array($attribute => $value)),
                                 array('upsert' => true)
                             );
}

function checkUserIDExists($userId) {
    $user= array();
    if ($userId)
        $user = $GLOBALS['usersCol']->findOne(array('id' => $userId));

    return $user;
}

function checkUserLoginExists($login) {
    $user= array();
    if ($login)
        $user = $GLOBALS['usersCol']->findOne(array('_id' => $login));

    return $user;
}

function loadUser($login, $pass) {

    // check user exists
    $user = $GLOBALS['usersCol']->findOne(array('_id' => $login));
    if (!$user['_id'] || $user['Status'] == 0) {
        $_SESSION['errorData']['Error'][]="Requested user (_id = $login) not found. Cannot load user.";
        return False;
    }
    // check pass/token verifies - except when loading an ANON or when impersonating
    $pass_verified =  check_password($pass, $user['crypPassword']);
    $impersonating =  (isset($_SESSION['User']) && $_SESSION['User']['Type'] == 0 && $pass == 99 ? TRUE : FALSE );
    $loadingAnon   =  ($user['Type'] == 3 ? TRUE : FALSE );

    if (!$pass_verified){
        if (!$loadingAnon  && !$impersonating){
            //$_SESSION['errorData']['Error'][]="Trying to load user without password from SESSION data. Rejected!";
            // keep open SESSION
            $user['lastReload'] = moment();
            updateUser($user);
            setUser($user);
            return False;
        }else{
            if ($impersonating){
                $_SESSION['errorData']['Info'][]="User $login successfully impersonated!";
            }
        }
    }

    // edit user to load
	$auxlastlog = $user['lastLogin'];
    $user['lastLogin'] = moment();
    updateUser($user);

    
    // load user into SESSION 
    setUser($user,$auxlastlog);

    return $user;
}

function loadUserWithToken($userinfo, $token){
    $login = $userinfo['email'];
    $user = $GLOBALS['usersCol']->findOne(array('_id' => $login));

    if (!$user['_id'] || $user['Status'] == 0)
        return False;
    
    $auxlastlog = $user['lastLogin'];
    $user['lastLogin'] = moment();
    $user['Token']     = $token;
    $user['TokenInfo'] = $userinfo;

    updateUser($user);
    setUser($user,$auxlastlog);

    return $user;
}

function allowedRoles($role, $allowed){
	
	if(in_array($role,$allowed)){
		return true;
	}else{
		return false;
	}

}

function getUser_diskQuota($login) {
    $r = $GLOBALS['usersCol']->findOne(array('_id'  => $login,
                                         'diskQuota'=> array('$exists' => true)
                                   ));
    if (isset($r['diskQuota']))
        return $r['diskQuota'];
    else
        return false;
}

function saveUserJobs($login,$jobInfo) {
    $GLOBALS['usersCol']->updateOne(array('_id' => $login),
                                 array('$set'   => array('lastjobs' => $jobInfo)),
                                 array('upsert' => true));
}

function delUserJob($login,$pid) {
    $GLOBALS['usersCol']->updateOne(array('_id' => $login),
                                 array('$unset' => array("lastjobs.$pid" => 1 ))
                                );
                                 //array('$pull' => array("lastjobs" => $pid ))
                                //multi
}

function addUserJob($login,$data,$pid) {

    $pid = strval($pid);
    $lastjobs = getUserJobs($login);

    $lastjobs[$pid] = $data;

    $GLOBALS['usersCol']->updateOne(array('_id' => $login),
                                 //array('$set'  => array("lastjobs.$pid" => $data )),
                                 array('$set'   => array('lastjobs' => $lastjobs)),
                                array('upsert' => true)
                                );
}

function getUserJobs($login) {
    $r = $GLOBALS['usersCol']->findOne(array('_id'  => $login,
                                             'lastjobs'=> array('$exists' => true)
                                            ));
    if (isset($r['lastjobs']))
        return $r['lastjobs'];
    else
        return Array();
}

function getAllUserJobs() {
    $r = $GLOBALS['usersCol']->find(array('$nor' => array(
                                                            array('lastjobs'=> array('$exists' => false)),
                                                            array('lastjobs'=> array('$size' => 0)),
                                                         )
                                    ),
                                    array("_id" => 1, "lastjobs" => 1, "id" => 1)
                                );

    if (empty($r))
        return Array();

    $r_arr = iterator_to_array($r);
    // return [login] => array(jobId_1 => job1, jobId_2 => job2)
    $result = array();
    foreach ($r_arr as $login => $info){
        $result[$login] = $info["lastjobs"];
        foreach ($info["lastjobs"] as $job_id => $job){
            $result[$login][$job_id]["userId"]= $info["id"];
        }
    }
    return $result;

}

function getUserJobPid($login,$pid) {
    $r = $GLOBALS['usersCol']->findOne(array("_id"      => $login,
                                             "lastjobs.$pid"=> array('$exists' => true)
                                            ));
    if (isset($r['lastjobs']))
        return $r['lastjobs'];
    else
        return Array();
}

?>
