<?php

require __DIR__."/../../config/bootstrap.php";

redirectToolDevOutside();

#
# find available tools - by user

$tools  = array();
$result = array(); 
$stats_filter = array();

// query 'tools' collection
if ($_SESSION['User']['Type'] == 0){
        // do query
        $result = $GLOBALS['toolsCol']->find(array());

}elseif ($_SESSION['User']['Type'] == 1){
    if ($_SESSION['User']['ToolsDev']){
        // do query
        $result = $GLOBALS['toolsCol']->find(array("_id" => array('$in' => $_SESSION['User']['ToolsDev'])));
        // prepare stats filter
        $stats_filter = array("toolId" => array('$in' => $_SESSION['User']['ToolsDev']));

    }else{
        // return error
        $_SESSION['errorData']['Error'][]="Sorry, your account has no tool ownership. Sorry, cannot adminstrate them.";
        // do dump query
        $result = $GLOBALS['toolsCol']->find(array("_id" => "force_empty"));
        // prepare dump stats filter
        $stats_filter = array("force" => "empty");
    }
}else{
	redirect($GLOBALS['URL']);
}

// format query result
foreach (array_values(iterator_to_array($result)) as $v){
	$toolId = $v['_id'];
	$tools[$toolId]['json']  = $v;
	//$tools[$toolId]['json']["owners"]  = getToolDev_fromTool($toolId);
	if ( $tools[$toolId]['json']['owner']['user'] ){
		$tools[$toolId]['json']["owners"] =  array($tools[$toolId]['json']['owner']['user']);
	}else{
		$tools[$toolId]['json']["owners"] =  array($tools[$toolId]['json']['owner']['author']);
	}
}
//  get statistics
if ($_REQUEST['tools']){
        if (! is_array($_REQUEST['tools'])){ $_REQUEST['tools'] = array($_REQUEST['tools']); }
        $stats_filter = array("toolId" => array('$in' => $_REQUEST['tools']));
}
$jobs  = aggregateJobLogs($stats_filter);
$stats = getStatsFromJobLogs($jobs,array_keys($tools));




//// if export=true, generate CSV and exit

if ($_REQUEST['export'] == 1){


    // set CSV name
    $fileName='VRE_jobstats_'.date('m-d-Y').".csv";

    // print job entry per line
    ob_start();
    ob_clean();

    header('Pragma: public');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Cache-Control: private', false);
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename=' . $fileName); 

    printCSVJobLogs($jobs,'php://output');

    ob_flush();
    die(0);

}


 
//// if no export, print logs html page


$toolsList = $tools;

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
                                  <span>My installed Tools</span>
                              </li>
                            </ul>
                        </div>
                        <!-- END PAGE BAR -->
                        <!-- BEGIN PAGE TITLE-->
                        <h1 class="page-title"> My Tools Administration
                            <small>configure & test</small>
					    <div class="btn-group" style="float:right;">
                            <div class="actions">
                            <a href="admin/adminTools.php?export=1" class="btn green"><i class="fa fa-download"></i> Download Statistics </a>
							</div>
                        </div>
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
                                        <input type="hidden" id="base-url" value="<?php echo $GLOBALS['BASEURL']; ?>" />

                                        <table class="table table-striped table-hover table-bordered" id="sample_editable_1">
                                            <thead>
                                                <tr>
                                                    <th>Tool Id</th>
													<th>Status</th>
                                                    <th>Owner/s</th>
                                                    <th>Tool specification</th>
                                                    <th>Statistics</th>
                                                </tr>
                                            </thead>
                                            <tbody>
		<?php
		foreach($toolsList as $toolId => $v){
		?>
			<tr>
				<td><?php echo $toolId;?></td>
				<td><?php 
					if(!$v["json"]["external"]) {
                        // show Internal tool 
						echo '<span class="label label-default"><b>Internal</b></span>';
                    } else {
                        // allow update status
                        if($_SESSION['User']['Type'] == 0 || ($_SESSION["User"]["Type"] == 1 && in_array($toolId,$_SESSION['User']['ToolsDev']))){

						    echo ' <select onChange="changeToolStatus(\''.$toolId.'\', this);">';
						    echo '   <option value="" disabled selected> status...</option>';

    						foreach($GLOBALS['tool_status'] as $k => $stts) {
								//if($k == $v["json"]["status"]) $sel = "selected";
								//else $sel = "";
								echo '<option value="'.$k.'" '.$sel.'>'.$stts.'</option>';
    						}
    						echo '</select>  ';
                        }
                        // show status 
						switch($v["json"]["status"]){
							case 0: echo '<span class="label label-warning"><b> '.$GLOBALS['tool_status'][$v["json"]["status"]].' </b></span>';break;
							case 1: echo '<span class="label label-primary"><b> '.$GLOBALS['tool_status'][$v["json"]["status"]].' </b></span>';break;
							case 2: echo '<span class="label label-danger"><b> '.$GLOBALS['tool_status'][$v["json"]["status"]].' </b></span>';break;
                            case 3: echo '<span class="label bg-purple-intense"><b> '.$GLOBALS['tool_status'][$v["json"]["status"]].'</b></span>';break;
                        }

                    }
                ?>
                </td>

				<td>
                    <?php echo implode(",<br/>", $v["json"]["owners"]); ?>
                <td>
					<a href="javascript:callShowToolJson('<?php echo $toolId;?>')">View JSON</a>
				</td>
                <td>
                <?php if ($stats[$toolId]["jobs_total"]){ ?>
                    <ul>
                    <li><?php echo "Total num. of jobs: ".$stats[$toolId]["jobs_total"]; ?></li>
                    <li><?php echo "Successfully finished: ".number_format(($stats[$toolId]["jobs_finished_success"]/$stats[$toolId]["jobs_total"])*100,2)."%";?></li>
                    <li><?php echo "Distinct users: ".count(array_keys($stats[$toolId]["distinct_users"])); ?></li>
                    <li><?php echo "Average duration: ".$stats[$toolId]["avg_duration"]." minutes"; ?></li>
                    </ul>
                    <!--
                    <div class="sparkline-chart">
                        <span id="spark_registers"><?php //$i = 1; foreach($stats[$toolId]["freq_executions"] as $k => $v){  if($i < sizeof($$stats[$toolId]["freq_executions"])) { echo $v.','; }else{ echo $v; } $i++; } ?></span>
    					<p style="margin-top:15px;">Monthly new users</p>
                    </div> -->
                    <p><a href="admin/adminTools.php?tools=<?php echo $toolId;?>&export=1" class="btn btn-sm green"><i class="fa fa-download"></i> Download statistics </a></p>
                <?php }else{ ?>
                    <ul>
                    <li><?php echo "Total num. of jobs: ".$stats[$toolId]["jobs_total"]; ?></li>
                    </ul>
                <?php } ?>
				</td>
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


<?php 

require "../htmlib/footer.inc.php"; 
require "../htmlib/js.inc.php";

?>
