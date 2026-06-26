<?php
require __DIR__ . "/../config/bootstrap.php";

redirectOutside();

redirect($GLOBALS['BASEURL'] . "home/redirect.php");
