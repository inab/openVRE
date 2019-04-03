<?php


function parseSHFile_PMES($rfn){

	$SH_parsed = array();
    $json = json_decode(trim(file_get_contents($rfn)));

    $json_array = json_decode(json_encode($json[0]), True);
    foreach ($json_array as $k => $v_obj){
        $v     = json_encode($v_obj, JSON_PRETTY_PRINT);
        $v_txt = "<pre style='white-space: pre-wrap;'>$v</pre>";
        $SH_parsed[$k]=$v_txt;
    }
    return $SH_parsed;
}

// TOOL DEVELOPERS

function getInputMetadata($input) {
	
	$output = "";	

	foreach($input as $k => $v) {

		if((is_array($v) && $input["type"] != "enum" && $input["type"] != "enum_multiple") ||
			(is_array($v) && $k == "default" && ($input["type"] == "enum" || $input["type"] == "enum_multiple")))
			$v = implode(", ", $v); 

		if(is_array($v) && $k == "enum_items" && ($input["type"] == "enum" || $input["type"] == "enum_multiple")) {

			$ei = "";		
	
			foreach($v as $kei => $vei) {

				$ei .= $kei;
				$ei .= " (".implode(", ", $vei).")<br>";

			}

			$ei = rtrim($ei, "<br>");
			$v = $ei;

		}

		if(is_bool($v)) $v = $v ? 'true' : 'false';

		$output .= "<strong>$k:</strong> $v<br>";

	}

	return $output;

}

function getArgument($arg, $class = null, $defval, $input_name = null) {

	if(!$input_name) $input_name = "arguments";

	// llegir required (*)
	$output = '';

	switch($arg["type"]) {

		case "integer":
		case "number":

			if(isset($arg["default"])) 
				$val = 'value="'.$arg["default"].'"'; 
			else 
				$val = 'placeholder="default value"';

			if($defval) $val = 'value="'.$defval.'"';

			$output .= '<input 
										type="number" 
										name="'.$input_name.'['.$arg["name"].']" 
										'.$val.'
										class="form-control '.$class.'" >';
			break;

		case "string":

			if(isset($arg["default"])) 
				$val = 'value="'.$arg["default"].'"'; 
			else 
				$val = 'placeholder="default value"';

			if($defval) $val = 'value="'.$defval.'"';

			$output .= '<input 
										type="text" 
										name="'.$input_name.'['.$arg["name"].']" 
										'.$val.'
										class="form-control '.$class.'" >';
			break;

		case "boolean":

			$options["name"] = [1, 0];
			$options["description"] = ["True", "False"];

			if(isset($defval)) {
				$val = ($defval == 1) ? ["True"] : ["False"];
			} else { $val = $arg["default"]; }

			if($val == null) {
				$noselected = true;
				$val = ["True"];
			}

			$output .= '<select  
										name="'.$input_name.'['.$arg["name"].']" 
										class="form-control '.$class.'">';
										for ($i=0; $i<count($options['name']); $i++) {
											/*if(strtolower($options["description"][$i]) == strtolower($val)) $sel = "selected";
											else $sel = "";*/
											if(in_array($options['name'][$i], $val)) $sel = "selected";
											else $sel = "";

											if($noselected) $sel = "";

											$output .= '<option value="'.$options["name"][$i].'" '.$sel.'>'.$options["description"][$i].'</option>';
										}
			$output .= '</select>';
			break;
	
		case "enum":

			$options = $arg["enum_items"];

			if($defval) $val = array($defval);
			else $val = $arg["default"];

			if(!$val) $val = [];

			$output .= '<select  
										name="'.$input_name.'['.$arg["name"].']" 
										class="form-control '.$class.'">';
										for ($i=0; $i<count($options['name']); $i++) {
											if(in_array($options['name'][$i], $val)) $sel = "selected";
											else $sel = "";
											if(isset($options["description"])) $output .= '<option value="'.$options["name"][$i].'" '.$sel.'>'.$options["description"][$i].'</option>';
											else $output .= '<option value="'.$options["name"][$i].'" '.$sel.'>'.$options["name"][$i].'</option>';
										}
			$output .= '</select>';
			break;

		case "enum_multiple":

			$options = $arg["enum_items"];

			if($defval) $val = $defval;
			else $val = $arg["default"];

			if(!$val) $val = [];

			$output .= '<select  
				name="'.$input_name.'['.$arg["name"].'][]" 
				class="form-control select-multiple" 
				multiple="multiple">';
			for ($i=0; $i<count($options['name']); $i++) {
				if(in_array($options['name'][$i], $val)) $sel = "selected";
				else $sel = "";
				$output .= '<option value="'.$options["name"][$i].'" '.$sel.'>'.$options["description"][$i].'</option>';
			}
			$output .= '</select>';
			break;
	

	}

	return $output;

}

function generateLogo($toolid) {

	$result = $GLOBALS['toolsDevMetaCol']->findOne(array("_id" => $toolid));

	$path = $GLOBALS['dataDir']."/".$result["user_id"]."/.dev/".$toolid."/logo/";
	if(!file_exists($path)) mkpath($path);

	$text = ($result["step3"]["tool_spec"]? $result["step3"]["tool_spec"]["name"]: $toolid);
	$tsize = strlen($text);
	if($tsize < 5) $tsize = 5;

	// image size
	$w = 600;
	$h = 600;
	// Create the image
	$im = imagecreatetruecolor($w, $h);

	// Create some colors
	$background = imagecolorallocate($im, 255, 255, 255);
	$color = imagecolorallocate($im, 0, 107, 143);

	imagefilledrectangle($im, 0, 0, $w, $w, $background);

	// Font size
	$fsize = intval(1500/$tsize);
	// The text to draw
	$text = strtoupper($text);
	// Replace path by your own font path
	$font = __DIR__.'/../assets/global/fonts/Deutschlander.ttf';

	// calculating x-position
	$tb = imagettfbbox($fsize, 0, $font, $text);
	$x = ceil(($w - $tb[2]) / 2); // lower left X coordinate for text

	$y = ($h/2)+((abs($tb[5] - $tb[1]))/2);

	// Add the text
	imagettftext($im, $fsize, 0, $x, $y, $color, $font, $text);

	// Using imagepng() results in clearer text compared with imagejpeg()
	imagepng($im, $path.'logo.png');
	imagedestroy($im);
}

?>
