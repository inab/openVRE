<?php

require __DIR__."/../../config/bootstrap.php";

redirectOutside();

if(! $_POST){
	redirect($GLOBALS['URL']);
}

// TODO: get list of files and match with all the visualizers (casi ná :)

$fft = getFiles_FileTypes($_REQUEST["fn"]);

$ft = getVisualizers_FileTypes();

$visualizersList = getVisualizers_ByFT($ft, $fft);

$visualizers = getVisualizers_ListByID($visualizersList);

sort($visualizers);

if(!empty($visualizers)) {

foreach($visualizers as $v) { 
	
	echo '<li>';
	echo '<a href="javascript:runVisualizer(\''.$v['_id'].'\', \''.$_SESSION['User']['id'].'\');" class="'.$v['_id'].'">';
	include '../visualizers/'.$v['_id'].'/assets/ws/icon.php';
	echo ' View in '.$v['name'];
	echo '</a>';
	echo '</li>';

}

}else{

	echo '<li>';
	echo '<a href="javascript:;" style="mouse:default;"><i class="fa fa-exclamation-triangle"></i> No visualizers available for this combination of files</a>';
	echo '</li>';

}
