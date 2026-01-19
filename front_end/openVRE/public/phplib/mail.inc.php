<?php

use OpenVRE\Email;
use PHPMailer\PHPMailer\PHPMailer;


function sendEmail($recipient, $subject, $body, $reply = null, $bcc = null)
{
	$mail = new PHPMailer(); // create a new object
	$mail->IsSMTP(); // enable SMTP
	$mail->SMTPDebug = 0; // debugging: 0 = no messages, 1 = errors and messages, 2 = messages only
	$mail->SMTPAuth = true; // authentication enabled
	$mail->SMTPSecure = 'ssl'; // secure transfer enabled REQUIRED for Gmail
	$mail->Host = getenv('MAIL_HOST');
	$mail->Port = 465; // or 587
	$mail->IsHTML(true);
	$mail->Username = getenv('MAIL_USER');
	$mail->Password = getenv('MAIL_PASS');

	if (is_null($reply)) {
		$reply = $GLOBALS['ADMINMAIL'];
	}

	$mail->AddReplyTo($reply, $GLOBALS['FROMNAME']);
	$mail->SetFrom($reply, $GLOBALS['FROMNAME']);
	$mail->Subject = $subject;
	$mail->Body = $body;
	$mail->AddAddress($recipient);

	if (isset($bcc)) {
		$mail->addBcc($bcc);
	}

	if (!$mail->Send()) {
		return false;
	} else {
		$f = array("Email" => $recipient);
		$objMail = new Email($f);
		$mailObj = (array)$objMail;
		$GLOBALS['logMailCol']->insertOne($mailObj);
		return true;
	}
}
