<?php
//////////////////////////////////////////
// Querying Cardiovascular GWAS MongoDB // 
//////////////////////////////////////////

function getPhenoGeneAssociations($var){
    $response="{}";
    $files="{}";
    $files = $var->find()->toArray();
    return $files;
}

function getGeneVariants($var, $var2){
    $response="{}";
    $files="{}";
    $files = $var->findOne(array('gene' => $var2));
    //$files = $var->find()->toArray();
    return $files;
}

function getFilteredGenes($var, $var2){
    $response="{}";
    $files="{}";
    //$files = $var->find(array('variants.statistical.phenotype' => $var2));
    $files = $var->find(array('variants.statistical.phenotype' => $var2))->toArray();
    //$files = $var->find()->toArray();
    return $files;
}

function getPDBs($var, $var2){
    $response="{}";
    $files="{}";
    //$files = $var->find(array('variants.statistical.phenotype' => $var2));
    //$files = $var->find(array('variants.statistical.phenotype' => $var2))->toArray();
    //$files = $var->find()->toArray();
    return $files;
}
