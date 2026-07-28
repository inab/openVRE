<?php

require_once __DIR__ . "/../../vendor/autoload.php";

$connectionUri = "mongodb://" . getenv('MONGO_CREDENTIALS') . "@" . getenv('MONGO_SERVER') . "/?authSource=" . getenv('MONGO_MAIN_DB');
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

// create handlers

$dbname = getenv('MONGO_MAIN_DB');

$GLOBALS['db']              = $VREConn->$dbname;
$GLOBALS['usersCol']        = $GLOBALS['db']->users;
$GLOBALS['filesCol']        = $GLOBALS['db']->files;
$GLOBALS['filesMetaCol']    = $GLOBALS['db']->filesMetadata;
$GLOBALS['logMailCol']      = $GLOBALS['db']->checkMail;
$GLOBALS['toolsCol']        = $GLOBALS['db']->tools;
$GLOBALS['visualizersCol']  = $GLOBALS['db']->visualizers;
$GLOBALS['fileFormatsCol']    = $GLOBALS['db']->file_formats;
$GLOBALS['dataTypesCol']    = $GLOBALS['db']->data_types;
$GLOBALS['helpsCol']        = $GLOBALS['db']->helps;
$GLOBALS['sampleDataCol']   = $GLOBALS['db']->sampleData;
$GLOBALS['logExecutionsCol'] = $GLOBALS['db']->log_executions;
//adding new cred for SITES collection
$GLOBALS['sitesCol']   = $GLOBALS['db']->sites;
