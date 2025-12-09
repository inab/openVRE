<?php

require __DIR__ . "/../../config/bootstrap.php";

redirectOutside();


function getProcessValidationLogger()
{
    static $logger = null;

    if ($logger === null) {
        $logger = LoggerFactory::getLogger('Process validation interface');
    }

    return $logger;
}


$resp = array(
	'filename'  => "",
	'fileId'    => "",
	'msg'       => "",
	'state'     => 0,
);

if (!$_REQUEST['fn']) {
	$resp['msg'] = "Internal error: Select the file to validate.</br>";
	print json_encode($resp);
	die();
} elseif (is_array($_REQUEST['fn'])) {
	$resp['msg'] = "Internal error: A list of files given. Expecting only one.</br>";
	print json_encode($resp);
	die();
}

// user project path
$userPath = getAttr_fromGSFileId($_SESSION['User']['dataDir'], "path");

// file to be processed

$fn     = $_REQUEST['fn'];
$fnFile = getGSFile_fromId($fn);
$fnPath = $fnFile['path'];
$rfn    = $GLOBALS['dataDir'] . "/" . $fnPath;


$resp['fileId']   = $fn;
$resp['filename'] = basename($fnPath);

if (!is_file($rfn) && !$fnFile['uri']) {
	$resp['msg'] = "Error: Cannot find file '" . basename($fnPath) . "' . Not stored in disk anymore.</br>";
	print json_encode($resp);
	die();
}

$fileData = $GLOBALS['filesCol']->findOne(array('_id' => $fn, 'owner' => $_SESSION['User']['id']));
$fileMeta = $GLOBALS['filesMetaCol']->findOne(array('_id' => $file));

if (empty($fileData)) {
	$resp['msg'] = "Error: Cannot validate '" . basename($fnPath) . "'. File do not belong to the user currently logged.</br>";
	print json_encode($resp);
	die();
}

// check obligatory fields && build validation action list ($_SESSION['validation'])
// returns file state && validation/error info
// 0 = ERROR     - $_SESSION['errorData'] is set
// 1 = VALIDATED - $_SESSION['validation'] is empty
// 2 = READY     - $_SESSION['validation'] has pending actions
// 3 = PROCESSING- process has been submitted

unset($_SESSION['errorData']);

// restart validation action list
if (isset($_SESSION['validation'][$fn])) {
	unset($_SESSION['validation'][$fn]);
}

$resp['state'] = 1;

// check compulsory fields
if (is_null($_REQUEST['format'])) {
	$resp['msg'] = "Missing compulsory fields. Please, especify file format.</br>";
	getProcessValidationLogger()->error("Missing compulsory fields. Please, especify file format.");
	echo json_encode($resp);
}

$_SESSION['validation'][$fn]['format'] = $_REQUEST['format'];

$required_metadata = getFeaturesFromDataType($_REQUEST['data_type'], $_REQUEST['format']);

// check validation actions to perfome on file
switch ($_REQUEST['format']) {
	case 'BAM':
		if (is_null($_REQUEST['refGenome']) || is_null($_REQUEST['paired']) || is_null($_REQUEST['sorted'])) {
			$resp['msg'] = "Missing compulsory fields. Please, especify: reference genome, sorted/unsorted && paired/single.</br>";
			$resp['state'] = 0;
			break;
		}
		if (($_REQUEST['sorted'] != "sorted" && $_REQUEST['sorted'] != 1) && (is_null($fileMeta['sorted']) || $fileMeta['sorted'] == "unsorted")) {
			$resp['msg']   = "The BAM file will be sorted && indexed.</br>";
			$_SESSION['validation'][$fn]['action']["sort"] = 0;
			$_SESSION['validation'][$fn]['action']["index"] = 0;
			$resp['state'] = 2;
		} else {
			if (!is_file($rfn . ".bai")) {
				$resp['msg']   = "The BAM file will be indexed.</br>";
				$_SESSION['validation'][$fn]['action']["index"] = 0;
				$resp['state'] = 2;
			} else {
				$resp['msg']   = "BAM file already indexed.</br>";
				$resp['state'] = 1;
			}
		}
		break;

	case 'BEDGRAPH';
	case 'WIG':
	case 'BED':
		if ($required_metadata['assembly'] === true && is_null($_REQUEST['refGenome'])) {
			$resp['msg'] = "Missing compulsory fields. Please, specify reference genome.</br>";
			$resp['state'] = 0;
		}

		break;
	case 'GFF':
	case 'GFF3':
		if ($required_metadata['assembly'] === true && is_null($_REQUEST['refGenome'])) {
			$resp['msg'] = "Missing compulsory fields. Please, specify reference genome.</br>";
			$resp['state'] = 0;
			break;
		}
		break;

	default:
		# other formats accepted as uploaded
		$resp['msg']   = "Metadata file is valid</br>";
		$resp['state'] = 1;
		break;
}

// set file state according to SESSION['errorData'] && SESSION['validation']
if (isset($_SESSION['validation'][$fn]['action'])) {
	$resp['state'] = 2;
}

if (isset($_SESSION['errorData'])) {
	$resp['state'] = 0;
}

// save metadata if file already validated
if ($resp['state'] == 1) {
	try {
		saveMetadataUpload($fn, $_REQUEST, 1);
		$resp['msg'] .= basename($fnPath) . " successfully validated<br/>";
	} catch (Exception $e) {
		$resp['msg'] .= printErrorData();
		$resp['state'] = 0;
	}
}

print json_encode($resp);
