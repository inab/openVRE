<?php

require __DIR__ . "/../../config/bootstrap.php";

redirectOutside();


// Retrieve data to be displayed 

$url = "https://dev-openebench.bsc.es/sciapi/graphql/";




$data_string = 
'{ "query" : 
  "{ getDatasets(datasetFilters:
    {visibility:\"public\",type:\"metrics_reference\"})
 { _id 
  community_id 
  visibility 
  name 
  version 
  description 
  type
  datalink {
     uri 
    } 
    } 
  }"
}';



$headers = array(
  'Content-Type: application/json',
  'Content-Length: ' . strlen($data_string)
);

list($r, $info) = post($data_string, $url, $headers);

logger("RESPONSE => " . json_encode($r) . "'");


if ($r == "0") {
  if ($_SESSION['errorData']['Error']) {
    $err = array_pop($_SESSION['errorData']['Error']);
    logger("ERROR:" . $err);
  }
  if ($info['http_code'] != 200) {
    logger("ERROR: Unexpected http code. HTTP code: " . $info['http_code']);
    logger("ERROR: calling PMES. POST_RESPONSE = '" . strip_tags($r) . "'");
  }
}

//var_dump($r);


$array = json_decode($r)->data->getDatasets;

// var_dump($array);

//exit(0);

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
          Benchmarking Datasets
        </h1>
        <!-- END PAGE TITLE-->
        <!-- END PAGE HEADER-->


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
                      <th> Community </th>
                      <th> Visibility </th>
                      <th> Import </th>
                    </tr>
                  </thead>
  
                  <tbody>
                    <!-- process and display each result row -->
                    <?php foreach ($array as $obj) {
                      

                      // if ($dataset->type == "metrics_reference") {

                      //   // get URL from datalink
                      //   $datalink = $obj->datalink;
                      //   $URL = "";
                      //   if ($datalink->attrs == "curie") {
                      //     #$URL = resolve_curie_via_idsolv($datalink->uri);
                      //     $URL = "www.google.cat";
                      //   } else {
                      //     $URL =  $datalink->uri;
                      //   }
                        ?>
                        <tr>
                          <td> <?php echo "$obj->_id"; ?> </td>
                          <td> <?php echo "$obj->name"; ?> </td>
                          <td> <?php echo "$obj->description"; ?> </td>
                          <td> <?php echo "$obj->version"; ?> </td>
                          <td> <?php echo "$obj->type"; ?> </td>
                          <td> <?php echo "$obj->community_id"; ?> </td>
                          <td> <?php echo "$obj->visibility"; ?> </td>
                          <!-- <td style="vertical-align:middle;">
                            <a href="applib/getData.php?uploadType=repository&url=<?php echo "$URL"; ?>&community=<?php echo "$obj->community_id"; ?>&type=<?php echo "$obj->type" ?>&id=<?php echo "$obj->_id" ?>">
                              <i class="font-green fa fa-download tooltips" aria-hidden="true" style="font-size:22px;" data-container="body" data-html="true" data-placement="right" data-original-title="<p align='left' style='margin:0'>Import dataset to workspace</p>"></i></a>
                          </td> -->
                          <td> <?php $obj2 = $obj->datalink;?> <a href="<?php echo $obj2->uri[0];?>"><?php echo $obj2->uri[0];?></a> </td>
                        </tr>
                          
                        
                      <?php //}
                     }  ?>

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
