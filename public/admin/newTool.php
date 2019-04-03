<?php

require __DIR__."/../../config/bootstrap.php";
redirectToolDevOutside();


//**************************
// Integrate new tool
//**************************

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
                                  <span>Create new tool</span>
                              </li>
                            </ul>
                        </div>
                        <!-- END PAGE BAR -->
                        <!-- BEGIN PAGE TITLE-->
                        <h1 class="page-title"> Create new tool</h1>
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

				<form name="new-tool" id="new-tool" action="applib/createNewToolID.php" method="post" >

				<div class="portlet box blue-oleo">
                                  <div class="portlet-title">
                                      <div class="caption">
																				<div style="float:left;margin-right:20px;"> <i class="fa fa-plus" ></i> First of all, you must provide a new and unique tool ID</div>
                                      </div>
                                  </div>
                                  <div class="portlet-body form">
                                    <div class="form-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label">
																											Insert new tool id
																										</label>
																										<input type="text" name="toolid" id="toolid" value="" class="form-control" >
                                                </div>
                                            </div>
                                        </div>
                                        
                                    </div>
																		<div class="form-actions">
                                                <button type="submit" class="btn blue"><i class="fa fa-check"></i> Submit</button>
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

<?php 

require "../htmlib/footer.inc.php"; 
require "../htmlib/js.inc.php";

?>
