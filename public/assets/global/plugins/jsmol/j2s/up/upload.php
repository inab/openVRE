<?php
//$ds          = '/';  //1
 
$storeFolder = 'upload/';   //2
 
if (!empty($_FILES)) {
//	echo 'que passa?';     
    $tempFile = $_FILES['file']['tmp_name'];          //3             
      
    //$targetPath = dirname( __FILE__ ) . $ds. $storeFolder . $ds;  //4
     
    $targetFile = dirname( __FILE__ ).'/'. $storeFolder. $_FILES['file']['name'];  //5
 
    move_uploaded_file($tempFile,$targetFile); //6
     
}
?>   
