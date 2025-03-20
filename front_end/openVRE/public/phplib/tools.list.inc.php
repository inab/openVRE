<?php

//require "classes/Tooljob.php";

// list tools

function getTools_List($status = 1)
{
	if ($_SESSION['User']['Type'] == UserType::Guest->value) {
		$tools = $GLOBALS['toolsCol']->find(array('external' => true, 'status' => $status, 'owner.license' => array('$ne' => "free_for_academics")), array('name' => 1, 'title' => 1, 'short_description' => 1, 'keywords' => 1), array('title' => 1));
	} elseif ($_SESSION['User']['Type'] == UserType::Admin->value || $_SESSION['User']['Type'] == UserType::ToolDev->value) {
		$tools = $GLOBALS['toolsCol']->find(array('external' => true, 'status' => $status), array('name' => 1, 'title' => 1, 'short_description' => 1, 'keywords' => 1, 'status' => 1), array('title' => 1));
	} else {
		$tools = $GLOBALS['toolsCol']->find(array('external' => true, 'status' => $status), array('name' => 1, 'title' => 1, 'short_description' => 1, 'keywords' => 1), array('title' => 1));
	}

	if ($_SESSION['User']['Type'] == UserType::ToolDev->value) {
		$tools_list = iterator_to_array($tools);
		foreach ($tools_list as $key => $tool) {
			if ($tool["status"] == 3 && !in_array($tool["_id"], $_SESSION['User']["ToolsDev"])) {
				unset($tools_list[$key]);
			}
		}

		return $tools_list;
	} else {
		return iterator_to_array($tools);
	}
}

// list tools

function getTools_ListComplete($status = 1)
{
	if ($_SESSION['User']['Type'] == UserType::Guest->value) {
		$tools = $GLOBALS['toolsCol']->find(array('external' => true, 'status' => $status, 'owner.license' => array('$ne' => "free_for_academics")), array(), array('title' => 1));
	} elseif ($_SESSION['User']['Type'] == UserType::Admin->value || $_SESSION['User']['Type'] == UserType::ToolDev->value) {
		$tools = $GLOBALS['toolsCol']->find(array('external' => true, 'status' => array('$ne' => 2)), array(), array('title' => 1));
	} else {
		$tools = $GLOBALS['toolsCol']->find(array('external' => true, 'status' => $status), array(), array('title' => 1));
	}

	if ($_SESSION['User']['Type'] == UserType::ToolDev->value) {
		$tools_list = iterator_to_array($tools);
		foreach ($tools_list as $key => $tool) {
			if ($tool["status"] == 3 && !in_array($tool["_id"], $_SESSION['User']["ToolsDev"])) {
				unset($tools_list[$key]);
			}
		}

		return $tools_list;
	} else {
		return iterator_to_array($tools);
	}
}

// list tools

function getTool_fromId($toolId, $indexByName = 0)
{
	$filterFields = [];
	$tool = $GLOBALS['toolsCol']->findOne(['_id' => $toolId], $filterFields);
	if (empty($tool)) {
		return 0;
	}

	if ($indexByName) {
		$toolIndexed = [];
		foreach ($tool as $attribute => $value) {
			if (is_array($value)) {
				$shouldReindex = 0;
				foreach ($value as $v) {
					if (isset($v['name'])) {
						$shouldReindex = 1;
						$toolIndexed[$attribute][$v['name']] = $v;
					}
				}

				if (!$shouldReindex) {
					$toolIndexed[$attribute] = $value;
				}
			} else {
				$toolIndexed[$attribute] = $value;
			}
		}

		$tool = $toolIndexed;
	}

	return $tool;
}

// list visualizers

function getVisualizer_fromId($toolId, $indexByName = 0)
{
	$filterfields = array();
	$tool = $GLOBALS['visualizersCol']->findOne(array('_id' => $toolId)/*, $filterfields*/);

	if (empty($tool))
		return 0;

	if ($indexByName) {
		$toolIndexed = array();
		foreach ($tool as $attribute => $value) {
			if (is_array($value)) {
				$t = 0;
				foreach ($value as $v) {
					if (isset($v['name'])) {
						$t = 1;
						$toolIndexed[$attribute][$v['name']] = $v;
					}
				}
				if (!$t) {
					$toolIndexed[$attribute] = $value;
				}
			} else {
				$toolIndexed[$attribute] = $value;
			}
		}
		$tool = $toolIndexed;
	}
	return $tool;
}

// get Tool under development

function getToolDev_fromId($toolId, $indexByName = 0)
{
	$tool = $GLOBALS['toolsDevMetaCol']->findOne(array('_id' => $toolId));

	if (empty($tool))
		return 0;

	if ($indexByName) {
		if ($tool["step1"]["tool_io"]) {
			$toolIndexed = array();
			foreach ($tool['step1']['tool_io'] as $attribute => $value) {
				if (is_array($value)) {
					$t = 0;
					foreach ($value as $v) {
						if (isset($v['name'])) {
							$t = 1;
							$toolIndexed[$attribute][$v['name']] = $v;
						}
					}
					if (!$t) {
						$toolIndexed[$attribute] = $value;
					}
				} else {
					$toolIndexed[$attribute] = $value;
				}
			}
			$tool["step1"]["tool_io"] = $toolIndexed;
		}
		if ($tool["step3"]["tool_spec"]) {
			$toolIndexed = array();
			foreach ($tool['step3']['tool_spec'] as $attribute => $value) {
				if (is_array($value)) {
					$t = 0;
					foreach ($value as $v) {
						if (isset($v['name'])) {
							$t = 1;
							$toolIndexed[$attribute][$v['name']] = $v;
						}
					}
					if (!$t) {
						$toolIndexed[$attribute] = $value;
					}
				} else {
					$toolIndexed[$attribute] = $value;
				}
			}
			$tool["step3"]["tool_spec"] = $toolIndexed;
		}
	}
	return $tool;
}

// delete Tool under development

function deleteToolDev($toolId)
{
	$tool = $GLOBALS['toolsDevMetaCol']->findOne(array('_id' => $toolId));
	if (!$tool) {
		$_SESSION['errorData']['Warning'][] = "Cannot delete tool '$toolId'. Entry not found.";
		return 1;
	}
	// Clean associated dev files
	$dev_dir = $GLOBALS['dataDir'] . "/" . $_SESSION['User']['id'] . "/.dev/" . $toolId;
	if (is_dir($dev_dir)) {
		exec("rm -r \"$dev_dir\" 2>&1", $output);
		if (error_get_last()) {
			$_SESSION['errorData']['Error'][] = "Cannot delete tool '$toolId'.";
			$_SESSION['errorData']['Error'][] = implode(" ", $output);
			return 0;
		}
	}

	// Delete from mongo
	$GLOBALS['toolsDevMetaCol']->deleteOne(array('_id' => $toolId));

	$tool = $GLOBALS['toolsDevMetaCol']->findOne(array('_id' => $toolId));
	if ($tool) {
		$_SESSION['errorData']['Error'][] = "Cannot delete tool '$toolId' from DB. An error occurred.";
		return 0;
	}
	return 1;
}

// has tool custom visualizer

function hasTool_custom_visualizer($toolId)
{
	$has_custom_visualizer = $GLOBALS['toolsCol']->findOne(
		array(
			'_id' => $toolId,
			'output_files' => array('$elemMatch' => array("custom_visualizer" => true))
		),
		array('_id' => 1)
	);
	return $has_custom_visualizer;
}


// launch tool - used for internal tools

function launchToolInternal($toolId, $inputs = [], $args = [], $outs = [], $output_dir = "", $logName = "")
{
	$tool = getTool_fromId($toolId, 1);
	if (empty($tool)) {
		$_SESSION['errorData']['Error'][] = "Tool internal not specified or not registered. Please, register '$toolId'";
		return 0;
	}

	if ($tool['external'] !== false) {
		$_SESSION['errorData']['Error'][] = "Selected tool ($toolId) expected to be Internal but specification states: {'external':false}";
		return 0;
	}

	// Set Tool job - tmp working dir
	$execution = 0;   // internal tool do not create a execution folder
	$project = 0;   // internal tool do not have an associated project
	$descrip = "Internal job execution of " . $tool['name'];
	$jobMeta = new Tooljob($tool, $execution, $project, $descrip, $output_dir);

	if (strlen($logName)) {
		$jobMeta->setLog($logName);
	}

	// Stage in (fake)  TODO

	// Checking files locally
	$files = []; // distinct file Objs to stage in 
	foreach ($inputs as $inName => $inIds) {
		foreach ($inIds as $inId) {
			$file = getGSFile_fromId($inId);
			if (!$file) {
				$_SESSION['errorData']['Error'][] = "Input file $inId does not belong to current user or has been not properly registered. Stopping internal tool execution";
				return 0;
			}

			$files[$file['_id']] = $file;
		}
	}

	$jobMeta->setInput_files($inputs, [], []);
	if ($jobMeta->input_files == 0) {
		$_SESSION['errorData']['Error'][] = "Internal tool execution has no input files defined";
		return 0;
	}

	$args['working_dir'] = $jobMeta->working_dir;
	$jobMeta->setArguments($args, $tool);

	$jobId = $jobMeta->createWorking_dir();
	if (!$jobId) {
		$_SESSION['errorData']['Error'][] = "Cannot create tool temporal working dir";
		return 0;
	}

	// Set outfiles metadata -- for register latter
	$jobMeta->setStageout_data($outs);

	// Setting Command line. Adding parameters
	$isExecutionPrepared = $jobMeta->prepareExecution($tool, $files);
	if ($isExecutionPrepared == 0) {
		return 0;
	}

	$pid = $jobMeta->submit($tool);
	if ($pid == 0) {
		return 0;
	}

	addUserJob($_SESSION['User']['_id'], (array)$jobMeta, $jobMeta->pid);

	return $jobMeta->pid;
}


function parse_configFile_OBSOLETE($configFile)
{
	$configParsed = array();

	// load config as json
	$config = json_decode(file_get_contents($configFile));

	// parse json
	$configParsed['input_files'] = array();
	if ($config->input_files) {
		foreach ($config->input_files as $input) {
			if (!isset($configParsed['input_files'][$input->name]))
				$configParsed['input_files'][$input->name] = array();
			$input_fn = getAttr_fromGSFileId($input->value, 'path');
			if ($input_fn)
				array_push($configParsed['input_files'][$input->name], str_replace($_SESSION['User']['id'] . "/", "", $input_fn));
			else
				array_push($configParsed['input_files'][$input->name], $input->value);
		}
	}
	$configParsed['arguments'] = array();
	if ($config->arguments) {
		foreach ($config->arguments as $arg) {
			$configParsed['arguments'][$arg->name] = $arg->value;
		}
	}
	return $configParsed;
}


function parse_submissionFile_SGE_OBSOLETE($rfn)
{
	$cmdsParsed = array();

	$cmds = preg_grep("/^\//", file($rfn));
	$cwd  = str_replace("cd ", "", join("", preg_grep("/^cd /", file($rfn))));

	$n = 1;
	foreach ($cmds as $cmd) {

		$cmdsParsed[$n]['cmdRaw']    = $cmd;
		$cmdsParsed[$n]['cwd']       = $cwd;

		$cmdsParsed[$n]['prgName']   = "";      # tool executable name for table title
		$cmdsParsed[$n]['params']    = array(); # paramName=>paramValue

		if (preg_match('/^#/', $cmd))
			continue;
		if (preg_match('/^(.[^ ]*) (.[^>]*)(\d*>*.*)$/', $cmd, $m)) {
			$executable =  ($m[1] ? basename($m[1]) : "No information");
			$paramsStr  =  ($m[2] ? $m[2] : "");
			$log        =  ($m[3] ? $m[3] : "");

			// parse executable file
			$cmdsParsed[$n]['prgName']  = $executable;

			// parse cmd params
			foreach (explode("--", $paramsStr) as $p) {
				trim($p);
				if (!$p)
					continue;
				list($k, $v) = explode(" ", $p);
				if (strlen($k) == 0 && strlen($v) == 0)
					continue;
				if (!$v)
					$v = "";
				// if paramValue is a file, show only 'execution/filename'
				$v  = str_replace($GLOBALS['dataDir'] . "/" . $_SESSION['User']['id'] . "/", "", $v);

				// HACK; when rfn comes from sample data, filenames in cmd do not contain the right userId. Cutting filepath using explode
				if (preg_match('/^\//', $v)) {
					$execution = explode("/", $rfn);
					$v = $execution[count($execution) - 2] . "/" . basename($v);
				}
				$cmdsParsed[$n]['params'][$k] = $v;
			}
		}
		$n++;
	}
	return $cmdsParsed;
}

// list visualizers

function getVisualizers_List($status = 1)
{

	$visualizers = $GLOBALS['visualizersCol']->find(array('external' => true, 'status' => $status), array('name' => 1, 'title' => 1, 'short_description' => 1, 'keywords' => 1), array('title' => 1));

	return iterator_to_array($visualizers);
}

// list visualizers

function getVisualizers_ListComplete($status = 1)
{

	$visualizers = $GLOBALS['visualizersCol']->find(array('external' => true, 'status' => $status), array(), array('title' => 1));

	return iterator_to_array($visualizers);
}

// list file_types in use

function getFileTypes_List()
{

	$tls = $GLOBALS['toolsCol']->find(array('external' => true), array('name' => 1, 'input_files' => 1), array('name' => 1));

	$tools = iterator_to_array($tls);

	sort($tools);

	$filetypes = array();

	$i = 0;

	foreach ($tools as $t) {

		if (isset($t['input_files'])) {

			$filetypes[$i]['name'] = $t['name'];

			$types = array();

			foreach ($t['input_files'] as $if) array_push($types, implode($if['file_type']));

			$filetypes[$i]['file_types'] = $types;

			$i++;
		}
	}

	return $filetypes;
}

// list a tool input file combination

function getInputFilesCombinations($tool)
{

	$descriptions = [];
	foreach ($tool["input_files_combinations"] as $t) {

		$descriptions[] = $t["description"];
	}

	return implode("~", $descriptions);
}

// list visualizers

function getVisualizerTableList($file_types, $visualizer = null)
{

	$files_list = getGSFiles_filteredBy(array("format" => array('$in' => $file_types), "visible"   => true));
	error_log(print_r($file_types, true));
	$list = [];

	foreach ($files_list as $file) {

		$path = getAttr_fromGSFileId($file["_id"], 'path');
		$p = explode("/", $path);

		$a = [];
		$a["id"] = $file["_id"];
		$a["project"] = getProject($p[1])["name"];
		$a["execution"] = $p[2];
		$a["file"] = $p[3];

		$list[] = $a;
	}

	$html = '<table id="workspace_st2" class="table display" cellspacing="0" width="100%">';

	$html .= '<thead>';
	$html .= '<tr id="headerSearch">';
	$html .= '<th style="background-color: #eee;padding:3px;width:60px;"></th>';
	$html .= '<th style="background-color: #eee;padding:3px;" class="inputSearch">Files</th>';
	$html .= '<th style="background-color: #eee;padding:3px;" class="selector">Project</th>';
	$html .= '<th style="background-color: #eee;padding:3px;" class="selector">Execution</th>';
	if ($visualizer == "jbrowse") $html .= '<th style="background-color: #eee;padding:3px;" class="selector">Reference Genome</th>';
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
	if ($visualizer == "jbrowse") $html .= '<th>Reference Genome</th>';
	$html .= '</tr>';
	$html .= '</thead>';


	$html .= '<tbody>';

	$selectedFiles = [];

	foreach ($list as $file) {

		$tr_class = "";

		$html .= '<tr class="row-clickable ' . $tr_class . '">';
		$html .= '<td>';
		$html .= '<label class="mt-checkbox mt-checkbox-single mt-checkbox-outline">';
		$html .= '<input type="checkbox" class="checkboxes" value="' . $file["id"] . '" onchange="changeCheckbox(this, \'' . $file["file"] . '\', \'' . $file["id"] . '\', \'Project01 (TODO) / ' . $file["execution"] . ' /\')" />';
		$html .= '<span></span>';
		$html .= '</label>';
		$html .= '</td>';
		$html .= '<td>' . $file["file"] . '</td>';
		$html .= '<td>' . $file["project"] . '</td>';
		$html .= '<td>' . $file["execution"] . '</td>';
		if ($visualizer == "jbrowse") $html .= '<td>' . $file["refGenome"] . '</td>';
		$html .= '</tr>';
	}

	$html .= '</tbody>';

	$html .= '</table>';

	return $html;
}


// has tool custom visualizer

function getToolDev_fromTool($toolId)
{
	$r = $GLOBALS['usersCol']->find(
		array('ToolsDev' => array('$elemMatch' => array('$eq' => $toolId))),
		array("_id" => 1)
	);

	if (empty($r))
		return array();

	$r_arr = iterator_to_array($r);
	return array_keys($r_arr);
}



function getExecutionSitesForTool($toolId)
{
	// Retrieve tool document from the tools collection
	$toolDocument = $GLOBALS['toolsCol']->findOne(['_id' => $toolId]);

	if (!$toolDocument) {
		return null;
	}

	$executionSitesData = $toolDocument['sites'];
	$executionSites = [];
	$launchers = [];

	foreach ($executionSitesData as $siteData) {
		if ($siteData['status'] === 1) {
			$siteId = $siteData['site_id'];

			//$_SESSION['errorData']['Error'][]="SiteId: {$siteId}";

			// Query the execution_sites collection to get details about the execution site
			// retrieve the document to check	
			// $siteDocument = $GLOBALS['sitesCol']->findOne(['_id' => 'sites']);
			// per referenza:        $tool = $GLOBALS['toolsCol']->findOne(array('_id' => $toolId), $filterfields);	
			//$_SESSION['errorData']['Error'][]="Site Doc: " . print_r($siteDocument['site'], true);
			//$sites = $siteDocument['site'];
			//$siteDetails = $siteDocument['site'] ->findOne(['site_id' => $siteId]);


			if (isset($GLOBALS['sitesCol'])) {
				$matchingSite = null;
				foreach ($siteDocument['site'] as $site) {
					if ($site['site_id'] == $siteId) {
						$matchingSite = $site;
						break;
					}
				}

				if ($matchingSite) {
					$siteDetails = [
						'site_id' => $matchingSite['site_id'],
						'name' => $matchingSite['name'],
						'launcher' => $matchingSite['launcher'],
						'status' => $siteData['status'],
					];
					$executionSites[] = $siteDetails;
					//	$_SESSION['errorData']['Error'][] = "Site: " . print_r($siteDetails, true);
				} else {
					$_SESSION['errorData']['Error'][] = "Site not found blabla for site ID: {$siteId}";
				}
			} else {

				$_SESSION['errorData']['Error'][] = "No 'site' field found in the document";
			}
		}
	}

	//$_SESSION['errorData']['Error'][] = "Site: " . print_r($siteDetails, true);	
	//$_SESSION['errorData']['Error'][] = "Site: " . print_r($executionSites, true);
	return $executionSites;
}

function getSites_Info($toolId)
{

	// Retrieve tool document from the tools collection
	//	$filterfields=array();
	$toolDocument = $GLOBALS['toolsCol']->findOne(['_id' => $toolId]);
	//$_SESSION['errorData']['Error'][] = "Site: " . print_r($toolDocument, true);
	if (!$toolDocument) {
		return null;
	}

	$executionSitesData = $toolDocument['sites'];
	$executionSites = [];
	$launchers = [];

	foreach ($executionSitesData as $siteData) {
		if ($siteData['status'] === 1) {
			$siteId = $siteData['site_id'];
			//$_SESSION['errorData']['Error'][] = "site ID: {$siteId}";
			$filterfields = array();
			$siteDocument = $GLOBALS['sitesCol']->findOne(array('_id' => $siteId), $filterfields);
			//$_SESSION['errorData']['Warning'][] = "Site Document: {$siteDocument}";
			//echo ("Site Document: {$siteDocument}");

			if ($siteDocument) {
				$siteDetails = [
					'site_id' => $siteDocument['_id'],  // Assuming _id is site_id
					'name' => $siteDocument['name'],
					'launcher' => $siteDocument['launcher'],
					'status' => $siteData['status'],
				];
				$executionSites[] = $siteDetails;
			} else {
				$_SESSION['errorData']['Error'][] = "Site not found jcjcj for site ID: {$siteId}";
			}
		}
	}
	//$_SESSION['errorData']['Error'][] = "Site: " . print_r($executionSites, true);
	return $executionSites;
}

function getLauncherDetails($toolId)
{
	// Get the execution sites details
	$executionSites = $this->getSites_Info($toolId);

	if (!$executionSites) {
		return null;
	}

	$launcherDetails = [];
	// Iterate through each site and extract only the launcher section
	foreach ($executionSites as $siteDetails) {
		if (isset($siteDetails['launcher'])) {
			$launcherDetails[] = $siteDetails['launcher'];
		}
	}
	return $launcherDetails;
}
