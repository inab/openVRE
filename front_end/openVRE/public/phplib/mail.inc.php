<?php

//require_once('classes/class.smtp.php');
//require_once('classes/class.phpmailer.php');
//require_once('classes/Email.php');

function sendEmail($recipient, $subject, $body, $reply = null, $bcc = null){

	$confFile = $GLOBALS['mail_credentials'];
	$conf = array();
	if (($F = fopen($confFile, "r")) !== FALSE) {
	    while (($data = fgetcsv($F, 1000, ";")) !== FALSE) {
    		foreach ($data as $a){
               	    $r = explode(":",$a);
                    if (isset($r[1])){array_push($conf,$r[1]);}
	        }
            }
            fclose($F);
    	}   
	
	$mail = new PHPMailer(); // create a new object
	$mail->IsSMTP(); // enable SMTP
	$mail->SMTPDebug = 0; // debugging: 0 = no messages, 1 = errors and messages, 2 = messages only
	$mail->SMTPAuth = true; // authentication enabled
	$mail->SMTPSecure = 'ssl'; // secure transfer enabled REQUIRED for Gmail
	$mail->Host = $conf[2];
	$mail->Port = 465; // or 587
	$mail->IsHTML(true);
	$mail->Username = $conf[0];
	$mail->Password = $conf[1];

	if(!isset($reply)) $reply = $GLOBALS['ADMINMAIL'];

	$mail->AddReplyTo($reply, $GLOBALS['FROMNAME']);
	$mail->SetFrom($reply, $GLOBALS['FROMNAME']);
	$mail->Subject = $subject;
	$mail->Body = $body;
	// ******************
	$mail->AddAddress($recipient);
	// ******************
	//$mail->AddAddress("genis.bayarri@irbbarcelona.org");

	if(isset($bcc)) {
		$mail->addBcc($bcc);
	}

	if(!$mail->Send()) {
		return false;
	} else {
		$f = array("Email" => $recipient);
		$objMail = new Email($f, True);
		$mailObj = (array)$objMail;
		$GLOBALS['logMailCol']->insertOne($mailObj);
		return true;
	}

}

function requestPremiumUser($login, $name, $surname){
	
	$subject = $GLOBALS['NAME']." Request Premium User";
	$message = ' 
	Hello '.utf8_decode($name).' '.utf8_decode($surname).',<br><br>
		
	Your request for a premium user account is being processed. In the meantime, you can use the platform as a '.$GLOBALS['ROLES']['2'].' user.'.'<br><br>

	Thanks for using '.$GLOBALS['NAME'].'.';
	
	sendEmail($login,$subject,$message);

}

function requestNewPassword($login, $name, $surname, $hash){
	
	$subject = $GLOBALS['NAME']." Request new Password";
	$message = ' 
	Hello '.utf8_decode($name).' '.utf8_decode($surname).',<br><br>
		
	To reset your password please follow the link below:'.'<br>

	<a href="'.$GLOBALS['URL'].'user/resetPassword.php?q='.$hash.'">'.$GLOBALS['URL'].'user/resetPassword.php?q='.$hash.'</a><br><br>
			
	Thanks for using '.$GLOBALS['NAME'].'.';

	if(sendEmail($login,$subject,$message)) {
		return "1";
	}else{
		return "2";
	}

}

function answerPremium($login, $name, $surname, $type){

	$subject = $GLOBALS['NAME']." Request Premium User";
	
	if($type == 1){
		$message = ' 
		Hello '.utf8_decode($name).' '.utf8_decode($surname).',<br><br>	
		Your request to be a premium user on the platform has been accepted'.'<br><br>
		Thanks for using BioActive Compounds.';
	}else if($type == 101){
		$message = ' 
		Hello '.utf8_decode($name).' '.utf8_decode($surname).',<br><br>	
		Your request to be a premium user on the platform has been rejected'.'<br><br>
		Thanks for using '.$GLOBALS['NAME'].'.';
	}

	sendEmail($login,$subject,$message);

}

function sendWelcomeToNewUser($login, $name, $surname){
	
	$subject = "Welcome to ".$GLOBALS['NAME']." platform";
	$message = ' 
	Hello '.utf8_decode($name).' '.utf8_decode($surname).',<br><br>
	
	Your new account in the '.$GLOBALS['NAME'].' has been created.<br><br>

	To access to the platform, please click here: <a href="'.$GLOBALS['URL'].'" target="_blank">'.$GLOBALS['URL'].'</a><br><br>
			
	Thanks for using '.$GLOBALS['NAME'].'.';

	sendEmail($login,$subject,$message);

}


function sendPasswordToNewUser($login, $name, $surname, $password){
	
	$subject = $GLOBALS['NAME']." New Account";
	$message = ' 
	Hello '.utf8_decode($name).' '.utf8_decode($surname).',<br><br>
	
	Your new account in the '.$GLOBALS['NAME'].' has been created. You can access with the following data:<br><br>

	<strong>Address:</strong> <a href="'.$GLOBALS['URL'].'">'.$GLOBALS['URL'].'</a><br>
	<strong>Email:</strong> '.$login.'<br>
	<strong>Password:</strong> '.$password.'<br><br>
			
	Thanks for using '.$GLOBALS['NAME'].'.';

	sendEmail($login,$subject,$message);

}


?>
