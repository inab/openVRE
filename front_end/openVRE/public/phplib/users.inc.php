<?php

use OpenVRE\LoggerFactory;
use OpenVRE\NotFoundException;
use OpenVRE\Permission;
use OpenVRE\User;
use OpenVRE\UserStatus;
use OpenVRE\UserType;


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

    return isset($_SESSION['User']) && ($user['Status'] == UserStatus::Active->value) && (in_array($user['Type'], $GLOBALS['ADMIN']));
}

function checkToolDev()
{
    $user = getUserById($_SESSION['User']['_id']);

    return isset($_SESSION['User']) && ($user['Status'] == UserStatus::Active->value) && (in_array($user['Type'], $GLOBALS['TOOLDEV']) || in_array($user['Type'], $GLOBALS['ADMIN']));
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


// create user - after being authentified by the Auth Server
function createUserFromToken($login, $token, $userInfo = array(), $anonID = false)
{
    if (!$anonID) {
        $userAttributes = array(
            "Email"        => $login,
            "Type"         => UserType::Registered->value
        );
    } else {
        $userAttributes = getUserById($anonID);
        // overwrite currently logged anon user
        if ($userAttributes) {
            $userAttributes["Email"] = $login;
            $userAttributes["Type"]  = UserType::Registered->value;
        } else {
            $userAttributes = array(
                "Email"        => $login,
                "Type"         => UserType::Registered->value
            );
        }
    }

    $_SESSION['userToken'] = $token;
    if (isset($userInfo) && $userInfo) {
        if (isset($userInfo['family_name'])) {
            $userAttributes['Surname'] = $userInfo['family_name'];
        }

        if (isset($userInfo['given_name'])) {
            $userAttributes['Name'] = $userInfo['given_name'];
        }

        if (isset($userInfo['provider'])) {
            $userAttributes['AuthProvider'] = $userInfo['provider'];
        }

        if (isset($userInfo['sub'])) {
            $userAttributes['secretsId'] = $userInfo['sub'];
        }

        if (isset($userInfo['roles'])) {
            $userAttributes['roles'] = explode(',', $userInfo['roles']);
        }

        $_SESSION['allowedDatasetIds'] = [];
        if (isset($userInfo['ga4gh_passport_v1'])) {
            $gh4ghPassport = $userInfo['ga4gh_passport_v1'];

            foreach ($gh4ghPassport as $gh4ghVisaJwt) {
                $gh4ghVisaTokenParts = explode(".", $gh4ghVisaJwt);
                $gh4ghTokenPayload = base64UrlDecode($gh4ghVisaTokenParts[1]);
                $gh4ghJwtPayload = json_decode($gh4ghTokenPayload);

                if ($gh4ghJwtPayload->ga4gh_visa_v1->type == "ControlledAccessGrants") {
                    array_push($_SESSION['allowedDatasetIds'], $gh4ghJwtPayload->ga4gh_visa_v1->value);
                }
            }
        }

        $_SESSION['tokenInfo'] = $userInfo;
    }

    $objUser = new User($userAttributes['Email'], $userAttributes['secretsId'], $userAttributes['Surname'], $userAttributes['Name'], "", $userAttributes['Type'], "", "", $userAttributes['AuthProvider'], "", $userAttributes['roles']);

    $userArray = $objUser->toDocument();
    //load user in current session
    $_SESSION['userId'] = $userArray['id']; //OBSOLETE
    $_SESSION['User'] = $userArray;

    // create user directory
    if (!$userArray['dataDir']) {
        getUsersLogger()->debug("Creating workspace for user: " . $userArray['id']);
        // create new workspace
        $dataDirId =  prepUserWorkSpace($userArray['id'], $userArray['activeProject']);
        $userArray['dataDir'] = $dataDirId;
        $_SESSION['User']['dataDir'] = $dataDirId;
        getUsersLogger()->info("Workspace created for user: " . $userArray['id']);
    }

    // register user in mongo. NOT in ldap, as user exists for a oauth2 provider
    try {
        getUsersLogger()->debug("Saving new user into Mongo database");
        saveNewUser($userArray);
    } catch (Exception $e) {
        getUsersLogger()->error("Error saving new user into Mongo database");
        unset($_SESSION['User']);
        throw $e;
    }

    // if not all user metadata mapped from oauth2 provider, ask the user
    if (!$userArray['Name'] || !$userArray['Surname'] || !$userArray['Inst']) {
        getUsersLogger()->info("User metadata incomplete, redirecting to profile page");
        redirect($GLOBALS['BASEURL'] . 'user/usrProfile.php');
        exit(0);
    }

    return $userArray;
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
    getUsersLogger()->info("User " . $_SESSION['User']['id'] . " logging out");
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

function loadUserWithToken($user, $userInfo, $token)
{
    if ($user['Status'] == UserStatus::Inactive->value) {
        getUsersLogger()->error("Requested user is inactive. Cannot load user.");
        throw new UnexpectedValueException("Requested user is inactive. Cannot load user.");
    }

    $auxlastlog = $user['lastLogin'];
    $user['lastLogin'] = moment();
    $user['secretsId'] = $userInfo['sub'];
    $user['roles']     = explode(',', $userInfo['roles']);
    $_SESSION['userToken'] = $token;
    $_SESSION['tokenInfo'] = $userInfo;

    $_SESSION['allowedDatasetIds'] = [];
    if (isset($userInfo['ga4gh_passport_v1'])) {
        $gh4ghPassport = $userInfo['ga4gh_passport_v1'];

        foreach ($gh4ghPassport as $gh4ghVisaJwt) {
            $gh4ghVisaTokenParts = explode(".", $gh4ghVisaJwt);
            $gh4ghTokenPayload = base64UrlDecode($gh4ghVisaTokenParts[1]);
            $gh4ghJwtPayload = json_decode($gh4ghTokenPayload);

            if ($gh4ghJwtPayload->ga4gh_visa_v1->type == "ControlledAccessGrants") {
                array_push($_SESSION['allowedDatasetIds'], $gh4ghJwtPayload->ga4gh_visa_v1->value);
            }
        }
    }

    updateUser($user);
    setUser($user, $auxlastlog);

    $_SESSION['userVaultInfo'] = array(
        "vaultKey"     => null,
    );

    return $user;
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
    getUsersLogger()->debug("Updating user $login with job data: " . json_encode($jobInfo));
    $GLOBALS['usersCol']->updateOne(
        array('_id' => $login),
        array('$set'   => array('lastjobs' => $jobInfo)),
        array('upsert' => true)
    );
}

function delUserJob($login, $pid)
{
    getUsersLogger()->debug("Deleting job $pid from user $login");
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
    getUsersLogger()->debug("Adding job $pid to user $login");
    getUsersLogger()->debug("Job data: " . json_encode($data));
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

function hasPermissions(string $userId, Permission $requiredPermission) {
    $userPermissions = getUserPermissions($userId);

    return in_array($requiredPermission->value, $userPermissions);
}
