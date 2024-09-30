<?php

require __DIR__."/../../config/bootstrap.php";

redirectAdminOutside();


#
## find active jobs

// get users with jobs to examinate
$jobs_per_user = getAllUserJobs();
$users_withJobs = array_keys($jobs_per_user);

// update job status
foreach ($users_withJobs as $u){
    updatePendingFiles($u);
}
// get user's job updated
$jobs_per_user = getAllUserJobs();

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
                                  <span>Admin</span>
                                  <i class="fa fa-circle"></i>
                              </li>
                              <li>
                                  <span>Admin Users</span>
                              </li>
                            </ul>
                        </div>
                        <!-- END PAGE BAR -->
                        <!-- BEGIN PAGE TITLE-->
                        <h1 class="page-title"> Job Administration
                            <small>Manage user's jobs</small>
                        </h1>
                        <!-- END PAGE TITLE-->
                        <!-- END PAGE HEADER-->

												<div class="row">
													<div class="col-md-12">
													<?php  
														$error_data = false;
														if ($_SESSION['errorData']){ 
															$error_data = true;
														?>
														<?php if ($_SESSION['errorData']['Info']) { ?> 
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



                        <div class="row">
                            <div class="col-md-12">
                                <!-- BEGIN EXAMPLE TABLE PORTLET-->
                                <div class="portlet light portlet-fit bordered">

                                    <div class="portlet-body">

			 						<input type="hidden" id="base-url" value="<?php echo $GLOBALS['BASEURL']; ?>" />

                                        <table class="table table-striped table-hover table-bordered" id="sample_editable_1">
                                            <thead>
                                                <tr>
                                                    <th> Job id </th>
                                                    <th> Status </th>
                                                    <th> Tool </th>
                                                    <th> Execution </th>
                                                    <th> User </th>
                                                    <th> Submission time </th>
                                                    <th> Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
												<?php
		                                        foreach($jobs_per_user as $login => $jobs){
	        										foreach($jobs as $pid => $job){
												?>
												<tr>
				                                    <td><?php echo $pid;?></td>
													<td>
														<?php 
														$colorRole = '';
														if($job['state'] == "RUNNING") $colorRole = 'font-blue bold';
														echo "<span class='$colorRole'>".$job['state']."</span>"; 
														?>
													</td>
                                	    			<td><?php echo $job['toolId'];?></td>
                                                    <td><?php echo $job['execution'];
                                                        $execution_path = fromAbsPath_toPath($job["working_dir"]);
                                                        $execution_id   = getGSFileId_fromPath($execution_path,1);
                                                        ?>
                                                            <a href="javascript:viewFileMeta('<?php echo $pid;?>', '<?php echo $job['execution'];?>', 2,'<?php echo $login;?>');" style="margin-left:5px;"><i class="fa fa-info-circle"></i></a>
                                                    </td>
                                       				<td><?php echo $login;?></td>
                                                    <td><?php if ($job['start_time']){
                                                            echo date("F d Y H:i:s",$job['start_time']);
                                                        }elseif (is_file($job['config_file'])){
                                                            echo date ("F d Y H:i:s",filemtime($job['config_file']));
                                                        }else{
                                                            echo "ND";
                                                        }?>
                                                    </td>
													<td>
														  <div class="btn-group">
														  <button class="btn btn-xs red dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false"> Actions
                                                              <i class="fa fa-angle-down"></i>
                                                          </button>
                                                          <ul class="dropdown-menu pull-right" role="menu">
                                                              <li>
                                                              <a class="enable" href="applib/delJob.php?pid=<?php echo $pid;?>&user=<?php echo $login;?>">
                                                                      <i class="fa fa-check-circle"></i> Cancel job user</a>
                                                              </li>
                                                              <li>
                                                              <a class="" href="applib/loginImpersonate.php?id=<?php echo $login;?>">
                                                                    <i class="fa fa-user"></i> Impersonate user</a>
                                                              </li>
                                                          </ul>
														  </div>
													</td>
												</tr>
												<?php
                                                    }
                                                };
												?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <!-- END EXAMPLE TABLE PORTLET-->
                            </div>
                        </div>
                    </div>
                    <!-- END CONTENT BODY -->
                </div>
                <!-- END CONTENT -->

								<div class="modal fade bs-modal" id="modalMeta" tabindex="-1" role="basic" aria-hidden="true">
					<div class="modal-dialog modal-lg">
						<div class="modal-content">
							<div class="modal-header">
								<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
								<h4 class="modal-title">Project info</h4>
							</div>
							<div class="modal-body table-responsive">
								<div id="meta-container" style="max-height: calc(100vh - 255px); ">						
								<div id="meta-summary">
									
								</div>
								
																</div>
							</div>
							<div class="modal-footer">
								<button type="button" class="btn dark btn-outline" data-dismiss="modal">Close</button>
							</div>
						</div>
					</div>
				</div>

								<div class="modal fade bs-modal" id="modalDelete" tabindex="-1" role="basic" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                                <h4 class="modal-title">Warning!</h4>
                            </div>
                            <div class="modal-body">Are you sure you want to delete the selected file?
                             </div>
                            <div class="modal-footer">
                                <button type="button" class="btn dark btn-outline" data-dismiss="modal">Cancel</button>
								<button type="button" class="btn red btn-modal-del">Delete</button>
                            </div>
                        </div>
                    </div>
				</div>


                <div class="modal fade bs-modal-sm" id="myModal1" tabindex="-1" role="basic" aria-hidden="true">
                    <div class="modal-dialog modal-sm">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                                <h4 class="modal-title">Warning!</h4>
                            </div>
                            <div class="modal-body"> You can't add a new user until you have finished editing the current one. </div>
                            <div class="modal-footer">
                                <button type="button" class="btn dark btn-outline" data-dismiss="modal">Accept</button>
                            </div>
                        </div>
                        <!-- /.modal-content -->
                    </div>
                    <!-- /.modal-dialog -->
                </div>

                <div class="modal fade" id="myModal2" tabindex="-1" role="basic" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                                <h4 class="modal-title">Validation error</h4>
                            </div>
                            <div class="modal-body"> Please fill in all fields and be sure that the e-mail has a proper format and the disk quota value is between 1 and 100 </div>
                            <div class="modal-footer">
                                <button type="button" class="btn dark btn-outline" data-dismiss="modal">Accept</button>
                            </div>
                        </div>
                        <!-- /.modal-content -->
                    </div>
                    <!-- /.modal-dialog -->
                </div>

                <div class="modal fade bs-modal-sm" id="myModal3" tabindex="-1" role="basic" aria-hidden="true">
                    <div class="modal-dialog modal-sm">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                                <h4 class="modal-title">Warning!</h4>
                            </div>
                            <div class="modal-body"> You can't edit a user until you have finished editing the new one. </div>
                            <div class="modal-footer">
                                <button type="button" class="btn dark btn-outline" data-dismiss="modal">Accept</button>
                            </div>
                        </div>
                        <!-- /.modal-content -->
                    </div>
                    <!-- /.modal-dialog -->
                </div>

				<div class="modal fade bs-modal-sm" id="myModal5" tabindex="-1" role="basic" aria-hidden="true">
                    <div class="modal-dialog modal-sm">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                                <h4 class="modal-title">Error!</h4>
                            </div>
                            <div class="modal-body"> Something happened updating data, please try again. </div>
                            <div class="modal-footer">
                                <button type="button" class="btn dark btn-outline" data-dismiss="modal">Accept</button>
                            </div>
                        </div>
                        <!-- /.modal-content -->
                    </div>
                    <!-- /.modal-dialog -->
                </div>

				<div class="modal fade bs-modal-sm" id="myModal6" tabindex="-1" role="basic" aria-hidden="true">
                    <div class="modal-dialog modal-sm">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                                <h4 class="modal-title">Error!</h4>
                            </div>
                            <div class="modal-body"> A user with this email already exists in our database, please try with different data. </div>
                            <div class="modal-footer">
                                <button type="button" class="btn dark btn-outline" data-dismiss="modal">Accept</button>
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
