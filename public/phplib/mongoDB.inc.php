<?php

//  test if a given file_id is a directory
 
function isGSDirBNS($col, $fn) {
	$file = $col->findOne(array('_id'  => $fn,
			'files' => array('$exists' => true)
		   )
	);
	if (empty($file)){
		return false;
	}else{
		return true;
	}
}

//  recursively retrive entire Files for a given directory selection

function getGSFileIdsFromDir($dirId,$asRoot=0,$filesAnt=array()){
/*
    $files = getAttr_fromGSFileId($dirId,"files",$asRoot);

    if ($files) {
        foreach($files as $f){
            $files_child = getGSFileIdsFromDir($f,$asRoot);
            array_merge($files,$files_child);
        }
    }else{
        array_push($files,$dirId);
        $files = 
    }
    return $files;
 */
    return array();
}

function getGSFilesFromDir($dataSelection=Array(),$onlyVisible=0){

	$files=Array();

    // set directory query
    if (count($dataSelection) == 0 ){
        if (!isset($_SESSION['curDir'])){
            $_SESSION['errorData']['internal'][]="Cannot retrieve files from the database. Given query is not valid. Please, try it later or mail <a href=\"mailto:".$GLOBALS['helpdeskMail']."\">".$GLOBALS['helpdeskMail']."</a>";
            return FALSE;
        }
        $dataSelection = Array(
            'owner' => $_SESSION['User']['id'],
            'path'  => $_SESSION['curDir']
        );
    }
    // query directory document


    $dirData = $GLOBALS['filesCol']->findOne($dataSelection);

    if (!isset($dirData['_id'])){
        $_SESSION['errorData']['Error'][]="Data is not accessible or do not exist anymore.";
        if (isset($GLOBALS['helpdeskMail'])){
            $_SESSION['errorData']['Error'][]="Please, try it later or mail <a href=\"mailto:".$GLOBALS['helpdeskMail']."\">".$GLOBALS['helpdeskMail']."</a>";
        }
        return FALSE;
    }

    if (!isset($dirData['files']) || count($dirData['files'])==0 ){
        $_SESSION['errorData']['Warning'][]="No data to display in the given directory.";
        return FALSE;
	}

    // retrieve File Data and Metada for each file in directory
    $count =count( $dirData['files']);

    foreach ($dirData['files'] as $d) {

        if ($onlyVisible)
		    $fData = getGSFile_filteredBy($d, array('visible'=> Array('$ne'=>false)) );	
	    else
	    	    $fData = getGSFile_fromId($d);

	if ( $fData['path'] == $_SESSION['User']['id'] ){ // home file
            continue;
        }
        if ($fData == 0){ // file visible == false
            continue;
	}
	$fData['mtime'] = $fData['mtime']->toDateTime()->format('U'); # UTC DateTime to seconds

	$files[$fData['_id']] = $fData; 
	if (isset($fData['files']) && count($fData['files'])>0 ){
		foreach ($fData['files'] as $dd) {

	    		if ($onlyVisible)
			    $ffData = getGSFile_filteredBy($dd, array('visible'=> Array('$ne'=>false)) );	
			else
	    	   	     $ffData = getGSFile_fromId($dd);

			if (is_object($ffData['mtime']))
				$ffData['mtime'] = $ffData['mtime']->toDateTime()->format('U');
	    		$files[$ffData['_id']] = $ffData; 
		}
	}
    }
    return $files;

}

//  return file_id from attribute 'path'

function getGSFileId_fromPath($fnPath,$asRoot=0) {
	$col = $GLOBALS['filesCol'];
	if ($asRoot){
		$file = $col->findOne(array('path'  => $fnPath));
	}else{
		$file = $col->findOne(array('path'  => $fnPath,
				    'owner' => $_SESSION['User']['id']
		));
	}
	if (empty($file)){
		return 0;
	}else{
		return $file['_id'];
	}
}

//  return File (entire, onlyMetadata, onlyData) from file_id

function getGSFile_fromId($fn,$filter="",$asRoot=0) {
    if ($asRoot)
        $fileData = $GLOBALS['filesCol']->findOne(array('_id' => $fn) );
    else
    	$fileData = $GLOBALS['filesCol']->findOne(array('_id' => $fn, 'owner' => $_SESSION['User']['id']));
    $fileMeta = $GLOBALS['filesMetaCol']->findOne(array('_id' => $fn));

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

//  return File (entire, onlyMetadata, onlyData) from file_id

function getGSFile_filteredBy($fn,$filters) {

	$filter_filesCol     = Array('_id' => $fn);
	$filter_filesMetaCol = Array('_id' => $fn);
	foreach ($filters as $attr => $v){
		if (in_array($attr, Array('owner', 'size', 'path', 'mtime', 'parentDir', 'expiration','project')) )
			$filter_filesCol[$attr] = $v;
		else
			$filter_filesMetaCol[$attr] = $v;
	}	
    $fileData = $GLOBALS['filesCol']->findOne($filter_filesCol);
    $fileMeta = $GLOBALS['filesMetaCol']->findOne($filter_filesMetaCol);
	$existMeta= $GLOBALS['filesMetaCol']->findOne(Array('_id' => $fn));

	if (empty($fileData))
		return 0;

	if (empty($existMeta))
		return $fileData;

	elseif (empty($fileMeta))
		return 0;
	else
		return array_merge($fileData,$fileMeta);	
	
}

function getGSFiles_filteredBy($filters,$asRoot=0) {

#print_r($filters);
		
    $filter_filesCol=array();	
    $filter_filesMetaCol=array();	
    foreach ($filters as $attr => $v){
	if (in_array($attr, Array('type','owner', 'size', 'path', 'mtime', 'atime' ,'parentDir', 'expiration','project', 'files', 'lastAccess')) )
		$filter_filesCol[$attr] = $v;
	else
		$filter_filesMetaCol[$attr] = $v;
    }

    # files = filesData + filesMetadata
    $files=array();

    if (count($filter_filesMetaCol) && count($filter_filesCol)){
        # Find in Files and FilesMetadata by filter.
        if (!$asRoot)
            $filter_filesCol['owner'] = $_SESSION['User']['id'];
	$fileData = $GLOBALS['filesCol']->find($filter_filesCol)->toArray(); 
	$fileData_arr = indexArray($fileData);
	//$fileData_arr = iterator_to_array($fileData);

        if (empty($fileData)){
            return $files;
        }

        $ids = array_keys($fileData_arr);
        $filter_filesMetaCol["_id"] = array('$in' => $ids);

        $fileMeta = $GLOBALS['filesMetaCol']->find($filter_filesMetaCol)->toArray();

        foreach ($fileMeta as $fm){
            $id = $fm['_id'];
            $file = array_merge($fm,$fileData_arr[$id]); 
            $file['_id'] = $id;
            $files[$id] = $file;

        }

    }elseif (count($filter_filesMetaCol)){
        # Find in FilesMetadata by filter, and find the resulting files into Files 
        $fileMeta = $GLOBALS['filesMetaCol']->find($filter_filesMetaCol)->toArray();
	$fileMeta_arr = indexArray($fileMeta);
        //$fileMeta_arr = iterator_to_array($fileMeta);

        if (empty($fileMeta)){
            return $files;
        }
        $ids = array_keys($fileMeta_arr);

        $fileData = $GLOBALS['filesCol']->find(array("_id" => array('$in' => $ids)))->toArray();

        foreach ($fileData as $fd){
            $id = $fd['_id'];
            if (!$asRoot && $fd['owner'] != $_SESSION['User']['id'] ){
                continue;
            }
            $file = array_merge($fd,$fileMeta_arr[$id]); 
            $file['_id'] = $id;
            $files[$id] = $file;

        }
       

    }elseif(count($filter_filesCol)){
        # Find in Files by filter, and find the resulting files into FilesMetadata
        if (!$asRoot)
            $filter_filesCol['owner'] = $_SESSION['User']['id'];
        

         $fileData = $GLOBALS['filesCol']->find($filter_filesCol); 

        if (empty($fileData)){
            return $files;
        }

        //$fileData_arr = iterator_to_array($fileData);
	$fileData_arr = indexArray($fileData);
        $ids = array_keys($fileData_arr);

        $fileMeta = $GLOBALS['filesMetaCol']->find(array("_id" => array('$in' => $ids)))->toArray();

        foreach ($fileMeta as $fm){
            $id = $fm['_id'];
            $file = array_merge($fm,$fileData_arr[$id]); 
            $file['_id'] = $id;
            $files[$id] = $file;

        }

    }
    return $files;

}

function addAssociatedFiles_OBSOLETE($masterId,$assocIds) {

    $meta_master  = $GLOBALS['filesMetaCol']->findOne(array('_id'
        => $masterId));
	if (!isset($meta_master['associated_files']))
		$meta_master['associated_files']=array();

	// update associated files metadata
	foreach ($assocIds as $assocId){
		array_push($meta_master['associated_files'],$assoc);
		addMetadataBNS($assocId,array('associated_id'=>$masterId) );
	}
	// update master file metadata
	modifyMetadataBNS($masterId,$meta_master);
	return 1;
}

function getAssociatedFiles_fromId($fn,$assoc=Array()) {
	if (in_array($fn,$assoc))
		return $assoc;

	$f  = getGSFile_fromId($fn);
	if (isset($f['associated_files'])){
		foreach ($f['associated_files'] as $a){
			$assoc = getAssociatedFiles_fromId($a,$assoc);
			array_push($assoc,$a);
		}
		return $assoc;
	}else{
		return $assoc;
	}
}

function getAttr_fromGSFileId($fnId,$attr,$asRoot=0) {
	//$f = $GLOBALS['filesCol']->findOne(array('_id' => $fnId));
	$f = getGSFile_fromId($fnId,"",$asRoot);
	if (empty($f))
		return false;
	elseif (!isset($f[$attr]) )
		return false;
	else
		return $f[$attr];
}


function getSizeDirBNS($dir){
	$s=0;
	$dirObj= $GLOBALS['filesCol']->findOne(array('_id' => $dir, 'owner'=>$_SESSION['User']['id'] ));
	if (empty($dirObj) || !isset($dirObj['files']) ){
	$_SESSION['errorData']['mongoDB'][] = $dir ." directory has no files<br/>";
		return 0;
	}
	$files = $dirObj['files'];
	foreach ($files as $child){
	$childObj= $GLOBALS['filesCol']->findOne(array('_id' => $child));
	if (empty($childObj))
		continue;
	if ( isset($childObj['files']) ){
		$s += getSizeDirBNS($child);
	}else{
		$s += $childObj['size'];
	}
	}
	return $s; 
}


function moveGSFileBNS($fn,$fnNew,$asRoot=0,$owner=""){

    if (!$asRoot){
        $owner = $_SESSION['User']['id'];
    }


    $fn     = fromAbsPath_toPath($fn);
    $fnNew  = fromAbsPath_toPath($fnNew);

    // Check file to be created is not there   
    $fileNewId = getGSFileId_fromPath($fnNew,$asRoot);
    if ( $fileNewId != "0"){
        $_SESSION['errorData']['Error'][]="Cannot move '$fn' to '$fnNew'. The target file already exists.";
        return 0;
    }

    // Check file to be moved exists 
    $fileOldId = getGSFileId_fromPath($fn,$asRoot);
    $fileOld   = getGSFile_fromId($fileOldId,"",$asRoot);
	if ( empty($fileOld)){
		$_SESSION['errorData']['Error'][] = "Cannot move file '$fn'. File not found or not accessible.";
		return 0;
    }
	if (isGSDirBNS($GLOBALS['filesCol'], $fileOld['_id'])){
		$_SESSION['errorData']['Error'][]= "Cannot move file 'fn'. Expected file type, but directory found.";
		return 0;
	}

	if (isset($fileOld['permissions']) && $fileOld['permissions']== "000" ){
		$_SESSION['errorData']['Error'][]= "Cannot move '$fn'. Permission denied.";
		return 0;
    }
    //print "MOVE FILE ID = $fileOldId ---> $fileNewId<br/>";

    //Set parent for the new file
    $parentOld = $fileOld['parentDir']; 
    $parentNew = "";
	$parentPath = "";
	if ( $fnNew == $owner){
		$parentNew = 0;
	}else{
		$parentPath = dirname($fnNew);
		$parentNew  = getGSFileId_fromPath($parentPath,$asRoot);
		if ($parentNew == "0"){
			$_SESSION['errorData']['Error'][] = "Cannot move file ".$fn." to ".$fnNew." . Target folder '$parentPath' should exist.";
            return 0;
		}
	    if (!isGSDirBNS($GLOBALS['filesCol'], $parentNew)){
			$_SESSION['errorData']['Error'][] = "Cannot move file ".$fn." to ".$fnNew." . Target folder '$parentPath' is not a directory.";
            return 0;
        }
    }
    if ($parentNew == "" ){
        $_SESSION['errorData']['Error'][] = "Cannot move file ".$fn." to ".$fnNew." . Cannot define target folder. Is it a valid target path?";
        return 0;
    }
    if ($parentNew == "0" ){
        $_SESSION['errorData']['Error'][] = "Cannot move file ".$fn." to ".$fnNew." . Target folder cannot be a home folder.";
        return 0;
    }
    //Set project
    $p_code="";
    if (preg_match('/\/(__PROJ[^\/]*)/',$fnNew,$match)){ $p_code = $match[1];}
    

    // Update file entry
    modifyGSFileBNS($fileOld['_id'],"path", $fnNew);
    modifyGSFileBNS($fileOld['_id'],"parentDir", $parentNew);
    modifyGSFileBNS($fileOld['_id'],"atime", new MongoDB\BSON\UTCDateTime(strtotime("now")*1000));
    if ($p_code !=""){
        modifyGSFileBNS($fileOld['_id'],"project", $p_code);
    }

    // if not only rename required, update parents and children
    
    if ($parentNew != $parentOld){
    
        $fileNew = getGSFileId_fromPath($fnNew,$asRoot);
        if ( empty($fileNew)){
    		$_SESSION['errorData']['Error'][] = "Error moving file $fn to $fnNew . Failed to set new path.";
    		return 0;
        }
    
    	// update new parentDir  - add moved file
    	$GLOBALS['filesCol']->updateOne(
        	array("_id"=>$parentNew),
    	    array('$addToSet' => array("files" => $fileOld['_id']))
        );
	var_dump("XXXXX",strtotime("now"));
	
    	modifyGSFileBNS($parentNew,"atime", new MongoDB\BSON\UTCDateTime(strtotime("now")*1000));
        $size_parent = 0 + getAttr_fromGSFileId($parentNew,"size");
    	modifyGSFileBNS($parentNew,"size", $size_parent + $fileOld['size']);
    
    
    	// update old parentDir - pull moved file
    	$GLOBALS['filesCol']->updateOne(
    		 array('_id'=> $parentOld),
    		 array('$pull' => array("files"=>$fileOld['_id']))
         );
    	modifyGSFileBNS($parentOld,"atime", new MongoDB\BSON\UTCDateTime(strtotime("now")*1000));
        $size_parent = 0 + getAttr_fromGSFileId($parentOld,"size");
        modifyGSFileBNS($parentNew,"size", $size_parent - $fileOld['size']);
    }
    
	return 1;
}


function moveGSDirBNS($fn,$fnNew,$asRoot=0,$owner=""){

    if ($asRoot == 0){
        $owner = $_SESSION['User']['id'];
    }

    $fn     = fromAbsPath_toPath($fn);
    $fnNew  = fromAbsPath_toPath($fnNew);

    // Check dir to be created is not there   
    $dirNewId = getGSFileId_fromPath($fnNew,$asRoot);
    if ( $dirNewId != "0"){
        $_SESSION['errorData']['Error'][]="Cannot move '$fn' to '$fnNew'. The target directory already exists.";
        return 0;
    }

    // Check dir to be moved exists 
    $dirId = getGSFileId_fromPath($fn,$asRoot);
    $dir   = getGSFile_fromId($dirId,"",$asRoot);
	if ( empty($dir)){
		$_SESSION['errorData']['Error'][] = "Cannot move directory '$fn'. Directory not found or not accessible.";
		return 0;
    }

	if (!isset($dir['parentDir'])){
		$_SESSION['errorData']['mongoDB'][]= " Cannot find parent directory attribute for $fn . </br> <a href=\"javascript:history.go(-1)\">[ OK ]</a>";
	    return 0;
    }

    //Set parent for the new dir
    $parentId   = $dir['parentDir']; 
    $parentNew  = "";
	$parentPath = "";
	if ( $fnNew == $owner){
		$parentNew = 0;
	}else{
		$parentPath = dirname($fnNew);
		$parentNew  = getGSFileId_fromPath($parentPath,$asRoot);
		if ($parentNew == "0"){
			$_SESSION['errorData']['Error'][] = "Cannot move directory ".$fn." to ".$fnNew." . Target folder '$parentPath' should exist.";
            return 0;
		}
	    if (!isGSDirBNS($GLOBALS['filesCol'], $parentNew)){
			$_SESSION['errorData']['Error'][] = "Cannot move directory ".$fn." to ".$fnNew." . Target folder '$parentPath' is not a directory.";
            return 0;
        }
    }
    if ($parentNew == "" ){
        $_SESSION['errorData']['Error'][] = "Cannot move directory ".$fn." to ".$fnNew." . Cannot define target folder. Is it a valid target path?";
        return 0;
    }
    if ($parentNew == "0" ){
        $_SESSION['errorData']['Error'][] = "Cannot move directory ".$fn." to ".$fnNew." . Target folder cannot be a home folder.";
        return 0;
    }


    // Update dir entry
    modifyGSFileBNS($dir['_id'],"path", $fnNew);
 	modifyGSFileBNS($dir['_id'],"parentDir", $parentNew);
   	modifyGSFileBNS($dir['_id'],"atime", new MongoDB\BSON\UTCDateTime(strtotime("now")*1000));
   
    $dirNew = getGSFileId_fromPath($fnNew,$asRoot);
    if ( empty($dirNew)){
		$_SESSION['errorData']['Error'][] = "Error moving directory $fn to $fnNew . Failed to set new path.";
 		return 0;
    }
    
    // if not only rename required, update parents and children
    
    if ($parentNew != $parentId){

	    // update new parentDir  - add moved file
    	$GLOBALS['filesCol']->updateOne(
        	array("_id"=>$parentNew),
    	    array('$addToSet' => array("files" => $dir['_id']))
        );
    	modifyGSFileBNS($parentNew,"atime", new MongoDB\BSON\UTCDateTime(strtotime("now")*1000));
        $size_parent = 0 + getAttr_fromGSFileId($parentNew,"size");
    	modifyGSFileBNS($parentNew,"size", $size_parent + $dir['size']);

    
    	// update old parentDir - pull moved file
    	$GLOBALS['filesCol']->updateOne(
	    	 array('_id'=> $parentId),
    		 array('$pull' => array("files"=>$dir['_id']))
         );
       	modifyGSFileBNS($parentId,"atime", new MongoDB\BSON\UTCDateTime(strtotime("now")*1000));
        $size_parent = 0 + getAttr_fromGSFileId($parentId,"size");
        $siz  = ($dir['size'] < $size_parent ? $size_parent - $dir['size']:0 );
    	modifyGSFileBNS($parentId,"size", $siz);
    }
    
    // Recursivelly move each dir file
    foreach ($dir['files'] as $f ){
        $f_file = getGSFile_fromId($f,"",1);
        $f_fn    = $f_file['path'];
        $f_fnNew = $fnNew ."/". basename($f_file['path']);
        if ( isGSDirBNS($GLOBALS['filesCol'], $f) ){
            $r = moveGSDirBNS($f_fn,$f_fnNew,$asRoot,$owner);
    	}else{
            $r = moveGSFileBNS($f_fn,$f_fnNew,$asRoot,$owner);
        }
   		if ($r == 0)
           return 0;
    }

    return 1;
}



function fromAbsPath_toPath($absPath){
	$path = str_replace($GLOBALS['dataDir'],"",$absPath);
	return preg_replace('/^\//',"",$path);
}


function absolutePathGSDir($dir,$asRoot=0){
	if ($asRoot){
		if (preg_match('/^\//',$dir)){
			$path = str_replace($GLOBALS['dataDir'],"",$dir);
			$path = preg_replace('/^\//',"",$path);
			return array($path,1);
		}else{
			return array($dir,1);
		}	
		
	}else{
		$root = $_SESSION['User']['id'];
		if ( $root != $_SESSION['curDir'] && ! preg_match('/^(\/)*'.$root.'(\/|$)/',$_SESSION['curDir'])){
			$_SESSION['errorData']['mongoDB'][]= "Current directory ".$_SESSION['curDir']." is not under the home directory $root. Restart login, please";
			return array($dir,0);
		}
		if (!preg_match('/^(\/)*'.$root.'(\/|$)/',$dir)){
			return array($_SESSION['curDir']."/".$dir,1);
		}else{
			return array($dir,1);
		}
	}
}

function absolutePathGSFile($fn,$asRoot){
	if ($asRoot){
                if (preg_match('/^\//',$fn)){
                        $path = str_replace($GLOBALS['dataDir'],"",$fn);
                        $path = preg_replace('/^\//',"",$path);
                        return array($path,1);
                }else{
                        return array($fn,1);
                }

	}else{
		$root = $_SESSION['User']['id'];
		if ( $root != $_SESSION['curDir'] && ! preg_match('/^(\/)*'.$root.'(\/|$)/',$_SESSION['curDir'])){
			$_SESSION['errorData']['mongoDB'][] = "Current directory ".$_SESSION['curDir']." is not under the home directory $root. Restart login, please";
			return array($fn,0);
		}
		if ( !preg_match('/^(\/)*'.$root.'\//',$fn)){
			return array($_SESSION['curDir']."/".$fn,1);
		}else{
			return array($fn,1);
		}
	}	
}


// create new directory registry

function createGSDirBNS($dirPath,$asRoot=0) {
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

	// already there?
	$r = getGSFileId_fromPath($dirPath,1);
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
		$parentId   = getGSFileId_fromPath($parentPath,1);
		if ($parentId == "0"){
			$r = createGSDirBNS($parentPath);
			if ($r=="0")
				return 0;
		}
	}
	if ($parentId && $parentId!="0"){
		$parentObj = $col->findOne(array('_id' => $parentId, 'owner' => $_SESSION['User']['id']) );
		if (isset($parentObj['permissions']) && $parentObj['permissions']== "000" ){
			$_SESSION['errorData']['mongoDB'][]= "Not permissions to modify parent directory $parent";
			return 0;
		}
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
    //set project # TODO : take proj as argument
    $project =  $_SESSION['User']['activeProject'];

	//store
	$dirId = createLabel();


	$col->updateOne(
	   array('_id' => $dirId),
	   array('$set' => array(
			'_id'        => $dirId,
			'type'       => 'dir',
			'owner'      => $owner,
			'size'       => 0,
			'path'       => $dirPath,
			'project'    => $project,
			'mtime'      => new MongoDB\BSON\UTCDateTime(strtotime("now")*1000),
			'atime'      => new MongoDB\BSON\UTCDateTime(strtotime("now")*1000),
			'files'      => [],
			'parentDir'  => $parentId)
		),array('upsert'=> true)	
	);

	if ($parentId && $parentId!="0"){
		$col->updateOne(
			array("_id"=>$parentId),
			array('$addToSet' => array("files" => $dirId))
		);
	}
	return $dirId;
}


// create new file registry
// load file content to GRID, if load2grid===TRUE
function uploadGSFileBNS($fnPath, $file, $attributes=Array(), $meta=Array(), $load2grid=false,$asRoot=0){

	$col = $GLOBALS['filesCol'];

	//check fn
	list($fnPath,$r) = absolutePathGSFile($fnPath,$asRoot);
	if ($r==0){
		$_SESSION['errorData']['mongoDB'][]="Cannot upload $fnPath . Check current directory". $_SESSION['curDir'];
        return $r;
    }
    
    $r = getGSFileId_fromPath($fnPath);
    if ($r != "0"){
           $_SESSION['errorData']['mongoDB'][]="Cannot upload $fnPath . File path already exists";
           return $r;
    }

	//check parent
    if (isset($attributes['parentDir']) ){
        if (!$attributes['parentDir']){
            $_SESSION['errorData']['Warning'][]="Given parent directory is invalid (".$attributes['parentDir']."). Infering it from '$fnPath' path file.";
            unset($attributes['parentDir']);
        }
        $parentId = $attributes['parentDir'];
    }
    if (!isset($parentId) || !$parentId){
       $parentPath  = dirname($fnPath);
       if ($parentPath == ".")
               $parentPath=$_SESSION['User']['id'];
       
       $parentId  = getGSFileId_fromPath($parentPath,$asRoot);
       if ($parentId == "0"){
               $r = createGSDirBNS($parentPath,$asRoot);
               if ($r=="0")
                       return 0;
       }else{
               if (!isGSDirBNS($col,$parentId) ){
                       $_SESSION['errorData']['mongoDB'][]="Cannot upload $fnPath. Parent '$parentPath' is not a directoryy";
                       return 0;
               }
               $parentObj = $col->findOne(array(
                                       '_id' => $parentId,
                                       'owner' => $_SESSION['User']['id']
                                       ) );
               if (isset($parentObj['permissions']) && $parentObj['permissions']== "000" ){
                       $_SESSION['errorData']['mongoDB'][]= "Not permissions to modify parent directory $parentPath";
                       return 0;
               }
       }
    }

	//load file content to grid
	if ($load2grid){
		$r = uploadGSFile($GLOBALS['grid'], $fnPath, $file );
	}

	// load File info to mongo

	$fnId = (!isset($attributes['_id'])? createLabel():$attributes['_id']);

	if ($attributes){
		//set default file attributes
		if (! isset($attributes['_id']))
				$attributes['_id'] = $fnId;
		if (! isset($attributes['owner']))
				$attributes['owner'] = $_SESSION['User']['id'];
		if (! isset($attributes['mtime']))
				$attributes['mtime'] = new MongoDB\BSON\UTCDateTime(filemtime($file)*1000);
		if (! isset($attributes['size']))
				$attributes['size'] =filesize($file);
		if (! isset($attributes['parentDir']))
				$attributes['parentDir'] =$parentId;
		if (! isset($attributes['path']))
				$attributes['path'] = $fnPath;
		if (! isset($attributes['project']))
				$attributes['project'] = $_SESSION['User']['activeProject'];
		if (! isset($attributes['expiration'])){
				$expiration = $GLOBALS['caduca'] * 24 * 3600;
				$t = filemtime($file);
				$attributes['expiration'] = new MongoDB\BSON\UTCDateTime(($t + $expiration)*1000);
        }

        // insert file
		$GLOBALS['filesCol']->updateOne(
			array('_id'  => $fnId),
			array('$set' => $attributes),
			array('upsert' => true)
		);

        // update parent - add into files, set new size and atime
		$GLOBALS['filesCol']->updateOne(
			array("_id"=>$parentId),
			array('$addToSet' => array("files" => $fnId))
        );

	modifyGSFileBNS($parentId,"atime", new MongoDB\BSON\UTCDateTime(filemtime($file)*1000));

        $size_parent = 0 + getAttr_fromGSFileId($parentId,"size");
	modifyGSFileBNS($parentId,"size", $size_parent + $attributes['size']);


	}
	// add metadata file
	if (count($meta)){
		modifyMetadataBNS($fnId,$meta);
	}
	return $attributes['_id'];
}


// create new file registry from a URL
function uploadGSFileBNS_fromURL($url, $parentPath , $attributes=Array(), $meta=Array(), $asRoot=0){

	$col = $GLOBALS['filesCol'];

    //check url
    if(!is_url($url)){
        $_SESSION['errorData']['mongoDB'][]="Cannot upload '$url'. Invalid URL format";
        return 0;
    }
    $r = getGSFileId_fromPath($url);
    if ($r != "0"){
		$_SESSION['errorData']['mongoDB'][]="Warning: Cannot upload '$url'. The resource was already there";
        return $r;
    }

	//check parent
	$parentId  = getGSFileId_fromPath($parentPath,$asRoot);
	if ($parentId == "0"){
		$r = createGSDirBNS($parentPath,$asRoot);
		if ($r=="0")
			return 0;
	}else{
		if (!isGSDirBNS($col,$parentId) ){
			$_SESSION['errorData']['mongoDB'][]="Cannot upload '$url'. Parent '$parentPath' is not a directoryy";
			return 0;
        }
		$parentObj = $col->findOne(array(
					'_id' => $parentId,
					'owner' => $_SESSION['User']['id']
					) );
		if (isset($parentObj['permissions']) && $parentObj['permissions']== "000" ){
			$_SESSION['errorData']['mongoDB'][]= "Not permissions to modify parent directory $parentPath";
			return 0;
		}
	}


	// load File info to mongo

	$fnId = (!isset($attributes['_id'])? createLabel():$attributes['_id']);

	if ($attributes){
		//set default file attributes
		if (! isset($attributes['_id']))
				$attributes['_id'] = $fnId;
		if (! isset($attributes['owner']))
				$attributes['owner'] = $_SESSION['User']['id'];
		if (! isset($attributes['mtime']))
				$attributes['mtime'] = new MongoDB\BSON\UTCDateTime(strtotime("now")*1000);
		if (! isset($attributes['size'])){
                $ch = curl_init($params['url']);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_HEADER, TRUE);
                curl_setopt($ch, CURLOPT_NOBODY, TRUE);
                $attributes['size'] = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
                curl_close($ch);
        }
		if (! isset($attributes['parentDir']))
				$attributes['parentDir'] =$parentId;
		if (! isset($attributes['path']))
				$attributes['path'] = $url;
		if (! isset($attributes['project']))
				$attributes['project'] = $_SESSION['User']['activeProject'];
		if (! isset($attributes['expiration'])){
				$expiration = $GLOBALS['caduca'] * 24 * 3600;
				$attributes['expiration'] = new MongoDB\BSON\UTCDateTime((strtotime("now") + $expiration)*1000);
		}
		$GLOBALS['filesCol']->updateOne(
			array('_id' => $fnId),
			$attributes,
			array('upsert'=> true)
		);

		// set parent
		$GLOBALS['filesCol']->updateOne(
			array("_id"=>$parentId),
			array('$addToSet' => array("files" => $fnId))
		);
		modifyGSFileBNS($parentId,"atime", new MongoDB\BSON\UTCDateTime(filemtime($file)*1000));

	}
	// add metadata file
	if (count($meta)){
		modifyMetadataBNS($fnId,$meta);
	}
	return $attributes['_id'];
}


//insert metadata for a file
//overwrites all metadata
function modifyMetadataBNS($fn, $metadata){
	if (empty($GLOBALS['filesCol']->findOne(array('_id' => $fn))  ) ){
		$_SESSION['errorData']['mongoDB'][]= "Cannot modify metadata for $fn. File not in the repository";
		return 0;
	}
	$GLOBALS['filesMetaCol']->updateOne(
			array('_id' => $fn),
			array('$set' => $metadata),
			array('upsert'=> true)
	);
	//if ($GLOBALS['filesMetaCol']->lastError()){
	//	$err = $GLOBALS['filesMetaCol']->lastError();
	//	$_SESSION['errorData']['mongoDB'][] = $err['err'];
	//	return 0;
	//}else{
		return 1;
	//}
}


//insert metadata for a file
//add new metadata keys to previous metadata
function addMetadataBNS($fn, $metadata){
	if (empty($GLOBALS['filesCol']->findOne(array('_id' => $fn))) ){
		$_SESSION['errorData']['mongoDB'][]= "Cannot add metadata for $fn. File not in the repository";
		return 0;
	}
	foreach ($metadata as $k=>$v){
		$GLOBALS['filesMetaCol']->updateOne(
			array('_id'   => $fn),
			array('$set'  => array($k => $v)),
			array('upsert'=> true)
		);
		
	}
	return 1;
}

// edit file registry (update  mtime, permissions, etc)

function modifyGSFileBNS($fn, $attribute, $value){

	$file  = $GLOBALS['filesCol']->findOne(array('_id' => $fn) );
	if ( empty($file)){
		$_SESSION['errorData']['mongoDB'][] = " Cannot set $attribute=$value into file $fn. File not found.";
		return 0;
	}

	if (is_string($attribute) && !is_array($value) ){
		$GLOBALS['filesCol']->updateOne(
			array('_id' => $fn),
			array('$set'=> array( $attribute => $value))
		);
	}else{
		$_SESSION['errorData']['mongoDB'][] = " Cannot set $attribute=$value into file $fn. Attribute expects a string. Value cannot be an array";
		return 0;
	}
	return 1;
}

// delete file registry

function deleteGSFileBNS($fn,$asRoot=0,$force=false){ //fn == fnId

	// check file
	if ($asRoot == 1)
		$file  = $GLOBALS['filesCol']->findOne(array('_id' => $fn) );
	else
        $file  = $GLOBALS['filesCol']->findOne(array('_id' => $fn, 'owner' => $_SESSION['User']['id'] ) );

	if (empty($file)){
		$_SESSION['errorData']['Warning'][]= " Cannot remove file with id=$fn. File was not there anymore.";
		if(!$force){return 0;}
	}
	if (!$force && isset($file['permissions']) && $file['permissions']== "000" ){
		$_SESSION['errorData']['mongoDB'][]= " Not permissions to remove $fn";
		return 0;
	}
	if (!$force && isGSDirBNS($GLOBALS['filesCol'], $fn)){
		$_SESSION['errorData']['mongoDB'][]= " Expected file type, but directory type for $fn";
		return 0;
	}

	//check parent
	$parentId = "";
	$parentPath="";
	
	if (!empty($file)){

        // get parent dir
    	if (isset($file['parentDir']) && $file['parentDir'] != "0" ){
	    	$parentId= $file['parentDir'];
    	}else{
    		$filePath  = $file['path'];
    		$parentPath = dirname($filePath);
    		if ($parentPath == ".")
                $parentPath=$_SESSION['User']['id'];
            $parentId  = getGSFileId_fromPath($parentPath,$asRoot);
    
        }
        // check parent dir
    	if (!$parentId or !isGSDirBNS($GLOBALS['filesCol'], $parentId)){
    		$_SESSION['errorData']['mongoDB'][] = " Cannot remove $filePath. 'parentPath' ($parentId)  is not a directory.";
    		return 0;
    	}   
    	if ( ($parentPath == $_SESSION['User']['id'] || $parentId == "0") && !$asRoot){
    		$_SESSION['errorData']['mongoDB'][] = " Cannot remove home directory.";
    		return 0;
    	}   
    
    	// delete file
    	$GLOBALS['filesCol']->deleteOne(array('_id'=> $fn));
    	$GLOBALS['filesMetaCol']->deleteOne(array('_id'=> $fn));

        // update parent dir
	    $GLOBALS['filesCol']->updateOne(
			array('_id'=> $parentId),
			array('$pull' => array("files"=>$fn))
        );
	}
	return 1;
}

// delete directory registry

function deleteGSDirBNS($fn,$asRoot=0,$force=false){
	if ($asRoot == 1)
		$dir  = $GLOBALS['filesCol']->findOne(array('_id' => $fn));
	else
		$dir  = $GLOBALS['filesCol']->findOne(array('_id' => $fn, 'owner' => $_SESSION['User']['id']) );

	if (!isset($dir['parentDir'])){
		$_SESSION['errorData']['mongoDB'][]= " Cannot find parent directory attribute for $fn . </br> <a href=\"javascript:history.go(-1)\">[ OK ]</a>";
	    return 0;
    }
    
	$parentId= $dir['parentDir'];

	if ( $parentId == "0" && !$asRoot){
		$_SESSION['errorData']['mongoDB'][]= " Cannot remove home directory.";
		return 0;
	}

	foreach ($dir['files'] as $f ){
		if ( isGSDirBNS($GLOBALS['filesCol'], $f) ){
			$r = deleteGSDirBNS($f,1,$force);
		}else{
			$r = deleteGSFileBNS($f,1,$force);
		}
		if ($r == 0)
			return 0;
	}

	$GLOBALS['filesCol']->deleteOne(array('_id'=> $fn));
	$GLOBALS['filesMetaCol']->deleteOne(array('_id'=> $fn));

	$GLOBALS['filesCol']->updateOne(
				array('_id'=> $parentId),
				array('$pull' => array("files"=>$fn))
		  	);
	return 1;
}


function saveGSDirBNS($dir,$outDir) {
	$dirObj = $GLOBALS['filesCol']->findOne(array('_id' =>$dir,  'owner'=>$_SESSION['User']['id'] ));
	if (empty($dirObj) || !isset($dirObj['files']) ){
		$_SESSION['errorData']['mongoDB'][]="Cannot extract $dir from database. It is not a directory of your workspace";
		return 0;
	}
	if (! is_dir($outDir)){
	   exec("mkdir $outDir 2>&1",$output);
		   if ($output){
		   	$_SESSION['errorData']['mongoDB'][] = implode(" ", $output)."</br> <a href=\"javascript:history.go(-1)\">[ OK ]</a>";
		return 0;
	   }
	}
	
	foreach($dirObj['files'] as $f){
		if (isGSDirBNS($GLOBALS['filesCol'], $f)){	
		$outDirSub = $outDir."/".basename($f);
		$r = saveGSDirBNS($f,$outDirSub);
		if ($r == 0)
			break;
		}else{
	 	$outTmp = "$outDir/".basename($f);
		saveGSFile($GLOBALS['grid'],$f,$outTmp);
		if (! is_file($outTmp)){
			$_SESSION['errorData']['mongoDB'][]="Cannot extract $dir from database. Inner $f not written in temporal dir $outTmp . ";
			return 0;
		}
		}
	}
	return 1;
}


//
//

	
function printGSFile($col, $fn, $mime = '', $sendFn = False) {
	$file = $col->findOne($fn);
	if (!$file->file['_id'])
		return 1;
	if ($mime)
		header('Content-type: ' . $mime);
	if ($sendFn)
		header('Content-Disposition: attachment; filename="' . $fn . '"');
	print($file->getBytes());
	return 0;
}

function getGSFileSmall($col, $fn) {
	$file = $col->findOne(array('filename' => $fn));
	if (empty($file)){
		print errorPage('File Not Found', 'File id ' . $fn . ' not found');
		exit;
	}else{
		return $file->getBytes();
	}
}
function getGSFile($col, $fn) {
	$file = $col->findOne(array('filename' => $fn));
	if (empty($file)){
		print errorPage('File Not Found', 'File id ' . $fn . ' not found');
		exit;
	}else{
		$content ="";
		$stream = $file->getResource();
		while(!feof($stream)){
			$buff = fread($stream, 1024);
			print $buff;
			ob_flush();
			flush();
		}
	}
}

function saveGSFile($col,$fn,$outFn) {
	$file = $col->findOne(array('filename' => $fn));
	if (empty($file)) {
		print errorPage('File Not Found', 'Cannot save file ' . $fn . ' . File not found');
		exit;
	}
	$file->write($outFn);
	return 0;
}

function calcGSUsedSpace ($id) {
/*
	$ops = array(
				array('$match' => array('owner' => $id)),
				array('$group'=> array(
					'_id'=>'$owner',
					'size'=> array('$sum'=>'$size')
				)
			)
		);
    #$d = $GLOBALS['filesCol']->aggregate($ops,array('cursor' => array('batchSize' => 101)));
    $d = $GLOBALS['filesCol']->aggregateCursor($ops);
    #$d = $GLOBALS['filesCol']->aggregateCursor($ops);
    return $d['result'][0]['size']+0
*/

    
    $files = $GLOBALS['filesCol']->find(array('owner' => $id))->toArray();
    $size=0;
    foreach ($files as $f){
        if (isset($f['type']) && $f['type'] == "dir")
            continue;
        $size+=$f['size'];
    }
    return $size;
    

}

// sums file sizes down from a given dir

function calcGSUsedSpaceDir ($fn) {
    /*
	$ops = array(
				array('$match' => array('parentDir' => $fn)),
				array('$group'=> array(
					'_id'=>'$parentDir',
					'size'=> array('$sum'=>'$size')
				)
			)
		);
	$d = $GLOBALS['filesCol']->aggregate($ops);
	if (!count($d['result']))
		return 0;
	else
        return $d['result'][0]['size']+0.;
     */

    $files = $GLOBALS['filesCol']->find(array('parentDir' => $fn))->toArray();
    $size=0;
    foreach ($files as $f){
        $size+=$f['size'];
    }
    return $size;
}


// store file content into GRID

function uploadGSFile($col,$fn,$fsFile) {
   $path= pathinfo($fsFile,PATHINFO_DIRNAME);

   if(file_exists($fsFile)){
   	chdir($path);
   	$col->deleteOne(array('filename' => $fn));
	//exec("cd $path;mongofiles -h mmb.pcb.ub.es -u dataLoader -p mdbwany2015 --authenticationDatabase admin -d restcastemp -r put $fn");
	//$col->storeBytes(file_get_contents("$fsFile"), array('filename'=>$fn));
	$col->storeFile($fsFile,array('filename'=>$fn));
	return 1;
   }else{
	$_SESSION['errorData']['mongoDB'][]= 'File ' . $fn . ' not stored. Temporal '. $fsFile . ' not found';
	return 0;
   }
}

// create unique file id

function createLabel(){
        $label= uniqid($_SESSION['User']['id']."_",TRUE);
        if (! empty($GLOBALS['filesCol']->findOne(array('_id' => $label))) ){
                $label= uniqid($_SESSION['User']['id']."_",TRUE);
        }
        return $label;
}
