<?php

// set seccion for each page

$currentSection = '';
$currentSubSection = '';
$currentSubSubSection = '';
$currentSubSubSubSection =  '';

switch (pathinfo($_SERVER['PHP_SELF'])['filename']) {
	case 'index':
		if (basename(dirname($_SERVER['PHP_SELF'])) == 'home') {
			$currentSection = 'hp';
		} elseif (basename(dirname($_SERVER['PHP_SELF'])) == 'helpdesk') {
			$currentSection = 'hd';
		} elseif (basename(dirname($_SERVER['PHP_SELF'])) == 'cookies') {
			$currentSection = '';
		} elseif (basename(dirname($_SERVER['PHP_SELF'])) == 'launch') {
			$currentSection = 'lt';
		} else {
			$currentSection = 'uw';
		}
		break;
	case 'input':
	case 'output':
		$currentSection = 'lt';
		break;
	case 'newProject':
		$currentSection = 'uw';
		break;
	case 'editFile':
		$currentSection = 'dt';
		break;
	case 'uploadForm':
	case 'uploadForm2':
		$currentSection = 'dt';
		$currentSubSection = 'lc';
		break;
	case 'general':
		$currentSection = 'he';
		$currentSubSection = 'h1';
		break;
	case 'starting':
		$currentSection = 'he';
		$currentSubSection = 'h2';
		break;
	case 'upload':
		$currentSection = 'he';
		$currentSubSection = 'h3';
		break;
	case 'ws':
		$currentSection = 'he';
		$currentSubSection = 'h4';
		break;
	case 'launch':
		$currentSection = 'he';
		$currentSubSection = 'h5';
		break;
	case 'tools':
		$currentSection = 'he';
		$currentSubSection = 'h6';
		break;
	case 'hdesk':
		$currentSection = 'he';
		$currentSubSection = 'h7';
		break;
	case 'related':
		$currentSection = 'he';
		$currentSubSection = 'h8';
		break;
	case 'refs':
		$currentSection = 'he';
		$currentSubSection = 'h9';
		break;
	case 'ackn':
		$currentSection = 'he';
		$currentSubSection = 'h10';
		break;
	case 'visualizers':
		$currentSection = 'he';
		$currentSubSection = 'h11';
		break;
	case 'datasets':
		$currentSection = 'dt';
		$currentSubSection = 'rp';
		$currentSubSubSection = 'bs';
		break;
	case 'dataFromID':
		$currentSection = 'dt';
		$currentSubSection = 'id';
		break;
	case 'sampleDataList':
		$currentSection = 'dt';
		$currentSubSection = 'sd';
		break;
	case 'usrProfile':
		$currentSection = 'up';
		$currentSubSection = 'mp';
		break;
	case 'dashboard':
		$currentSection = 'ad';
		$currentSubSection = 'ds';
		break;
	case 'adminUsers':
		$currentSection = 'ad';
		$currentSubSection = 'au';
		break;
	case 'adminJobs':
		$currentSection = 'ad';
		$currentSubSection = 'aj';
		break;
	case 'help':
		$currentSection = 'he';
		$a = explode("/", dirname($_SERVER['PHP_SELF']));
		if ($a[1] == "tools") {
			$currentSubSection = 'h6';
		} else {
			$currentSubSection = 'h11';
		}
		$currentSubSubSection = $a[sizeof($a) - 2];
		break;
	case 'toolhelp':
		parse_str($_SERVER['QUERY_STRING'], $queries);
		$currentSection = 'he';
		$currentSubSection = 'h6';
		$currentSubSubSection = $queries['tool'];
		$currentSubSubSubSection = substr($queries['sec'], 0, 3);
		break;
	case 'method':
		$currentSection = 'he';
		$currentSubSection = 'h6';
		$a = explode("/", dirname($_SERVER['PHP_SELF']));
		$currentSubSubSection = $a[sizeof($a) - 2];
		$currentSubSubSubSection = 'met';
		break;
	case 'inputs':
		$currentSection = 'he';
		$currentSubSection = 'h6';
		$a = explode("/", dirname($_SERVER['PHP_SELF']));
		$currentSubSubSection = $a[sizeof($a) - 2];
		$currentSubSubSubSection = 'inp';
		break;
	case 'outputs':
		$currentSection = 'he';
		$currentSubSection = 'h6';
		$a = explode("/", dirname($_SERVER['PHP_SELF']));
		$currentSubSubSection = $a[sizeof($a) - 2];
		$currentSubSubSubSection = 'out';
		break;
	case 'results':
		$currentSection = 'he';
		$currentSubSection = 'h6';
		$a = explode("/", dirname($_SERVER['PHP_SELF']));
		$currentSubSubSection = $a[sizeof($a) - 2];
		$currentSubSubSubSection = 'res';
		break;
	case 'tutorials':
		$currentSection = 'he';
		$currentSubSection = 'h6';
		$a = explode("/", dirname($_SERVER['PHP_SELF']));
		$currentSubSubSection = $a[sizeof($a) - 2];
		$currentSubSubSubSection = 'tut';
		break;
	case 'references':
		$currentSection = 'he';
		$currentSubSection = 'h6';
		$a = explode("/", dirname($_SERVER['PHP_SELF']));
		$currentSubSubSection = $a[sizeof($a) - 2];
		$currentSubSubSubSection = 'ref';
		break;
}

// load all tools
$tools = getTools_List();
sort($tools);

// load all visualizers
$visualizers = getVisualizers_List();
sort($visualizers);

$datasets = [];

?>

<!-- BEGIN HEADER & CONTENT DIVIDER -->
<div class="clearfix"> </div>
<!-- END HEADER & CONTENT DIVIDER -->
<!-- BEGIN CONTAINER -->
<div class="page-container">
	<!--<div class="top-gradient"></div> -->
	<!-- BEGIN SIDEBAR -->
	<div class="page-sidebar-wrapper">
		<!-- BEGIN SIDEBAR -->
		<div class="page-sidebar navbar-collapse collapse">
			<!-- BEGIN SIDEBAR MENU -->
			<ul class="page-sidebar-menu  page-header-fixed " data-keep-expanded="false" data-auto-scroll="true" data-slide-speed="200" style="padding-top: 20px">
				<!-- BEGIN SIDEBAR TOGGLER BUTTON -->
				<li class="sidebar-toggler-wrapper hide">
					<div class="sidebar-toggler">
						<span></span>
					</div>
				</li>
				<!-- END SIDEBAR TOGGLER BUTTON -->
				<li class="nav-item  <?php if ($currentSection == 'hp') { ?>active open<?php } ?>">
					<a href="home/" class="nav-link nav-toggle">
						<i class="icon-home"></i>
						<span class="title">Homepage</span>
					</a>
				</li>
				<li class="nav-item  <?php if ($currentSection == 'uw') { ?>active open<?php } ?>">
					<a href="workspace/" class="nav-link nav-toggle">
						<i class="icon-screen-desktop"></i>
						<span class="title">User Workspace</span>
					</a>
				</li>
				<li class="nav-item  <?php if ($currentSection == 'dt') { ?>active open<?php } ?>">
					<a href="javascript:;" class="nav-link nav-toggle">
						<i class="icon-cloud-upload"></i>
						<span class="title">Get Data</span>
						<?php if ($currentSection == 'dt') { ?><span class="selected"></span><?php } ?>
						<span class="arrow <?php if ($currentSection == 'dt') { ?>open<?php } ?>"></span>
					</a>
					<ul class="sub-menu">
						<li class="nav-item <?php if ($currentSubSection == 'lc') { ?>active open<?php } ?>">
							<a href="getdata/uploadForm.php" class="nav-link ">
								<span class="title">Upload Files</span>
							</a>
						</li>
						<?php if ($datasets): ?>
							<li class="nav-item <?php if ($currentSubSection == 'rp') { ?>active open<?php } ?>">
								<a href="javascript:;" class="nav-link nav-toggle ">
									<span class="title">Data Archives</span>
									<span class="arrow"></span>
								</a>
							</li>
						<?php endif; ?>
					</ul>
				</li>

				<li class="nav-item  <?php if ($currentSection == 'lt') { ?>active open<?php } ?>">
					<a href="launch/" class="nav-link nav-toggle">
						<i class="icon-rocket"></i>
						<span class="title">Run Tool / Visualizer</span>
					</a>
				</li>
				<li class="nav-item  <?php if ($currentSection == 'he') { ?>active open<?php } ?>">
					<a href="javascript:;" class="nav-link nav-toggle">
						<i class="icon-question"></i>
						<span class="title">Help</span>
						<?php if ($currentSection == 'he') { ?><span class="selected"></span><?php } ?>
						<span class="arrow <?php if ($currentSection == 'he') { ?>open<?php } ?>"></span>
					</a>
					<ul class="sub-menu">
						<li class="nav-item  <?php if ($currentSubSection == 'h1') { ?>active open<?php } ?>">
							<a href="help/general.php" class="nav-link ">
								<span class="title">General information</span>
							</a>
						</li>
						<!-- <li class="nav-item  <?php //if ($currentSubSection == 'h2') { 
													?>active open<?php //} 
																	?>">
							<a href="help/starting.php" class="nav-link ">
								<span class="title">Getting Started</span>
							</a>
						</li> -->
						<li class="nav-item  <?php if ($currentSubSection == 'h3') { ?>active open<?php } ?>">
							<a href="help/upload.php" class="nav-link ">
								<span class="title">Get Data</span>
							</a>
						</li>
						<!-- <li class="nav-item  <?php //if ($currentSubSection == 'h4') { 
													?>active open<?php //} 
																	?>">
							<a href="help/ws.php" class="nav-link ">
								<span class="title">Workspace</span>
							</a>
						</li> -->
						<li class="nav-item  <?php if ($currentSubSection == 'h5') { ?>active open<?php } ?>">
							<a href="help/launch.php" class="nav-link ">
								<span class="title">Launch Job</span>
							</a>
						</li>
						<li class="nav-item  <?php if ($currentSubSection == 'h6') { ?>active open<?php } ?>">
							<a href="help/tools.php" class="nav-link">
								<span class="title">Tools</span>
								<span class="arrow <?php if ($currentSubSection == 'h6') { ?>open<?php } ?>"></span>
							</a>

							<ul class="sub-menu">
								<?php foreach ($tools as $t) {
									$s = $GLOBALS['helpsCol']->find(array('tool' => $t["_id"]), array('_id' => 1));
									$sections = iterator_to_array($s);
									$sections2 = array_column($sections, 'help');
									$arrSect = array();
									foreach ($sections as $sec) {
										$arrSect[] = $sec['help'];
									} ?>
									<li class="nav-item <?php if ($currentSubSubSection == $t["_id"]) { ?>active open<?php } ?>">
										<a href="help/toolhelp.php?tool=<?php echo $t["_id"]; ?>&sec=help" class="nav-link">
											<span class="title"> <?php echo $t["name"]; ?> </span>
											<span class="arrow <?php if ($currentSubSubSection == $t["_id"]) { ?>open<?php } ?>"></span>
										</a>

										<ul class="sub-menu">
											<?php foreach ($sections as $sec) {
												if ($sec['help'] == "help") {
													continue;
												}
											?>
												<li class="nav-item <?php if ($currentSubSubSubSection == substr($sec['help'], 0, 3)) { ?>active open<?php } ?>">
													<a href="help/toolhelp.php?tool=<?php echo $t["_id"]; ?>&sec=<?php echo $sec['help']; ?>" class="nav-link">
														<span class="title"><?php echo $sec['help']; ?></span>
													</a>
												</li>
											<?php } ?>
										</ul>
									</li>
								<?php } ?>
							</ul>

						</li>
					</ul>
				</li>
				<?php if (in_array($_SESSION['User']['Type'], $GLOBALS['NO_GUEST'])) { ?>
					<li>
					<li class="nav-item <?php if ($currentSection == 'hd') { ?>active open<?php } ?>">
						<a href="helpdesk/" class="nav-link nav-toggle">
							<i class="icon-earphones"></i>
							<span class="title">Helpdesk</span>
						</a>
					</li>
				<?php } ?>
				<?php if (in_array($_SESSION['User']['Type'], $GLOBALS['ADMIN']) || in_array($_SESSION['User']['Type'], $GLOBALS['TOOLDEV'])) { ?>
					<li class="nav-item  <?php if ($currentSection == 'ad') { ?>active open<?php } ?>">
						<a href="javascript:;" class="nav-link nav-toggle">
							<i class="icon-settings"></i>
							<span class="title">Admin</span>
							<?php if ($currentSection == 'up') { ?><span class="selected"></span><?php } ?>
							<span class="arrow <?php if ($currentSection == 'ad') { ?>open<?php } ?>"></span>
						</a>
						<ul class="sub-menu">
							<?php if (!in_array($_SESSION['User']['Type'], $GLOBALS['TOOLDEV'])) { ?>
								<li class="nav-item  <?php if ($currentSubSection == 'ds') { ?>active open<?php } ?>">
									<a href="admin/dashboard.php" class="nav-link ">
										<span class="title">Dashboard</span>
									</a>
								</li>
							<?php } ?>
							<?php if (!in_array($_SESSION['User']['Type'], $GLOBALS['TOOLDEV'])) { ?>
								<li class="nav-item  <?php if ($currentSubSection == 'au') { ?>active open<?php } ?>">
									<a href="admin/adminUsers.php" class="nav-link ">
										<span class="title">Users Administration</span>
									</a>
								</li>
								<li class="nav-item  <?php if ($currentSubSection == 'aj') { ?>active open<?php } ?>">
									<a href="admin/adminJobs.php" class="nav-link ">
										<span class="title">Job Administration</span>
									</a>
								</li>
							<?php } ?>
							<!--<li class="nav-item  <?php if ($currentSubSection == 'at') { ?>active open<?php } ?>">
					</li>
					<li class="nav-item  <?php if ($currentSubSection == 'jv') { ?>active open<?php } ?>">
                                        	<a href="admin/jsonValidator.php" class="nav-link ">
                                            	<span class="title">JSON Validator</span>
                                        	</a>
					</li>-->
							<li class="nav-item  <?php if ($currentSubSection == 'at') { ?>active open<?php } ?>">
								<a href="javascript:;" class="nav-link nav-toggle">
									<span class="title">My tools</span>
									<span class="arrow <?php if ($currentSubSection == 'at') { ?>open<?php } ?>"></span>
								</a>
							</li>
						</ul>
					</li>
				<?php } ?>

				<li class="nav-item active open beta-long" style="color:#b4bcc8;margin-left:18px;margin-top:10px;font-size:12px;">This is the <?php echo $GLOBALS['AppVersion']; ?> version of <?php echo $GLOBALS['AppPrefix']; ?> VRE</li>
				<li class="nav-item active open beta-short" style="color:#b4bcc8;margin-left:8px;margin-top:10px;font-size:12px;display:none;"><?php echo $GLOBALS['AppVersion']; ?></li>

			</ul>
			<!-- END SIDEBAR MENU -->
			<!-- END SIDEBAR MENU -->

		</div>
		<!-- END SIDEBAR -->
	</div>
	<!-- END SIDEBAR -->