<?php

require __DIR__."/../../config/bootstrap.php";
#require_once __DIR__."/../../public/phplib/classes/Vault.php";

redirectOutside();


// Check query
if(!$_REQUEST){
	redirect($GLOBALS['URL']);

}elseif (!isset($_REQUEST['account'])) {
	redirect($_SERVER['HTTP_REFERER']);
}



//echo ($_REQUEST['account']);
//var_dump($_REQUEST['action']);
//var_dump($_POST); 

addUserLinkedAccount($_REQUEST['account'], $_REQUEST['action'], $_POST);


//
// Process actions for the linked account

/* switch ($_REQUEST['account']) {
	case "euBI":
		// Process according to 'action'
		switch ($_REQUEST['action']) {

		    // Validate and Save/Update Alias Token
		    case "update":
		    case "new":

		    	// Check compulsory fields
		    	if (!isset($_POST['alias_token']) || !isset($_POST['secret'])) {
				$_SESSION['errorData']['Error'][]="Not receiving expected fields. Please user submit the data again.";
				$_SESSION['formData'] = $_POST;
				redirect($_SERVER['HTTP_REFERER']);
		    	}

			// Add/Update eurobioimanging Token
			// function not DEFINED
			$r = addUserLinkedAccount_euBI($_POST['alias_token'],$_POST['secret']);
			if(!$r){
				$_SESSION['errorData']['Error'][]="Failed to link euroBioImaging account";
				$_SESSION['formData'] = $_POST;
				redirect($_SERVER['HTTP_REFERER']);
			}

			$_SESSION['errorData']['Info'][]="Account successfully linked";
			redirect($GLOBALS['BASEURL']."user/usrProfile.php#tab_1_4");
			break;

		    // Delete Alias Token
		    case "delete":

			$r = deleteUserLinkedAccount($_SESSION['User']['_id'],$_REQUEST['account']);
			if(!$r){
				$_SESSION['errorData']['Error'][]="Failed to unlink euroBioImaging account";
				redirect($_SERVER['HTTP_REFERER']);
			}
			$_SESSION['errorData']['Info'][]="Account successfully unlinked";
			redirect($GLOBALS['BASEURL']."user/usrProfile.php#tab_1_4");
			break;
		}
		break;

	case "EGA":

		break;
	case "MN":
		//echo "<br> CIAO";
		//var_dump($_REQUEST);

		// Process according to 'action'
		switch ($_REQUEST['action']) {

		    // Validate and Save/Update Alias Token
		    case "update":
		    case "new":

		 	$data = [];
			//echo $GLOBALS['vaultUrl'] . "<br>";

		    	// Check compulsory fields
			if (isset($_POST['generate_keys']) && $_POST['generate_keys']=='true') {
				$keys = generate_RSA_keys($_REQUEST['account']);
					
			//}elseif(isset($_POST['generate_keys']) && $_POST['generate_keys']=='false' ) {
			} elseif (isset($_POST['save_credential']) && $_POST['save_credential']=='true') {
				
				$accessToken = json_decode($_SESSION['User']['JWT'], true)["access_token"];

				$data['data']['SSH'] = [];
				$data['data']['SSH']['private_key'] = $_POST['priv_key'];
				$data['data']['SSH']['public_key'] = $_POST['pub_key'];
				$data['data']['SSH']['username'] = $_POST['username'];

				$vaultClient = new VaultClient($GLOBALS['vaultUrl'], $GLOBALS['vaultToken'], $accessToken, $GLOBALS['vaultRolename'], $_POST['username']);
				//$serializedObject = serialize($vaultClient);
				//var_dump($serializedObject);
				/// Can check internally if  the format is correct of the credentials, now the conditions changed!
        			$try = $vaultClient->uploadKeystoVault($data);
					
				//echo "	OKAY <br></br>";
			//	var_dump($vaultClient);
			//	var_dump($try);
				//$_SESSION['User']['vaultClient'] = $vaultClient;
			//	var_dump($_SESSION['User']['vaultClient']);
				//$_SESSION["bho"] = $vaultClient;
				//var_dump($vaultKey);
				$_SESSION['User']['vaultKey'] = $try;
				$r = updateUser($_SESSION['User']);
				$_SESSION['formData'] = $_POST;
				#$_SESSION['hola'] = "Hola";
				#$_SESSION['User']
				#var_dump($_SESSION['hola']);
				#echo '<br /><a href="../workspace/index.php">page 2</a>';
				#die(0);
				//var_dump($_SESSION['errorData']);	
				$_SESSION['errorData'] = ['Info' => ["Account successfully linked."]];
				redirect($GLOBALS['BASEURL']."user/usrProfile.php#tab_1_4");
				

				//$_SESSION['errorData']['Error'][] = "Something went wrong.";
				#var_dump($_SESSION['User']);
				//$_SESSION['errorData'] = ['Info' => ["Account successfully linked."]];
				redirect($GLOBALS['BASEURL']."user/usrProfile.php#tab_1_4");
				//redirect($_SERVER['HTTP_REFERER']);
				break;


			}else{  
				$_SESSION['errorData']['Error'][]="Not receiving expected fields so please submit the data again.";
				$_SESSION['formData'] = $_POST;
				redirect($_SERVER['HTTP_REFERER']);
			}

			//$_SESSION['errorData']['Info'][]="Account successfully linked";

		    	// Add/Update eurobioimanging Token
			//$r = addUserLinkedAccount_euBI($_POST['alias_token'],$_POST['secret']);
			//break;
		}
		//exit("sss");
		break;
	case "molgenis":
                // Process according to 'action'
                switch ($_REQUEST['action']) {

                    // Validate and Save/Update Alias Token
                    case "update":
                    case "new":

                        // Check compulsory fields
                        if (!isset($_POST['username']) || !isset($_POST['secret'])) {
                                $_SESSION['errorData']['Error'][]="Not receiving expected fields. Please, submit the data again.";
                                $_SESSION['formData'] = $_POST;
                                redirect($_SERVER['HTTP_REFERER']);
                        } elseif (isset($_POST['save_credential']) && $_POST['save_credential']=='true') {

                                $accessToken = json_decode($_SESSION['User']['JWT'], true)["access_token"];

                                $data['data']['app_cred'] = [];
                                $data['data']['app_cred']['app_user'] = $_POST['app_cred'];
                                $data['data']['app_cred']['public_key'] = $_POST['pub_key'];
                                $data['data']['SSH']['username'] = $_POST['username'];

                                $vaultClient = new VaultClient($GLOBALS['vaultUrl'], $GLOBALS['vaultToken'], $accessToken, $GLOBALS['vaultRolename'], $_POST['username']);
                                //$serializedObject = serialize($vaultClient);
                                //var_dump($serializedObject);
                                /// Can check internally if  the format is correct of the credentials, now the conditions changed!
                                $try = $vaultClient->uploadKeystoVault($data);

                                //echo "        OKAY <br></br>";
                        //      var_dump($vaultClient);
                        //      var_dump($try);
                                //$_SESSION['User']['vaultClient'] = $vaultClient;
                        //      var_dump($_SESSION['User']['vaultClient']);
                                //$_SESSION["bho"] = $vaultClient;
                                //var_dump($vaultKey);
                                $_SESSION['User']['vaultKey'] = $try;
                                $r = updateUser($_SESSION['User']);
                                $_SESSION['formData'] = $_POST;
                                #$_SESSION['hola'] = "Hola";
                                #$_SESSION['User']
                                #var_dump($_SESSION['hola']);
                                #echo '<br /><a href="../workspace/index.php">page 2</a>';
                                #die(0);
                                //var_dump($_SESSION['errorData']);     
                                $_SESSION['errorData'] = ['Info' => ["Account successfully linked."]];
                                redirect($GLOBALS['BASEURL']."user/usrProfile.php#tab_1_4");
			}

                        // Add/Update eurobioimanging Token
                        // function is not defined!
                        $r = addUserLinkedAccount_molgenis($_POST['username'],$_POST['secret']);
                        if(!$r){
                                $_SESSION['errorData']['Error'][]="Failed to link Molgenis account";
                                $_SESSION['formData'] = $_POST;
                                redirect($_SERVER['HTTP_REFERER']);
                        }

                        $_SESSION['errorData']['Info'][]="Account successfully linked";
                        redirect($GLOBALS['BASEURL']."user/usrProfile.php#tab_1_4");
                        break;

                    // Delete Alias Token
                    case "delete":

                        $r = deleteUserLinkedAccount($_SESSION['User']['_id'],$_REQUEST['account']);
                        if(!$r){
                                $_SESSION['errorData']['Error'][]="Failed to unlink Molgenis account";
                                redirect($_SERVER['HTTP_REFERER']);
                        }
                        $_SESSION['errorData']['Info'][]="Account successfully unlinked";
                        redirect($GLOBALS['BASEURL']."user/usrProfile.php#tab_1_4");
                        break;
                }
		break;
	case "objectstorage":
                // Process according to 'action'
                switch ($_REQUEST['action']) {

                    // Validate and Save/Update Alias Token
                    case "update":
                    case "new":

                        // Check compulsory fields
                        if (!isset($_POST['app_user']) || !isset($_POST['app_secret'])) {
                                $_SESSION['errorData']['Error'][]="Not receiving expected fields. Please, submit the data again.";
                                $_SESSION['formData'] = $_POST;
                                redirect($_SERVER['HTTP_REFERER']);
                        }

                        // Add/Update eurobioimanging Token
                        // function is not defined!
                        $r = addUserLinkedAccount_objectstorage($_POST['username'],$_POST['secret']);
                        if(!$r){
                                $_SESSION['errorData']['Error'][]="Failed to link OpenStack account";
                                $_SESSION['formData'] = $_POST;
                                redirect($_SERVER['HTTP_REFERER']);
                        }

                        $_SESSION['errorData']['Info'][]="Account successfully linked";
                        redirect($GLOBALS['BASEURL']."user/usrProfile.php#tab_1_4");
                        break;

                    // Delete Alias Token
                    case "delete":

                        $r = deleteUserLinkedAccount($_SESSION['User']['_id'],$_REQUEST['account']);
                        if(!$r){
                                $_SESSION['errorData']['Error'][]="Failed to unlink Molgenis account";
                                redirect($_SERVER['HTTP_REFERER']);
                        }
                        $_SESSION['errorData']['Info'][]="Account successfully unlinked";
                        redirect($GLOBALS['BASEURL']."user/usrProfile.php#tab_1_4");
                        break;
                }
                break;
	default:
		$_SESSION['errorData']['Error'][]= "Account of type '".$_REQUEST['account']."' is not yet supported.";
		redirect($_SERVER['HTTP_REFERER']);
	

}

//redirect($_SERVER['HTTP_REFERER']);
?>
