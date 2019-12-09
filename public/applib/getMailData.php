<?php

require __DIR__."/../../config/bootstrap.php";

if($_POST){

$emails = array();
$ops = [ 'projection' => [ 'timestamp' => 1 ], 'sort' => [ 'timestamp' => 1 ] ];
foreach (array_values(iterator_to_array($GLOBALS['logMailCol']->find(array(), $ops))) as $v)
	array_push($emails, date('m/y', strtotime($v['timestamp'])));

$emails = array_count_values($emails);

$months = array();
for ($i = 11; $i >= 0; $i--) {
	array_push($months, date('m/y', strtotime("-".$i." month")));
}

$output = array();
foreach ($months as $k=>$v) {
	$output[$v] = $emails[$v];
}

$i = 1;
foreach($output as $k=>$v) {
	if(!$v) $v = 0;
	echo "[".$k.", ".$v."]";	
	if($i < count($output)) echo ";";
	$i ++;
}

}else{
	redirect($GLOBALS['URL']);
}


?>
