<?php 

// system call in a subprocess
function subprocess($cmd, &$stdout=null, &$stderr=null,$cwd=null) {
        $proc = proc_open($cmd,[
                1 => ['pipe','w'],
                2 => ['pipe','w'],
                ],$pipes,$cwd,null);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        return proc_close($proc);
}

/*
// create random string uses as salt for crypting password
function randomSalt( $length ) {
    $possible = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $str = '';
    while (strlen($str) < $length)
        $str .= substr($possible, (rand() % strlen($possible)), 1);

    return $str;
}
 */

function mkpath($path){
    if (@mkdir($path) or file_exists($path)) {
        return true;
    }
    return mkpath(dirname($path)) and mkdir($path,0777);
}


// define JBrowse tracktype from file format
function format2trackType($format,$fn=NULL){
        if (!isset ($format) ){
                return FALSE;
        }
        $program = NULL;
        if ($fn){
                if (preg_match('/^([A-Z]+)_/',basename($fn),$m) ){
                        $program = $m[1];
                }
        }
        switch ($format){
        case "BAM":
                $type = "BAM";
                break;
        case "GTF":
        case "GFF":
        case "GFF3":
                if ($program && $program == "ND"){
                        $type = "GFF_ND";
                }elseif($program && $program == "NR"){
                        $type = "GFF_NR";
                }elseif($program && $program == "TSS"){
                        $type = "GFF_TX";
                }elseif($program && $program == "P"){
                        $type = "GFF_P";
                }elseif($program && $program == "NFR"){
                        $type = "GFF_NFR";
                }elseif($program && $program == "STF"){
                        $type = "GFF_GAU";
                }else{
                        $type = "GFF";
                }
                break;
        case "BW":
        //case "BEDGRAPH":
        //case "WIG":
                if ($program && $program == "P"){
                        $type = "BW_P";
                }else{
                        $type = "BW";
                }
                break;
        default:
                $type = 0;
        }
        return $type;
}


function createLink ($source, $target){
	if (is_file($source))
                 unlink($source);
	if (is_file($target) || is_link($target))
        	unlink($target);
	touch($source);
	symlink($source,$target);
}



//return html from
//SESSION['errorData'] = Array( 'seccionA' => Array( 'error msg A1', 'error msg A2'))
function printErrorDivision(){
        // unset empty error sections
        if (isset($_SESSION['errorData'])) {
                foreach ($_SESSION['errorData'] as $subTitle => $txts){
                        if (count($txts) == 0){
                                unset($_SESSION['errorData'][$subTitle]);
                        }
                }
        }
        // print PHP ERROR MESSAGES
        if (isset($_SESSION['errorData']) && $_SESSION['errorData']) {
                $errType= "alert";
                if (isset($_SESSION['errorData']['Info'])) {
                        $errType= "alert alert-info";
                } else {
                        $errType= "alert alert-warning";
                }
                print "<div class=\"$errType\">";
                foreach ($_SESSION['errorData'] as $subTitle => $txts) {
                        print "$subTitle<br/>"; 
                        foreach ($txts as $txt) {
                                print "<div style=\"margin-left:20px;\">$txt</div>";
                        }
                }
                unset($_SESSION['errorData']);
                print "</div>";
        }
}

//return html from
//SESSION['errorData'] = Array( 'seccionA' => Array( 'error msg A1', 'error msg A2'))
function printErrorData($targetSeccion=0){
   $txt='';
    foreach ($_SESSION['errorData'] as $seccion =>$lines) {
	    if ($targetSeccion && $targetSeccion != $seccion )
    		continue;
        $txt .='<b>'.$seccion.'</b></br>';
        if (!is_array($lines))
            $lines[0] = $lines;
    	$txt .= '<span style=\"margin-left:45px;\"></span>';
    	$txt .= join('<br/><span style=\"margin-left:45px;\"></span>',$lines);
    	$txt .= '<br/>';
    }
    unset($_SESSION['errorData']);
    return $txt;
}


function printFilePath_fromPath($path,$asRoot=0){

    $path = str_replace(array("\\\\","\\/", "//", "\\/","/\\"), "/", $path);

    // parse file path
    
    $p = explode("/", $path);

    $filePath = "";
    $projDir  = "";
    $userDir  = "";
    $proj=array();

    // first path element - userId
    if (preg_match('/^'.$GLOBALS['AppPrefix'].'/',$p[0]) ){
        $userDir = array_shift($p);
    }else{
        $_SESSION['errorData']['Warning'][]=" Cannot show file '$path'. It has an invalid path.";
    }
    // if path has project Dir 
    if (preg_match('/__PROJ/',$p[0]) ){
        $projDir  = array_shift($p);
        $filePath = $p;
        if (isProject($projDir,$asRoot,$userDir))
            $proj = getProject($projDir,$asRoot,$userDir);
        if (count($proj)==0){
            $_SESSION['errorData']['Warning'][]=" Cannot show file '$path'. The assigned project '$projDir' does not exists or is unaccessible";
            $proj = array("name" => $projDir);
        }

    // if path has NO project Dir 
    }else{
        $_SESSION['errorData']['Internal'][]="Error: incorrect file. Path contains no project ($path)";
        $proj = array("name" => "Foo Project");
        $filePath = $p;
    }
    $html = '
			<ul class="feeds" id="list-files-run-tools">
            <li class="tool-122 tool-list-item">
			<div class="col1">
              <div class="cont">
				   <div class="cont-col1">
					  <div class="label label-sm label-info"><i class="fa fa-file"></i></div>
				   </div>
				   <div class="cont-col2">
                      <div class="desc">
                        <span class="text-info" style="font-weight:bold;"> '.$proj['name'].'<i class="fa fa-caret-right"></i></span>
                        <span style="color:#495a6d;font-weight:bold"> '.$filePath[0].' </span>
                ';
    for ($i=1;$i<count($filePath);$i++){
        $html .= " / ".$filePath[$i];
    }
    $html .= '      </div>
                  </div>
               </div>
            </div>
            </li>
            </ul> ';
    return $html;
}


    
   
function fromPrefix2Program($prefix){

        $tools   = $GLOBALS['toolsCol']->find(array('prefix' => array('$exists'=> true)));
	if (empty($tools)){
		$_SESSION['errorData']['Error'][]="Internal Error. Cannot extract any prefix from 'tools' collection";
		return 0;
	}
	foreach ( $tools as $toolId => $d ){
		if ($d['prefix'] == $prefix)
			return $d['title'];
	}
	$_SESSION['errorData']['Warning'][]=" Prefix '$prefix"."_' not registered. File descriptions may not be complete.";
	return 0;
}


// build text description for running jobs in datatables
function getJobDescription($descrip0,$jobSGE,$lastjobs){

	$descrip = ($descrip0?$descrip0."<br/>":"");
        if ($jobSGE['state'] == "RUNNING"){
		$descrip = "<b>Job in course</b><br/>".$descrip;

	}elseif($jobSGE['state'] == "HOLD"){
	        $descrip .= "<br><strong>Job waiting</strong>";

		# get info for dependent jobs from lastjobs
	        if (isset($jobSGE['jid_predecessor_list'])){
	                $depText = "";  
	               $depPids = explode(",",$jobSGE['jid_predecessor_list']);
	               foreach ($depPids as $depPid){
	                        if (isset($lastjobs[$depPid])){
	                                $depText.=basename($lastjobs[$depPid]['out'][0])." ";
	                        }
	                }
	                if ($depText)
	                        $descrip .= " for predecessor analyses to finish: $depText";
	        }

	}elseif($jobSGE['state'] == "ERROR"){
	        $descrip .= " Job in error.";
	        if (isset($jobSGE['error reason    1'])){
	                $descrip .= "<br/>".$jobSGE['error reason    1'];
	        }
	}
	return $descrip;
}

// build text description from systematic execution file names
function getDescriptionFromFN ($fn,$prefix=0){
        $descr  = "";
	$ext = strtoupper(pathinfo($fn,PATHINFO_EXTENSION));
	if (!$prefix){
	   if (preg_match('/([A-Z]+)_.+\.(\w+)$/',$fn,$m)){
                $prefix = $m[1];
                $ext = strtoupper($m[2]);
	   }
	}
	if ($ext){
	    switch ($ext){
               	case "SH":
			$descr="Execution file";
			break;
               	case "LOG":
			$descr="Log file";
			break;
                case "GFF":
                case "BW":
			$descr="Result file";
			break;
                case "PNG":
			$descr="Image file";
			break;
                case "BAM":
			$descr="BAM file";
			if (!$prefix)
                		$descr.=" is being sorted (if needed), indexed, and preprocessed for running Nucleosome Dynamics ";
			break;
                default:
			$descr="$ext file";
	    }
            if (preg_match('/E\d+/',$ext)){
		$descr= "ERROR file";
	    }
	}
	if ($prefix){
	    $program = fromPrefix2Program($pre);
            if ($program)
            	$descr.= " from  $program";
	}
        return $descr;
}



//adds regular metadata
function saveMetadataUpload($fn,$request,$validationState){

        // filters known metadata fields
        $insertMeta = prepMetadataUpload($request,$validationState);

        // save to mongo        
        $r = modifyMetadataBNS($fn,$insertMeta);
        return $r;
}


// filters uploadForm2 request and formats mongo file metadata
function prepMetadataUpload($request,$validationState=0){
        $fnPath    = getAttr_fromGSFileId($fn,'path');

        $format      = (isset($request['format'])?$request['format']:"UNK");
        $data_type   = (isset($request['data_type'])?$request['data_type']:NULL);
        $input_files = (isset($request['input_files'])?$request['input_files']:Array(0));
        $validated   = $validationState;
        $visible     = (isset($insertMeta['visible'])?$insertMeta['visible']:true);

        // compulsory metadata
        $insertMeta=array(
            'format'     => $format,
            'validated'  => $validated,
	    'data_type'  => $data_type,
            'input_files'=> $input_files,
            'visible'    => $visible,
        );
        // GFF, BAM, BW,.. metadata
        if (isset($request['taxon_id']))    {if($request['taxon_id'] == ""){$request['taxon_id']=0;};
		$insertMeta['taxon_id']   = $request['taxon_id'];}
        if (isset($request['refGenome']))   {$insertMeta['refGenome']  = $request['refGenome'];}
        // BAM metadata
        if (isset($request['paired']))      {$insertMeta['paired']     = $request['paired'];}
        if (isset($request['sorted']))      {$insertMeta['sorted']     = $request['sorted'];}
        if (isset($request['description'])) {$insertMeta['description']= $request['description'];}
        //  results metadata
        if (isset($request['submission_file'])){$insertMeta['submission_file']= $request['shFile'];}
        if (isset($request['log_file']))       {$insertMeta['log_file'] = $request['logFile'];}


        return  $insertMeta;
}

function setVREfile_fromScratch($file_data=array()){
	$file     = Array();
	$metadata = Array();

    //set file
    if (!isset($file_data['_id'])){
        $file['_id']= uniqid("unique_file_id_",TRUE);
    }else{
        $file['_id']= $file_data['_id'];
    }
    if (!isset($file_data['type'])){
        $file['type']= "file";
    }else{
        $file['type']= $file_data['type'];
        unset($file_data['type']);
    }
    if (!isset($file_data['owner'])){
        $file['owner']= "user_id";
    }else{
        $file['owner']= $file_data['owner'];
        unset($file_data['owner']);
    }
    if (!isset($file_data['size'])){
        $file['size']= 0;
    }else{
        $file['size']= $file_data['size'];
        unset($file_data['size']);
    }
    if (!isset($file_data['project'])){
        $file['project']= "my_project_id";
    }else{
        $file['project']= $file_data['project'];
        unset($file_data['project']);
    }
    if (!isset($file_data['path'])){
        $file['path']= $file['owner']."/".$file['project']."/uploads/myinfile.txt";
    }else{
        $file['path']= $file_data['path'];
        unset($file_data['path']);
    }
    if (!isset($file_data['mtime'])){
        $file['mtime']= new MongoDB\BSON\UTCDateTime(strtotime("now")*1000);
    }else{
        $file['mtime']= $file_data['mtime'];
        unset($file_data['mtime']);
    }
    if (!isset($file_data['atime'])){
        $file['atime']= new MongoDB\BSON\UTCDateTime(strtotime("now")*1000);
    }else{
        $file['atime']= $file_data['atime'];
        unset($file_data['atime']);
    }
    if (!isset($file_data['parentDir'])){
        $file['parentDir']= uniqid("unique_file_id_",TRUE);
    }else{
        $file['parentDir']= $file_data['parentDir'];
        unset($file_data['parentDir']);
    }
    if (!isset($file_data['lastAccess'])){
        $file['lastAccess']= new MongoDB\BSON\UTCDateTime(strtotime("now")*1000);
    }else{
        $file['lastAccess']= $file_data['lastAccess'];
        unset($file_data['lastAccess']);
    }

	//set metadata
    if (!isset($file_data['_id'])){
        $metadata['_id']= $file['_id'];
    }else{
        $metadata['_id']= $file_data['_id'];
		unset($file_data['_id']);
    }
	if (isset($file_data['meta_data'])){
		foreach ($file_data['meta_data'] as $k => $v){
			$metadata[$k]=$v;
		}
		unset($file_data['meta_data']);
	}
    if (!isset($file_data['file_type']) && !isset($file_data['format'])){
		$metadata['format'] = "TXT";
    }elseif (isset($file_data['file_type'])){
		$metadata['format'] = $file_data['file_type'];
        unset($file_data['file_type']);
    }elseif (isset($file_data['format'])){
		$metadata['format'] = $file_data['format'];
        unset($file_data['format']);
    }
    if (!isset($file_data['data_type'])){
		$metadata['data_type'] = "other";
    }else{
		$metadata['data_type'] = $file_data['data_type'];
        unset($file_data['data_type']);
    }
	foreach ($file_data as $k=>$v){
		$metadata[$k]=$v;
    }

    return array($file,$metadata);
}

function getVREfile_fromFile($mugfile){
	$file     = Array();
	$metadata = Array();

	//set file
    if (isset($mugfile['_id'])){
        $file['_id']= $mugfile['_id'];
    }
	if (isset($mugfile['type'])){
		$file['type']= $mugfile['type'];
		unset($mugfile['type']);
	}
	if (isset($mugfile['file_path'])){
		$file['path']= $mugfile['file_path'];
		unset($mugfile['file_path']);
	}
	if (isset($mugfile['creation_time'])){
		$file['mtime']= $mugfile['creation_time'];
		unset($mugfile['creation_time']);
	}
	if (isset($mugfile['user_id'])){
		$file['owner']= $mugfile['user_id'];
		unset($mugfile['user_id']);
	}else{
		$file['owner']= $_SESSION['User']['id'];
	}
	if (isset($mugfile['meta_data']['expiration'])){
		$file['expiration']= $mugfile['meta_data']['expiration'];
		unset($mugfile['meta_data']['expiration']);
	}
	if (isset($mugfile['meta_data']['files'])){
		$file['files']= $mugfile['meta_data']['files'];
		unset($mugfile['meta_data']['files']);
	}
	if (isset($mugfile['meta_data']['parentDir'])){
		$file['parentDir']= $mugfile['meta_data']['parentDir'];
		unset($mugfile['meta_data']['parentDir']);
	}

	//set metadata
    if (isset($mugfile['_id'])){
        $metadata['_id']= $mugfile['_id'];
		unset($mugfile['_id']);
    }
	if (isset($mugfile['meta_data'])){
		foreach ($mugfile['meta_data'] as $k => $v){
			$mugfile[$k]=$v;
		}
		unset($mugfile['meta_data']);
	}
	if (isset($mugfile['file_type'])){
		$metadata['format'] = $mugfile['file_type'];
		unset($mugfile['file_type']);
	}
	if (isset($mugfile['assembly'])){
		$metadata['refGenome'] = $mugfile['assembly'];
		unset($mugfile['assembly']);
	}
	foreach ($mugfile as $k=>$v){
		$metadata[$k]=$v;
	}

	return Array($file,$metadata);
}

//formats and completes mongo file metadata from $meta and $lastjob
function prepMetadataResult($meta,$fnPath=0,$lastjob=Array() ){

        if ($fnPath){
                $extension = pathinfo("$fnPath",PATHINFO_EXTENSION);
                $extension = preg_replace('/_\d+$/',"",$extension);
	        if (preg_match('/^E\d+$/',strtoupper($extension)) )
	                $extension="ERR";
        }

        if (!isset($meta['format']) && $fnPath)
                $meta['format']= strtoupper($extension);
        
        if (!isset($meta['input_files']) && isset($lastjob['input_files']) ){
            $input_ids = array();
            array_walk_recursive($lastjob['input_files'], function($v, $k) use (&$input_ids){ $input_ids[] = $v; });
            $input_ids = array_unique($input_ids);
            $meta['input_files']=$input_ids;
        }

        if (!isset($meta['submission_file']) && isset($lastjob['submission_file']) )
                $meta['submission_file']=$lastjob['submission_file'];

        if (!isset($meta['submission_file']) && isset($lastjob['shPath']) )
                $meta['submission_file']=$lastjob['shPath'];

        if (!isset($meta['log_file']) && isset($lastjob['log_file']) )
                $meta['log_file']=$lastjob['log_file'];

        if (!isset($meta['log_file']) && isset($lastjob['log_file']) )
                $meta['log_file']=$lastjob['log_file'];

        if (!isset($meta['tool']) && isset($lastjob['tool']))
                $meta['tool']=$lastjob['tool'];
        if (!isset($meta['tool']) && isset($lastjob['toolId']))
                $meta['tool']=$lastjob['toolId'];


        if (!isset($meta['refGenome']) && in_array($meta['format'],array("BAM","GFF","GFF3","BW")) ){
            if (isset($meta['input_files']) ){
                $inp = $meta['input_files'][0];
                $inpObj = $GLOBALS['filesMetaCol']->findOne(array('path'  => $inp));
                if (!empty($inpObj) && isset($inpObj['refGenome']) ){
                        $meta['refGenome']= $inpObj['refGenome'];
                }
            }
            if (!isset($meta['refGenome']) && $fnPath ){
                $fnCore   = "";
                $refGenome= "";
                $ext = $meta['format'];
                if (preg_match("/^[A-Z]+_\(\w+\)(.+)-\(\w+\)(.+)\.$ext/i",basename($fnPath),$m)) {
                        $fnCore = $m[1];
                }elseif (preg_match("/^[A-Z]+_\(\w+\)(.+)-/",basename($fnPath),$m)) {
                         $fnCore = $m[1];
                }elseif (preg_match("/^[A-Z]+_(.+)\.$ext/i",basename($fnPath),$m)) {
                        $fnCore = $m[1];
                }elseif (preg_match("/^[A-Z]+_(.+)\./i",basename($fnPath),$m)) {
                        $fnCore = $m[1];
                }else{
                        $fnCore = preg_replace("/.$ext/i","",basename($fnPath));
                        $fnCore = preg_replace("/^.*_/","",$fnCore);
                }
                $reObj = new MongoRegex("/".$_SESSION['User']['id'].".*".$fnCore."/i");
                $relatedBAMS = $GLOBALS['filesMetaCol']->find(array('path'  => $reObj));
                if (!empty($relatedBAMS)){
                       $relatedBAMS->next();
                       $BAM = $relatedBAMS->current();
                       if (!empty($BAM))
                                $meta['refGenome'] = $BAM['refGenome'];
                }
            }
        }
	if (!isset($meta['description']) ){
		$prefix = 0;
		if ($meta['tool']){
			$tool = $GLOBALS['toolsCol']->findOne(array('_id' => $meta['tool']));
			$prefix = $tool['prefix'];
		}
		$meta['description'] = getDescriptionFromFN(basename($fnPath),$prefix);
	}
        if (!isset($meta['validated'])){
                $meta['validated']=1;
        }
        if (!isset($meta['visible']) && $fnPath){
                $meta['visible']=((in_array($extension,$GLOBALS['internalResults']))?0:1);
        }
        return $meta;
}

//completes $meta for log files based on expected outfile
function prepMetadataLog($metaOutfile,$logPath=0){
        $metaLog = $metaOutfile;
        if (!isset($metaLog['format']   )){ $metaLog['format']    = "LOG";}
        if (!isset($metaLog['data_type'])){ $metaLog['data_type'] = "data_log";}
        $metaLog['validated'] = true;
        $metaLog['visible']   = true;
        return $metaLog;
}


function validateMugFile($file,$is_output=false){

    $val_score=0; # 0 = no valid file; 1 = no valid but remediable; 2 = valid file

	if (!isset($file['type']))
		$file['type']= "file";

	if ($file['type']=="dir"){
		if (!isset($file['meta_data']['files'])){
			$file['meta_data']['files']=Array();
			//$_SESSION['errorData']['Error'][]= "Invalid MuG Directory. Attribute 'meta_data->files' is required when 'type=dir'.";	
			//return array($val_score, $file);
		}
	}elseif($file['type']=="file" ){
		if (!isset($file['file_path']) || !isset($file['file_type']) || !isset($file['data_type']) ){
			$_SESSION['errorData']['Error'][]= "Invalid File. Attributes 'file_path','file_type' and 'data_type' are required.";
			return array($val_score, $file);
		}
	}

	if (!isset($file['meta_data']))
		$file['meta_data']=Array();

	if (!isset($file['compressed']))
		$file['compressed']=false;
	
	if (!isset($file['sources'])){
		if (isset($file['meta_data']['tool'])){
	         	$_SESSION['errorData']['Warning'][]="Invalid File. Attribute 'sources' required if metadata 'tool' is set";
			$val_score= 1;
			return array($val_score, $file);
		}else{
			$file['sources']=Array(0);
		}
	}
	if (!isset($file['meta_data']['visible']))
		$file['meta_data']['visible']=true;
	
	if ($is_output){
            if (!isset($file['meta_data']['tool'])){
		//TODO tool value is a valid tool_id
		$_SESSION['errorData']['Error'][]= "Invalid File. Attribute 'meta_data->tool' required if file is a tool output";
           	$val_score= 1;
		return array($val_score, $file);
	    }
	    if (!isset($file['meta_data']['validated'])){
            	$file['meta_data']['validated']=true;
            }
	}
	return array(2,$file);
}


function output_is_required($out_def){
	if (isset($out_def['required']))
		return $out_def['required'];
	else
		return false;
}		

function output_allow_multiple($out_def){
	if (isset($out_def['allow_multiple']))
		return $out_def['allow_multiple'];
	else
		return false;
}
function rutime($ru, $rus, $index) {
    return ($ru["ru_$index.tv_sec"]*1000 + intval($ru["ru_$index.tv_usec"]/1000))
     -  ($rus["ru_$index.tv_sec"]*1000 + intval($rus["ru_$index.tv_usec"]/1000));
}

// Merge 2 multidimentional arrays joining common keys

function array_merge_recursive_distinct(array &$array1, array &$array2){
    $merged = $array1;
    foreach ($array2 as $key => &$value) {
        if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])){
            $merged[$key] = array_merge_recursive_distinct($merged[$key], $value);
        }else{
            $merged[$key] = $value;
        }
    }
    return $merged;
}

// Converts multidimentional array (arr) into 2D array
// Can mantain key names using the dot notation: (key.subkey.subsubkey)

function flattenArray($arr,$dot_keynames=true,$narr = array(), $nkey = '') {
    foreach ($arr as $key => $value) {
        if ($dot_keynames){
            	if (is_array($value)) {
                    $narr = array_merge($narr, flattenArray($value, $dot_keynames, $narr, $nkey . $key . '.'));
                } else {
                    $narr[$nkey . $key] = $value;
                }
        
        }else{
            	if (is_array($value) && count($value)) {
                    $narr = array_merge($narr, flattenArray($value, $dot_keynames, $narr, ''));
                } else {
                    $narr[$key] = $value;
                }
        }
    }
    return $narr;
}


function getCurrentCloud (){
	$cloud=array();
	foreach ($GLOBALS['clouds'] as $cloudName => $c){
	    if ($_SERVER['HTTP_HOST'] == $c['http_host']) //PHP_URL_HOST);
  		$cloud=$c;
	}
	if (!$cloud){
		$_SESSION['ErrorData']['Error'][]="Cannot guess current cloud based on http_host='".$_SERVER['HTTP_HOST']."'. Some job execution will fail";
		return 0;
	}else{
		return $cloud;
	}
}

// HTTP post
function post($data,$url,$headers=array(),$auth_basic=array()){

		$c = curl_init();
		curl_setopt($c, CURLOPT_URL, $url);
		curl_setopt($c, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
		curl_setopt($c, CURLOPT_POST, 1);
		#curl_setopt($c, CURLOPT_TIMEOUT, 7);
		curl_setopt($c, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($c, CURLOPT_POSTFIELDS, $data);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        if (count($headers))
            curl_setopt($c, CURLOPT_HTTPHEADER, $headers);
        if (isset($auth_basic['user']) && isset($auth_basic['pass']))
            curl_setopt($c, CURLOPT_USERPWD, $auth_basic['user'].":".$auth_basic['pass']);
            
		$r = curl_exec ($c);
        $info = curl_getinfo($c);

		if ($r === false){
			$errno = curl_errno($c);
			$msg = curl_strerror($errno);
            $err = "POST call failed. Curl says: [$errno] $msg";
		    $_SESSION['errorData']['Error'][]=$err;	
			return array(0,$info);
		}
		curl_close($c);

		return array($r,$info);
}

// HTTP get
function get($url,$headers=array(),$auth_basic=array()){

		$c = curl_init();
        curl_setopt($c, CURLOPT_URL, $url);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        if (isset($_SERVER['HTTP_USER_AGENT'])){                      curl_setopt($c, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);}
        if (count($headers)){                                         curl_setopt($c, CURLOPT_HTTPHEADER, $headers);}
        if (isset($auth_basic['user']) && isset($auth_basic['pass'])){curl_setopt($c, CURLOPT_USERPWD, $auth_basic['user'].":".$auth_basic['pass']);}
            
		$r = curl_exec ($c);
		$info = curl_getinfo($c);

		if ($r === false){
			$errno = curl_errno($c);
			$msg = curl_strerror($errno);
            $err = "GET call failed. Curl says: [$errno] $msg";
		    $_SESSION['errorData']['Error'][]=$err;	
			return array(0,$info);
		}
		curl_close($c);

		return array($r,$info);
}

// HTTP put
function put($data,$url,$headers=array(),$auth_basic=array()){

	$c = curl_init();
        curl_setopt($c, CURLOPT_URL, $url);
        curl_setopt($c, CURLOPT_CUSTOMREQUEST, "PUT");
	curl_setopt($c, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($c, CURLOPT_POSTFIELDS, $data);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        if (count($headers))
            curl_setopt($c, CURLOPT_HTTPHEADER, $headers);
        if (isset($auth_basic['user']) && isset($auth_basic['pass']))
            curl_setopt($c, CURLOPT_USERPWD, $auth_basic['user'].":".$auth_basic['pass']);
            
		$r = curl_exec ($c);
		$info = curl_getinfo($c);

		if ($r === false){
			$errno = curl_errno($c);
            $msg = curl_strerror($errno);
            $err = "PUT call failed. Curl says: [$errno] $msg";
		    $_SESSION['errorData']['Error'][]=$err;	
			return array(0,$info);
		}
		curl_close($c);

		return array($r,$info);
}

function is_url($url){
    $regex = "((https?|ftps?)\:\/\/)?"; 
    $regex .= "([a-z0-9+!*(),;?&=\$_.-]+(\:[a-z0-9+!*(),;?&=\$_.-]+)?@)?"; // User and Pass 
    $regex .= "([a-z0-9-.]*)\.([a-z]{2,3})"; // Host or IP 
    $regex .= "(\:[0-9]{2,5})?"; // Port 
    $regex .= "(\/([a-z0-9+\$_-]\.?)+)*\/?"; // Path 
    $regex .= "(\?[a-z+&\$_.-][a-z0-9;:@&%=+\/\$_.-]*)?"; // GET Query 
    $regex .= "(#[a-z_.-][a-z0-9+\$_.-]*)?"; // Anchor
    if(preg_match("/^$regex$/i", $url))
        return true;
    else
        return false;
    
    //return filter_var($url, FILTER_VALIDATE_URL);
}

function fromTaxonID2TaxonName($taxon_id){
    $taxonomy_ep = "https://www.ebi.ac.uk/ena/data/taxonomy/v1/taxon/tax-id";
    $url = "$taxonomy_ep/$taxon_id";
    list($resp,$info) = get($url);
    if (!$resp){
        return "Not found";
    }else{
        $resp = json_decode($resp);
        if ($resp->scientificName){
            return $resp->scientificName;  
        }else{
            return "Unknown";
        }
    }
}

function getFileExtension($fnPath){
    $fileExtension  = ""; // extensions are registered in file_types collection (i.e. FA)
    $fileCompression = 0; // 0 or compression type as appear in keys(GLOBALS[compression]) (i.e. 'TAR.GZ')
    $fileBaseName  = "";  // basename after removing extension/s (.i.e 'myfolder' from 'myfolder.tar.gz')

    $fileInfo = pathinfo($fnPath);
    $fileExtension = $fileInfo['extension'];
    $fileBaseName = $fileInfo['filename'];

    if (isset($fileExtension)){

	$fileExtension_ori = $fileExtension;
	$fileExtension     = preg_replace('/_\d$/',"",strtoupper($fileExtension_ori));

	$compressExtensions = preg_filter('/^/',".",array_map('strtoupper', array_keys($GLOBALS['compressions'])));

	if (in_array(".".$fileExtension, $compressExtensions )){

    		$fileCompression    = $fileExtension;
		// get real fileExtension (sql.gz)
		$fnPath2            = str_replace(".".$fileExtension_ori,"",$fnPath);
		$fileExtension2_ori = pathinfo($fnPath2, PATHINFO_EXTENSION);
		$fileBaseName       = pathinfo($fnPath2, PATHINFO_FILENAME);
		$fileExtension      = preg_replace('/_\d$/',"",strtoupper($fileExtension2_ori));
		
		// if real fileExtension is also a compression type, append it to fileCompression (tar.gz)
		if (in_array(".".$fileExtension,$compressExtensions )){
			$fileCompression = "$fileExtension.$fileCompression";
		}
	}
    }
    return array($fileExtension,strtolower($fileCompression),$fileBaseName);
}

function indexFiles_zip($zip_rfn){
    $files = array();

    // list zip files
    exec("unzip -l \"$zip_rfn\" 2>&1", $zip_out);

    if (!preg_grep('/Name/',$zip_out)){
        $_SESSION['errorData']['Error'][]= "Cannot read ZIP file content for '".basename($zip_rfn)."'";
        return array($files,$zip_out);
    }
    // parse zip output
    $zip_summary = array_pop($zip_out);
    foreach ($zip_out as $l){
        if (preg_match('/---/',$l) || preg_match('/Name/',$l) || preg_match('/Archive/',$l) ){
            continue;
        }
        $fields = preg_split('/ +/',$l);
        #  Length      Date    Time    Name
        #  ---------  ---------- -----   ----
        #  14158360  2017-02-22 10:35   by_cet1FLAG_glu_repeat1_ntsub_unsmo_posstrand.bigwig
        if (count($fields) == 5){
            $files[$fields[4]]['name'] = $fields[4];
            $files[$fields[4]]['time'] = $fields[3];
            $files[$fields[4]]['date'] = $fields[2];
            $files[$fields[4]]['size'] = $fields[1];
        }
    }
    return array($files,$zip_out);
}

function create_png_from_text($text="My Text",$png_file=FALSE){

    // setting image properties
    $img = array();
    $img['background'] = '008080';
    $img['color']      = 'FFF';
    $img['width']      = 252;
    $img['height']     = 252;

    $background = explode(",",hex2rgb($img['background']));
    $color = explode(",",hex2rgb($img['color']));
    $width = empty($img['width']) ? 100 : $img['width'];
    $height = empty($img['height']) ? 100 : $img['height'];
    $string = (string) isset($text) ? $text : $width ."x". $height;

    // set GD image object
    $image = @imagecreate($width, $height) or die("Cannot Initialize new GD image stream");
    $background_color = imagecolorallocate($image, $background[0], $background[1], $background[2]);
    $text_color = imagecolorallocate($image, $color[0], $color[1], $color[2]);
    // center text
    /*$text_box    = imagettfbbox(20, 45, "./arial.ttf", $string);
    $text_width  = $text_box[2]-$text_box[0];
    $text_height = $text_box[7]-$text_box[1];
    $x = ($width/2) - ($text_width/2);
    $y = ($height/2) - ($text_height/2);
    imagettftext($image, 20, 0, $x, $y, $text_color, "./arial.ttf", $string);*/

    imagestring($image, 5, $width/4, $height/2.5, $string, $text_color);

    // save image into file
    if ($png_file){
        imagepng($image,$png_file);
     }else{
        imagepng($image);
     }
    // del image object
    imagedestroy($image);
}

function hex2rgb($hex) {
    // Copied
   $hex = str_replace("#", "", $hex);

   switch (strlen($hex)) {
       case 1:
           $hex = $hex.$hex;
       case 2:
          $r = hexdec($hex);
          $g = hexdec($hex);
          $b = hexdec($hex);
           break;

       case 3:
          $r = hexdec(substr($hex,0,1).substr($hex,0,1));
          $g = hexdec(substr($hex,1,1).substr($hex,1,1));
          $b = hexdec(substr($hex,2,1).substr($hex,2,1));
           break;

       default:
          $r = hexdec(substr($hex,0,2));
          $g = hexdec(substr($hex,2,2));
          $b = hexdec(substr($hex,4,2));
           break;
   }

   $rgb = array($r, $g, $b);
   return implode(",", $rgb); 
}

function file_get_contents_chunked($file,$chunk_size,$callback){
	try	{
		$handle = fopen($file, "r");
		$i = 0;
		while (!feof($handle))
		{
			call_user_func_array($callback,array(fread($handle,$chunk_size),&$handle,$i));
			$i++;
		}
		fclose($handle);
	}
	catch(Exception $e) {
		 trigger_error("file_get_contents_chunked::" . $e->getMessage(),E_USER_NOTICE);
		 return false;
	}
	return true;
}

function indexArray($multiArray,$attr="_id"){
    $assocArray = array();
    foreach ($multiArray as $key => $value) {
            if (isset($value['_id'])  &&  !is_object($value['_id']) ) {
                $k = (string) $value['_id'];
                $assocArray[$k] = $value;
            }else{
                $assocArray[$key] = $value;
            }
    }
    return $assocArray;
}

 
