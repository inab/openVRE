<?php

set_exception_handler(function (Throwable $e) {
    $_SESSION['errorData']['Error'][] = $e->getMessage();

    http_response_code(500);
    echo "<!DOCTYPE html><html><body>";
    echo "<h1>Application error</h1>";
    echo "<div class='alert alert-danger'>"
        . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
        . "</div>";
    echo "</body></html>";

    exit;
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
