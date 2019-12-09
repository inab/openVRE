<?php

require __DIR__."/../../config/bootstrap.php";

if($_POST){

	$login = $_SESSION['User']['Email'];
	
	$user = $GLOBALS['usersCol']->findOne(array('_id' => $login));

	if ($user['_id']) {
		$newdata = array('$set' => array('Surname' => ucfirst($_POST['Surname']),
						'Name'     => ucfirst($_POST['Name']),
						'Inst'     => $_POST['Inst'],
						'Country'  => $_POST['Country'],
						'terms'    => $_POST['terms']
		));
		$GLOBALS['usersCol']->updateOne(array('_id' => $login), $newdata );

		$_SESSION['User']['Name'] = ucfirst($_POST['Name']);
		$_SESSION['User']['Surname'] = ucfirst($_POST['Surname']);
		$_SESSION['User']['Country'] = $_POST['Country'];
		$_SESSION['User']['Inst'] = $_POST['Inst'];
		$_SESSION['User']['terms'] = $_POST['terms'];
		$_SESSION['lastUserLogin'] = $user['lastLogin'];

		echo '1';
	}else{
		echo '0';
	}

}else{
	redirect($GLOBALS['URL']);
}

?>
