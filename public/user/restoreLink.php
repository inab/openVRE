<?php

require __DIR__."/../../config/bootstrap.php";
redirectOutside();

$countries = array();
foreach (array_values(iterator_to_array($GLOBALS['countriesCol']->find(array(),array('country'=>1))->sort(array('country'=>1)))) as $v)
	$countries[$v['_id']] = $v['country'];


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
                  <span>User</span>
                  <i class="fa fa-circle"></i>
              </li>
              <li>
                  <span>Restore link</span>
              </li>
            </ul>
        </div>
        <!-- END PAGE BAR -->
        <!-- BEGIN PAGE TITLE-->
        <h1 class="page-title"> Restore link
            <small>for guest users</small>
        </h1>
        <!-- END PAGE TITLE-->
        <!-- END PAGE HEADER-->

        <div class="row">
            <div class="col-md-12">


                <!-- BEGIN PROFILE SIDEBAR -->
                <div class="profile-sidebar">
                    <!-- PORTLET MAIN -->
                    <div class="portlet light profile-sidebar-portlet ">
                        <!-- SIDEBAR USERPIC -->
                        <div class="profile-userpic">
							<img alt="" class="img-responsive<?php echo $dispClassAv1; ?>" src="<?php echo $avatarImg; ?>" />
							<div class="img-circle<?php echo $dispClassAv2; ?>" id="avatar-usr-profile" style="background-color:<?php echo $avatarColors[$bgColorAvatar]; ?>"><?php echo $firstLetterName.$firstLetterSurname; ?></div>
						</div>
                        <!-- END SIDEBAR USERPIC -->
                        <!-- SIDEBAR USER TITLE -->
                        <div class="profile-usertitle">
                            <div class="profile-usertitle-name"> <?php echo $_SESSION['User']['Name'].' '.$_SESSION['User']['Surname']; ?> </div>
							<div class="profile-usertitle-lastlogin"> Temporal and anonymous user</div>
                        </div>
                        <!-- END SIDEBAR USER TITLE -->
                        <!-- SIDEBAR BUTTONS -->
                        <!--<div class="profile-userbuttons">
                            <button type="button" class="btn btn-circle green btn-sm">Follow</button>
                            <button type="button" class="btn btn-circle red btn-sm">Message</button>
                        </div>-->
                        <!-- END SIDEBAR BUTTONS -->
                        <!-- SIDEBAR MENU -->
                        <div class="profile-usermenu">
                            <!--<ul class="nav">
                                <li>
                                    <a href="page_user_profile_1.html">
                                        <i class="icon-home"></i> Overview </a>
                                </li>
                                <li class="active">
                                    <a href="page_user_profile_1_account.html">
                                        <i class="icon-settings"></i> Account Settings </a>
                                </li>
                                <li>
                                    <a href="page_user_profile_1_help.html">
                                        <i class="icon-info"></i> Help </a>
                                </li>
                            </ul>-->
                        </div>
                        <!-- END MENU -->
                    </div>
                    <!-- END PORTLET MAIN -->

                </div>
                <!-- END BEGIN PROFILE SIDEBAR -->
                <!-- BEGIN PROFILE CONTENT -->
                <div class="profile-content">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="portlet light ">
                                <div class="portlet-title tabbable-line">
                                    <div class="caption caption-md">
                                        <i class="icon-globe theme-font hide"></i>
                                        <span class="caption-subject font-blue-madison bold uppercase">Restore Link</span>
                                    </div>
                                </div>
                                <div class="portlet-body">
                                    <div class="tab-content">

                                        <!-- PERSONAL INFO TAB -->
                                        <div class="tab-pane active" id="tab_1_1">
                                            <p>Using the following link, you'll be able to restore your anonymous session. Please, copy and save it if you are interested in recovering your data even after the current session ends. Pasting back the URL in your web browser will allow you to keep working on your data up to 10 days after your first access (check the <a href="javascript:openTermsOfUse();" >Terms of Use</a> for more details).</p>
													<div class="input-group">
														<input id="mt-target-1" type="text" class="form-control" value="<?php echo $GLOBALS['URL']."/applib/loginAnonymous.php?id=".$_SESSION['User']['_id']; ?>" readonly style="background:#fff;" >
															<span class="input-group-btn">
															<button class="btn green mt-clipboard" data-clipboard-action="copy" data-clipboard-target="#mt-target-1" type="button"><i class="fa fa-copy"></i> Copy to clipboard</button>
    														</span>
                                                    </div>
                                            <br/>
                                            <p>For being granted a permanent workspace, abandon the <i>guest</i> mode and simply <a target="_blank" href="applib/loginToken.php">sign in</a> into VRE!</p>
                                            
                                        </div>
                                        <!-- END PERSONAL INFO TAB -->


                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- END PROFILE CONTENT -->
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
