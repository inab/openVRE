<?php

require __DIR__."/../../config/bootstrap.php";

redirectToolDevOutside();

if(!isset($_REQUEST['id'])) {

	$_SESSION['errorData']['Error'][] = "Please provide a tool id.";
	redirect($GLOBALS['BASEURL'].'admin/myNewTools.php');
	
}

$toolDevJSON = $GLOBALS['toolsDevMetaCol']->findOne(array('_id' => $_REQUEST['id']));

if(!isset($toolDevJSON)) {
	$_SESSION['errorData']['Error'][] = "The tool id <strong>".$_REQUEST['toolid']."</strong> doesn't exist in our database.";
	redirect($GLOBALS['BASEURL'].'admin/myNewTools.php');
}

$toolDevMetaJSON = $GLOBALS['toolsDevMetaCol']->findOne(array('_id' => $_REQUEST['id'], 'user_id' => $_SESSION['User']['id']));

if(!isset($toolDevMetaJSON) && ($_SESSION['User']['Type'] != 0)) {
		$_SESSION['errorData']['Error'][] = "The tool id <strong>".$_REQUEST['toolid']."</strong> you are trying to edit doesn't belong to you.";
			redirect($GLOBALS['BASEURL'].'admin/myNewTools.php');
}

//vaR_dump();

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
                                  <a href="admin/myNewTools.php">My new tools</a>
                                  <i class="fa fa-circle"></i>
                              </li>
                              <li>
                                  <span>Add VM URL</span>
                              </li>
                            </ul>
                        </div>
                        <!-- END PAGE BAR -->
                        <!-- BEGIN PAGE TITLE-->
                        <h1 class="page-title"> Add VM URL</h1>
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

				<form name="vm-url" id="vm-url" action="applib/updateToolDevURL.php" method="post" >

				<div class="portlet box blue-oleo">
                                  <div class="portlet-title">
                                      <div class="caption">
																				<div style="float:left;margin-right:20px;"> <i class="fa fa-cloud-upload" ></i> Please provide the URL of your VM</div>
                                      </div>
                                  </div>
                                  <div class="portlet-body form">
                                    <div class="form-body">
                                        <div class="row">
                                            <div class="col-md-12">
																							<p>How are you going to submit your code?</p>
																							<?php 
																							$active_vm = "active";
																							$checked_vm = "checked";
																							$disabled_vm = "";
																							$value_vm = "";
																							if($toolDevMetaJSON["step2"]["type"] == "vm") { 
																								$active_vm = "active";
																								$checked_vm = "checked";
																								$disabled_vm = "";
																								$value_vm = $toolDevMetaJSON["step2"]["tool_code"];
																							} else if($toolDevMetaJSON["step2"]["type"] != "") {
																								$active_vm = "";
																								$checked_vm = "";
																								$disabled_vm = "disabled";
																								$value_vm = "";
																							} ?>
																							<!--<div id="vm-block" class="vm-url-block <?php echo $active_vm; ?>">
																								<div class="form-group">
																										<div class="mt-radio-list">
																												<label class="mt-radio mt-radio-outline"> VM
																												<i target="_blank" class="tooltips icon-question" data-toggle="tooltip" data-trigger="hover" data-placement="right" title="What's that?"></i>
																														<input type="radio" value="vm" name="type" <?php echo $checked_vm; ?> />
																														<span></span>
																												</label>
																											</div>
																									</div>
																									<div class="form-group">
																											<input type="text" name="vm-code" id="vm" value="<?php echo $value_vm; ?>" class="form-control mandatory-vm" placeholder="URL for your VM" <?php echo $disabled_vm; ?>>
																									</div>
																								</div>-->
																								<?php if($toolDevMetaJSON["step2"]["type"] == "git") { 
																									$active_git = "active";
																									$checked_git = "checked";
																									$disabled_git = "";
																									$value_git = $toolDevMetaJSON["step2"]["tool_code"];
																								} else {
																									$active_git = "";
																									$checked_git = "";
																									$disabled_git = "disabled";
																									$value_git = "";
																								} 
																								//////////////////////////////
																								$active_git = "active";
																								$checked_git = "checked";
																								$disabled_git = "";
																								//////////////////////////////
																								?>
																								<div id="git-block" class="vm-url-block <?php echo $active_git; ?>">
																									<div class="form-group">
																											<div class="mt-radio-list">
																													<label class="mt-radio mt-radio-outline"> Git repo
																													<i target="_blank" class="tooltips icon-question" data-toggle="tooltip" data-trigger="hover" data-placement="right" title="What's that?"></i>
																															<input type="radio" value="git" name="type" <?php echo $checked_git; ?> />
																															<span></span>
																													</label>
																											</div>
																									</div>
																									<div class="form-group">
																											<input type="text" name="vm-code" id="git"  value="<?php echo $value_git; ?>" class="form-control mandatory-vm" placeholder="Git URL for your VM"  <?php echo $disabled_git; ?>>
																									</div>
																								</div>
                                            </div>
                                        </div>
                                        
                                    </div>
																		<div class="form-actions">
																								<a href="admin/myNewTools.php" class="btn btn-default">BACK</a>
																								<input type="hidden" name="toolid" value="<?php echo $_REQUEST['id']; ?>" />
                                                <button type="submit" class="btn blue" style="float:right;"><i class="fa fa-check"></i> Submit</button>
                                                <!--<button type="reset" class="btn default">Reset</button>-->
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

<?php 

require "../htmlib/footer.inc.php"; 
require "../htmlib/js.inc.php";

?>
