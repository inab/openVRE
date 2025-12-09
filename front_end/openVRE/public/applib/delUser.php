<?php

require __DIR__ . "/../../config/bootstrap.php";


if ($_REQUEST) {
	$u = getUserById(sanitizeString($_REQUEST["id"]));

	if (is_null($u)) {
		$_SESSION['errorData']['Error'][] = "You are trying to remove a non existing user.";
		redirect($GLOBALS['URL'] . 'admin/adminUsers.php');
	}

	//check current user privilegies # TODO
	if ($u['Type'] == UserType::Admin->value) { {
			$_SESSION['errorData']['Error'][] = "You are trying to remove an admin user.";
			redirect($GLOBALS['URL'] . 'admin/adminUsers.php');
		}

		delUser($_REQUEST["id"]);
		redirect($GLOBALS['URL'] . '/admin/adminUsers.php');
	} else {
		redirect($GLOBALS['URL']);
	}
}
