<?php

require __DIR__."/../../config/bootstrap.php";

redirectOutside();

if (! $_REQUEST['uploadType']) {
	$_SESSION['errorData']['getData'][] = "Please specify a source data";
	die(0);
	//redirect($GLOBALS['BASEURL']."/workspace/"); # Bug fix for: TOO LONG REQUEST
}

switch ($_REQUEST['uploadType']) {
	case 'file':
		header ("Connection: close");
		getData_fromLocal();
		break;

	case 'url':
		getData_fromUrl($_REQUEST['url']);
		break;

	case 'txt':
		getData_fromTXT();
		break;
	case 'id':
		$source = getSourceURL();
		getData_fromURL($source['url'], $source['ext'],"id");
        	break;
	case 'repository':
		$url = $_REQUEST['url'];
		$datatype = $_REQUEST['data_type'] ?? "";
		$filetype = $_REQUEST['filetype'] ?? "";
		$descrip = $_REQUEST['description'] ?? "";
		$oeb_dataset_id = $_REQUEST['oeb_dataset_id'];
		$oeb_community_ids = $_REQUEST['oeb_community_ids'];
		getData_fromRepository($url, $datatype, $filetype, $descrip, $oeb_dataset_id, $oeb_community_ids);
        break;

	case 'repositoryTest':
		getData_fromRepository_ToPublic($_REQUEST); // TODO: should be removed?
        break;
			
	case 'sampleData':
		getData_fromSampleData($_REQUEST);
		break;

	case 'ega':
		$datasetIds = $_REQUEST['datasetIds'];
		$fileIds = $_REQUEST['fileIds'];
		$filenames = $_REQUEST['displayNames'];
		$fileSizes = $_REQUEST['fileSizes'];
		getData_fromEGA($datasetIds, $fileIds, $filenames, $fileSizes);
		break;

	default:
		die(0);
}
