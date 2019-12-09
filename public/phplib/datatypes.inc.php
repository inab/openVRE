<?php

/////////////////////////////////
/////// QUERY FILETYPES / DATATYPES
/////////////////////////////////

function getFileTypesList() {

	$ft = $GLOBALS['fileTypesCol']->find(array(),array('_id' => 1));

	return iterator_to_array($ft);

}

function getDataTypesList() {

	$dt = $GLOBALS['dataTypesCol']->find(array(),array('_id' => 1));

	return iterator_to_array($dt);

}

function getDataTypeFromFileType($filetype) {

	$dt = $GLOBALS['dataTypesCol']->find(array('file_types' => array('$in' => array($filetype))),array('_id' => 1));

	return iterator_to_array($dt);

}

function getFileTypeFromExtension($fileExtension) {

	$dt = $GLOBALS['fileTypesCol']->find(array('extension' => array('$in' => array($fileExtension))),array('_id' => 1));

	return iterator_to_array($dt);

}

function getDataTypeName($datatype) {
	$dt = $GLOBALS['dataTypesCol']->findOne(array('_id' => $datatype), array('name' => 1));
	if (isset($dt['name'])){
		return $dt['name'];
	}else{
		return $datatype;
	}

}

function getFeaturesFromDataType($datatype, $filetype) {

	$dt = $GLOBALS['dataTypesCol']->findOne(array('_id' => $datatype), array('assembly' => 1, 'taxon_id' => 1, 'paired' => 1, 'sorted' => 1, ));

	$res = array();

	$res["_id"] = $dt["_id"];
	$res["taxon_id"] = false;
	$res["assembly"] = false;
	$res["paired"] = false;
	$res["sorted"] = false;


	if (isset($dt["taxon_id"])) $res["taxon_id"] = true;
	if (isset($dt["assembly"]) && in_array($filetype,$dt["assembly"])) $res["assembly"] = true;
	if (isset($dt["paired"]  ) && in_array($filetype,$dt["paired"])  ) $res["paired"] = true;
	if (isset($dt["sorted"]  ) && in_array($filetype,$dt["sorted"])  ) $res["sorted"] = true;

	return $res;

}
