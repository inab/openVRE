<?php
require __DIR__ . "/../../config/bootstrap.php";


$isLoggedIn = checkLoggedIn();
if ($isLoggedIn) {
    if (!checkAdmin()) {
        $_SESSION['errorData']['Error'][] = "Cannot impersonate a user. Permission denied.";
        die(0);
    }

    // Load requested user
    if ($_REQUEST['id']) {
        $user = loadUser($_REQUEST['id'], 99);
    }
}

redirect("../home/redirect.php");
