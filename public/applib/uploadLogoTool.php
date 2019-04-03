<?php

require __DIR__."/../../config/bootstrap.php";

//if($_POST){
if(isset($_POST) and $_SERVER['REQUEST_METHOD'] == "POST"){

	$toolid = $_REQUEST["toolid"];
	$result = $GLOBALS['toolsDevMetaCol']->findOne(array("_id" => $toolid));

	$path = $GLOBALS['dataDir']."/".$result["user_id"]."/.dev/".$toolid."/logo/";
	if(!file_exists($path)) mkpath($path);

	$valid_formats = array("png");
	$name = $_FILES['file']['name'];
	$size = $_FILES['file']['size'];
	$tmp_name = $_FILES['file']['tmp_name'];
	$width = getimagesize($tmp_name)[0];
	$height = getimagesize($tmp_name)[1];

	if(strlen($name)) {
		list($txt, $ext) = explode(".", strtolower($name));
		
		if(in_array($ext,$valid_formats)) {
				
			if($size<(1024*1024*3)) { // Image size max 3 MB

				//if($width == 600 && $height == 600) {
				if(1) {

					$actual_image_name = "logo.".$ext;
				
					if(move_uploaded_file($tmp_name, $path.$actual_image_name)) {
						$_SESSION['errorData']['Info'][] = "Logo successfully uploaded.";
						redirect($GLOBALS['BASEURL'].'admin/myNewTools.php');
					} else	{
						$_SESSION['errorData']['Error'][] = "Error uploading files, please try again.";
						redirect($GLOBALS['BASEURL'].'admin/myNewTools.php');
					}

				} else {
					$_SESSION['errorData']['Error'][] = "Image size must be <strong>600x600</strong> pixels.";
					redirect($GLOBALS['BASEURL'].'admin/myNewTools.php');				
				}
			
			} else {
				$_SESSION['errorData']['Error'][] = "The maximum allowed size for the image mus be <strong>3MB</strong>.";
				redirect($GLOBALS['BASEURL'].'admin/myNewTools.php');
			}
		
		} else {
			$_SESSION['errorData']['Error'][] = "Only <strong>PNG</strong> images allowed.";
			redirect($GLOBALS['BASEURL'].'admin/myNewTools.php');
		}
	} else {
		$_SESSION['errorData']['Error'][] = "Incorrect file name, please try again.";
		redirect($GLOBALS['BASEURL'].'admin/myNewTools.php');
	}

}else{
	redirect($GLOBALS['URL']);
}
