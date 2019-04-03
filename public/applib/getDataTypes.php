<?php

require __DIR__."/../../config/bootstrap.php";

redirectOutside();

if($_POST){

	$dt = getDataTypeFromFileType($_REQUEST['filetype']);

	echo json_encode($dt);

}else{
	redirect($GLOBALS['URL']);
}


