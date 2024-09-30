<?php 

function getInteractiveToolURL(){
}
// Build URL from job metadata (port)
$port=$_REQUEST['port'];

$url_path = 'rstudio_'.md5($port);
$url = "http://vre.disc4all.eu/$url_path/";

# c3daba8ba04565423e12eb8cb6237b46 == 9001
# f3957fa3bea9138b3f54f0e18975a30c == 9002
# c3570da56db1511167324d0f5d0c8dfa == 49158
# ...

//$url = 'https://longitools.bsc.es/tool-proxy/';




// Custom headers to pass
$headers = [
//    "x-interactive-tool-host: RStudio_b04cbeb0bc6f0e70",
//    "x-interactive-tool-port: 8787",
	"X-RStudio-Root-Path: /".$url_path
	//"X-Root-Path:  /".$url_path
];
foreach ($headers as $h) {
	header($h);
}

// Redirect to the external URL
header("Location: $url");


exit;
