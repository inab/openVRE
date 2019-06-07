<?php

require __DIR__."/../../config/bootstrap.php";

redirectOutside();

if(! $_REQUEST['uploadType']){
	$_SESSION['errorData']['getData'][]="Please specify a source data";
	redirect($GLOBALS['BASEURL']."/workspace/");
}

switch ($_REQUEST['uploadType']){
	case 'file':
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
	case 'repositoryTest':
		getData_fromRepository_ToPublic($_REQUEST);
        	break;
	case 'sampleData':
		getData_fromSampleData($_REQUEST);
		break;
	default:
		die(0);
}

?>
