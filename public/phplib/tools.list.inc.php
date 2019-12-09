<?php

//require "classes/Tooljob.php";

// list tools

function getTools_List($status = 1) {
   if ($_SESSION['User']['Type'] == 3){
       $tools = $GLOBALS['toolsCol']->find(array('external' => true, 'status' => $status, 'owner.license' => array('$ne' => "free_for_academics")), array('name' => 1, 'title' => 1, 'short_description' => 1, 'keywords' => 1),array('title' => 1));
	 } elseif($_SESSION['User']['Type'] == 0 || $_SESSION['User']['Type'] == 1) {
		 $tools = $GLOBALS['toolsCol']->find(array('external' => true, 'status' => $status), array('name' => 1, 'title' => 1, 'short_description' => 1, 'keywords' => 1, 'status' => 1),array('title' => 1));
	 } else {
       $tools = $GLOBALS['toolsCol']->find(array('external' => true, 'status' => $status), array('name' => 1, 'title' => 1, 'short_description' => 1, 'keywords' => 1),array('title' => 1));
   }

	 if($_SESSION['User']['Type'] == 1) {

		 $tools_list = iterator_to_array($tools);

		 foreach($tools_list as $key => $tool) {

			 if($tool["status"] == 3 && !in_array($tool["_id"], $_SESSION['User']["ToolsDev"])) {
				 unset($tools_list[$key]);
			 }

		 }

			return $tools_list;

	 } else {
		return iterator_to_array($tools);	
	 }

}

// list tools

function getTools_ListComplete($status = 1) {
   if ($_SESSION['User']['Type'] == 3){
       $tools = $GLOBALS['toolsCol']->find(array('external' => true, 'status' => $status, 'owner.license' => array('$ne' => "free_for_academics")), array(),array('title' => 1));
	 }elseif($_SESSION['User']['Type'] == 0 || $_SESSION['User']['Type'] == 1) {
	 		$tools = $GLOBALS['toolsCol']->find(array('external' => true, 'status' => array('$ne' => 2)), array(),array('title' => 1));
	 }else{
       $tools = $GLOBALS['toolsCol']->find(array('external' => true, 'status' => $status), array(),array('title' => 1));
   }

	if($_SESSION['User']['Type'] == 1) {

		 $tools_list = iterator_to_array($tools);

		 foreach($tools_list as $key => $tool) {

			 if($tool["status"] == 3 && !in_array($tool["_id"], $_SESSION['User']["ToolsDev"])) {
				 unset($tools_list[$key]);
			 }

		 }

			return $tools_list;

	 } else {
		return iterator_to_array($tools);	
	 }	

}

// list tools

function getTool_fromId($toolId,$indexByName=0) {
        $filterfields=array();
        $tool = $GLOBALS['toolsCol']->findOne(array('_id' => $toolId), $filterfields);
        
        if (empty($tool))
                return 0;

        if ($indexByName){
		$toolIndexed=Array();
                foreach ($tool as $attribute => $value){
                        if (is_array($value)){
			    $t=0;
                            foreach ($value as $v){
                                if (isset($v['name'])){
					$t=1;
                                        $toolIndexed[$attribute][$v['name']]=$v;
				}
                            }
			    if (!$t){
				$toolIndexed[$attribute]=$value;
			    }   
                        }else{
			    $toolIndexed[$attribute]=$value;
			}
                }
		$tool = $toolIndexed;
        }      
	return $tool;


}

// list visualizers

function getVisualizer_fromId($toolId,$indexByName=0) {
        $filterfields=array();
        $tool = $GLOBALS['visualizersCol']->findOne(array('_id' => $toolId)/*, $filterfields*/);
        
        if (empty($tool))
                return 0;

        if ($indexByName){
		$toolIndexed=Array();
                foreach ($tool as $attribute => $value){
                        if (is_array($value)){
			    $t=0;
                            foreach ($value as $v){
                                if (isset($v['name'])){
					$t=1;
                                        $toolIndexed[$attribute][$v['name']]=$v;
				}
                            }
			    if (!$t){
				$toolIndexed[$attribute]=$value;
			    }   
                        }else{
			    $toolIndexed[$attribute]=$value;
			}
                }
		$tool = $toolIndexed;
								}   
	return $tool;


}

// get Tool under development

function getToolDev_fromId($toolId,$indexByName=0) {
    $tool = $GLOBALS['toolsDevMetaCol']->findOne(array('_id' => $toolId));

    if (empty($tool))
        return 0;

    if ($indexByName){
        if ($tool["step1"]["tool_io"]){
		        $toolIndexed=Array();
                foreach ($tool['step1']['tool_io'] as $attribute => $value){
                        if (is_array($value)){
			                $t=0;
                            foreach ($value as $v){
                                if (isset($v['name'])){
					               $t=1;
                                   $toolIndexed[$attribute][$v['name']]=$v;
				                }
                            }
			                if (!$t){
				                $toolIndexed[$attribute]=$value;
			                }   
                        }else{
			                $toolIndexed[$attribute]=$value;
			            }
                }
		        $tool["step1"]["tool_io"] = $toolIndexed;
        }      
        if ($tool["step3"]["tool_spec"]){
		        $toolIndexed=Array();
                foreach ($tool['step3']['tool_spec'] as $attribute => $value){
                        if (is_array($value)){
			                $t=0;
                            foreach ($value as $v){
                                if (isset($v['name'])){
					               $t=1;
                                   $toolIndexed[$attribute][$v['name']]=$v;
				                }
                            }
			                if (!$t){
				                $toolIndexed[$attribute]=$value;
			                }   
                        }else{
			                $toolIndexed[$attribute]=$value;
			            }
                }
		        $tool["step3"]["tool_spec"] = $toolIndexed;
        }      
    }
	return $tool;
}

// delete Tool under development

function deleteToolDev($toolId) {
    $tool = $GLOBALS['toolsDevMetaCol']->findOne(array('_id' => $toolId));
    if (!$tool){
        $_SESSION['errorData']['Warning'][]="Cannot delete tool '$toolId'. Entry not found.";
        return 1;
    }
    // Clean associated dev files
    $dev_dir = $GLOBALS['dataDir']."/".$_SESSION['User']['id']."/.dev/".$toolId;
    if (is_dir($dev_dir)){
        exec ("rm -r \"$dev_dir\" 2>&1",$output);
   		if (error_get_last()){
            $_SESSION['errorData']['Error'][]="Cannot delete tool '$toolId'.";
			$_SESSION['errorData']['Error'][]=implode(" ",$output);
            return 0;
        }
    }

    // Delete from mongo
    $GLOBALS['toolsDevMetaCol']->deleteOne(array('_id'=> $toolId));

    $tool = $GLOBALS['toolsDevMetaCol']->findOne(array('_id' => $toolId));
    if ($tool){
        $_SESSION['errorData']['Error'][]="Cannot delete tool '$toolId' from DB. An error occurred.";
        return 0;
    }
    return 1;
}

// has tool custom visualizer

function hasTool_custom_visualizer($toolId){
        $has_custom_visualizer = $GLOBALS['toolsCol']->findOne(array('_id' => $toolId,
					            'output_files' =>array('$elemMatch' => array("custom_visualizer"=>true) )),
					       array('_id'=>1)
					     );
	return $has_custom_visualizer;
}


// launch tool - used for internal tools
 
function launchToolInternal($toolId,$inputs=array(),$args=array(),$outs=array(),$output_dir="",$logName=""){

	// Get tool.
    $tool = getTool_fromId($toolId,1);
    if (empty($tool)){
        $_SESSION['errorData']['Error'][]="Tool internal not specified or not registered. Please, register '$toolId'";
        return 0;
    }
    if ($tool['external'] !== false){
        $_SESSION['errorData']['Error'][]="Selected tool ($toolId) expected to be Internal but specification states: {'external':false}";
        return 0;
    }

    // Set Tool job - tmp working dir
    $execution = 0;   // internal tool do not create a execution folder
    $project   = 0;   // internal tool do not have an associated project
    $descrip = "Internal job execution of ".$tool['name'];

	$jobMeta  = new Tooljob($tool,$execution,$project,$descrip,$output_dir);

    
	// Set LogName
	if (strlen($logName)){
		$jobMeta->setLog($logName);
	}

	// Stage in (fake)  TODO

	// Checking files locally
	$files   = Array(); // distinct file Objs to stage in 
	foreach($inputs as $inName=>$inIds){
	    foreach($inIds as $inId){
		$file = getGSFile_fromId($inId);
		if (!$file){
		        $_SESSION['errorData']['Error'][]="Input file $inId does not belong to current user or has been not properly registered. Stopping internal tool execution";
			return 0;
		}
		$files[$file['_id']]=$file;
	    }
	}
	// Set input files
	$jobMeta->setInput_files($inputs,array(),array());
	if ($jobMeta->input_files == 0){
		$_SESSION['errorData']['Error'][]="Internal tool execution has no input files defined";
	        return 0;
	}
	
	// Set Arguments
	$args['working_dir']=$jobMeta->working_dir;

    $jobMeta->setArguments($args,$tool);

    // Create working_dir
	$jobId = $jobMeta->createWorking_dir();
	if (!$jobId){
		$_SESSION['errorData']['Error'][]="Cannot create tool temporal working dir";
	        return 0;
	}

	// Set outfiles metadata -- for register latter
	$jobMeta->setStageout_data($outs);

	// Setting Command line. Adding parameters

	$r = $jobMeta->prepareExecution($tool,$files);
	if($r == 0)
		return 0;

	// Launching Tooljob
	$pid = $jobMeta->submit($tool);
	if($pid == 0)
	        return 0;

	addUserJob($_SESSION['User']['_id'],(array)$jobMeta,$jobMeta->pid);

	return $jobMeta->pid;
}


function parse_configFile_OBSOLETE($configFile){
	$configParsed = array();

	// load config as json
	$config = json_decode(file_get_contents($configFile));

	// parse json
	$configParsed['input_files']= array();
	if ($config->input_files){
		foreach ($config->input_files as $input){
			if(!isset($configParsed['input_files'][$input->name]))
				$configParsed['input_files'][$input->name]=array();
			$input_fn = getAttr_fromGSFileId($input->value,'path');
			if ($input_fn)
				array_push($configParsed['input_files'][$input->name],str_replace($_SESSION['User']['id']."/","",$input_fn));
			else
				array_push($configParsed['input_files'][$input->name],$input->value);
		}
	}
	$configParsed['arguments']= array();
	if ($config->arguments){
		foreach ($config->arguments as $arg){
			$configParsed['arguments'][$arg->name] = $arg->value;
		}
	}
	return $configParsed;
}


function parse_submissionFile_SGE_OBSOLETE($rfn){
        $cmdsParsed = array();

        $cmds = preg_grep("/^\//",file($rfn));
        $cwd  = str_replace("cd ","",join("",preg_grep("/^cd /", file($rfn))));

        $n=1;
        foreach ($cmds as $cmd){

                $cmdsParsed[$n]['cmdRaw']    = $cmd;
                $cmdsParsed[$n]['cwd']       = $cwd;

                $cmdsParsed[$n]['prgName']   = "";      # tool executable name for table title
                $cmdsParsed[$n]['params']    = array(); # paramName=>paramValue

                if (preg_match('/^#/',$cmd))
                        continue;
                if (preg_match('/^(.[^ ]*) (.[^>]*)(\d*>*.*)$/',$cmd,$m)){
                        $executable =  ($m[1]? basename($m[1]):"No information" );
                        $paramsStr  =  ($m[2]? $m[2]:"" );
                        $log        =  ($m[3]? $m[3]:"" );

                        // parse executable file
                        $cmdsParsed[$n]['prgName']  = $executable;
                
                        // parse cmd params
                        foreach (explode("--",$paramsStr) as $p){
                                trim($p);
                                if (!$p)
                                        continue;
                                list($k,$v) = explode(" ",$p);
                                if (strlen($k)==0 && strlen($v)==0)
                                        continue;
                                if (!$v)
                                        $v="";
                                // if paramValue is a file, show only 'execution/filename'
                                $v  = str_replace($GLOBALS['dataDir']."/".$_SESSION['User']['id']."/","",$v);

                                // HACK; when rfn comes from sample data, filenames in cmd do not contain the right userId. Cutting filepath using explode
                                if (preg_match('/^\//',$v)){
                                        $execution = explode("/",$rfn);
                                        $v = $execution[count($execution)-2]."/".basename($v);
                                }
                                $cmdsParsed[$n]['params'][$k]=$v;
                        }
                }
                $n++;
        }
        return $cmdsParsed;
}

// list visualizers

function getVisualizers_List($status = 1) {
	
	$visualizers = $GLOBALS['visualizersCol']->find(array('external' => true, 'status' => $status), array('name' => 1, 'title' => 1, 'short_description' => 1, 'keywords' => 1), array('title' => 1));

	return iterator_to_array($visualizers);	
	
}

// list visualizers

function getVisualizers_ListComplete($status = 1) {
	
	$visualizers = $GLOBALS['visualizersCol']->find(array('external' => true, 'status' => $status), array(), array('title' => 1));

	return iterator_to_array($visualizers);	
	
}

// list file_types in use

function getFileTypes_List() {
	
	$tls = $GLOBALS['toolsCol']->find(array('external' => true), array('name' => 1, 'input_files' => 1), array('name' => 1));

	$tools = iterator_to_array($tls);

	sort($tools);

	$filetypes = array();

	$i = 0;

	foreach($tools as $t) {

		if(isset($t['input_files'])) {

			$filetypes[$i]['name'] = $t['name'];

			$types = array();

			foreach($t['input_files'] as $if) array_push($types, implode($if['file_type'])); 

			$filetypes[$i]['file_types'] = $types;

			$i ++;

		}

	}

	return $filetypes;

}

// list a tool input file combination

function getInputFilesCombinations($tool) {

	$descriptions = [];
	foreach($tool["input_files_combinations"] as $t) {

		$descriptions[] = $t["description"];

	}

	return implode("~", $descriptions); 

}

// list visualizers

function getVisualizerTableList($file_types, $visualizer = null) {

	$files_list = getGSFiles_filteredBy(array("format" => array('$in' => $file_types), "visible"   => true));

	$list = [];

	foreach ($files_list as $file) {
		
		$path = getAttr_fromGSFileId($file["_id"], 'path');
		$p = explode("/", $path);

		$a = [];
		$a["id"] = $file["_id"];
		$a["project"] = getProject($p[1])["name"];
		$a["execution"] = $p[2];
		$a["file"] = $p[3];
		$a["refGenome"] = $GLOBALS['refGenomes_names'][$file["refGenome"]];

		$list[] = $a;
	}

	$html = '<table id="workspace_st2" class="table display" cellspacing="0" width="100%">';

		$html .= '<thead>';
			$html .= '<tr id="headerSearch">';
				$html .= '<th style="background-color: #eee;padding:3px;width:60px;"></th>';
				$html .= '<th style="background-color: #eee;padding:3px;" class="inputSearch">Files</th>';
				$html .= '<th style="background-color: #eee;padding:3px;" class="selector">Project</th>';
				$html .= '<th style="background-color: #eee;padding:3px;" class="selector">Execution</th>';
				if($visualizer == "jbrowse") $html .= '<th style="background-color: #eee;padding:3px;" class="selector">Reference Genome</th>';
			$html .= '</tr>';
			$html .= '<tr id="heading">';
				$html .= '<th>';
					$html .= '<label class="mt-checkbox mt-checkbox-single mt-checkbox-outline" style="right:3px">';
						$html .= '<input type="checkbox" class="checkboxes" value="" onchange="toggleAllFiles(this);" />';
						$html .= '<span></span>';
					$html .= '</label>';
				$html .= '</th>';
				$html .= '<th>File</th>';
				$html .= '<th>Project</th>';
				$html .= '<th>Execution</th>';
				if($visualizer == "jbrowse") $html .= '<th>Reference Genome</th>';
			$html .= '</tr>';
		$html .= '</thead>';

		
		$html .= '<tbody>';

		$selectedFiles = [];

			foreach($list as $file) { 

				$tr_class = "";
				
				$html .= '<tr class="row-clickable '.$tr_class.'">';
					$html .= '<td>';
					$html .= '<label class="mt-checkbox mt-checkbox-single mt-checkbox-outline">';
						$html .= '<input type="checkbox" class="checkboxes" value="'.$file["id"].'" onchange="changeCheckbox(this, \''.$file["file"].'\', \''.$file["id"].'\', \'Project01 (TODO) / '.$file["execution"].' /\')" />';
						$html .= '<span></span>';
					$html .= '</label>';
					$html .= '</td>';
				$html .= '<td>'.$file["file"].'</td>';
				$html .= '<td>'.$file["project"].'</td>';
				$html .= '<td>'.$file["execution"].'</td>';
				if($visualizer == "jbrowse") $html .= '<td>'.$file["refGenome"].'</td>';
				$html .= '</tr>';

			} 

	$html .= '</tbody>';
	
		$html .= '</table>';

		return $html;

}


// has tool custom visualizer

function getToolDev_fromTool($toolId){
    $r = $GLOBALS['usersCol']->find(array('ToolsDev' =>array('$elemMatch' => array('$eq' => $toolId))),
        array ("_id" => 1));

    if (empty($r))
        return Array();

    $r_arr = iterator_to_array($r);
    return array_keys($r_arr);
}

