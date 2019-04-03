<?php

require __DIR__."/../../config/bootstrap.php";
redirectOutside();

if(!isset($_REQUEST['project']) || !isset($_REQUEST['tool'])){

	$_SESSION['errorData']['Error'][]="You should select a tool and a project to view results";
	redirect('/workspace/');

}

?>

<?php require "../htmlib/header.inc.php"; ?>

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
                                  <a href="workspace/">User Workspace</a>
                                  <i class="fa fa-circle"></i>
                              </li>
                              <li>
                                  <span>Tools</span>
                                                               </li>
                            </ul>
                        </div>
                        <!-- END PAGE BAR -->
                        <!-- BEGIN PAGE TITLE-->
                        <h1 class="page-title"> Loading Results <img src="assets/layouts/layout/img/loading-spinner-blue.gif" style="margin-left:10px;" /></h1>
                        <!-- END PAGE TITLE-->
                        <!-- END PAGE HEADER-->
                    </div>
                    <!-- END CONTENT BODY -->
                </div>
                <!-- END CONTENT -->

<?php 

require "../htmlib/footer.inc.php"; 
require "../htmlib/js.inc.php";

?>

<?php

// setting custom visualizer working_dir
//

$wd  = $GLOBALS['dataDir']."/".$_SESSION['User']['id']."/.tmp/outputs_".$_REQUEST['project'];
$indexFile = $wd.'/index';

$results =Array();
if(is_dir($wd)) {

	// check if content uncompressed

	if(file_exists($indexFile)) {
	
		$results = file($indexFile);
		//var_dump($results);

	}

}else{

	// create $wd

	mkdir($wd);
	touch($indexFile);

}


// Get internal results
//

if(!count($results)) {

	$files = $GLOBALS['filesCol']->findOne(array('_id' => $_REQUEST['project']), array('files' => 1, '_id' => 0));

	foreach($files["files"] as $id) {

		$fMeta = iterator_to_array($GLOBALS['filesMetaCol']->find(array('_id' => $id,
																																		'data_type'  => "tool_statistics",
																																		'format'     =>'TAR',
																																		'compressed' =>"gzip")));
		if(count($fMeta) ) {
			$path = $GLOBALS['dataDir']."/".getAttr_fromGSFileId($id,'path');
			exec("tar --touch -xzf \"$path\" -C \"$wd\" 2>&1", $err);

			if(!count($err)) {

				$fp = fopen($indexFile, 'a');
				fwrite($fp, $id);
				fclose($fp);

			} else { echo "error!!!!"; }
		}
	}

	$results = file($indexFile);

}


//redirect('/tools/'.$_REQUEST['tool'].'output.php?project='.$_REQUEST['project']);

?>

<script>
location.href="<?php echo '/tools/'.$_REQUEST['tool'].'/output.php?project='.$_REQUEST['project']; ?>"
</script>
