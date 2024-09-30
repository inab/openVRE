<?php

require __DIR__."/../../config/bootstrap.php";
redirectOutside();

require "../htmlib/header.inc.php";

// load project from REQUEST
 
if (!isset($_REQUEST['id'])){
    $_SESSION['errorData']['Error'][]="In order to rename or edit a project, please, first select it from the 'Project' drop down menu";
    redirect($GLOBALS['BASEURL']."workspace/");
}

$project = getProject($_REQUEST['id']);

if (!isset($project['name'])){
    $_SESSION['errorData']['Error'][]="Sorry, the selected project code (".$_REQUEST['id'].") is not valid or your user have no access to it. Please, contact <a href=\"mailto:".$GLOBALS['helpdeskMail']."\">us to reporting this error.</a>";
    redirect($GLOBALS['BASEURL']."workspace/");
}


//format project atime time
if (is_object($project['atime']))
       $project['atime'] =$project['atime']->toDateTime()->format('U');
$project['atime'] = strftime('%Y/%m/%d %H:%M', $project['atime']);


?>


<body class="page-header-fixed page-sidebar-closed-hide-logo page-content-white page-container-bg-solid page-sidebar-fixed">
  <div class="page-wrapper">

  <?php
   require "../htmlib/top.inc.php"; 
   require "../htmlib/menu.inc.php";
  
  ?>

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
				  <a href="workspace/">Workspace</a>
				  <i class="fa fa-circle"></i>
			      </li>
			      <li>
				  <span>Edit project <?php echo getProject($_SESSION['User']['dataDir'])["name"]; ?></span>
			      </li>
			    </ul>
			</div>
			<!-- END PAGE BAR -->
			<!-- BEGIN PAGE TITLE-->
			<h1 class="page-title"> Edit project <?php echo getProject($_SESSION['User']['dataDir'])["name"]; ?>
			    
			</h1>
			<!-- END PAGE TITLE-->
			<!-- END PAGE HEADER-->

			<div class="row">
			    <div class="col-md-12">
               	
					
						<!-- BEGIN EXAMPLE TABLE PORTLET -->
				<div class="row">
			  <div class="col-md-12 col-sm-12">

				<div class="portlet light bordered">

					<div class="portlet-title">
							<div class="caption">
					    	<i class="icon-share font-dark hide"></i>
					    	<span class="caption-subject font-dark bold uppercase">Edit data</span> <small style="font-size:75%;">Please edit the data and metadata for the project</small>
							</div>

						</div>

				    <div class="portlet-body">

					<form id="newProject" action="" method="get">
					        <input type="hidden" name="op"     value="edit"/>
                            <input type="hidden" name="pr_id"  value="<?php echo $_REQUEST['id'];?>"/>
							<input type="hidden" id="base-url" value="<?php echo $GLOBALS['BASEURL']; ?>"/>

              <div class="form-body">
                <div class="row">
      			      <div class="col-md-12 col-sm-12">
                    <div class="form-group">
                        <label>Project name *</label>
                        <input type="text" class="form-control" name="pr_name" value="<?php echo $project['name'];?>">
                    </div>
                  </div>
                </div>
                <div class="row">
      			      <div class="col-md-6 col-sm-6">
                    <div class="form-group">
												<label>Description (optional)</label>
                                                <textarea class="form-control" name="pr_ldesc" rows="5"><?php echo $project['description'];?></textarea>
                    </div>
                  </div>
                  <div class="col-md-6 col-sm-6">
                    <div class="form-group">
                        <label>Keywords list (optional, separated by commas)</label>
                        <textarea class="form-control" name="pr_keywords" placeholder="Keywords list" rows="5"><?php echo $project['keywords'];?></textarea>
                    </div>
                  </div>
                </div>
              </div>
                <div class="row">
      			      <div class="col-md-6 col-sm-6">
                    <div class="form-group">
                        <label>Created</label>
                        <input type="text" class="form-control" name="created" value="<?php echo $project['atime'];?>" readonly>
                    </div>
                  </div>
                  <div class="col-md-6 col-sm-6">
                    <div class="form-group">
						<label>Type</label>
                        <input type="text" class="form-control" name="type" value="<?php echo $project['project_type'];?>" readonly>
                    </div>
                  </div>
                </div>
              </div>
              <div class="form-actions">
									<div class="row">
											<div class="col-md-12">
													<button type="submit" class="btn green"><i class="fa fa-check"></i> Submit</button>
													<button type="reset" class="btn grey-salsa btn-outline">Reset</button>
											</div>
									</div>
							</div>

					</form>
				    </div>
				</div>
				<!-- END EXAMPLE TABLE PORTLET-->


			    </div>
			</div>               

 
					</div>
			</div>
								

		    </div>
		    <!-- END CONTENT BODY -->
		</div>
		<!-- END CONTENT -->


<?php 

require "../htmlib/footer.inc.php"; 
require "../htmlib/js.inc.php";

?>


