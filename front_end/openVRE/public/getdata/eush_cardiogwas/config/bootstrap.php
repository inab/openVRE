<?php

// set up app settings
require dirname(__FILE__)."/globals.inc.php";
// import local libs
foreach(glob(dirname(__FILE__)."/../lib/*.php") as $lib){
    require $lib;
}

?>
