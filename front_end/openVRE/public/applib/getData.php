<?php

require __DIR__ . "/../../config/bootstrap.php";

redirectOutside();

if (! $_REQUEST['uploadType']) {
	$_SESSION['errorData']['getData'][] = "Please specify a source data";
	die(0);
	//redirect($GLOBALS['BASEURL']."/workspace/"); # Bug fix for: TOO LONG REQUEST
}

switch ($_REQUEST['uploadType']) {
	case 'file':
		header("Connection: close");
		getData_fromLocal();
		break;

	case 'url':
		getData_fromUrl($_REQUEST['url']);
		break;

	case 'txt':
		echo getData_fromTXT();
		break;
	case 'repository':
		$url = $_REQUEST['url'];
		$datatype = $_REQUEST['data_type'] ?? "";
		$filetype = $_REQUEST['filetype'] ?? "";
		$descrip = $_REQUEST['description'] ?? "";
		getData_fromRepository($url, $datatype, $filetype, $descrip);
		break;
	case 'sampleData':
		getData_fromSampleData($_REQUEST);
		break;

	case 'EGA':
		$datasetIds = $_REQUEST['datasetIds'];
		$fileIds = $_REQUEST['fileIds'];
		$filenames = $_REQUEST['displayNames'];
		$fileSizes = $_REQUEST['fileSizes'];
		getData_fromEGA($datasetIds, $fileIds, $filenames, $fileSizes);
		break;

	default:
		die(0);
}
