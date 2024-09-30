<?php
/*
 * 
 */

require __DIR__."/../../config/bootstrap.php";

redirectOutside();

//
// Check operation and input files

if (!isset($_REQUEST['op'])) {
	header("location:../workspace/");
}
if (!isset($_REQUEST['fn']) && !isset($_REQUEST['fnPath']) && !preg_match('/cancelJob/',$_REQUEST['op']) ) {
	$_SESSION['errorData']['Error'][] = "Selected operation ('".$_REQUEST['op']."') requires at least one file. Any file name received.";
	header("location:../workspace/");
}
/*if (is_array($_REQUEST['fn']))
	$_REQUEST['fn']=$_REQUEST['fn'][0];*/
    	
//$fileData = $GLOBALS['filesCol']->findOne(array('_id' => $_REQUEST['fn'], 'owner' => $_SESSION['User']['id']));
//$fileMeta = $GLOBALS['filesMetaCol']->findOne(array('_id' => $_REQUEST['fn']));
$filePath = getAttr_fromGSFileId($_REQUEST['fn'],'path'); 
$rfn      = $GLOBALS['dataDir']."/$filePath";

//
// Process operation


if (isset($_REQUEST['op'])){
  switch ($_REQUEST['op']) {


	case 'deleteAll':
    case 'deleteSure':
        $r = deleteFiles($_REQUEST['fn']);
        break;

	case 'deleteDirOk':

        if (basename($filePath) == "uploads" || basename($filePath) == "repository" ){
			$_SESSION['errorData']['error'][]="Cannot delete structural directory '$filePath'.";
            break;
        }
        $r = deleteGSDirBNS($_REQUEST['fn']);

		if ($r == 0){
			$_SESSION['errorData']['error'][]="Cannot delete directory '$filePath' file from repository";
            break;
        }
        exec ("rm -r \"$rfn\" 2>&1",$output);
		if (error_get_last()){
			$_SESSION['errorData']['error'][]=implode(" ",$output);
        }
		break;

	}
}

