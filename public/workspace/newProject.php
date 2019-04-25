<?php

require __DIR__ . "/../../config/bootstrap.php";
redirectOutside();

require "../htmlib/header.inc.php";

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
							<span>Create new project</span>
						</li>
					</ul>
				</div>
				<!-- END PAGE BAR -->
				<!-- BEGIN PAGE TITLE-->
				<h1 class="page-title"> Create new project

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
											<span class="caption-subject font-dark bold uppercase">Insert data</span> <small style="font-size:75%;">Please fill the data and metadata for the project</small>
										</div>

									</div>

									<div class="portlet-body">

										<form id="newProject" action="" method="get">
											<input type="hidden" name="op" value="new" />
											<input type="hidden" id="base-url" value="<?php echo $GLOBALS['BASEURL']; ?>" />

											<div class="form-body">
												<div class="row">
													<div class="col-md-12 col-sm-12">
														<div class="form-group">
															<label>Project name *</label>
															<input type="text" class="form-control" name="pr_name" placeholder="Project name">
														</div>
													</div>
												</div>
												<div class="row">
													<div class="col-md-6 col-sm-6">
														<div class="form-group">
															<label>Description (optional)</label>
															<textarea class="form-control" name="pr_ldesc" placeholder="Long description" rows="5"></textarea>
														</div>
													</div>
													<div class="col-md-6 col-sm-6">
														<div class="form-group">
															<label>Keywords list (optional, separated by commas)</label>
															<textarea class="form-control" name="pr_keywords" placeholder="Keywords list" rows="5"></textarea>
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
										<!--<button class="btn green" type="submit" id="btn-run-files" style="margin-top:20px;" >Run Selected Files</button>-->
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