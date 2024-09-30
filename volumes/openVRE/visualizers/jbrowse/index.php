<?php

require __DIR__."/../../../config/bootstrap.php";
redirectOutside(); 

# Classes to build json tracks
include "jsonClassTemplates_new.php";

$user = $_SESSION['User']['id'];
$proj = $_SESSION['User']['activeProject'];


// direct link to Jbrowse without tracks

if (isset($_REQUEST['direct_refGenome'])){ 
	$ref= $_REQUEST['direct_refGenome'];
	if ($ref == "r5.01"){
		# changing url: JBrowse update to support BED files (v1.12.3)
        	#$url = "JBrowse-1.12.0/index.html?data=user_jbrowse%2F$ref%2Fjbrowse";
                $url = "JBrowse-1.12.3_ct/index.html?data=user_jbrowse%2F$ref%2Fjbrowse";
#	        $url = $url . "data=" . urlencode("user_data/$user/$proj/.jbrowse");
#        	$url = $url . "&loc=" . urlencode("chrI:30000..50000");
#	        $url = $url . "&tracks=";
	}else{
                # changing url: JBrowse update to support BED files (v1.12.3)
        	#$url = "JBrowse-1.12.1/index.html?data=user_jbrowse%2F$ref%2Fjbrowse";
                $url = "JBrowse-1.12.3/index.html?data=user_jbrowse%2F$ref%2Fjbrowse";
	}
	#https://vre.multiscalegenomics.eu/visualizers/jbrowse/JBrowse-1.12.1-yeast/index.html?data=user_jbrowse%2Fdm6%2Fjbrowse
	$url = $GLOBALS['jbrowseURL'] . $url;
	redirect($url);
}


// link to Jbrowse with tracks

# JBrowse url
# changing url: JBrowse update to support BED files (v1.12.3)
#$url = "JBrowse-1.12.1/index.html?";
$url = "JBrowse-1.12.3/index.html?";
$url = $url . "data=" . urlencode("user_data/$user/$proj/.jbrowse");
$url = $url . "&loc=" . urlencode("chrII:30000..50000");
$url = $url . "&tracks=";
$url_tracks = "";


# user track file trackList.json
$file = $GLOBALS['dataDir']."/$user/$proj/.jbrowse/trackList.json";
//print $file;
$trackf = fopen($file, "w");
$tracks = "";

$query_string = "";

$tracks = $_REQUEST["fn"];
##$arr_tracks = split(',',$tracks);
$arr_tracks = $tracks;

$first = true;
$refGlobal;

foreach ($arr_tracks as $id) {

	$label = $id;
	$fileData = getGSFile_fromId($id);

        $filepath = $fileData['path'];
	$filename = basename($filepath);
	$type = $fileData['data_type'];
	$format = $fileData['file_type'];
        $ref = $fileData['refGenome'];

	if (!$ref){
                $_SESSION['errorData']['JBrowse'][] ="$filename cannot be visualized, unknown reference genome $ref";
		redirect("/visualizers/error.php");
	}

        $a_project = explode("/",dirname($filepath));
        $project = array_pop($a_project);

	if ($first) {
#                if ($ref  == "hg38" || $ref == "hg19"){
                        # head of trackList.json, having all common tracks 
                        //print "HUMAN";
			# changing url: JBrowse update to support BED files (v1.12.3)
                        #$trackHead = file_get_contents("JBrowse-1.12.1/data/$ref/jbrowse/tracks/trackList_head.json", FILE_USE_INCLUDE_PATH);
                        #$trackTail = file_get_contents("JBrowse-1.12.1/data/$ref/jbrowse/tracks/trackList_tail.json", FILE_USE_INCLUDE_PATH);
                        $trackHead = file_get_contents("JBrowse-1.12.3/data/$ref/jbrowse/tracks/trackList_head.json", FILE_USE_INCLUDE_PATH);
                        $trackTail = file_get_contents("JBrowse-1.12.3/data/$ref/jbrowse/tracks/trackList_tail.json", FILE_USE_INCLUDE_PATH);
                        # Common tracks (reference sequence, genes, GC, etc.)
                        fwrite($trackf, $trackHead);
                        $refGlobal=$ref;

#                } else {
			# head of trackList.json, having all common tracks 
#			$trackHead = file_get_contents("JBrowse-1.11.6/data/$ref/jbrowse/tracks/trackList_head.json", FILE_USE_INCLUDE_PATH);
#			$trackTail = file_get_contents("JBrowse-1.11.6/data/$ref/jbrowse/tracks/trackList_tail.json", FILE_USE_INCLUDE_PATH);
			# Common tracks (reference sequence, genes, GC, etc.)
#			fwrite($trackf, $trackHead);
#			$refGlobal=$ref;
#print "HEAD: $trackHead";
#print "TAIL: $trackTail";
#		}
	}

#print "LABEL: " . $label . " " . "FILENAME: " . $filename . " TYPE: " . $type . " FORMAT : " .$format . " PROJECT: " . $project . " REF: " . $ref . "<br/>";

	if ($refGlobal != $ref){
                $_SESSION['errorData']['JBrowse'][] ="All selected tracks should have the same Reference Genome.";
                redirect("/visualizers/error.php");
	}

	if ($type == "FALSE"){
                $_SESSION['errorData']['JBrowse'][] ="$filename cannot be visualized, file has no track type.";
                redirect("/visualizers/error.php");
	}


	if ($type == "BAM") {
                $e = new Alignment($label,"uploads",$filename);
		
		$json = json_encode($e);
		# jbrowse doesn't seem to like escaped slashes
		$json = str_replace("\/","/",$json);
		//echo $json;
		if ($first) {
			fwrite($trackf, "\n".$json.",");
		} else {
			fwrite($trackf, ",\n".$json);
		}
                
		$e = new AlignmentCoverage($label,"$project",$filename);

	 } elseif ($type == "BW_cov"){
                $e = new Coverage($label,"$project",$filename);
                
	} elseif ($type == "nucleosome_positioning"){
                $e = new GFF_NR($label,"$project",$filename);
                
        } elseif ($type == "nucleosome_dynamics" && ( $format=="GFF3" || $format=="GFF" ) ){
                $e = new GFF_ND($label,"$project",$filename);
        } elseif ($type == "nucleosome_dynamics" && $format=="BW"){
                $e = new BW_ND($label,"$project",$filename);
        } elseif ($type == "tss_classification_by_nucleosomes"){
                $e = new GFF_TX($label,"$project",$filename);
        } elseif ($type == "nucleosome_free_regions"){
                $e = new GFF_NFR($label,"$project",$filename);
        } elseif ($type == "nucleosome_stiffness"){
                $e = new GFF_GAU($label,"$project",$filename);
        } elseif ($type == "nucleosome_gene_phasing" && ( $format=="GFF3" || $format=="GFF" ) ){
                $e = new GFF_P($label,"$project",$filename);
        } elseif ($type == "nucleosome_gene_phasing" && $format=="BW"){
                $e = new BW_P($label,"$project",$filename);
	} elseif ($format == "GFF" || $format=="GFF3"){
                $e = new GFF($label,"$project",$filename);
        } elseif ($format == "BED"){
                $e = new BED($label,"$project",$filename);
        } elseif ($format == "BW"){
                $e = new BW($label,"$project",$filename);
        } elseif ($format == "BAM"){
                $e = new Alignment($label,$project,$filename);

                $json = json_encode($e);
                # jbrowse doesn't seem to like escaped slashes
                $json = str_replace("\/","/",$json);
                //echo $json;
                if ($first) {
                        fwrite($trackf, "\n".$json.",");
                } else {
                        fwrite($trackf, ",\n".$json);
                }

                $e = new AlignmentCoverage($label,"$project",$filename);

	} else {
#                $_SESSION['errorData']['JBrowse'][] ="$filename cannot be visualized.  Unknown track type '$type' or unsupported format '$format'.";
                $_SESSION['errorData']['JBrowse'][] ="$filename cannot be visualized.  Unsupported format '$format'.<br>Supported formats are: GFF, GFF3, BW and BAM.";

                redirect("/visualizers/error.php");
	}

	if ($e != null) {
	        $json = json_encode($e);
		# jbrowse doesn't seem to like escaped slashes
	        $json = str_replace("\/","/",$json);
//	        echo $json;
			if ($first) {
				fwrite($trackf, "\n".$json);
			} else {
		        fwrite($trackf, ",\n".$json);
			}
	}

	# url where we'll redirect (jbrowse activating tracks) 
	if ($first){
		$first = false;
		$url_tracks = urlencode($label);
	} else {
	  	$url_tracks = $url_tracks . "," . urlencode($label);
	}
}


# If we got here, trackType's and refGenome's are OK

$seqfrom = $GLOBALS['dataDir']."/$user/$proj/.jbrowse/seq";
$seqto = $GLOBALS['refGenomes']."/$ref/jbrowse/seq/";
$tracksfrom = $GLOBALS['dataDir']."/$user/$proj/.jbrowse/tracks";
$tracksto = $GLOBALS['refGenomes']."/$ref/jbrowse/tracks/";
$namesfrom = $GLOBALS['dataDir']."/$user/$proj/.jbrowse/names";
$namesto = $GLOBALS['refGenomes']."/$ref/jbrowse/names/";
		//symlink($GLOBALS['jbrowseData']."/names/","$dataDirP/.jbrowse/names"); // OJ // OJOO

//print "SEQFROM: $seqfrom<br/>SEQTO: $seqto<br/><br/>TRACKSFROM: $tracksfrom<br/>TRACKSTO: $tracksto<br/><br/>";

if(file_exists($seqfrom)) {
//print "Exists";
    if(is_link($seqfrom)) {
	$seqto_ant = readlink($seqfrom);
//print "SEQTO_ANT: $seqto_ant<br/>";
	if ($seqto_ant != $seqto) {
		//print "CHANGING!";
	        unlink($seqfrom);
		symlink($seqto,$seqfrom);
	} else {
		//print "SAME!";
	}
    } else {
        exit("$linkfile exists but not symbolic link\n");
    }
} else {
//print "not exists";
	unlink($seqfrom);
	symlink($seqto,$seqfrom);
}


if(file_exists($tracksfrom)) {
    if(is_link($tracksfrom)) {
        $seqto_ant = readlink($tracksfrom);
        if ($seqto_ant != $tracksto) {
//                print "CHANGING!";
                unlink($tracksfrom);
                symlink($tracksto,$tracksfrom);
        } else {
//                print "SAME!";
        }
    } else {
        exit("$linkfile exists but not symbolic link\n");
    }
} else {
	unlink($tracksfrom);
	symlink($tracksto,$tracksfrom);
}


if(file_exists($namesfrom)) {
    if(is_link($namesfrom)) {
        $seqto_ant = readlink($namesfrom);
        if ($seqto_ant != $namesto) {
                //print "CHANGING!";
                unlink($namesfrom);
                symlink($namesto,$namesfrom);
        } else {
                //print "SAME!";
        }
    } else {
        exit("$linkfile exists but not symbolic link\n");
    }
} else {
    unlink($namesfrom);
    symlink($namesto,$namesfrom);
}


fwrite($trackf, $trackTail);
fclose($trackf);

if ($ref == "r5.01"){
	# changing url: JBrowse update to support BED files (v1.12.3)
	#$url = "JBrowse-1.12.0/index.html?";
        $url = "JBrowse-1.12.3_ct/index.html?";

	$url = $url . "data=" . urlencode("user_data/$user/$proj/.jbrowse");
	$url = $url . "&loc=" . urlencode("chrI:30000..50000");
	$url = $url . "&tracks=";
}/* else if ($ref == "hg38" || $ref=="hg19"){
        $url = "JBrowse-1.12.1/index.html?";
        $url = $url . "data=" . urlencode("user_data/$user/$proj/.jbrowse");
        $url = $url . "&loc=" . urlencode("chr1:30000..50000");
        $url = $url . "&tracks=";
}*/

$jbrowseURL = $GLOBALS['URL']."/visualizers/jbrowse/";
//print $jbrowseURL;

//$url = $GLOBALS['jbrowseURL'] . $url;
$url = $jbrowseURL . $url;

redirect($url);


//header('Location: ' . $url);
//exit();


?>


