<?php

require __DIR__."/../../config/bootstrap.php";

redirectToolDevOutside();

#
# find available tools - by user

$toolsList  = array();

$result = array(); 

switch($_SESSION['User']['Type']) {

	case 0:
	    $result = $GLOBALS['toolsDevMetaCol']->find();
		break;
	default:
	    $result = $GLOBALS['toolsDevMetaCol']->find(array("user_id" => $_SESSION['User']['id']));


}

foreach (array_values(iterator_to_array($result)) as $v){

	$toolsList[] = $v;

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
                                  <span>Tools under development</span>
                              </li>
                            </ul>
                        </div>
                        <!-- END PAGE BAR -->
                        <!-- BEGIN PAGE TITLE-->
                        <h1 class="page-title"> Tools under development
                            <small></small>
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

                        <div class="row">
                            <div class="col-md-12">
                                <!-- BEGIN EXAMPLE TABLE PORTLET-->
                                <div class="portlet light portlet-fit bordered">
				    <div class="portlet-body">
					<a href="admin/newTool.php" class="btn btn-lg green" style="margin-bottom:30px;"> <i class="fa fa-plus"></i> Create new</a>

                                        <input type="hidden" id="base-url" value="<?php echo $GLOBALS['BASEURL']; ?>" />

                                        <table class="table table-striped table-bordered" id="my-new-tools" style="margin-top:30px;">
                                            <thead>
					    <tr>
						<th rowspan="2" style="vertical-align:middle">Tool (Identifier) 
                                                    <a href="javascript:;" target="_blank" class="tooltips" data-toggle="tooltip" data-trigger="hover" data-placement="top" title="Name (and identifier) for the tool being developed"><i class="icon-question"></i></a>
						</th>
                                                <th colspan="2">Wrap your code
                                                    <a href="javascript:;" target="_blank" class="tooltips" data-toggle="tooltip" data-trigger="hover" data-placement="top" title="Click the icon to open 'Bring your tool - Step 1' in a new tab"><i class="icon-question"></i></a>
                                                </th>
						<th rowspan="2" style="vertical-align:middle">Define Tool
						    <a href="javascript:;" target="_blank" class="tooltips" data-toggle="tooltip" data-trigger="hover" data-placement="top" title="Click the icon to open 'Bring your tool - Step 3' tab"><i class="icon-question"></i></a>
						</th>
                                                <th rowspan="2" style="vertical-align:middle">Submit Tool
                                                    <a href="javascript:;" target="_blank" class="tooltips" data-toggle="tooltip" data-trigger="hover" data-placement="top" title="Development status. Learn all available status by clicking the icon"><i class="icon-question"></i></a>
						</th>
						<th rowspan="2">Last update</th>
					    </tr>
                                            	<th>Generate test files
						    <a href="javascript:;" target="_blank" class="tooltips" data-toggle="tooltip" data-trigger="hover" data-placement="top" title="Click the icon to open 'Bring your tool - Step 2' in a new tab"><i class="icon-question"></i></a>
                                                </th>
                                                <th style="border-right: 1px solid #e7ecf1;">
							Bring us your code
							<a href="javascript:;" target="_blank" class="tooltips" data-toggle="tooltip" data-trigger="hover" data-placement="top" title="Click the icon to open 'Bring your tool - Step 2' in a new tab"><i class="icon-question"></i></a>
                                                </th>
                                           </tr>
					   </thead>
				<tbody>
			<?php foreach($toolsList as $v){ ?>

			<tr>
                <!-- show tool identifier -->
                <td style="padding-top:30px;">
										<?php if($_SESSION['User']['Type'] == 0) $uid = " <br> ".$v["user_id"]; ?>
										<b><?php echo $v["step3"]["tool_spec"]["name"]."&nbsp;&nbsp; ( ".$v["_id"]." )".$uid;?></b>
                    <br/><br/>
										<a href="javascript:removeTool('<?php echo $v["_id"];?>');" class="btn btn-icon-only red tooltips"
										data-toggle="tooltip" data-trigger="hover" data-placement="bottom" title="Click here to remove this tool permanently"
										><i class="fa fa-trash"></i></a>
										<br><br>

										<?php if(file_exists($GLOBALS['dataDir']."/".$v["user_id"]."/.dev/".$v['_id']."/logo/logo.png")) { ?>

										<a href="workspace/workspace.php?op=openPlainFileFromPath&fnPath=<?php echo $v["user_id"]."/.dev/".$v['_id']."/logo/logo.png";?>" target="_blank">
											<img src="workspace/workspace.php?op=openPlainFileFromPath&fnPath=<?php echo $v["user_id"]."/.dev/".$v['_id']."/logo/logo.png";?>" style="width:100px;border:1px solid #999;" />
										</a>
										<br>
										<a href="applib/createLogo.php?toolid=<?php echo $v["_id"]; ?>" class="btn green tooltips" style="margin-top:10px;"
										data-toggle="tooltip" data-trigger="hover" data-placement="bottom" title="Re-generates automatically a logo with the name of your tool."
										><i class="fa fa-file-image-o" aria-hidden="true"></i> Automatic logo</a>
										<form action="applib/uploadLogoTool.php" method="post" class="" id="uplogo_<?php echo $v["_id"]; ?>"  enctype="multipart/form-data">
											<div class="form-group" style="margin-top:10px;">
												<input type="file" name="file" id="file_logo_<?php echo $v["_id"]; ?>" class="inputfile" />
												<label for="file_logo_<?php echo $v["_id"]; ?>" class="btn green tooltips"
												data-toggle="tooltip" data-trigger="hover" data-placement="bottom" title="You can update your own logo, but it must be a PNG file of 600x600 pixels and 3MB of maximum size."
												>
												<i class="fa fa-upload" aria-hidden="true"></i> Change logo</label>
												<input type="hidden" name="toolid" value="<?php echo $v["_id"]; ?>" />
											</div>
										</form>
										<?php } else { ?>
										<a href="applib/createLogo.php?toolid=<?php echo $v["_id"]; ?>" class="btn green tooltips"
										data-toggle="tooltip" data-trigger="hover" data-placement="bottom" title="Generates automatically a logo with the name of your tool."
										><i class="fa fa-file-image-o" aria-hidden="true"></i> Automatic logo</a>
										<br>
										<form action="applib/uploadLogoTool.php" method="post" class="" id="uplogo_<?php echo $v["_id"]; ?>"  enctype="multipart/form-data">
											<div class="form-group" style="margin-top:10px;">
												<input type="file" name="file" id="file_logo_<?php echo $v["_id"]; ?>" class="inputfile" />
												<label for="file_logo_<?php echo $v["_id"]; ?>" class="btn green tooltips"
												data-toggle="tooltip" data-trigger="hover" data-placement="bottom" title="You can update your own logo, but it must be a PNG file of 600x600 pixels and 3MB of maximum size."
												>
												<i class="fa fa-upload" aria-hidden="true"></i> Choose a file</label>
												<input type="hidden" name="toolid" value="<?php echo $v["_id"]; ?>" />
											</div>
										</form>
										<?php } ?>
                </td>

                <td style="position:relative" class="mt-element-ribbon">

								<!-- show step1 status -->
					<?php if($v["step1"]["status"]) { ?>
                        <div class="ribbon ribbon-clip" style="left:-20px;">
                            <div class="ribbon-sub ribbon-clip" style="background-color: #26C281;"></div> <i class="fa fa-check" aria-hidden="true" style="color:white!important"></i>
												</div>
					<?php } else if(!$v["step1"]["status"] && $v["step1"]["tool_io_saved"]) { ?>
                        <div class="ribbon ribbon-clip" style="left:-20px;">
                            <div class="ribbon-sub ribbon-clip" style="background-color: #f3cc31;"></div> <i class="fa fa-warning" aria-hidden="true" style="color:white!important"></i>
												</div>
					<?php } else { ?>
                        <div class="ribbon ribbon-clip ribbon-color-danger" style="left:-20px;">
                            <div class="ribbon-sub ribbon-clip"></div> <i class="fa fa-times" aria-hidden="true" style="color:white!important"></i>
                        </div>
                    <?php } ?>


                <!-- show step1 main button -->
                <div style="margin: 10px 10px 10px 30px;">
					<?php if($v['last_status'] != "submitted" && $v['last_status'] != "registered" && $v['last_status'] != "rejected") { ?>
					  <?php if(!$v["step1"]["status"] && !$v["step1"]["tool_io_validated"] && !$v["step1"]["tool_io_saved"] && !$v["step1"]["tool_io_files"]) { ?>
						<p><a class="btn btn-block btn-sm green" href="admin/jsonTestValidator.php?id=<?php echo $v["_id"];?>"><i class="fa fa-plus" aria-hidden="true"></i> Define I/O</a></p>
					  <?php } else { ?>
						<p><a class="btn btn-block btn-sm green" href="admin/jsonTestValidator.php?id=<?php echo $v["_id"];?>"><i class="fa fa-edit" aria-hidden="true"></i> Update I/O</a></p>
						<?php if(!$v["step1"]["tool_io_validated"]) { ?>
						    <p>I/O definitions saved but <span class="font-red">not validated</span>, please edit them to generate the test files.</p>
                        <?php }
                      } 
										} else { 
										if($_SESSION['User']['Type'] != 0) {
										?>
						<p><a class="btn btn-block btn-sm green" disabled><i class="fa fa-edit" aria-hidden="true"></i> Update I/O</a></p>
										<?php } else { ?>
											<p><a class="btn btn-block btn-sm green" href="admin/jsonTestValidator.php?id=<?php echo $v["_id"];?>"><i class="fa fa-edit" aria-hidden="true"></i> Update I/O</a></p>
										<?php } ?>
                    <?php } ?>
                </div>

                <!-- show step1 test files | NEW -->

                <div class="ribbon-content" style="clear:both; padding:10px 0px;">
					<?php if(!$v["step1"]["tool_io_files"]) { ?>
                        <div class="note note-success" style="background-color:rgb(233, 237, 239);border-color:#bfcad1;padding:10px;">
                            <h5>Test Files &nbsp;&nbsp;<a disabled><i class="fa fa-download"></i></a></h5>
                            <p>Not created yet</p>
                        </div>

                     <?php } else { ?>
                        <div class="note note-success" style="background-color:rgb(233, 237, 239);border-color:#bfcad1;padding:10px;">
                            <h5>Test Files &nbsp;&nbsp;
				<a href="workspace/workspace.php?op=openPlainFileFromPath&fnPath=<?php echo $v["step1"]["test_files"]."&v=".rand();?>"><i class="fa fa-download"></i></a>
                            </h5>
                        <?php
                        if (count($v["step1"]["files"])> 1){?>
													<div class=btn-group-solid">
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline green dropdown-toggle" data-toggle="dropdown">test.sh <i class="fa fa-angle-down"></i></button>
                                <ul class="dropdown-menu" role="menu">
                                    <?php foreach($v["step1"]["files"] as $comb => $files) { ?>
					<li><a href="workspace/workspace.php?op=openPlainFileFromPath&fnPath=<?php echo $v["step1"]["files"][$comb]['bash_file']."&v=".rand(); ?>" target="_blank"><?php echo $comb;?></a></li>
                                    <?php } ?>
                                </ul>
                            </div>
                            <div class="btn-group btn-group-sm" style="margin-left: -4px;">
                                <button type="button" class="btn btn-outline green dropdown-toggle" data-toggle="dropdown">config.json <i class="fa fa-angle-down"></i></button>
                                <ul class="dropdown-menu" role="menu">
                                    <?php foreach($v["step1"]["files"] as $comb => $files) { ?>
                                    <li><a href="workspace/workspace.php?op=openPlainFileFromPath&fnPath=<?php echo $v["step1"]["files"][$comb]['configuration_file']."&v=".rand(); ?>" target="_blank" ><?php echo $comb;?></a></li>
                                    <?php } ?>
                                </ul>
                            </div>
                            <div class="btn-group btn-group-sm" style="margin-left: -4px;">
                                <button type="button" class="btn btn-outline green dropdown-toggle" data-toggle="dropdown">metadata.json <i class="fa fa-angle-down"></i></button>
                                <ul class="dropdown-menu" role="menu">
                                    <?php foreach($v["step1"]["files"] as $comb => $files) { ?>
                                    <li><a href="workspace/workspace.php?op=openPlainFileFromPath&fnPath=<?php echo $v["step1"]["files"][$comb]['metadata_file']."&v=".rand(); ?>" target="_blank" ><?php echo $comb;?></a></li>
                                    <?php } ?>
                                </ul>
														</div>
													</div>

                        <?php  }else{ ?>

                            <div class="btn-group btn-group-sm btn-group-solid">
                                <?php foreach($v["step1"]["files"] as $comb => $files) { ?>
                                <a type="button" class="btn btn-outline green" href="workspace/workspace.php?op=openPlainFileFromPath&fnPath=<?php echo $v["step1"]["files"][$comb]['bash_file']."&v=".rand(); ?>" target="_blank">test.sh </a>
                                <a type="button" class="btn btn-outline green" href="workspace/workspace.php?op=openPlainFileFromPath&fnPath=<?php echo $v["step1"]["files"][$comb]['configuration_file']."&v=".rand(); ?>" target="_blank">config.json</a>
                                <a type="button" class="btn btn-outline green" href="workspace/workspace.php?op=openPlainFileFromPath&fnPath=<?php echo $v["step1"]["files"][$comb]['metadata_file']."&v=".rand(); ?>" target="_blank">metadata.json</a>
                                <?php } ?>
                                </ul>
                            </div>

                        <?php }?>
                        </div>

                      <!-- show step1 test files | OLD -->

                     <?php } ?>

                <!-- show step1 tool I/O -->
				<?php if($v["step1"]["tool_io_validated"] || $v["step1"]["tool_io_saved"] ) { ?>
					<p class="margin-top-10 margin-bottom-40"><a href="admin/viewJSON.php?id=<?php echo $v["_id"];?>&type=io" target="_blank">View i/o definition</a></p>
                <?php } ?>

                <!-- show last update -->
				<?php if($v["step1"]["date"] ) { ?>
                   <p style="font-size:12px;position:absolute;bottom:-10px;">Last updated: <?php echo $v["step1"]["date"]; ?></p>						
                <?php } ?>

                </div>
                </td>


                <td style="position:relative" class="mt-element-ribbon">

                <!-- show step2 status -->
					<?php if($v["step2"]["status"]) { ?>
                        <div class="ribbon ribbon-clip" style="left:-20px;">
                            <div class="ribbon-sub ribbon-clip" style="background-color: #26C281;"></div> <i class="fa fa-check" aria-hidden="true" style="color:white!important"></i></div>
                        </div>
					<?php } else { ?>
                        <div class="ribbon ribbon-clip ribbon-color-danger" style="left:-20px;">
                            <div class="ribbon-sub ribbon-clip"></div> <i class="fa fa-times" aria-hidden="true" style="color:white!important"></i></div>
                        </div>
                    <?php } ?>


                <!-- show step2 main button -->
                <div style="margin: 10px 10px 10px 30px;">
					<?php if($v['last_status'] != "submitted" && $v['last_status'] != "registered" && $v['last_status'] != "rejected") { ?>
					  <?php if(!$v["step2"]["status"]) { ?>
						<p><a class="btn btn-block btn-sm green" href="admin/vmURL.php?id=<?php echo $v["_id"];?>"><i class="fa fa-plus" aria-hidden="true"></i> Load your code</a></p>
					  <?php } else { ?>
						<p><a class="btn btn-block btn-sm green" href="admin/vmURL.php?id=<?php echo $v["_id"];?>"><i class="fa fa-edit" aria-hidden="true"></i> Update URL code</a></p>
                        <?php }
												} else { 
												if($_SESSION['User']['Type'] != 0) {
													?>
						<p><a class="btn btn-block btn-sm green" disabled><i class="fa fa-edit" aria-hidden="true"></i> Update URL code</a></p>
												<?php } else { ?>
												<p><a class="btn btn-block btn-sm green" href="admin/vmURL.php?id=<?php echo $v["_id"];?>"><i class="fa fa-edit" aria-hidden="true"></i> Update URL code</a></p>
												<?php } ?>
                    <?php } ?>
                </div>

                <!-- show step2 show code -->
                <div class="ribbon-content" style="clear:both; padding:10px 0px;">

                    <div class="input-group" style="width:185px;">
                        
					<?php if($v["step2"]["tool_code"]) { ?>
												<input type="text" readonly class="form-control" value="<?php echo $v["step2"]["tool_code"]; ?>">
												<a href="<?php echo $v["step2"]["tool_code"]; ?>" target="_blank" class="input-group-addon tooltips" 
												data-toggle="tooltip" data-trigger="hover" data-placement="bottom" title="<?php echo $v["step2"]["tool_code"]; ?>"
												style="background:#5e738b;"><i class="fa fa-link font-white"></i></a>
					<?php } else { ?>
												<input type="text" readonly class="form-control" value="">
												<span class="input-group-addon" style="background:#5e738b;"><i class="fa fa-link font-white"></i></span>
										<?php } ?>
										
                    </div>

                <!-- show last update -->
				    <?php if($v["step2"]["date"] ) { ?>
                      <p  style="font-size:12px;position:absolute;bottom:-10px;">Last updated: <?php echo $v["step2"]["date"]; ?></p>						
                   <?php } ?>
                  </div> 


                </td>


                <td style="position:relative" class="mt-element-ribbon">

                <!-- show step3 status -->
					<?php if($v["step3"]["status"]) { ?>
                        <div class="ribbon ribbon-clip" style="left:-20px;">
                            <div class="ribbon-sub ribbon-clip" style="background-color: #26C281;"></div> <i class="fa fa-check" aria-hidden="true" style="color:white!important"></i></div>
                        </div>
					<?php } else { ?>
                        <div class="ribbon ribbon-clip ribbon-color-danger" style="left:-20px;">
                            <div class="ribbon-sub ribbon-clip"></div> <i class="fa fa-times" aria-hidden="true" style="color:white!important"></i></div>
                        </div>
                    <?php } ?>

                <!-- show step3 main button -->

                <div style="margin: 10px 10px 10px 30px;">
					<?php if($v['last_status'] != "submitted" && $v['last_status'] != "registered" && $v['last_status'] != "rejected") { ?>
					  <?php if(!$v["step3"]["status"] && !$v["step3"]["tool_spec_validated"] && !$v["step3"]["tool_spec_saved"]) { ?>
						<p><a class="btn btn-block btn-sm green" href="admin/jsonSpecValidator.php?id=<?php echo $v["_id"];?>"><i class="fa fa-plus" aria-hidden="true"></i> Define tool</a></p>
						<?php } else { 

						?>
						<p><a class="btn btn-block btn-sm green" href="admin/jsonSpecValidator.php?id=<?php echo $v["_id"];?>"><i class="fa fa-edit" aria-hidden="true"></i> Update tool specification</a></p>
                      <?php  } 
												} else { 
													if($_SESSION['User']['Type'] != 0) {
												?>
						<p><a class="btn btn-block btn-sm green" disabled><i class="fa fa-edit" aria-hidden="true"></i> Update tool specification</a></p>
												<?php } else { ?>
													<p><a class="btn btn-block btn-sm green" href="admin/jsonSpecValidator.php?id=<?php echo $v["_id"];?>"><i class="fa fa-edit" aria-hidden="true"></i> Update tool specification</a></p>
												<?php } ?>
                    <?php } ?>

                </div>

                <!-- show step3 tool specification -->
                <div class="ribbon-content" style="clear:both; padding:10px 0px;">

				<?php if($v["step3"]["tool_spec"]) { ?>
					<p class="margin-top-10 margin-bottom-40"><a href="admin/viewJSON.php?id=<?php echo $v["_id"];?>&type=sp" target="_blank">View tool specification</a></p>
				<?php } else { ?>
					<p class="margin-top-10 margin-bottom-40"><a disabled>View tool specification</a></p>
				<?php } ?>


                <!-- show last update -->
			    <?php if($v["step3"]["date"] ) { ?>
                     <p  style="font-size:12px;position:absolute;bottom:-10px;">Last updated: <?php echo $v["step3"]["date"]; ?></p>						
                <?php } ?>
                </div>
                </td>



                <td style="padding-top:30px;">

                <!-- show final step status -->
					<?php switch($v['last_status']) { 

						case "in_preparation": 
                            if(!$v["step1"]["status"] || !$v["step2"]["status"] || !$v["step3"]["status"] ){ ?>
                                <div class="note note-warning">
                                    <p class="font-yellow"><b>IN PREPARATION</b>:<br/> Working on the tool..</p>
                                </div>
                            <?php } else { ?>
                                <div class="note note-warning">
		        					<p class="font-yellow"><b>IN PREPARATION</b>:<br/> Code ready to be submitted!</p>
                                </div>
                            <?php	}
							break;

                        case "submitted": ?>
                            <div class="note note-success" style="background-color:rgba(109, 91, 142,0.7);border-color:rgb(109, 91,142)">
                            <p class="font-white"><b>SUBMITTED</b>:<br/> Waiting for VRE team response.</p>
                            </div>
                            <?php break;

                        case "to_be_reviewed":  ?>
                            <div class="note note-danger" style="background-color:rgba(109, 91, 142,0.7),border-color:rgb(109, 91,142)">
                            <p class="font-red"><b>TO BE REVIEWED</b>:<br/>Please, update your code as suggested and submit it again.</p>
                            </div>
                            <?php break;

                        case "registered":  ?>
                            <div class="note bg-green-jungle">
                            <p class="font-white"><b>ACCEPTED</b>:<br/>Tool successfully registed!</p>
                            </div>
                            <?php break;

                        case "rejected":  ?>
                            <div class="note note-danger">
                            <p class="font-red"><b>REJECTED</b>:<br/>Code not accepted</p>
                            </div>
                            <?php break;
			

					}  ?>

								

                <!-- show final step submit  button -->
                <div style="margin: 10px 0;">
                    <?php if($v['last_status'] == "in_preparation"){ 
					    if(!$v["step1"]["status"] || !$v["step2"]["status"] || !$v["step3"]["status"] ){ ?>
                            <a class="btn btn-block btn-sm green" disabled><i class="fa fa-check" aria-hidden="true" title="Pass the previous steps before submitting the tool"></i> Submit tool</a>
                        <?php } else { ?>
                            <a class="btn btn-block btn-sm green" href="javascript:submitTool('<?php echo $v["_id"];?>');"><i class="fa fa-check" aria-hidden="true"></i> Submit tool</a>
                    <?php } 
                    } ?>
                </div>

								<!-- only for admin -->
								<?php if($_SESSION['User']['Type'] == 0) { ?>
								<div class="btn-group btn-group-sm">
                	<button type="button" class="btn btn-outline green dropdown-toggle" data-toggle="dropdown">Status <i class="fa fa-angle-down"></i></button>
                  <ul class="dropdown-menu pull-right" role="menu">
                  	<li><a href="applib/changeToolStatus.php?toolid=<?php echo $v['_id']; ?>&status=in_preparation">In preparation</a></li>
                    <li><a href="applib/changeToolStatus.php?toolid=<?php echo $v['_id']; ?>&status=submitted">Submitted</a></li>
										<li><a href="applib/changeToolStatus.php?toolid=<?php echo $v['_id']; ?>&status=to_be_reviewed">To be reviewed</a></li>
										<li><a href="applib/changeToolStatus.php?toolid=<?php echo $v['_id']; ?>&status=registered">Registered</a></li>
										<li><a href="applib/changeToolStatus.php?toolid=<?php echo $v['_id']; ?>&status=rejected">Rejected</a></li>
                  </ul>
                </div>
								<?php } ?>

					
				</td>
				<td><?php echo $v["last_status_date"]; ?></td>
			</tr>

		<?php
		}
		?>

					    </tbody>
                                       </table>
				</div>


                                <!-- END EXAMPLE TABLE PORTLET-->
                            </div>
                        </div>
                    </div>
                    <!-- END CONTENT BODY -->
                </div>
                <!-- END CONTENT -->

		<div class="modal fade bs-modal" id="modalSubmitTool" tabindex="-1" role="basic" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                                <h4 class="modal-title">Submit Tool</h4>
														</div>
														<form id="submit-tool" method="post" action="applib/submitTool.php">
															<div class="modal-body table-responsive">
																<p id="st-title">You are about to submit the x tool, please fill the comments to send a message to our technical team</p>
																	<div class="form-group">
																		<textarea class="form-control" style="width:100%;" name="comments" rows="5" placeholder="Write your comments here"></textarea>
																	</div>
																	<input type="hidden" name="toolid" id="toolid-modal" value="" />
															</div>
															<div class="modal-footer">
																	<button type="button" class="btn dark btn-outline" data-dismiss="modal">Close</button>
																	<button type="submit" class="btn green">Submit</button>
															</div>
														</form>
                        </div>
                    </div>
								</div>

								<div class="modal fade bs-modal" id="modalRemoveTool" tabindex="-1" role="basic" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                                <h4 class="modal-title">Remove Tool</h4>
														</div>
															<div class="modal-body table-responsive">
																
															</div>
															<div class="modal-footer">
																	<button type="button" class="btn dark btn-outline" data-dismiss="modal">Close</button>
																	<a href="javascript:;" id="btn-rmv-tool" class="btn red">Remove</a>
															</div>
                        </div>
                    </div>
                </div>


<?php 

require "../htmlib/footer.inc.php"; 
require "../htmlib/js.inc.php";

?>

