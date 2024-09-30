<?php

require __DIR__."/../../config/bootstrap.php";

redirectToolDevOutside();


# check previleges

$filters = array();

// show tool_dev tool logs
if ( $_SESSION['User']['Type'] == 1){
    if (! $_SESSION['User']['ToolsDev'] ){
        $_SESSION['errorData']['Error'][]="Your account owns no tools. Sorry, no logs to show";
        $filters = array("force" => "empty");
    }
    $filters = array("toolId" => array('$in' => $_SESSION['User']['ToolsDev']));

// show all tool logs
}elseif ( $_SESSION['User']['Type'] == 0){
    $filters = array();
}

// find in mongo log execution collection grouping by job_id
    $jobs = aggregateJobLogs($filters);



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
                                  <span>Logs</span>
                              </li>
                            </ul>
                        </div>
                        <!-- END PAGE BAR -->
                        <!-- BEGIN PAGE TITLE-->
                        <h1 class="page-title"> Logs
                            <small>for tool executions</small>
					    <div class="btn-group" style="float:right;">
                            <div class="actions">
                            <?php $query_url = http_build_query($_REQUEST); ?>
                            <a href="admin/logs.php?<?php echo $query_url;?>&export=1" class="btn green"><i class="fa fa-download"></i> Export </a>
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

                                        <table class="table table-striped table-hover table-bordered" id="table-logs">
                                            <thead>
                                                <tr>
                                                    <th>Job Id</th>
                                                    <th>Execution</th>
													<th>Tool Id</th>
                                                    <th>User</th>
                                                    <th>Success</th>
                                                    <th>Date</th>
                                                    <th>Log message</th>
                                                </tr>
                                            </thead>
                                            <tbody>
        <?php
        // print logEvents foreach job
        foreach($jobs as $pid => $jobInfo){

            $user = $jobInfo['user'];
            if ($_SESSION['User']['Type']==0 || $_SESSION['User']["_id"] == $jobInfo['user']){
                $u = checkUserIDExists($jobInfo['user']);
                $user = $u['_id'];
            }

            // for each log entry
            $rowspan = count($jobInfo["logs"]);
            $i = 0;
            foreach ($jobInfo["logs"] as $logEvent){
                // process submission msg
                if ($logEvent["log_type"] == "Submission"){
                    $logEvent["msg"] = $logEvent["launcher"]." job submitted with ". $logEvent["cpus"]. " CPUS and ".$logEvent["memory"]. "GB of RAM";
                }
                // print logEvent line 
            ?>
            <tr>
                <?php if ($i == 0 ){ ?>
                    <td rowspan="<?php echo $rowspan; ?>">
                        <span class="truncate" style="max-width:50px;" title="<?php echo $pid;?>"><?php echo $pid;?></span>
                    </td>
                    <td rowspan="<?php echo $rowspan; ?>">
                        <a href="javascript:;" class="popovers" data-toggle="popover" title="Execution path" data-html="true" data-content="<span style='word-break:break-all;'><?php echo $jobInfo['work_dir']; ?></span>"><?php echo basename($jobInfo['work_dir']); ?></span>
                    </td>
                    <td rowspan="<?php echo $rowspan; ?>">
                        <?php echo $jobInfo['toolId'];?>
                    </td>
                    <td rowspan="<?php echo $rowspan; ?>">
                        <span class="truncate" title="<?php echo $user;?>"><?php echo $user;?></span>
                    </td>
                    <td rowspan="<?php echo $rowspan; ?>">
                        <b><?php
                        if($jobInfo['success'] == "TRUE"){
                            print "<span class='font-green-meadow'>TRUE</span>";
                        }elseif($jobInfo['success'] == "ERR"){
                            print "<span class='font-red'>ERR</span>";
				        }else{
                            print $jobInfo["success"];
                        }?>
                        </b>
                    </td>
				<?php } ?>

                <td nowrap class="denser">
                    <?php echo strftime('%d/%m/%Y %H:%M', $logEvent['date']->toDateTime()->format('U')); ?>
                </td>
                <td class="denser">
                    <?php if($logEvent["msg"]){echo "<b>[".$logEvent['log_type']."]</b> ".str_replace("/"," /",$logEvent["msg"]);} ?>
                </td>
			</tr>
              <?php
              $i++;
            }

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
