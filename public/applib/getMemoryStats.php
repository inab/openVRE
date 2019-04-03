<?php

require __DIR__."/../../config/bootstrap.php";

if($_POST){

$mem = GetMemoryInfo();

echo round((($mem[0] - $mem[1]) / $mem[0]) * 100);

}else{
	redirect($GLOBALS['URL']);
}


?>
