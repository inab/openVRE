<?php
/*
 * 
 */

// get chr names from ref genome description file
function get_chrNames($ref){
    $names=Array();
    //$chrFile= $GLOBALS['refGenomes']. "/$ref/$ref.fa.chrom.sizes";
    //$chrFile= glob($GLOBALS['refGenomes']. "/*/$ref/$ref.fa.chrom.sizes")[0];
    $chrFile= $GLOBALS['refGenomes']. "/$ref/$ref.fa.chrom.sizes";
    //$_SESSION['errorData']['Info'][]="Reference names are found in  $chrFile ";
    if (! is_file($chrFile)){
	//$_SESSION['errorData']['error'][]="Cannot find file with chromosome information names '$chrFile'";
	$_SESSION['errorData']['error'][]="Cannot find file with chromosome information names ".$GLOBALS['refGenomes']."/$ref/$ref.fa.chrom.sizes";
        return $names;
    }
    $stdOut;
    $stdErr;
    $cmd = "cut -f1 $chrFile";
    subprocess($cmd,$stdOut,$stdErr);
    if ($stdErr){
        $_SESSION['errorData']['error'][]="Error extracting reference Genome chromosomes from $chrFile: $stdErr";
        return $names;
    }
    $names  = explode(PHP_EOL, $stdOut);

    return $names;
}

// get chr possible alternative names
function get_chrAlternatives(){ 
	return Array (
		'1'  => 'I',
		'2'  => 'II',
		'3'  => 'III',
		'4'  => 'IV',
		'5'  => 'V',
		'6'  => 'VI',
		'7'  => 'VII',
		'8'  => 'VIII',
		'9'  => 'IX',
		'10' => 'X',
		'11' => 'XI',
		'12' => 'XII',
		'13' => 'XIII',
		'14' => 'XIV',
		'15' => 'XV',
		'16' => 'XVI',
		'17' => 'XVII',
		'18' => 'XVIII',
		'19' => 'XIX',
		'20' => 'XX',
		'21' => 'XXI',
        '22' => 'XXII',
		'I'  => '1',
		'II' => '2',
		'III'=> '3',
		'IV' => '4',
		'V'  => '5',
		'VI' => '6',
		'VII'=> '7',
		'VIII'=>'8',
		'IX' => '9',
		'X'  => '10',
		'XI' => '11',
		'XII'=> '12',
		'XIII'=>'13',
		'XIV'=> '14',
		'XV' => '15',
		'XVI'=> '16',
		'XVII'=>'17',
		'XVIII'=>'18',
		'XIX'=> '19',
		'XX' => '20',
		'XXI'=> '21',
		'XXII'=>'22',
		/*'I'  => 'I',
		'II' => 'II',
		'III'=> 'III',
		'IV' => 'IV',
		'V'  => 'V',
		'VI' => 'VI',
		'VII'=> 'VII',
		'VIII'=> 'VIII',
		'IX' => 'IX',
		'X'  => 'X',
		'XI' => 'XI',
		'XII'=> 'XII',
		'XIII'=> 'XIII',
		'XIV'=> 'XIV',
		'XV' => 'XV',
        'XVI'=> 'XVI',*/
		'Mito'=>'M',
		'Mt' => 'M',
		'MT' => 'M',
		'M' => 'M',
		'Y' => 'Y'
	);
}


// check and match file chr names against reference format chr names
// add items in validation action list (SESSION['validation'])

function validateUPLOAD($fn,$inFn,$refGenome,$format){
	    $dirTmp   = $GLOBALS['dataDir']."/".$_SESSION['User']['id']."/".$_SESSION['User']['activeProject']."/".$GLOBALS['tmpUser_dir'];
        $chrs     = Array();
        $chrRefRoot="chr";

        $chrRef = get_chrNames($refGenome);
        if (! count($chrRef) ){
		$_SESSION['errorData']['error'][]="Cannot find any chromosome for reference genome $refGenome.";
                return (false);
        }
        if (! is_file($inFn)){
                $_SESSION['errorData']['error'][]="Given file $inFn is not on disk. Please, check the file is selected and has size > 0";
                return (false);
        }
        $chrAlt = get_chrAlternatives();
        if (!is_dir($dirTmp) && ! mkdir($dirTmp, 0777, true)){
                $_SESSION['errorData']['error'][]="Cannot create temporal file $dirTmp";
                return (false);
        }

        # get chromosome names
        $chrNames=Array();

	switch ($format){
            case "BAM":
                $chrNames = getChrFromBAM($inFn);
                break;

            case "WIG":
                $chrNames = getChrFromWIG($inFn);
                break;
            case "BED":
            case "BEDGRAPH":
                $chrNames = getChrFromBEDGRAPH($inFn);
                 break;
            case "BW":
                $chrNames = getChrFromBIGWIG($inFn);
                break;
            case "GFF":
            case "GFF3":
                $chrNames = getChrFromGFF($inFn);
                break;
            default:
                //$_SESSION['errorData']['error'][]="Cannot parse chromosome names from files of format '$format'.";
                return true;
    }
	if (!$chrNames || count($chrNames)==0){
	    # BAM with no header? if no BAM chr_names, check BAM format and enqueue the validation process
	    if ($format=="BAM"){
                $ok=runValidatorBAM($inFn);
                if ($ok){
                        $_SESSION['validation'][$fn]['action']["enqueue_chrNames"]=0;
                        return (true);
                }else{
                        $_SESSION['errorData']['error'][]="BAM validator failed for ".basename($inFn).". Check format";
                        return (false);
                }

	    # Any chr name found in file. Wrong format? 
	    }else{
                $_SESSION['errorData']['error'][]="Cannot retrieve any chromosome name from ".basename($inFn).". Please, check format.";
                return (false);

	    }
	}

        # match file chr names against ref genome chr names
	foreach ($chrNames as $chr){
        	if (!$chr)
                continue;
		# look for the exact chr name
            if ( in_array($chr,$chrRef) ){
            $chrs[$chr]=$chr;

		# look for chr alternatives names
        }elseif (preg_match('/(chromosome)(.*)/i',$chr,$m) || preg_match('/(chr)(.*)/i',$chr,$m) || preg_match('/^()(.*)/i',$chr,$m)){
			if (!count($m)){continue;}
           	$pre  = $m[1];
			$post = $m[2];
            if (isset($chrAlt[$post]) && in_array($chrRefRoot.$chrAlt[$post],$chrRef)){
                $chrs[$chr]=$chrRefRoot.$chrAlt[$post];
			}elseif(in_array($post,$chrRef)){
				$chrs[$chr]=$post;
			}elseif(in_array("$chrRefRoot$post",$chrRef) ){
                $chrs[$chr]=$chrRefRoot.$post;
            }else{
                $chrs[$chr]=false;
           }
		}
	}

        # check matching of chrs names
        $hasErr=0;
        foreach ($chrs as $c=>$r){
                if ($r == false){
                	$_SESSION['errorData']['Naming of genomic regions failed'][]="Cannot find chromosome name <b>$c</b> in reference sequence '$refGenome'. No transformations suggested.";
                        $hasErr=1;
		}elseif($c != $r){
                	$_SESSION['validation'][$fn]['action']["substitutions"]["$c => $r"]=0;
		}
	}
        if ($hasErr){
            $_SESSION['errorData']['Error'][]="Please, edit your file to match <a href=\"help/upload.php\" target=\"_blank\">reference genome names</a> . Given file contains: ".join(" , ",$chrNames);
            $_SESSION['errorData']['Error'][]="Reference '$refGenome' assembly contains : ".join(" , ",$chrRef);
                return false;
        }
        return (true);
}




function getChrFromWIG($wigFn){
    $dirTmp   = $GLOBALS['dataDir']."/".$_SESSION['User']['id']."/".$_SESSION['User']['activeProject']."/".$GLOBALS['tmpUser_dir'];
	$trackLines= "";
	$stdErr    = "";
	$chrNames  = Array();

	subprocess("grep \"chrom=\" '$wigFn'",$trackLines,$stdErr,$dirTmp);
	if ($stdErr){
		$_SESSION['errorData']['error'][]="Error extracting chromosome names: $stdErr";
		return (false);
	}
	if ($trackLines){
		foreach (explode(PHP_EOL, $trackLines) as $line){
			if (preg_match('/chrom=(\S+)/',$line,$m)){
				if (!isset($m[1])){continue;}
				array_push($chrNames,$m[1]);
			}
		}
	}
	return $chrNames;
}

function getChrFromGFF($gffFn){

    $dirTmp   = $GLOBALS['dataDir']."/".$_SESSION['User']['id']."/".$_SESSION['User']['activeProject']."/".$GLOBALS['tmpUser_dir'];
	$chrs     = "";
	$stdErr   = "";
	$chrNames = Array();

	# check GFF format
	subprocess("grep -v \"^#\" '$gffFn' 2> /dev/null | head -n 1 | awk -F '\t' '{print NF}' ",$numCols,$stdErr,$dirTmp);
	if (strlen($stdErr)){
		$numCols = trim($numCols);
		$_SESSION['errorData']['error'][]="Error extracting chromosome names. <br/>CMD:grep -v \"^#\" '$gffFn' | head -n 1 | awk -F '\\t' '{print NF}'<br/>STDOUT:$numCols<br/>STDERR:$stdErr</br>";
		return (false);
	}
	if ($numCols != 9 ){
		$numCols = trim($numCols);
		$_SESSION['errorData']['error'][]="Error extracting chromosome names. Wrong GFF format (COLS=$numCols)? Are columns separeted by TABs?";
		return (false);
	}

	# extract chr names
	subprocess("cut -f1 '$gffFn' | sort -u | grep -v -P \"^#\"",$chrs,$stdErr,$dirTmp);
	if ($stdErr){
		$_SESSION['errorData']['error'][]="Error extracting chromosome names: $stdErr";
		return (false);
	}
	$chrNames = explode(PHP_EOL,trim($chrs));

	return $chrNames;
}

function getChrFromBIGWIG($bwFn){
    $dirTmp   = $GLOBALS['dataDir']."/".$_SESSION['User']['id']."/".$_SESSION['User']['activeProject']."/".$GLOBALS['tmpUser_dir'];
	$dirExe    = $GLOBALS['appsDir']."/UCSC/";
	$trackLines= "";
	$stdErr    = "";
	$chrNames  = Array();

	subprocess("$dirExe/bigWigInfo -chroms '$bwFn' | awk '{if($0~/[:space:]+.* [0-9]+ [0-9]+/){print $1}} '",$trackLines,$stdErr,$dirTmp);
    $trackLines = preg_replace('/\n$/','',$trackLines);
	if ($stdErr){
		$_SESSION['errorData']['error'][]="Error extracting chromosome names: $stdErr";
		return (false);
	}
	if (!$trackLines)
		return $chrNames;

	$chrNames = explode(PHP_EOL,$trackLines);
	return $chrNames;
}




function getChrFromBEDGRAPH($bgFn){
    $dirTmp   = $GLOBALS['dataDir']."/".$_SESSION['User']['id']."/".$_SESSION['User']['activeProject']."/".$GLOBALS['tmpUser_dir'];
	$trackLines= "";
	$stdErr    = "";
	$chrNames  = Array();

	$cmd= "awk '{if($0~/[ \\t]+[0-9]+[ \\t]+[0-9]+/){print $1}}' '$bgFn' | sort -u | tr -d \" \" ";
	subprocess($cmd,$trackLines,$stdErr,$dirTmp);
	if ($stdErr){
		$_SESSION['errorData']['error'][]="Error extracting chromosome names: $stdErr";
		return $chrNames;
	}
	if (!$trackLines)
		return $chrNames;
	$chrNames  = explode(PHP_EOL, $trackLines);
	return $chrNames;
}

function getChrFromBAM($bamFn){
    $dirTmp   = $GLOBALS['dataDir']."/".$_SESSION['User']['id']."/".$_SESSION['User']['activeProject']."/".$GLOBALS['tmpUser_dir'];
	$samtools = "/orozco/services/Rdata/Web/apps/samtools/bin/samtools";
	$SQs      = "";
	$stdErr   = "";
	$chrNames = Array();

	# check chromosome names from header
	subprocess("$samtools view -H '$bamFn' | grep \"\@SQ\"",$SQs,$stdErr,$dirTmp);
	if ($stdErr){
		$_SESSION['errorData']['error'][]="Error extracting chromosome names: $stdErr";
		return (false);
	}
	if (count($SQs)){
		foreach (explode("\n",$SQs) as $SQ){
			if (preg_match('/SN:(\w+)\s/',$SQ,$m)){
				if (!isset($m[1])){continue;}
				array_push($chrNames,$m[1]);
			}
		}		
	}
	# check chromosome names reading BAM
//	if (count($chrNames)==0){
//		subprocess("$samtools view '$bamFn' | cut -f3 | sort -u",$SQs,$stdErr,$dirTmp);
//		if ($stdErr){
//			$_SESSION['errorData']['error'][]="Error extracting chromosome names: $stdErr";
//			return (false);
//		}
//		if (count($SQs)) {	
//			foreach (explode('/$/',$SQs) as $chr){
//				if (!strlen($chr)){continue;}
//				array_push($chrNames,$m[1]);
//			}
//		}
//	}
	return $chrNames;
}

function runValidatorBAM($bamFn){
    $dirTmp   = $GLOBALS['dataDir']."/".$_SESSION['User']['id']."/".$_SESSION['User']['activeProject']."/".$GLOBALS['tmpUser_dir'];
	$bamValidator  = "/orozco/services/Rdata/Web/apps/bamValidator/bamUtil-1.0.13/bin/bam";
	$report   = 0;
	$stdErr   = "";
	subprocess("$bamValidator validate --i '$bamFn'",$report,$stdErr,$dirTmp);

	if ( preg_match("/SUCCESS/",$stdErr )){
		return 1;
	}else{
		return 0;
	}
}


/*
function validateWIG($inFn,$refGenome){
    $dirTmp   = $GLOBALS['dataDir']."/".$_SESSION['User']['id']."/".$_SESSION['User']['activeProject']."/".$GLOBALS['tmpUser_dir'];
	$chrs     = Array();

	$chrRef = get_chrNames($refGenome);
	if (! count($chrRef) ){
			$_SESSION['errorData']['error'][]="Cannot find any chromosome for reference genome $refGenome.";
			return (false);
	}
	$chrRefRoot="Chr";
	if (! is_file($inFn)){
			$_SESSION['errorData']['error'][]="Given file $inFn is not on disk. Please, check the file is selected and has size > 0";
			return (false);
	}
	$chrAlt = get_chrAlternatives();

	if (!is_dir($dirTmp) && ! mkdir($dirTmp, 0777, true)){
		$_SESSION['errorData']['error'][]="Cannot create temporal file $dirTmp";
		return (false);
	}

	# check chromosome names from WIG
	$trackLines="";
	$stdErr="";
	subprocess("grep \"chrom=\" $inFn",$trackLines,$stdErr,$dirTmp);
	if (count($trackLines)) {
		foreach (explode("\n",$trackLines) as $line){
			if (preg_match('/chrom=(\S+)/',$line,$m)){
				if (!isset($m[1])){continue;}
				$chr=$m[1];
				if ( in_array($chr,$chrRef) ){
					$chrs[$chr]=$chr;
				}else{
					if (preg_match('/(chromosome)(.*)/i',$chr,$m) || preg_match('/(chr)(.*)/i',$chr,$m) || preg_match('/^()(.*)/i',$chr,$m) ){
						if (!count($m)){continue;}
						$pre  = $m[1];
						$post = $m[2];
						if (isset($chrAlt[$post])){
							$chrs[$chr]=$chrRefRoot.$chrAlt[$post];
						}else{
							$chrs[$chr]=false;
						}	
					}
				}
			}
		}
	}

	if (count($trackLines) == 0){
		$_SESSION['errorData']['error'][]="Cannot retrieve any chromosome names from $inFn. Check format";
		return (false);
	}
	foreach ($chrs as $c=>$r){
			if ($r == false){
				$_SESSION['errorData']['error'][]="Cannot find chromosome name $c on reference sequence $refGenome. No transformations suggested";
				return (false);
			}elseif($c != $r){
				$_SESSION['validation'][]="WIG chromosome name not in reference sequence. The following transformation will be performed: $c => $r";
			}
	}
	return (true);
}



function validateBAM($bamFn,$refGenome){

	$samtools = "/orozco/services/Rdata/Web/apps/samtools/bin/samtools";
    $dirTmp   = $GLOBALS['dataDir']."/".$_SESSION['User']['id']."/".$_SESSION['User']['activeProject']."/".$GLOBALS['tmpUser_dir'];
	$chrs     = Array();

	$chrRef = get_chrNames($refGenome);
	if (! count($chrRef) ){
			$_SESSION['errorData']['error'][]="Cannot find any chromosome for reference genome $refGenome.";
			return (false);
	}
	$chrRefRoot="Chr";
	if (! is_file($bamFn)){
			$_SESSION['errorData']['error'][]="Given BAM file $bamFn is not on disk. Please, check the file is selected and has size > 0";
			return (false);
	}
	$chrAlt = get_chrAlternatives();

	if (!is_dir($dirTmp) && ! mkdir($dirTmp, 0777, true)){
		$_SESSION['errorData']['error'][]="Cannot create temporal file $dirTmp";
		return (false);
	}

	# check chromosome names from header
	$SQs="";
	$stdErr="";
	subprocess("$samtools view -H $bamFn | grep \"\@SQ\"",$SQs,$stdErr,$dirTmp);
	if (count($SQs)) {
		foreach (explode("\n",$SQs) as $SQ){
			if (preg_match('/SN:(\w+)\s/',$SQ,$m)){
				if (!isset($m[1])){continue;}
				$chr=$m[1];
				if ( in_array($chr,$chrRef) ){
					$chrs[$chr]=$chr;
				}else{
					if (preg_match('/(chromosome)(.*)/i',$chr,$m) || preg_match('/(chr)(.*)/i',$chr,$m) || preg_match('/^()(.*)/i',$chr,$m) ){
						if (!count($m)){continue;}
						$pre  = $m[1];
						$post = $m[2];
						if (isset($chrAlt[$post])){
							$chrs[$chr]=$chrRefRoot.$chrAlt[$post];
						}else{
							$chrs[$chr]=false;
						}	
					}
				}
			}
		}
	}

	# check chromosome names reading BAM
	if (count($chrs)==0){
		subprocess("$samtools view '$bamFn' | cut -f3 | sort -u",$SQs,$stdErr,$dirTmp);
		if ($stdErr){
			$_SESSION['errorData']['error'][]="Error extracting chromosome names: $stdEr\n";
			return (false);
		}
		if (count($SQs)) {	
			foreach (explode("\n",$SQs) as $chr){
				if (!strlen($chr)){continue;}
				if ( in_array($chr,$chrRef)){
					$chrs[$chr]=$chr;
				}else{
					if (preg_match('/(chromosome)(.*)/i',$chr,$m) || preg_match('/(chr)(.*)/i',$chr,$m) || preg_match('/^()(.*)/i',$chr,$m) ){
						if (!count($m)){continue;}
						$pre  = $m[1];
						$post = $m[2];
						if (isset($chrAlt[$post])){
							$chrs[$chr]=$chrRef[0].$chrAlt[$post];
						}else{
							$chrs[$chr]=false;
						}	
					}
				}
			}
		}
		#NOTE: An alternative is to get chrs from 'samtools idxstat aln.sorted.bam'
	}
	if (count($chrs) == 0){
		$_SESSION['errorData']['error'][]="Cannot retrieve any chromosome names from $bamFn. Check format";
		return (false);
	}
	foreach ($chrs as $c=>$r){
			if ($r == false){
				$_SESSION['errorData']['error'][]="Cannot find chromosome name $c on reference sequence $refGenome";
				return (false);
			}elseif($c != $r){
				$_SESSION['validation'][]="BAM chromosome name not in reference sequence. The following transformation will be performed: $c => $r";
			}
	}
	return (true);
}

*/

function get_seds_fromChrNameValidation($fn){
	$subs=Array();
    if (isset($_SESSION['validation'][$fn]['action']['substitutions'] )){
        foreach ($_SESSION['validation'][$fn]["action"]['substitutions'] as $action => $v ){
            if (preg_match('/(.[^ ]*) => (.*)/',$action,$m)){
                    $format= $_SESSION['validation'][$fn]['format'];
    				if ($format == "GFF" || $format == "GFF3" || $format == "BED"){
    					//print " sed 's/^".$m[1]."/".$m[2]."/g' <br/> ";
    					array_push($subs," sed 's/^".$m[1]."/".$m[2]."/g' ");
    				}elseif ($format == "WIG" ){
    					//print " sed 's/chrom=".$m[1]."/chrom=".$m[2]."/g' <br/> ";
    					array_push($subs," sed 's/chrom=".$m[1]."/chrom=".$m[2]."/g' ");
    				}elseif ($format == "BAM" ){
    					//print " sed 's/".$m[1]."\\t/".$m[2]."\\t/g' <br/> ";
    					array_push($subs," sed 's/".$m[1]."\\t/".$m[2]."\\t/g' ");
    				}else{	
    					//print " sed 's/".$m[1]."/".$m[2]."/g' <br/> ";
    					array_push($subs," sed 's/".$m[1]."/".$m[2]."/g' ");
    				}
	    		}
		}
	}
	return $subs;
}

function processBAM($bamId,$type,$cores){
	$fileInfo = $GLOBALS['filesCol']->findOne(array('_id' => $bamId));
	$bam      = $fileInfo['path'];
	$bamFn    = $GLOBALS['dataDir']."/".$bam;
	$samtools = "/orozco/services/Rdata/Web/apps/samtools/bin/samtools";
    $dirTmp   = $GLOBALS['dataDir']."/".$_SESSION['User']['id']."/".$_SESSION['User']['activeProject']."/".$GLOBALS['tmpUser_dir'];
	$bamNew   = $dirTmp. "/".basename($bam); 

	# edit/sort BAM according SESSION[validation] 
	
	$subs  = get_seds_fromChrNameValidation($bamId);
	$sort  = (preg_grep('/will be sorted/', $_SESSION['validation'])? true:false);
	$index = (preg_grep('/indexed/', $_SESSION['validation'])? true:false);

	if (empty($fileInfo) || count($subs) || $sort || $index ){
		$shfile = queueNucDynPrepUpload(basename($bam),$dirTmp,$bamFn,$type,$sort,$subs,$index,$cores);
	    $pid    = execNucDyn($dirTmp,$shfile);

        //print "PID PREPROCESSING is $pid for ".$bamFn." sORT=$sort index=$index type=$type fileInfo=".empty($fileInfo)."---> SH: $shfile<br>";
		$fileMeta = $GLOBALS['filesMetaCol']->findOne(array('_id' => $bam));
	    $SGE_updated[$pid]= Array('_id' => $pid,
                                    'out'   => array($bam,"$bam.RData","$bam.cov","$bam.bai"),
                                    'log'   => str_replace(".sh",".log","$dirTmp/$shfile"),
									'metaData'=> $fileMeta
                                );
		addUserJob($_SESSION['userId'],$SGE_updated);
		return $pid;
	}else{
		$_SESSION['errorData']['error'][]="Nothing to do with $bam . Already sorted, indexed, checked and stored.";
		return (false);
	}

//        $insertData=array(
//               '_id'   => $bam,
//               'owner' => $_SESSION['userId'],
//               'size'  => filesize($bamFn),
//               'mtime' => new MongoDB\BSON\UTCDateTime(filemtime($bamFn)*1000)
//        );
//		$fileMeta = $GLOBALS['filesMetaCol']->findOne(array('_id' => $bam));
//		$r = uploadGSFileBNS($bam, $bamFn, $insertData,$fileMeta,FALSE);
//		if ($r == 0 )
//			return (false);
//	}

}


function processUPLOAD($inId){
    $dirTmp   = $GLOBALS['dataDir']."/".$_SESSION['User']['id']."/".$_SESSION['User']['activeProject']."/".$GLOBALS['tmpUser_dir'];
	$fileInfo = $GLOBALS['filesCol']->findOne(array('_id' => $inId));
    $fileMeta = $GLOBALS['filesMetaCol']->findOne(array('_id' => $inId));
    $in       = $fileInfo['path'];
	$inFn     = $GLOBALS['dataDir']."/".$in;
	$outFn    = $dirTmp. "/".basename($in);

	# edit file according SESSION[validation] 
    $subs  = get_seds_fromChrNameValidation($inId);

	$cmd = "";
	if (count($subs)){
		$cmd = "cat $inFn | ". join(" | ",$subs). " > $outFn ; ";
	}
	if ($cmd){
		subprocess($cmd,$stdOut,$stdErr,$dirTmp);
		if ($stdErr){
        	$_SESSION['errorData']['error'][]="Error while executing the following command: $cmd<br/>$stdErr";
			return false;
		}
		if (! is_file($outFn) ){
			$_SESSION['errorData']['error'][]="Error while executing the following command: $cmd<br/>Output $outFn not created";
			return false;
		}

		subprocess("mv $outFn $inFn",$stdOut,$stdErr,$dirTmp);
		if (! is_file($inFn) and filesize($inFn) ){
			$_SESSION['errorData']['error'][]="Error while executing the following command: $cmd<br/>Output $inFn not created";
			return false;
        }
	    $insertData=array(
	           '_id'   => $inId,
	           'owner' => $_SESSION['userId'],
	           'size'  => filesize($inFn),
	           'mtime' => new MongoDB\BSON\UTCDateTime(filemtime($inFn)*1000)
           );

        // delete original file from DMP
        $r = deleteGSFileBNS($inId);

        // create new file from DMP
        $r = uploadGSFileBNS($in, $inFn, $insertData,$fileMeta,FALSE);
		if ($r == "0")
			return false;
    }
	return true;
}


function convert2BW_getCmd($input,$bw,$refGenome){
	$chrSizes= glob($GLOBALS['refGenomes']. "/*/$refGenomes/$refGenome.fa.chrom.sizes")[0];
    $dirTmp   = $GLOBALS['dataDir']."/".$_SESSION['User']['id']."/".$_SESSION['User']['activeProject']."/".$GLOBALS['tmpUser_dir'];
	$dirExe  = $GLOBALS['appsDir']."/UCSC/";
	$ext = pathinfo($input, PATHINFO_EXTENSION);
	$ext = preg_replace('/_\d+$/',"",$ext);

	if (preg_match('/wig/i',$ext)){
		$exe  = $dirExe."/wigToBigWig";
	}elseif (preg_match('/wig/i',$ext)){
		$exe  = $dirExe."/bedGraphToBigWig";
	}else{
		$_SESSION['errorData']['error'][]= "Cannot convert $input to BIGWIG. Cannot guess file format. Expected .wig or .bedgraph";
		return (false);
	}
	
	if (! is_file($chrSizes)){
		$_SESSION['errorData']['error'][]="No file with chromosome sizes given to the UCSC script. File $chrSizes not found.";
		return (false);
	}
	$cmd = "$exe $input $chrSizes $bw";
	return $cmd;
}

function convert2BW($fn,$BW,$refGenome,$format){
	//$chrSizes= $GLOBALS['refGenomes']. "/$refGenome/$refGenome.fa.chrom.sizes";
	$chrSizes= glob($GLOBALS['refGenomes']. "/*/$refGenomes/$refGenome.fa.chrom.sizes")[0];
    $dirTmp   = $GLOBALS['dataDir']."/".$_SESSION['User']['id']."/".$_SESSION['User']['activeProject']."/".$GLOBALS['tmpUser_dir'];
	$dirExe  = $GLOBALS['appsDir']."/UCSC/";

	$input  = $GLOBALS['dataDir']."/".$fn;
	$BWfn   = $GLOBALS['dataDir']."/".$BW;

	if ($format == "WIG"){
		$exe  = $dirExe."/wigToBigWig";
	}elseif ($format == "BEDGRAPH"){
		$exe  = $dirExe."/bedGraphToBigWig";
	}elseif ($format == "BED"){
		$exe  = $dirExe."/bedToBigWig";
	}else{
		$_SESSION['errorData']['error'][]= "Cannot convert $input from format $format. Expected wig or bedgraph";
		return (false);
	}
	
	if (! is_file($chrSizes)){
		$_SESSION['errorData']['error'][]="No file with chromosome sizes given to the UCSC script. File $chrSizes not found.";
		return (false);
	}
	$cmd = "$exe $input $chrSizes $BWfn";
	$stdOut  = "";
	$stdErr  = "";
	subprocess($cmd,$stdOut,$stdErr,$dirTmp);
	if ($stdErr){
		$_SESSION['errorData']['error'][]="Error while converting $fn to BIGWIG.<br/><u>CMD</u>: $cmd<br/><u>ERR</u>: $stdErr";
		return (false);
	}
	if (! is_file($BWfn) ){
		$_SESSION['errorData']['error'][]="Error while converting $fn to BIGWIG.<br/><u>CMD</u>: $cmd<br/><u>ERR</u>: Expected outfile not created!";
		return (false);
	}
	$insertData=array(
	       '_id'   => $BW,
	       'owner' => $_SESSION['userId'],
	       'size'  => filesize($BWfn),
	       'mtime' => new MongoDB\BSON\UTCDateTime(filemtime($BWfn)*1000)
	);
	$fileMeta = $GLOBALS['filesMetaCol']->findOne(array('_id' => $fn));
	$r = uploadGSFileBNS($BW, $BWfn, $insertData,$fileMeta,FALSE);
	if ($r == 0)
		return (false);

	#clean original wig
	$r = deleteGSFileBNS($fn);
    if ($r == 0)
    	return false;
	unlink ($input);	

	return (true);
}

/*
//adds regular metadata
function saveMetadataUpload($fn,$request,$validationState){

	// filters known metadata fields
	$insertMeta = prepMetadataUpload($request,$validationState);

	// save to mongo 	
	$r = modifyMetadataBNS($fn,$insertMeta);
	return $r;

        //if ($_SESSION['validation'][$fn]['action']['enqueue_chrNames']){
        //    $pid = $_SESSION['enqueued'][$IDX]['pid'];
        //    $SGE_updated = getUserJobs($_SESSION['userId']);
        //    $SGE_updated[$pid]['metaData'] = $insertMeta;

        //    saveUserJobs($_SESSION['userId'],$SGE_updated);

        //    $_SESSION['SGE'][$pid]['metaData'] = $insertMeta;
        //    unset($_SESSION['enqueued'][$IDX]);
        //}

}


// filters uploadForm2 request and formats mongo file metadata
function prepMetadataUpload($request,$validationState=0){
	$fnPath    = getAttr_fromGSFileId($fn,'path');

	$format    = (isset($request['format'])?$request['format']:"UNK");
	$validated = $validationState;
	$tracktype = format2trackType($format,$fnPath);
	$visible   = (isset($insertMeta['visible'])?$insertMeta['visible']:"1");

	// compulsory metadata
        $insertMeta=array(
            'format'     => $format,
            'validated'  => $validated,
            'trackType'  => $tracktype,
	    'visible'    => $visible,
	);
	// GFF, BAM, BW,.. metadata
	if (isset($request['refGenome']))   {$insertMeta['refGenome']  = $request['refGenome'];}
	// BAM metadata
	if (isset($request['paired']))      {$insertMeta['paired']     = $request['paired'];}
	if (isset($request['sorted']))      {$insertMeta['sorted']     = $request['sorted'];}
	if (isset($request['description'])) {$insertMeta['description']= $request['description'];}
	//  results metadata
	if (isset($request['submission_file'])){$insertMeta['submission_file'] = $request['shFile'];}
	if (isset($request['log_file']))       {$insertMeta['log_file']        = $request['logFile'];}
	if (isset($request['in']))             {$insertMeta['in']              = $request['in'];}

	return  $insertMeta;
}


//completes $meta and formats mongo file metadata
function prepMetadataResult($meta,$fnPath=0){
	if ($fnPath){
		$extension = pathinfo("$fnPath",PATHINFO_EXTENSION);
	        $extension = preg_replace('/_\d+$/',"",$extension);
	}
	if (!isset($meta['format']) && $fnPath){
		$meta['format']= strtoupper($extension);
	}
	if (!isset($meta['tracktype']) && $fnPath ){
		$meta['tracktype']=format2trackType($meta['format'],basename($fnPath));
	}
	if (!isset($meta['refGenome'])){
	    if (isset($meta['in']) ){
                $inpObj = $GLOBALS['filesMetaCol']->findOne(array('_id'  => $meta['in']));
                if (!empty($inpObj)){
                        $meta['refGenome']= $inpObj['refGenome'];
		}
	    }
            if (!isset($meta['refGenome']) && !in_array($meta['format'],array("LOG","SH","ERR")) && $fnPath ){
                $prefix   = "";
		$validated= true;
                $refGenome= "";
		$ext = $meta['format'];
                if (preg_match("/^[A-Z]+_\(\w+\)(.+)-\(\w+\)(.+)\.$ext/i",basename($filePath),$m)) {
                        $prefix = $m[1];
                }elseif (preg_match("/^[A-Z]+_\(\w+\)(.+)-/",basename($filePath),$m)) {
                         $prefix = $m[1];
                }elseif (preg_match("/^[A-Z]+_(.+)\.$ext/i",basename($filePath),$m)) {
        		$prefix = $m[1];
                }elseif (preg_match("/^[A-Z]+_(.+)\./i",basename($filePath),$m)) {
                        $prefix = $m[1];
                }else{
                        $prefix = preg_replace("/.$ext/i","",basename($filePath));
                        $prefix = preg_replace("/^.*_/","",$prefix);
                }
                $reObj = new MongoRegex("/".$_SESSION['User']['id'].".*".$prefix."/i");
                $relatedBAMS = $GLOBALS['filesMetaCol']->find(array('path'  => $reObj));
                if (!empty($relatedBAMS)){
                       $relatedBAMS->next();
                       $BAM = $relatedBAMS->current();
                       if (!empty($BAM))
                                $meta['refGenome'] = $BAM['refGenome'];
                }
	    }
	}
	if (!isset($meta['validated'])){
		$meta['validated']=1;
	}
	if (!isset($meta['visible']) && $fnPath){
		$meta['visible']=((in_array($extension,hiddenFiles()))?0:1);
	}
	return $meta;
}




//completes $meta for log files based on expected outfile
function prepMetadataLog($metaOutfile,$logPath=0,$format="LOG"){
	$metaLog = $metaOutfile;
	$metaLog['format']    = $format;
	$metaLog['tracktype'] = format2trackType($metaLog['format'],$logPath);
	$metaLog['validated'] = 1;
	$metaLog['visible']   = 1;
	return $metaLog;
}

*/

/*
//creates SH for enqueuing BAM sorting and indexing
function queueBAMvalidation ($prepNum,$tmpdir,$outdir,$bamFn,$type,$sort,$subs,$index,$cores) {

    $samtools = "/orozco/services/Rdata/Web/apps/samtools/bin/samtools";
    $out   = "$outdir/PP_${prepNum}.sh";
    $bamTmp= "$tmpdir/".basename($bamFn);
    $bai   = "$bamFn.bai";

    $fout = fopen($out, "w");
    if (!$fout){
	$_SESSION['errorData']['error'][]="Cannot create executable file '$out'.";
	return 0;
    }
    fwrite($fout, "#!/bin/bash\n");
    fwrite($fout, "# generated by MuG VRE\n");
    fwrite($fout, "cd $tmpdir\n");

    fwrite($fout, "\n# Running BAM preprocessing ...\n");
    fwrite($fout, "\necho '# Start time:' \$(date) > $outdir/PP_$prepNum.log\n");

    if(0){ #TODO
	fwrite($fout, "\necho \"#BAM has no header. Extract chromosome names reading the entire BAM\" >  $outdir/PP_$prepNum.log\n");
	fwrite($fout, "$samtools view '$bamFn' | cut -f3 | sort -u >> $outdir/PP_$prepNum.log 2>&1\n");
	fwrite($fout, "\necho \"#Matching chromosome names to reference genome names\" >  $outdir/PP_$prepNum.log\n");
	fwrite($fout, "php matchChrNames.php?format=format&chrs=chrFile&refGenome=refGenome >> $outdir/PP_$prepNum.log 2>&1\n");
	
    }	

    if (count($subs) || $sort){
        if (count($subs)){
            fwrite($fout, "\necho \"#Normalizing chromose names and sorting BAM file...\" >  $outdir/PP_$prepNum.log\n");
            fwrite($fout, "$samtools view -h $bamFn |". join(" | ",$subs) . "|". "$samtools view -uhS - | $samtools sort - -o $bamTmp >> $outdir/PP_$prepNum.log 2>&1\n");
            fwrite($fout, "echo '# End time:' \$(date) >> $outdir/PP_$prepNum.log\n");
        }else{
            fwrite($fout, "\necho \"#Sorting BAM file...\" >  $outdir/PP_$prepNum.log\n");
            fwrite($fout,"$samtools sort $bamFn -o $bamTmp >> $outdir/PP_$prepNum.log 2>&1\n");
            fwrite($fout, "echo '# End time:' \$(date) >> $outdir/PP_$prepNum.log\n");
        }
        fwrite($fout,"\nif [ ! -f  $bamTmp ]; then\n\techo 'Error sorting $bamFn, aborting' >> $outdir/PP_$prepNum.log\n\texit 2\nfi\n");
        fwrite($fout,"mv $bamTmp $bamFn\n");
    }
    if (count($subs) || $sort || $index){
        fwrite($fout, "\necho \"# Indexing BAM file...\" >> $outdir/PP_$prepNum.log\n");
        fwrite($fout,"$samtools index $bamFn >> $outdir/PP_$prepNum.log\n");
        fwrite($fout, "echo '# End time:' \$(date) >> $outdir/PP_$prepNum.log\n");

        fwrite($fout,"\nif [ ! -f  $bai ]; then\n\techo 'Error indexing $bamFn, aborting. Is the BAM file sorted? If not, mark it as \"unsorted\"' >> $outdir/PP_$prepNum.log\n\texit 2\nfi\n");

    }
    fclose($fout);

    return basename($out);
}
*/



//get header CURL
/*
function readHeaderOLD($ch, $header)
{
     // read headers
    echo "Read header: ", $header;
    return strlen($header);
}
function readHeader($ch, $header)
{
    global $responseHeaders;
    $url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $responseHeaders[$url][] = $header;
    return strlen($header);
}

*/

?>
