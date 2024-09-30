<?php

require __DIR__ . "/../../config/bootstrap.php";
redirectOutside();

$studies = array();

$url = "https://dev-openebench.bsc.es/api/scientific/Dataset.json";



$arrContextOptions = array(
    "ssl" => array(
        "verify_peer" => false,
        "verify_peer_name" => false,
    ),
);
$response = file_get_contents($url, false, stream_context_create($arrContextOptions));




$response_d = '[
    {
      "_id": "CAMEO:2017-01-14_00000003_1_M",
      "datalink": {
        "uri": "https://www.cameo3d.org/static/data/modeling/2017.01.14/2NAS_A/target.pdb",
        "attrs": "url",
        "validation_date": "2017-01-14T00:00:00Z",
        "status": "ok"
      },
      "type": "metrics_reference",
      "version": "1",
      "name": "Solution structure of a PWWP doamin from Trypanosoma brucei",
      "description": "Target 3_1 of week 2017-01-14",
      "dates": {
        "creation": "2017-01-14T00:00:00Z",
        "modification": "2017-01-18T00:00:00Z"
      },
      "depends_on": {
        "rel_dataset_ids": [
          {
            "dataset_id": "CAMEO:2017-01-14_00000003_1_I",
            "role": "input"
          }
        ]
      },
      "_schema": "https://www.elixir-europe.org/excelerate/WP2/json-schemas/0.4#Dataset",
      "community_id": "CAMEO",
      "dataset_contact_ids": [
        "help.CAMEO-3D"
      ],
      "visibility": "public"
    },
    {
      "_id": "CAMEO:2017-01-14_00000004_1_M",
      "_schema": "https://www.elixir-europe.org/excelerate/WP2/json-schemas/0.4#Dataset",
      "name": "The solution NMR structure of the C-terminal effector domain of BfmR from Acinetobacter baumannii",
      "version": "1",
      "description": "Target 04_1 of week 2017-01-14",
      "dates": {
        "creation": "2017-01-14T00:00:00Z",
        "modification": "2017-01-18T00:00:00Z"
      },
      "type": "metrics_reference",
      "datalink": {
        "uri": "https://www.cameo3d.org/static/data/modeling/2017.01.14/2NAZ_A/target.pdb",
        "attrs": "url",
        "validation_date": "2017-01-14T00:00:00Z",
        "status": "ok"
      },
      "community_id": "CAMEO",
      "depends_on": {
        "rel_dataset_ids": [
          {
            "dataset_id": "CAMEO:2017-01-14_00000004_1_I",
            "role": "input"
          }
        ]
      },
      "dataset_contact_ids": [
        "help.CAMEO-3D"
      ],
      "visibility": "public"
    },
    {
      "_id": "CAMEO:2017-01-14_00000010_1_M",
      "_schema": "https://www.elixir-europe.org/excelerate/WP2/json-schemas/0.4#Dataset",
      "name": "Crystal structure of phosphoribulokinase from Methanospirillum hungatei",
      "version": "1",
      "description": "Target 10_1 of week 2017-01-14",
      "dates": {
        "creation": "2017-01-14T00:00:00Z",
        "modification": "2017-01-18T00:00:00Z"
      },
      "type": "metrics_reference",
      "datalink": {
        "uri": "https://www.cameo3d.org/static/data/modeling/2017.01.14/5B3F_B/target.pdb",
        "attrs": "url",
        "validation_date": "2017-01-14T00:00:00Z",
        "status": "ok"
      },
      "community_id": "CAMEO",
      "depends_on": {
        "rel_dataset_ids": [
          {
            "dataset_id": "CAMEO:2017-01-14_00000010_1_I",
            "role": "input"
          }
        ]
      },
      "dataset_contact_ids": [
        "help.CAMEO-3D"
      ],
      "visibility": "public"
    },
    {
      "_id": "CAMEO:2017-01-14_00000013_2_M",
      "datalink": {
        "uri": "https://www.cameo3d.org/static/data/modeling/2017.01.14/5CIR_G/target.pdb",
        "attrs": "url",
        "validation_date": "2017-01-14T00:00:00Z",
        "status": "ok"
      },
      "type": "metrics_reference",
      "version": "1",
      "name": "Crystal structure of death receptor 4 (DR4; TNFFRSF10A) bound to TRAIL (TNFSF10)",
      "description": "Target 13_2 of week 2017-01-14",
      "dates": {
        "creation": "2017-01-14T00:00:00Z",
        "modification": "2017-01-18T00:00:00Z"
      },
      "depends_on": {
        "rel_dataset_ids": [
          {
            "dataset_id": "CAMEO:2017-01-14_00000013_1_I",
            "role": "input"
          }
        ]
      },
      "_schema": "https://www.elixir-europe.org/excelerate/WP2/json-schemas/0.4#Dataset",
      "community_id": "CAMEO",
      "dataset_contact_ids": [
        "help.CAMEO-3D"
      ],
      "visibility": "public"
    },
    {
      "_id": "CAMEO:2017-01-14_00000021_1_M",
      "type": "metrics_reference",
      "datalink": {
        "uri": "https://www.cameo3d.org/static/data/modeling/2017.01.14/5EJJ_B/target.pdb",
        "attrs": "url",
        "validation_date": "2017-01-14T00:00:00Z",
        "status": "ok"
      },
      "name": "Crystal structure of UfSP from C.elegans",
      "description": "Target 21_1 of week 2017-01-14",
      "dates": {
        "creation": "2017-01-14T00:00:00Z",
        "modification": "2017-01-18T00:00:00Z"
      },
      "depends_on": {
        "rel_dataset_ids": [
          {
            "dataset_id": "CAMEO:2017-01-14_00000021_1_I",
            "role": "input"
          }
        ]
      },
      "_schema": "https://www.elixir-europe.org/excelerate/WP2/json-schemas/0.4#Dataset",
      "community_id": "CAMEO",
      "version": "1",
      "dataset_contact_ids": [
        "help.CAMEO"
      ],
      "visibility": "public"
    },
    {
      "_id": "CAMEO:2017-01-14_00000024_1_M",
      "name": "Insights into Hunter syndrome from the structure of iduronate-2- sulfatase",
      "description": "Target 24_1 of week 2017-01-14",
      "dates": {
        "creation": "2017-01-14T00:00:00Z",
        "modification": "2017-01-18T00:00:00Z"
      },
      "type": "metrics_reference",
      "datalink": {
        "uri": "https://www.cameo3d.org/static/data/modeling/2017.01.14/5FQL_A/target.pdb",
        "attrs": "url",
        "validation_date": "2017-01-14T00:00:00Z",
        "status": "ok"
      },
      "version": "1",
      "depends_on": {
        "rel_dataset_ids": [
          {
            "dataset_id": "CAMEO:2017-01-14_00000024_1_I",
            "role": "input"
          }
        ]
      },
      "_schema": "https://www.elixir-europe.org/excelerate/WP2/json-schemas/0.4#Dataset",
      "community_id": "CAMEO",
      "dataset_contact_ids": [
        "help.CAMEO-3D"
      ],
      "visibility": "public"
    }
  ]';

// $array = json_decode($response_d);
$array = json_decode($response)->Dataset;




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
                            <span>From Repository List</span>
                        </li>
                    </ul>
                </div>
                <!-- END PAGE BAR -->
                <!-- BEGIN PAGE TITLE-->
                <h1 class="page-title">List of Datasets
                </h1>
                <!-- END PAGE TITLE-->
                <!-- END PAGE HEADER-->


                <?php
                if (isset($_SESSION['errorData'])) {
                    if (isset($_SESSION['errorData']['Info'])) {
                        ?>
                        <div class="alert alert-info">
                        <?php
                    } else {
                        ?>
                            <div class="alert alert-warning">
                            <?php } ?>
                            <?php foreach ($_SESSION['errorData'] as $subTitle => $txts) {
                                print "$subTitle<br/>";
                                foreach ($txts as $txt) {
                                    print "<div style=\"margin-left:20px;\">$txt</div>";
                                }
                            }
                            unset($_SESSION['errorData']);
                            ?>
                        </div>

                    <?php } ?>


                    <div class="mt-element-step">
                        <div class="row step-line"> </div>
                    </div>


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

                                    <!-- <div id="loading-datatable"><div id="loading-spinner">LOADING</div></div> -->
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
                                            <?php foreach ($array as $obj) :

                                                if ($obj->type == "metrics_reference") :

                                                    // get URL from datalink
                                                    $datalink = $obj->datalink;
                                                    $URL = "";
                                                    if ($datalink->attrs == "curie") {
                                                        #$URL = resolve_curie_via_idsolv($datalink->uri);
                                                        $URL = "www.google.cat";
                                                    } else {
                                                        $URL =  $datalink->uri;
                                                    }

                                                    // print entry 
                                                    ?>
                                                    <tr>
                                                        <td> <?php echo "$obj->_id" ?> </td>
                                                        <td> <?php echo "$obj->name" ?> </td>
                                                        <td> <?php echo "$obj->description" ?> </td>
                                                        <td> <?php echo "$obj->version" ?> </td>
                                                        <td> <?php echo "$obj->type" ?> </td>
                                                        <td> <?php echo "$obj->community_id" ?> </td>
                                                        <td> <?php echo "$obj->visibility" ?> </td>
                                                        <td style="vertical-align:middle;">
                                                            <a href="applib/getData.php?uploadType=repository&url=<?php echo "$URL"; ?>&community=<?php echo "$obj->community_id"; ?>&type=<?php echo "$obj->type" ?>&id=<?php echo "$obj->_id" ?>">
                                                                <i class="font-green fa fa-download tooltips" aria-hidden="true" style="font-size:22px;" data-container="body" data-html="true" data-placement="right" data-original-title="<p align='left' style='margin:0'>Import dataset to workspace</p>"></i></a>
                                                        </td>
                                                    </tr>
                                                <?php
                                            endif;
                                        endforeach;
                                        ?>
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
