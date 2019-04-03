<?php

require __DIR__."/../../config/bootstrap.php";
redirectOutside();

$e = $GLOBALS['studiesCol']->findOne(array('_id'=>strtoupper($_GET['id'])));

header('Content-Type: application/json');
echo json_encode($e, JSON_PRETTY_PRINT);
