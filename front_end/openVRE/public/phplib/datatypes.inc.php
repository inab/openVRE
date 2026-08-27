<?php

/////////////////////////////////
/////// QUERY FILETYPES / DATATYPES
/////////////////////////////////

function getFileTypesList()
{

	$ft = $GLOBALS['fileFormatsCol']->find(array(), array('_id' => 1));

	return iterator_to_array($ft);
}

function getDataTypesList()
{

	$dt = $GLOBALS['dataTypesCol']->find(array(), array('_id' => 1));

	return iterator_to_array($dt);
}

function getDataTypeFromFileType($filetype)
{

	$dt = $GLOBALS['dataTypesCol']->find(array('formats' => array('$in' => array($filetype))), array('_id' => 1));

	return iterator_to_array($dt);
}

function getFileTypeFromExtension($fileExtension)
{
	$dataType = $GLOBALS['fileFormatsCol']->find(['extension' => ['$in' => [$fileExtension]], ['_id' => 1]]);
	return iterator_to_array($dataType);
}

function getDataTypeName($datatype)
{
	$dt = $GLOBALS['dataTypesCol']->findOne(array('_id' => $datatype), array('name' => 1));
	if (isset($dt['name'])) {
		return $dt['name'];
	} else {
		return $datatype;
	}
}
