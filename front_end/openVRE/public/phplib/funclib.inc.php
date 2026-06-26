<?php

function redirect($url)
{
	header("Location:$url");
	exit;
}

function moment()
{
	return date("Y/m/d*H:i:s");
}


function redirectOutside()
{
	if (checkLoggedIn()) {
		loadUser($_SESSION['User']['_id'], false);
	} else {
		header("Location: /login.php");
	}
	
	if (!checkTermsOfUse()) {
		if (pathinfo($_SERVER['PHP_SELF'])['filename'] != 'usrProfile') {
			redirect($GLOBALS['BASEURL'] . "user/usrProfile.php");
		}
	}
}

function redirectAdminOutside()
{
	if (!checkAdmin()) {
		redirectInside();
	}
}

function redirectToolDevOutside()
{
	if (!checkToolDev()) {
		redirectInside();
	}
}

function redirectInside()
{
	redirect($GLOBALS['BASEURL'] . "/workspace/");
}

function sanitizeString($s)
{
	if (!is_string($s)) {
		return $s;
	}

	return strip_tags(trim((string)$s));
}

function returnHumanDate($q)
{
	$d = explode("*", $q);
	$dma = explode("/", $d[0]);
	$hms = explode(":", $d[1]);

	//return $dma[0]."/".$dma[1]."/".$dma[2]."<div style=\"display:none;\">".$hms[0].":".$hms[1]."</div>";
	return $dma[0] . "/" . $dma[1] . "/" . $dma[2] . " - " . $hms[0] . ":" . $hms[1];
}

function returnHumanDateDashboard($q)
{
	$d = explode("*", $q);
	$dma = explode("/", $d[0]);
	$hms = explode(":", $d[1]);
	return "<span class=\"mt-action-date\">" . $dma[0] . "/" . $dma[1] . "/" . $dma[2] . "</span> <span class=\"mt-action-time\">" . $hms[0] . ":" . $hms[1] . "</span>";
}

function is_multi_array($arr)
{
	rsort($arr);
	return isset($arr[0]) && is_array($arr[0]);
}

function maxlength($in, $length)
{

	return strlen($in) > $length ? substr($in, 0, $length) . "..." : $in;
}

function getSize($bytes)
{

	if ($bytes >= 1073741824) {
		$bytes = (number_format($bytes / 1073741824, 2) + 0) . ' GB';
	} elseif ($bytes >= 1048576) {
		$bytes = (number_format($bytes / 1048576, 2) + 0) . ' MB';
	} elseif ($bytes >= 1024) {
		$bytes = (number_format($bytes / 1024, 2) + 0) . ' KB';
	} elseif ($bytes >= 0) {
		$bytes = ($bytes + 0) . ' B';
	}

	return $bytes;
}

function cleanName($str)
{

	return preg_replace('/[\(\) \/\,\#\@\>\<\$\%\&\!\?\¿\¡\*\'\"\{\}\:\;\º\ª\ç]/', '-', $str);
}

function secondsToTime($seconds)
{
	$dtF = new \DateTime('@0');
	$dtT = new \DateTime("@$seconds");
	return $dtF->diff($dtT)->format('%H:%I:%S');
}

function momentToTime($moment)
{
	if (empty($moment)) {
		return 0;
	}

	$dtime = DateTime::createFromFormat("Y/m/d*H:i:s", $moment);
	return $dtime->getTimestamp();
}
