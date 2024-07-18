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
                            <span>Exposome Data Analysis Toolbox</span>
                        </li>
                    </ul>
                </div>
                <!-- END PAGE BAR -->
                <!-- BEGIN PAGE TITLE-->
                <h1 class="page-title">Exposome Data Analysis Toolbox</h1>
                <!-- END PAGE TITLE-->
                <!-- END PAGE HEADER-->

                <div class="note note-info">
                    <h4 class="block">
                        Welcome to the Exposome Data Analysis Toolbox, your comprehensive solution for analyzing exposome data. Here's what you need to know about the platform:

                        <ul>
                            <li><strong style="color: green;">Workflows:</strong> These tools are workflows that take specific input data, process it using a series of operations, and generate an output. Think of them as customizable data processing pipelines tailored to your research needs.</li>

                            <li><strong style="color: purple;">R Libraries:</strong> these tools represent R libraries. When you initiate an interactive session with an R library, it opens an R environment with the specified library installed within your private workspace. This provides you with a flexible and customizable R programming environment for data analysis.</li>

                            <li><strong style="color: pink;">DataSHIELD Libraries:</strong> When you access the DataSHIELD libraries, they redirect you to the central JupiterHub of the project. Here, you gain access to the project's data for analysis within a secure and controlled environment, ensuring the confidentiality of the shared data. Before running these tools, please email <a href="molgenis-support@umcg.nl">molgenis-support@umcg.nl</a> to gain access to the analysis server. </li>
                        </ul>
                    </h4>
                </div>

                <div class="note note-info" style="background-color: #f0f8ff;">
                    <h4 class="block">
                        Our platform is designed to facilitate seamless collaboration and efficient analysis within a secure ecosystem. We value your feedback and continuously work on enhancing the platform to meet your evolving research requirements.
                    </h4>
                </div>

                <div class="note note-info" style="background-color: #f0f8ff;">
                    <h4 class="block">
                        <strong>Please Note:</strong> The Exposome Data Analysis Toolbox is already developed and operational. While it's functional, we continue to refine and enhance its features. Your input is invaluable in shaping the future of our platform. We encourage you to explore the available tools and functionalities. Should you have any suggestions, encounter issues, or require assistance, please do not hesitate to reach out. Together, we can advance exposome research and contribute to scientific discovery.
                    </h4>
                </div>
            </div>
            <!-- END CONTENT BODY -->
        </div>
        <!-- END CONTENT -->

        <?php

        require "../htmlib/footer.inc.php";
        require "../htmlib/js.inc.php";

        ?>

