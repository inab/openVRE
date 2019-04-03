<?php

require __DIR__."/../../config/bootstrap.php";

redirectOutside();

if($_POST){

	$dt = getFeaturesFromDataType($_REQUEST['datatype'], $_REQUEST['filetype']);

	echo json_encode($dt);

}else{
	redirect($GLOBALS['URL']);
}


