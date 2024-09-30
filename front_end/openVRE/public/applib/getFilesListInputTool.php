<?php 

require __DIR__."/../../config/bootstrap.php";

redirectOutside();

$toolsHelp = getSingleTool_Help($_REQUEST["toolID"], $_REQUEST["op"]);

$dt_list = json_decode($_REQUEST["dt_list"]);
$ft_list = json_decode($_REQUEST["ft_list"]);
$multiple = json_decode($_REQUEST["multiple"]);
$file_selected = json_decode($_REQUEST["file_selected"]);

$files_list = getGSFiles_filteredBy(array("data_type" => array('$in' => $dt_list),"format" => array('$in' => $ft_list), "visible"   => true));


//$files_list = getFilesFromDT($dt_list);

/*foreach ($files_list as $file) {
	var_dump($file["_id"]);
}*/

$list = [];
$file_arr = [];
$project_arr = [];
$id_arr = [];
$execution_arr = [];

//if($from == "workspace") {

//var_dump($files_list);

foreach ($files_list as $file) {

	$path = getAttr_fromGSFileId($file["_id"], 'path');
	$p = explode("/", $path);

	$a = [];
	$a["id"]       = $file["_id"];
	$a["execution"]= $p[2];
	$a["file"]     = $p[3];
	$a["description"] = $file["description"];
	$dt = $GLOBALS['dataTypesCol']->findOne(array('_id' => $file["data_type"]));
	$a["data_type"] = $dt['name'];
    
	$proj_code = $p[1];
    $project   = getProject($proj_code);

    if (isset($project['name'])){
    	$a["project_name"]  = $project['name'];
    }else{
    	$a["project_name"]  = "Foo project";
    }

	$list[] = $a;
	// Alejandro: Select All fn -> Create individual lists.
	$file_arr[] = $a["file"];	
	$project_arr[] = $a["project_name"];
	$id_arr[] = $a["id"];
	$execution_arr[] = $a["execution"];
}

// Alejandro: Select All fn -> Implode for creating a unique string from an array.
$files = implode(" ",$file_arr);
$projects = implode(" ",$project_arr);
$ids = implode(" ",$id_arr);
$executions = implode(" ",$execution_arr);

$count1 = 0;
$count2 = 0;
$init_all = "";
$count1 = count($file_arr);
$count2 = count($file_selected);

if($count2 == $count1){
	$init_all = "checked";
}

// TABLE

$html = '<table id="workspace_st2" class="display" cellspacing="0" width="100%">';

	$html .= '<thead>';
		$html .= '<tr id="headerSearch">';
			$html .= '<th style="background-color: #eee;padding:3px;"></th>';
			$html .= '<th style="background-color: #eee;padding:3px;" class="inputSearch">Files</th>';
			$html .= '<th style="background-color: #eee;padding:3px;" class="selector">Project</th>';
			$html .= '<th style="background-color: #eee;padding:3px;" class="selector">Execution</th>';
		$html .= '</tr>';
    $html .= '<tr id="heading">';
			
	  // Alejandro: Select All fn -> Adding HTML and event listener call (onclick="changeAllCheckbox")
	  // ORIGINAL -> $html .= '<th></th>';
	  $html .= '<th style="padding-left: 15px;"> 
	  <label class="mt-checkbox mt-checkbox-outline" style="margin-bottom: 0px; font-weight: bold">
		  All
		  <input type="checkbox" class="checkboxes" '.$init_all.' onclick="changeAllCheckbox(this, \''.$files.'\', \''.$ids.'\', \''.$projects.'\', \''.$executions.'\' )" />
		  <span></span>
	  </label>
	  </th>';

      $html .= '<th>File</th>';
      $html .= '<th>Project</th>';
      $html .= '<th>Execution</th>';
		$html .= '</tr>';
  $html .= '</thead>';

	
	$html .= '<tbody>';

	$selectedFiles = [];

		foreach($list as $file) { 

			$tr_class = "";
			$file_sel = "";
			
			if(in_array($file["id"], $file_selected)) {

				$tr_class = "input_highlighted";
				$file_sel = "checked";

				$a = [];
				$a["fileName"] = $file["file"];
				$a["fileID"] = $file["id"];
				$a["filePath"] = $file["project_name"]. ' / '.$file["execution"];

				$selectedFiles[] = $a;

			} 

			$html .= '<tr class="row-clickable '.$tr_class.'">';
			if($multiple) { 
				$html .= '<td>';
				$html .= '<label class="mt-checkbox mt-checkbox-single mt-checkbox-outline">';
					$html .= '<input type="checkbox" class="checkboxes" '.$file_sel.' value="false" onchange="changeCheckbox(this, \''.$file["file"].'\', \''.$file["id"].'\', \''.$file['project_name'].' / '.$file["execution"].' /\')" />';
					$html .= '<span></span>';
				$html .= '</label>';
				$html .= '</td>';
			} else { 
				$html .= '<td>';
				$html .= '<label class="mt-radio mt-radio-outline">';
					$html .= '<input type="radio" name="filesRadios" '.$file_sel.' value="'.$file["id"].'" onchange="changeRadio(\''.$file["file"].'\', \''.$file["id"].'\', \''.$file['project_name'].' / '.$file["execution"].' /\')" />';
					$html .= '<span></span>';
				$html .= '</label>';
				$html .= '</td>';
			}
			$html .= '<td>'.$file["file"].' <a href="javascript:;" onmouseover="javascript:;" class="tooltips" data-trigger="hover" data-container="body" 
																					data-html="true" data-placement="right" data-original-title="<p align=\'left\' style=\'margin:0\'><strong>'.$file["data_type"].'</strong><br>'.$file["description"].'</p>"><i class="fa fa-info-circle"></i></a>';
			$html .= '<td>'.$file['project_name'].'</td>';
			$html .= '<td>'.$file["execution"].'</td>';
			$html .= '</tr>';

		} 

	$html .= '</tbody>';
	
	
$html .= '</table>';

// TOOL HELP

$thelp = '<table class="table">';
	$thelp .= '<thead>';
		$thelp .= '<tr>';
			$thelp .= '<th>Operations</th>';
			$thelp .= '<th>File(s) required</th>';
			$thelp .= '<th>File format</th>';
			$thelp .= '<th>File type</th>';
		$thelp .= '</tr>';
	$thelp .= '</thead>';
	$thelp .= '<tbody>';
					
					$count = 0;
					foreach($toolsHelp as $th) {
						
							$cc = 1;
							foreach($th["content"] as $content) { 
							if($cc == 1) { $trclass = "first-tr"; }else{ $trclass = ""; } 
							$thelp .= '<tr class="'.$trclass.'">';
								if($cc == 1) { 
								$thelp .= '<td rowspan="'.sizeof($th["content"]).'">'.$th["operation"].'</td>';
								}
								$thelp .= '<td>'.$content["description"].'</td>';
								$thelp .= '<td>'.implode("<br>", $content["format"]).'</td>';
								$thelp .= '<td>'.implode("<br>", $content["data_type"]).'</td>';
							$thelp .= '</tr>';
							
							$cc ++;
							}
							
																
					}

				$thelp .= '</tbody>';
			$thelp .= '</table>';

echo '{"table":'.json_encode($html).', "selectedFiles":'.json_encode($selectedFiles).', "array":'.json_encode($out).', "help": '.json_encode($thelp).'}';

