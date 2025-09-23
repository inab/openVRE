<?php


try {
	$connectionUri = "mongodb://" . getenv('MONGODB_CREDENTIALS') . "@" . getenv('MONGODB_SERVER') . ":" . getenv('MONGODB_PORT') . "/?authSource=" . getenv('MONGO_DB');
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

$dbname = getenv('MONGO_DB');

$GLOBALS['db']              = $VREConn->$dbname;
$GLOBALS['usersCol']        = $GLOBALS['db']->users;
$GLOBALS['filesCol']        = $GLOBALS['db']->files;
$GLOBALS['filesMetaCol']    = $GLOBALS['db']->filesMetadata;
$GLOBALS['logMailCol']      = $GLOBALS['db']->checkMail;
$GLOBALS['toolsCol']        = $GLOBALS['db']->tools;
$GLOBALS['toolsDevCol']     = $GLOBALS['db']->tools_dev;
$GLOBALS['toolsDevMetaCol'] = $GLOBALS['db']->tools_dev_meta;
$GLOBALS['visualizersCol']  = $GLOBALS['db']->visualizers;
$GLOBALS['fileTypesCol']    = $GLOBALS['db']->file_types;
$GLOBALS['dataTypesCol']    = $GLOBALS['db']->data_types;
$GLOBALS['helpsCol']        = $GLOBALS['db']->helps;
$GLOBALS['sampleDataCol']   = $GLOBALS['db']->sampleData;
$GLOBALS['logExecutionsCol'] = $GLOBALS['db']->log_executions;
//adding new cred for SITES collection
$GLOBALS['sitesCol']   = $GLOBALS['db']->sites;
