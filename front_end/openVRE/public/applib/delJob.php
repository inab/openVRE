<?php

require __DIR__ . "/../../config/bootstrap.php";

redirectAdminOutside();


if ($_REQUEST["pid"]) {
    delJob($_REQUEST["pid"], null, $_REQUEST["user"]);
    redirect($GLOBALS['URL'] . '/admin/adminJobs.php');
} else {
    redirect($GLOBALS['URL']);
}
