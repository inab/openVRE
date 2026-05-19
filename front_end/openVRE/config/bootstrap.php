<?php

// import vendor libs
require dirname(__FILE__) . "/../vendor/autoload.php";

use OpenVRE\LoggerFactory;

set_exception_handler(function (Throwable $e) {
    $logger = LoggerFactory::getLogger("Bootstrap interface");
    $logger->error($e->getTraceAsString());
    $logger->error(sprintf(
        "%s in %s:%d",
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    ));

    $_SESSION['errorData']['Error'][] = $e->getMessage();
    redirect($GLOBALS['BASEURL'] . "home/redirect.php");
});

// set up app settings
require dirname(__FILE__) . "/../config/globals.inc.php";

// import vendor libs
require dirname(__FILE__) . "/../vendor/autoload.php";

// import local libs
foreach (glob(dirname(__FILE__) . "/../public/phplib/*.php") as $lib) {
    require $lib;
}

// initialize session
require dirname(__FILE__) . "/../public/phplib/session.inc";
