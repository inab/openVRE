<?php

require __DIR__."/../../config/bootstrap.php";

redirectAdminOutside();

$user = $GLOBALS['usersCol']->findOne(array('id' => $_REQUEST['id']));

if($user['Type'] == 0) {
	$_SESSION['errorData']['Error'][] = "You are trying to edit an admin user.";
	redirect($GLOBALS['URL'].'admin/adminUsers.php');
}

/*$tls = $GLOBALS['toolsCol']->find();
$tls = $GLOBALS['visualizersCol']->find();*/

$tls = getTools_List();
$vlzrs = getVisualizers_List();

$tlsvlzrs = array_merge($tls, $vlzrs);
sort($tlsvlzrs);

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
                                  <span>Edit user <?php echo $user["Name"].' '.$user["Surname"]; ?></span>
                              </li>
                            </ul>
                        </div>
                        <!-- END PAGE BAR -->
                        <!-- BEGIN PAGE TITLE-->
                        <h1 class="page-title"> Edit user <?php echo $user["Name"].' '.$user["Surname"]; ?> </h1>
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

				<form name="newuser" action="applib/updateUser.php" method="post" enctype="multipart/form-data">

				<div class="portlet box blue-oleo">
                                  <div class="portlet-title">
                                      <div class="caption">
                                        <div style="float:left;margin-right:20px;"> <i class="fa fa-user" ></i> User Data</div>
                                      </div>
                                  </div>
                                  <div class="portlet-body form">
                                    <div class="form-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label">Surname</label>
																										<input type="text" name="Surname" id="Surname" class="form-control"  value="<?php echo $user["Surname"]; ?>">
                                                </div>
                                            </div>
																						<div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label">Name</label>
                                                    <input type="text" name="Name" id="Name" class="form-control"  value="<?php echo $user["Name"]; ?>">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label">Email</label>
                                                    <input type="text" name="Email" id="Email" class="form-control"  value="<?php echo $user["Email"]; ?>" readonly>
                                                </div>
                                            </div>
																						<div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label">Institution</label>
                                                    <input type="text" name="Inst" id="Inst" class="form-control"  value="<?php echo $user["Inst"]; ?>">
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
				foreach(iterator_to_array($GLOBALS['countriesCol']->find(array(), $ops)) as $k => $v){
																												$selected="";
																												if ($user["Country"] == $k)
																												$selected = "selected";
																													?>
																													<option <?php echo $selected; ?> value=<?php echo $k; ?>><?php echo $v['country']; ?></option>
																												<?php } ?>
																											</select>
                                                </div>
                                            </div>
																						<div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label">Disk Quota</label>
																										<div class="input-group">
																										<input type="number" name="diskQuota" id="diskQuota" class="form-control" min="1" max="100" step="1" value="<?php echo ((($user["diskQuota"]/1024)/1024)/1024); ?>" placeholder="">
																												<span class="input-group-addon">GB</span>
																										</div>
                                                </div>
                                            </div>
                                        </div>
																				<div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label">Type of user</label>
                                                    <select name="Type" id="Type" class="form-control">
                                                		<?php
                                                		if (!$user['Type']) $_REQUEST['Type']=2;
																											
																										foreach($GLOBALS['ROLES'] as $k => $v){
						    																		  $selected="";
                                                    	if ($user['Type'] == $k) $selected = "selected";
						    																		?>
						    																			<option <?php echo $selected; ?> value=<?php echo $k; ?>><?php echo $v; ?></option>
                                                		<?php } ?>
                                        			</select>
                                                </div>
                                            </div>
																						<div class="col-md-6">
                                                
                                            </div>
                                        </div>

																				<?php 
																				$dispTools = "";
																				$stTools = "enabled";
																				if($user['Type'] != 1) {
																					$dispTools = "display-hide";
																					$stTools = "disabled";
																				}
																				?>

																				<div class="row tools_select <?php echo $dispTools; ?>">
                                            <div class="col-md-12">
                                            	<div class="form-group">
																								<label class="control-label">Tools permissions <i class="icon-question tooltips" data-container="body" data-html="true" data-placement="right" data-original-title="<p align='left' style='margin:0'>Select the tools this user will be able to administer.</p>"></i></label>
																								<select class="form-control form-field-enabled valid select2tools" name="tools[]" id="tools" aria-invalid="false" multiple="multiple" <?php echo $stTools; ?>>
																									<option value=""></option>
																									<?php
																									foreach ( $tlsvlzrs as $id => $value ) {
																											$selected="";
                                                    	if (in_array($value['_id'], $user['ToolsDev'])) $selected = "selected";

																											echo "<option value='".$value['_id']."' $selected>".$value['name']."</option>";
																									}
																									?>
																								</select>
																							</div>
                                            </div>
                                        </div>

                                    </div>
																		<div class="form-actions">
                                                <button type="submit" class="btn blue"><i class="fa fa-check"></i> Update</button>
                                                <button type="reset" class="btn default">Reset</button>
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
