<?php

require __DIR__ . "/../../config/bootstrap.php";
redirectOutside();

?>

<?php require "../htmlib/header.inc.php"; ?>

<body class="page-header-fixed page-sidebar-closed-hide-logo page-content-white page-container-bg-solid page-sidebar-fixed">
    <div class="page-wrapper">

        <?php require "../htmlib/top.inc.php"; ?>
        <?php require "../htmlib/menu.inc.php"; ?>

        <!-- BEGIN CONTENT -->
        <div class="page-content-wrapper">
            <!-- BEGIN CONTENT BODY -->
            <div class="page-content" id="body-help">
                <!-- BEGIN PAGE HEADER-->
                <!-- BEGIN PAGE BAR -->
                <div class="page-bar">
                    <ul class="page-breadcrumb">
                        <li>
                            <a href="/home/">Home</a>
                            <i class="fa fa-circle"></i>
                        </li>
                        <li>
                            <span>Help</span>
                            <i class="fa fa-circle"></i>
                        </li>
                        <li>
                            <span>General Information</span>
                        </li>
                    </ul>
                </div>
                <!-- END PAGE BAR -->
                <!-- BEGIN PAGE TITLE-->
                <h1 class="page-title"> What is OpenEBench ?</h1>
                <!-- END PAGE TITLE-->
                <!-- END PAGE HEADER-->

                <div class="note note-info">
                    <h4 class="block">
                        OpenEBench is an infra-structure designed to establish a continuous automated benchmarking system for bioinformatics methods, tools and web services.
                    </h4>
                </div>

                <p><img src="assets/layouts/layout/img/help/Diagram_with_textbox.svg" style="width:80%;max-width:100%;" /></p>

                <p>
                    OpenEBench is being developed so as to cater for the needs of the bioinformatics community, especially software developers who need an objective and quantitative way to inform their decisions as well as the larger community of end-users, in their search for unbiased and up-to-date evaluation of bioinformatics methods.</p>

                <p>
                    The goals of OpenEBench are to:

                    <ul>
                        <li>Provide guidance and software infrastructure for Benchmarking and Techincal monitoring of bioinformatics tools.</li>
                        <li>Engage with existing benchmark initiatives making different communities aware of the platform.</li>
                        <li>Maintain a data warehouse infrastructure to keep record of Benchmarking initiatives.</li>
                        <li>Expose benchmarking and technical monitoring results to Elixir Tools registry.</li>
                        <li>Establish and refine communication protocols with communities and/or infrastructure projects willing to have a unified benchmark infrastructure Coordinate with Elixir.</li>
                        <li>Interoperability Platform to keep FAIR data principles on Benchmarking data warehouse.</li>
                </p>

            </div>
            <!-- END CONTENT BODY -->
        </div>
        <!-- END CONTENT -->

        <?php

        require "../htmlib/footer.inc.php";
        require "../htmlib/js.inc.php";

        ?>