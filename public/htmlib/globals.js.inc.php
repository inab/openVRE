<?php
header("Content-Type: application/javascript");
require __DIR__."/../../config/bootstrap.php";

/*******************************************/
/*          ADMIN ROLES VARIABLES          */
/*******************************************/

// COUNTRIES VAR
$countries = array();
$countries[''] = 'Country';
foreach (array_values(iterator_to_array($GLOBALS['countriesCol']->find(array(),array('country'=>1),array('country'=>1)) )) as $v){
	$countries[$v['_id']] = $v['country'];
}

echo 'var countriesSelect = \'<select style="width: 100%!important;" class="selector form-control input-sm input-xsmall input-inline" id="select-countries">';
foreach($countries as $key => $value){
	$value = str_replace("'","&#39;",$value);
	echo '<option value="'.$key.'">'.$value.'</option>';
}
echo '</select>\';';

// ROLES VAR (LIST)
echo '

var rolesList = \'<ul class="dropdown-menu" role="menu">';
foreach($GLOBALS['ROLES'] as $k => $v):
	echo '<li>';
	echo '<a class="role-usr role'.$k.'" href="javascript:;">'.$v.'</a>';
	echo '</li>';
endforeach;
echo '</ul>\';';

// ROLES VAR (SELECT)
echo '

var rolesSelect = \'<select style="width: 100%!important;" class="selector form-control input-sm input-xsmall input-inline" id="select-type-user"><option value="">Role</option>';
foreach($GLOBALS['ROLES'] as $k => $v):
	echo '<option value="'.$k.'">'.$v.'</option>';
endforeach;
echo'</select>\';';

// ROLES COLORS

echo '

var rolesColor = {';
foreach($GLOBALS['ROLES_COLOR'] as $k => $v):
	if($k == '2') echo $k.':null,';
	else  echo $k.':"'.$v.'",';
endforeach;
echo '};';

// INITIAL DISK LIMIT

echo '

var diskLimit = '.$GLOBALS['DISKLIMIT'].';';

// MAX UPLOAD SIZE

echo '

var maxUpSize = '.$GLOBALS['MAXSIZEUPLOAD'].';';

// FILE STATE COLORS

echo '

var fileStateColor = {';
foreach($GLOBALS['STATES_COLOR'] as $k => $v):
	echo $k.':"'.$v.'",';
endforeach;
echo '};';

// FILE FEEDBACK MESSAGE COLORS

echo '

var fileMessageColor = {';
foreach($GLOBALS['FILE_MSG_COLOR'] as $k => $v):
	echo $k.':"'.$v.'",';
endforeach;
echo '};';



/*******************************************/
/*          DATA TABLES VARIABLES          */
/*******************************************/

echo '

var allFiles = [];';

echo '

var table = "";';

/*******************************************/
/*           DASHBOARD VARIABLES           */
/*******************************************/

$count_tou = 0;
echo '

var labelsUsersPieChart = {';
foreach($GLOBALS['ROLES'] as $k => $v):
	echo $count_tou.':\''.$v.'\',';
	$count_tou ++;
endforeach;
echo '};';


echo '

var baseURL = \''.$GLOBALS['BASEURL'].'\';';


?>
