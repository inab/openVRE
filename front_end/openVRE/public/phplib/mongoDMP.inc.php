<?php


function DMP_http($method,$service,$data=false){
    
    $resp = 0;
    $info = array();

    $url_base = $GLOBALS['DMPserver_domain'].":".$GLOBALS['DMPserver_port'].$GLOBALS['DMPserver_address'];
    $headers  = array("Content-Type: application/json", "Authorization: Bearer ".$_SESSION['User']['token']);
    print "HEADER : -H \"Content-Type: application/json\" -H \"Authorization: Bearer ".$_SESSION['User']['token']."\"\n";

    $url = $url_base."/".$service;
    switch ($method){
        case "get":
            $data = http_build_query($data);
            $url = $url."?".$data;
            print "URL ============> $url \n";
            list($resp,$info) = get($url,$headers);
            break;
        case "post":
            $data = json_encode($data);
            print "URL ============> $url \n";
            print "DATA ============> $data\n";
            list($resp,$info) = post($data,$url,$headers);
            break;
        case "put":
            $data = json_encode($data);
            print "URL ============> $url \n";
            print "DATA ============> $data\n";
            list($resp,$info) = put($data,$url,$headers);
            break;
        default:
            $_SESSION['errorData']['Error'][]="Method '$method' not implemented in the DMP_http handler";
            return $resp;
    }
    print "RESP ===========> $resp\n";
    #print "INFO ===========> "; var_dump($info); print "\n";

    if ($info['http_code'] != 200 && $info['http_code'] != 204){
        if ($resp){
            $err = json_decode($resp,TRUE);
            $_SESSION['errorData']['DMP'][]="MuG data manager (DM) returned error. [".$err['error']."]: ".$err['error_description'];
        }else{
            $_SESSION['errorData']['DMP'][]="MuG data manager (DM) returned HTTP code = ".$info['http_code'];
        }
        return false;
    } 
    $resp = json_decode($resp,TRUE);
    return $resp;
}

function isGSDirBNS_TEST($fn) { ### !OJO: old params were ($col,$fn) !!!

    // get DMP file
    if ($asRoot){
        $_SESSION['errorData']['Error'][]="Sorry, no admin role defined in DMP yet. Not allowed to check isGSDir $fn being user ".$_SESSION['User']['id'];
        return 0;
    }
    //$user_id = ($asRoot?$asRoot:$_SESSION['User']['id']);
    $params = array("file_id" => $fn);
    $fileDMP = DMP_http("get","file_meta",$params);

    //check files attribute
    if (isset($fileDMP['files']))
        return false;
    else
        return true;
}

function getGSFileId_fromPath_TEST($fnPath,$asRoot=0) {

    // get DMP file
    if ($asRoot){
        $_SESSION['errorData']['Error'][]="Sorry, no admin role defined in DMP yet. Not allowed to get file from path $fnPath being user ".$_SESSION['User']['id'];
        return 0;
    }
    //$user_id = ($asRoot?$asRoot:$_SESSION['User']['id']);
    $files = getGSFiles_byUser_TEST("path");
    if (isset($files[$fnPath])){
        return $files[$fnPath]['_id'];
    }else{
        return 0;
    }
}

function getGSFilesFromDir_TEST($dirId,$onlyVisible=0){  ### !OJO: old params were ($dataSelection=Array(),$onlyVisible=0) !!! 

    $files=Array();

    // get dir
    if (!$dirId){
        if (!isset($_SESSION['curDir'])){
            $_SESSION['errorData']['internal'][]="Cannot retrieve files from the database. Given query is not valid. Please, try it later or mail <a href=\"mailto:".$GLOBALS['helpdeskMail']."\">".$GLOBALS['helpdeskMail']."</a>";
            return FALSE;
        }
        $dirId = $_SESSION['curDir'];
    }
    $dir = getGSFile_fromId_TEST($dirId);

    // check dir
    if (!isset($dir['_id'])){
        $_SESSION['errorData']['Error'][]="Data is not accessible or do not exist anymore. Please, try it later or mail <a href=\"mailto:".$GLOBALS['helpdeskMail']."\">".$GLOBALS['helpdeskMail']."</a>";
        return FALSE;
    }

    if (!isset($dir['files']) || count($dir['files'])==0 ){
        $_SESSION['errorData']['Warning'][]="No data to display in the given directory.";
        return FALSE;
    }

    // Retrieve File Data and Metada for each file in directory
    $count =count( $dir['files']);
	foreach ($dir['files'] as $d) {
        $fData = getGSFile_fromId_TEST($d);

        //clean results: visible files, root dir, mtime
        if ($onlyVisible && (isset($fData['visible']) || $fData['visible'] === false) ){ continue;}
	    if ( $fData['path'] == $_SESSION['User']['id']){continue; }
       	if (is_object($fData['mtime'])){$fData['mtime'] = $fData['mtime']->toDateTime()->format('U');}
        //append
        $files[$fData['_id']] = $fData; 

        if (isset($fData['files']) && count($fData['files'])>0 ){
	    	foreach ($fData['files'] as $dd) {
                $ffData = getGSFile_fromId_TEST($dd);
                //clean results: visible files, root dir, mtime
                if ($onlyVisible && (isset($ffData['visible']) || $ffData['visible'] === false) ){ continue;}
       			if (is_object($ffData['mtime'])){$ffData['mtime'] = $ffData['mtime']->toDateTime()->format('U');}
                //append
        		$files[$ffData['_id']] = $ffData; 
    		}
	    }
    }

    // Return dir files
	return $files;
}

function getGSFile_fromId_TEST($fn,$filter="",$asRoot=0) {
    // get DMP file
    if ($asRoot){
        $_SESSION['errorData']['Error'][]="Sorry, no admin role defined in DMP yet. Not allowed to get file $fn being user ".$_SESSION['User']['id'];
        return 0;
    }
    //$user_id = ($asRoot?$asRoot:$_SESSION['User']['id']);

    $params = array("file_id" => $fn);
    $fileDMP = DMP_http("get","file_meta",$params);

    // convert DMP file to VRE file
    list($fileData,$fileMeta) = getVREfile_fromFile($fileDMP);

    // return VRE file according filter
    if($filter == "onlyMetadata"){
        if (empty($fileMeta))
            return 0;
        return $fileMeta;
    
    }elseif($filter == "onlyData"){
        if (empty($fileData))
            return 0;
        return $fileData;
    
    }else{
        if (empty($fileData))
            return 0;
        if(!isset($fileMeta)) $fileMeta = array();
        return array_merge($fileData,$fileMeta);
    }

}

// get files filtered by certain metadata query
// DMP no not support it
//
function getGSFiles_filteredBy_TEST($fn,$filters) {

    $filter = array_merge(array('user_id' => $_SESSION['User']['id']),$filters);

    $fileDMP = DMP_http("get","file",$filter);

    // convert DMP file to VRE file
    list($fileData,$fileMeta) = getVREfile_fromFile($fileDMP);

    // return VRE file according filter

	if (empty($fileData))
		return 0;

	elseif (empty($fileMeta))
		return $fileData;
	else
		return array_merge($fileData,$fileMeta);	
}

function getAttr_fromGSFileId_TEST($fnId,$attr) {
	$f = getGSFile_fromId_TEST($fnId);
	if (empty($f))
        return false;

	elseif (!isset($f[$attr]) )
		return false;
	else
		return $f[$attr];
}

function getSizeDirBNS_TEST($dir){
	$s=0;
	$dirObj = getGSFile_fromId_TEST($dir);
    if (empty($dirObj) || !isset($dirObj['files']) ){
        $_SESSION['errorData']['mongoDB'][] = $dir ." directory has no files<br/>";
        return 0;
    }
	$files = $dirObj['files'];
	foreach ($files as $child){
	    $childObj = getGSFile_fromId_TEST($child);
    	if (empty($childObj))
    		continue;
    	if ( isset($childObj['files']) ){
    		$s += getSizeDirBNS_TEST($child);
        }elseif(!isset($childObj['size'])){
    		$s +=0;
    	}else{
    		$s += $childObj['size'];
    	}
	}
	return $s; 
}

// create new directory registry

function createGSDirBNS_TEST($dirPath,$asRoot=0) {
	$col = $GLOBALS['filesCol'];
	//check dirPath
	if (strlen($dirPath) == 0){
		$_SESSION['errorData']['mongoDB'][]= "No directory path given";
		return 0;
	}
	list($dirPath,$r) = absolutePathGSDir($dirPath,$asRoot);
	
    if ($r == "0"){
		$_SESSION['errorData']['mongoDB'][]="Cannot create $dirPath . Target not under root directory ".$_SESSION['User']['id']." ?";
		return 0;
    }

	//check owner
	$owner = $_SESSION['User']['id'];
	if ($asRoot){
		if ( preg_match('/^'.preg_quote($GLOBALS['dataDir'], '/').'(\/)*([^\/.]+)/',$dirPath,$m) ){
			$owner = $m[2];
		}elseif (preg_match('/^([^\/.]+)/',$dirPath,$m) ){
			$owner = $m[1];
		}
	}

	// already there?
	$r = getGSFileId_fromPath_TEST($dirPath,1); # OJO TODO: asroot=1 
	if ($r != "0"){
		return $r;
	}
	
	//check parent
	if ( $dirPath == $_SESSION['User']['id'] ){
		$parentId = 0;
	}elseif($asRoot && (preg_match('/^'.preg_quote($GLOBALS['dataDir'], '/').'(\/)*[^\/.]+$/',$dirPath))  ){
		$parentId = 0;
	}elseif($asRoot && (preg_match('/^[^\/.]+$/',$dirPath))  ){
		$parentId = 0;
	}else{
		$parentPath = dirname($dirPath);
		$parentId   = getGSFileId_fromPath_TEST($parentPath,1); # OJO TODO: asroot=1
		if ($parentId == "0"){
			$r = createGSDirBNS($parentPath);
			if ($r=="0")
				return 0;
		}
	}
	if ($parentId && $parentId!="0"){
        $parentObj = getGSFile_fromId_TEST($parentId,"",$asRoot);
		if (isset($parentObj['permissions']) && $parentObj['permissions']== "000" ){
			$_SESSION['errorData']['mongoDB'][]= "Not permissions to modify parent directory $parent";
			return 0;
		}
    }

	//store
    $dirId = createLabel();

	$dataDMP = array(
			'user_id'       => $owner,
			'file_path'     => $dirPath,
            'creation_time' => new MongoDB\BSON\UTCDateTime(strtotime("now")*1000),
            'meta_data' =>  array(
      			'size'       => 0,
    			'type'       => 'dir',
			    'atime'      => new MongoDB\BSON\UTCDateTime(strtotime("now")*1000),
			    'files'      => array(),
                'parentDir'  => $parentId
            )
    );
    $dirId = DMP_http("post","track",$dataDMP);
    print "DIR ID ------------> $dirId";
    if ($dirId){
        if ($parentId && $parentId!="0"){

            #curl -X PUT -H "Content-Type: application/json" -d '{"type":"add_meta", "file_id":"<file_id>", "user_id":"test_user", "meta_data":{"citation":"PMID:1234567890"}}' http://localhost:5002/mug/api/dmp/track
            $params = array("file_id"  => $parentId,
                            "user_id"  => $owner,
                            "type"     => "add_meta",
                            "meta_data"=> array("files" => array($dirId))
            );
            $r = DMP_http("put","track",$params);
    	}
	    return $dirId;
    }else{
	    return 0;
    }
}


// get all files owned by user
//
function getGSFiles_byUser_TEST($index="_id") {

    $files=array();

    // get DMP file
    $params = array("by_user"=> 1);
    $filesDMP = DMP_http("get","files",$params);

    if (isset($filesDMP['files']) && count($filesDMP['files']) ){
        foreach ($filesDMP['files'] as $fileDMP){
            list($file,$fileMeta) = getVREfile_fromFile($fileDMP);
            $file = array_merge($file,$fileMeta);
            if (isset($file[$index])){
                $files[$file[$index]] = $file;
            }
        }
    }
    return $files;
}
