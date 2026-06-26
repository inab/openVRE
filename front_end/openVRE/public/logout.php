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

<style>
    /* --- 1. Global Layout & Reset --- */
    html, body {
        margin: 0 !important;
        padding: 0 !important;
        height: 100%;
        overflow-x: hidden;
    }

    /* --- 2. Header (Top Navigation) --- */
    .page-header.navbar {
        background-color: #1d4175 !important; /* Brand Primary Blue */
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        width: 100% !important;
        height: 72px !important;
        min-height: 60px !important;
	z-index: 10000;
	margin: 0 !important;
        border-bottom: none !important;
    }

    /* Logo Section */
    .page-header.navbar .page-logo {
        background-color: #1d4175 !important;
        width: 235px !important;
        height: 72px !important;
        padding: 0 20px !important;
        display: flex;
        align-items: center;
        float: left !important;
    }

    .page-header.navbar .page-logo img {
        height: 45px !important;
	width: auto !important;
	margin: 0 !important;
    }

    /* User Menu Cleanup: Hide name and initials */
    .page-header.navbar .top-menu .navbar-nav > li.dropdown-user > .dropdown-toggle > .username,
    .page-header.navbar .top-menu .navbar-nav > li.dropdown-user > .dropdown-toggle > img,
    .page-header.navbar .top-menu .navbar-nav > li.dropdown-user > .dropdown-toggle > span {
        display: none !important;
    }

    /* Ensure the dropdown arrow remains visible and centered */
    .page-header.navbar .top-menu .navbar-nav > li.dropdown-user > .dropdown-toggle {
        padding: 22px 10px !important;
    }

    /* --- 3. Sidebar (Left Column) --- */
    .page-container {
        margin-top: 72px !important;
        display: flex;
    }

    .page-sidebar-wrapper {
        width: 235px !important;
        background-color: #233f66 !important; /* Sidebar Navy Blue */
        min-height: calc(100vh - 72px);
    }

    .page-sidebar {
        width: 235px !important;
        background-color: #233f66 !important;
        border: 0 !important;
    }

    /* --- 4. Main Content Area --- */
    .page-content-wrapper {
        flex: 1;
    }

    .page-content {
        background-color: #f3f3f3 !important;
        min-height: calc(100vh - 72px);
        padding: 20px !important;
    }

    /* --- 5. Dropdown Menu Refinement --- */
    /* Hide all default session links */
    .page-header.navbar .top-menu .navbar-nav > li.dropdown-user .dropdown-menu > li {
        display: none !important;
    }
    /* Show only our injected Log in option */
    .page-header.navbar .top-menu .navbar-nav > li.dropdown-user .dropdown-menu li.login-option {
        display: block !important;
    }
</style>

<body class="page-header-fixed page-sidebar-closed-hide-logo page-content-white page-container-bg-solid page-sidebar-fixed">

    <div class="page-header navbar">
        <?php require "htmlib/top.inc.php"; ?>
    </div>

    <script>
        /**
         * Dynamic UI Update:
         * Removes session data from the top menu and replaces dropdown items with a Login link.
         */
        document.addEventListener("DOMContentLoaded", function() {
            // 1. Clean the dropdown menu
            var menu = document.querySelector('.dropdown-user .dropdown-menu');
            if (menu) {
                menu.innerHTML = '<li class="login-option"><a href="/"><i class="fa fa-sign-in"></i> Log in </a></li>';
            }
            
            // 2. Remove any leftover text nodes in the username toggle
            var userToggle = document.querySelector('.dropdown-user .dropdown-toggle');
            if (userToggle) {
                // Keep only the icon (caret/arrow)
                var icon = userToggle.querySelector('i');
                userToggle.innerHTML = '';
                if (icon) userToggle.appendChild(icon);
                else userToggle.innerHTML = '<i class="fa fa-angle-down"></i>';
            }
        });
    </script>

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

    </div>

    <?php 
    require "htmlib/footer.inc.php"; 
    require "htmlib/js.inc.php"; 
    logoutUser(); // Finalize session termination
    ?>
</body>
</html>
