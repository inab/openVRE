<?php

/////////////////////////////////
/////// FROM LOCAL
/////////////////////////////////

// upload file from local

function getData_fromLocal() {

	// set destination working_directory/uploads
	$dataDirPath = getAttr_fromGSFileId($_SESSION['User']['dataDir'],"path");
	$wd          = $dataDirPath."/uploads";
	$wdP         = $GLOBALS['dataDir']."/".$wd;
	$wdId        = getGSFileId_fromPath($wd);
	
	// check source file/s
	if(empty($_FILES)){
		$_SESSION['errorData']['upload'][]="ERROR: Receiving blank. Please select a file to upload";
		die("ERROR: Recieving blank. Please select a file to upload0");
	}

	// check target directory
	if ( $wdId == "0" || !is_dir($wdP) ){
		$_SESSION['errorData']['upload'][]="Target server directory '".basename($wd)."' does not exist. Please, login again.";
		die("Target server directory '".basename($wd)."' does not exist. Please, login again.0");
	}

	$FNs=Array();
	$resp=0;
	// upload each source file	
	for ($i = 0; $i < count($_FILES['file']['tmp_name']); ++$i) {
		$rfnNew = "$wdP/".cleanName($_FILES['file']['name']);
		$size   = $_FILES['file']['size'];

		// check upload errors
		if ($_FILES['file']['error'] ) { 
        		$code = $_FILES['file']['error'];
		    	$errMsg = array(
				0=>"[UPLOAD_ERR_OK]:  There is no error, the file uploaded with success",
				1=>"[UPLOAD_ERR_INI_SIZE]: The uploaded file exceeds the upload_max_filesize directive in php.ini",
				2=>"[UPLOAD_ERR_FORM_SIZE]: The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form",
				3=>"[UPLOAD_ERR_PARTIAL]: The uploaded file was only partially uploaded",
				4=>"[UPLOAD_ERR_NO_FILE]: No file was uploaded",
				6=>"[UPLOAD_ERR_NO_TMP_DIR]: Missing a temporary folder",
				7=>"[UPLOAD_ERR_CANT_WRITE]: Failed to write file to disk",
				8=>"[UPLOAD_ERR_EXTENSION]: File upload stopped by extension"
			);
			if(isset($errMsg[$code])){
				$_SESSION['errorData']['upload'][] = "ERROR [code $code] ".$errMsg[$code];
				die("ERROR [code $code] ".$errMsg[$code]."0");
			}else{
				$_SESSION['errorData']['upload'][] = "Unknown upload error";
				die("Unknown upload error 0");
			}
		}

		// check file size and space
		if (!$size || $size == 0 ){
			$_SESSION['errorData']['upload'][] = "ERROR: ".$_FILES['file']['name']." file size is zero";
			die("ERROR: ".$_FILES['file']['name']." file size is zero 0");
		}
		if ( $size > return_bytes(ini_get('upload_max_filesize')) || $size > return_bytes(ini_get('post_max_size')) ){
			$_SESSION['errorData']['upload'][] = "ERROR: File size $size larger than UPLOAD_MAX_FILESIZE (".ini_get('upload_max_filesize').") ";
			die("ERROR: File size $size larger than UPLOAD_MAX_FILESIZE (".ini_get('upload_max_filesize').") 0");
		}
		$usedDisk     = (int)getUsedDiskSpace();
		$diskLimit    = (int)$_SESSION['User']['diskQuota'];
		if ($size > ($diskLimit-$usedDisk) ) {
			$_SESSION['errorData']['upload'][] = "ERROR: Cannot upload file. Not enough space left in the workspace";
			die("ERROR: Cannot upload file. Not enough space left in the workspace");
		}

		//do not overwrite, rename
		if (is_file($rfnNew)){
			foreach (range(1, 99) as $N) {
				if ($pos = strrpos($rfnNew, '.')) {
					$name = substr($rfnNew, 0, $pos);
					$ext = substr($rfnNew, $pos);
				} else {
					$name = $rfnNew;
				}
				$tmpNew= $name .'_'. $N . $ext;
				if (!is_file($tmpNew)){
					$rfnNew = $tmpNew;
					break;
				}
			}
		}


		//actual upload
		if ( $_FILES['file']['tmp_name'] ){ //  $_FILES['file']['tmp_name'][$i]
			$resp = move_uploaded_file($_FILES['file']['tmp_name'], $rfnNew); // $_FILES['file']['tmp_name'][$i]
		}

		if (is_file($rfnNew)){
			chmod($rfnNew, 0666);
			$fnNew = basename($rfnNew);
			$insertData=array(
				'owner' => $_SESSION['User']['id'],
				'size'  => filesize($rfnNew),
				'mtime' => new MongoDB\BSON\UTCDateTime(filemtime($rfnNew)*1000)
			);
			$metaData=array(
				'validated' => FALSE
			);
		
			$fnId = uploadGSFileBNS("$wd/$fnNew", $rfnNew, $insertData,$metaData,FALSE);

			if ($fnId == "0"){
				$_SESSION['errorData']['upload']="Error occurred while registering the uploaded file";
				die("Error occurred while registering the uploaded file0");
			}
			array_push($FNs,$fnId);

		}else{
			$_SESSION['errorData']['upload'][]="Uploaded file not correctly stored";
			die("Uploaded file not correctly stored0");
		}
	}

	print implode(",",$FNs);
}

/////////////////////////////////
/////// FROM URL or ID
/////////////////////////////////


// upload file from URL via CURL

function getData_fromURL($url, $meta = null, $uploadType="url"){
    if ($uploadType == "id"){
       list($toolArgs,$toolOuts,$output_dir) = prepare_getData_fromURL($url,"uploads",$GLOBALS['BASEURL']."/getdata/dataFromID.php",$meta);
       getData_wget_syncron($toolArgs,$toolOuts,$output_dir,"uploads",$GLOBALS['BASEURL']."/getdata/dataFromID.php"); 
    }else{
       list($toolArgs,$toolOuts,$output_dir) = prepare_getData_fromURL($url,"uploads",$GLOBALS['BASEURL']."/getdata/uploadForm.php#load_from_url",$meta);
       getData_wget_asyncron($toolArgs,$toolOuts,$output_dir,$GLOBALS['BASEURL']."/getdata/uploadForm.php#load_from_url"); 
    }
    echo 1;
}

// build file metadata and URL depending databank

function getSourceURL() {

	$input = $_REQUEST;

	$source = array();

	switch($input["databank"]) {
		case 'pdb':
			$source['url'] = "http://mmb.pcb.ub.es/api/pdb/".$input["idcode"];
                    	// TODO: infer metadata from databank -> $source['ext'] = array( "file_type" => "PDB")
			//$source['ext'] = "pdb";
			$source['ext'] = NULL;
			break;

		default: die(0);
	}
	return $source;
}

// prepare target directory and file metadata

function prepare_getData_fromURL($url,$outdir,$referer,$meta=array()) {

    //parse out username and password from URL, if any
    $user=0;
    $pass=0;
    $url_withCredentials=0;
    if (preg_match('/(.*\/\/)(.*):(.*)@(.*)/',$url,$m)){
        $user = $m[2];
        $pass = $m[3];
        $url_withCredentials = $m[1].urlencode($user).":".urlencode($pass)."@".$m[4];
        $url  = $m[1].$m[4];
    }

    //validate URL: get status and size and filename
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, TRUE);
    curl_setopt($ch, CURLOPT_VERBOSE,TRUE);
    curl_setopt($ch, CURLOPT_NOBODY, TRUE);
    if($user && $pass){
        curl_setopt($ch, CURLOPT_USERPWD, "$user:$pass");
    }
    $curl_data = curl_exec($ch);

    //status
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($status != 200 && !preg_match('/^3/',$status) ){
        $msg = "Resource URL '$url' is not valid or unaccessible. Status: $status";
        if($referer == "die"){die($msg);}else{$_SESSION['errorData']['Error'][] =$msg; redirect($referer);}
    }
    //filename
    $filename="";
    if (preg_match('/^Content-Disposition: .*?filename=(?<f>[^\s]+|\x22[^\x22]+\x22)\x3B?.*$/m', $curl_data,$m)){
        $filename = trim($m['f'],' ";');
    }else{
        $filename = basename($url);
    }
    if (!$filename){
        $msg = "Resource URL ('".$url."') has not a valid HTTP header. Filename not found";
        if($referer == "die"){die($msg);}else{$_SESSION['errorData']['Error'][] =$msg; redirect($referer);}
    }

    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($curl_data, 0, $header_size);

    //size
    $size   = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
    $usedDisk     = (int)getUsedDiskSpace();
    $diskLimit    = (int)$_SESSION['User']['diskQuota'];
    if ( $size == 0 ) {
        $msg = "Resource URL ('".$url."') is pointing to an empty resource (size = 0)";
        if($referer == "die"){die($msg);}else{$_SESSION['errorData']['Error'][] =$msg; redirect($referer);}
    }

    if ($size > ($diskLimit-$usedDisk) ) {
        $msg = "Cannot import file. There will be not enough space left in the workspace (size = ".getSize($size).")";
	if($referer == "die"){
		$_SESSION['errorData']['Error'][] =$msg; 
		redirect($GLOBALS['BASEURL']."workspace/");
	}else{
		$_SESSION['errorData']['Error'][] =$msg; 
		redirect($referer);
	}
    }
    curl_close($ch);

    // setting output directory

    $dataDirPath = getAttr_fromGSFileId($_SESSION['User']['dataDir'],"path");
    $wd          = $dataDirPath."/$outdir";
    $wdP         = $GLOBALS['dataDir']."/".$wd;
    $wdId        = getGSFileId_fromPath($wd);

    if ( $wdId == "0"){
	//creating repository directory. Old users dont have it
	$wdId  = createGSDirBNS($wd,1);
	$_SESSION['errorData']['Info'][] = "Creating  '$outdir' directory: $wd ($wdId)";

	if ($wdId == "0" ){
            $msg = "Cannot create repository directory in $dataDirPath";
            if($referer == "die"){die($msg);}else{$_SESSION['errorData']['Error'][] =$msg; redirect($referer);}
	}
	$r = addMetadataBNS($wdId,Array("expiration" => -1,
				   "description"=> "Remote personal data"));
	if ($r == "0"){
            $msg = "Cannot set '$outdir' directory $wd";
            if($referer == "die"){die($msg);}else{$_SESSION['errorData']['Error'][] =$msg; redirect($referer);}
	}
	if (!is_dir($wdP))
		mkdir($wdP, 0775);
    }
    if ($wdId == "0" || !is_dir($wdP)){
	$msg ="Target server directory '$wd' is not a directory. Your user account is corrupted. Please, report to <a href=\"mailto:helpdesk@multiscalegenomics.eu\">helpdesk@multiscalegenomics.eu</a>";
        if($referer == "die"){die($msg);}else{$_SESSION['errorData']['Error'][] =$msg; redirect($referer);}
    }
    
    // Check file already registered
    $fnP  = "$wdP/$filename";
    $fn   = "$wd/$filename";
    $fnId = getGSFileId_fromPath($fn);

    if ($fnId){
        // file already here
        $_SESSION['errorData']['Error'][]="Resource file ('".$url."') is already available in the workspace: $fnP";

        redirect("../getdata/editFile.php?fn[]=$fnId");
    }else{

        //output_dir will be where fn is expected to be created
        $output_dir = $wdP;
    
        // working_dir will be set in user temporal dir. Checking it
        // TODO Or NO! maybe we decide to run directly on uploads/
        $dirTmp = $GLOBALS['dataDir']."/".$dataDirPath."/".$GLOBALS['tmpUser_dir'];
        if (! is_dir($dirTmp)){
    	   if(!mkdir($dirTmp, 0775, true)) {
    		$_SESSION['errorData']['error'][]="Cannot create temporal file $dirTmp . Please, try it later.";
    		$resp['state']=0;
	    	#break;
    	    }
        }

        // setting tool	arguments
        $toolArgs  = array(
                "url"    => $url,   
                "output" => $fnP);           // Tool is responsible to create outputs in the output_dir
        if ($url_withCredentials){
            $toolArgs["url"] = $url_withCredentials;
        }
        // setting tool outputs -- metadata to save in DMP during tool output_file registration
        $descrip="File imported from URL '$url'";
        $taxon = (isset($meta['taxon'])?$meta['taxon']:"");
        list($fileExtension,$compressed,$fileBaseName) = getFileExtension($fnP); 
        $filetypes = getFileTypeFromExtension($fileExtension);
        $filetype =(isset(array_keys($filetypes)[0])?array_keys($filetypes)[0]:"");

        $fileOut=array("name"  => "file",
       		   "file_path"=> $fnP,
    	    	   "data_type"=> "",
    	    	   "file_type"=> $filetype,
                   "sources"=> [0],
                   "taxon_id" => $taxon,
       		   "meta_data"=> array(
                   "validated"   => false,
                   "compressed"  => $compressed,
      		   "description" => $descrip)
	);
        $toolOuts = Array ("output_files" => Array($fileOut));
        
    }
    return array($toolArgs,$toolOuts,$output_dir);
}



//function getData_wget($url,$outdir,$referer,$meta=array()) {
function  getData_wget_asyncron($toolArgs,$toolOuts,$output_dir,$referer){
 
   // choosing interanl tool 
   $toolId = "wget";	

    // setting tool	inputs
   $toolInputs= array();

   // setting logName
   $fnP = $toolOuts['output_files'][0]["file_path"];
   $logName = basename($fnP). ".log";


   //asyncronous download file (internal tool wget)
   //FIXME START - This is a temporal fix. In future, files should not be downloaded, only registered
   $pid = launchToolInternal($toolId,$toolInputs,$toolArgs,$toolOuts,$output_dir,$logName);
   
   $outdir = basename($output_dir);

   if ($pid == 0){
        $msg ="File imported from URL '".basename($fnP)."' cannot be imported. Error occurred while preparing the job 'Get remote file'";
        if($referer == "die"){die($msg);}else{$_SESSION['errorData']['Error'][] =$msg; redirect($referer);}
    }else{
    	$_SESSION['errorData']['Info'][] ="File from URL '".basename($fnP)."' is being imported into the '$outdir' folder below. Please, edit its metadata once the import has finished";
	redirect($GLOBALS['BASEURL']."/workspace/");
	//header("Location:".$GLOBALS['url']."/workspace/");
    }
        //FIXME END
       
    
       /*
        // save File into DMP
        $insertData=array(
    	'owner' => $_SESSION['User']['id'],
    	'size'  => $size,
    	'mtime' => new new MongoDB\BSON\UTCDateTime(strtotime("now")*1000)
        );
        $descrip=(isset($params['repo'])?"Remote file extracted from ".$params['repo']:"Remote file");
        $metaData=array(
            'validated' => false,
            'description' => $descrip
            );
    
        $fnId = uploadGSFileBNS_fromURL($params['url'],$wd, $insertData,$metaData,0);
    
        
        if ($fnId == "0"){
            $_SESSION['errorData']['Error']="Error occurred while registering the repository file";
        	die("ERROR: Error occurred while registering the repository file.");
        }
        */
}   


function  getData_wget_syncron($toolArgs,$toolOuts,$output_dir,$referer){

   // choosing tool 
   $toolId = "wget";
   $tool = getTool_fromId($toolId,1);
 
   // setting logName
   $fnP = $toolOuts['output_files'][0]["file_path"];
   $logName = basename($fnP). ".log";

   // run tool executable interactively
   if ($tool){
        $executable = $tool['infrastructure']['executable'];
        if (!is_file($executable)){
            $msg ="File from URL '".basename($fnP)."' cannot be imported. Internal error occurred while preparing the job 'Get remote file'";
            if($referer == "die"){die($msg);}else{$_SESSION['errorData']['Error'][] =$msg; redirect($referer);}
        }
        // Add to Cmd: --argument_name value
        $cmd = "$executable ";
        foreach ($toolArgs as $k=>$v){
            $cmd .= " --$k $v";
        }

        subprocess($cmd,$stdout,$stdErr,$output_dir);

        if (!is_file($fnP) || filesize($fnP) == 0){
            $msg ="File from URL '".basename($fnP)."' cannot be imported.  URL is not returning a valid file";
            if($referer == "die"){die($msg);}else{$_SESSION['errorData']['Error'][] =$msg; redirect($referer);}
        }
        chmod($fnP, 0666);
		$fnNew = basename($fnP);

		$insertData=array(
			'owner' => $_SESSION['User']['id'],
			'size'  => filesize($fnP),
			'mtime' => new MongoDB\BSON\UTCDateTime(filemtime($fnP)*1000)
		);

		$metaData=array(
			'validated' => FALSE
        );
        $dataDirPath = getAttr_fromGSFileId($_SESSION['User']['dataDir'],"path");
        $fn          = str_replace($_SESSION['User']['dataDir'],"",$fnP);
        $fnP_parsed = explode("/",$fnP);
        $fn = implode("/",array_slice(explode("/",$fnP),-3,3));
		$fnId = uploadGSFileBNS($fn, $fnP, $insertData,$metaData,FALSE);

		if ($fnId == "0"){
			unlink($fnP);
            $msg ="File from URL '".basename($fnP)."' cannot be imported.  An error occurred while registering the uploaded file";
            if($referer == "die"){die($msg);}else{$_SESSION['errorData']['Error'][] =$msg; redirect($referer);}
        }else{
            echo $fnId;
        }

   }else{
        $msg ="File imported from URL '".basename($fnP)."' cannot be imported. Cannot find tool 'Get remote file'";
        if($referer == "die"){die($msg);}else{$_SESSION['errorData']['Error'][] =$msg; redirect($referer);}
   }

}

/////////////////////////////////
/////// BUILD FILE TEXT
/////////////////////////////////

function getData_fromTXT() {

	$filename = $_REQUEST['filename'];
	$data = 	$_REQUEST['txtdata'];

	// getting working directory
	$dataDirPath = getAttr_fromGSFileId($_SESSION['User']['dataDir'],"path");
	$wd          = $dataDirPath."/uploads";

	$wdP  = $GLOBALS['dataDir']."/".$wd;
	$wdId = getGSFileId_fromPath($wd);

	// check target directory
	if ( $wdId == "0" || !is_dir($wdP) ){
		//$_SESSION['errorData']['upload'][]="Target server directory '".basename($wd)."' does not exist. Please, login again.";
		die("ERROR: Target server directory '".basename($wd)."' does not exist. Please, login again.");
	}

	// getting path and file size
	$rfnNew = "$wdP/".cleanName($filename);
	if(isset($ext)) $rfnNew .= '.'.$ext;
	$size   = strlen($data);

	if($size == 0) {
		//$_SESSION['errorData']['upload'][] = "ERROR: ".$nameFile." file size is zero";
		die("ERROR: ".$nameFile." file size is zero");
	}

	// checking if user disk has run out
	$usedDisk     = (int)getUsedDiskSpace();
	$diskLimit    = (int)$_SESSION['User']['diskQuota'];
			
	if ($size > ($diskLimit-$usedDisk) ) {
		//$_SESSION['errorData']['upload'][] = "ERROR: Cannot upload file. Not enough space left in the workspace";
		die("ERROR: Cannot upload file. Not enough space left in the workspace");
	}

	// checking if file name exists
	if (is_file($rfnNew)){
		foreach (range(1, 99) as $N) {
			if ($pos = strrpos($rfnNew, '.')) {
				$name = substr($rfnNew, 0, $pos);
				$ext = substr($rfnNew, $pos);
			} else {
				$name = $rfnNew;
			}
			$tmpNew= $name .'_'. $N . $ext;
			if (!is_file($tmpNew)){
				$rfnNew = $tmpNew;
				break;
			}
		}
	}
	
	$file = fopen($rfnNew, "w+");

	fputs($file, $data);
	fclose($file);

	if (is_file($rfnNew)){
		chmod($rfnNew, 0666);
		$fnNew = basename($rfnNew);

		$insertData=array(
			'owner' => $_SESSION['User']['id'],
			'size'  => filesize($rfnNew),
			'mtime' => new MongoDB\BSON\UTCDateTime(filemtime($rfnNew)*1000)
		);

		$metaData=array(
			'validated' => FALSE
		);

		$fnId = uploadGSFileBNS("$wd/$fnNew", $rfnNew, $insertData,$metaData,FALSE);

		if ($fnId == "0"){
			unlink($rfnNew);
			//$_SESSION['errorData']['upload']="Error occurred while registering the uploaded file";
			die("ERROR: Error occurred while registering the uploaded file.");
		}

		echo $fnId;

	}else{
		//$_SESSION['errorData']['upload'][]="Uploaded file not correctly stored";
		die("ERROR: Uploaded file not correctly stored.");
	}


}

/////////////////////////////////
/////    DATA FROM REPOSITORY 
/////////////////////////////////

// ONGOING: import data from Resository to Public dir.
// Step 0: if TAR is given, files are unbundled and a directory is registered

function getData_fromRepository_ToPublic($params=array()) { //url, untar, data_type, file_type, other metadata

    // Get params
    $url      = $params['url'];
    $extract_uncompress = (isset($params['extract_uncompress'])? $params['extract_uncompress']:false); // true, false

    $datatype = (isset($params['data_type'])&& $params['data_type']? $params['data_type']:"");
    $filetype = (isset($params['file_type'])&& $params['file_type']? $params['file_type']:"");
    $descrip  = (isset($params['description'])&& $params['description']? $params['description']:"");

    // Get URL headers

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, TRUE);
    $curl_data = curl_exec($ch);

    // Validate HTTP status

    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $url_effective = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

    if ($status != 200 && !preg_match('/^3/',$status) ){
        $_SESSION['errorData']['Error'][] = "Resource URL ('$url') is not valid or unaccessible. Status: $status";
        redirect($_SERVER['HTTP_REFERER']);
    }
    // Get filename from header

    if (! $filename){
    	if (preg_match('/^Content-Disposition: .*?filename=(?<f>[^\s]+|\x22[^\x22]+\x22)\x3B?.*$/m', $curl_data,$m)){
        	$filename = trim($m['f'],' ";');
    	}elseif (preg_match('/^Content-Length:\s*(\d+)/m', $curl_data,$m)){
	        $hasLength = $m[1];
        	if ($hasLength){
	            $filename = basename($url);
	        }
	}
    }
    if (!$filename){
        $_SESSION['errorData']['Error'][] = "Resource URL ('$url') is not pointing to a valid filename";
        redirect($_SERVER['HTTP_REFERER']);
    }

    // Check size and available space

    $size   = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
    $usedDisk     = (int)getUsedDiskSpace();
    $diskLimit    = (int)$_SESSION['User']['diskQuota'];
    if ($size == 0 ) {
        $_SESSION['errorData']['Error'][] = "Resource URL ('$url') is pointing to an empty resource (size = 0)";
        redirect($_SERVER['HTTP_REFERER']);

    }
    if ($size > ($diskLimit-$usedDisk) ) {
        $_SESSION['errorData']['Error'][] = "Cannot import file. There will be not enough space left in the workspace (size = $size)";
        redirect($_SERVER['HTTP_REFERER']);
    }
    curl_close($ch);

    // Check repository from workspace

    $dataDirPath = getAttr_fromGSFileId($_SESSION['User']['dataDir'],"path");
    $wd          = $dataDirPath."/repository";
    $wdP         = $GLOBALS['dataDir']."/".$wd;
    $wdId        = getGSFileId_fromPath($wd);

    if ($wdId == "0" || !is_dir($wdP)){
	$_SESSION['errorData']['Error'][]="Target server directory '$wd' is not a directory. Your user account is corrupted. Please, report to <a href=\"mailto:helpdesk@multiscalegenomics.eu\">helpdesk@multiscalegenomics.eu</a>";
        redirect($_SERVER['HTTP_REFERER']);
    }

    // Set output file/folder

    list($fileExtension,$compressExtension,$fileBaseName) = getFileExtension($filename);
    $compression = ($compressExtension && isset($GLOBALS['compressions'][$compressExtension])? $GLOBALS['compressions'][$compressExtension] : 0);
    $output = ($compression && $extract_uncompress && preg_match('/TAR/',$compression)? hash('md5', $url, false):$filename);

print "($fileExtension,$compressExtension,$fileBaseName) = getFileExtension($filename)\n<br/>";
print "output  (file or folder) = $output\n";
    
    // Check output file/folder already registered

    $fnP  = "$wdP/$output";
    $fn   = "$wd/$output";
    $fnId = getGSFileId_fromPath($fn);

    if ($fnId){
        // file already here
        $_SESSION['errorData']['Error'][]="Resource file ('$url') is already available in the workspace: $fnP";
        redirect("../getdata/editFile.php?fn[]=$fnId");

    //Do asyncronous download file (internal tool wget)

    }else{
        
        //FIXME START - This is a temporal fix. In future, files should not be downloaded, only registered

        //output_dir will be where fn is expeted to be created: repository
        $output_dir = $wdP;
    
        // working_dir will be set in user temporal dir. Checking it
        $dirTmp = $GLOBALS['dataDir']."/".$dataDirPath."/".$GLOBALS['tmpUser_dir'];
        if (! is_dir($dirTmp)){
    	   if(!mkdir($dirTmp, 0775, true)) {
    		$_SESSION['errorData']['error'][]="Cannot create temporal file $dirTmp . Please, try it later.";
    		$resp['state']=0;
	    	#break;
    	    }
        }

        // choosing interanl tool 
        $toolId = "wget";	

        // setting tool	inputs
        $toolInputs= array();

        // setting tool	arguments. Tool is responsible to create outputs in the output_dir
        $toolArgs  = array(
                "url"    => $url,   
                "output" => $fnP
	);
	if ($compression and $extract_uncompress){
		$compressors = explode(",",$compression);
		foreach($compressors as $c){
		    if ($c == "TAR"){
			$toolArgs['archiver']  = $c;
			continue;
		    }else{
			$toolArgs['compressor']  = $c;
			continue;
		    }
		}
//			$toolArgs['uncompress_cmd'] =  " tar -xz -C ";
//			$toolArgs['uncompress_cmd'] =  " tar -xj ";
//			$toolArgs['uncompress_cmd'] =  " gunzip -c ";
//			$toolArgs['uncompress_cmd'] =  " bzip2 ";
	}

        // setting tool output metadata. It will be registerd after tool execution
        if (!$descrip){
		$descrip="Remote file extracted from <a target='_blank' href=\"$url\">$url</a>";
	}
        if (!$filetype){
	        $filetypes = getFileTypeFromExtension($fileExtension);
        	$filetype =(isset(array_keys($filetypes)[0])?array_keys($filetypes)[0]:"");
	}
	$validated = ($filetype != "" && $datatype != ""? true:false); // Can lead to problems

	$fileOut_type = ($compression && $extract_uncompress && preg_match('/TAR/',$compression)? "dir":"file");

        $fileOut=array(
		   "name"       => "file",
		   "type"       => $fileOut_type,
       		   "file_path"  => $fnP,
    	    	   "data_type"  => $datatype,
    	    	   "file_type"  => $filetype,
                   "sources"    => [0],
	           "source_url" => $url,
        	   "meta_data"  => array(
	                   "validated"   => $validated,
        	           "compressed"  => $compressed,
	            	   "description" => $descrip,
			   )
	);
	if (isset($params['oeb_dataset_id'])){ $fileOut['meta_data']["oeb_dataset_id"] = $params['oeb_dataset_id'];}
	if (isset($params['oeb_community_ids'])){ $fileOut['meta_data']["oeb_community_ids"] = $params['oeb_community_ids'];}
        $toolOuts = Array ("output_files" => Array($fileOut));
        
        // setting logName
        $logName = basename($fnP). ".log";
    
        //calling internal tool
        $pid = launchToolInternal($toolId,$toolInputs,$toolArgs,$toolOuts,$output_dir,$logName);

        if ($pid == 0){
            $_SESSION['errorData']['Error'][]="Resource file '".basename($fnP)."' cannot be imported. Error occurred while preparing the job 'Get remote file'";
            redirect($_SERVER['HTTP_REFERER']);
        }else{
            $_SESSION['errorData']['Info'][] ="Remote file '".basename($fnP)."' imported into the 'repository' folder below. Please, edit its metadata once the job has finished";
	    redirect($GLOBALS['BASEURL']."workspace/");
        }
   }
}


function process_URL($url){

    $response = array(
		"status"        => false,
		"size"          => 0,
		"filename"      => false,
		"effective_url" => false);

    // get URL header
    $headers_data = get_headers($url,1);
    var_dump($headers_data);
   
    //check server and status
    if ($headers_data === false){
        $_SESSION['errorData']['Error'][] = "Resource URL ('$url') is not valid or unaccessible. Server not found";
	return false;
    }

    // corrects url when 301/302 redirect(s) lead(s) to 200
    $response['effective_url'] = (isset($headers_data['Location']) && preg_match("/^Location: (.+)$/",$headers_data['Location'],$m)? $m[1] : $url );

    // grabs last code, in case of redirect(s):
    $response['status'] = (preg_match("/^HTTP.* (\d\d\d) /",$headers_data[0],$m)? $m[1] : $response['status']);

    // grabs filename
    $response['filename'] = (isset($headers_data['Content-Disposition']) && preg_match('/filename=(?<f>[^\s]+|\x22[^\x22]+\x22)\x3B?.*$/m',$headers_data['Content-Disposition'],$m)? $m[1] : $response['filename']);
    $response['size'] = (isset($headers_data['Content-Disposition']) && preg_match("/filename=.+/",$headers_data['Content-Disposition']) && $headers_data['Content-Length']? $headers_data['Content-Length'] : $response['size']);
    

    $status = substr($headers[0], 9, 3);
    if (!preg_match('/(200)/',$headers_data[0]) && !preg_match('/^3/',$status) ){
        $_SESSION['errorData']['Error'][] = "Resource URL ('$url') is not valid or unaccessible. Status: $status";
        redirect($_SERVER['HTTP_REFERER']);
    }
    //filename
    /*
    if (! $filename){
    	if (preg_match('/^Content-Disposition: .*?filename=(?<f>[^\s]+|\x22[^\x22]+\x22)\x3B?.*$/m', $curl_data,$m)){
        	$filename = trim($m['f'],' ";');
    	}elseif (preg_match('/^Content-Length:\s*(\d+)/m', $curl_data,$m)){
	        $hasLength = $m[1];
        	if ($hasLength){
	            $filename = basename($url);
	        }
	}
    }

    if (!$filename){
        $_SESSION['errorData']['Error'][] = "Resource URL ('$url') is not pointing to a valid filename";
  0 redirect($_SERVER['HTTP_REFERER']);
    }
    //size
    if ($size == 0 ) {
        $_SESSION['errorData']['Error'][] = "Resource URL ('$url') is pointing to an empty resource (size = 0)";
        redirect($_SERVER['HTTP_REFERER']);

    }
    if ($size > ($diskLimit-$usedDisk) ) {
        $_SESSION['errorData']['Error'][] = "Cannot import file. There will be not enough space left in the workspace (size = $size)";
        redirect($_SERVER['HTTP_REFERER']);
    }*/

    return $response;

}

// import from Repository (URL) to user workspace

function getData_fromRepository($params=array()) { //url, repo, id, taxon, filename, data_type

    // get params
    $url      = $params['url'];
    $datatype = (isset($params['data_type'])&& $params['data_type']? $params['data_type']:"");
    $filetype = (isset($params['file_type'])&& $params['file_type']? $params['file_type']:"");
    $descrip  = (isset($params['description'])&& $params['description']? $params['description']:"");
        
    //validate URL via HEAD
/*
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, TRUE);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    //curl_setopt($ch, CURLOPT_NOBODY, TRUE); // forces method HEAD. Does not work with redirects
    $curl_data = curl_exec($ch);
    if (!$curl_data){
        $_SESSION['errorData']['Error'][] = "Resource URL ('$url') cannot be verified. HEAD not accepted";
        redirect($_SERVER['HTTP_REFERER']);
    }
*/
    $url_data = process_URL($url);

    //status
    //$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $status = $url_data['status'];
    //$url_effective = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $url_effective = $url_data['effective_url'];

    if ($status != 200 && !preg_match('/^3/',$status) ){
        $_SESSION['errorData']['Error'][] = "Resource URL ('$url') is not valid or unaccessible. Status: $status";
        redirect($_SERVER['HTTP_REFERER']);
    }
    //filename
    $filename = $url_data['filename'];
    if (! $filename){
        $_SESSION['errorData']['Error'][] = "Resource URL ('$url') is not pointing to a valid filename";
        redirect($_SERVER['HTTP_REFERER']);
    }

    //size
    //$size      = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
    $size      = (int)$url_data['size'];
    $usedDisk  = (int)getUsedDiskSpace();
    $diskLimit = (int)$_SESSION['User']['diskQuota'];
    if ($size == 0 ) {
        $_SESSION['errorData']['Error'][] = "Resource URL ('$url') is pointing to an empty resource (size = 0)";
        redirect($_SERVER['HTTP_REFERER']);

    }
    if ($size > ($diskLimit-$usedDisk) ) {
        $_SESSION['errorData']['Error'][] = "Cannot import file. There will be not enough space left in the workspace (size = $size)";
        redirect($_SERVER['HTTP_REFERER']);
    }
    //curl_close($ch);

    // setting repository directory

    $dataDirPath = getAttr_fromGSFileId($_SESSION['User']['dataDir'],"path");
    $wd          = $dataDirPath."/repository";
    $wdP         = $GLOBALS['dataDir']."/".$wd;
    $wdId        = getGSFileId_fromPath($wd);

    if ( $wdId == "0"){
	//creating repository directory. Old users dont have it
	$wdId  = createGSDirBNS($wd,1);
	$_SESSION['errorData']['Info'][] = "Creating  repository directory: $wd ($wdId)";

	if ($wdId == "0" ){
            $_SESSION['errorData']['Internal error'][] = "Cannot create repository directory in $dataDirPath";
            redirect($_SERVER['HTTP_REFERER']);
	}
	$r = addMetadataBNS($wdId,Array("expiration" => -1,
				   "description"=> "Remote personal data"));
	if ($r == "0"){
            $_SESSION['errorData']['Internal error'][] = "Cannot set 'repository' directory $wd";
            redirect($_SERVER['HTTP_REFERER']);
	}
	if (!is_dir($wdP))
		mkdir($wdP, 0775);
    }
    if ($wdId == "0" || !is_dir($wdP)){
		$_SESSION['errorData']['Error'][]="Target server directory '$wd' is not a directory. Your user account is corrupted. Please, report to <a href=\"mailto:helpdesk@multiscalegenomics.eu\">helpdesk@multiscalegenomics.eu</a>";
        redirect($_SERVER['HTTP_REFERER']);
    }
    
    // Check file already registered
    $fnP  = "$wdP/$filename";
    $fn   = "$wd/$filename";
    $fnId = getGSFileId_fromPath($fn);

    if ($fnId){
        // file already here
        $_SESSION['errorData']['Error'][]="Resource file ('$url') is already available in the workspace: $fnP";

        redirect("../getdata/editFile.php?fn[]=$fnId");

    }else{
        //asyncronous download file (internal tool wget)
        
        //FIXME START - This is a temporal fix. In future, files should not be downloaded, only registered

        //output_dir will be where fn is expeted to be created: repository
        $output_dir = $wdP;
    
        // working_dir will be set in user temporal dir. Checking it
        // TODO Or NO! maybe we decide to run directly on uploads/
        $dirTmp = $GLOBALS['dataDir']."/".$dataDirPath."/".$GLOBALS['tmpUser_dir'];
        if (! is_dir($dirTmp)){
    	   if(!mkdir($dirTmp, 0775, true)) {
    		$_SESSION['errorData']['error'][]="Cannot create temporal file $dirTmp . Please, try it later.";
    		$resp['state']=0;
	    	#break;
    	    }
        }

        // choosing interanl tool 
        $toolId = "wget";	

        // setting tool	inputs
        $toolInputs= array();

        // setting tool	arguments. Tool is responsible to create outputs in the output_dir
        $toolArgs  = array(
                "url"    => $url,   
                "output" => $fnP); 

        // setting tool outputs. Metadata will be saved in DB during tool output_file registration
        if (!$descrip){
		$descrip="Remote file extracted from <a target='_blank' href=\"$url\">$url</a>";
	}
        if (!$filetype){
		list($fileExtension,$compressed,$fileBaseName) = getFileExtension($fnP); 
	        $filetypes = getFileTypeFromExtension($fileExtension);
        	$filetype =(isset(array_keys($filetypes)[0])?array_keys($filetypes)[0]:"");
	}

        if ($filetype != "" && $datatype != ""){
            $validated = true ; // Can lead to problems
        }else{
            $validated = false;
        }

        $fileOut=array(
		   "name"       => "file",
		   "type"       => "file",
       		   "file_path"  => $fnP,
    	    	   "data_type"  => $datatype,
    	    	   "file_type"  => $filetype,
                   "sources"    => [0],
	           "source_url" => $url,
        	   "meta_data"  => array(
	                   "validated"   => $validated,
        	           "compressed"  => $compressed,
	            	   "description" => $descrip,
			   )
	);
	if (isset($params['oeb_dataset_id'])){ $fileOut['meta_data']["oeb_dataset_id"] = $params['oeb_dataset_id'];}
	if (isset($params['oeb_community_ids'])){ $fileOut['meta_data']["oeb_community_ids"] = $params['oeb_community_ids'];}

        $toolOuts = Array ("output_files" => Array($fileOut));
        
        // setting logName
        $logName = basename($fnP). ".log";
    
        //calling internal tool
        $pid = launchToolInternal($toolId,$toolInputs,$toolArgs,$toolOuts,$output_dir,$logName);

        if ($pid == 0){
            $_SESSION['errorData']['Error'][]="Resource file '".basename($fnP)."' cannot be imported. Error occurred while preparing the job 'Get remote file'";
            redirect($_SERVER['HTTP_REFERER']);
        }else{
            $_SESSION['errorData']['Info'][] ="Remote file '".basename($fnP)."' imported into the 'repository' folder below. Please, edit its metadata once the job has finished";
	    redirect($GLOBALS['BASEURL']."workspace/");
        }
        //FIXME END
       
    
       /*
        // save File into DMP
        $insertData=array(
    	'owner' => $_SESSION['User']['id'],
    	'size'  => $size,
    	'mtime' => new MongoDB\BSON\UTCDateTime(strtotime("now")*1000)
        );
        $descrip=(isset($params['repo'])?"Remote file extracted from ".$params['repo']:"Remote file");
        $metaData=array(
            'validated' => false,
            'description' => $descrip
            );
    
        $fnId = uploadGSFileBNS_fromURL($params['url'],$wd, $insertData,$metaData,0);
    
        
        if ($fnId == "0"){
            $_SESSION['errorData']['Error']="Error occurred while registering the repository file";
        	die("ERROR: Error occurred while registering the repository file.");
        }
        */
   }
}



/*********************************/
/*                               */
/*      DATA FROM SAMPLE DATA    */
/*                               */
/*********************************/

// list sampleData

function getSampleDataList($status=1,$filter_tool_status=true) {

    $ft;
    if ($filter_tool_status){
	    $fa = indexArray($GLOBALS['toolsCol']->find(array('status' => 1),array('_id' => 1)));
	    $fu = indexArray($GLOBALS['visualizersCol']->find(array('status' => 1),array('_id' => 1)));
	    $tools_active = array_keys(array_merge($fa,$fu));

        // if common/anon user, list sampledata for active tools
        if ($_SESSION['User']['Type'] == 3 || $_SESSION['User']['Type'] == 2){
            $ft = $GLOBALS['sampleDataCol']->find(array(
                                                '$or' => array(
                                                    array("status" => $status, "tool"=> array('$not' => array('$exists' => 1)) ),
                                                    array("status" => $status, "tool"=> array('$in'  => $tools_active))
                                                )
                                            ),array('_id' => 1));

        // if admin user, list sampledata regardless tool status    
        }elseif ($_SESSION['User']['Type'] == 0){
            $ft = $GLOBALS['sampleDataCol']->find(array('status' => $status),array('_id' => 1));
        
        // if tool dev user, list sampledata for active tools + its own tools
        }elseif ($_SESSION['User']['Type'] == 1){
            $fr = $GLOBALS['toolsCol']->find(array('status' => 3,'_id'=> array('$in'=>$_SESSION['User']['ToolsDev'])),array('_id' => 1));
            $tools_owned = array_keys(iterator_to_array($fr));
            $ft = $GLOBALS['sampleDataCol']->find(array(
                                                '$or' => array(
                                                    array("status" => $status, "tool"=> array('$not' => array('$exists' => 1)) ),
                                                    array("status" => $status, "tool"=> array('$in'  => array_merge($tools_active,$tools_owned)))
                                                )
                                            ),array('_id' => 1));

        }

    }else{
        // list active sample data sets, regardless tool status
        $ft = $GLOBALS['sampleDataCol']->find(array('status' => $status),array('_id' => 1));
    }
	return iterator_to_array($ft);

}

// get sampleData

function getSampleData($sampleData) {

    return  $GLOBALS['sampleDataCol']->findOne(array('_id' => $sampleData));

}


// import sampleData into into current WS user 

function getData_fromSampleData($params=array()) { //sampleData

    if (!is_array($params['sampleData'])){
        $params['sampleData']=array($params['sampleData']);
    }
    foreach ($params['sampleData'] as $sampleName ){
        $_SESSION['errorData']['Info'][]="Importing exemple dataset for '$sampleName'";
        $dataDir = $_SESSION['User']['id'] ."/".$_SESSION['User']['activeProject'];
        $r = setUserWorkSpace_sampleData($sampleName,$dataDir);
	if ($r=="0"){
        	$_SESSION['errorData']['Warning'][] = "Cannot fully inject exemple dataset into user workspace.";
        	redirect($GLOBALS['URL']."/getdata/sampleDataList.php");
        }else{
	       	$_SESSION['errorData']['Info'][] = "Example data successfuly imported.";
		header("Location:".$GLOBALS['URL']."/workspace/");
	}
    }

}
// progress bar 

function progress_DEPRECATED($resource,$download_size, $downloaded, $upload_size, $uploaded){
	ob_get_clean();
	if($download_size > 0)
     		echo (number_format($downloaded / $download_size  * 100))."\n";
  	ob_flush();
		flush();
}

// upload file from URL via CURL

function getData_fromURL_DEPRECATED($source, $ext = null) {

	// checking if remote file exists
	$ch = curl_init($source);

	curl_setopt($ch, CURLOPT_NOBODY, true);
	curl_exec($ch);
	$retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

	curl_close($ch);

	if(($retcode != 200) && ($retcode != 350)) {
		//$_SESSION['errorData']['upload'][]="ERROR: Trying to upload an unexisting file. Please select a correct file.";
		die("ERROR: Trying to upload an unexisting file. Please select a correct file.");
	}

	// getting working directory
	$dataDirPath = getAttr_fromGSFileId($_SESSION['User']['dataDir'],"path");
	$wd          = $dataDirPath."/uploads";

	$wdP  = $GLOBALS['dataDir']."/".$wd;
	$wdId = getGSFileId_fromPath($wd);

	// check target directory
	if ( $wdId == "0" || !is_dir($wdP) ){
		//$_SESSION['errorData']['upload'][]="Target server directory '".basename($wd)."' does not exist. Please, login again.";
		die("ERROR: Target server directory '".basename($wd)."' does not exist. Please, login again.");
	}

	// downloading file
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $source);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, 'progress');
	curl_setopt($ch, CURLOPT_NOPROGRESS, false); // needed to make progress function work
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
	$data = curl_exec ($ch);
	$error = curl_error($ch); 
	curl_close ($ch);

	// getting path and file size
	$source_path = parse_url($source,PHP_URL_PATH);
	$source_path = explode('&',$source_path);
	$source_path = explode('?',$source_path[0]);
	$nameFile = basename($source_path[0]);
    
	$rfnNew = "$wdP/".cleanName($nameFile);
		
	if(isset($ext)) $rfnNew .= '.'.$ext;
	$size   = strlen($data);

	if($size == 0) {
		//$_SESSION['errorData']['upload'][] = "ERROR: ".$nameFile." file size is zero";
		die("ERROR: ".$nameFile." file size is zero");
	}

	// checking if user disk has run out
	$usedDisk     = (int)getUsedDiskSpace();
	$diskLimit    = (int)$_SESSION['User']['diskQuota'];
			
	if ($size > ($diskLimit-$usedDisk) ) {
		//$_SESSION['errorData']['upload'][] = "ERROR: Cannot upload file. Not enough space left in the workspace";
		die("ERROR: Cannot upload file. Not enough space left in the workspace");
	}

	// checking if file name exists
	if (is_file($rfnNew)){
		foreach (range(1, 99) as $N) {
			if ($pos = strrpos($rfnNew, '.')) {
				$name = substr($rfnNew, 0, $pos);
				$ext = substr($rfnNew, $pos);
			} else {
				$name = $rfnNew;
			}
			$tmpNew= $name .'_'. $N . $ext;
			if (!is_file($tmpNew)){
				$rfnNew = $tmpNew;
				break;
			}
		}
	}
	
	$file = fopen($rfnNew, "w+");

	fputs($file, $data);
	fclose($file);

	if (is_file($rfnNew)){
		chmod($rfnNew, 0666);
		$fnNew = basename($rfnNew);

		$insertData=array(
			'owner' => $_SESSION['User']['id'],
			'size'  => filesize($rfnNew),
			'mtime' => new MongoDB\BSON\UTCDateTime(filemtime($rfnNew)*1000)
		);

		$metaData=array(
			'validated' => FALSE
		);

		$fnId = uploadGSFileBNS("$wd/$fnNew", $rfnNew, $insertData,$metaData,FALSE);

		if ($fnId == "0"){
			unlink($rfnNew);
			//$_SESSION['errorData']['upload']="Error occurred while registering the uploaded file";
			die("ERROR: Error occurred while registering the uploaded file.");
		}

		echo $fnId;

	}else{
		//$_SESSION['errorData']['upload'][]="Uploaded file not correctly stored";
		die("ERROR: Uploaded file not correctly stored.");
	}

}
?>
