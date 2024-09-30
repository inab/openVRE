<?php

require __DIR__."/../../config/bootstrap.php";

logoutUser();

if(!isset($_GET['q'])) redirect($GLOBALS['URL']);

?>

<?php require "../htmlib/header.inc.php"; ?>

<body class=" login">
        <!-- BEGIN LOGO -->
        <div class="logo">
            <a href="<?php echo $GLOBALS['URL']; ?>">
                <img src="assets/layouts/layout/img/logo-big.png" alt="" /> </a>
        </div>
        <!-- END LOGO -->
        <!-- BEGIN LOGIN -->
        <div class="content">
            <!-- BEGIN LOGIN FORM -->
						<form class="login-form" action="javascript:void(0);" method="post">
							<input type="hidden" id="base-url"     value="<?php echo $GLOBALS['BASEURL']; ?>"/>

                <h3 class="form-title font-green">Reset your Password</h3>
				<div class="alert alert-danger display-hide" id="err-msg-login">
                    <button class="close" data-close="alert"></button>
                    <span> Email incorrect. </span>
				</div>
				<div class="alert alert-danger display-hide" id="err-msg-link">
                    <button class="close" data-close="alert"></button>
                    <span> Your link is probably broken or the Email provided is incorrect. </span>
                </div>
                <div class="alert alert-success display-hide" id="succ-msg-pwdchg">
                    <button class="close" data-close="alert"></button>
                    <span> Your password has been updated, please click below to login. </span>
                </div>
                <div class="form-group fg-chgpwd" >
                    <!--ie8, ie9 does not support html5 placeholder, so we just show field title for that-->
                    <label class="control-label visible-ie8 visible-ie9">Email</label>
                    <input class="form-control form-control-solid placeholder-no-fix" type="text" autocomplete="off" placeholder="Email" name="usermail" /> </div>
                <div class="form-group fg-chgpwd">
                    <label class="control-label visible-ie8 visible-ie9">Password</label>
                    <input class="form-control form-control-solid placeholder-no-fix" id="register_password" type="password" autocomplete="off" placeholder="New Password" name="pass1" /> </div>
				<div class="form-group fg-chgpwd">
                    <label class="control-label visible-ie8 visible-ie9">Re-type Your Password</label>
					<input class="form-control form-control-solid placeholder-no-fix" type="password" autocomplete="off" placeholder="Re-type Your New Password" name="pass2" /> </div>
					<input type="hidden" name="q" value="<?php if(isset($_GET["q"])) echo $_GET["q"]; ?>" />
				<div class="form-actions fg-chgpwd" style="border-bottom:none;">
                    <button type="submit" id="login-button"  class="btn green uppercase">Submit</button>
				</div>
				<div class="create-account login-guest display-hide">
                    <p>
					<a href="<?php echo $GLOBALS['URL']; ?>" class="uppercase">Go to Login page</a>
                    </p>
                </div>

            </form>
            <!-- END LOGIN FORM -->
        </div>

<?php

require "../htmlib/footer-login.inc.php";
require "../htmlib/js.inc.php";

?>

