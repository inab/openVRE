<?php

#
# Get UNIPROT Code (+ basic info) from given Gene Name

function getUniprotData_fromGeneName($gene_name,$only_one_uniprot=true){
	$response = new JsonResponse();
	
	$uniprot_data=array();

	# Query Uniprot API
	$query = "gene_exact:$gene_name+AND+organism:9606"; 
	$url = $GLOBALS['uniprot_api']."?query=$query&columns=id,entry%20name,reviewed,protein%20names,genes,organism,length,database(PDB)&format=tab&sort=score";
	list($r,$info)=get($url);
	#var_dump($r);
	
	if ($info['http_code'] == 404){
		$response->setCode(404);
		$response->setMessage("The resource you requested could not be found in UNIPROT. Query= $url");
		return $response->getResponse();
	}elseif($info['http_code'] != 200){
		$response->setCode($info['http_code']);
		$response->setMessage("Unexpected error while querying UNIPROT API. Query= $url");
		return $response->getResponse();
	}
	# Parse response: split by TAB
	$lines = explode(PHP_EOL, $r);
	if (!preg_match('/^Entry/',$lines[0])){
		$response->setCode(422);
		$response->setMessage("Bad format in UNIPROT response. Contact the administrators. Query= $url");
		return $response->getResponse();
	}
	
	# Select 1st item from the list of entries associated to the given gene 
	$headers=explode("\t", trim(array_shift($lines)));
	foreach($lines as $line){
		$line_array = explode("\t", trim($line));
		$entry= array();
		for($i=0;$i<count($headers);$i++){
			$entry[$headers[$i]] = $line_array[$i];
		}
		if ($only_one_uniprot) {
			$uniprot_data = $entry;
			break;
		}
		$uniprot_data[]=$entry;
	}
	#var_dump("\n\n==> SELECTED UNIPROT ID", $uniprot_data);

	$response->setBody($uniprot_data);	
	return $response->getResponse();
}


# Get PDB Codes from given UNIPROT ID

function getPDBsData_fromUniprotId($uniprot_id){
	$response = new JsonResponse();

	$pdb_data=array();

	# Query MMB UNIPROT API
	$GLOBALS['mmb_uniprot_api']= "http://mmb.irbbarcelona.org/api/uniprot";
	$GLOBALS['mmb_pdb_api']="http://mmb.irbbarcelona.org/api/pdb";

	$url = $GLOBALS['mmb_uniprot_api']."/$uniprot_id/dbxref/PDBExt";

	list($r,$info)=get($url);
	#var_dump($r);

	if ($info['http_code'] != 200){
		$response->setCode($info['http_code']);
		$r = json_decode($r,true);
		if (isset($r['msg'])){
			$response->setMessage($r['msg']);
		}else{
			$response->setMessage("Unexpected error while querying MMB UNIPROT API. Query= $url");
		}
		return $response->getResponse();
	}

	# Parse Response: list of PDBs (including: Resolution, Type)
	$r = json_decode($r,true);
	if (!isset($r['dbxref.PDBExt'])){
		$response->setCode(422);
		$response->setMessage("Bad format in MMB UNIPROT API response. Contact the administrators. Query= $url");
		return $response->getResponse();
	}
	if (!is_array($r['dbxref.PDBExt']) || count($r['dbxref.PDBExt']) == 0){
		$response->setCode(404);
		$response->setMessage("Sorry, no associated 3D structures could be found to Protein=$uniprot_id in MMB UNIPROT API. Query= $url");
		return $response->getResponse();
	}
	$pdb_data = $r['dbxref.PDBExt'];
	#var_dump("\n\n==> INITIAL PDB DATA", $pdb_data);
	

	# Complete PDB code list with extra Info
	$full_pdb_data = array();

	foreach($pdb_data as $data){
		$pdb_code = $data["id"];

		# Adding Info: from Residue Mapping
		$url = $GLOBALS['mmb_uniprot_api']."/$uniprot_id/mapPDBRes?pdbId=$pdb_code";

		list($r,$info)=get($url);
		$chain_residues = json_decode($r,true);
		//var_dump("\n\n==> MAP-PDB-RES  pdbId=$pdb_code ",$chain_residues);

		if ($info['http_code'] != 200 || !is_array($chain_residues) || count($chain_residues) == 0){
		    if (preg_match_all("/[A-Z]/",$data['chains'],$chain_ids)){
			$data['chain_residues'] = array($data['chains']);
			$data['chain_ids']      = $chain_ids[0];
		     }
		}else{
		   foreach($chain_residues as $chain_name=>$c){
			$chain_id = substr($chain_name,-1);
			$data['chain_ids'][]      = $chain_id;
			$data['chain_residues'][] = "chain $chain_id (".$c[0]['length']." residues): ".$c[0]['pdb_start']." - ".$c[0]['pdb_end']."<br/>" ;
		   }
		}

		# Adding Info: Download Links
		$data['download']['URLs'][]  = $GLOBALS['mmb_pdb_api']."/$pdb_code";
		$data['download']['names'][] = $pdb_code;
		foreach ($data['chain_ids'] as $c){
			$data['download']['URLs'][] = $GLOBALS['mmb_pdb_api']."/$pdb_code"."_$c";
			$data['download']['names'][]= $pdb_code."_$c";
		}

		# Push Info
		$full_pdb_data[]=$data;
	}

	#var_dump("\n\n==> FINAL PDB DATA", $full_pdb_data);
	$response->setBody($full_pdb_data);
	return $response->getResponse();
}
