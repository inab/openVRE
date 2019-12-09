<?php

require __DIR__."/../../config/bootstrap.php";
if(!allowedRoles($_SESSION['User']['Type'], $GLOBALS['NO_GUEST'])) redirectInside(); 
redirectOutside();

$firstLetterName = substr($_SESSION['User']['Name'], 0, 1);
$firstLetterSurname = substr($_SESSION['User']['Surname'], 0, 1);

$avatarColors = array('#0f7e8c','#a3d86d','#9113ff','#edc642','#2ac5a3','#ffb858','#955216');
$bgColorAvatar = array_rand($avatarColors,1);

$name = $_SESSION['User']['Name'];
$surname = $_SESSION['User']['Surname'];
$email = $_SESSION['User']['_id'];

$filename = glob('../assets/avatars/'.$_SESSION['User']['id'].'.*');
$avatarImg = $filename[0];
if (file_exists($avatarImg)){
	$avatarExists = 1;
} else {
    $avatarExists = 0;
}


logoutUser();

?>

<?php require "../htmlib/header.inc.php"; ?>

<body class="">
        <div class="page-lock">
            <div class="page-logo">
                    <img src="assets/layouts/layout/img/logo-big.png" alt="logo" />
            </div>
            <div class="page-body">
                <div class="lock-head"> Locked </div>
                <div class="lock-body">
                    <div class="pull-left lock-avatar-block">
						<?php if($avatarExists == 1) { ?>
						<img src="<?php echo $avatarImg; ?>" class="lock-avatar">
						<?php }else{ ?>
						<div class="lock-avatar" id="avatar-no-picture-lockscreen" style="background-color:<?php echo $avatarColors[$bgColorAvatar]; ?>"><?php echo $firstLetterName.$firstLetterSurname; ?></div>
						<?php } ?>
					</div>
                    <form class="lock-form pull-left" action="home/redirect.php" method="post">
					<h4><?php echo $name.' '.$surname; ?> </h4>
						<div class="alert alert-danger display-hide" id="err-lock-pwd">
                    		<button class="close" data-close="alert"></button>
                    		<span> Wrong password. </span>
                		</div>
                        <div class="form-group">
							<input class="form-control form-control-solid placeholder-no-fix" type="password" autocomplete="off" placeholder="Password" name="password" /> 
							<input type="hidden" name="usermail" value="<?php echo $email; ?>" /> 
							<input type="hidden" id="base-url"     value="<?php echo $GLOBALS['BASEURL']; ?>"/>

						</div>
                        <div class="form-actions">
                            <button id="login-button" type="submit" class="btn green uppercase">Login</button>
                        </div>
                    </form>
                </div>
                <div class="lock-bottom">
					<a href="<?php echo $GLOBAL['BASEURL']; ?>">Not <?php echo $name.' '.$surname; ?>?</a>
                </div>
            </div>
			<?php require "../htmlib/footer-login.inc.php"; ?>
        </div>

<?php

require "../htmlib/js.inc.php";

?>

