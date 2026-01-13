<?php

/*
 * users.inc.php
 * 
 */

//require_once "classes/User.php";


function getUsersLogger()
{
    static $logger = null;

    if ($logger === null) {
        $logger = LoggerFactory::getLogger('Users interface');
    }

    return $logger;
}


function checkLoggedIn()
{
    if (isset($_SESSION['User']) && isset($_SESSION['User']['_id'])) {
        $user = getUserById($_SESSION['User']['_id']);
    }

    return isset($_SESSION['User']) && ($user['Status'] == UserStatus::Active->value);
}

function checkTermsOfUse()
{
    return isset($_SESSION['User']['terms']) && $_SESSION['User']['terms'] == 1;
}

function checkAdmin()
{
    $user = getUserById($_SESSION['User']['_id']);

    return isset($_SESSION['User']) && ($user['Status'] == UserStatus::Active->value) && (allowedRoles($user['Type'], $GLOBALS['ADMIN']));
}

function checkToolDev()
{
    $user = getUserById($_SESSION['User']['_id']);

    return isset($_SESSION['User']) && ($user['Status'] == UserStatus::Active->value) && (allowedRoles($user['Type'], $GLOBALS['TOOLDEV']) || allowedRoles($user['Type'], $GLOBALS['ADMIN']));
}

// create user - after being authentified by the Auth Server
function createUserFromToken($login, $token, $jwt, $userinfo = array(), $anonID = false)
{
    if (!$anonID) {
        $userAttributes = array(
            "Email"        => $login,
            "JWT"          => $jwt,
            "Type"         => UserType::Registered->value
        );
    } else {
        $userAttributes = getUserById($anonID);
        // overwrite currently logged anon user
        if ($userAttributes) {
            $userAttributes["Email"] = $login;
            $userAttributes["JWT"]   = $jwt;
            $userAttributes["Type"]  = UserType::Registered->value;
        } else {
            $userAttributes = array(
                "Email"        => $login,
                "JWT"          => $jwt,
                "Type"         => UserType::Registered->value
            );
        }
    }

    $_SESSION['userToken'] = $token;
    if (isset($userinfo) && $userinfo) {
        if (isset($userinfo['family_name'])) {
            $userAttributes['Surname'] = $userinfo['family_name'];
        }

        if (isset($userinfo['given_name'])) {
            $userAttributes['Name'] = $userinfo['given_name'];
        }

        if (isset($userinfo['provider'])) {
            $userAttributes['AuthProvider'] = $userinfo['provider'];
        }

        if (isset($userinfo['sub'])) {
            $userAttributes['secretsId'] = $userinfo['sub'];
        }

        $_SESSION['tokenInfo'] = $userinfo;
    }

    $objUser = new User($userAttributes['Email'], $userAttributes['secretsId'], $userAttributes['Surname'], $userAttributes['Name'], "", $userAttributes['Type'], "", "", $userAttributes['AuthProvider'], "", $userAttributes['JWT']);
    if (!$objUser) {
        return false;
    }

    $userArray = (array) $objUser;
    //load user in current session
    $_SESSION['userId'] = $userArray['id']; //OBSOLETE
    $_SESSION['User'] = $userArray;

    // create user directory
    if (!$userArray['dataDir']) {
        // create new workspace
        $dataDirId =  prepUserWorkSpace($userArray['id'], $userArray['activeProject']);
        if (!$dataDirId) {
            $_SESSION['errorData']['Error'][] = "Error creating data dir";

            return false;
        }

        $userArray['dataDir'] = $dataDirId;
        $_SESSION['User']['dataDir'] = $dataDirId;
    }

    // register user in mongo. NOT in ldap, as user exists for a oauth2 provider
    try {
        saveNewUser($userArray);
    } catch (Exception $e) {
        getUsersLogger()->error("Error saving new user into Mongo database");
        getUsersLogger()->error($e->getMessage());
        unset($_SESSION['User']);
    }

    // if not all user metadata mapped from oauth2 provider, ask the user
    if (!$userArray['Name'] || !$userArray['Surname'] || !$userArray['Inst']) {
        redirect($GLOBALS['BASEURL'] . 'user/usrProfile.php');
        exit(0);
    }

    return true;
}


// create anonymous user - without being authentified by the Auth Server
function createUserAnonymous($sampleData)
{
    $userAttributes = array(
        "Email"        => substr(md5(rand()), 0, 25) . "",
        "Type"         => UserType::Guest->value,
        "Name"         => "Guest",
        "Surname"      => "",
        "AuthProvider" => "VRE"
    );

    $objUser = new User($userAttributes['Email'], "", $userAttributes['Surname'], $userAttributes['Name'], "", $userAttributes['Type'], "", "", $userAttributes['AuthProvider'], "", "");
    if (!$objUser) {
        return false;
    }

    $userArray = (array) $objUser;
    $_SESSION['userId'] = $userArray['id'];
    $_SESSION['User']   = $userArray;
    $_SESSION['anonID'] = $userArray['Email'];

    $dataDirId = prepUserWorkSpace($userArray['id'], $userArray['activeProject'], $sampleData);
    $userArray['dataDir'] = $dataDirId;
    $userArray['terms']  =  "1";
    $_SESSION['User']['dataDir'] = $dataDirId;
    $_SESSION['User']['terms'] = "1";

    // register user in mongo. NOT in ldap nor in the oauth2 provider
    try {
        saveNewUser($userArray);
    } catch (Exception $e) {
        getUsersLogger()->error("Error saving new user into Mongo database");
        getUsersLogger()->error($e->getMessage());
        exit('Login error: cannot create anonymous user');
    }
}


function getUserById($id, $options = array())
{
    return $GLOBALS['usersCol']->findOne(["_id" => $id], $options);
}


function getUserByType($type, $options = array())
{
    return $GLOBALS['usersCol']->findOne(["Type" => $type], $options);
}


function getUsersByFilter($filter, $options = array())
{
    return $GLOBALS['usersCol']->find($filter, $options);
}


// load user to SESSION
function setUser($f, $lastLogin = false)
{
    $aux = (array)$f;
    $_SESSION['User']   = $aux;
    $_SESSION['curDir'] = $_SESSION['User']['id'];

    if (!isset($_SESSION['lastUserLogin']) && $lastLogin) $_SESSION['lastUserLogin'] = $lastLogin;
}


//delete user data from Mongo and disk
function delUser($id)
{
    $homePath =  $id;
    $homeId = getGSFileId_fromPath($homePath, 1);
    if (is_null($homeId)) {
        getUsersLogger()->error("Cannot delete directory from database.");
        throw new NotFoundException("Cannot delete directory from database. Path $homePath not found.");
    }

    deleteGSDirBNS($homeId, 1);

    $rfn =  $GLOBALS['dataDir'] . "/" . $homePath;
    if (is_dir($rfn)) {
        exec("rm -r \"$rfn\" 2>&1", $output);
    }

    $GLOBALS['usersCol']->deleteOne(array('id' => $id));
}


function logoutUser()
{
    session_unset();
}

function logoutAnon()
{
    unset($_SESSION['User']);
    unset($_SESSION['userToken']);
    unset($_SESSION['userInfo']);
}

function saveNewUser($user)
{
    return $GLOBALS['usersCol']->insertOne($user);
}

function updateUser($user)
{
    $GLOBALS['usersCol']->updateOne(array('_id' => $user['_id']), array('$set' => $user), array('upsert=>true'));
}


// update attribute user document in Mongo

function modifyUser($login, $attribute, $value)
{
    $GLOBALS['usersCol']->updateOne(
        array('_id'   => $login),
        array('$set'  => array($attribute => $value)),
        array('upsert' => true)
    );
}


function loadUser($login, $pass)
{
    // check user exists
    $user = getUserById($login);
    if (empty($user['_id']) || $user['Status'] == UserStatus::Inactive->value) {
        getUsersLogger()->error("Requested user (_id = $login) not found. Cannot load user.");
        throw new NotFoundException("Requested user (_id = $login) not found. Cannot load user.");
    }

    // check pass/token verifies - except when loading an ANON or when impersonating
    $pass_verified =  check_password($pass, null);
    $impersonating =  isset($_SESSION['User']) && $_SESSION['User']['Type'] == UserType::Admin->value && $pass == 99;
    $loadingAnon   =  $user['Type'] == UserType::Guest;

    if (!$pass_verified) {
        if (!$loadingAnon  && !$impersonating) {
            // keep open SESSION
            $user['lastReload'] = moment();
            updateUser($user);
            setUser($user);
            return;
        } elseif ($impersonating) {
            getUsersLogger()->info("User $login successfully impersonated");
        }
    }

    // edit user to load
    $auxlastlog = $user['lastLogin'];
    $user['lastLogin'] = moment();
    updateUser($user);

    // load user into SESSION
    setUser($user, $auxlastlog);

    return $user;
}

function loadUserWithToken($userinfo, $token, $jwt)
{
    $user = getUserById($userinfo['email']);
    if (!$user['_id'] || $user['Status'] == UserStatus::Inactive->value) {
        return false;
    }

    $auxlastlog = $user['lastLogin'];
    $user['lastLogin'] = moment();
    $_SESSION['userToken'] = $token;
    $_SESSION['tokenInfo'] = $userinfo;

    updateUser($user);
    setUser($user, $auxlastlog);

    $_SESSION['userVaultInfo'] = array(
        "jwt"          => $jwt ??  "",
        "vaultKey"     => null,
        "secretPath"   => $GLOBALS['secretPath'] ?? '',
        "vaultRolename" => $GLOBALS['vaultRolename'] ?? '',
        "vaultUrl"     => $GLOBALS['vaultUrl'] ?? ''
    );

    return $user;
}

function allowedRoles($role, $allowed)
{

    if (in_array($role, $allowed)) {
        return true;
    } else {
        return false;
    }
}

function getUser_diskQuota($login)
{
    $r = $GLOBALS['usersCol']->findOne(array(
        '_id'  => $login,
        'diskQuota' => array('$exists' => true)
    ));

    return $r['diskQuota'] ?? false;
}

function saveUserJobs($login, $jobInfo)
{
    $GLOBALS['usersCol']->updateOne(
        array('_id' => $login),
        array('$set'   => array('lastjobs' => $jobInfo)),
        array('upsert' => true)
    );
}

function delUserJob($login, $pid)
{
    $GLOBALS['usersCol']->updateOne(
        array('_id' => $login),
        array('$unset' => array("lastjobs.$pid" => 1))
    );
}


function addUserJob($login, $data, $pid)
{
    $pid = strval($pid);
    $lastjobs = getUserJobs($login);
    $lastjobs[$pid] = $data;
    $GLOBALS['usersCol']->updateOne(
        array('_id' => $login),
        array('$set'   => array('lastjobs' => $lastjobs)),
        array('upsert' => true)
    );
}


function getUserJobs($login)
{
    $userLastJobs = $GLOBALS['usersCol']->findOne(array(
        '_id'  => $login,
        'lastjobs' => array('$exists' => true)
    ));

    return $userLastJobs['lastjobs'] ?? [];
}

function getAllUserJobs()
{
    $r = $GLOBALS['usersCol']->find(
        array(
            '$nor' => array(
                array('lastjobs' => array('$exists' => false)),
                array('lastjobs' => array('$size' => 0)),
            )
        ),
        array("_id" => 1, "lastjobs" => 1, "id" => 1)
    );

    if (empty($r))
        return array();

    $r_arr = iterator_to_array($r);
    // return [login] => array(jobId_1 => job1, jobId_2 => job2)
    $result = array();
    foreach ($r_arr as $login => $info) {
        $result[$login] = $info["lastjobs"];
        foreach ($info["lastjobs"] as $job_id => $job) {
            $result[$login][$job_id]["userId"] = $info["id"];
        }
    }
    return $result;
}

function getUserJobPid($login, $pid)
{
    $r = $GLOBALS['usersCol']->findOne(array(
        "_id"      => $login,
        "lastjobs.$pid" => array('$exists' => true)
    ));

    return $r['lastjobs'] ?? array();
}
