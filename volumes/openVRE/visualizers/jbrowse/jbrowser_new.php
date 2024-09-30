<?php

require "../../phplib/genlibraries.php";

redirectOutside();

# Classes to build json tracks
include "jsonClassTemplates_new.php";

$user = $_REQUEST["user"];

# JBrowse url
$url = "JBrowse-1.11.6/index.html?";
$url = $url . "data=" . urlencode("user_data/$user/.jbrowse");
$url = $url . "&loc=" . urlencode("chrI:30000..50000");
$url = $url . "&tracks=";
$url_tracks = "";

# tail of trackList.json, closing tags
#$trackTail = file_get_contents('../JBrowse/templates/trackTail.json', FILE_USE_INCLUDE_PATH); 

# user track file trackList.json
$file = "/data2/www/inb/NucleosomeDynamics/JBrowse/JBrowse-1.11.6/user_data/" . $user . "/.jbrowse/trackList.json";
$file = $GLOBALS['dataDir']."/". $user . "/.jbrowse/trackList.json";
//print $file;
$trackf = fopen($file, "w");
$tracks = "";

$query_string = "";

$tracks = $_REQUEST["tracks"];
$arr_tracks = split(',',$tracks);

$first = true;
$refGlobal;

foreach ($arr_tracks as $id) {
	### FALTA agafar reference genome i fer links: seq, tracks, names(?)

	$label = $id;
	$fileData = getGSFile_fromId($id);
        //$fileData = $GLOBALS['filesMetaCol']->findOne(array('_id' => $id));
        //$fileData2 = $GLOBALS['filesCol']->findOne(array('_id' => $id));

        $filepath = $fileData['path'];
	$filename = basename($filepath);
        $type = $fileData['trackType'];
        $ref = $fileData['refGenome'];
        $a_project = split("/",dirname($filepath));
        $project = array_pop($a_project);


	if ($first) {
		# head of trackList.json, having all common tracks 
		$trackHead = file_get_contents("JBrowse-1.11.6/data/tracks/$ref/trackList_head.json", FILE_USE_INCLUDE_PATH);
		$trackTail = file_get_contents("JBrowse-1.11.6/data/tracks/$ref/trackList_tail.json", FILE_USE_INCLUDE_PATH);
		# Common tracks (reference sequence, genes, GC, etc.)
		fwrite($trackf, $trackHead);
		$refGlobal=$ref;
	}


//	$filename = exec("grep -P \"^$label\\t\" metadata.txt |cut -f2");
//	$type = exec("grep -P \"^$label\\t\" metadata.txt |cut -f3");
//	$project = exec("grep -P \"^$label\\t\" metadata.txt |cut -f4");
//	$ref = exec("grep -P \"^$label\\t\" metadata.txt |cut -f5");
// print "LABEL: " . $label . " " . "FILENAME: " . $filename . " TYPE: " . $type . " PROJECT: " . $project . " REF: " . $ref . "<br/>";

	if ($refGlobal != $ref){
		print errorPage("Error", "All selected tracks should have the same Reference Genome. <a href=\"javascript:self.close()\">[ Close  ]</a>");
		exit(0);

	}

	if ($type == "FALSE"){
//		if ($filename == "lala.gff"){ 
//			$type = "GFF";
//		} elseif ($filename == "prova.bw"){
//			$type = "BW";
//		} else {
		print errorPage("Error", "$filename cannot be visualized, file has no track type. <a href=\"javascript:self.close()\">[ Close  ]</a>");
                exit(0);
//		}
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
                
	} elseif ($type == "GFF_NR"){
                $e = new GFF_NR($label,"$project",$filename);
                
	} elseif ($type == "GFF_ND"){
                $e = new GFF_ND($label,"$project",$filename);
        } elseif ($type == "GFF_TX"){
                $e = new GFF_TX($label,"$project",$filename);
        } elseif ($type == "GFF_NFR"){
                $e = new GFF_NFR($label,"$project",$filename);
        } elseif ($type == "GFF_GAU"){
                $e = new GFF_GAU($label,"$project",$filename);

        } elseif ($type == "GFF_P"){
                $e = new GFF_P($label,"$project",$filename);
        } elseif ($type == "BW_P"){
                $e = new BW_P($label,"$project",$filename);
        } elseif ($type == "GFF"){
                $e = new GFF($label,"$project",$filename);
        } elseif ($type == "BW"){
                $e = new BW($label,"$project",$filename);

	} else {
//		print $type;
//		print "unknown trackType<br>";
                print errorPage("Error", "$filename cannot be visualized, unknown track type $type. <a href=\"javascript:self.close()\">[ Close  ]</a>");
                exit(0);
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

$seqfrom = $GLOBALS['dataDir']."/". $user . "/.jbrowse/seq";
$seqto = $GLOBALS['jbrowseData']."/seq/$ref";
$tracksfrom = $GLOBALS['dataDir']."/". $user . "/.jbrowse/tracks";
$tracksto = $GLOBALS['jbrowseData']."/tracks/$ref";
$namesfrom = $GLOBALS['dataDir']."/". $user . "/.jbrowse/names";
$namesto = $GLOBALS['jbrowseData']."/names/$ref";
		//symlink($GLOBALS['jbrowseData']."/names/","$dataDirP/.jbrowse/names"); // OJ // OJOO

//print "SEQFROM: $seqfrom<br/>SEQTO: $seqto<br/><br/>TRACKSFROM: $tracksfrom<br/>TRACKSTO: $tracksto<br/><br/>";

if(file_exists($seqfrom)) {
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
    symlink($namesto,$namesfrom);
}


//symlink($GLOBALS['jbrowseData']."/seq/$ref", $GLOBALS['dataDir']."/". $user . "/.jbrowse/seq");
//symlink($GLOBALS['jbrowseData']."/tracks/$ref", $GLOBALS['dataDir']."/". $user . "/.jbrowse/tracks");


fwrite($trackf, $trackTail);
fclose($trackf);

if ($ref == "dmel-r5.1"){
	$url = "../JBrowse/JBrowse-1.12.0/index.html?";
	$url = $url . "data=" . urlencode("user_data/$user/.jbrowse");
	$url = $url . "&loc=" . urlencode("chrI:30000..50000");
	$url = $url . "&tracks=";
}
//$url = $url . $url_tracks;
$url = $GLOBALS['absURL'] . $url;
//print $url;

header('Location: ' . $url);
exit();


?>


