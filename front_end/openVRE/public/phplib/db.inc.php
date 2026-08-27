<?php

require_once __DIR__ . "/../../vendor/autoload.php";

try {
	$connectionUri = getenv('MONGO_TLS_MODE') == "requireTLS"
		? "mongodb://" . getenv('MONGO_CREDENTIALS') . "@" . getenv('MONGO_SERVER') . "/?authSource=" . getenv('MONGO_MAIN_DB') . "&tls=true&tlsCAFile=" . getenv('MONGO_CA_FILE') . "&tlsCertificateKeyFile=" . getenv('MONGO_CERT_KEYFILE')
		: "mongodb://" . getenv('MONGO_CREDENTIALS') . "@" . getenv('MONGO_SERVER') . "/?authSource=" . getenv('MONGO_MAIN_DB');

	$VREConn =  new MongoDB\Client(
		$connectionUri,
		array(
			'readConcernLevel' => 'local'
		),
		array(
			'typeMap' => array(
				'root'     => 'array',
				'document' => 'array',
				'array'    => 'array'
			)
		)
	);
} catch (MongoConnectionException $e) {
	error_log($e->getMessage());
	header('Location: ' . $GLOBALS['BASEURL'] . '/htmlib/errordb.php?msg=Cannot connect to VRE MuG database');
} catch (MongoException $e) {
	die('Error: ' . $e->getMessage());
}

// create handlers

$dbname = getenv('MONGO_MAIN_DB');
$GLOBALS['mongodbClient']         = $VREConn;
$GLOBALS['db']              = $VREConn->$dbname;
$GLOBALS['usersCol']        = $GLOBALS['db']->users;
$GLOBALS['filesCol']        = $GLOBALS['db']->files;
$GLOBALS['filesMetaCol']    = $GLOBALS['db']->filesMetadata;
$GLOBALS['logMailCol']      = $GLOBALS['db']->checkMail;
$GLOBALS['toolsCol']        = $GLOBALS['db']->tools;
$GLOBALS['visualizersCol']  = $GLOBALS['db']->visualizers;
$GLOBALS['fileFormatsCol']  = $GLOBALS['db']->file_formats;
$GLOBALS['dataTypesCol']    = $GLOBALS['db']->data_types;
$GLOBALS['helpsCol']        = $GLOBALS['db']->helps;
$GLOBALS['sampleDataCol']   = $GLOBALS['db']->sampleData;
$GLOBALS['actionLogs']      = $GLOBALS['db']->action_logs;
$GLOBALS['rolePermissions']      = $GLOBALS['db']->role_permissions;
//adding new cred for SITES collection
$GLOBALS['sitesCol']   = $GLOBALS['db']->sites;
