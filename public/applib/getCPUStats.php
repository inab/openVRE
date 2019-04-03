<?php

require __DIR__."/../../config/bootstrap.php";

if($_POST){

/* get core information (snapshot) */
$stat1 = GetCoreInformation();
/* sleep on server for one second */
sleep(1);
/* take second snapshot */
$stat2 = GetCoreInformation();
/* get the cpu percentage based off two snapshots */
$data = GetCpuPercentages($stat1, $stat2);

echo json_encode($data);

}else{
	redirect($GLOBALS['URL']);
}


?>
