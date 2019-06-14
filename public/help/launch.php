<?php

require __DIR__."/../../config/bootstrap.php";
redirectOutside();

?>

<?php require "../htmlib/header.inc.php"; ?>

<body class="page-header-fixed page-sidebar-closed-hide-logo page-content-white page-container-bg-solid page-sidebar-fixed">
  <div class="page-wrapper">

  <?php require "../htmlib/top.inc.php"; ?>
  <?php require "../htmlib/menu.inc.php"; ?>

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
                                  <span>Launch Job</span>
                              </li>
                            </ul>
                        </div>
                        <!-- END PAGE BAR -->
                        <!-- BEGIN PAGE TITLE-->
                        <h1 class="page-title"> Launch Job
                        </h1>
                        <!-- END PAGE TITLE-->
                        <!-- END PAGE HEADER-->

												<div id="html-content-help">

<!-- -------------------- HELP TEXT GOES HERE !!  ------------- -->

          

          <p>To launch a job using the VRE go to Run Tool / Visualizer and choose the tool you would like to use </p>				

					<p><img src="assets/layouts/layout/img/help/Launch01.png" style="width:800px;max-width:100%;" /></p>

          
          <p>Upload prediction files to evaluate and click on compute </p> <p> info please look at the Help section on <a href="https://dev-openebench.bsc.es/vre/help/upload.php">Get Data</a></p>          

          <p><img src="assets/layouts/layout/img/help/Launch02.png" style="width:800px;max-width:100%;" /></p>
          
          <p>This will redirect you back to your workspace and a new job will be added to your files</p>

          <p><img src="assets/layouts/layout/img/help/Launch03.png" /></p>

          <p>Most of the tools have a customized View Results page, which is used to show the users the results of the tool properly. To view this special page users should click the View Results button:</p>

          <p><img src="assets/layouts/layout/img/help/Launch04.png" /></p>
          

												</div>
				
                    </div>
                    <!-- END CONTENT BODY -->
                </div>
                <!-- END CONTENT -->

<?php 

require "../htmlib/footer.inc.php"; 
require "../htmlib/js.inc.php";

?>
