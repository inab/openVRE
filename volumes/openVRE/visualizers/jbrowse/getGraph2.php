<?php

require "../../phplib/genlibraries.php";

redirectOutside();

$start = $_REQUEST["start"];
$end = $_REQUEST["end"];
$chr = $_REQUEST["chr"];
$window = $_REQUEST["window"];
$label = $_REQUEST["label"];
$win = $_REQUEST["win"];
$user = $_REQUEST["userId"];
$proj = $_SESSION['User']['activeProject'];

//NOU
//$fileData = $GLOBALS['filesMetaCol']->findOne(array('label' => $label));
//$filepath = $fileData['_id'];
//$filename2 = basename($filepath);

$fileData = $GLOBALS['filesMetaCol']->findOne(array('_id' => $label));
$fileData2 = $GLOBALS['filesCol']->findOne(array('_id' => $label));

$filepath = $fileData2['path'];
$filename2 = basename($filepath);


$ext = pathinfo($filename2,PATHINFO_EXTENSION);
$filename = str_replace(".".$ext,"",$filename2);


//$a_file = split("\.",$filename2);
//$filename = array_pop($a_file);
//$filename = array_pop($a_file);
$a_execution = split("/",dirname($filepath));
$execution = array_pop($a_execution);

//print $label ." ". $filename ." ".$execution . "<br/>";

$start = $start - $window;
if ($start < 0 ){
$start = 0;
}
$end = $end + $window;

//FROM l'antiga versio....
//$graph = "http://mmb.pcb.ub.es/NucleosomeDynamics/JBrowse/JBrowse-1.11.6/user_data/$user/.tmp/$filename.png";
//$file_image = "/orozco/services/Rdata/Web/USERS/$user/.tmp/$filename.png";
//$dest = "/orozco/services/Rdata/Web/USERS/$user/$execution/$filename.png";
//$placeholder = "$filename.png";

//$cmd = "Rscript "."/orozco/services/Rdata/Web/apps/nucleServ/bin/js_plot.R"." --input \"/orozco/services/Rdata/Web/USERS/$user/$proj/$execution/$filename"."_plot.RData\" --output \"/orozco/services/Rdata/Web/USERS/$user/$proj/.tmp/$filename.html\" --chr $chr --start ".$start." --end ".$end. " 2>&1";

$cmd = "Rscript "."/orozco/services/Rdata/Web/apps/nucleServ/bin/js_plot.R"." --input \"".$GLOBALS['dataDir']."/$user/$proj/$execution/$filename"."_plot.RData\" --output \"".$GLOBALS['dataDir']."/$user/$proj/.tmp/$filename.html\" --chr $chr --start ".$start." --end ".$end. " 2>&1";

#print $cmd;

exec("$cmd",$output);
//var_dump($output);

//http://www.example.com/?start=913&end=1152&chr=chrI&label=120502_SN365_B_L002_GGM-34_120502_SN365_B_L002_GGM-35&window=500

//$url_png = "http://mmb.pcb.ub.es/NucleosomeDynamics/JBrowse/JBrowse-1.11.6/user_data/$user/$proj/.tmp/$filename.html";
//$url_png = $GLOBALS['jbrowseURL']."/JBrowse-1.12.1/user_data/$user/$proj/.tmp/$filename.html";
$url_png = $GLOBALS['jbrowseURL']."/JBrowse-1.12.3/user_data/$user/$proj/.tmp/$filename.html";
print "<iframe src=$url_png style='height:100%;width:100%'></iframe>";

?> 

<br/>

</div>

</div>
</body>
</html>
