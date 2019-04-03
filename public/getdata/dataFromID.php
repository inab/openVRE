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
                                  <a href="home/">Home</a>
                                  <i class="fa fa-circle"></i>
                              </li>
                              <li>
                                  <span>Get Data</span>
                                  <i class="fa fa-circle"></i>
                              </li>
                             	<li>
                                  <span>From ID Code</span>
                              </li>
                            </ul>
                        </div>
                        <!-- END PAGE BAR -->
                        <!-- BEGIN PAGE TITLE-->
                        <h1 class="page-title"> Upload file from ID Code
                        </h1>
                        <!-- END PAGE TITLE-->
                        <!-- END PAGE HEADER-->

												<div class="mt-element-step">
                                    <div class="row step-line">
                                        <div class="mt-step-desc">
																				Please select a code from a Data Bank.
																				</div>

										<?php require "../htmlib/stepsup.inc.php"; ?>	
										
                                    </div>
                                </div>

												<form name="uploadFromID" id="uploadFromID" action="javascript:;" method="post">

													<div class="alert alert-danger display-hide" id="alert-down-form">
														Error downloading file, please, try again.
													</div>

													<input type="hidden" name="baseURL" id="base-url" value="<?php echo $GLOBALS['BASEURL']; ?>" />
													<input type="hidden" name="uploadType" value="id" />

													<div class="row">
												
														<div class="col-md-6">	
															<div class="form-group " id="">
																<label>Select Data Bank</label>
																<select name="databank" id="databank" class="form-control" >
																	<option value="">Please select a Data Bank</option>
																	<option value="pdb">Protein Data Bank</option>
																</select>
															</div>
														</div>
	
														<div class="col-md-6">	
															<div class="form-group " id="">
																<label>Insert ID</label>
																<input type="text" maxlength="4" name="idcode" id="idcode" class="form-control" placeholder="ID" disabled>
																<img class="Typeahead-spinner" src="assets/layouts/layout/img/loading-spinner-blue.gif" style="display: none;">	
															</div>
														</div>

													</div>

													<div class="form-actions btn-send-data">
				  									<input type="submit" class="btn green snd-metadata-btn" id="send_data" value="SEND DATA" style="position:relative;z-index:20;" disabled>
				  								</div>
		
													<div class="progress-bar-down progress display-hide">
														<div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100" style="width:0%;">
															<span class="sr-only"> 20% Complete </span>
														</div>
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
