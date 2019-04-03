<?php

// set up app settings
require dirname(__FILE__)."/../config/globals.inc.php";

// import vendor libs
require dirname(__FILE__)."/../vendor/autoload.php"; 

// initialize session
require dirname(__FILE__)."/../public/phplib/session.inc";

// import local classes
foreach(glob(dirname(__FILE__)."/../public/phplib/classes/*.php") as $lib){
    require $lib;
}
// import local libs
foreach(glob(dirname(__FILE__)."/../public/phplib/*.php") as $lib){
    require $lib;
}

?>
