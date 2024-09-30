<?php

use MongoDB\Driver\Manager;

// configure
//$GLOBALS['eush_cardiogwas_dbcredentials'] = __DIR__."/eush_cardiogwas_mongo.conf";
//$GLOBALS['eush_cardiogwas_dbname'] = "cardioGWAS";
//$GLOBALS['eush_cardiogwas_dbname_auth'] = "cardioGWAS";


// read credentials
$conf = array();
if (($F = fopen($GLOBALS['eush_cardiogwas_dbcredentials'], "r")) !== FALSE) {
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
	$cardioConn =  new MongoDB\Client("mongodb://".$conf[0].":".$conf[1]."@".$conf[2].":27017",
					array("authSource" => $GLOBALS['eush_cardiogwas_dbname_auth']),
					array('typeMap' => array ('root'     => 'array',
								  'document' => 'array',
						    		  'array'    => 'array')
					     )
				      );

} catch (MongoConnectionException $e){
	//die('Error Connecting Mongo DB: ' . $e->getMessage());
	header('Location: '.$GLOBALS['BASEURL'].'/htmlib/errordb.php?msg=Cannot connect to Cardio GWAS database');	
} catch (MongoException $e) {
	die('Error: ' . $e->getMessage());
}

// create handlers

$dbname =  $GLOBALS['eush_cardiogwas_dbname'];
$GLOBALS['eush_cardiogwas_db']     = $cardioConn->$dbname;

// collection handlers
$GLOBALS['phenoGeneCol']          = $GLOBALS['eush_cardiogwas_db']->phenoGene;
$GLOBALS['geneProteinVariantsCol']= $GLOBALS['eush_cardiogwas_db']->geneProteinVariants;

#$r = $GLOBALS['phenoGeneCol']->find()->toArray();
#var_dump($r);

?>
