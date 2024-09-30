<?php

$firstLetterName =  substr($_SESSION['User']['Name'], 0, 1);
$firstLetterSurname =  substr($_SESSION['User']['Surname'], 0, 1);

$avatarColors = array('#0f7e8c', '#a3d86d', '#9113ff', '#edc642', '#2ac5a3', '#ffb858', '#955216');
$bgColorAvatar = array_rand($avatarColors, 1);

$filename = glob('../assets/avatars/' . $_SESSION['User']['id'] . '.*');
$avatarImg = (isset($filename[0])?$filename[0]:false);
if (file_exists($avatarImg)) {
    $avatarExists = 1;
    $dispClassAv1 = '';
    $dispClassAv2 = ' display-hide';
} else {
    $avatarExists = 0;
    $dispClassAv2 = '';
    $dispClassAv1 = ' display-hide';
}

?>

<!-- BEGIN HEADER -->
<div class="page-header navbar navbar-fixed-top">
    <!-- BEGIN HEADER INNER -->
    <div class="page-header-inner ">
        <!-- BEGIN LOGO -->
        <div class="page-logo">
            <a href="workspace/">
                <img src="assets/layouts/layout/img/disc4all-removebg.png" alt="logo" class="logo-default" style="width:35%"/>
                <img src="assets/layouts/layout/img/VRE_white.svg" alt="logo" class="logo-default" style="width:45%"/>
            </a>
            <div class="menu-toggler sidebar-toggler">
                <span></span>
            </div>
        </div>
        <!-- END LOGO -->
        <!-- BEGIN RESPONSIVE MENU TOGGLER -->
        <a href="javascript:;" class="menu-toggler responsive-toggler" data-toggle="collapse" data-target=".navbar-collapse">
	    <span></span>
        </a>
        <!-- END RESPONSIVE MENU TOGGLER -->
        <!-- BEGIN TOP NAVIGATION MENU -->
        <div class="top-menu">
            <div class="display-hide" id="session-expire-top"> <i class="glyphicon glyphicon-time"></i> Your session will expire in <span>60</span> seconds </div>

            <?php if (allowedRoles($_SESSION['User']['Type'], $GLOBALS['NO_GUEST'])) { ?>
                <ul class="nav navbar-nav pull-right">

                    <!-- BEGIN USER LOGIN DROPDOWN -->
                    <!-- DOC: Apply "dropdown-dark" class after below "dropdown-extended" to change the dropdown styte -->
                    <li class="dropdown dropdown-user">
                        <a href="javascript:;" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" data-close-others="true">
                            <img alt="" class="img-circle<?php echo $dispClassAv1; ?>" id="avatar-with-picture" src="<?php echo $avatarImg; ?>" />
                            <div class="img-circle<?php echo $dispClassAv2; ?>" id="avatar-no-picture" style="background-color:<?php echo $avatarColors[$bgColorAvatar]; ?>"><?php echo $firstLetterName . $firstLetterSurname; ?></div>
                            <span class="username username-hide-on-mobile"><?php echo $_SESSION['User']['Name'] ?></span>
                            <i class="fa fa-angle-down"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-default">

                            <li>
                                <a href="user/usrProfile.php">
                                    <i class="glyphicon glyphicon-user"></i> My Profile </a>
                            </li>
                            <?php if ((allowedRoles($_SESSION['User']['Type'], $GLOBALS['ADMIN'])) && (!allowedRoles($_SESSION['User']['Type'], $GLOBALS['TOOLDEV']))) { ?>
                                <li>
                                    <a href="admin/dashboard.php">
                                        <i class="glyphicon glyphicon-dashboard"></i> Dashboard </a>
                                </li>
                            <?php } ?>
                            <li class="divider"> </li>
                            <li>
                                <a id="logout-button" href="javascript:;">
                                    <i class="fa fa-power-off" style="font-size:16px;"></i> Log Out </a>
                            </li>
                        </ul>
                    </li>
                    <!-- END USER LOGIN DROPDOWN -->
                </ul>
            <?php } else { ?>

                <ul class="nav navbar-nav pull-right">
                    <li class="dropdown dropdown-user">
                        <a href="<?php echo $GLOBALS['URL_login']; ?>" class="dropdown-toggle" style="padding-right:15px;">
                            <i class="fa fa-sign-in" style="font-size: 23px;position: relative;top: 3px;color: white;"></i>
                            <span class="username username-hide-on-mobile" style="color: white;">Log In</span>
                        </a>
                    </li>
                </ul>
            <?php } ?>
        </div>
        <!-- END TOP NAVIGATION MENU -->
    </div>
    <!-- END HEADER INNER -->
</div>
<!-- END HEADER -->
