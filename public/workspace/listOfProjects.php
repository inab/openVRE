<?php

require __DIR__ . "/../../config/bootstrap.php";
redirectOutside();

require "../htmlib/header.inc.php";

// project list
$projects = getProjects_byOwner();

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
							<span>List of projects</span>
						</li>
					</ul>
				</div>
				<!-- END PAGE BAR -->
				<!-- BEGIN PAGE TITLE-->
				<h1 class="page-title"> List of projects

				</h1>
				<!-- END PAGE TITLE-->
				<!-- END PAGE HEADER-->

				<!-- BEGIN EXAMPLE TABLE PORTLET -->
				<div class="row">
					<div class="col-md-12 col-sm-12">

						<div class="portlet light bordered">

							<div class="portlet-title">
								<div class="caption">
									<i class="icon-share font-dark hide"></i>
									<span class="caption-subject font-dark bold uppercase">Select a project</span>
								</div>

							</div>

							<!-- CHANGE: new id "portlet-ws" -->
							<div class="portlet-body" id="portlet-lp">

								<input type="hidden" id="base-url" value="<?php echo $GLOBALS['BASEURL']; ?>" />

								<?php

								// TO MODIFY WITH NEW "secondary kws"	
								$kw = array();
								foreach ($projects as $t) {
									$k = explode(",", $t['keywords']);
									$kw = array_merge($kw, $k);
								}

								$kw = array_unique($kw);
								sort($kw);

								?>

								<div class="row" style="margin-top:20px;">
									<div class="col-md-6">
										<div class="form-group">
											<label class="control-label">Search by text</label>
											<input class="form-control form-field-enabled valid" type="text" name="simpSearch" id="simp-search" placeholder="Write something" />
										</div>
									</div>
									<div class="col-md-6">
										<div class="form-group">
											<label class="control-label">Search by keywords</label>
											<select class="form-control form-field-enabled valid" id="sel-keyword" name="selKey[]" aria-invalid="false" multiple="multiple">
												<option value=""></option>
												<?php foreach ($kw as $k) { ?>
													<option value="<?php echo $k; ?>"><?php echo $k; ?></option>
												<?php } ?>
											</select>
										</div>
									</div>
								</div>

								<div class="row">
									<div class="col-md-12">

										<div id="loading-datatable">
											<div id="loading-spinner">LOADING</div>
										</div>

										<table id="projects-list" class="table table-striped table-bordered">
											<thead>
												<tr>
													<th>Project</th>
													<th>Description</th>
													<th>Created</th>
													<th>Actions</th>
													<!-- hidden columns -->
													<th>Keywords</th>
												</tr>
											</thead>
											<tbody>
												<?php foreach ($projects as $pr) {  ?>
													<tr>
														<td><?php echo $pr["name"]; ?></td>
														<td><?php echo $pr["description"]; ?></td>
														<td><?php echo strftime('%Y/%m/%d %H:%M', $pr['atime']->toDateTime()->format('U')) ?></td>
														<td>
															<a href="workspace/editProject.php?id=<?php echo $pr["_id"]; ?>">
																<i class="fa fa-pencil-square-o tooltips" aria-hidden="true" data-container="body" data-html="true" data-placement="top" data-original-title="<p align='left' style='margin:0'>Click here to edit this project.</p>"></i>
															</a>
															<a href="javascript:deleteProject('<?php echo $pr["_id"]; ?>', '<?php echo $pr["name"]; ?>')">
																<i class="fa fa-trash-o tooltips" aria-hidden="true" data-container="body" data-html="true" data-placement="top" data-original-title="<p align='left' style='margin:0'>Click here to delete this project.</p>"></i>
															</a>
														</td>
														<td><?php echo $pr["keywords"]; ?></td>
													</tr>
												<?php } ?>
											</tbody>
										</table>
									</div>
								</div>

							</div>
						</div>
						<!-- END EXAMPLE TABLE PORTLET-->


					</div>
				</div>


			</div>
			<!-- END CONTENT BODY -->
		</div>
		<!-- END CONTENT -->


		<div class="modal fade bs-modal" id="modalDeleteProject" tabindex="-1" role="basic" aria-hidden="true">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
						<h4 class="modal-title">Warning!</h4>
					</div>
					<div class="modal-body">Are you sure you want to delete the project <strong><?php echo getProject($_SESSION['User']['dataDir'])["name"]; ?></strong>
						and <strong>ALL</strong> its executions and files? This action cannot be undone.
					</div>
					<div class="modal-footer">
						<button type="button" class="btn dark btn-outline" data-dismiss="modal">Cancel</button>
						<button type="button" class="btn red btn-modal-del">Delete</button>
					</div>
				</div>
			</div>
		</div>

		<?php

		require "../htmlib/footer.inc.php";
		require "../htmlib/js.inc.php";

		?>
