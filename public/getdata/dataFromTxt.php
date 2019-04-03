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
                    <div class="page-content">
                        <!-- BEGIN PAGE HEADER-->
                        <!-- BEGIN PAGE BAR -->
                        <div class="page-bar">
                            <ul class="page-breadcrumb">
															<li>
                                  <a href="/home/">Home</a>
                                  <i class="fa fa-circle"></i>
                              </li>
                              <li>
                                  <span>Get Data</span>
                                  <i class="fa fa-circle"></i>
                              </li>
                             	<li>
                                  <span>From Text</span>
                              </li>
                            </ul>
                        </div>
                        <!-- END PAGE BAR -->
                        <!-- BEGIN PAGE TITLE-->
                        <h1 class="page-title"> Insert Text Data
                        </h1>
                        <!-- END PAGE TITLE-->
												<!-- END PAGE HEADER-->

											<div class="mt-element-step">
                                    <div class="row step-line">
                                        <div class="mt-step-desc">
																				Please insert below your data. We need to assign a file name to the provided data in order to convert it into a file for a more easy processing.
																				</div>

										<?php require "../htmlib/stepsup.inc.php"; ?>	
										
                                    </div>
                                </div>

	
											
												<form name="uploadFromTxt" id="uploadFromTxt" action="javascript:;" method="post">
												
													<div class="form-group " id="">
				        						<label>File Name</label>
				        						<input type="text" name="filename" id="filename" class="form-control" placeholder="Insert your file name here">
				  								</div>
	
													<div class="form-group " id="">
				        						<label>Text Data</label>
				        						<textarea name="txtdata" id="txtdata" class="form-control" rows="6" placeholder="Insert your text data here"></textarea>
				  								</div>

													<div class="form-actions btn-send-data">
				  									<input type="submit" class="btn green snd-metadata-btn" value="SEND DATA" style="position:relative;z-index:20;" >
				  								</div>

												</form>
                    </div>
                    <!-- END CONTENT BODY -->
                </div>
                <!-- END CONTENT -->

<?php 

require "../htmlib/footer.inc.php"; 
require "../htmlib/js.inc.php";

?>
