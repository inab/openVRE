<?php
header('Content-Type: application/json');

require __DIR__ ."/../../../../config/bootstrap.php";
require __DIR__."/../config/bootstrap.php";

if($_REQUEST) {
	// Get list of Phenotypes & Genes.
    if ($_REQUEST['action'] == "getPhenoGeneAssociations"){
        $var = $GLOBALS['phenoGeneCol'];
        $res = getPhenoGeneAssociations($var);
        echo json_encode($res);
        exit;

	// Get user info
    } elseif(isset($_REQUEST['action']) && $_REQUEST['action'] == "getUser") {
        echo getUser($_SESSION['User']['id']);
	exit;
    
    // Get list of Variants for selected genes.
    }elseif((isset($_REQUEST['action']) && $_REQUEST['action'] == "getGeneVariants" && $_REQUEST['gene'])){
        $var = $GLOBALS['geneProteinVariantsCol'];
        $var2 = $_REQUEST['gene'];
        $res = getGeneVariants($var, $var2);
        echo json_encode($res);
        exit;

    // Get list of Variants for selected genes.
    }elseif ((isset($_REQUEST['action']) && $_REQUEST['action'] == "getFilteredGenes" && $_REQUEST['phenotype'])){
        $var = $GLOBALS['geneProteinVariantsCol'];
        $var2 = $_REQUEST['phenotype'];
        $res = getFilteredGenes($var, $var2);
        echo json_encode($res);
        exit;

    // Get PDB. -- OBSOLETE?? 
    }elseif ((isset($_REQUEST['action']) && $_REQUEST['action'] == "getPBDs" && $_REQUEST['gene_ids'] && $_REQUEST['variant_ids'])){
        $var = $GLOBALS['geneProteinVariantsCol'];
        $var2 = $_REQUEST['phenotype'];
        $res = getFilteredGenes($var, $var2);
        echo json_encode($res);
        exit;

    // Get PDB structures 
    }elseif (isset($_REQUEST['action']) && $_REQUEST['action'] == "getStructures" && isset($_REQUEST['geneName']) ){
	$r = getUniprotData_fromGeneName($_REQUEST['geneName']);
	print $r;

	$r = json_decode($r,TRUE);
	$uniprot_id = $r['body']['Entry'];
	$r = getPDBsData_fromUniprotId($uniprot_id);
	print $r;
	exit;
    
    }
}
echo '{}';
exit;

