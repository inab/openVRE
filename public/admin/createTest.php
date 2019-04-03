<?php

require __DIR__."/../../config/bootstrap.php";

redirectToolDevOutside();

if(!isset($_REQUEST['id'])) {

	$_SESSION['errorData']['Error'][] = "Please provide a tool id.";
	redirect($GLOBALS['BASEURL'].'admin/myNewTools.php');
	
}

$toolDevJSON = $GLOBALS['toolsDevMetaCol']->findOne(array('_id' => $_REQUEST['id']));

//vaR_dump($toolDevJSON); die();

if(!isset($toolDevJSON)) {
	$_SESSION['errorData']['Error'][] = "The tool id <strong>".$_REQUEST['toolid']."</strong> doesn't exist in our database.";
    redirect($GLOBALS['BASEURL'].'admin/myNewTools.php');
} 

if($_SESSION['User']['Type'] != 0)
	$toolDevMetaJSON = $GLOBALS['toolsDevMetaCol']->findOne(array('_id' => $_REQUEST['id'], 'user_id' => $_SESSION['User']['id']));
else 
	$toolDevMetaJSON = $GLOBALS['toolsDevMetaCol']->findOne(array('_id' => $_REQUEST['id']));

if(!isset($toolDevMetaJSON) && ($_SESSION['User']['Type'] != 0)) {
		$_SESSION['errorData']['Error'][] = "The tool id <strong>".$_REQUEST['toolid']."</strong> you are trying to edit doesn't belong to you.";
			redirect($GLOBALS['BASEURL'].'admin/myNewTools.php');
}

if(!$toolDevMetaJSON["step1"]["tool_io_validated"]) {
	$_SESSION['errorData']['Error'][] = "Please first insert a valid JSON.";
	redirect($GLOBALS['BASEURL'].'admin/myNewTools.php');
}

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
                                  <span>Generate test files</span>
                              </li>
                            </ul>
                        </div>
                        <!-- END PAGE BAR -->
                        <!-- BEGIN PAGE TITLE-->
                        <h1 class="page-title"> Generate Test Files
                        </h1>
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

		<form name="create-test" id="create-test" action="applib/updateToolDevTest.php" method="post" >

			<div class="portlet box blue-oleo">
					<div class="portlet-title">
							<div class="caption">
								<div style="float:left;margin-right:20px;"> Test Files Configuration</div>
							</div>
					</div>
					<div class="portlet-body form">
						
						<div class="form-body">

						<p>You are to develop your tool code offline, in a local environmet (i.e VM). And to best way to check that your tool is going to be properly called by VRE server when integrated into the cloud, is precisely emulating a VRE execution to you tool code. Fill in this form with the test input files that you have in your development environent, and use the resulting JSON files to create your test script</p>


<!---------------  SETTING LOCAL DIRECTORIES -->
                                             <h4 class="form-section">Local test environment</h4>

                                             <div class="row">
                                                <div class="col-md-6">
                                                  <div class="form-group">
                                                    <label class="control-label">Test working directory <i class="icon-question popovers" data-container="body" data-content="Correspond to the local working directory of your tool. In there is where output files and logs are expected." data-html="true" data-original-title="Foo execution folder" data-trigger="hover"></i>
			</label>
			<?php 
			$val = $toolDevMetaJSON["step1"]["form_data"]["execution"];
			if(!isset($val)) $val = "/test/execution/directory";
			?>
			<input type="text" name="execution" placeholder="/local/development/env/path/tests/run000" class="form-control input-file-path " value="<?php echo $val; ?>"> 
                                                   </div>
	 </div>

	<div class="col-md-6">
                                                  <div class="form-group">
                                                    <label class="control-label">Tool wrapper <i class="icon-question popovers" data-container="body" data-content="TODO" data-html="true" data-original-title="Foo execution folder" data-trigger="hover"></i>
			</label>
			<?php 
			$val = $toolDevMetaJSON["step1"]["form_data"]["tool_executable"]; 
			if(!isset($val)) $val = "/main/mg-tool/executable.py";
			?> 
                                                    <input type="text" name="tool_executable" placeholder="/local/development/env/path/tests/run000" class="form-control" value="<?php echo $val; ?>"> 
                                                   </div>
                                                 </div>
												 </div>

<div class="row">
                                                <div class="col-md-6">
                                                  <div class="form-group">
                                                    <label class="control-label">Workflow type <i class="icon-question popovers" data-container="body" data-content="What is it?." data-html="true" data-original-title="Foo project Id" data-trigger="hover"></i>
			</label>
			<?php $val = $toolDevMetaJSON["step1"]["form_data"]["workflowtype"]; ?>
			<select name="workflowtype" id="workflowtype" class="form-control" >
				<option value="single" <?php echo ($val == "single" ? 'selected' : '') ?>>Single</option>
				<option value="compss" <?php echo ($val == "compss" ? 'selected' : '') ?>>COMPSs</option>
			</select>
                                                  </div>
                                                </div>

	<div class="col-md-6">
                                                  <div class="form-group <?php echo ($val != "compss" ? 'display-hide' : '') ?> " id="tool_lib_block">
                                                    <label class="control-label">Tool python libraries <i class="icon-question popovers" data-container="body" data-content="TODO" data-html="true" data-original-title="Foo execution folder" data-trigger="hover"></i>
			</label> 
			<?php 
			$vte = $toolDevMetaJSON["step1"]["form_data"]["tool_lib"]; 
			if(!isset($vte) || $vte === 0) $vte = "/packages/to/add/into/pythonpath";
			?>
                                                    <input type="text" name="tool_lib" id="tool_lib" placeholder="/local/development/env/path/tests/run000" class="form-control input-file-path "  <?php echo ($val != "compss" ? 'disabled' : '') ?>  value="<?php echo $vte; ?>"> 
                                                   </div>
                                                 </div>				
                                                
                                             </div>

<!---------------  SETTING INPUT FILES -->
								<h4 class="form-section">Local data test</h4>

									<?php foreach($toolDevJSON["step1"]["tool_io"]["input_files"] as $inputf) { ?>

									<div class="row margin-top-20">
                                            <div class="col-md-12">
                                                <div class="form-group">
		<label class="control-label">
			<?php echo $inputf["description"] ?>
			<i class="icon-question popovers" data-container="body" data-content="<?php echo getInputMetadata($inputf); ?>" data-html="true" data-original-title="Metadata for <?php echo $inputf["description"] ?>" data-trigger="hover"></i>
		</label>
		<?php $val = $toolDevMetaJSON["step1"]["form_data"]["input_files"]; ?>
		<input type="text" name="input_files[<?php echo $inputf["name"] ?>]" value="<?php echo $val[$inputf["name"]]; ?>" class="form-control input-file-path input-files" placeholder="">
                                                </div>
                                            </div>

                                                                            <?php
                                                                                if ($inputf['data_type']){

					$features =getFeaturesFromDataType($inputf['data_type'][0],$inputf['file_type'][0]);
						$c = 0;
                                                                                    foreach ($features as $feature => $required){
                                                                                       if ($required === FALSE) {continue;}
							 if ($feature === "_id") {continue;}
							 if($c%2 == 0) $ml = 15;
							else $ml = -30;
                                                                                        ?>

									<div class="col-md-6" style="background:#e9e9e9;margin-left:<?php echo $ml; ?>px;margin-top: -15px;padding-top: 15px;">
                                                                                            <div class="form-group">
										 <label class="control-label">
												<?php echo $feature; ?>
												<a href="http://www.multiscalegenomics.eu/MuGVRE/integration-of-tools/test-job-configuration-files/" target="_blank"><i class="icon-question tooltips" data-container="body" data-html="true" data-original-title="Click here to open the Input metadata file help section" data-trigger="hover" data-placement="right"></i></a>
										</label>
                                                                                                    <?php $val = $toolDevMetaJSON["step1"]["form_data"]["metadata"][$inputf["name"]]; ?>
                                                                                                    <input type="text" name="metadata[<?php echo $inputf["name"]; ?>][<?php echo $feature; ?>]" value="<?php echo $val[$feature]; ?>" class="form-control input-files" style="width:95%;">
                                                </div>
                                                    </div>

                                                                                       <?php
							$c ++;
						}
						if($c%2 != 0) {
							echo '<div class="col-md-6" style="background:#e9e9e9;margin-left:-30px;margin-top: -15px;padding-top: 15px;">
											<div class="form-group">
												<label class="control-label">&nbsp;</label>
                                                                                                    <input type="text" class="form-control" style="width:95%;opacity:0" disabled>
											</div>
										</div>';
						}
                                                                                     
				}
			echo "</div>";
                                                                                                        
                                                                                } ?>

<!---------------  SETTING INPUT FILES FROM PUBLIC DIR -->
                                                                         <?php if (count($toolDevJSON["step1"]["tool_io"]["input_files_public_dir"])){ ?>

                                                                        <h4 class="form-section">Local data test - <span style="font-size:14px">hidden input files</span></h4>

									<?php foreach($toolDevJSON["step1"]["tool_io"]["input_files_public_dir"] as $inputf) { ?>

									<div class="row margin-top-20">
                                            <div class="col-md-12">
                                                <div class="form-group">
		<label class="control-label">
			<?php echo $inputf["description"] ?>
			<i class="icon-question popovers" data-container="body" data-content="<?php echo getInputMetadata($inputf); ?>" data-html="true" data-original-title="Metadata for <?php echo $inputf["description"] ?>" data-trigger="hover"></i>
		</label>
		<?php $val = $toolDevMetaJSON["step1"]["form_data"]["input_files_public_dir"]; ?>
		<!--<input type="text" name="input_files_public_dir[<?php echo $inputf["name"] ?>]" value="<?php echo $val[$inputf["name"]]; ?>" class="form-control input-file-path input-files" placeholder="">-->
		
		<?php echo getArgument($inputf, null, "", "input_files_public_dir"); ?>
                                                </div>
                                            </div>

                                                                            <?php
                                                                                    if ($inputf['data_type']){


											$features =getFeaturesFromDataType($inputf['data_type'][0],$inputf['file_type'][0]);
	    									$c = 0;
                                                                                    foreach ($features as $feature => $required){
                                                                                       if ($required === FALSE) {continue;}
							 if ($feature === "_id") {continue;}
							 if($c%2 == 0) $ml = 15;
							else $ml = -30;
                                                                                        ?>

									<div class="col-md-6" style="background:#e9e9e9;margin-left:<?php echo $ml; ?>px;margin-top: -15px;padding-top: 15px;">
                                                                                            <div class="form-group">
										 <label class="control-label">
												<?php echo $feature; ?>
												<a href="http://www.multiscalegenomics.eu/MuGVRE/integration-of-tools/test-job-configuration-files/" target="_blank"><i class="icon-question tooltips" data-container="body" data-html="true" data-original-title="Click here to open the Input metadata file help section" data-trigger="hover" data-placement="right"></i></a>
										</label>
                                                                                                    <?php $val = $toolDevMetaJSON["step1"]["form_data"]["metadata"][$inputf["name"]]; ?>
	<input type="text" name="metadata[<?php echo $inputf["name"]; ?>][<?php echo $feature; ?>]" value="<?php echo $val[$feature]; ?>" class="form-control input-files" style="width:95%;">
                                                </div>
                                                    </div>

                                                                                       <?php
							$c ++;
						}
						if($c%2 != 0) {
							echo '<div class="col-md-6" style="background:#e9e9e9;margin-left:-30px;margin-top: -15px;padding-top: 15px;">
											<div class="form-group">
												<label class="control-label">&nbsp;</label>
                                                                                                    <input type="text" class="form-control" style="width:95%;opacity:0" disabled>
											</div>
										</div>';
						}
                                                                                     
				}
			echo "</div>";
                                                                                } } ?>


<!---------------  SETTING ARGUMENTS -->

                                                                            <h4 class="form-section">Test arguments</h4>

									<?php 
									$c = 0;
									foreach($toolDevJSON["step1"]["tool_io"]["arguments"] as $arg) { ?>

										<?php if($c%2 == 0) { ?><div class="row"><?php } ?>
                                            <div class="col-md-6">
                                                <div class="form-group">
		<label class="control-label">
			<?php echo $arg["description"] ?>
			<i class="icon-question popovers" data-container="body" data-content="<?php echo getInputMetadata($arg); ?>" data-html="true" data-original-title="Metadata for <?php echo $arg["description"] ?>" data-trigger="hover"></i>
		</label>
		<?php echo getArgument($arg, null, $toolDevMetaJSON["step1"]["form_data"]["arguments"][$arg["name"]]); ?>	
                                                </div>
                                            </div>
										<?php if($c%2 != 0) { ?></div><?php } ?>

									<?php 
									$c ++;
									} 
									if($c%2 != 0) echo '</div>';
									?>

									<!-- foreach d'arguments i input_files amb (?) mostrant les metadades de cadascun amb un popover.
									Separar en dos blocs diferenciats. Als arguments, si hi ha default el poso, sinó placeholder. 
									Als input_files per ara posar strings (mirar si regex path?) -->
								
						</div>
						<div class="form-actions">
								<input type="hidden" id="base-url" value="<?php echo $GLOBALS['BASEURL']; ?>" />
								<input type="hidden" name="toolid" value="<?php echo $_REQUEST['id']; ?>" />
								<button type="submit" class="btn blue"><i class="fa fa-check"></i> Submit</button>
								<button type="reset" class="btn default">Reset</button>
						</div>
					</div>
			</div>

		</form>

                    <!-- END CONTENT BODY -->
                </div>
                <!-- END CONTENT -->

								<!--<div class="modal fade bs-modal" id="modalJSONSchema" tabindex="-1" role="basic" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                                <h4 class="modal-title">JSON Schema Validation</h4>
                            </div>
				<div class="modal-body"></div>
                            <div class="modal-footer">
                                <button type="button" class="btn dark btn-outline" data-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>-->

<?php 

require "../htmlib/footer.inc.php"; 
require "../htmlib/js.inc.php";

?>
