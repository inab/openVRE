<?php

// UTILITIES
//

// get files from $fileList matching with given file type
function matchFormat_File($type, $fileList) {

	$output = [];	
    // from tool, return empty and select from modal

	if(empty($fileList)) return "";

	// from ws / rerun, match format file with format tool field (type)
	foreach ($fileList as $file) {

		if(isset($file["fn"])) {

			if(preg_grep("/".$file['format']."/i" , $type)) {
		
				$p = explode("/", $file['path']);
                
				$proj = getProject($p[1]);

				$a[0] = $proj['name']. " / $p[2] / $p[3]";
				$a[1] = $file['fn'];

				$output[] = $a;

				//return $output;

			}

		}

	}

	return $output;

}

// format PHP array to JS array
function getArrayJS($array) {

	return preg_replace('/"/', '\'', json_encode($array));

}


// HEADER FUNCTIONS 
//

// check if data request is correct
function InputTool_checkRequest($request) {
	if (!isset($request['fn']) && !isset($request['rerunDir']) && !isset($request['op'])){
		$_SESSION['errorData']['Error'][]="Please, before running this tool, select the correct files from the workspace or launch tool from the side menu.";
		redirect($GLOBALS["BASEURL"].'workspace/');
	}
}

// get if user is coming from ws, rerun or tool
function InputTool_getOrigin($request) {

	$from = "";

	// coming from workspace
	if (isset($request['fn'])){
		$from = "workspace";
	}

	// coming from rerun
	if (isset($request['rerunDir'])){
		$from = "rerun";
	}

	// coming from tool
	if (isset($request['op'])){
		$from = "tool";
	}

	return $from;
}

function InputTool_getPathsAndRerun($request) {

	$output = [];
	$output[0] = [];
	$output[1] = [];

	if (isset($request['rerunDir']) && $request['rerunDir']){
		$dirMeta = $GLOBALS['filesMetaCol']->findOne(array('_id' => $request['rerunDir'])); 
		if (!is_array($dirMeta['input_files']) && !isset($dirMeta['arguments'])){
			$_SESSION['errorData']['Error'][]="Cannot rerun job ".$request['rerunDir'].". Some folder metadata is missing.";
			redirect($GLOBALS["BASEURL"].'workspace/');
		}
		if (is_array($dirMeta['input_files'][0])){
			$_SESSION['errorData']['Internal'][]="Cannot rerun job ".$request['rerunDir'].". New directory metadata not implemeted yet.";
			redirect($GLOBALS["BASEURL"].'workspace/');
		}
		foreach ($dirMeta['input_files'] as $fn){
			$file['path'] = getAttr_fromGSFileId($fn,'path');
			$file['fn'] = $fn;
			$file['format'] = getAttr_fromGSFileId($fn,'format');
			array_push($output[1],$file);
		}
		$output[0] = $dirMeta['arguments'];
	}else{
		if (!isset($request['fn']))
			$request['fn']=array();
		
		if (!is_array($request['fn']))
			$request['fn'][]=$request['fn'];

		foreach($request['fn'] as $fn){
			$file['path'] = getAttr_fromGSFileId($fn,'path');
			$file['fn'] = $fn;
			$file['format'] = getAttr_fromGSFileId($fn,'format');
			array_push($output[1],$file);
		}
		//array_push($output[1],getAttr_fromGSFileId($fn,'path'));
	}

	return $output;

}

function InputTool_getDefExName() {

	// default execution name
	$dirNum="000";
	$dataDirPath = getAttr_fromGSFileId($_SESSION['User']['dataDir'],"path");
	$reObj = new \MongoDB\BSON\Regex("^".$dataDirPath."\\/run\d\d\d$");
	$prevs  = $GLOBALS['filesCol']->find(array('path' => $reObj, 'owner' => $_SESSION['User']['id']), array('sort' => array('path' => -1)))->toArray();
	if ($prevs) {
	   foreach ($prevs as $p){
		$previous = $p;
		if (preg_match('/(\d+)$/',$previous["path"],$m) ){
			$dirNum= sprintf("%03d",$m[1]+1);
			break;
		}
	   }
	}
	$dirName="run".$dirNum;
	$prevs  = $GLOBALS['filesCol']->find(array('path' => $dataDirPath."/$dirName", 'owner' => $_SESSION['User']['id']))->toArray();
	if ($prevs){
		$dirName="run".rand(100, 999);
	}
	return $dirName;

}

// PROJECTS
//

// get list of projects
function InputTool_getSelectProjects() {

    $projects = getProjects_byOwner();

    $output = '<select class="form-control" id="select_project" name="project">';
    foreach ($projects as $project_id => $project){
        $selected="";
        $project_code = basename($project['path']);
        if ($project_code == $_SESSION['User']['activeProject'])
            $selected = " selected ";
        $output.="<option value=\"$project_code\" $selected >".$project['name']."</option>";
    }
	$output .='</select>';

	echo $output;	

}

// FORM GENERIC FUNCTIONS
//

// print select file(s)
function InputTool_printSelectFile($input, $rerun, $ff, $multiple, $required) {

	$req = "field_not_required";
	if($required) $req = "field_required";

	if(!$multiple) {

		$labelopt = (!$required) ? " (optional)" : "";

		$output = '<div class="form-group">
			<label class="control-label">'.$input['description'].$labelopt.' <i class="icon-question tooltips" data-container="body" data-html="true" data-placement="right" data-original-title="<p align=\'left\' style=\'margin:0\'>'.$input['help'].'</p>"></i></label>
			<div class="input-group">
					<span class="input-group-addon"><i class="fa fa-file"></i></span>
					<input type="text" 
						name="visible_'.$input['name'].'" 
						class="form-control form-field-enabled '.$req.'" 
						placeholder="'.$GLOBALS['placeholder_input'].'" 
						value="'.$ff[0].'"
						readonly>
					<input type="hidden" class="form-field-enabled" name="input_files['.$input['name'].']" value="'.$ff[1].'">
					<span class="input-group-btn input-tool">
						<a href="javascript:cleanInput(\'visible_'.$input['name'].'\', \'input_files['.$input['name'].']\', 1);" class="clean-input"><i class="fa fa-times-circle"></i></a>
						<button class="btn green" type="button" 
						onclick="toolModal(\'visible_'.$input['name'].'\', \'input_files['.$input['name'].']\', '.getArrayJS($input['data_type']).', '.getArrayJS($input['file_type']).', false);"><i class="fa fa-check-square-o"></i> Select</button>
					</span>
			</div>
		</div>';

	} else {

		$p = [];
                $r = 0;
                foreach($ff as $fi) {
                	$p[] = $fi[0];
                        $r ++;
                }
		$textarea_height= ($r > 1 ? $r*34 : 34);

		$labelopt = (!$required) ? " (optional)" : "";
	

		$output = '<div class="form-group">
			<label class="control-label">'.$input['description'].$labelopt.' <i class="icon-question tooltips" data-container="body" data-html="true" data-placement="right" data-original-title="<p align=\'left\' style=\'margin:0\'>'.$input['help'].'</p>"></i></label>
			      <div class="input-group">
					<span class="input-group-addon"><i class="fa fa-file"></i></span>
					<textarea 
						name="visible_'.$input['name'].'"
						class="form-control form-field-enabled field_required" 
						style="height:'.$textarea_height.'px"
						placeholder="'.$GLOBALS['placeholder_input'].'" 
						readonly>'.implode("\n", $p).'</textarea>
					<div id="hidden_visible_'.$input['name'].'">';
		foreach($ff as $fi) { 
			$output.='	<input type="hidden" class="form-field-enabled" name="input_files['.$input['name'].'][]" value="'.$fi[1].'">';
		}
		$output.='		</div>
					<span class="input-group-btn input-tool">
						<a href="javascript:cleanInput(\'visible_'.$input['name'].'\', \'input_files['.$input['name'].'][]\', 0);" class="clean-input"><i class="fa fa-times-circle"></i></a>
						<button class="btn green" type="button" 
						onclick="toolModal(\'visible_'.$input['name'].'\', \'input_files['.$input['name'].'][]\','. getArrayJS($input['data_type']).', '.getArrayJS($input['file_type']).', true);"><i class="fa fa-check-square-o"></i> Select</button>
					</span>
			</div>
			</div>';

	}

	echo $output;
}

// print list of files (in select) 
function InputTool_printListOfFiles($input, $rerun, $required) {

	$req = "field_not_required";
	if($required) $req = "field_required";

	$output = '<div class="form-group">
		<label class="control-label">'.$input['description'].' <i class="icon-question tooltips" data-container="body" data-html="true" data-placement="right" data-original-title="<p align=\'left\' style=\'margin:0\'>'.$input['help'].'</p>"></i></label>
		<div class="input-group">
			<span class="input-group-addon"><i class="fa fa-file"></i></span>
			<select name="input_files_public_dir['.$input['name'].']" class="form-control '.$req.'">
				<option value="">Select the '.$input['description'].'</option>';

				$tool_options = $input['enum_items'];
				for ($i=0; $i<count($tool_options['name']); $i++){

					if($tool_options['name'][$i] == $rerun) $sel = "selected";
					else $sel = "";

					$output .= '<option value="'.$tool_options['name'][$i].'" '.$sel.'>'.$tool_options['description'][$i].'</option>';
				} 

	$output .= '</select>
		</div>
	</div>';

	echo $output;

}

// print input
function InputTool_printInput($input, $type) {

	$req = "field_not_required";
	if($input["required"]) $req = "field_required";

	$max  = ""; 
	$min  = "";
	$range= "";
	$step = "";

	if(isset($input["maximum"]) && isset($input["minimum"])) {
		$max = $input["maximum"]; 
		$min = $input["minimum"];
		$range = 'min="'.$min.'" max="'.$max.'"';
	}

	if($type == "number") {

		if(isset($max) && isset($min)) {
			if(($max - $min) > 10) $st = 1;
			if(($max - $min) < 10 && ($max - $min) > 1) $st = 0.1;
			if(($max - $min) < 1) $st = 0.01;
		} else {
			$st = "any";
		}

		$step = 'step="'.$st.'"';

	}

	if( isset($input['default']) && ($input['default'] !== null) && ($input['default'] !== "null")) $value = $input['default'];
	else $value = "";

	$output = '<div class="form-group">
				<label class="control-label">'.$input['description'].' <i class="icon-question tooltips" data-container="body" data-html="true" data-placement="right" data-original-title="<p align=\'left\' style=\'margin:0\'>'.$input['help'].'</p>"></i></label>
				<input type="'.$type.'" '.$range.' '.$step.' name="arguments['.$input['name'].']" id="'.str_replace(":", "_", $input['name']).'" class="form-control form-field-enabled '.$req.'" value="'.$value.'">
				</div>';

	return $output;

}

// print input
function InputTool_printInputHidden($input, $type) {

	$req = "field_not_required";
	if($input["required"]) $req = "field_required";

	if(($input['value'] !== null) && ($input['value'] !== "null")) $value = $input['value'];
	else $value = "";

	$output = '<input type="hidden" name="arguments['.$input['name'].']" id="'.str_replace(":", "_", $input['name']).'" class="form-control '.$req.'" value="'.$value.'">';

	return $output;

}

// print select
function InputTool_printSelect($input) {

	$req = "field_not_required";
	if(isset($input["required"]) && $input["required"]) $req = "field_required";

	if(($input['default'] !== null) && ($input['default'] !== "null")) $default = $input['default'];
	else $default = "";

	if($default === "false") $default = [0];
	if($default === "true") $default = [1];

	if($input["type"] == "boolean") {
		$tool_options["name"] = [1, 0];
		$tool_options["description"] = ["True", "False"];
	} else {
		$tool_options = $input['enum_items'];
	}

	$output = '<div class="form-group">
				<label class="control-label">'.$input['description'].' <i class="icon-question tooltips" data-container="body" data-html="true" data-placement="right" data-original-title="<p align=\'left\' style=\'margin:0\'>'.$input['help'].'</p>"></i></label>
				<select  name="arguments['.$input['name'].']" id="'.str_replace(":", "_", $input['name']).'" class="form-control '.$req.'">';
				$sel = "";
				for ($i=0; $i<count($tool_options['name']); $i++) {
					if(($default != "") && in_array($tool_options['name'][$i], $default)) $sel = "selected";
					else $sel = "";
					$output .= '<option value="'.$tool_options['name'][$i].'" '.$sel.'>'.$tool_options['description'][$i].'</option>';
				}
	$output .= '</select>
		</div>';

	return $output;

}

// print select multiple
function InputTool_printSelectMultiple($input) {

	$req = "field_not_required";
	if($input["required"]) $req = "field_required";

	if(($input['default'] !== null) && ($input['default'] !== "null") && ($input['default'] !== "")) $default = array_values($input['default']);
	else $default = "";

	$tool_options = $input['enum_items'];

	$output = '<div class="form-group">
				<label class="control-label">'.$input['description'].' <i class="icon-question tooltips" data-container="body" data-html="true" data-placement="right" data-original-title="<p align=\'left\' style=\'margin:0\'>'.$input['help'].'</p>"></i></label>
				<select  name="arguments['.$input['name'].'][]" id="'.str_replace(":", "_", $input['name']).'" class="form-control '.$req.'" multiple="multiple">';
				$sel = "";
				for ($i=0; $i<count($tool_options['name']); $i++) {
					if(($default != "") && in_array($tool_options['name'][$i], $default)) $sel = "selected";
					else $sel = "";
					$output .= '<option value="'.$tool_options['name'][$i].'" '.$sel.'>'.$tool_options['description'][$i].'</option>';
				}
	$output .= '</select>
		</div>';

	return $output;

}

// print field
function InputTool_printField($input, $rerun) {

	if(isset($input["required"]) && $input["required"]) $req = "field_required";

	switch($input["type"]) {

		case 'string': $field = "input";
			 $type = "text";
			 break;
		case 'enum': 
		case 'boolean': $field = "select";
			break;
		case 'enum_multiple': $field = "select_multiple";
			break;	
		case 'integer':
		case 'number': $field = "input";
			 $type = "number";
			 break;
		case 'hidden': $field = "input";
			 $type = "hidden";
			 break;
	}

	switch($field) {

		case "input": if($rerun) $input["default"] = $rerun;
			if($type == "hidden") $output = InputTool_printInputHidden($input, $type);
			else $output = InputTool_printInput($input, $type);	
			break;

		case "select": if($rerun) $input["default"] = [$rerun];
			$output = InputTool_printSelect($input);
			break;

		case "select_multiple": if($rerun) $input["default"] = $rerun;
			$output = InputTool_printSelectMultiple($input);
			break;
	}
	return $output;

}

// print the whole form for standard tools
function InputTool_printSettings($arguments, $rerun) {

	$output = '';

	$c = 0;
	foreach($arguments as $arg) {

		if(($c % 2) == 0) $output .= '<div class="row">';

		$output .= '<div class="col-md-6">';
		$output .= InputTool_printField($arg, $rerun[$arg['name']]);
		$output .= '</div>';

		if(($c % 2) != 0) $output .= '</div>';

		$c ++;

	}

	echo $output;

}


