<?php

require __DIR__."/../../config/bootstrap.php";

redirectOutside();


$resp = Array (
	'fileName'  => "",
	'fileId'    => "", 
	'msg'       => "",
	'state'     => 0,
);

if(!$_REQUEST['op']){
	$resp['msg']="Internal error: Operation not received. Restart form<br/>";
	print json_encode($resp);
	die();
}
if(!$_REQUEST['fn']){
	$resp['msg']="Internal error: Select the file to validate.</br>";
	print json_encode($resp);
	die();
}elseif(is_array($_REQUEST['fn'])){
	$resp['msg']="Internal error: A list of files given. Expecting only one.</br>";
	print json_encode($resp);
	die();
}

// user project path
$userPath = getAttr_fromGSFileId($_SESSION['User']['dataDir'],"path");

// file to be processed

$fn     = $_REQUEST['fn'];
$fnPath = getAttr_fromGSFileId($fn,'path');
$rfn    = $GLOBALS['dataDir']."/".$fnPath;


$resp['fileId']   = $fn;
$resp['fileName'] = basename($fnPath);

if (! is_file($rfn)){
	$resp['msg'] ="Error: Cannot find file '".basename($fnPath)."' . Not stored in disk anymore.</br>";
	print json_encode($resp);
	die();
}

$fileData = $GLOBALS['filesCol']->findOne(array('_id' => $fn, 'owner' => $_SESSION['User']['id']));
$fileMeta = $GLOBALS['filesMetaCol']->findOne(array('_id' => $file));

if (empty ($fileData) ){
	$resp['msg'] ="Error: Cannot validate '".basename($fnPath)."'. File do not belong to the user currently logged.</br>";
	print json_encode($resp);
	die();
}

// define operations
switch ($_REQUEST['op']) {

  case '1':
    // check obligatory fields and build validation action list ($_SESSION['validation'])
    // returns file state and validation/error info
    // 0 = ERROR     - $_SESSION['errorData'] is set
    // 1 = VALIDATED - $_SESSION['validation'] is empty
    // 2 = READY     - $_SESSION['validation'] has pending actions
    // 3 = PROCESSING- process has been submitted

    unset($_SESSION['errorData']);

    // restart validation action list
    if (isset($_SESSION['validation'][$fn]))
	unset($_SESSION['validation'][$fn]);
    
    $resp['state'] = 1;
    	
    // check compulsory fields
    if (!isset($_REQUEST['format'])){
    	$resp['msg']="Missing compulsory fields. Please, especify file format.</br>";
        break;
    }
    $_SESSION['validation'][$fn]['format']=$_REQUEST['format'];


    // check validation actions to perfome on file
    switch ( $_REQUEST['format'] ) {

	case 'BAM':
        if (!isset($_REQUEST['refGenome']) || !isset($_REQUEST['paired']) || !isset($_REQUEST['sorted'])){
            $resp['msg']="Missing compulsory fields. Please, especify: reference genome, sorted/unsorted and paired/single.</br>";
        	$resp['state'] = 0;
            break;
        }
        if (($_REQUEST['sorted']!= "sorted" && $_REQUEST['sorted']!= 1) && (!isset($fileMeta['sorted']) || $fileMeta['sorted']=="unsorted")){
            $resp['msg']   = "The BAM file will be sorted and indexed.</br>";
		    $_SESSION['validation'][$fn]['action']["sort"] = 0;
    		$_SESSION['validation'][$fn]['action']["index"]= 0;
    		$resp['state'] = 2;

        }else{
            if (!is_file($rfn.".bai") ){
                $resp['msg']   = "The BAM file will be indexed.</br>";
		        $_SESSION['validation'][$fn]['action']["index"] = 0;
		        $resp['state'] = 2;
            }else{
                $resp['msg']   = "BAM file already indexed.</br>";
		        $resp['state'] = 1;
           }
        }
	    break;

    case 'BEDGRAPH';
	case 'WIG':
	case 'BED':
            if (!isset($_REQUEST['refGenome'])){
                $resp['msg']="Missing compulsory fields. Please, specify reference genome.</br>";
                $resp['state'] = 0;
                break;
            }
            //$resp['msg'] = "File will be converted to BW";
	        //$_SESSION['validation'][$fn]['action']["convert"]=0; 
	        break;

    case 'GFF':
	case 'GFF3':
            if (!isset($_REQUEST['refGenome'])){
                $resp['msg']="Missing compulsory fields. Please, specify reference genome.</br>";
        		$resp['state'] = 0;
                break;
            }
	        break;

    default:
            # other formats accepted as uploaded
    	    $resp['msg']   = "Metadata file is valid</br>";
	        $resp['state'] = 1;
            break;
    }


    // check formats and chrs names
    if ( $resp['state'] != 0 && $_REQUEST['refGenome']){
        $valid = validateUPLOAD($fn,$rfn,$_REQUEST['refGenome'],$_REQUEST['format']);

    	// add function error msgs to resp
        if (! $valid){
    	    $resp['msg']  .= printErrorData();
    	    $_SESSION['errorData']['error'][] = "File '".basename($fnPath)."' <b>not validated</b>. Please, mend your warnings/errors or upload the file again<br/>";
    	    $resp['state'] = 0;
    
    	// translate pending accions into nice msgs
    	}else{
    	    if (!isset($_SESSION['validation'][$fn]['action'])){
     	    	$resp['msg'] .= "Genomic coordinates successfully mapped against ".$_REQUEST['refGenome']." genome.<br/>";
    	    	$resp['state'] = 1;
	        }else{
    	    	$resp['state'] = 2;
        		if (isset($_SESSION['validation'][$fn]['action']['substitutions']) ){
    		        foreach ($_SESSION['validation'][$fn]['action']['substitutions'] as $sub => $r){
    		        	$resp['msg'] .= $_REQUEST['format']." chromosome name not in reference sequence. The following transformation will be performed: $sub<br/>";	
    		        }	
    		    }
    		    if (isset($_SESSION['validation'][$fn]['action']['enqueue_chrNames']) ){
    		        $resp['msg'] .= "Chromosome names in BAM will be validated. If they do not match the names of reference genome, your BAM will modified to do so (i.e. 'Chr2' => 'ChrII'). If no equivalent name can be found, the validation will fail.</br>";
    		    }
    	    }
        }
    }
    // set file state according to SESSION['errorData'] and SESSION['validation']
    if ( isset($_SESSION['validation'][$fn]['action'])){
    	$resp['state']= 2;  
    }

    if (isset($_SESSION['errorData'])){
    	$resp['state']= 0;
    }


    // save metadata if file already validated
    if ($resp['state'] == 1 ){
    	$ok = saveMetadataUpload($fn,$_REQUEST,1);
    	if (!$ok){
    		$resp['msg'] .= printErrorData();
    		$resp['state']= 0;
    	}else{
    		$resp['msg'] .= basename($fnPath). " successfully validated<br/>";
    	}
    }


/*
    print "SESSION - ERROR<br/>";
    var_dump($_SESSION['errorData']);
    print "SESSION - VALIDATION<br/>";
    var_dump($_SESSION['validation']);
    print "RESP<br/>";
    var_dump($resp);

 */
    break;


  case 'uncompress':
    break;


  case '2':
    // execute validation action list (reading $_SESSION['validation'])
    // returns file state and validation/error info
    // 0 = ERROR
    // 1 = READY
//$_SESSION['validation'] = array();
//$_SESSION['validation']["MuGUSER57ed26593bc58_5912f1dac6af46.42839482"]["format"] ="BAM";
//$_SESSION['validation']["MuGUSER57ed26593bc58_5912f1dac6af46.42839482"]["action"]["sort"]  = 0;
//$_SESSION['validation']["MuGUSER57ed26593bc58_5912f1dac6af46.42839482"]["action"]["index"] = 0;
//print "<br/>----------------CASE 2 : INI VALIDATION LIST-----<br/>";
//var_dump($_SESSION['validation']);

    if (!isset($_SESSION['validation'][$fn]) ){
    	$resp['msg']   = "Nothing else to do for '".basename($fnPath)."'! File will we set as valid.<br/>";
    	$resp['state'] = 1;
    }
    $format = $_SESSION['validation'][$fn]['format'];

    switch($format){

	case 'BAM':

      $subs   = get_seds_fromChrNameValidation($fn);
      $sort   = (isset($_SESSION['validation'][$fn]['action']['sort']            )? true:false);
      $index  = (isset($_SESSION['validation'][$fn]['action']['index']           )? true:false);
      $chrs   = (isset($_SESSION['validation'][$fn]['action']['enqueue_chrNames'])? true:false); #TODO !!!
	    
      if (count($subs) || $sort || $index || $chrs){
        $bamFn  = $rfn;
        //$dirFn  = str_replace("/".basename($fnPath),"",$fnPath);
        //$dirRfn = $GLOBALS['dataDir']."/".$dirFn;

		// prepare temporal dir
		$dirTmp   = $GLOBALS['dataDir']."/".$userPath."/".$GLOBALS['tmpUser_dir'];

        if (! is_dir($dirTmp)){
		   if(!mkdir($dirTmp, 0775, true)) {
			$_SESSION['errorData']['error'][]="Cannot create temporal file $dirTmp . Please, try it later.";
			$resp['state']=0;
			break;
		    }
        }
        //output file dir
		$output_dir  = $GLOBALS['dataDir']."/".$userPath."/uploads"; #$GLOBALS['dataDir']."/".$_SESSION['User']['id']."/uploads";

        // enqueue BAMval tool	
		$resp['state']=3;
		$toolId = "BAMval";
		//tool inputs -- should match tool registry
		$toolInputs = array("bam"=>array($fn));
		//tool arguments -- should match tool registry
        $toolArgs   = array(
                    "sort"    => $sort,
				    "index"   => $index,
				    "replace" => "\"".join(" | ",$subs) ."\""
				   );
		//tool outputs -- metadata saved in mongo until tool output registration phase
		$bamMeta = prepMetadataUpload($_REQUEST,$resp['state']);
		$bamMeta['associated_files']=array("$fnPath.bai");
		$bamMeta['assembly']=$bamMeta['refGenome'];
		unset($bamMeta['refGenome']);
		unset($bamMeta['taxon_id']);
		unset($bamMeta['data_type']);
		$bamMeta['validated']=true;
        $bamOut=array(
                "name"     => "bam",
				"file_path"=> $bamFn,
				"data_type"=> $_REQUEST['data_type'],
				"sources"  => array($bamFn),
				"taxon_id" => $_REQUEST['taxon_id'],
				"meta_data"=> $bamMeta);
		$baiMeta = array(
			"name"             => "bam_index",
			"visible"          => false,
			"assembly"         => $_REQUEST['refGenome'],
			"description"      => $_REQUEST['description'],
			"associated_master"=> $fnPath
			);
        $baiOut=array(
                "name"     => "bam_index",
				"file_path"=> "$bamFn.bai",
				"data_type"=> $_REQUEST['data_type'],
				"sources"  => array($bamFn),
				"taxon_id" => $_REQUEST['taxon_id'],
				"meta_data"=> $baiMeta);
		$toolOuts = Array ("output_files" => Array($bamOut, $baiOut));
		//tool logName
		$logName = basename($bamFn,".bam"). ".log"; 
		//call tool
		$pid = launchToolInternal("BAMval",$toolInputs,$toolArgs,$toolOuts,$output_dir,$logName);

         if ($pid){
		    $resp['state']=3;
            unset($_SESSION['validation'][$fn]);
         }else{
		    $resp['state']=0;
            $_SESSION['errorData']['Error'][]="Cannot submit BAM preprocessing to the queue. Try it later, sorry.";
		    $resp['msg'] .= printErrorData();
		    break;
         }

      }else{
	      $resp['state']= 1;
          unset($_SESSION['validation'][$fn]);
          $_REQUEST['sorted'] = "sorted";
      }
	  break;

	case 'BEDGRAPH':
	case 'WIG':
	case 'BED':
	case 'GFF':
    case 'GFF3':
       $ok = processUPLOAD($fn);
	   if (!$ok){
    		$resp['msg'] .= printErrorData();
    		$resp['state']= 0;
	    }else{
    		unset($_SESSION['validation'][$fn]);
    		$resp['msg'] .= basename($fnPath). " processed<br/>";
    		$resp['state']= 1;
	    }
	    break;

	default:
	   $resp['msg'] .= "Nothing to do in '".basename($fnPath). "'.<br/>";
	   $resp['state']= 1;
	   	
    }

    // set file state according to SESSION['errorData'] and SESSION['validation']
    if ( isset($_SESSION['validation'][$fn]['action'])){
    	$resp['state']= 2;
    }

    if (isset($_SESSION['errorData'])){
    	$resp['state']= 0;
        //var_dump($_SESSION['errorData']);
        //echo "-----------";
        //var_dump($resp);
        exit(0);
    }


    // save metadata if file already validated or is enqueued
    if ($resp['state'] == 1 ){
	$ok = saveMetadataUpload($fn,$_REQUEST,1);
	if (!$ok){
		$resp['msg'] .= printErrorData();
		$resp['state']= 0;
	}else{
		$resp['msg'] .= basename($fnPath). " successfully validated<br/>";
	}
    }elseif ($resp['state'] == 3){
	//$ok = saveMetadataUpload($fn,$_REQUEST,3);
	//if (!$ok){
	//	$resp['msg'] .= printErrorData();
	//	$resp['state']= 0;
	//}else{
		$resp['msg'] .= basename($fnPath). " validation process has being submited to the server. The task could take some time to run. Return to 'User Workspace' for monitoring it.<br/>";
	//}
    }
    break;
  
  //no  format
  default:
    break;

}
/*
print "<br/>----------------RESP-----<br/>";
var_dump($resp);
print "<br/>----------------VALIDATION LIST-----<br/>";
var_dump($_SESSION['validation']);
print "<br/>----------------ERROR-----<br/>";
var_dump($_SESSION['errorData']);
print "<br/>--------------------<br/>";
 */


print json_encode($resp);

?>
