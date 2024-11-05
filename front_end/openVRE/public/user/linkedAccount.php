<?php

require __DIR__ . "/../../config/bootstrap.php";

redirectOutside();


require "../htmlib/header.inc.php";?>

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
                            <span>Home</span>
                            <i class="fa fa-circle"></i>
                        </li>
                        <li>
                            <span>User</span>
                            <i class="fa fa-circle"></i>
                        </li>
                        <li>
			<span><a href="<?php echo $GLOBALS['URL'];?>user/usrProfile.php#tab_1_4">My Keys</a></span>
                            <i class="fa fa-circle"></i>
                        </li>
                        <li>
                            <span>Linked Accounts</span>
                        </li>
                    </ul>
                </div>
                <!-- END PAGE BAR -->
                <!-- BEGIN PAGE TITLE-->
                <h1 class="page-title"> Linked Accounts </h1>
                <!-- END PAGE TITLE-->
                <!-- END PAGE HEADER-->
                <div class="row">
                    <div class="col-md-12">
			<!-- SHOW ERRORS -->
                <div class="col-md-12">
                <?php if(isset($_SESSION['errorData'])) { ?>
                        <div class="alert alert-warning">
                        <?php foreach($_SESSION['errorData'] as $subTitle=>$txts){
                                print "$subTitle<br/>";
                                foreach($txts as $txt){
                                        print "<div style=\"margin-left:20px;\">$txt</div>";
                                }
                        }
                        unset($_SESSION['errorData']);
                        ?>
                        </div>
                <?php } ?>

			</div>
                    </div>

                    <form name="linkedAccount" id="linkedAccount" action="applib/linkedAccount.php" method="post">
                  
			<input type="hidden" name="account" id="account" value="<?php echo $_REQUEST['account'];?>">
			<input type="hidden" name="action"  id="action"  value="<?php echo $_REQUEST['action'];?>">
			<input type="hidden" name="_id" value="<?php echo $_SESSION['User']['_id']; ?>">

		    <!--  EUROBIOIMAGING FORM     -->

		    <?php if ($_REQUEST['account'] == "euBI"){

		    	// set form default values

		    	$defaults = array();
		    	if (isset($_SESSION['formData'])){
		    		$defaults = $_SESSION['formData'];
                                unset($_SESSION['formData']);
			}elseif($_REQUEST['action'] == 'update'){
				$defaults['alias_token'] = $_SESSION['User']['linked_accounts']['euBI']['alias']; 
				$defaults['secret']      = $_SESSION['User']['linked_accounts']['euBI']['secret']; 
			}

			// print html form
			?>

			<div class="portlet box blue-oleo">
                            <div class="portlet-title">
                                <div class="caption">
                                    <div style="float:left;margin-right:20px;"> <i class="fa fa-link"></i> Unstructured Data Access</div>
                                </div>
			    </div>
                            <div class="portlet-body form">
                                <div class="form-body">
				<p>XNAT provides Alias Tokens for allowing external applications to <strong>authenticate a session on your behalf</strong> for a limited period of time. If your are sure you want to allow <?php echo $GLOBALS['NAME']?> VRE access, fill in the following form.</p>
                                    <div class="row">
                                        <div class="col-md-6"></div>
                                        <div class="col-md-6">
					    <div class="form-group">
							<a target="_blank" href="https://wiki.xnat.org/documentation/how-to-use-xnat/generating-an-alias-token-for-scripted-authentication">How to generate an XNAT Alias Token?</a><br/>
							<a target="_blank" href="https://xnat.bmia.nl/">Go to XNAT</a></br>
						<a href="javascript:openTermsOfUse();"><?php echo $GLOBALS['NAME']?>VRE terms of use</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label class="control-label">Alias Token</label>
						<input type="text" name="alias_token" id="alias_token" class="form-control" value="<?php echo $defaults['alias_token'];?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label class="control-label">Secret</label>
                                                <input type="text" name="secret" id="secret" class="form-control" value="<?php echo $defaults['secret'];?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label class="control-label">Time limit (hours)</label>
                                                <span  class="form-control" readonly>48</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
				<div class="form-actions"> 
                                    <button type="submit" class="btn blue"><i class="fa fa-check"></i> Submit</button>
                                    <button type="reset" class="btn default">Reset</button>
				    <button onclick="window.history.go(-1); return false;" class="btn default">Cancel</button>
                                </div>
                            </div>
			</div>

		  <!--  EUROBIOIMAGING FORM CLOSED    -->

	                  <!--  MOLGENIS FORM    -->
		   <?php } elseif ($_REQUEST['account'] == "molgenis"){


                        // set form default values

                        $defaults = array();
                        if (isset($_SESSION['formData'])){
                                $defaults = $_SESSION['formData'];
                                unset($_SESSION['formData']);
                        }elseif($_REQUEST['action'] == 'update'){
                                $defaults['username'] = $_SESSION['User']['linked_accounts']['molgenis']['username'];
				$defaults['secret']      = $_SESSION['User']['linked_accounts']['molgenis']['secret'];
			}
			?>

		    <div class="portlet box blue-oleo">
                            <div class="portlet-title">
                                <div class="caption">
                                    <div style="float:left;margin-right:20px;"> <i class="fa fa-link"></i> Structured Data Access</div>
                                </div>
                            </div>
                            <div class="portlet-body form">
                                <div class="form-body">
                                <p>Molgenis provides Username for allowing external applications to <strong>authenticate a session on your behalf</strong> for a limited period of time. If your are sure you want to allow <?php echo $GLOBALS['NAME']?> VRE access, fill in the following form.</p>
                                    <div class="row">
                                        <div class="col-md-6"></div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                        <a target="_blank" href="https://data.disc4all.eu/login">How to authenticate on Molgenis?</a><br/>
                                                        <a target="_blank" href="https://catalogue.disc4all.eu/apps/central/#/">Go to Molgenis</a></br>
                                                <a href="javascript:openTermsOfUse();"><?php echo $GLOBALS['NAME']?>VRE terms of use</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label class="control-label">Username</label>
                                                <input type="text" name="username" id="alias_token" class="form-control" value="<?php echo $defaults['username'];?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label class="control-label">Secret</label>
                                                <input type="text" name="secret" id="secret" class="form-control" value="<?php echo $defaults['secret'];?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label class="control-label">Time limit (hours)</label>
                                                <span  class="form-control" readonly>48</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
				<div class="form-actions">
                                    <button type="submit" class="btn blue"><i class="fa fa-check"></i> Submit</button>
                                    <button type="reset" class="btn default">Reset</button>
                                    <button onclick="window.history.go(-1); return false;" class="btn default">Cancel</button>
                                </div>
                            </div>
                        </div>

		     <!--  OPENSTACK FORM    -->
                   <?php } elseif ($_REQUEST['account'] == "objectstorage"){


                        // set form default values

                        $defaults = array();
                        if (isset($_SESSION['formData'])){
                                $defaults = $_SESSION['formData'];
                                unset($_SESSION['formData']);
                        }elseif($_REQUEST['action'] == 'update'){
                                $defaults['app_id'] = $_SESSION['User']['linked_accounts']['Swift']['app_id'];
                                //$defaults['app_secret']      = $_SESSION['User']['linked_accounts']['Swift']['app_secret'];
                        }
                        ?>

                    <div class="portlet box blue-oleo">
                            <div class="portlet-title">
                                <div class="caption">
                                    <div style="float:left;margin-right:20px;"> <i class="fa fa-link"></i> Structured Data Access</div>
                                </div>
                            </div>
                            <div class="portlet-body form">
                                <div class="form-body">
                                <p>OpenStack provides different way of authenticta, but in this case it is requires the <strong>Application Credentials authentication</strong>, to allow external applications to <strong>authenticate a session on your behalf</strong> for a limited period of time. If your are sure you want to allow <?php echo $GLOBALS['NAME']?> VRE access, fill in the following form.</p>
                                    <div class="row">
                                        <div class="col-md-6"></div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                        <a target="_blank" href="https://docs.openstack.org/keystone/queens/user/application_credentials.html">How to generate Application Credentials?</a><br/>
							<a target="_blank" href="https://ncloud.bsc.es/dashboard/auth/login/?next=/dashboard/project/instances/">Go to OpenStack by BSC OpenID Connect</a></br>
                                                <a href="javascript:openTermsOfUse();"><?php echo $GLOBALS['NAME']?>VRE terms of use</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label class="control-label">Application Credential ID</label>
                                                <input type="text" name="app_id" id="app_id" class="form-control" value="<?php echo $defaults['app_id'];?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label class="control-label">Application Credential Secret</label>
                                                <input type="text" name="app_secret" id="app_secret" class="form-control" value="<?php echo $defaults['app_secret'];?>">
                                            </div>
                                        </div>
				    </div>
				    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="control-label">Project Name</label>
                                                <input type="text" name="projectName" id="projectName" class="form-control" value="<?php echo $defaults['projectName'];?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="control-label">Project Id</label>
                                                <input type="text" name="projectId" id="projectId" class="form-control" value="<?php echo $defaults['projectId'];?>">
                                            </div>
					</div>
				    </div>
				    <div class="row">
                                    	<div class="col-md-6">
                                            <div class="form-group">
                                                <label class="control-label">Domain Name</label>
                                                <input type="text" name="domainName" id="domainName" class="form-control" value="<?php echo $defaults['domainName'];?>">
                                            </div>
                                        </div>
				    	<div class="col-md-6">
                                            <div class="form-group">
                                                <label class="control-label">Domain Id</label>
                                                <input type="text" name="projectDomainId" id="projectDomainId" class="form-control" value="<?php echo $defaults['projectDomainId'];?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label class="control-label">Region Name</label>
                                                <span  class="form-control" readonly>RegionOne</span>
                                            </div>
                                        </div>
				    </div>
				    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label class="control-label">Interface</label>
                                                <span  class="form-control" readonly>Public</span>
                                            </div>
                                        </div>
				    </div>
				    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label class="control-label">Authorization Type</label>
                                                <span  class="form-control" readonly>v3applicationcredential</span>
                                            </div>
                                        </div>
				    </div>
				    <div class="row">
				 	<div class="col-md-12 text-right">
						<input type="hidden" name="save_credential" id="save_credential" value="false">
                                        	<button type="submit" onclick="document.getElementById('save_credential').value=true" class="btn blue"><i class="fa fa-check"></i> Accept</button>
                                        	<button type="submit" name="submitOption" value="clearAccount" href="<?php echo $GLOBALS['BASEURL']; ?>user/linkedAccount.php?account=objectstorage&action=delete" class="btn" style="background-color: white"><i class="fa fa-plus"></i> &nbsp; Clear account</button>
                                        	<button type="submit" name="submitOption" value="updateAccount" href="<?php echo $GLOBALS['BASEURL']; ?>user/linkedAccount.php?account=objectstorage&action=update"  class="btn" style="background-color: #d4d4d4"><i class="fa fa-plus"></i> &nbsp; Update account</button>
				   	</div>
				    </div>
				</div>
			    </div>
                        </div>


		    <!--  marenostrum FORM     -->
		 
		    <?php } elseif ($_REQUEST['account'] == "SSH"){

		    	// set form default values

		    	$defaults = array();
		    	if (isset($_SESSION['formData'])){
		    		$defaults = $_SESSION['formData'];
                                unset($_SESSION['formData']);
			#}elseif($_REQUEST['action'] == 'update'){
			}else{
				//$defaults['priv_key'] = (isset($_SESSION['User']['linked_accounts']['SSH']['hpc_priv_key'])?$_SESSION['User']['linked_accounts']['SSH']['hpc_priv_key']:null); 
				//$defaults['pub_key']  = (isset($_SESSION['User']['linked_accounts']['SSH']['hpc_pub_key'])?$_SESSION['User']['linked_accounts']['SSH']['hpc_pub_key']:null);
				//$defaults['username'] = (isset($_SESSION['User']['linked_accounts']['SSH']['hpc_username'])?$_SESSION['User']['linked_accounts']['SSH']['hpc_username']:null); 
				//$defaults['password'] = (isset($_SESSION['User']['linked_accounts']['MN']['password'])?$_SESSION['User']['linked_accounts']['MN']['password']:"bsccns"); 
			}

			// print html form
			?>
			<div class="portlet box blue-oleo">

                            <div class="portlet-title">
                                <div class="caption">
                                    <div style="float:left;margin-right:20px;"> <i class="fa fa-link"></i> SSH Credentials for Computational Environments</div>
                                </div>
			   </div>
                            <div class="portlet-body form">
                                <div class="form-body">
				<p>Enable the use public key authentication for your account in 'High Performance Computer' or other Computational Environment</p>
                                    <div class="row">
                                        <div class="col-md-6"></div>
                                        <div class="col-md-6">
					    <div class="form-group">
						<a target="_blank" href="">High Performance Computer Guide</a></br>
						<a href="javascript:openTermsOfUse();"><?php echo $GLOBALS['NAME']?> VRE terms of use</a>
                                            </div>
                                        </div>
				    </div>


				    <h4> Stored Credentials</h4>
				   These are the credentials currently used by VRE for tools configured to be remotely executed at external HPC resources (MareNostrum as latest). 
                                    <div class="form-body">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label class="control-label">Private Key</label>
                                                <input type="text" name="priv_key" id="priv_key" class="form-control" value="<?php echo $defaults['priv_key'];?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label class="control-label">Public Key</label>
						<input type="text" name="pub_key" id="pub_key" class="form-control" value="<?php echo $defaults['pub_key'];?>">
                                            </div>
					</div>
				    </div>
				    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label class="control-label">HPC Account Username</label>
                                                <input type="text" name="hpc_username" id="hpc_username" class="form-control" value="<?php echo $defaults['hpc_username'];?>">
                                            </div>
                                        </div>
                                    </div>
				    <!-- <input type="hidden" name="save_credential" id="save_credential" value="false"/> 
				    <button type="submit'" class="btn blue" onclick="document.getElementById('save_credential').value=true"><i class="fa fa-cog"></i> Save Credentials</button> -->					
				    <div class="col-md-12 text-right">
					<input type="hidden" name="save_credential" id="save_credential" value="false">
    					<button type="submit" onclick="document.getElementById('save_credential').value=true" class="btn blue"><i class="fa fa-check"></i> Accept</button>
    					<button type="submit" name="submitOption" value="clearAccount" href="<?php echo $GLOBALS['BASEURL']; ?>user/linkedAccount.php?account=SSH&action=delete" class="btn" style="background-color: white"><i class="fa fa-plus"></i> &nbsp; Clear account</button>
    					<button type="submit" name="submitOption" value="updateAccount" href="<?php echo $GLOBALS['BASEURL']; ?>user/linkedAccount.php?account=SSH&action=update"  class="btn" style="background-color: #d4d4d4"><i class="fa fa-plus"></i> &nbsp; Update account</button>
				   </div>
				</div>


				   <h4> Generate SSH Key Pair</h4>
				   If you have a user account for 'High Performance Computer', create an new SSH Key Pair to enable VRE access to it
                                    <div class="form-body">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
						<span  class="form-control" style="height:auto" readonly>
						<strong>How to </strong> authorize the new keys into your HPC user account is a two-steps process:
						<ol>
							<li>Generate a new pair of encrypted RSA keys here</li>
							<li>Download and copy the resulting 'Public Key' in the HPC server. Install it under your home directory, in the <a href="https://www.ssh.com/academy/ssh/authorized-keys-file>">authorized keys file</a> </li>
						</ol>
						Remember that you always can <strong>disable</strong> the VRE access simply deleting or commenting the 'Public Key' line from your HPC server.  
						</span>
					    </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label class="control-label">HPC Account Username</label>
                                                <input type="text" name="username" id="username" class="form-control" value="<?php echo $defaults['username'];?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label class="control-label">New SSH key passphrase</label>
                                                <input type="text" name="password" id="password" class="form-control" value="<?php echo $defaults['password'];?>">
                                            </div>
                                        </div>
                                    </div>
				    <input type="hidden" name="generate_keys" id="generate_keys" value="false"/>
				    <button type="submit" class="btn blue" onclick="document.getElementById('generate_keys').value=true"><i class="fa fa-cog"></i> Generate keys</button>
				    </div>
                            </div>
                                <div class="form-actions">
                                   <!--- <button type="submit" class="btn blue"><i class="fa fa-check"></i> Accept</button>
				    <button type="reset" class="btn default">Reset</button> --->
				    <button onclick="window.history.go(-1); return false;" class="btn default">Reset</button>
				    <button onclick="window.location.href='<?php echo $GLOBALS['BASEURL']; ?>user/usrProfile.php#tab_1_4'; return false;" class="btn default">Back</button>
				
                                </div>
                        </div>
		 
			<?php } ?>	


                    </form>


                </div>
                <!-- END CONTENT BODY -->
            </div>
	    <!-- END CONTENT -->

	  <div class="modal" id="myModal1" tabindex="-1" role="basic" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <?php
                        if (isset($_SESSION['errorData'])) {
                            ?>
                            <div class="alert alert-warning">
                                <?php foreach ($_SESSION['errorData'] as $subTitle => $txts) {
					?>
				<div class="modal-header">
				    <h4 class="modal-title"><?php echo $subTitle; ?></h4>
				    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
                                </div>
                                <div class="modal-body">
                                    <?php foreach ($txts as $txt) {
                                        print $txt . "</br>";
                                    } ?>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn dark btn-outline" data-dismiss="modal">Close</button>
                                </div>
                            <?php
                        }
                        unset($_SESSION['errorData']);
                        ?>

                        <?php } ?>
                    </div>
                    <!-- /.modal-content -->
                </div>
                <!-- /.modal-dialog -->
            </div>

            <div class="modal fade bs-modal-sm" id="myModal5" tabindex="-1" role="basic" aria-hidden="true">
                <div class="modal-dialog modal-sm">
		    <div class="modal-content">
			<div class="alert alert-success">
                    		<?php foreach ($_SESSION['errorData']['Info'] as $info) { ?>
                        		<h4 class="modal-title">Success! </h4>
                        		<div><?php echo $info; ?></div>
                    		<?php } ?>
                	</div>
			<div class="modal-footer">
                    		<button type="button" class="btn dark btn-outline" data-dismiss="modal">Accept</button>
                	</div>
                    </div>
		    <!-- /.modal-content -->
                </div>
                <!-- /.modal-dialog -->
            </div>

            <?php

            require "../htmlib/footer.inc.php";
            require "../htmlib/js.inc.php";

?>
