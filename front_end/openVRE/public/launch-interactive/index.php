<?php

require __DIR__ . "/../../config/bootstrap.php";
redirectOutside();

require "../htmlib/header.inc.php";

$interactiveToolprefix = "/interactive-tool/";
$autorefresh = shouldAutorefresh($_REQUEST['pid']);

?>

<body class="page-header-fixed page-sidebar-closed-hide-logo page-content-white page-container-bg-solid page-sidebar-fixed">
	<div class="page-wrapper">

		<?php require "../htmlib/top.inc.php"; ?>
		<?php require "../htmlib/menu.inc.php"; ?>

		<div class="page-content-wrapper">
			<div class="page-content">
				<div class="page-bar">
					<ul class="page-breadcrumb">
						<li>
							<a href="home/">Home</a>
							<i class="fa fa-circle"></i>
						</li>
						<li>
							<a href="workspace/">Interactive Tool</a>
							<i class="fa fa-circle"></i>
						</li>
					</ul>
				</div>
				<div class="row">
					<div class="col-md-12">
						<?php
						if (isset($_SESSION['errorData'])) { ?>
							<div class="alert alert-warning">
								<?php foreach ($_SESSION['errorData'] as $subTitle => $txts) {
									print "$subTitle<br/>";
									foreach ($txts as $txt) {
										print "<div style=\"margin-left:20px;\">$txt</div>";
									}
								}
								unset($_SESSION['errorData']);
								?>
							</div>
						<?php }
						?>

						<form action="#" class="horizontal-form" id="tool-input-form">
							<input type="hidden" id="base-url" value="<?php echo $GLOBALS['BASEURL']; ?>" />
							<?php if ($autorefresh) {
								print "<input type=\"hidden\" id=\"autorefresh\" value=\"$autorefresh\"/>\n";
							} ?>

						</form>

						<?php
						$tool_log    = $_SESSION['User']['lastjobs'][$_REQUEST['pid']]['log_file'];
						$stdout_file = $_SESSION['User']['lastjobs'][$_REQUEST['pid']]['stdout_file'];
						$stderr_file = $_SESSION['User']['lastjobs'][$_REQUEST['pid']]['stderr_file'];
						$tool_port   = $_SESSION['User']['lastjobs'][$_REQUEST['pid']]['interactive_tool']['port'];
						$toolContainerName = $_SESSION['User']['lastjobs'][$_REQUEST['pid']]['containerName'];
						$toolUrl = $GLOBALS['SERVER'] . $interactiveToolprefix . $toolContainerName . "/";
						$status = ($autorefresh ? "disabled" : "");

						?>
						<br />

						<a target=_blank <?php echo $status; ?> href="<?php echo $toolUrl; ?>" class="btn green"> Interactive Session </a>
						<br />
						<br />
						<a href="workspace/workspace.php?op=openPlainFileFromPath&fnPath=<?php echo urlencode($stdout_file); ?>" class="btn" target="_blank"><i class="fa fa-file-text-o"></i> Job Standard Output</a>

						<pre><?php echo file_get_contents($stdout_file); ?></pre>

						<br />
						<br />
						<a href="workspace/workspace.php?op=openPlainFileFromPath&fnPath=<?php echo urlencode($tool_log); ?>" class="btn" target="_blank"><i class="fa fa-file-text-o"></i> Session Console Log </a>

						<pre id="tool_log" style="max-height:300px;"><?php echo file_get_contents($tool_log); ?></pre>
						<br />
						<br />
						<a href="workspace/workspace.php?op=openPlainFileFromPath&fnPath=<?php echo urlencode($stderr_file); ?>" class="btn" target="_blank"><i class="fa fa-file-text-o"></i> Job Standard Error</a>
						<pre><?php echo file_get_contents($stderr_file); ?></pre>

						<br />
						<hr />
						<?php die(); ?>
						<pre>-------</pre>
						<hr />

						<iframe id="if0" src="" frameborder="1px red" style="width:100%;"></iframe>

						<hr />
						<pre>-------</pre>
						<hr />


						<iframe id="if1" src="" frameborder="1px red" style="width:100%;"></iframe>
						<hr />
					</div>
				</div>
			</div>
		</div>

		<div class="modal fade bs-modal-lg" id="modalDTStep2" tabindex="-1" role="basic" aria-hidden="true">
			<div class="modal-dialog modal-lg">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
						<h4 class="modal-title">Select file(s)</h4>
					</div>
					<div class="modal-body">
						<div id="loading-datatable">
							<div id="loading-spinner">LOADING</div>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn dark btn-outline" data-dismiss="modal">Close</button>
						<button type="button" class="btn green btn-modal-dts2-ok" disabled>Accept</button>
					</div>
				</div>
			</div>
		</div>

		<?php

		require "../htmlib/footer.inc.php";
		require "../htmlib/js.inc.php";

		?>