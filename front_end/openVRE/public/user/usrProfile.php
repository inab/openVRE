<?php

require __DIR__ . "/../../config/bootstrap.php";
#if (!allowedRoles($_SESSION['User']['Type'], $GLOBALS['NO_GUEST'])) redirectInside();
redirectOutside();

$countries = array();
$ops = [ 'projection' => [ 'country' => 1 ], 'sort' => [ 'country' => 1 ] ];
foreach (array_values(iterator_to_array($GLOBALS['countriesCol']->find(array(), $ops))) as $v)
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
                            <span>My Profile</span>
                        </li>
                    </ul>
                </div>
                <!-- END PAGE BAR -->
                <!-- BEGIN PAGE TITLE-->
                <h1 class="page-title"> User Profile
                    <small>user account page</small>
                </h1>
                <!-- END PAGE TITLE-->
                <!-- END PAGE HEADER-->

                <?php if (!checkTermsOfUse()) { ?>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="alert alert-warning">
                                <strong>WARNING:</strong> You must accept the terms of use before start using the platform.
                            </div>
                        </div>
                    </div>
                <?php } ?>

                <div class="row">
                    <div class="col-md-12">



                        <!-- BEGIN PROFILE SIDEBAR -->
                        <div class="profile-sidebar">
                            <!-- PORTLET MAIN -->
                            <div class="portlet light profile-sidebar-portlet ">
                                <!-- SIDEBAR USERPIC -->
                                <div class="profile-userpic">
                                    <img alt="" class="img-responsive<?php echo $dispClassAv1; ?>" src="<?php echo $avatarImg; ?>" />
                                    <div class="img-circle<?php echo $dispClassAv2; ?>" id="avatar-usr-profile" style="background-color:<?php echo $avatarColors[$bgColorAvatar]; ?>"><?php echo $firstLetterName . $firstLetterSurname; ?></div>
                                </div>
                                <!-- END SIDEBAR USERPIC -->
                                <!-- SIDEBAR USER TITLE -->
                                <div class="profile-usertitle">
                                    <div class="profile-usertitle-name"> <?php echo $_SESSION['User']['Name'] . ' ' . $_SESSION['User']['Surname']; ?> </div>
                                    <div class="profile-usertitle-job"> <?php echo $_SESSION['User']['Inst'] ?> </div>
                                    <?php if (isset($_SESSION['lastUserLogin'])) { ?>
                                        <div class="profile-usertitle-lastlogin"> Last login: <strong><?php echo returnHumanDateDashboard($_SESSION['lastUserLogin']); ?></strong> </div>
                                    <?php } ?>
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
                                                <span class="caption-subject font-blue-madison bold uppercase">Profile Account</span>
                                            </div>
                                            <ul class="nav nav-tabs">
                                                <li class="active">
                                                    <a href="#tab_1_1" data-toggle="tab">Personal Info</a>
                                                </li>
                                                <!--<li>
                                            <a href="#tab_1_2" data-toggle="tab">Change Avatar</a>
                                        </li>
                                        <li>
                                            <a href="#tab_1_3" data-toggle="tab">Change Password</a>
                                        </li>-->
                                                <li>
                                                    <a href="#tab_1_4" data-toggle="tab">Keys</a>
                                                </li>
                                            </ul>
                                        </div>
                                        <div class="portlet-body">
                                            <div class="tab-content">
                                                <!-- PERSONAL INFO TAB -->
                                                <div class="tab-pane active" id="tab_1_1">

                                                    <?php if (!isset($_SESSION['lastUserLogin'])) { ?>
                                                        <p>As you have signed up the VRE from <?php echo $_SESSION['User']['AuthProvider']; ?>, you should complete some fields of your profile in this form.</p>
                                                    <?php } ?>
                                                    <div class="alert alert-danger display-hide" id="err-chg-prf">
                                                        Something was wrong. Please try again.
                                                    </div>
                                                    <div class="alert alert-info display-hide" id="succ-chg-prf">
                                                        Your personal info has been updated. Use the left-hand menu to start navigating!
                                                    </div>
                                                    <form role="form" action="javascript:;" id="form-change-profile">
                                                        <input type="hidden" id="base-url" value="<?php echo $GLOBALS['BASEURL']; ?>" />
                                                        <?php if (!isset($_SESSION['lastUserLogin'])) { ?>
                                                            <input type="hidden" id="is-first-time" value="1" />
                                                        <?php } ?>
                                                        <div class="form-group">
                                                            <label class="control-label">Email</label>
                                                            <input type="text" value="<?php echo $_SESSION['User']['Email']; ?>" class="form-control" readonly />
                                                        </div>
                                                        <div class="form-group">
                                                            <label class="control-label">Name</label>
                                                            <input name="Name" type="text" value="<?php echo $_SESSION['User']['Name']; ?>" class="form-control" id="name-usr-profile" />
                                                        </div>
                                                        <div class="form-group">
                                                            <label class="control-label">Surname</label>
                                                            <input name="Surname" type="text" value="<?php echo $_SESSION['User']['Surname']; ?>" class="form-control" id="surname-usr-profile" /> </div>
                                                        <div class="form-group">
                                                            <label class="control-label">Institution</label>
                                                            <input name="Inst" type="text" value="<?php echo $_SESSION['User']['Inst']; ?>" class="form-control" /> </div>
                                                        <div class="form-group">
                                                            <label class="control-label">Country</label>
                                                            <select name="Country" class="form-control">
                                                                <option value=""></option>
                                                                <?php
                                                                $selCountry = '';
                                                                foreach ($countries as $key => $value) :
                                                                    if ($_SESSION['User']['Country'] == $key) $selCountry = ' selected';
                                                                    else $selCountry = '';
                                                                    echo '<option value="' . $key . '"' . $selCountry . '>' . $value . '</option>';
                                                                endforeach;
                                                                ?>
                                                            </select>
                                                        </div>
                                                        <?php if (!allowedRoles($_SESSION['User']['Type'], $GLOBALS['TOOLDEV']) && (checkTermsOfUse())) { ?>
                                                            <div class="form-group margin-top-30">
                                                                <label class="control-label">You are a standard user. Do you want to bring your own tool?
                                                                    <a href="https://eucanshare.bsc.es/dataportal/?page_id=629" target="_blank"><i class="icon-question tooltips" data-container="body" data-placement="right" data-original-title="Click here to read more about how to bring your own tool."></i></a>
                                                                </label>
                                                                <br /><br />
                                                                <a href="<?php echo $GLOBALS['BASEURL']; ?>helpdesk/?sel=tooldev" class="btn green">Become tool developer</a></div>
                                                        <?php } ?>
                                                        <div class="form-group margin-top-30">
                                                            <label class="mt-checkbox mt-checkbox-outline" style="margin-bottom:0;"> Please, accept <a href="javascript:openTermsOfUse();">terms of use</a>
                                                                <input type="checkbox" value="1" name="terms" id="terms" <?php if (checkTermsOfUse()) echo 'checked readonly'; ?> />
                                                                <span></span>
                                                            </label></div>


                                                        <div class="margin-top-10">
                                                            <?php if (isset($_SESSION['lastUserLogin'])) { ?>
                                                                <button type="submit" id="submit-changes" class="btn green">Save Changes</button>
                                                            <?php } else { ?>
                                                                <button type="submit" id="submit-changes" class="btn green">Save changes and go to Homepage</button>
                                                            <?php } ?>
                                                            <button type="reset" class="btn default">Reset Form</button>
                                                        </div>
                                                    </form>
                                                </div>
                                                <!-- END PERSONAL INFO TAB -->
                                                <!-- CHANGE AVATAR TAB -->
                                                <div class="tab-pane" id="tab_1_2">
                                                    <p> Select an image from your computer. Max size: 1MB.
                                                        <?php if ($avatarExists == 1) { ?> If you want to remove your profile picture, please click <i>Remove</i> button and then <i>Submit</i>. <?php } ?>
                                                    </p>
                                                    <div class="alert alert-danger display-hide" id="err-chg-av">
                                                        Error auploading avatar.
                                                    </div>
                                                    <div class="alert alert-info display-hide" id="succ-chg-av">
                                                        Image successfully uploaded.
                                                    </div>

                                                    <form action="javascript:;" role="form" id="form-chg-img" enctype="multipart/form-data">
                                                        <div class="form-group">
                                                            <div class="fileinput fileinput-<?php if ($avatarExists == 1) echo 'exists';
                                                                                            else echo 'new'; ?>" data-provides="fileinput">
                                                                <div class="fileinput-new thumbnail" style="width: 200px; height: 150px;">
                                                                    <img src="https://www.placehold.it/200x150/EFEFEF/AAAAAA&amp;text=select+image" alt="" />
                                                                </div>
                                                                <div class="fileinput-preview fileinput-exists thumbnail" style="max-width: 150px; height: auto;">
                                                                    <?php if ($avatarExists == 1) { ?>
                                                                        <img src="<?php echo $avatarImg; ?>" alt="" />
                                                                    <?php } ?>
                                                                </div>
                                                                <div>
                                                                    <span class="btn default btn-file">
                                                                        <span class="fileinput-new"> Select image </span>
                                                                        <span class="fileinput-exists"> Change </span>
                                                                        <input type="file" name="imageprofile" id="imageprofile"> </span>
                                                                    <a href="javascript:;" class="btn default fileinput-exists" data-dismiss="fileinput"> Remove </a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="margin-top-10">
                                                            <a href="javascript:;" class="btn green" id="submit-img"> Submit </a>
                                                        </div>
                                                    </form>
                                                </div>
                                                <!-- END CHANGE AVATAR TAB -->
                                                <!-- CHANGE PASSWORD TAB -->
                                                <div class="tab-pane" id="tab_1_3">
                                                    <div class="alert alert-danger display-hide" id="err-chg-pwd">
                                                        Something was wrong. Please try again.
                                                    </div>
                                                    <div class="alert alert-danger display-hide" id="err-chg-pwd2">
                                                        Current password incorrect. Please try again.
                                                    </div>
                                                    <div class="alert alert-info display-hide" id="succ-chg-pwd">
                                                        Your password has been changed.
                                                    </div>
                                                    <form action="javascript:;" id="form-change-pwd">
                                                        <div class="form-group">
                                                            <label class="control-label">Current Password</label>
                                                            <input type="password" name="oldpass" class="form-control" /> </div>
                                                        <div class="form-group">
                                                            <label class="control-label">New Password</label>
                                                            <input type="password" name="pass1" class="form-control" id="new-password" /> </div>
                                                        <div class="form-group">
                                                            <label class="control-label">Re-type New Password</label>
                                                            <input type="password" name="pass2" class="form-control" /> </div>
                                                        <div class="margin-top-10">
                                                            <button type="submit" id="submit-pwd" class="btn green">Change Password</button>
                                                            <button type="reset" class="btn default">Reset Form</button>
                                                        </div>
                                                    </form>
                                                </div>
                                                <!-- END CHANGE PASSWORD TAB -->
                                                <!-- API KEYS TAB -->
                                                <div class="tab-pane" id="tab_1_4">
                                                    <?php
                                                    if (isset($_SESSION['errorData'])) { ?>
                                                        <div class="alert alert-warning">
                                                            <?php foreach ($_SESSION['errorData'] as $subTitle => $txts) {
                                                                print "$subTitle<br/>";
                                                                foreach ($txts as $txt) {
                                                                    print "<div style=\"margin-left:20px;\">$txt</div>";
                                                                }
                                                            }
                                                            unset($_SESSION['errorData']);
                                                            ?>
                                                        </div>

                                                    <?php } ?>
                                                    <p>These are your user credentials required for authenticating to any of the platform services. VRE manage them on your behalf for accessing to your data.</p>
                                                    <div class="form-group mt-clipboard-container">
                                                        <label class="control-label">Access Token <i class="icon-question tooltips" data-container="body" data-html="true" data-placement="right" data-original-title="<p align='left' style='margin:0'>Bearer token used to authenticate your access to any platform service.</p>"></i></label>
                                                        <div class="input-group">
                                                            <input id="mt-target-1" type="text" class="form-control" value="<?php echo $_SESSION['User']['Token']['access_token']; ?>" readonly style="background:#fff;">
                                                            <span class="input-group-btn">
                                                                <button class="btn green mt-clipboard" data-clipboard-action="copy" data-clipboard-target="#mt-target-1" type="button"><i class="fa fa-copy"></i> Copy to clipboard</button>
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <input id="exp-token" type="hidden" value="<?php echo $_SESSION['User']['Token']['expires']; ?>">
                                                    <input id="curr-time" type="hidden" value="<?php echo time(); ?>">
<?php
                                                    $ed = date('h:i:s A (jS \of F Y)', $_SESSION['User']['Token']['expires']);
                                                    /*$edd = date('h:i:s A (jS \of F Y)');
                                                           print ">>>>>>>>> INI : $edd <br/>";
							print ">>>>>>>>> EXP : $ed <br/>";*/
                                                    $expiresIn = $_SESSION['User']['Token']['expires'] - time();
                                                    if ($expiresIn > 0)
                                                        $expDate = "Token will expire in " . intval($expiresIn / 60) . " minutes, at $ed";
                                                    else
                                                        $expDate = "This Token is expired...  It needs a refresh!";
                                                    ?>

                                                    <div class="form-group">
                                                        <label class="control-label">Expiration date <i class="icon-question tooltips" data-container="body" data-html="true" data-placement="right" data-original-title="<p align='left' style='margin:0'>Access token expiration time. Its lifespan is short, so VRE refreshs it just before accessing the requested resource, if needed.</p>"></i></label>
                                                        <div class="input-group">
                                                            <input id="token-exp-date" type="text" value="" class="form-control" readonly style="background:#fff;" />
                                                            <span class="input-group-btn">
                                                                <a href="applib/refreshToken.php" class="btn green button"><i class="fa fa-refresh"></i> Refresh token</a>
                                                            </span>
                                                        </div>
                                                    </div>


                                                    <div class="form-group mt-clipboard-container">
                                                        <label class="control-label">Refresh Token <i class="icon-question tooltips" data-container="body" data-html="true" data-placement="right" data-original-title="<p align='left' style='margin:0'>Token used to refresh an expired access token. It is revoked when used, so access tokens are issued together with a new refresh token</p>"></i></label>
                                                        <div class="input-group">
                                                            <input id="mt-target-2" type="text" class="form-control" value="<?php echo $_SESSION['User']['Token']['refresh_token']; ?>" readonly style="background:#fff;">
                                                            <span class="input-group-btn">
                                                                <button class="btn green mt-clipboard" data-clipboard-action="copy" data-clipboard-target="#mt-target-2" type="button"><i class="fa fa-copy"></i> Copy to clipboard </button>
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <input id="exp-refrtoken" type="hidden" value="<?php echo $_SESSION['User']['Token']['expires'] + $_SESSION['User']['Token']['refresh_expires_in']; ?>">
                                                    <?php
                                                    /*$expiresDate = $_SESSION['User']['Token']['expires'] + $_SESSION['User']['Token']['refresh_expires_in'];
     																							$ed = date('h:i:s A (jS \of F Y)',$expiresDate);
																									$expiresIn = $expiresDate - time();
																									if ($expiresIn > 0 )
																											$expDate = "Token will expire in ". intval($expiresIn/60) ." minutes, at $ed";
																									else
																											$expDate = "This Token is expired...  It needs a new login!";*/
                                                    ?>
                                                    <div class="form-group">
                                                        <label class="control-label">Expiration date <i class="icon-question tooltips" data-container="body" data-html="true" data-placement="right" data-original-title="<p align='left' style='margin:0'>Refresh token expiration time. A new SSO session is required to obtain a new pair of tokens.</p>"></i></label>
                                                        <input id="refrtoken-exp-date" type="text" value="" class="form-control" readonly style="background:#fff;" /> </div>


                                                    <div class="form-group">
                                                        <label class="control-label">Token User information<i class="icon-question tooltips" data-container="body" data-html="true" data-placement="right" data-original-title="<p align='left' style='margin:0'>Information returned by Oauth2 provider when the user token is beared</p>"></i></label>
                                                        <br />
                                                        <pre><?php echo json_encode($_SESSION['User']['TokenInfo'], JSON_PRETTY_PRINT); ?></pre>
						    </div>


                                                    <div class="form-group mt-clipboard-container">
						    <input id="vault-exp-token" type="hidden" value="<?php echo $_SESSION['User']['Vault']['expires_in']; ?>">
                                                    <input id="curr-time" type="hidden" value="<?php echo time(); ?>">
<?php
						    //$ed = date('h:i:s A (jS \of F Y)', $_SESSION['User']['Vault']['expires_in']);
						    $expirationTimestamp = intval($_SESSION['User']['Vault']['expires_in']);
						    $ed = gmdate('h:i:s A (jS \of F Y)', $expirationTimestamp);
						    $expiresIn = $expirationTimestamp - time();
						    if ($expiresIn > 0)
                                                        $vexpDate = "Token will expire in " . intval($expiresIn / 60) . " minutes, at $ed";
                                                    else
                                                        $vexpDate = "This Token is expired...  It needs a refresh!";
                                                    ?>

                                                    <div class="form-group">
                                                        <label class="control-label">Expiration Vault date <i class="icon-question tooltips" data-container="body" data-html="true" data-placement="right" data-original-title="<p align='left' style='margin:0'>Vault token expiration time. Its lifespan is short, so VRE refreshs it just before accessing the requested resource, if needed.</p>"></i></label>
                                                        <div class="input-group">
                                                            <input id="token-exp-date" type="text" value="<?php echo $vexpDate; ?>" class="form-control" readonly style="background:#fff;" />
                                                            <span class="input-group-btn">
                                                                <a href="applib/refreshVaultToken.php" class="btn green button"><i class="fa fa-refresh"></i> Renew token</a>
                                                            </span>
                                                        </div>
                                                    </div>
                                                    </div>

						<br/>
						<br/>
						<span class="caption-subject font-blue-madison bold uppercase">Linked Accounts</span>

						<!-- HPC ACCOUNT -->
						<hr>
						<img src="https://cdn-icons-png.flaticon.com/512/5225/5225347.png"/ style="float: right; height: 50px; margin: 0 50px;">
						<h4>HPC resources access (via SSH)</h4>

						<div style="padding-left: 15px;border-left: 2px solid lightgray;">
					<?php
					if (! isset($_SESSION['User']['linked_accounts']['mn'])){?>
						<p>
						  <span style="color: #666;font-weight: bold;">
						  Do you have an account to an HPC facility?
						  </span>
						  Link it and you'll be able to launch jobs there. Data will be transferred via SSH, from/to your HPC home directory. 
						</p>
						<div class="row" style="margin-left:30px;">
				    		    <div class="col-md-6">
						     <a href="<?php echo $GLOBALS['BASEURL']; ?>user/linkedAccount.php?account=SSH&action=new" class="btn green"><i class="fa fa-plus"></i> &nbsp; Link your account</a>
						    </div>
				    		    <div class="col-md-6">
							<a href="https://bsc.es/es/marenostrum/marenostrum" target="_blank" ><i class="fa fa-sign-in"></i> Learn more on Marenostrum 4</a>
						    </div>
						</div>

					<?php } else {			
					      ?>
                                                    <div class="form-group">
							<label class="control-label">HPC system Username</label>
							<br />
						    	<input type="text" value="<?php echo $_SESSION['User']['linked_accounts']['mn']['hpc_username'] ?>" class="form-control" readonly style="background:#fff;"  />
						   </div>

                                                    <div class="form-group">
							<label class="control-label">SSH Public Key</label>
							<br />
						    	<input type="text" value="<?php echo $_SESSION['User']['linked_accounts']['mn']['hpc_pub_key'] ?>" class="form-control" readonly style="background:#fff;"  />
						   </div>

                                                    <div class="form-group">
							<label class="control-label">SSH Private key</label>

							<i onclick="this.classList.toggle('fa-eye-slash');x=document.getElementById('priv_key');if (x.style.display === 'none') {x.style.display = 'block';} else {x.style.display = 'none';}" class="fa fa-eye font-green" style="margin:10px; font-size:18px"></i>
							<br />
							<div style="height:150px;display:none;" id="ssh_priv_key">
							<pre ><?php echo $_SESSION['User']['linked_accounts']['SSH']['hpc_priv_key'] ?></pre>
							</div>
						   </div>

                                                    <div class="form-group">
							<label class="control-label">Creation Time</label>
							<br />
						    	<span class="form-control" readonly style="background:#fff;"><?php echo $_SESSION['User']['linked_accounts']['SSH']['creation_time'];?></span>
						   </div>

                                                    <div class="form-group">
						        <a href="<?php echo $GLOBALS['BASEURL']; ?>user/linkedAccount.php?account=MN&action=update" class="btn btn-xs green"><i class="fa fa-refresh"></i> &nbsp; Generate new pair of Keys</a>
						        <a href="<?php echo $GLOBALS['BASEURL']; ?>applib/linkedAccount.php?account=MN&action=delete" class="btn btn-xs green"><i class="fa fa-trash"></i> &nbsp; Delete Account</a>
						    </div>
						</div>
					   <?php } ?>
						</div>
						<!-- END MN ACCOUNT -->


						<!-- EGA ACCOUNT
						
						<hr>
						<img src="https://ega-archive.org/blog/wp-content/uploads/2015/07/logo-ega.png"/ style="float: right; height: 50px; margin: 0 50px;">
						<h4>European Genome-phenome Archive (EGA)</h4><br/>
					
				
						<div style="padding-left: 15px;border-left: 2px solid lightgray;">

					<?php /*
					if (! isset($_SESSION['User']['linked_accounts']['EGA'])){?>
							
						<p>
						  <span style="color: #666;font-weight: bold;">
						  Do you have an EGA account?
						  </span>
						  Link it and you'll have one-click access for all your EGA datasets, under <a href="<?php echo $GLOBALS['BASEURL']; ?>getdata/eush_ega/eush_ega.php" target="blank"> <i class="icon-cloud-upload"></i> Get Data<i class="fa fa-circle" style="font-size:5px; margin: 0 5px; position: relative;top: -3px;"></i> EGA </a>
						</p>
						<div class="row" style="margin-left:30px;">
				    		    <div class="col-md-6">
						<a target="_blank" href="<?php echo $GLOBALS['BASEURL']; ?>user/linkedAccount.php?account=EGA&action=new" class="btn green"><i class="fa fa-plus"></i> &nbsp; Link your account</a>
						    </div>
				    		    <div class="col-md-6">
							<a href="javascript:void(0)"><i class="fa fa-sign-in"></i> How to apply to EGA access?</a>
						    </div>
						</div>
					<?php			
					}else{ ?>
                                                    <div class="form-group">
							<label class="control-label">User Name</label>
							<br />
							<input type="text" value="<?php echo $_SESSION['User']['linked_accounts']['EGA']['username'] ?>" class="form-control" readonly style="background:#fff;"  />
						   </div>
                                                    <div class="form-group">
							<label class="control-label">Crypt4GH Public key</label>
							<br />
							<pre><?php echo $_SESSION['User']['linked_accounts']['EGA']['pub_key'] ?></pre>
						   </div>
                                                    <div class="form-group">
							<label class="control-label">Crypt4GH Private key</label>

							<i onclick="this.classList.toggle('fa-eye-slash');x=document.getElementById('priv_key');if (x.style.display === 'none') {x.style.display = 'block';} else {x.style.display = 'none';}" class="fa fa-eye font-green" style="margin:10px; font-size:18px"></i>
							<br />
							<div style="height:150px;display:none;" id="priv_key">
							<pre ><?php echo $_SESSION['User']['linked_accounts']['EGA']['priv_key'] ?></pre>
							</div>
						   </div>
					<?php }*/ ?>
						</div> -->
						
						<!-- euroBioImaging ACCOUNT -->
						<hr>
						<img src="https://www.xnat.org/img/XNAT-logo-980.png"/ style="float: right; height: 50px; margin: 0 25px;">
						<h4>Unstructure Data Access</h4>

						<div style="padding-left: 15px;border-left: 2px solid lightgray;">
					<?php
					if (! isset($_SESSION['User']['linked_accounts']['euBI'])){?>
						<p>
						  <span style="color: #666;font-weight: bold;">
						  Do you have a XNAT account?
						  </span>
						  Link it and you'll have one-click access for all your euBI/XNAT protected datasets, under <a href="<?php echo $GLOBALS['BASEURL']; ?>getdata/eush_bioimages/eush_projects.php" target="blank"> <i class="icon-cloud-upload"></i> Get Data <i class="fa fa-circle" style="font-size:5px; margin: 0 5px; position: relative;top: -3px;"></i> XNAT </a>
						</p>
						<div class="row" style="margin-left:30px;">
				    		    <div class="col-md-6">
						     <a href="<?php echo $GLOBALS['BASEURL']; ?>user/linkedAccount.php?account=euBI&action=new" class="btn green"><i class="fa fa-plus"></i> &nbsp; Link your account</a>
						    </div>
				    		    <div class="col-md-6">
							<a href="javascript:void(0)"><i class="fa fa-sign-in"></i> How to apply to XNAT access?</a>
						    </div>
						</div>
					<?php			
					}else{ 
						// compute expiration time for XNAT token
						//
						$xnat_expiration = intval($_SESSION['User']['linked_accounts']['euBI']['estimatedExpirationTime']/1000);
						$ed = date('h:i:s A jS \of F Y', $xnat_expiration);
                                                $expiresIn = $xnat_expiration - time();
                                                if ($expiresIn > 0)
                                                    $expDate = "Alias will expire in " . intval($expiresIn / (60*60)) . " hours, at $ed";
                                                else
						    $expDate = "Alias is expired... Regenerate it at <a href='https://xnat.bmia.nl' target='_blank'>euro-BioImaging</a>";
						// validate XNAT token
						//if (is_valid_EuroBioImagingAliasToken()){
						//	print "TOKEN IS VALID<br/>";	
						//}else{
						//	print "SHIIT! TOKEN IS INVALID<br/>";	
						//}
						?>
                                                    <div class="form-group">
							<label class="control-label">euro-BioImaging Username</label>
							<br />
						    	<input type="text" value="<?php echo $_SESSION['User']['linked_accounts']['euBI']['xdatUserId'] ?>" class="form-control" readonly style="background:#fff;"  />
						   </div>
                                                    <div class="form-group">
							<label class="control-label">Alias Token</label>
							<br />
						    	<input type="text" value="<?php echo $_SESSION['User']['linked_accounts']['euBI']['alias'] ?>" class="form-control" readonly style="background:#fff;"  />
						   </div>

                                                    <div class="form-group">
							<label class="control-label">Expiration date</label>
							<br />
							<span class="form-control" readonly style="background:#fff;"><?php echo $expDate;?></span>

                                                	<?php if ($expiresIn < 0){ ?>
								<div style="margin:3px;font-size:0.9em;">
								<a target="_blank" href="https://wiki.xnat.org/documentation/how-to-use-xnat/generating-an-alias-token-for-scripted-authentication">How to generate an euro-BioImaging Alias Token?</a>
								<br/>
								<a target="_blank" href="https://xnat.bmia.nl/">Go to euro-BioImaging</a>
								</div>
							<?php } ?>

						   </div>

                                                    <div class="form-group">
							<label class="control-label">Secret</label>

							<i onclick="this.classList.toggle('fa-eye-slash');x=document.getElementById('secret');if (x.style.display === 'none') {x.style.display = 'block';} else {x.style.display = 'none';}" class="fa fa-eye font-green" style="margin:10px; font-size:18px"></i>
							<br />
							<div id="secret" style="height:50px;display:none;">
							<pre><?php echo $_SESSION['User']['linked_accounts']['euBI']['secret'] ?></pre>
							</div>
						   </div>
                                                    <div class="form-group">
						        <a href="<?php echo $GLOBALS['BASEURL']; ?>user/linkedAccount.php?account=euBI&action=update" class="btn btn-xs green"><i class="fa fa-refresh"></i> &nbsp; Update Alias Token</a>
						        <a href="<?php echo $GLOBALS['BASEURL']; ?>applib/linkedAccount.php?account=euBI&action=delete" class="btn btn-xs green"><i class="fa fa-trash"></i> &nbsp; Delete Account</a>
						    </div>
						</div>
					   <?php } ?>
						</div>
						<!-- END euroBioImaging ACCOUNT -->

						<!-- MOLGENIS ACCOUNT -->
						<hr>
						<div style="float: right; margin: 20px 50px; margin-right: 10px;">
							<!-- <img src="https://avatars.githubusercontent.com/u/1688158?s=200&v=4"/ style="float: right; height: 50px; margin: 0 50px;"> -->
							<img src="https://avatars.githubusercontent.com/u/1688158?s=200&v=4" style="height: 50px; margin-right: 10px;">
    							<img src="https://pbs.twimg.com/profile_images/810981601253261312/V1zRfeKR_400x400.jpg" style="height: 50px; margin-right: 10px;">
						</div>			
                                                <h4>Structure Data Access</h4>

                                                <div style="padding-left: 15px;border-left: 2px solid lightgray;">
                                        <?php
                                        if (! isset($_SESSION['User']['linked_accounts']['molgenis'])){?>
                                                <p>
                                                  <span style="color: #666;font-weight: bold;">
                                                  Do you have a Molgenis account?
                                                  </span>
                                                  Link it and you'll have one-click access for all your euBI/XNAT protected datasets, under <a href="<?php echo $GLOBALS['BASEURL']; ?>getdata/eush_bioimages/eush_projects.php" target="blank"> <i class="icon-cloud-upload"></i> Get Data <i class="fa fa-circle" style="font-size:5px; margin: 0 5px; position: relative;top: -3px;"></i> XNAT </a>
                                                </p>
                                                <div class="row" style="margin-left:30px;">
                                                    <div class="col-md-6">
                                                     <a href="<?php echo $GLOBALS['BASEURL']; ?>user/linkedAccount.php?account=molgenis&action=new" class="btn green"><i class="fa fa-plus"></i> &nbsp; Link your account</a>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <a href="javascript:void(0)"><i class="fa fa-sign-in"></i> How to apply to Molgenis access?</a>
                                                    </div>
                                                </div>
                                        <?php
                                        }else{
                                                // compute expiration time for XNAT token
                                                //
                                                $xnat_expiration = intval($_SESSION['User']['linked_accounts']['euBI']['estimatedExpirationTime']/1000);
                                                $ed = date('h:i:s A jS \of F Y', $xnat_expiration);
                                                $expiresIn = $xnat_expiration - time();
                                                if ($expiresIn > 0)
                                                    $expDate = "Alias will expire in " . intval($expiresIn / (60*60)) . " hours, at $ed";
                                                else
                                                    $expDate = "Alias is expired... Regenerate it at <a href='https://xnat.bmia.nl' target='_blank'>euro-BioImaging</a>";
                                                // validate XNAT token
                                                //if (is_valid_EuroBioImagingAliasToken()){
                                                //      print "TOKEN IS VALID<br/>";    
                                                //}else{
                                                //      print "SHIIT! TOKEN IS INVALID<br/>";   
                                                //}
?>
                                                    <div class="form-group">
                                                        <label class="control-label">euro-BioImaging Username</label>
                                                        <br />
                                                        <input type="text" value="<?php echo $_SESSION['User']['linked_accounts']['euBI']['xdatUserId'] ?>" class="form-control" readonly style="background:#fff;"  />
                                                   </div>
                                                    <div class="form-group">
                                                        <label class="control-label">Alias Token</label>
                                                        <br />
                                                        <input type="text" value="<?php echo $_SESSION['User']['linked_accounts']['euBI']['alias'] ?>" class="form-control" readonly style="background:#fff;"  />
                                                   </div>

                                                    <div class="form-group">
                                                        <label class="control-label">Expiration date</label>
                                                        <br />
                                                        <span class="form-control" readonly style="background:#fff;"><?php echo $expDate;?></span>

                                                        <?php if ($expiresIn < 0){ ?>
                                                                <div style="margin:3px;font-size:0.9em;">
                                                                <a target="_blank" href="https://wiki.xnat.org/documentation/how-to-use-xnat/generating-an-alias-token-for-scripted-authentication">How to generate an euro-BioImaging Alias Token?</a>
                                                                <br/>
                                                                <a target="_blank" href="https://xnat.bmia.nl/">Go to euro-BioImaging</a>
                                                                </div>
                                                        <?php } ?>

                                                   </div>

                                                    <div class="form-group">
                                                        <label class="control-label">Secret</label>

                                                        <i onclick="this.classList.toggle('fa-eye-slash');x=document.getElementById('secret');if (x.style.display === 'none') {x.style.display = 'block';} else {x.style.display = 'none';}" class="fa fa-eye font-green" style="margin:10px; font-size:18px"></i>
                                                        <br />
                                                        <div id="secret" style="height:50px;display:none;">
                                                        <pre><?php echo $_SESSION['User']['linked_accounts']['euBI']['secret'] ?></pre>
                                                        </div>
                                                   </div>
                                                    <div class="form-group">
                                                        <a href="<?php echo $GLOBALS['BASEURL']; ?>user/linkedAccount.php?account=euBI&action=update" class="btn btn-xs green"><i class="fa fa-refresh"></i> &nbsp; Update Alias Token</a>
                                                        <a href="<?php echo $GLOBALS['BASEURL']; ?>applib/linkedAccount.php?account=euBI&action=delete" class="btn btn-xs green"><i class="fa fa-trash"></i> &nbsp; Delete Account</a>
                                                    </div>
                                                </div>
                                           <?php } ?>
                                                </div>
						<!-- END molgenis ACCOUNT -->
						<!-- START opnestack ACCOUnt -->

					<div style="padding-top: 15px; padding-left: 15px;border-left: 2px solid lightgray;">
					<?php
                                        if (! isset($_SESSION['User']['linked_accounts']['openstack'])){?>
                                                <p>
                                                  <span style="color: #666;font-weight: bold;">
                                                  Do you have an OpenStack account?
                                                  </span>
                                                  Link it and you'll have one-click access for all your OpenStack Object Storage protected datasets, under <a href="<?php echo $GLOBALS['BASEURL']; ?>getdata/eush_bioimages/eush_projects.php" target="blank"> <i class="icon-cloud-upload"></i> Get Data <i class="fa fa-circle" style="font-size:5px; margin: 0 5px; position: relative;top: -3px;"></i> OPENSTACK </a>
                                                </p>
                                                <div class="row" style="margin-left:30px; padding-bottom:10px; ">
                                                    <div class="col-md-6">
                                                     <a href="<?php echo $GLOBALS['BASEURL']; ?>user/linkedAccount.php?account=objectstorage&action=new" class="btn green"><i class="fa fa-plus"></i> &nbsp; Link your account</a>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <a href="javascript:void(0)"><i class="fa fa-sign-in"></i> How to apply to BSC OpenStack Object Storage access?</a>
                                                    </div>
                                                </div>
                                        <?php
                                        }else{
                                                // compute expiration time for XNAT token
                                                //
                                                $xnat_expiration = intval($_SESSION['User']['linked_accounts']['euBI']['estimatedExpirationTime']/1000);
                                                $ed = date('h:i:s A jS \of F Y', $xnat_expiration);
                                                $expiresIn = $xnat_expiration - time();
                                                if ($expiresIn > 0)
                                                    $expDate = "Alias will expire in " . intval($expiresIn / (60*60)) . " hours, at $ed";
                                                else
                                                    $expDate = "Alias is expired... Regenerate it at <a href='https://xnat.bmia.nl' target='_blank'>euro-BioImaging</a>";
?>
                                                    <div class="form-group">
                                                        <label class="control-label">euro-BioImaging Username</label>
                                                        <br />
                                                        <input type="text" value="<?php echo $_SESSION['User']['linked_accounts']['euBI']['xdatUserId'] ?>" class="form-control" readonly style="background:#fff;"  />
                                                   </div>
                                                    <div class="form-group">
                                                        <label class="control-label">Alias Token</label>
                                                        <br />
                                                        <input type="text" value="<?php echo $_SESSION['User']['linked_accounts']['euBI']['alias'] ?>" class="form-control" readonly style="background:#fff;"  />
                                                   </div>

                                                    <div class="form-group">
                                                        <label class="control-label">Expiration date</label>
                                                        <br />
                                                        <span class="form-control" readonly style="background:#fff;"><?php echo $expDate;?></span>

                                                        <?php if ($expiresIn < 0){ ?>
                                                                <div style="margin:3px;font-size:0.9em;">
                                                                <a target="_blank" href="https://wiki.xnat.org/documentation/how-to-use-xnat/generating-an-alias-token-for-scripted-authentication">How to generate an euro-BioImaging Alias Token?</a>
                                                                <br/>
                                                                <a target="_blank" href="https://xnat.bmia.nl/">Go to euro-BioImaging</a>
                                                                </div>
                                                        <?php } ?>

                                                   </div>

                                                    <div class="form-group">
                                                        <label class="control-label">Secret</label>

                                                        <i onclick="this.classList.toggle('fa-eye-slash');x=document.getElementById('secret');if (x.style.display === 'none') {x.style.display = 'block';} else {x.style.display = 'none';}" class="fa fa-eye font-green" style="margin:10px; font-size:18px"></i>
                                                        <br />
                                                        <div id="secret" style="height:50px;display:none;">
                                                        <pre><?php echo $_SESSION['User']['linked_accounts']['euBI']['secret'] ?></pre>
                                                        </div>
                                                   </div>
                                                    <div class="form-group">
                                                        <a href="<?php echo $GLOBALS['BASEURL']; ?>user/linkedAccount.php?account=euBI&action=update" class="btn btn-xs green"><i class="fa fa-refresh"></i> &nbsp; Update Alias Token</a>
                                                        <a href="<?php echo $GLOBALS['BASEURL']; ?>applib/linkedAccount.php?account=euBI&action=delete" class="btn btn-xs green"><i class="fa fa-trash"></i> &nbsp; Delete Account</a>
                                                    </div>
                                                </div>
                                           <?php } ?>
                                                </div>
						<!-- end openstack account -->


                                                </div>

                                                </div>


                                                <!-- END CHANGE PASSWORD TAB -->


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
