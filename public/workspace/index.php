<?php

require __DIR__ . "/../../config/bootstrap.php";
redirectOutside();


// Print header

require "../htmlib/header.inc.php";

// Merge pending files and retrieved data compute data disk space

$usedDisk             = getUsedDiskSpace();
$diskLimit            = $_SESSION['User']['diskQuota']; // getDiskLimit();
$usedDiskPerc         = sprintf('%f', ($usedDisk / $diskLimit) * 100);
$usedDiskPerc         = number_format($usedDiskPerc, 1, '.', '');

if ($usedDisk < $diskLimit) {
	$_SESSION['accionsAllowed'] = "enabled";
} else {
	$_SESSION['accionsAllowed'] = "disabled";
	$usedDiskPerc = 100;
}

$kk = $GLOBALS['toolsCol']->find(array("external" => true), array("input_files_combinations_internal" => true));

// Tools list
$dtlist = ( (isset($_REQUEST["tool"]) && $_REQUEST["tool"] != "")?  getAvailableDTbyTool($_REQUEST["tool"]) : array() );

// project list
$projects = getProjects_byOwner();

//update files workspace content (job and files)

$allFiles = getFilesToDisplay(array('_id' => $_SESSION['User']['dataDir']), null);

$files = ( isset($dtlist['list'])? filterFiles_by_dataType($allFiles, $dtlist["list"]) : $allFiles );
$files = addTreeTableNodesToFiles($files);

$proj_name_active   = getAttr_fromGSFileId($_SESSION['User']['dataDir'], "name");

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
							<span><?php echo getProject($_SESSION['User']['dataDir'])["name"]; ?> Workspace</span>
						</li>
					</ul>
				</div>
				<!-- END PAGE BAR -->
				<!-- BEGIN PAGE TITLE-->
				<h1 class="page-title"> <?php echo getProject($_SESSION['User']['dataDir'])["name"]; ?> Workspace

					<div class="btn-group" style="float:right;">
						<a class="btn green" href="javascript:;" data-toggle="dropdown">
							<i class="fa fa-cogs"></i> Project actions
							<i class="fa fa-angle-down"></i>
						</a>
						<ul class="dropdown-menu pull-right">
							<li>
								<a href="workspace/editProject.php?id=<?php echo $_SESSION['User']['dataDir']; ?>">
									<i class="fa fa-pencil-square-o"></i> Edit current project
								</a>
							</li>
							<li>
								<a href="javascript:deleteProject('<?php echo $_SESSION['User']['dataDir']; ?>', '<?php echo getProject($_SESSION['User']['dataDir'])["name"]; ?>')">
									<i class="fa fa-trash-o"></i> Delete current project
								</a>
							</li>
							<li class="divider"> </li>
							<li>
								<a href="workspace/newProject.php">
									<i class="fa fa-plus"></i> Create new project
								</a>
							</li>
							<li>
								<a href="workspace/listOfProjects.php">
									<i class="fa fa-list"></i> View all my projects
								</a>
							</li>
						</ul>
					</div>

					<div class="input-group" style="float:right; width:200px; margin-right:10px;">
						<span class="input-group-addon" style="background:#5e738b;"><i class="fa fa-sitemap font-white"></i></span>
						<select class="form-control" id="select_project" onchange="loadProjectWS(this);">
							<?php foreach ($projects as $p_id => $p) {
								$selected = (($_SESSION['User']['dataDir'] == $p_id) ? "selected" : ""); ?>
								<option value="<?php echo $p_id; ?>" <?php echo $selected; ?>><?php echo $p['name']; ?></option>
							<?php } ?>
						</select>
					</div>
				</h1>
				<!-- END PAGE TITLE-->
				<!-- END PAGE HEADER-->

				<?php if (isset($_REQUEST["from"]) && $_REQUEST["from"]) { ?>

					<div class="row">
						<div class="col-md-12" style="margin-bottom:30px;">
							<?php require "../tools/" . $_REQUEST["from"] . "/assets/ws/btn-modal.php"; ?>
						</div>
					</div>

				<?php	} ?>

				<div class="row">
					<div class="col-md-12">
						<?php
						if ($_SESSION['User']['Type'] == 100) { ?>
							<div class="alert alert-warning">
								Your request for a premium user account is being processed. In the meantime, you can use the platform as a common user.
							</div>
						<?php }

					if ($_SESSION['User']['Type'] == 3) {

						?>

							<div class="profile-content">
								<div class="row">
									<div class="col-md-12">
										<div class="portlet light ">
											<div class="portlet-title tabbable-line">
												<div class="caption">
													<i class="icon-globe theme-font hide"></i>
													<span class="caption-subject font-dark bold uppercase">Restore Link</span>
													<label class="control-label" style="margin: 0 2px; vertical-align:middle;"><i class="icon-question tooltips" data-container="body" data-html="true" data-placement="right" data-original-title="<p align='left' style='margin:5px;'>Using the following link, you'll be able to restore your session. Please, copy and save it if you are interested in recovering your data even after the current session ends. Pasting back the URL in your web browser will allow you to keep working on your data up to 10 days after your first access.</p><p align='left' style='margin:5px' >For being granted a permanent workspace, sign-in into VRE!</p>"></i></label>
													<small style="font-size:75%;">Use this link to restore current session in the following 7 days:</small>
												</div>
											</div>
											<div class="portlet-body">
												<div class="tab-content">


													<div class="tab-pane active" id="tab_1_1">
														<div class="input-group">
															<input id="mt-target-1" type="text" class="form-control" value="<?php echo $GLOBALS['URL'] . "?id=" . $_SESSION['User']['_id']; ?>" readonly style="background:#fff;">
															<span class="input-group-btn">
																<button class="btn green mt-clipboard" data-clipboard-action="copy" data-clipboard-target="#mt-target-1" type="button"><i class="fa fa-copy"></i> Copy to clipboard</button>
															</span>
														</div>
														<br />

													</div>

												</div>
											</div>
										</div>
									</div>
								</div>
							</div>

						<?php

					}

						// show Error messages		
						print printErrorDivision();


						// fetch tool of lists
						$toolsList = getTools_List();
						sort($toolsList);
						?>

							<!-- BEGIN EXAMPLE TABLE PORTLET -->

							<div class="row">
								<div class="col-md-12 col-sm-12">

									<div class="portlet light bordered">

										<div class="portlet-title">
											<div class="caption">
												<i class="icon-share font-dark hide"></i>
												<span class="caption-subject font-dark bold uppercase">Select File(s)</span> <small style="font-size:75%;">Please select the file or files you want to use</small>
											</div>
											<div class="actions">
												<a href="<?php echo $GLOBALS['BASEURL']; ?>workspace/" class="btn green"> Reload Workspace </a>
											</div>
										</div>

										<div class="portlet-body">

											<div class="input-group" style="margin-bottom:20px;">
												<span class="input-group-addon" style="background:#5e738b;"><i class="fa fa-wrench font-white"></i></span>
												<select class="form-control" style="width:100%;" onchange="loadWSTool(this)">
													<option value="">Filter files by tool</option>
													<?php foreach ($toolsList as $tl) { ?>
														<option value="<?php echo $tl["_id"]; ?>" <?php if (isset($_REQUEST["tool"]) && $_REQUEST["tool"] == $tl["_id"]) echo 'selected'; ?>><?php echo $tl["name"]; ?></option>
													<?php } ?>
												</select>
											</div>

											<div id="loading-datatable">
												<div id="loading-spinner">LOADING</div>
											</div>

											<form name="gesdir" action="workspace/workspace.php" method="post" enctype="multipart/form-data">
												<input type="hidden" name="op" value="" />
												<input type="hidden" id="base-url" value="<?php echo $GLOBALS['BASEURL']; ?>" />
											<?php
											if (isset($_REQUEST["userId"])){
												print "input type=\"hidden\" id=\"userId\" value=\"".$_SESSION['userId']."\"";
											}
											if (isset($_REQUEST["tool"])){
												print "input type=\"hidden\" id=\"toolSelected\" value=\"".$_REQUEST['tool']."\"";
											}
											if (isset($_REQUEST["from"])){
												print "<input type=\"hidden\" id=\"from\" value=\"".$_REQUEST['from']."\"";
											} 	
											
											// print FILES in TABLE

											print printTable($files);
											?>

											</form>
											<!--<button class="btn green" type="submit" id="btn-run-files" style="margin-top:20px;" >Run Selected Files</button>-->
										</div>
									</div>
									<!-- END EXAMPLE TABLE PORTLET-->


								</div>
							</div>
						</div>
					</div>

					<div class="row">
						<div class="col-md-12 col-sm-12">
							<div class="portlet light bordered">
								<div class="portlet-title">
									<div class="caption">
										<i class="icon-share font-dark hide"></i>
										<span class="caption-subject font-dark bold uppercase">Manage Files</span>
									</div>
									<div class="actions" style="display:none!important;" id="btn-av-tools">
										<div class="btn-group">
											<a class="btn btn-sm blue-madison" href="javascript:;" data-toggle="dropdown">
												<i class="fa fa-cogs"></i> Actions
												<i class="fa fa-angle-down"></i>
											</a>
											<ul class="dropdown-menu pull-right" role="menu">
												<li><a href="javascript:downloadAllFiles();"><i class="fa fa-download"></i> Download selected files </a></li>
												<li><a href="javascript:editAllFiles();"><i class="fa fa-pencil"></i> Edit selected files metadata </a></li>
												<li><a href="javascript:deleteAllFiles();"><i class="fa fa-trash-o"></i> Delete selected files </a></li>
												<li><a href="javascript:moveAllFiles();"><i class="fa fa-exchange"></i> Move selected files </a></li>
											</ul>
										</div>
										<div class="btn-group">
											<a class="btn btn-sm purple-intense" id="visualization" href="javascript:;" data-toggle="dropdown">
												<i class="fa fa-eye"></i> Visualization
												<i class="fa fa-angle-down"></i>
											</a>
											<ul class="dropdown-menu pull-right" id="visualizers_list" role="menu"> </ul>
										</div>
										<div class="btn-group">
											<a class="btn btn-sm blue-dark" id="av_tools" href="javascript:;" data-toggle="dropdown">
												<i class="fa fa-wrench"></i> Available Tools
												<i class="fa fa-angle-down"></i>
											</a>
											<ul class="dropdown-menu pull-right" id="av_tools_list" role="menu"> </ul>
										</div>
									</div>
								</div>

								<div class="portlet-body">
									<div class="" data-always-visible="1" data-rail-visible="0">
										<ul class="feeds" id="list-files-run-tools"></ul>
										<div id="desc-run-tools">In order to run the tools on the files, please select them clicking on the checkboxes from the table above.</div>
									</div>
									<div class="scroller-footer">
										<a class="btn btn-sm red pull-right display-hide" id="btn-rmv-all" href="javascript:;">
											<i class="fa fa-times-circle"></i> Clear all files from list
										</a>
									</div>
								</div>
							</div>
						</div>
					</div>
					<?php
						
					// show Error messages          
					print printErrorDivision();

					?>
							
					<!-- SUMMARY AND DISK QUOTA ROW -->


					<?php
					$toolsHelp = getTools_Help();
					$toolsList = getTools_List();
					//var_dump($toolsHelp);
					sort($toolsList);
					?>


					<div class="row">
						<div class="col-md-12 col-sm-12">
							<div class="portlet light bordered">
								<div class="portlet-title">
									<div class="caption">
										<i class="icon-share font-dark hide"></i>
										<span class="caption-subject font-dark bold uppercase">TOOLS' HELP</span>

										<?php
										if (isset($_REQUEST["tool"]) && $_REQUEST["tool"] != "") {
											$expcol = "collapse";
											$portlet = "";
											?>

											<small>Below users can find all the possible data type combinations for the selected tool</small>

										<?php
									} else {
										$expcol = "expand";
										$portlet = "portlet-collapsed";
										?>

											<small style="font-size:75%;">Below users can find all the possible data type combinations for each tool (click expand button)</small>

										<?php } ?>

									</div>
									<div class="tools">
										<a href="javascript:;" class="<?php echo $expcol; ?>"></a>
									</div>
								</div>
								<div class="portlet-body <?php echo $portlet; ?>">

									<?php if (isset($_REQUEST["tool"]) && $_REQUEST["tool"] != "") { ?>

										<!--<p>Below users can find all the possible data type combinations for the selected tool:</p>-->

									<?php } else { ?>

										<!--<p>Below users can find all the possible data type combinations for each tool:</p>-->

									<?php } ?>

									<?php if (isset($_REQUEST["tool"]) && $_REQUEST["tool"] != "") { ?>

										<div class="panel-group accordion" id="accordion1">
											<?php
											$c = 0;
											foreach ($toolsList as $tl) {

												if ($tl["_id"] == $_REQUEST["tool"]) {
													?>
													<div class="panel panel-default">
														<div class="panel-heading">
															<h4 class="panel-title">
																<a class="accordion-toggle accordion-toggle-styled " data-toggle="collapse" data-parent="#accordion1" href="#collapse_<?php echo $c; ?>"> <?php echo $tl["name"]; ?> </a>
															</h4>
														</div>
														<div id="collapse_<?php echo $c; ?>" class="panel-collapse in">
															<div class="panel-body">
																<table class="table">
																	<thead>
																		<tr>
																			<th>Operations</th>
																			<th>File(s) required</th>
																			<th>File format</th>
																			<th>File type</th>
																		</tr>
																	</thead>
																	<tbody>
																		<?php
																		$count = 0;
																		foreach ($toolsHelp as $th) {

																			if ($th["id"] == $tl["_id"]) { ?>
																				<?php
																				$cc = 1;
																				foreach ($th["content"] as $content) { ?>
																					<?php if ($cc == 1) {
																						$trclass = "first-tr";
																					} else {
																						$trclass = "";
																					} ?>
																					<tr class="<?php echo $trclass; ?>">
																						<?php if ($cc == 1) { ?>
																							<td rowspan="<?php echo sizeof($th["content"]); ?>"><?php echo $th["operation"]; ?></td>
																						<?php } ?>
																						<td><?php echo $content["description"]; ?></td>
																						<td><?php echo implode("<br>", $content["format"]); ?></td>
																						<td><?php echo implode("<br>", $content["data_type"]); ?></td>
																					</tr>
																					<?php
																					$cc++;
																				} ?>

																			<?php
																		}
																	}

																	?>
																	</tbody>
																</table>
															</div>
														</div>
													</div>
												<?php
											}
											$c++;
										}
										?>
										</div>

									<?php } else { ?>

										<div class="panel-group accordion" id="accordion1">
											<?php
											$c = 0;
											foreach ($toolsList as $tl) {
												?>
												<div class="panel panel-default">
													<div class="panel-heading">
														<h4 class="panel-title">
															<a class="accordion-toggle accordion-toggle-styled collapsed" data-toggle="collapse" data-parent="#accordion1" href="#collapse_<?php echo $c; ?>"> <?php echo $tl["name"]; ?> </a>
														</h4>
													</div>
													<div id="collapse_<?php echo $c; ?>" class="panel-collapse collapse">

														<div class="panel-body">
															<table class="table ws-help-tools">
																<thead>
																	<tr>
																		<th>Operations</th>
																		<th>File(s) required</th>
																		<th>File format</th>
																		<th>File type</th>
																	</tr>
																</thead>
																<tbody>
																	<?php
																	$count = 0;
																	foreach ($toolsHelp as $th) {

																		if ($th["id"] == $tl["_id"]) { ?>
																			<?php
																			$cc = 1;
																			foreach ($th["content"] as $content) { ?>
																				<?php if ($cc == 1) {
																					$trclass = "first-tr";
																				} else {
																					$trclass = "";
																				} ?>
																				<tr class="<?php echo $trclass; ?>">
																					<?php if ($cc == 1) { ?>
																						<td rowspan="<?php echo sizeof($th["content"]); ?>"><?php echo $th["operation"]; ?></td>
																					<?php } ?>
																					<td><?php echo $content["description"]; ?></td>
																					<td><?php echo implode("<br>", $content["format"]); ?></td>
																					<td><?php echo implode("<br>", $content["data_type"]); ?></td>
																				</tr>
																				<?php
																				$cc++;
																			} ?>

																		<?php
																	}
																}

																?>
																</tbody>
															</table>
														</div>
													</div>
												</div>
												<?php
												$c++;
											}
											?>
										</div>

									<?php }  ?>

								</div>
							</div>
						</div>
					</div>


					<div class="row">
						<div class="col-lg-6 col-xs-12 col-sm-12">
							<div class="portlet light bordered">
								<div class="portlet-title">
									<div class="caption">
										<i class="icon-share font-dark hide"></i>
										<span class="caption-subject font-dark bold uppercase">LAST JOBS</span>
									</div>
								</div>
								<div class="portlet-body">
									<div class="scroller" style="height: 204px;" data-always-visible="1" data-rail-visible="0">
										<?php
										print printLastJobs($allFiles);
										?>
									</div>
								</div>
							</div>
						</div>

						<div class="col-lg-6 col-xs-12 col-sm-12">
							<div class="portlet light tasks-widget bordered">
								<div class="portlet-title">
									<div class="caption">
										<i class="icon-share font-dark hide"></i>
										<span class="caption-subject font-dark bold uppercase">DISK USE</span>
									</div>

								</div>
								<div class="portlet-body">

									<div id="loading-knob">
										<div id="loading-spinner">LOADING</div>
									</div>

									<div id="disk-home">
										<input class="knob" data-fgColor="#006b8f" data-bgColor="#eeeeee" readonly value="<?php echo $usedDiskPerc; ?>" />

										<div id="info-disk-home">
											You are using <strong><?php echo formatSize($usedDisk); ?></strong> from your <strong><?php echo formatSize($diskLimit); ?></strong> of disk quota.
										</div>

										<div id="extra-space-home">
											<?php if (allowedRoles($_SESSION['User']['Type'], $GLOBALS['NO_GUEST'])) { ?>
												Do you need extra disk space? Click the button below to contact us!
												<br><br>
												<a href="<?php echo $GLOBALS['BASEURL']; ?>helpdesk/?sel=space" class="btn green">I need more disk space</a>
											<?php } else { ?>
												Do you want extra disk space? Click the button below to register!
												<br><br>
												<a href="<?php echo $GLOBALS['URL_login']; ?>" class="btn green">Register to VRE</a>
											<?php } ?>
										</div>
									</div>

								</div>
							</div>
						</div>
					</div>
					<!-- END SUMMARY AND DISK QUOTA ROW -->

				</div>
				<!-- END CONTENT BODY -->
			</div>
			<!-- END CONTENT -->

			<div class="modal fade bs-modal-sm" id="myModal1" tabindex="-1" role="basic" aria-hidden="true">
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
							<h4 class="modal-title">Warning!</h4>
						</div>
						<div class="modal-body"> You have more than one file selected. If you go ahead, this tool will just be applied to the selected file. </div>
						<div class="modal-footer">
							<button type="button" class="btn dark btn-outline" data-dismiss="modal">Cancel</button>
							<button type="button" class="btn green btn-modal-ok">Accept</button>
						</div>
					</div>
					<!-- /.modal-content -->
				</div>
				<!-- /.modal-dialog -->
			</div>

			<div class="modal fade bs-modal" id="modalNGL" tabindex="-1" role="basic" aria-hidden="true">
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
							<h4 class="modal-title"></h4>
						</div>
						<div class="modal-body">
							<div id="loading-viewport" style="position:absolute;left:42%; top:200px;"><img src="assets/layouts/layout/img/ring-alt.gif" /></div>
							<div id="viewport" style="width:100%; height:500px;background:#ddd;"></div>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn dark btn-outline" data-dismiss="modal">Close</button>
						</div>
					</div>
				</div>
			</div>

			<div class="modal fade bs-modal" id="modalGuest" tabindex="-1" role="basic" aria-hidden="true">
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header">
							<h4 class="modal-title"></h4>
							Welcome to MuG Virtual Research Environment!
						</div>
						<div class="modal-body">
							<!--As an <strong>anonymous user</strong>, you can access to all VRE functionalities, yet your workpace is not permanent!<br/>
If you want to <strong>re-use your session</strong>, make sure you save the <strong><em>'Restore link'</em></strong> that appears on your workspace. Otherwise, your data will be unreachable in the moment the session ends. <br/>
														<img source=""/>-->
							As an <strong>anonymous user</strong>, you can access to all VRE functionalities, yet your workspace is not permanent!
						</div>
						<div class="modal-footer">
							<button type="button" class="btn dark btn-outline" data-dismiss="modal">Understood</button>
						</div>
					</div>
				</div>
			</div>

			<div class="modal fade bs-modal" id="modalTADkit" tabindex="-1" role="basic" aria-hidden="true">
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
							<h4 class="modal-title"></h4>
						</div>
						<div class="modal-body">
							<div id="container-tad" style="width: 100%;height: 500px;">
								<!-- <tadkit-viewer id="viewer" color="93AEBF" previews='[{"file_type": "tad","file_url": "visualizers/tadkit/tadkit-viewer/samples/tk-example-dataset-2K.json"}]'></tadkit-viewer>
-->
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


			<div class="modal fade bs-modal" id="modalAnalysis" tabindex="-1" role="basic" aria-hidden="true">
				<div class="modal-dialog modal-lg">
					<div class="modal-content">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
							<h4 class="modal-title">Execution Summary</h4>
						</div>
						<div class="modal-body table-responsive"></div>
						<div class="modal-footer">
							<button type="button" class="btn dark btn-outline" data-dismiss="modal">Close</button>
						</div>
					</div>
				</div>
			</div>

			<div class="modal fade bs-modal" id="modalRename" tabindex="-1" role="basic" aria-hidden="true">
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
							<h4 class="modal-title">Rename</h4>
						</div>
						<div class="modal-body table-responsive">
							<div id="loading-rename">Retriving data <i class="fa fa-spinner fa-spin"></i></div>
							<div id="form-rename" class="display-hide">
								<form action="">
									<div class="form-group" id="">
										<label class="control-label">Change name</label>
										<div class="input-group">
											<input type="text" class="form-control " name="" id="new-name" placeholder="Please enter the file name" value="">
											<div class="input-group-btn">
												<button type="button" class="btn green" data-toggle="dropdown" id="submit-rename">Submit <i class="fa fa-check"></i></button>
											</div>
										</div>
									</div>
								</form>
								<div class="alert alert-danger display-hide"> </div>
							</div>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn dark btn-outline" data-dismiss="modal">Close</button>
						</div>
					</div>
				</div>
			</div>

			<div class="modal fade bs-modal" id="modalMove" tabindex="-1" role="basic" aria-hidden="true">
				<div class="modal-dialog modal-lg">
					<div class="modal-content">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
							<h4 class="modal-title">Move</h4>
						</div>
						<div class="modal-body table-responsive">
							<div id="loading-move">Retriving data <i class="fa fa-spinner fa-spin"></i></div>
							<p>File X currently located at Y</p>
							<div class="row display-hide" id="move-file">
								<div class="col-md-3" id="col-1-move">
									<div class="form-group">
										<label class="control-label">Select project</label>
										<select name="" id="project-name" class="form-control"></select>
									</div>
								</div>
								<div class="col-md-3" id="col-2-move">
									<div class="form-group">
										<label class="control-label">Select execution</label>
										<select name="" id="execution-name" class="form-control"></select>
									</div>
								</div>
								<div class="col-md-3" id="col-3-move">
									<div class="form-group">
										<label class="control-label">File name</label>
										<input type="text" class="form-control " name="" id="new-name-move-file" placeholder="Please enter the file name" value="">
									</div>
								</div>
								<div class="col-md-3" id="col-4-move">
									<div class="form-group">
										<label class="control-label"> &nbsp;</label>
										<button type="button" class="btn green form-control" data-toggle="dropdown" id="submit-move">Submit <i class="fa fa-check"></i></button>
									</div>
								</div>
							</div>
							<div class="alert alert-danger display-hide"> </div>
							<!-- TODO: MOVE DIR!!! -->
						</div>
						<div class="modal-footer">
							<button type="button" class="btn dark btn-outline" data-dismiss="modal">Close</button>
						</div>
					</div>
				</div>
			</div>


			<div class="modal fade bs-modal" id="modalProgress" tabindex="-1" role="basic" aria-hidden="true">
				<div class="modal-dialog modal-lg">
					<div class="modal-content">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
							<h4 class="modal-title">Progress for execution</h4>
						</div>
						<div class="modal-body table-responsive">
							<div style="max-height: calc(100vh - 255px);padding-left:20px;">
								<div id="meta-progress" style="padding: 0 0 0px 0; color:#949494"></div>
								<div id="meta-log" class="display-hide" style="padding: 0 0 20px 0; color:#949494"></div>
							</div>
						</div>
						<div class="modal-footer" style="text-align:left;">
							<button type="button" class="btn default" id="btn-modal-progress">View Progress</button>
							<button type="button" class="btn green" id="btn-modal-log">View Raw Log</button>
							<button type="button" class="btn dark btn-outline" data-dismiss="modal" style="right: 15px;position: absolute;">Close</button>
						</div>
					</div>
				</div>
			</div>


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

			<?php

			if (isset($_REQUEST["from"]) && $_REQUEST["from"]) {

				require "../tools/" . $_REQUEST["from"] . "/assets/ws/modal.php";
			}



			?>


			<?php
			require "../htmlib/footer.inc.php";
			require "../htmlib/js.inc.php";

			?>
