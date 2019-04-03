<?php

//
// start or resume PHP session restarting timeout

// set session timeout
$timeout = (isset($GLOBALS['TIMEOUT'])?$GLOBALS['TIMEOUT']:3600); 

// ..for server storing session data
ini_set('session.gc_maxlifetime', $timeout);

// ..for client remembering session id
session_set_cookie_params($timeout);

// start or resume session
session_start();

$_SESSION['VREtimeout'] = time();

?>
