<?php

// read credentials
$conf = array();
if (($F = fopen($GLOBALS['db_credentials'], "r")) !== FALSE) {
    while (($data = fgetcsv($F, 1000, ";")) !== FALSE) {
	foreach ($data as $a){
        	$r = explode(":",$a);
                if (isset($r[1])){array_push($conf,$r[1]);}
	}
    }
    fclose($F);
}   

// connect DB
try {
	$VREConn =  new MongoDB\Client("mongodb://".$conf[0].":".$conf[1]."@".$conf[2].":27017",
					array(),
					array('typeMap' => array ('root'     => 'array',
								  'document' => 'array',
						    		  'array'    => 'array')
					     )
				      );

} catch (MongoConnectionException $e){
	//die('Error Connecting Mongo DB: ' . $e->getMessage());
	header('Location: '.$GLOBALS['BASEURL'].'/htmlib/errordb.php?msg=Cannot connect to VRE MuG database');	

} catch (MongoException $e) {
	die('Error: ' . $e->getMessage());
}

// create handlers

$dbname =  $GLOBALS['dbname_VRE'];

$GLOBALS['db']              = $VREConn->$dbname;
$GLOBALS['usersCol']        = $GLOBALS['db']->users;
$GLOBALS['countriesCol']    = $GLOBALS['db']->countries;
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
$GLOBALS['logExecutionsCol']= $GLOBALS['db']->log_executions;

?>
