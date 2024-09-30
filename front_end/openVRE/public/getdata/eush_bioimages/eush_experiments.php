<?php

require __DIR__ . "/../../../config/bootstrap.php";

?>

<?php
require "../../htmlib/header.inc.php";
require "../../htmlib/js.inc.php"; ?>

<body class="page-header-fixed page-sidebar-closed-hide-logo page-content-white page-container-bg-solid page-sidebar-fixed">
    <div class="page-wrapper">

        <?php require "../../htmlib/top.inc.php"; ?>
        <?php require "../../htmlib/menu.inc.php"; ?>

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
                            <span>Get Data</span>
                            <i class="fa fa-circle"></i>
                        </li>
                        <li>
                            <span>Object Storage</span>
                        </li>
                    </ul>
                </div>
                <!-- END PAGE BAR -->

                <!-- BEGIN PAGE TITLE-->
                <h1 class="page-title"> Object Storage
                    <small> List of containers and files for an specific project on the Object Storage of OpenStack, hosted at BSC </small>
                </h1>
                <!-- END PAGE TITLE -->
                <!-- END PAGE HEADER -->

                <!-- BEGIN ERRORS DIV -->
                <div id="errorsTool" style="display:none;"></div>
                <div class="row">
                        <div class="col-md-12">
                        <?php
                        $error_data = false;
                        if ($_SESSION['errorData']) {
                            $error_data = true;
                      
                            if ($_SESSION['errorData']['Info']) { ?>
                                <div class="alert alert-info">
                             <?php } else { ?>
                                <div class="alert alert-danger">
                             <?php }
                             foreach ($_SESSION['errorData'] as $subTitle => $txts) {
                                        print "<strong>$subTitle</strong><br/>";
                                        foreach ($txts as $txt) {
                                            print "<div>$txt</div>";
                                        }
                             }
                             unset($_SESSION['errorData']);
                             ?>
                                </div>
			  <?php } ?>
			  <h4>Granted Access to: </h4>
                          <div id="loading-datatable" class="loadingForm">
                                <div id="loading-spinner">LOADING</div>
                                <!-- <div id="loading-text">It could take a few minutes</div> -->
                           </div>
                           <div class="portlet light portlet-fit bordered" id="general">
                                <div id="workflows" class="portlet-body">
                                    <div class="btn-group" style="float:right; margin-bottom: 20px" >
                                        <div class="actions">
					    <a id="workflowsReload" class="btn green" disabled> Reload</a>
					    <a id=getCredentialsButton" class="btn green" disabled> Get Credentials </a>
					</div>
					<!-- <table id="credentialTable" class="table table-striped table-hover table-bordered"></table>-->
				    </div>
				    <select id="containerDropdown" class="form-control" style="margin-bottom: 20px;"></select>
				    <input type="hidden" id="base-url" value="<?php echo $GLOBALS['BASEURL']; ?>" />
				    <table id="workflowsTable" class="table table-striped table-hover table-bordered">
					<thead>
						<tr>
            						<th>Name</th>
           						<th>Action</th>
        					</tr>	
    					</thead>
    					<tbody id="workflow-data"></tbody>
        			    </table>
                                </div>
                            </div>
		       </div>
		    </div>
                    </div>
                    </div>
                
                <!-- END ERRORS DIV -->
                <!-- END EXAMPLE TABLE PORTLET-->
                </div>
                <!-- END CONTENT BODY -->
                <!-- VIEW JSON PART -->
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
                <style type="text/css">
                    #workflowsTable_filter {
                        float: right;
                    }

                    .btn-block {
                        width: 100%;
                        font-size: 12px;
                        display: block;
                        line-height: 1.5;
		    }

		    .loadingForm {
			position: fixed;
        		top: 0;
        		left: 0;
        		width: 100%;
        		height: 100%;
        		background-color: rgba(255, 255, 255, 0.7); /* semi-transparent white background */
        		z-index: 1000; /* ensures it's on top of other content */
		    }
		    
		    #loading-spinner {
			position: absolute;
			top: 50%;
			left: 50%;
        		transform: translate(-50%, -50%);
        		font-size: 14px;
        		color: #333; /* dark color for the spinner text */
    		    }
#loading-text {
        position: absolute;
        top: calc(50% + 20px); /* slightly below the spinner text */
        left: 50%;
        transform: translateX(-50%);
        font-size: 14px;
        color: #333; /* dark color for the loading text */
    }


                </style>
                <?php
                require "../../htmlib/footer.inc.php";
                ?>
