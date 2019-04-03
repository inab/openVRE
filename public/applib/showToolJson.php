<?php

require __DIR__."/../../config/bootstrap.php";
#require "../phplib/tools.inc.php";
redirectOutside();

print "<h2>Tool configuration file</h2>";

$toolId =  $_REQUEST['tool'];
$tool   = $GLOBALS['toolsCol']->findOne(array('_id' => $toolId));
if (empty($tool)){
	print "<p>The tool '$toolId' is not defined or is not registered in the database. Sorry, cannot show the details for the selected execution</p>";
	die(0);
}
$json = json_encode($tool, JSON_PRETTY_PRINT);

print "<pre style='max-height: calc(100vh - 300px);white-space: pre-wrap;'>$json</pre>";

?>
