<!DOCTYPE html>
<!--[if IE 8]> <html lang="en" class="ie8 no-js"> <![endif]-->
<!--[if IE 9]> <html lang="en" class="ie9 no-js"> <![endif]-->
<!--[if !IE]><!-->
<html lang="en">
<!--<![endif]-->
<!-- BEGIN HEAD -->

<head>
	<meta charset="utf-8" />
	<title><?php echo $GLOBALS['SITETITLE']; ?></title>
	<base href="<?php echo $GLOBALS['BASEURL']; ?>" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta content="width=device-width, initial-scale=1" name="viewport" />
	<meta content="" name="description" />
	<meta content="" name="author" />
	<!-- BEGIN GLOBAL MANDATORY STYLES -->
	<link href="https://fonts.googleapis.com/css?family=Open+Sans:400,300,600,700&subset=all" rel="stylesheet" type="text/css" />
	<link href="assets/global/plugins/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css" />
	<link href="assets/global/plugins/simple-line-icons/simple-line-icons.min.css" rel="stylesheet" type="text/css" />
	<link href="assets/global/plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
	<link href="assets/global/plugins/bootstrap-switch/css/bootstrap-switch.min.css" rel="stylesheet" type="text/css" />
	<!-- END GLOBAL MANDATORY STYLES -->
	<!-- BEGIN PAGE LEVEL PLUGINS -->
	<?php
	switch (pathinfo($_SERVER['PHP_SELF'])['filename']) {
		case 'index2': ?>
			<?php if (basename(dirname($_SERVER['PHP_SELF'])) == 'workspace') { ?>
				<link href="assets/pages/css/jquery.dataTables.min.css" rel="stylesheet" type="text/css" />
				<link href="assets/pages/css/treeTable.dataTables.css" rel="stylesheet" type="text/css" />
				<link href="assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.css" rel="stylesheet" type="text/css" />
				<link href="assets/global/plugins/bootstrap-toastr/toastr.min.css" rel="stylesheet" type="text/css" />
			<?php } elseif (basename(dirname($_SERVER['PHP_SELF'])) == 'home') { ?>
				<link href="assets/global/plugins/cubeportfolio/css/cubeportfolio.css" rel="stylesheet" type="text/css" />
			<?php } else { ?>
				<link href="assets/global/plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
				<link href="assets/global/plugins/select2/css/select2-bootstrap.min.css" rel="stylesheet" type="text/css" />
				<link href="assets/pages/css/jquery.dataTables.min.css" rel="stylesheet" type="text/css" />
			<?php } break;
		case 'index': ?>
			<?php if (basename(dirname($_SERVER['PHP_SELF'])) == 'workspace') { ?>
				<link href="assets/pages/css/jquery.dataTables.min.css" rel="stylesheet" type="text/css" />
				<link href="assets/pages/css/treeTable.dataTables.css" rel="stylesheet" type="text/css" />
				<link href="assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.css" rel="stylesheet" type="text/css" />
				<link href="assets/global/plugins/bootstrap-toastr/toastr.min.css" rel="stylesheet" type="text/css" />
				<link href="assets/global/plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
				<link href="assets/global/plugins/select2/css/select2-bootstrap.min.css" rel="stylesheet" type="text/css" />
				<link href="assets/global/plugins/progress-tracker/progress-tracker.css" rel="stylesheet" type="text/css" />
			<?php } elseif (basename(dirname($_SERVER['PHP_SELF'])) == 'launch') { ?>
				<link href="assets/pages/css/jquery.dataTables.min.css" rel="stylesheet" type="text/css" />
				<link href="assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.css" rel="stylesheet" type="text/css" />
				<link href="assets/global/plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
				<link href="assets/global/plugins/select2/css/select2-bootstrap.min.css" rel="stylesheet" type="text/css" />
			<?php } elseif (( basename(dirname($_SERVER['PHP_SELF'])) == 'home') || ( basename(dirname($_SERVER['PHP_SELF'])) == 'publicsite')) { ?>
				<link href="assets/global/plugins/cubeportfolio/css/cubeportfolio.css" rel="stylesheet" type="text/css" />
			<?php } else { ?>
				<link href="assets/global/plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
				<link href="assets/global/plugins/select2/css/select2-bootstrap.min.css" rel="stylesheet" type="text/css" />
			<?php } ?>
			<?php break;
		case 'usrProfile': ?>
			<link href="assets/global/plugins/bootstrap-fileinput/bootstrap-fileinput.css" rel="stylesheet" type="text/css" />
			<?php break;
		case 'dataFromID': ?>
			<link href="assets/global/plugins/typeahead/typeahead.css" rel="stylesheet" type="text/css" />
			<?php break;
		case 'listOfProjects': ?>
			<link href="assets/pages/css/jquery.dataTables.min.css" rel="stylesheet" type="text/css" />
			<link href="assets/pages/css/treeTable.dataTables.css" rel="stylesheet" type="text/css" />
			<link href="assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.css" rel="stylesheet" type="text/css" />
			<link href="assets/global/plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
			<link href="assets/global/plugins/select2/css/select2-bootstrap.min.css" rel="stylesheet" type="text/css" />
			<?php break;
		case 'uploadForm': ?>
			<link href="assets/global/plugins/dropzone/dropzone.min.css" rel="stylesheet" type="text/css" />
			<link href="assets/global/plugins/dropzone/basic.min.css" rel="stylesheet" type="text/css" />
			<?php break;
		case 'uploadForm2':
		case 'editFile':
		case 'editFile2': ?>
			<link href="assets/global/plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
			<link href="assets/global/plugins/select2/css/select2-bootstrap.min.css" rel="stylesheet" type="text/css" />
			<link href="assets/global/plugins/typeahead/typeahead.css" rel="stylesheet" type="text/css" />
			<?php break;
		case 'adminUsers':
		case 'adminTools':
		case 'adminJobs':
		case 'myNewTools':
		case 'dashboard':
		case 'datasets':
		case 'logs': ?>
			<link href="assets/global/plugins/bootstrap-fileinput/bootstrap-fileinput.css" rel="stylesheet" type="text/css" />
			<link href="assets/global/plugins/datatables/datatables.min.css" rel="stylesheet" type="text/css" />
			<link href="assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.css" rel="stylesheet" type="text/css" />
			<?php break;
		case 'help':
		case 'toolhelp':
		case 'method':
		case 'inputs':
		case 'outputs':
		case 'results':
		case 'tutorials':
		case 'references': ?>
			<link href="assets/global/plugins/markdown/bootstrap-markdown-editor.css" rel="stylesheet" type="text/css" />
			<?php break;
		case 'output': ?>
			<?php if (preg_match('tools/pydockdna',dirname($_SERVER['PHP_SELF'])) )  { ?>
				<link href="assets/global/plugins/datatables/datatables.min.css" rel="stylesheet" type="text/css" />
				<link href="assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.css" rel="stylesheet" type="text/css" />
			<?php } elseif (preg_match('tools/naflex',dirname($_SERVER['PHP_SELF'])) )  { ?>
				<link href="tools/naflex/css/styles.css" rel="stylesheet" type="text/css" />
			<?php } elseif (preg_match('tools/nucldynwf',dirname($_SERVER['PHP_SELF'])) ) { ?>
				<link href="assets/pages/css/jquery.dataTables.min.css" rel="stylesheet" type="text/css" />
				<link href="assets/global/plugins/datatables/plugins/fixedColumns/fixedColumns.dataTables.min.css" rel="stylesheet" type="text/css" />
				<link href="assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.css" rel="stylesheet" type="text/css" />
			<?php } elseif (preg_match('tools/nucldynwf_pmes',dirname($_SERVER['PHP_SELF'])) ) { ?>
				<link href="assets/pages/css/jquery.dataTables.min.css" rel="stylesheet" type="text/css" />
				<link href="assets/global/plugins/datatables/plugins/fixedColumns/fixedColumns.dataTables.min.css" rel="stylesheet" type="text/css" />
				<link href="assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.css" rel="stylesheet" type="text/css" />
			<?php } ?>
			<?php break;
		case 'input': ?>
			<link href="assets/global/plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
			<link href="assets/global/plugins/select2/css/select2-bootstrap.min.css" rel="stylesheet" type="text/css" />
			<link href="assets/global/plugins/datatables/datatables.min.css" rel="stylesheet" type="text/css" />
			<link href="assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.css" rel="stylesheet" type="text/css" />
			<link href="assets/global/plugins/typeahead/typeahead.css" rel="stylesheet" type="text/css" />
			<?php break;
		case 'jsonTestValidator':
		case 'jsonSpecValidator': ?>
			<link href="assets/global/plugins/codemirror/lib/codemirror.css" rel="stylesheet" type="text/css" />
			<link href="assets/global/plugins/codemirror/addon/lint/lint.css" rel="stylesheet" type="text/css" />
			<?php break;
		case 'editUser': ?>
			<link href="assets/global/plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
			<link href="assets/global/plugins/select2/css/select2-bootstrap.min.css" rel="stylesheet" type="text/css" />
			<?php break;
		case 'createTest': ?>
			<link href="assets/global/plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
			<link href="assets/global/plugins/select2/css/select2-bootstrap.min.css" rel="stylesheet" type="text/css" />
			<?php break;
		case 'tools':
		case 'visualizers': ?>
			<link href="assets/global/plugins/cubeportfolio/css/cubeportfolio.css" rel="stylesheet" type="text/css" />
			<?php break ?>;

		<?php } ?>
		<!-- END PAGE LEVEL PLUGINS -->
		<!-- BEGIN THEME GLOBAL STYLES -->
		<link href="assets/global/css/components.min.css" rel="stylesheet" id="style_components" type="text/css" />
		<link href="assets/global/css/plugins.min.css" rel="stylesheet" type="text/css" />
		<!-- END THEME GLOBAL STYLES -->
		<!-- BEGIN PAGE LEVEL STYLES -->
		<?php
		switch (pathinfo($_SERVER['PHP_SELF'])['filename']) {
			case 'index2': ?>
			<?php if (basename(dirname($_SERVER['PHP_SELF'])) == 'workspace') { ?>
			<?php } elseif (basename(dirname($_SERVER['PHP_SELF'])) == 'home') { ?>
				<link href="assets/pages/css/portfolio.min.css" rel="stylesheet" type="text/css" />
			<?php } else { ?>
				<link href="assets/pages/css/login.min.css" rel="stylesheet" type="text/css" />
			<?php } ?>
			<?php break;
		case 'resetPassword':
		case 'index': ?>
			<?php if (basename(dirname($_SERVER['PHP_SELF'])) == 'workspace') { ?>
			<?php } elseif ((basename(dirname($_SERVER['PHP_SELF'])) == 'home') || (basename(dirname($_SERVER['PHP_SELF'])) == 'publicsite')) { ?>
				<link href="assets/pages/css/portfolio.min.css" rel="stylesheet" type="text/css" />
			<?php } else { ?>
				<link href="assets/pages/css/login.min.css" rel="stylesheet" type="text/css" />
			<?php } ?>
			<?php break;
		case 'lockScreen': ?>
			<link href="assets/pages/css/lock.min.css" rel="stylesheet" type="text/css" />
			<?php break;
		case 'usrProfile': ?>
			<link href="assets/pages/css/profile.min.css" rel="stylesheet" type="text/css" />
			<?php break;
		case 'input':
		case 'output': ?>
			<link href="assets/pages/css/customized-tools.css" rel="stylesheet" type="text/css" />
			<?php break;
		case 'tools':
		case 'visualizers': ?>
			<link href="assets/pages/css/portfolio.min.css" rel="stylesheet" type="text/css" />
			<?php break; ?>
		<?php } ?>
	<!-- END PAGE LEVEL STYLES -->
	<!-- BEGIN THEME LAYOUT STYLES -->
	<?php
	switch (pathinfo($_SERVER['PHP_SELF'])['filename']) {
		case 'index2':
		case 'index':
		case 'home':
		case 'usrProfile':
		case 'uploadForm':
		case 'uploadForm2':
		case 'editFile':
		case 'editFile2':
		case 'adminUsers':
		case 'newUser':
		case 'newProject':
		case 'editProject':
		case 'listOfProjects':
		case 'editUser':
		case 'adminTools':
		case 'adminJobs':
		case 'myNewTools':
		case 'newTool':
		case 'vmURL':
		case 'createTest':
		case 'jsonSpecValidator':
		case 'jsonTestValidator':
		case 'dashboard':
		case 'datasets':
		case 'sampleDataList':
		case 'dataFromTxt':
		case 'dataFromID':
		case 'input':
		case 'output':
		case 'loading_output':

		case 'general':
		case 'starting':
		case 'upload':
		case 'ws':
		case 'launch':
		case 'hdesk':
		case 'related':
		case 'refs':
		case 'ackn':
		case 'help':
		case 'toolhelp':
		case 'method':
		case 'inputs':
		case 'outputs':
		case 'results':
		case 'tutorials':
		case 'references':
		case 'tools':
		case 'visualizers':
		case 'restoreLink':
		case 'form':
		case 'logs':

			?>
		<link href="assets/layouts/layout/css/layout.css" rel="stylesheet" type="text/css" />
		<link href="assets/layouts/layout/css/themes/darkblue.min.css" rel="stylesheet" type="text/css" id="style_color" />
		<?php break; ?>
	<?php } ?>
	<link href="assets/layouts/layout/css/custom.min.css?v=<?php echo rand(); ?>" rel="stylesheet" type="text/css" />
	<!-- END THEME LAYOUT STYLES -->
	<link rel="icon" href="assets/layouts/layout/img/icon.png" sizes="32x32" />

</head>
<!-- END HEAD -->
