<?php
require __DIR__."/../../config/bootstrap.php";
redirectOutside();

// Print header

require "../htmlib/header.inc.php";
//
// Find out execution status
//


function get_url_interactive_tool_333332($pid, $login="session") {

	$proxy_tool_url     = "";
	$proxy_tool_headers = array();
	$message            = "";
	$autorefresh        = true;

	$ok_service    = false;
	$ok_stdout     = false;
	$ok_stderr     = false;

	// Get job info
	$login = ($login == "session"? $_SESSION['User']['_id'] : $login);
	$jobs = getUserJobPid($login,$pid);
	$job = $jobs[$pid];
	// Check job status

	if (! $job['state'] == "RUNNING"){
		if ($job['state'] == "PENDING"){
			$_SESSION['errorData']['Info'] = "Please, wait. The tool session is not yet accessible. Job petition status: PENDING.\n Page is going to be automatically reloaded.";
		}else{
			$_SESSION['errorData']['Info'] = "Tool session is not accessible anymore. Please, check the execution status.\n Page is going to be automatically reloaded.";
		}
		return array($proxy_tool_url, $proxy_tool_headers, $autorefresh);
	}

	// Check session progress

	$stdout    = "";
	$tool_port = 0;

	if (is_file($job['stdout_file'])){
		$ok_stdout  =  true;
		$stdout = file_get_contents($job['stdout_file']);

		// parse port number
		if (preg_match_all('/ExposedPort: (\d+)/', $stdout, $matches)) {
			$tool_port = $matches[1][0];
			$_SESSION['User']['lastjobs'][$_REQUEST['pid']]['interactive_tool']['port'] = $tool_port;
			
		}

		// check service is UP
		if (preg_match_all('/Service UP/', $stdout, $matches)) {
			$_SESSION['User']['lastjobs'][$_REQUEST['pid']]['interactive_tool']['service_up'] = true;
			$ok_service  = true;
			$autorefresh = false;
		}else{
			$_SESSION['errorData']['Info'][]="Interactive session successfully established. Waiting for the service to respond...<br/>Page is going to be automatically reloaded.";
			return array($proxy_tool_url, $proxy_tool_headers, $autorefresh);
		}
		
	// No stdout
	}else{
		$_SESSION['errorData']['Error'][]="Execution has produced no STDOUT. Please, double check log data";
		$autorefresh = false;
		return array($proxy_tool_url, $proxy_tool_headers, $autorefresh);
	}

	// If service ready, find out public url

	if ($ok_service){
		// Build IP from port (md5)
		$url_proxy_path = 'rstudio_'.md5($tool_port);
		$proxy_tool_url = $GLOBALS['interactive_server'] . "/" . "$url_proxy_path/";

		// TODO: set gdx proxy headers
		$_SESSION['errorData']['Info'][]="Interactive session successfully established. Active session accessible at URL = <a target=_blank href='$proxy_tool_url'>$proxy_tool_url</a> .";

		// Set custom headers

		$proxy_tool_headers= array('"X-RStudio-Root-Path": "/'.$url_proxy_path.'"');
	}
	return array($proxy_tool_url, $proxy_tool_headers, $autorefresh);

}

list($proxy_tool_url,$proxy_tool_headers,$autorefresh) = get_url_interactive_tool($_REQUEST['pid']);

// Print page
?>
 
<body class="page-header-fixed page-sidebar-closed-hide-logo page-content-white page-container-bg-solid page-sidebar-fixed">
  <div class="page-wrapper">

  <?php require "../htmlib/top.inc.php"; ?>
  <?php require "../htmlib/menu.inc.php"; ?>

    <!-- BEGIN CONTENT -->
    <div class="page-content-wrapper">
	<!-- BEGIN CONTENT BODY -->
	<div class="page-content">
    	<!-- BEGIN PAGE HEADER-->
    	<!-- BEGIN PAGE BAR -->
    	<div class="page-bar">
    	    <ul class="page-breadcrumb">
    	      <li>
    		  <a href="home/">Home</a>
    		  <i class="fa fa-circle"></i>
    	      </li>
    	      <li>
    		  <a href="workspace/">Interactive Tool</a>
    		  <i class="fa fa-circle"></i>
    	      </li>
    	      <li>
    	      <span><?php echo $job['toolId']; ?></span>
    	      </li>
    	    </ul>
    	</div>
    	<!-- END PAGE BAR -->

    	<!-- BEGIN PAGE TITLE-->
    	<h1 class="page-title"> <?php echo $job['title']; ?> </h1>
    	<!-- END PAGE TITLE-->

    	<!-- END PAGE HEADER-->
    	<div class="row">
    		<!-- SHOW ERRORS -->
    		<div class="col-md-12">
		<?php
		if(isset($_SESSION['errorData'])) { ?>
    			<div class="alert alert-warning">
    			<?php foreach($_SESSION['errorData'] as $subTitle=>$txts){
    				print "$subTitle<br/>";
    				foreach($txts as $txt){
    					print "<div style=\"margin-left:20px;\">$txt</div>";
    				}
    			}
    			unset($_SESSION['errorData']);
    			?>
    			</div>
		<?php } 
		?>
    		
 	<form action="#" class="horizontal-form" id="tool-input-form">
		    <input type="hidden" name="tool" value="<?php echo $job['toolId'];?>" />
		    <input type="hidden" id="base-url"     value="<?php echo $GLOBALS['BASEURL']; ?>"/>
		    <?php if ($autorefresh){
		        print "<input type=\"hidden\" id=\"autorefresh\" value=\"$autorefresh\"/>\n";
	  	    }?>

	</form>
	
<?php
	$tool_log    = $_SESSION['User']['lastjobs'][$_REQUEST['pid']]['log_file'];
	$stdout_file = $_SESSION['User']['lastjobs'][$_REQUEST['pid']]['stdout_file'];
	$stderr_file = $_SESSION['User']['lastjobs'][$_REQUEST['pid']]['stderr_file'];
	$tool_port   = $_SESSION['User']['lastjobs'][$_REQUEST['pid']]['interactive_tool']['port'];

	$status = ($autorefresh?"disabled":"");
	//$tool_host  = "RStudio_b04cbeb0bc6f0e70"; 
	//$tool_port  = "8787"; 
	//$tool_rootp = "/tool-proxy"; 
	//$tool_rootp = "/interactive"; 

	$tool_rootp = "/".$url_proxy_path; 


?>
	<!-- CONTENT START -->

<!--<iframe src="https://longitools.bsc.es/rstudio/" title="Interactive Tool" noborder="0" width="830" height="8787" scrolling="yes" seamless></iframe>-->


  <br/>

  <a target=_blank <?php echo $status;?> href="launch-interactive/redirectTool.php?port=<?php echo $tool_port;?>" class="btn green">  Interactive Session </a>
<br/>
<br/>
  <a href="workspace/workspace.php?op=openPlainFileFromPath&fnPath=<?php echo urlencode($stdout_file); ?>" class="btn" target="_blank"><i class="fa fa-file-text-o"></i> Job Standard Output</a>

  <pre><?php echo file_get_contents($stdout_file); ?></pre>

<br/>
<br/>
  <a href="workspace/workspace.php?op=openPlainFileFromPath&fnPath=<?php echo urlencode($tool_log); ?>" class="btn" target="_blank"><i class="fa fa-file-text-o"></i> Session Console Log </a>

  <pre id="tool_log" style="max-height:300px;" ><?php echo file_get_contents($tool_log); ?></pre>
<br/>
<br/>
  <a href="workspace/workspace.php?op=openPlainFileFromPath&fnPath=<?php echo urlencode($stderr_file); ?>" class="btn" target="_blank"><i class="fa fa-file-text-o"></i> Job Standard Error</a>
  <pre><?php echo file_get_contents($stderr_file); ?></pre>

<br/>
  <hr/>
<?php die(); ?>
  <pre>-------</pre>
  <hr/>

  <iframe id="if0" src="" frameborder="1px red" style="width:100%;"></iframe>

  <script>
    var iframe = document.getElementById('if0');

    var xhr = new XMLHttpRequest();
    xhr.open('GET', '<?=$proxy_url;?>', true);
    //xhr.setRequestHeader('x-interactive-tool-host', '<?=$tool_host;?>');
    //xhr.setRequestHeader('x-interactive-tool-port', '<?=$tool_port;?>');
    xhr.setRequestHeader('X-RStudio-Root-Path',     '<?=$tool_rootp;?>');
    //xhr.setRequestHeader('X-Forwarded-Proto', 'https://longitools.bsc.es');
    xhr.onreadystatechange = function () {
      if (xhr.readyState === 4 && xhr.status === 200) {
	var iframeDocument = iframe.contentWindow.document;
        iframeDocument.open();
	iframeDocument.write('<base href="<?=$proxy_url;?>">');
	iframeDocument.write('<meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">');
        iframeDocument.write(xhr.responseText);
        iframeDocument.close();
      }
   };
   xhr.send();
  </script>
  <hr/>
  <pre>-------</pre>
  <hr/>


	<iframe id="if1" src="" frameborder="1px red" style="width:100%;"></iframe>
	 <script>
	 async function getSrc() {
	      const res = await fetch("<?=$proxy_url;?>", {
	        	method: 'GET',
		        headers: {
			//"x-interactive-tool-host": "<?=$tool_host;?>",
			//"x-interactive-tool-port": <?=$tool_port;?>,
			"X-RStudio-Root-Path": "<?=$tool_rootp;?>"
		        }
	      });
	      const blob = await res.blob();
	      const urlObject = URL.createObjectURL(blob);
	      document.getElementById('if1').setAttribute("src", urlObject);
	 }
	//getSrc();

	function send(){
    	    var myVar = {"id" : 1};
    	    fetch("<?=$proxy_url;?>", {
		        method: "GET",
			headers: {
			"X-RStudio-Root-Path": "<?=$tool_rootp;?>"
		        }
	    }).then(function(res) {
	        const blob = res.blob();
	        const urlObject = URL.createObjectURL(blob);
	        document.getElementById('if1').setAttribute("src", urlObject);
	    });
	 send();
}


	</script>
  <hr/>


	<?php
	$proxy_url = $GLOBALS['interactive_server'] . "/" . "tool-proxy/";; 
	$headers = array(
		'x-interactive-tool-host: RStudio_b04cbeb0bc6f0e70',
		'x-interactive-tool-port: 8787',
		'Host: $proxy_url',
		'X-RStudio-Root-Path: /tool-proxy',
		'X-Forwarded-Proto: https'
	);

	$options = array(
    		'http' => array(
        		'header' => implode("\r\n", $headers),
			'method' => 'GET', 
    		),
	);

	//$context = stream_context_create($options);

	//echo '<iframe src="' . $proxy_url . '" frameborder="0" style="width:100%; height:500px;" sandbox="allow-same-origin allow-scripts" seamless></iframe>';

	?>

	<!-- CONTENT FINISH -->





    	</div>
    	</div>
	</div>
	<!-- END CONTENT BODY -->
    </div>
    <!-- END CONTENT -->
    <div class="modal fade bs-modal-lg" id="modalDTStep2" tabindex="-1" role="basic" aria-hidden="true">
	<div class="modal-dialog modal-lg">
	    <div class="modal-content">
	    <div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
		<h4 class="modal-title">Select file(s)</h4>
	    </div>
	    <div class="modal-body"><div id="loading-datatable"><div id="loading-spinner">LOADING</div></div></div>
	    <div class="modal-footer">
		<button type="button" class="btn dark btn-outline" data-dismiss="modal">Close</button>
		<button type="button" class="btn green btn-modal-dts2-ok" disabled>Accept</button>
	    </div>
	</div>
	<!-- /.modal-content -->
    </div>

    <!-- /.modal-dialog -->
</div>
    
 
<?php 

require "../htmlib/footer.inc.php"; 
require "../htmlib/js.inc.php";

?>
