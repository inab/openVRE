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

                <!-- BEGIN PAGE TITLE-->
                <h1 class="page-title"> Cardiovascular GWAS:
                    <small> Available studies </small>
                </h1>
                    <div class="card border border-dark" style="background-color: #f9f9f9;">
                        <div class="card-header text-center">
                            <h4 style="color: #005076"> MAP KINASES AND TAB PROTEINS </h4>
                        </div>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item"><strong> Description: </strong> GWAS study extracted from the Cardiovascular disease knowledge portal (Broad Institute). </li>
                            <li class="list-group-item"><strong> List of genes: </strong> MAPKAPK2, MAPK3, MAPK7, MAPKAPK5, MAPK6, MAPK9, MAPK15, MAPK1IP1L, MAPK11, MAPK4, MAPKAPK3, MAPK8IP2, MAPKAP1, MAPK12, MAPK14, MAPK1, MAPK10, MAPK13, MAPK8, MAPK8IP1, MAPKBP1, MAPK8IP3, STAB1, TAB1 </li>
                            <li class="list-group-item"><strong> Total genes number: </strong> 24</li>
                            <li class="list-group-item"><strong> Total variants number: </strong> 5260 </li>
                        </ul>
                        
                        <div class="card-body text-center align-content-center">
                            <!-- <a href="/vre/getdata/eush_cardiogwas/assets/eush_cardiogwas_table.php" class="btn btn-primary"> Go to the study </a> -->
                            <a href="/vre/getdata/eush_cardiogwas/eush_cardiogwas_table.php" class="btn btn-primary"> Go to the study </a>
                            <a href="https://cvd.hugeamp.org/" target="_blank" class="btn btn-warning"> Reference (CVD) </a>
                            <p></p>
                        </div>
		    </div>      

		<!-- BEGIN ERRORS DIV -->
		<br/><br/><br/>
                <div id="errorsTool" style="display:none;"></div>
                    <div class="row">
                        <div class="col-md-12">
                        <?php
                        $error_data = false;
$_SESSION['errorData']['Be careful!']['fff']="Sorry for the inconvenience, this section is under developement and some areas are still being implemented";
                        if (isset($_SESSION['errorData'])) {
                            $error_data = true;

                            if (isset($_SESSION['errorData']['Info'])) { ?>
                                <div class="alert alert-info">
                             <?php } else { ?>
                                <div class="alert alert-danger">
                             <?php }
                             foreach ($_SESSION['errorData'] as $subTitle => $txts) {
                                        print "<strong> $subTitle</strong><br/>";
                                        foreach ($txts as $txt) {
                                            print "<div>$txt</div>";
                                        }
                             }
                             unset($_SESSION['errorData']);
                             ?>
                        </div>
                        <?php } ?>
                    </div>
                </div>




            </div>
	</div>


    </div>
<?php
require "../../htmlib/footer.inc.php";
?>
