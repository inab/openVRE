<?php

function getCommunities()
{

  $res = array();
  $data_string =
    '{ "query" : 
            "{ 
                getCommunities {
                    _id
                    acronym
                    status
                    name
                }
            }"
        }';

  $headers = array(
    'Content-Type: application/json',
    'Content-Length: ' . strlen($data_string)
  );

  list($r, $info) = post($data_string, $GLOBALS["OEB_sciapi"], $headers);

  logger("RESPONSE => " . json_encode($r) . "'");


  if ($r == "0") {
    if ($_SESSION['errorData']['Error']) {
      $err = array_pop($_SESSION['errorData']['Error']);
      logger("ERROR:" . $err);
    }
    if ($info['http_code'] != 200) {
      logger("ERROR: Unexpected http code. HTTP code: " . $info['http_code']);
      logger("ERROR: calling PMES. POST_RESPONSE = '" . strip_tags($r) . "'");
    }
  }

  $response = json_decode($r)->data->getCommunities;
  if ($response) {
    foreach ($response as $object) {
      $res[$object->_id] =  (array)$object;
    }
  }

  return $res;
}


function getDatasets()
{
  $data_string =
    '{ "query" : 
  "{ 
    getDatasets(datasetFilters:{visibility:\"public\"}){ 
      _id 
      community_ids 
      visibility 
      name 
      version 
      description 
      type
      datalink {
        uri
        inline_data
      } 
    } 
  }"
}';




  $headers = array(
    'Content-Type: application/json',
    'Content-Length: ' . strlen($data_string)
  );

  list($r, $info) = post($data_string, $GLOBALS["OEB_sciapi"], $headers);

  logger("RESPONSE => " . json_encode($r) . "'");


  if ($r == "0") {
    if ($_SESSION['errorData']['Error']) {
      $err = array_pop($_SESSION['errorData']['Error']);
      logger("ERROR:" . $err);
    }
    if ($info['http_code'] != 200) {
      logger("ERROR: Unexpected http code. HTTP code: " . $info['http_code']);
      logger("ERROR: calling PMES. POST_RESPONSE = '" . strip_tags($r) . "'");
    }
  }

  //var_dump($r);


  return json_decode($r)->data->getDatasets;
  
}
