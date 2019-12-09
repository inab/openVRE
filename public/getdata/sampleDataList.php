<?php

require __DIR__."/../../config/bootstrap.php";
redirectOutside();


$sampleList = getSampleDataList();
/*
    $sampleList  = scanDir($GLOBALS['sampleData']);
foreach ($sampleList as $sample){
    if ( preg_match('/^\./', $sample) || !is_dir($GLOBALS['sampleData']) )
        continue;
    $sampleName=$sample;
    //print "<option value=\"$sample\">$sampleName</option>";
}
 */
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
                                  <span>From Example Dataset</span>
                              </li>
                            </ul>
                        </div>
                        <!-- END PAGE BAR -->
                        <!-- BEGIN PAGE TITLE-->
                        <h1 class="page-title">Import Example Dataset
                        </h1>
                        <!-- END PAGE TITLE-->
												<!-- END PAGE HEADER-->

												<div class="row">
													<div class="col-md-12">
													<?php  
														$error_data = false;
														if (isset($_SESSION['errorData']) && $_SESSION['errorData']){ 
															$error_data = true;
														?>
														<?php if (isset($_SESSION['errorData']['Info']) && $_SESSION['errorData']['Info']) { ?> 
															<div class="alert alert-info">
														<?php } else { ?>
															<div class="alert alert-danger">
														<?php } ?>
															
<?php
														foreach($_SESSION['errorData'] as $subTitle=>$txts){
																		print "<strong>$subTitle</strong><br/>";
																	foreach($txts as $txt){
																		print "<div>$txt</div>";
															}
														}
															unset($_SESSION['errorData']);
															?>
															</div>
															<?php } ?>
														</div>
													</div>
											
											<form name="sampleDataForm" id="sampleDataForm"  action="applib/getData.php" method="post"  class="horizontal-form">

													<div class="portlet box blue-oleo">
                                  <div class="portlet-title">
                                      <div class="caption">
																				<div style="float:left;margin-right:20px;"> <i class="fa fa-database" ></i> Select example dataset</div>
                                      </div>
                                  </div>
                                  <div class="portlet-body form">
                                    <div class="form-body">
                                        
																				<div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
																										<input type="hidden"  name="uploadType" value="sampleData"/>
																										<label class="control-label">List of example datasets</label>
                                    <select class="form-control form-field-enabled valid select2naf" name="sampleData[]" id="sampleData" aria-invalid="false">
																		<option value="">Please select a sample</option>
																		<?php

                                    foreach ($sampleList as $sampleId => $sample){
                                        $sampleName=$sample['name'];
																				?><option value="<?php echo /*rtrim($sample['sample_path'], "/");*/ $sample['_id']; ?>"><?php echo $sampleName;?></option><?php
                                    }
                                    ?>
                                    </select>
                                                </div>
                                            </div>
																						
																				</div>

																				<div class="row">
																						<div class="col-md-12">
																							<p class="font-grey-mint" >
																		<?php
                                    foreach ($sampleList as $sampleId => $sample){
																			?><span class="display-hide sample-description" id="<?php echo /*rtrim($sample['sample_path'], "/");*/ $sample['_id']; ?>">
																				<i class="fa fa-sticky-note" aria-hidden="true"></i> <?php echo $sample['short_description'];?>
																			</span><?php
                                    }
																		?>
																							</p>
                                            </div>
																						
                                        </div>
																				
                                    </div>
																		<div class="form-actions">
                                                <button type="submit" class="btn blue" id="btn-sample"><i class="fa fa-check"></i> Import</button>
                                            </div>
                                  </div>
                              </div>

					</form>

	<!-- INIT
                        <div class="row">
                            <div class="col-md-12">
                                <div class="portlet light portlet-fit bordered">
                                  <div class="portlet-title">
                                    <div class="caption">
                                        <i class="icon-share font-red-sunglo hide"></i>
                                        <span class="caption-subject font-dark bold uppercase">Select sample data</span>
                                    </div>
																	</div>

													


									 

									<div class="portlet-body form">
                                    
												<form name="sampleDataForm" id="sampleDataForm"  action="applib/getData.php" method="post"  class="horizontal-form">
														<div class="form-body">
															<div class="row">
                                            <div class="col-md-12">
															<div class="form-group">
                                    <input type="hidden"  name="uploadType" value="sampleData"/>
                                    <select class="form-control form-field-enabled valid select2naf" name="sampleData[]" id="sampleData" aria-invalid="false">
																		<option value="">Please select a sample</option>
																		<?php
                                    $sampleList  = scanDir($GLOBALS['sampleData']);
                                    foreach ($sampleList as $sample){
                                       if ( preg_match('/^\./', $sample) || !is_dir($GLOBALS['sampleData']) )
                                         continue;
                                        $sampleName=$sample;
                                        ?><option value="<?php echo $sample;?>"><?php echo $sampleName;?></option><?php
                                    }
                                    ?>
                                    </select>
																</div>
																</div>
																</div>
															</div>
                                <div class="form-actions">
                                    <button type="submit" class="btn blue" style="float:right;">
                                    <i class="fa fa-check"></i> Import</button>
                                </div>

                                </form>
                                </div>
																- END EXAMPLE TABLE PORTLET

                            </div>
                        </div>
                    </div>-->
                    <!-- END CONTENT BODY -->
                </div>
                <!-- END CONTENT -->

<?php 

require "../htmlib/footer.inc.php"; 
require "../htmlib/js.inc.php";

?>
