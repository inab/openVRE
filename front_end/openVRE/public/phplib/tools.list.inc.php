<?php

use OpenVRE\LoggerFactory;
use OpenVRE\NotFoundException;
use OpenVRE\Tooljob;
use OpenVRE\UserType;


function getToolsLogger()
{
	static $logger = null;

	if ($logger === null) {
		$logger = LoggerFactory::getLogger('Tools interface');
	}

	return $logger;
}


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
function getTool_fromId($toolId, $indexByName = false)
{
	$tool = $GLOBALS['toolsCol']->findOne(['_id' => $toolId]);
	if (is_null($tool)) {
		return null;
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


// launch tool - used for internal tools
function launchToolInternal($toolId, $args = [], $output_dir = "", $logName = "")
{
	$tool = getTool_fromId($toolId, true);
	if (is_null($tool)) {
		getToolsLogger()->error("Tool '$toolId' not registered");
		throw new NotFoundException("Internal tool not registered");
	}

	if ($tool['external']) {
		getToolsLogger()->error("Tool '$toolId' is not internal");
		throw new UnexpectedValueException("Tool is not internal");
	}

	$descrip = "Internal job execution of " . $tool['name'];
	$jobMeta = new Tooljob($tool, descrip: $descrip, arguments_exec: $output_dir);

	if (strlen($logName)) {
		$jobMeta->setLog($logName);
	}

	$args['working_dir'] = $jobMeta->working_dir;
	$jobMeta->setArguments($args, $tool);
	$jobMeta->createWorking_dir();

	// Setting Command line. Adding parameters
	$jobMeta->prepareExecution($tool, []);
	$jobMeta->submit($tool);
	addUserJob($_SESSION['User']['_id'], (array)$jobMeta, $jobMeta->pid);

	return $jobMeta->pid;
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


// list a tool input file combination
function getInputFilesCombinations($tool)
{

	$descriptions = [];
	foreach ($tool["input_files_combinations"] as $t) {

		$descriptions[] = $t["description"];
	}

	return implode("~", $descriptions);
}


function getSites_Info($toolId)
{

	// Retrieve tool document from the tools collection
	$toolDocument = $GLOBALS['toolsCol']->findOne(['_id' => $toolId]);
	if (is_null($toolDocument)) {
		return null;
	}

	$executionSitesData = $toolDocument['sites'];
	$executionSites = [];

	foreach ($executionSitesData as $siteData) {
		if ($siteData['status'] === 1) {
			$siteId = $siteData['site_id'];
			$filterfields = array();
			$siteDocument = $GLOBALS['sitesCol']->findOne(array('_id' => $siteId), $filterfields);
			if (is_null($siteDocument)) {
				throw new NotFoundException("Site document not found for site ID: {$siteId}");
			}

			$siteDetails = [
				'site_id' => $siteDocument['_id'],  // Assuming _id is site_id
				'name' => $siteDocument['name'],
				'launcher' => $siteDocument['launcher'],
				'status' => $siteData['status'],
			];
			$executionSites[] = $siteDetails;
		}
	}

	return $executionSites;
}
