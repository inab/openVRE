<?php
require __DIR__ . "/../config/bootstrap.php";

/**
 * Professional Environment Setup
 * Disabling errors to ensure a clean logout interface.
 */
ini_set('display_errors', 0);
error_reporting(0);

require "htmlib/header.inc.php";
?>

<body class="page-header-fixed page-sidebar-closed-hide-logo page-content-white page-container-bg-solid page-sidebar-fixed">
    <div class="page-wrapper">

        <?php require "htmlib/top.inc.php"; ?>

        <div class="clearfix"> </div>

        <div class="page-container">
            <div class="page-sidebar-wrapper">
                <div class="page-sidebar navbar-collapse collapse">
                    <ul class="page-sidebar-menu"></ul>
                </div>
            </div>

            <div class="page-content-wrapper">
                <div class="page-content">
                    <div class="row" style="margin-top: 100px;">
                        <div class="col-md-offset-2 col-md-8">
                            <div class="portlet light bordered" style="text-align: center; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                                <div class="portlet-body">
                                    <div class="well" style="background: #fff; border: none; padding: 40px; margin: 0;">
                                        <i class="fa fa-sign-out" style="font-size: 60px; color: #e7505a;"></i>
                                        <h1 class="page-title" style="font-size: 28px; font-weight: 600; color: #333; margin-top: 20px;"> Session Ended </h1>
                                        <p style="color: #666; font-size: 16px;"> You have successfully logged out of the <strong>VRE</strong> platform. </p>
                                        <br>
                                        <a href="/" class="btn green-haze btn-lg">
                                            <i class="fa fa-refresh"></i> Log in again
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <script>
            /**
             * Dynamic UI Update:
             * Removes session data from the top menu and replaces dropdown items with a Login link.
             */
            document.addEventListener("DOMContentLoaded", function() {
                var menu = document.querySelector('.dropdown-user .dropdown-menu');
                if (menu) {
                    menu.innerHTML = '<li class="login-option"><a href="/"><i class="fa fa-sign-in"></i> Log in </a></li>';
                }

                var userToggle = document.querySelector('.dropdown-user .dropdown-toggle');
                if (userToggle) {
                    var icon = userToggle.querySelector('i');
                    userToggle.innerHTML = '';
                    if (icon) userToggle.appendChild(icon);
                    else userToggle.innerHTML = '<i class="fa fa-angle-down"></i>';
                }
            });
        </script>

        <?php
        require "htmlib/footer.inc.php";
        require "htmlib/js.inc.php";
        logoutUser();
        ?>
</body>
</html>
