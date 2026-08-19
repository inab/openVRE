<?php

require __DIR__ . "/../../config/bootstrap.php";
redirectOutside();

require "../htmlib/header.inc.php";

$interactiveToolprefix = "/interactive-tool/";
$status = checkStatus($_GET['pid']);

?>

<body class="page-header-fixed page-sidebar-closed-hide-logo page-content-white page-container-bg-solid page-sidebar-fixed">
    <div class="page-wrapper">

        <?php require "../htmlib/top.inc.php"; ?>
        <?php require "../htmlib/menu.inc.php"; ?>

        <div class="page-content-wrapper">
            <div class="page-content">
                <div class="page-bar">
                    <ul class="page-breadcrumb">
                        <li>
                            <a href="home/">Home</a>
                            <i class="fa fa-circle"></i>
                        </li>
                        <li>
                            <a href="workspace/">Interactive Tool</a>
                            <i class="fa fa-circle"></i>
                        </li>
                    </ul>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="row">

                            <div class="col-md-12">

                                <div class="portlet light bordered">

                                    <div class="portlet-title">
                                        <div class="caption">

                                            <?php if ($status["ready"]) { ?>

                                                <i class="fa fa-check-circle font-green"></i>

                                            <?php } else { ?>

                                                <i class="fa fa-spinner fa-spin font-blue"></i>

                                            <?php } ?>

                                            <span class="caption-subject bold uppercase">
                                                <?php echo htmlspecialchars($status["title"]); ?>
                                            </span>

                                        </div>
                                    </div>

                                    <div class="portlet-body text-center">

                                        <h4><?php echo htmlspecialchars($status["message"]); ?></h4>

                                        <?php if ($status["ready"]) { ?>

                                            <a class="btn btn-success btn-lg"
                                                href="<?= htmlspecialchars($status["url"]); ?>"
                                                target="_blank">

                                                <i class="fa fa-external-link"></i>
                                                Open Interactive Session

                                            </a>


                                        <?php } else { ?>

                                            <br>

                                            <div class="progress progress-striped active">
                                                <div class="progress-bar progress-bar-info"
                                                    style="width:100%">
                                                </div>
                                            </div>

                                            <small>
                                                This page refreshes automatically every 3 seconds.
                                            </small>

                                            <?php if ($status["reload"]) { ?>

                                                <script>
                                                    setTimeout(function() {
                                                        location.reload();
                                                    }, 3000);
                                                </script>

                                            <?php } ?>

                                        <?php } ?>

                                    </div>

                                </div>

                            </div>

                    </div>

                    <?php if (isset($status["job"])) {

                        $job = $status["job"];

                    ?>

                        <div class="row">

                            <div class="col-md-12">

                                <h3>Launcher logs</h3>

                                <a class="btn btn-default"
                                    target="_blank"
                                    href="workspace/workspace.php?op=openPlainFileFromPath&fnPath=<?php echo urlencode($job["stdout_file"]); ?>">
                                    Job Standard Output
                                </a>

                                <pre style="max-height:250px;"><?php
                                                                if (file_exists($job["stdout_file"]))
                                                                    echo htmlspecialchars(file_get_contents($job["stdout_file"]));
                                                                ?></pre>



                            </div>

                        </div>

                    <?php } ?>

                </div>
            </div>

        </div>

        <?php
        require "../htmlib/footer.inc.php";
        require "../htmlib/js.inc.php";
        ?>