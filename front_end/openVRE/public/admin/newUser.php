<?php

require __DIR__."/../../config/bootstrap.php";
require "../phplib/admin.inc.php";

redirectAdminOutside();


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
                                  <a href="admin/adminUsers.php">Admin Users</a>
                                  <i class="fa fa-circle"></i>
                              </li>
                              <li>
                                  <span>Create new user</span>
                              </li>
                            </ul>
                        </div>
                        <!-- END PAGE BAR -->
                        <!-- BEGIN PAGE TITLE-->
                        <h1 class="page-title"> Create new user </h1>
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

				<form name="newuser" action="applib/newUserFromAdmin2.php" method="post" enctype="multipart/form-data">

				<div class="portlet box blue-oleo">
                                  <div class="portlet-title">
                                      <div class="caption">
                                        <div style="float:left;margin-right:20px;"> <i class="fa fa-user-plus" ></i> User Data</div>
                                      </div>
                                  </div>
                                  <div class="portlet-body form">
                                    <div class="form-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label">Surname</label>
                                                    <input type="text" name="Surname" id="Surname" class="form-control"  placeholder="">
                                                </div>
                                            </div>
																						<div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label">Name</label>
                                                    <input type="text" name="Name" id="Name" class="form-control"  placeholder="">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label">Email</label>
                                                    <input type="text" name="Email" id="Email" class="form-control"  placeholder="<?php echo $_REQUEST['Email'];?>">
                                                </div>
                                            </div>
																						<div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label">Institution</label>
                                                    <input type="text" name="Inst" id="Inst" class="form-control"  placeholder="">
                                                </div>
                                            </div>
                                        </div>
																				<div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label">Country</label>
                                                    <select name="Country" id="Country" class="form-control">
																											<option value=""></option>
																											<?php
				
				$ops = [ 'projection' => [ 'country' => 1 ], 'sort' => [ 'country' => 1 ] ];
				foreach(iterator_to_array($GLOBALS['countriesCol']->find(array(),$ops)) as $k => $v){
																												$selected="";
																												if ($_REQUEST['Country'] == $k)
																												$selected = "selected";
																													?>
																													<option <?php echo $selected; ?> value=<?php echo $k; ?>><?php echo $v['country']; ?></option>
																												<?php } ?>
																											</select>
                                                </div>
                                            </div>
																						<div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label">Type of user</label>
                                                    <select name="Type" id="Type" class="form-control">
                                                		<?php
                                                		if (!$_REQUEST['Type'])
                                                            $_REQUEST['Type']=2;
														foreach($GLOBALS['ROLES'] as $k => $v){
						    							    $selected="";
                                                    		if ($_REQUEST['Type'] == $k)
																$selected = "selected";
						    								?>
						    								<option <?php echo $selected; ?> value=<?php echo $k; ?>><?php echo $v; ?></option>
                                                		<?php } ?>
                                        			</select>
                                                </div>
                                            </div>
                                        </div>
																				<div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label">Disk Quota</label>
																										<div class="input-group">
                                                    		<input type="number" name="diskQuota" id="diskQuota" class="form-control" min="1" max="50" step="1" value="10" placeholder="">
																												<span class="input-group-addon">GB</span>
																										</div>
                                                </div>
                                            </div>
																						<div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label">Sample Data</label>
                                                    <select name="DataSample" id="DataSample" class="form-control">
                                                			<option value="">Select sample data</option>
                                                			<?php
                                                			if (!$_REQUEST['Data'])
                                                                    $_REQUEST['Data'] = $GLOBALS['sampleData_default'];
                                               				$dataList  = scanDir($GLOBALS['sampleData']);
                                               				foreach ($dataList as $data){
                                                                $selected="";
                                                                if ( preg_match('/^\./', $data) || !is_dir($GLOBALS['sampleData']."/$data"))
                                                        		    continue;

                                                    			if ($_REQUEST['Data'] == $data)
																    $selected ="selected";

                                                    			print "<option $selected $enabled value=\"$data\">$data</option>";
                                                			} ?>
                                        			</select>                                                
												</div>
                                            </div>
                                        </div>

										<div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label">Password</label>
																										<?php $pass1 = generatePassword();?>
                                                    <input type="text" name="pass1" id="pass1" class="form-control" value="<?php echo $pass1;?>" > 
                                                </div>
																						</div>
																						<div class="col-md-6">
                                                <div class="form-group">
																										<label class="control-label" style="width:100%;">Send an email with the new password</label>

																										<label class="mt-checkbox  mt-checkbox-outline" style="margin-top:7px;"> Click here if you want to send the password to the new user
                                                        <input type="checkbox" value="1" name="sendEmail" />
																												<span></span>
                                                    </label>
																								</div>
																						</div>
																				</div>
                                    </div>
																		<div class="form-actions">
                                                <button type="submit" class="btn blue"><i class="fa fa-check"></i> Create</button>
                                                <button type="button" class="btn default" onclick="location.href='admin/adminUsers.php';"><i class="fa fa-th-list" aria-hidden="true"></i> Return to users panel</button>
                                                <button type="reset"  class="btn default">Reset </button>
                                            </div>
                                  </div>
                              </div>

					</form>

                        
                    </div>
                    <!-- END CONTENT BODY -->
                </div>
                <!-- END CONTENT -->

                <div class="modal fade bs-modal-sm" id="myModal1" tabindex="-1" role="basic" aria-hidden="true">
                    <div class="modal-dialog modal-sm">
                        <div class="modal-content">
				<?php 
                                if(isset($_SESSION['errorData'])) {
				?>
                                        <div class="alert alert-warning">
                                        <?php foreach($_SESSION['errorData'] as $subTitle=>$txts){
						?>
						   <h4 class="modal-title"><?php echo $subTitle;?></h4>
						</div>
						<div class="modal-body">
						    <?php foreach($txts as $txt){
							print $txt."</br>";
						    }?>
						</div>
						<div class="modal-footer">
						<button type="button" class="btn dark btn-outline" data-dismiss="modal">Accept</button>
						</div>
                                        <?php 
					}
                                        unset($_SESSION['errorData']);
                                        ?>

                                <?php } ?>
                        </div>
                        <!-- /.modal-content -->
                    </div>
                    <!-- /.modal-dialog -->
                </div>

		<div class="modal fade bs-modal-sm" id="myModal5" tabindex="-1" role="basic" aria-hidden="true">
                    <div class="modal-dialog modal-sm">
                        <div class="modal-content">
                        </div>
                        <!-- /.modal-content -->
                    </div>
                    <!-- /.modal-dialog -->
                </div>

<?php 

require "../htmlib/footer.inc.php"; 
require "../htmlib/js.inc.php";

?>
