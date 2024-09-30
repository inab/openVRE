<?php

require __DIR__ . "/../../config/bootstrap.php";

redirectOutside();

// Print page
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
              <a href="home/">Home</a>
              <i class="fa fa-circle"></i>
            </li>
            <li>
              <span>Get Data</span>
              <i class="fa fa-circle"></i>
            </li>
            <li>
              <span>From Repository</span>
              <i class="fa fa-circle"></i>
            </li>
            <li>
              <span>Repository Name</span>
            </li>
          </ul>
        </div>
        <!-- END PAGE BAR -->
        <!-- BEGIN PAGE TITLE-->
        <h1 class="page-title">
          <a href="javascript:;" target="_blank"><img src="assets/layouts/layout/img/icon.png" width=100></a>
          euCanSHare catalogue - My Datasets
        </h1>
        <!-- END PAGE TITLE-->
        <!-- END PAGE HEADER-->



	<?php

	// inject error Message
	$_SESSION['errorData']['Info'][]="Data catalogue under construction";


	// print PHP ERROR MESSAGES
	if (isset($_SESSION['errorData'])) {
		foreach ($_SESSION['errorData'] as $subTitle => $txts){
			if (count($txts) == 0){
				unset($_SESSION['errorData'][$subTitle]);
			}
		}
	}
	if (isset($_SESSION['errorData']) && $_SESSION['errorData']) {
		if (isset($_SESSION['errorData']['Info'])) {
			?><div class="alert alert-info"><?php
		} else {
			?><div class="alert alert-warning"><?php
		} 
		foreach ($_SESSION['errorData'] as $subTitle => $txts) {
			print "$subTitle<br/>";
			foreach ($txts as $txt) {
				print "<div style=\"margin-left:20px;\">$txt</div>";
			}
		}
		unset($_SESSION['errorData']);
		?></div><?php
	}
	?>


        <div class="row">
          <div class="col-md-12">
            <!-- BEGIN EXAMPLE TABLE PORTLET-->
            <div class="portlet light portlet-fit bordered">
              <div class="portlet-title">
                <div class="caption">
                  <i class="icon-share font-red-sunglo hide"></i>
                  <span class="caption-subject font-dark bold uppercase">Browse Datasets</span>
                </div>
              </div>
              <div class="portlet-body">
                <div id="loading-datatable">
                  <div id="loading-spinner">LOADING</div>
                </div>

                <table class="table table-striped table-hover table-bordered" id="table-repository">
                  <thead>
                    <tr>
                      <th> Id </th>
                      <th> Title </th>
                      <th> Description </th>
                      <th> Version </th>
                      <th> Type </th>
                      <th> Study </th>
                      <th> Access credentials </th>
                      <th> Import </th>

                    </tr>
                  </thead>
  
                  <tbody>
                    <!-- process and display each result row -->
                    
			<?php
	$ds_list =  getDatasets();	
	//JL DEMO
	$ds_list = True;
	if ($ds_list){
		$datasets=[
			['_id'=>'EUC0198100',
			'name'=> 'EUC_DEMO_MI_2020',
			'description' => 'Studies for myocardial infarction and healthy patients for demonstration',
			'version' => '1.0',
			'datatype' => 'Imaging Study',
			'study' => 'EUC_DEMO_MI',
			'access' => 'Granted'
		]];
		//		foreach (getDatasets() as $obj) {
		foreach ($datasets as $obj) { ?>
                        <tr>
                          <td> <?php echo $obj["_id"]; ?> </td>
                          <td> <?php echo $obj["name"]; ?> </td>
                          <td> <?php echo $obj["description"]; ?> </td>
                          <td> <?php echo $obj["version"]; ?> </td>
			  <td> <?php echo $obj["datatype"]; ?> </td>
                          <td> <?php echo $obj["study"]; ?> </td>
                          <td> <?php echo $obj['access']; ?> </td>
                          <td style="vertical-align:middle;">
			    
                            <?php
                              $dataset_uri = ($obj->datalink->uri? $obj->datalink->uri:"");
			    ?> 
			    <!-- Send via GET url and metadata to getData.php // CURRENTLY DONE VIA POST-->

                            <?php  
			      /*
                              $GET_metadata = http_build_query(array(
                                "uploadType"       => "repository",
                                "url"              => $dataset_uri,
				"data_type"        => "metrics_reference",
				"description"      => $obj->description,
                                "oeb_dataset_id"   => $obj->_id,
                                "oeb_community_ids"=> (array) $obj->community_ids
                              ));
                              */
                            ?>
                            <!-- <a <?php //if(!$dataset_uri){echo 'class="disabled" style="opacity: 0.4;"';}?> href="applib/getData.php?<?php //echo $GET_metadata; ?>">
                              <i class="font-green fa fa-download tooltips disabled" aria-hidden="true" style="font-size:22px;" data-container="body" data-html="true" data-placement="right" data-original-title="<p align='left' style='margin:0'>Import dataset to workspace</p>"></i></a> -->

			    <!-- Send via POST url and metadata to getData.php -->

			    <form action="applib/getData.php" method="post">
				<input type="hidden" name="uploadType"        value="eush_demo"/> 
				<input type="hidden" name="url"               value="" />
				<input type="hidden" name="data_type"         value=""/>
				<input type="hidden" name="description"       value="Studies for myocardial infarction and healthy patients for demonstration"/>
				<button type="submit" class="btn green dropdown-toggle" value="submit">
				    <i class="fa fa-download tooltips font-white" data-original-title="Import dataset to workspace"></i>
				</button>
			    </form>

                          </td>
                          
                        </tr>
                          
                        
	     <?php  }
	} ?>

                  </tbody>
                </table>
              </div>
            </div>
            <!-- END EXAMPLE TABLE PORTLET-->
          </div>
        </div>
      </div>
      <!-- END CONTENT BODY -->
    </div>
    <!-- END CONTENT -->

    <?php

    require "../htmlib/footer.inc.php";
    require "../htmlib/js.inc.php";
    
    
    

    ?>
