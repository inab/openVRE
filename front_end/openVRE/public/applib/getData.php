<?php

require __DIR__."/../../config/bootstrap.php";

redirectOutside();

if(! $_REQUEST['uploadType']){
	$_SESSION['errorData']['getData'][]="Please specify a source data";
	die(0);
	//redirect($GLOBALS['BASEURL']."/workspace/"); # Bug fix for: TOO LONG REQUEST
}
switch ($_REQUEST['uploadType']){
	case 'file':
		header ("Connection: close");
		getData_fromLocal();
		break;
	case 'url':
		$URL = $_REQUEST['url'];
		getData_fromURL($URL);
		break;
	case 'txt':
		getData_fromTXT();
		break;
	case 'id':
		$source = getSourceURL();
		getData_fromURL($source['url'], $source['ext'],"id");
        	break;
	case 'repository':
		getData_fromRepository($_REQUEST);
        	break;
	case 'federated_repository':
		registerData_fromRepository($_REQUEST);
        	break;
	case 'repositoryTest':
		getData_fromRepository_ToPublic($_REQUEST);
        	break;
	case 'sampleData':
		getData_fromSampleData($_REQUEST);
		break;
	case 'eush_demo':
		getData_demo2020();
		break;
	default:
		die(0);
}

?>
