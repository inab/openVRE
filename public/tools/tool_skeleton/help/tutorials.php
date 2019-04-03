<?php

require "../../../phplib/genlibraries.php";
redirectOutside();

$help = pathinfo($_SERVER['PHP_SELF'], PATHINFO_FILENAME);
$a = explode("/", dirname($_SERVER['PHP_SELF']));
$tool = $a[sizeof($a) - 2];

$toolData = $GLOBALS['toolsCol']->findOne(array('_id' => $tool));

?>

<?php require "../../../htmlib/header.inc.php"; ?>

<body class="page-header-fixed page-sidebar-closed-hide-logo page-content-white page-container-bg-solid page-sidebar-fixed">
  <div class="page-wrapper">

  <?php require "../../../htmlib/top.inc.php"; ?>
  <?php require "../../../htmlib/menu.inc.php"; ?>

	<!-- BEGIN CONTENT -->
		<div class="page-content-wrapper">
				<!-- BEGIN CONTENT BODY -->
				<div class="page-content" id="body-help">
						<!-- BEGIN PAGE HEADER-->
						<!-- BEGIN PAGE BAR -->
						<div class="page-bar">
								<ul class="page-breadcrumb">
									<li>
										<a href="/home/">Home</a>
										<i class="fa fa-circle"></i>
									</li>
									<li>
											<span>Help</span>
											<i class="fa fa-circle"></i>
									</li>
									<li>
											<a href="help/tools.php">Tools</a>
											<i class="fa fa-circle"></i>
									</li>
									<li>
											<a href="tools/<?php echo $toolData["_id"]; ?>/help/help.php"><?php echo $toolData["name"]; ?></a>
											<i class="fa fa-circle"></i>
									</li>
									<li>
											<span>Tutorials</span>
									</li>
								</ul>
						</div>
						<!-- END PAGE BAR -->
						<!-- BEGIN PAGE TITLE-->
						<h1 class="page-title"> <span id="tit-static"><?php echo $toolData["name"]; ?> Tutorials</span>
						</h1>
						<!-- END PAGE TITLE-->
						<!-- END PAGE HEADER-->

						<div id="html-content-help">Videos Coming Soon</div>

				</div>
				<!-- END CONTENT BODY -->
		</div>
		<!-- END CONTENT -->



<?php 

require "../../../htmlib/footer.inc.php"; 
require "../../../htmlib/js.inc.php";

?>
