<?php

/************************
// Settings
************************/


// Main config
$GLOBALS['SERVER']    = "https://dev-openebench.bsc.es"; // host 
$GLOBALS['BASEURL']   = "/submission/"; // prefix
$GLOBALS['AppPrefix'] = "OpEB"; // project abbreviation
$GLOBALS['NAME']      = "OpenEBench Submission VRE"; // project name 
$GLOBALS['SITETITLE'] = "OpenEBench Submission | Virtual Research Environment"; // site title
$GLOBALS['TIMEOUT']   = 3600; // session and cookies timeout

// Email
$GLOBALS['mail_credentials'] = __DIR__."/mail.conf"; // SMTP credentials
$GLOBALS['FROMNAME']  = "OEB submission"; // 'From' for VRE tickets and notifications
$GLOBALS['ADMINMAIL'] = "openebench@bsc.es"; // BBC address for VRE ticket emails

// SGE
$GLOBALS['queueTask']  = "local.q"; //default queue

// Mongo databases
$GLOBALS['db_credentials'] = __DIR__."/mongo.conf"; // Mongo access 
$GLOBALS['dbname_VRE']     = "oeb_submission_dev"; // Database name

//VRE installation paths
$GLOBALS['root']       = dirname(__DIR__); // VRE root directory
$GLOBALS['logFile']    = $GLOBALS['root']."/logs/VRE.log"; // Log file path 
$GLOBALS['shared']     = "/gpfs/vre-dev/"; // VRE data directory
$GLOBALS['dataDir']    = $GLOBALS['shared']."userdata/"; // User data directory
$GLOBALS['pubDir']     = $GLOBALS['shared']."public/"; // Public data directory
$GLOBALS['sampleData'] = $GLOBALS['shared']."sampleData/"; // Tool dataset directory 
$GLOBALS['sampleData_default'] = "basic"; // Default workspace's dataset entry

// File manager config
$GLOBALS['DISKLIMIT']       = 100*1024*1024*1024; // Default user disk quote (GB)
$GLOBALS['DISKLIMIT_ANON']  = 50*1024*1024*1024; // Default not-registerd disk quote (GB)
$GLOBALS['MAXSIZEUPLOAD']   = 4000; // Maximum upload file size (MB)
$GLOBALS['caduca']          = "182"; // Expiration date for user files (days)
$GLOBALS['project_default'] = "MyFirstProject"; // Default name for user project
$GLOBALS['tmpUser_dir']     = ".tmp/"; // Default name for user temporal forder

// Tool integration models and templates
$GLOBALS['tool_json_schema']    = "/home/user/VRE/install/data/tool_schemas/tool_specification/tool_schema.json"; // data model for tool registration
$GLOBALS['tool_io_json_schema'] = "/home/user/VRE/install/data/tool_schemas/tool_specification/tool_schema_io.json"; // data model for tool registration - only I/O definition
$GLOBALS['tool_dev_sample']     = "/home/user/VRE/install/data/tool_schemas/tool_specification/examples/example.json"; // template for tool registration - step 3
$GLOBALS['tool_io_dev_sample']  = "/home/user/VRE/install/data/tool_schemas/tool_specification/examples/example_io.json"; // template for tool registration - step 1 I/O

// Oauth2 authentification
$GLOBALS['auth_credentials']       = __DIR__."/oauth2.conf"; // oauth2 client credentials
$GLOBALS['authAdmin_credentials']  = __DIR__."/oauth2_admin.conf"; // oauth2 client credentials with admin privileges
$GLOBALS['authServer']             = 'https://inb.bsc.es/auth'; // external oauth2 server
$GLOBALS['authRealm']              = 'openebench'; // keycloak realm
$GLOBALS['urlAuthorize' ]          = $GLOBALS['authServer'].'/realms/'.$GLOBALS['authRealm'].'/protocol/openid-connect/auth';     //get autorization_code
$GLOBALS['urlAccessToken']         = $GLOBALS['authServer'].'/realms/'.$GLOBALS['authRealm'].'/protocol/openid-connect/token';    //get token
$GLOBALS['urlResourceOwnerDetails']= $GLOBALS['authServer'].'/realms/'.$GLOBALS['authRealm'].'/protocol/openid-connect/userinfo'; //get user details
$GLOBALS['urlLogout']              = $GLOBALS['authServer'].'/realms/'.$GLOBALS['authRealm'].'/protocol/openid-connect/logout';   //close keyclok session   
$GLOBALS['adminToken']             = $GLOBALS['authServer']."/realms/master/protocol/openid-connect/token"; // get Admin token
$GLOBALS['adminRealm']             = $GLOBALS['authServer']."/admin/realms/".$GLOBALS['authRealm']; // admin keycloak users


/************************
// Definitions
************************/


// Default names and local path for VRE
$GLOBALS['URL']       = $GLOBALS['SERVER'].$GLOBALS['BASEURL']; // full VRE URL 
$GLOBALS['URL_login'] = $GLOBALS['URL']."/login.php"; // Default for auth server login
$GLOBALS['htmlPath']  = $GLOBALS['root']. "/public"; // Default path for public folder
$GLOBALS['htmlib']    = $GLOBALS['htmlPath']."/htmlib"; // Default path for html templates
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
		"zip"  => "ZIP",
		"bz2"  => "BZIP2",
		"gz"   => "GZIP",
		"tgz"  => "TAR,ZIP",
		"tbz2" => "TAR,BZIP2"
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

// Reference Genomes
$GLOBALS['refGenomes'] = $GLOBALS['pubDir']."refGenomes/"; // Public data: assemblies
$GLOBALS['refGenomes_names'] = Array(
		'R64-1-1' => "Saccharomyces cerevisiae (R64-1-1)",
		'hg19'    => "Homo Sapiens (hg19 / GRCh37)",
		'hg38'    => "Homo Sapiens (hg38 / GRCh38)",
		'r5.01'   => "Drosophila Melanogaster (r5.01 / dm3)",
		'dm6'     => 'Drosophila Melanogaster (BDGP r6 + ISO1 MT / dm6)',
		'danRer11'=> "Zebra Fish (GRCz11 / danRer11)",
		'mm10'    => 'Mouse (GRCm38 / mm10)',
		'mm9'     => 'Mouse (NCBI37 / mm9)',
		'xenTro9' => 'Xenopus tropicalis (v9.1 / xenTro9)',
		'spombe'  => 'Schizosaccharomyces pombe (ASM294v2)',
		'0'       => 'Other'
	);


// MuG cloud infrastructures
$GLOBALS['cloud']              = "life-bsc"; // VRE central cloud. Options are any of $GLOBALS['clouds']
$GLOBALS['clouds'] = Array(
		'life-bsc' => array(
			"http_host"	    => "dev-openebench.bsc.es",	       // used in getCurrentCloud
			"dataDir_fs"        => "/data/cloud/apps/noroot/elixir_benchmarking_submission-dev/submission_userdata", //export path for NFS server
			"pubDir_fs"         => "/data/cloud/apps/noroot/elixir_benchmarking_submission-dev/submission_public",   //export path for NFS server
			"dataDir_virtual"   => "/MUG_USERDATA",
			"pubDir_virtual"    => "/MUG_PUBLIC",
			"PMESserver_domain" => "multiscalegenomics.bsc.es",
			"PMESserver_port"   => "80",
			"PMESserver_address"=> "pmes/",
			"imageTypes" 	    => array(),
			"auth"	            => array("required" => False)
	),

		'mug-irb' => array(
			"http_host"	 => "dev.multiscalegenomics.eu",	    // used in getCurrentCloud
			"dataDir_fs"	=> "/NAmmb5/services/MuGdev/MuG_userdata", //export path for NFS server
			"pubDir_fs"	 => "/NAmmb5/services/MuGdev/MuG_public",   //export path for NFS server
			"dataDir_virtual"   => "/orozco/services/MuGdev/MuG_userdata",
			"pubDir_virtual"    => "/orozco/services/MuGdev/MuG_public",
			"PMESserver_domain" => "192.168.11.236",
			"PMESserver_port"   => "8080",
			"PMESserver_address"=> "pmes/",
			"imageTypes"	=>  array(
				"2"  => array(
					"1"  => array("id" => "small", "disk" => null, "name" => "small"),
					"2"  => array("id" => "small-small", "disk" => null, "name" => "small-small"),
					"4"  => array("id" => "medium-small", "disk" => null, "name" => "medium-small"),
					"8"  => array("id" => "large-small", "disk" => null, "name" => "large-small"),
					"16" => array("id" => "extra_large-small", "disk" => null, "name" => "large-small")
				    ),
				"4"  => array(
					"2"  => array("id" => "medium", "disk" => null, "name" => "medium"),
					"1"  => array("id" => "tiny-medium", "disk" => null, "name" => "tiny-medium"),
					"2"  => array("id" => "small-medium", "disk" => null, "name" => "small-medium"),
					"4"  => array("id" => "medium-medium", "disk" => null, "name" => "medium-medium"),
					"8"  => array("id" => "large-medium", "disk" => null, "name" => "large-medium"),
					"16" => array("id" => "extra_large-medium", "disk" => null, "name" => "extra_large-medium")
				    ),
				"8"  => array(
					"4"  => array("id" => "large", "disk" => null, "name" => "large")
				    ),
				"10"  => array(
					"1"  => array("id" => "tiny-large", "disk" => null, "name" => "tiny-large"),
					"2"  => array("id" => "small-large", "disk" => null, "name" => "small-large"),
					"4"  => array("id" => "medium-large", "disk" => null, "name" => "medium-large"),
					"8"  => array("id" => "large-large", "disk" => null, "name" => "large-large"),
					"16" => array("id" => "extra_large-large", "disk" => null, "name" => "extra_large-large")
				    ),
				"16"  => array(
					"4"  => array("id" => "extra_large", "disk" => null, "name" => "extra_large"),
					"8"  => array("id" => "mammoth", "disk" => null, "name" => "mammoth")
				    ),
				"50"  => array(
					"1"  => array("id" => "tiny-max", "disk" => null, "name" => "tiny-max"),
					"2"  => array("id" => "small-max", "disk" => null, "name" => "small-max"),
					"4"  => array("id" => "medium-max", "disk" => null, "name" => "medium-max"),
					"8"  => array("id" => "large-max", "disk" => null, "name" => "large-max"),
					"16" => array("id" => "extra_large-max", "disk" => null, "name" => "extra_large-max")
				    ),
				"64"  => array(
					"16" => array("id" => "goliath", "disk" => null, "name" => "goliath")
				    )
			),
			"auth" => array("required" => False)
		),

		'mug-ebi' => array(
			"http_host"	 => "",	 // used in getCurrentCloud
			"dataDir_fs"	=> "/ifs/BSC-MuG/", //export path for NFS server
			"pubDir_fs"	 => "/ifs/BSC-MuG/",   //export path for NFS server
			"dataDir_virtual"   => "/MUG_USERDATA",
			"pubDir_virtual"    => "/MUG_PUBLIC",
			"PMESserver_domain" => "193.62.52.104",
			"PMESserver_port"   => "8080",
			"PMESserver_address"=> "pmes/",
			"imageTypes"	=>  array(
				"1"  => array(
					"1"  => array("id" => "e9ca7478-7957-4237-b3d0-d4767e1de65f", "disk" => "10", "name" => "si.tiny")
				    ),
				"2"  =>array(
					"1"  => array("id" => "58fa000a-b038-4482-82a2-dbea0dd27ac3", "disk" => "20", "name" => "c1.surechembl.1C2R"),
					"2"  => array("id" => "721112dd-2f33-40eb-8975-7bd34dbabfc8", "disk" => "20", "name" => "s1.small"),
					"10" => array("id" => "6550a617-3499-41c4-9c18-10e2312e01ad", "disk" => "15", "name" => "c1.cems")
				   ),
				"4"  =>array(
					"2"  => array("id" => "43890936-a6d7-41d4-8cb6-0c050466e697", "disk" => "30", "name" => "s1.modest"),
					"4"  => array("id" => "bba3e111-9247-40b7-9e55-9c5a1fa8bcfe", "disk" => "40", "name" => "s1.medium")
				   ),
				"6"  =>array(
					"4"  => array("id" => "91ba172b-cb4c-453c-b7fc-56cb79c78968", "disk" => "50", "name" => "s1.capacious")
				   ),
				"8"  =>array(
					"2"  => array("id" => "81e62624-582e-458b-80c0-9ff21f3e70c7", "disk" => "60", "name" => "c1.large"),
					"4"  => array("id" => "6a36101a-21c7-4b97-ac4d-9343fe784028", "disk" => "60", "name" => "s1.large")
				   ),
				"12" =>array(
					"6" => array("id" => "fa85f5f4-4560-4e1b-af95-21df6f714727", "disk" => "70", "name" => "s1.jumbo")
				   ),
				"16" =>array(
					"2" => array("id" => "35e1f3c1-e303-46f7-a128-66569f4e7628", "disk" => "80", "name" => "c1.surechembl.2C16R"),
					"4" => array("id" => "75259e96-3ef2-49c7-a548-373dc204201f", "disk" => "100","name" => "c1.surechembl.4C16R"),
					"8" => array("id" => "f3fcc537-c1fc-4108-a174-eb5bf52e7481", "disk" => "80", "name" => "s1.huge"),
					"16"=> array("id" => "04b5c511-387d-41da-800d-a675bb2f3a27", "disk" => "60", "name" => "c1.PhenoMeNal.12C16R")
				   ),
				"24" =>array(
					"4" => array("id" => "6c5da012-1f31-4834-83e7-3b368d4daed7", "disk" => "100", "name" => "c1.surechembl.4C24R")
				   ),
				"32" =>array(
					"2" => array("id" => "f680ac49-d943-462f-873d-ee9cd90adc5f", "disk" => "100", "name" => "c1.surechembl.2C32R"),
					"4" => array("id" => "3f8da13e-caac-4703-b6dd-2cd922ea76c2", "disk" => "100", "name" => "c1.surechembl.4C32R"),
					"8" => array("id" => "c511a528-319b-4378-b5ff-1f87ac3288af", "disk" => "100", "name" => "s1.massive")
				  ),
				"36" =>array(
					"22"=> array("id" => "c43b6066-987b-40d1-ab40-90c75358dc78", "disk" => "60", "name" => "c1.PhenoMeNal.22C36R")
				 ),
				"64" =>array(
					"16"=> array("id" => "1b5cfe48-58fe-4778-b18a-f1067d80ebfa", "disk" => "100", "name" => "s1.gargantuan"),
				)
			),
			"auth"  => array(
				"required"       => True,
				"OS_NO_CACHE"    => "True",
				"OS_CLOUDNAME"   => "overcloud",
				"OS_AUTH_URL"    => "https://extcloud05.ebi.ac.uk:13000/v2.0",
				"NOVA_VERSION"   => "1.2",
				"COMPUTE_API_VERSION" => "1.2",
				"OS_USERNAME"    => "laia.codo@bsc.es",
				"OS_PASSWORD"    => "VLZtKndy",
				"OS_TENANT_NAME" => "BSC-MuG" 
			)
	    )
);
