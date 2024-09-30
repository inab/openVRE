<?php

/************************
// Settings
************************/


// Main config
$GLOBALS['SERVER']    = "https://vre.disc4all.eu"; // domain 
$GLOBALS['BASEURL']   = "/"; // prefix url path. Set "/" for no prefix
$GLOBALS['AppPrefix'] = "D4A"; // project url acronym
$GLOBALS['NAME']      = "Disc4All"; // project name 
$GLOBALS['SITETITLE'] = "Disc4All | Virtual Research Environment"; // site title
$GLOBALS['TIMEOUT']   = 3600; // session and cookies timeout
$GLOBALS['vre_network_name'] = "dockerized_vre_local_net";  //Docker vnet name - make sure consistent with Docker Compose file

// Email
$GLOBALS['mail_credentials'] = __DIR__."/mail.conf"; // SMTP credentials
$GLOBALS['FROMNAME']  = "VRE_Disc4All"; // 'From' for VRE tickets and notifications
$GLOBALS['ADMINMAIL'] = "maria.ferri@bsc.es"; // BBC address for VRE ticket emails

// SGE
$GLOBALS['queueTask']  = "local.q"; //default queue

// Mongo databases
$GLOBALS['db_credentials'] = __DIR__."/mongo.conf"; // Mongo access 
$GLOBALS['dbname_VRE']     = "disc4all_vre"; // Database name
//$GLOBALS['dbname_VRE']     = "openVRE";

//VRE installation paths
$GLOBALS['root']       = dirname(__DIR__); // VRE root directory
$GLOBALS['localVolumes']  = "/home/ubuntu/dockerized_vre/volumes"; // Local path for volumes for right redirection
$GLOBALS['logFile']    = $GLOBALS['root']."/logs/application.log"; // Log file path 
$GLOBALS['shared']     = "/shared_data/"; // VRE data directory
$GLOBALS['dataDir']    = $GLOBALS['shared']."userdata"; // User data directory
$GLOBALS['pubDir']     = $GLOBALS['shared']."public/"; // Public data directorY
$GLOBALS['sampleData'] = $GLOBALS['shared']."sampleData/"; // Tool dataset directory 
$GLOBALS['sampleData_default'] = "basic"; // Default workspace's dataset entry

// File manager config
$GLOBALS['DISKLIMIT']       = 10*1024*1024*1024; // Default user disk quote (GB)
$GLOBALS['DISKLIMIT_ANON']  = 5*1024*1024*1024; // Default not-registerd disk quote (GB)
$GLOBALS['MAXSIZEUPLOAD']   = 4000; // Maximum upload file size (MB)
$GLOBALS['caduca']          = "182"; // Expiration date for user files (days)
$GLOBALS['project_default'] = "MyFirstDisc4AllProject"; // Default name for user project
$GLOBALS['tmpUser_dir']     = "basic"; // Default name for user temporal forder
// $GLOBALS['tmpUser_dir']     = ".tmp/";

// Tool integration models and templates
$GLOBALS['tool_json_schema']    = $GLOBALS['root']."/install/data/tool_schemas/tool_specification/tool_schema.json"; // data model for tool registration
$GLOBALS['tool_io_json_schema']  = $GLOBALS['root']."/install/data/tool_schemas/tool_specification/tool_schema_io.json"; // data model for tool registration - only I/O definition
$GLOBALS['tool_dev_sample']     = $GLOBALS['root']."/install/data/tool_schemas/tool_specification/examples/example.json"; // template for tool registration - step 3
$GLOBALS['tool_io_dev_sample']  = $GLOBALS['root']."/install/data/tool_schemas/tool_specification/examples/example_io.json"; // template for tool registration - step 1 I/O

// Oauth2 authentification
$GLOBALS['auth_credentials']       = __DIR__."/oauth2.conf"; // oauth2 client credentials
$GLOBALS['authAdmin_credentials']  = __DIR__."/oauth2_admin.conf"; // oauth2 client credentials with admin privileges
// $GLOBALS['authServer']             = 'http://84.88.186.195:8089/auth'; // external oauth2 server
//$GLOBALS['authServer']             = 'http://84.88.189.184:8089/auth'; // external oauth2 server
$GLOBALS['authServer']             = 'https://inb.bsc.es/auth';
//$GLOBALS['authRealm']              = 'VRE-realm'; // keycloak realm
$GLOBALS['authRealm']              = 'disc4all'; // keycloak realm
$GLOBALS['urlAuthorize' ]          = $GLOBALS['authServer'].'/realms/'.$GLOBALS['authRealm'].'/protocol/openid-connect/auth';     //get autorization_code
$GLOBALS['urlAccessToken']         = $GLOBALS['authServer'].'/realms/'.$GLOBALS['authRealm'].'/protocol/openid-connect/token';    //get token
$GLOBALS['urlResourceOwnerDetails']= $GLOBALS['authServer'].'/realms/'.$GLOBALS['authRealm'].'/protocol/openid-connect/userinfo'; //get user details
$GLOBALS['urlLogout']              = $GLOBALS['authServer'].'/realms/'.$GLOBALS['authRealm'].'/protocol/openid-connect/logout';   //close keyclok session   
$GLOBALS['adminToken']             = $GLOBALS['authServer']."/realms/master/protocol/openid-connect/token"; // get Admin token
$GLOBALS['adminRealm']             = $GLOBALS['authServer']."/admin/realms/".$GLOBALS['authRealm']; // admin keycloak users


// Vault integration and roles
$GLOBALS['vaultUrl'] = 'https://vre.disc4all.eu/vault/';
$GLOBALS['vaultToken'] = 'root';
//$jwtToken = $_SESSION['User']['JWT'];
$GLOBALS['vaultRolename'] = 'demo';
$GLOBALS['secretPath'] = 'secret/mysecret/data/';



/************************
// Definitions
************************/


// Default names and local path for VRE
$GLOBALS['URL']       = $GLOBALS['SERVER'].$GLOBALS['BASEURL']; // full VRE URL 
$GLOBALS['URL_login'] = $GLOBALS['URL']."login.php"; // Default for auth server login
$GLOBALS['htmlPath']  = $GLOBALS['root']. "/public/"; // Default path for public folder
$GLOBALS['htmlib']    = $GLOBALS['htmlPath']."htmlib"; // Default path for html templates
$GLOBALS['appsDir']   = $GLOBALS['shared']."apps/soft/"; // Default path for 3rd party soft in validation
$GLOBALS['internalTools'] = $GLOBALS['shared']."apps/internalTools/"; // Default path for internal tool's code

$GLOBALS['tool_submission_file'] = ".submit"; // Default name for runtime job submission file
$GLOBALS['tool_config_file']     = ".config.json"; // Default name for runtime config file
$GLOBALS['tool_log_file']        = ".tool.log"; //Default name for runtime execution log file
$GLOBALS['tool_stageout_file']   = ".results.json"; // Default name for runtime results file
$GLOBALS['tool_metadata_file']   = ".input_metadata.json"; // Default name for runtime metadata file


// Tool and visualizer status
$GLOBALS['tool_status'] = Array(
		0  => "Coming soon",
		1  => "Active",
		2   => "Disabled",
		3   => "Testing"
);

// Accepted values for 'compression' attribute
$GLOBALS['compressions'] = Array(
               "zip"   => "ZIP",
               "bz2"   => "BZIP2",
               "gz"    => "GZIP",
               "tgz"   => "TAR,GZIP",
               "tar.gz"=> "TAR,GZIP",
               "tbz2"  =>   "TAR,BZIP2",
               "tar.bz2" => "TAR,BZIP2",
               "tar.Z" => "TAR,ZIP",
               "rar"   => "RAR",
               "tar"   => "TAR"
);

// User Roles
$GLOBALS['ROLES'] = array(
		"0"=>"Admin",
		"1"=>"Tool Dev.",
		"2"=>"Common",
		"3" =>"Anonymous"
);
$GLOBALS['NO_GUEST'] = array(0,1,2,100,101); // 100, 101?
$GLOBALS['PREMIUM'] = array(0,1);
$GLOBALS['ADMIN'] = array(0);
$GLOBALS['TOOLDEV'] = array(1);

// Styling
$GLOBALS['ROLES_COLOR']          = array("0"=>"blue", "1"=>"grey-cascade", "2"=>"", 100=>"red-haze", 101=>"yellow-haze");
$GLOBALS['STATES_COLOR']         = array("0"=>"font-red", "1"=>"font-green-meadow", "2"=>"font-blue-steel", 3=>"font-green-meadow", 4=>"font-yellow-mint");
$GLOBALS['FILE_MSG_COLOR']       = array("0"=>"note-danger", "1"=>"note-info", "2"=>"note-success", 3=>"note-info");
$GLOBALS['placeholder_input']    = "Click right button to select file"; // text default
$GLOBALS['placeholder_textarea'] = "Click right button to select file(s)"; // text default


/*******************************
// Project specific definitions
********************************/

// Get Data from Repositories
// $GLOBALS['EUBI_XNAT_API']    = "https://xnat.bmia.nl";
// $GLOBALS['EGA_METADATA_API'] = "https://ega-archive.org/metadata/v2";
// $GLOBALS['EGA_METADATA_API_TEST'] = "https://test.ega-archive.org/metadata/v2";  # TODO: ask Alejandro

// Cloud infrastructures
$GLOBALS['cloud']              = "my_on_premises_cloud"; // VRE central cloud. Options are any of $GLOBALS['clouds']
$GLOBALS['clouds'] = Array(
		'my_on_premises_cloud' => array(
			"http_host"	    => "www.mydomain.com",	     // used in getCurrentCloud
			"dataDir_host"        => "/home/ubuntu/dockerized_vre/volumes/shared_data/userdata/", // export path for NFS server
			"pubDir_host"         => "/home/ubuntu/dockerized_vre/volumes/shared_data/public/",   // export path for NFS server
			"dataDir_virtual"   => $GLOBALS['dataDir'],
			"pubDir_virtual"    => $GLOBALS['pubDir'],
			"PMESserver_domain" => "pmes.mydomain.com",
			"PMESserver_port"   => "80",
			"PMESserver_address"=> "/",
			"imageTypes" 	    => array(),                      // list of cloud OCCI templates indexed by RAM (GB)
			"auth"	            => array("required" => False)
		),
		'marenostrum' => array(
			"http_host"	    => "mn1.bsc.es",  	                            // used in getCurrentCloud
			"dataDir_fs"        => "/home/\$REMOTE_USERNAME/openvre/userdata/", // export path for NFS server
			"pubDir_fs"         => "/home/\$REMOTE_USERNAME/openvre/public/",   // export path for NFS server
			"dataDir_virtual"   => $GLOBALS['dataDir'],
			"pubDir_virtual"    => $GLOBALS['pubDir'],
			"auth"	            => array("required" => True,
						     "accessible_via" => "SSH",
						     "credentials_file" => "??????" // /shared_data/USERID/.vault/dt01.bsc.es.secret

					       )
		),
		'marenostrum_dt' => array(
			"http_host"	    => "dt01.bsc.es",  	             // used in getCurrentCloud
			"port" 		    => "22",
			"mn_dir"	    => "/home", 
			"dataDir_fs"        => "/openvre/userdata/", // export path for NFS server
			"pubDir_fs"         => "/openvre/public/",   // export path for NFS server
			"dataDir_virtual"   => $GLOBALS['dataDir'],
			"pubDir_virtual"    => $GLOBALS['pubDir'],
			"auth"	            => array("required" => True,
						     "accessible_via" => "SSH",
						     "credentials_file" => "??????" // /shared_data/USERID/.vault/dt01.bsc.es.secret

					       )
		)

);
