<?php

// use identifiers.org to resolve a compacted URI
function URI_to_URL_via_identifiers($uri,$silent=false){

	$url=null;

	// set resolver identifier.org
	$resolver_validator = "https://identifiers.org/rest/identifiers/validate/";

	// parse URI
	list($prefix,$ref) = null;
	if (preg_match('/^([^:]*):([^:]*)$/',$uri,$m)){
		$prefix = $m[1];
		$ref = $m[2];
	}else{
		$_SESSION['errorData']['Error'][]="Cannot resolve compacted URI '$uri'. 'prefix' not found as in : <prefix>:<accession_id>";
		return $url;
	}

	// get effective URL from resolver
	list($r, $info) = get($resolver_validator."/".$uri);

	if ($r == "0") {
	  if ($_SESSION['errorData']['Error']) {
	    $err = array_pop($_SESSION['errorData']['Error']);
	    logger("ERROR:" . $err);
	  }
	  if ($info['http_code'] != 200) {
	    logger("ERROR: Unexpected http code. HTTP code: " . $info['http_code']);
	    logger("ERROR: GET_RESPONSE = '" . strip_tags($r) . "'");
	  }
	  return $url;
	}

	// process response
	$r = json_decode($r,true);
	if ($r['url']){
		$url = $r['url'];
	}elseif($r['message']){
		if (!$silent){
			$_SESSION['errorData']['Error'][]="Cannot resolve compated URI '$uri' via identifiers.org. Returns: ".$r['message'];
		}
	}else{
		$_SESSION['errorData']['Error'][]="Cannot resolve compated URI '$uri' via identifiers.org for unknown reasons";
	}

	// return URL or null
	return $url;
}

// use OEB idsolv to resolve a compacted URI
function URI_to_URL_via_idsolv($uri,$silent=false){

	$url=null;

	// set resolver OpEB idsolv
	$resolver = "https://dev-openebench.bsc.es/api/scientific/idsolv";

	// parse URI
	list($prefix,$ref) = null;
	if (preg_match('/^([^:]*):([^:]*)$/',$uri,$m)){
		$prefix = $m[1];
		$ref = $m[2];
	}else{
		$_SESSION['errorData']['Error'][]="Cannot resolve compacted URI '$uri'. 'prefix' not found as in : <prefix>:<accession_id>";
		return $url;
	}

	// get effective URL from resolver

	#curl -Ls --head -w %{url_effective} https://dev-openebench.bsc.es/api/scientific/idsolv/PDB.data:1ERT
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $resolver."/".$uri); 
	curl_setopt($ch, CURLOPT_HEADER, true); //get header
	curl_setopt($ch, CURLOPT_NOBODY, true); //do not include response body
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    	curl_setopt($ch, CURLOPT_VERBOSE,true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	$curl_data = curl_exec($ch);

	$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	if ($status != 200){
		if (!$silent){
			$_SESSION['errorData']['Error'][]="Cannot resolve compated URI '$uri' via dev-openebench.bsc.es/api/scientific/idsolv";
		}
		return $url;
	}

	$url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL); //extract effective url from header
	curl_close($ch);

	// return URL or null
	return $url;

}
