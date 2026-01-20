<?php

namespace OpenVRE;

use Monolog\Logger;
use UnexpectedValueException;


class Tooljob
{

	public $_id;
	public $title;
	public $execution;         // User defined. Correspond to the execution folder name
	public $project;           // User defined. Correspond to the project
	public $toolId;
	public $pub_dir;           // Public dir mounted to VMs. Path as seen by VRE
	public $root_dir;          // User dataDir. Path as seen by VRE
	public $root_dir_virtual;  // User dataDir. Path as seen by VMs
	public $pub_dir_virtual;   // Public dir mounted to VMs. Path as seen by VMs
	public $cloudName;         // Cloud name where tool should be executed. Available clouds set in GLOBALS['clouds']
	public $root_dir_host;
	public $pub_dir_host;
	public $scripts_dir_host;
	public $root_dir_volumes;
	public $pub_dir_volumes;
	public $description;
	public $working_dir;
	public $output_dir;
	public $launcher;
	public $imageType;
	public $arguments_exec;
	public $job_type;

	public $root_dir_mug;

	public $pub_dir_intern;

	public $containerName;

	// Paths to files genereted during ToolJob execution
	public $config_file;
	public $input_dir_virtual;
	public $config_file_virtual;
	public $stageout_file;
	public $stageout_file_virtual;
	public $submission_file;
	public $metadata_file;
	public $metadata_file_virtual;
	public $log_file;
	public $log_file_virtual;
	public $logName;
	public $stdout_file;
	public $stderr_file;

	public $input_files     = [];
	public $input_files_pub = [];
	public $input_paths_pub = [];
	public $arguments       = [];
	public $metadata        = [];
	public $pid             = 0;
	public $start_time      = 0;
	public $hasExecutionFolder = true;

	private Logger $logger;


	/**
	 * Creates new toolExecutor instance
	 * @param string $toolId Tool Id as appears in Mongo
	 */
	public function __construct($tool, $execution = "", $project = "", $descrip = "", $arguments_exec = "", $output_dir = "")
	{
		$this->logger = LoggerFactory::getLogger("Tool job");

		// Setting Tooljob
		$this->toolId    = $tool['_id'];
		$this->title     = $tool['name'] . " job";
		$this->execution = $execution;
		$this->project   = $project;

		// Set paths in VRE
		$this->root_dir  = $GLOBALS['dataDir'] . "/" . $_SESSION['User']['id'];
		$this->pub_dir   = $GLOBALS['pubDir'];
		$this->arguments_exec = $arguments_exec;

		// Set paths in the virtual machine
		if (!empty($this->arguments_exec['site_list']) && count($this->arguments_exec['site_list']) >= 1) {
			$site_list = $this->arguments_exec['site_list'];
			// The first element in site_list is the cloudName
			$this->cloudName = $site_list[0];

			// The second element in site_list is the launcher
			$this->launcher = str_replace($this->cloudName . "_", "", $site_list[1]);
		} else {

			// If not enough information is provided, fall back to default method
			$this->set_cloudName($tool);
			$this->launcher = $tool['infrastructure']['clouds'][$this->cloudName]['launcher'];
		}
		switch ($this->launcher) {
			case "SGE":
			case "docker_SGE":
				$this->root_dir_virtual = $GLOBALS['clouds'][$this->cloudName]['dataDir_virtual'] . "/" . $_SESSION['User']['id'];
				$this->root_dir_mug     = $GLOBALS['clouds'][$this->cloudName]['dataDir_virtual'];
				$this->pub_dir_virtual  = $GLOBALS['clouds'][$this->cloudName]['pubDir_virtual'];
				$this->pub_dir_volumes  = $GLOBALS['clouds'][$this->cloudName]['pubDir_host'];
				$this->root_dir_volumes  = $GLOBALS['clouds'][$this->cloudName]['dataDir_host'] . "/" . $_SESSION['User']['id'];
				$this->pub_dir_intern   = rtrim($this->pub_dir_virtual, "/") . "_tmp";
				break;
			case "ega_demo":
				$this->root_dir_virtual = $GLOBALS['clouds'][$this->cloudName]['dataDir_virtual'] . "/" . $_SESSION['User']['id'];
				$this->pub_dir_virtual  = $GLOBALS['clouds'][$this->cloudName]['pubDir_virtual'];
				$this->root_dir_host    = $GLOBALS['clouds'][$this->cloudName]['dataDir_host'];
				$this->pub_dir_host     = $GLOBALS['clouds'][$this->cloudName]['pubDir_host'];
				$this->scripts_dir_host = $GLOBALS['clouds'][$this->cloudName]['scriptsDir_host'];
				break;
			case "DTRCLONE":
				$this->root_dir_virtual = $GLOBALS['clouds'][$this->cloudName]['dataDir_virtual'];
				$this->pub_dir_virtual  = $GLOBALS['clouds'][$this->cloudName]['pubDir_virtual'];
				$this->root_dir_fs = $GLOBALS['clouds'][$this->cloudName]['dataDir_fs'];
				$this->pub_dir_fs = $GLOBALS['clouds'][$this->cloudName]['pubDir_fs'];
				$this->auth = $GLOBALS['clouds'][$this->cloudName]['auth'];
				$this->http_host = $GLOBALS['clouds'][$this->cloudName]['http_host'];
				break;
			case "Slurm":
				$this->root_dir_df = $GLOBALS['clouds'][$this->cloudName]['mn_dir'] .  "/" . substr($_SESSION['User']['linked_accounts']['MN']['username'], 0, 6) . "/" . $_SESSION['User']['linked_accounts']['MN']['username'] . "/" . $GLOBALS['clouds'][$this->cloudName]['dataDir_fs'];
				$this->pub_dir_fs = $GLOBALS['clouds'][$this->cloudName]['mn_dir'] .  "/" . substr($_SESSION['User']['linked_accounts']['MN']['username'], 0, 6) . "/" . $_SESSION['User']['linked_accounts']['MN']['username'] . "/" . $GLOBALS['clouds'][$this->cloudName]['pubDir_fs'];
				$this->auth = $GLOBALS['clouds'][$this->cloudName]['auth'];
				$this->http_host = $GLOBALS['clouds'][$this->cloudName]['http_host'];
				break;
			default:
				$_SESSION['errorData']['Error'][] = "Tool '$this->toolId' not properly registered. Launcher type is set to '" . $this->launcher . "'. Case not implemented.";
		}

		// Creating execution folder
		if (empty($execution)) {
			//internalTool
			$this->hasExecutionFolder = false;
			$this->__setWorking_inTmp($tool['_id']);
			$this->output_dir = $output_dir;
		} else {
			//create Project Folder
			$this->hasExecutionFolder = true;
			$this->__setWorking_dir($execution);
			$this->output_dir = $this->working_dir;
		}

		// Set description
		if (!empty($descrip)) {
			$this->setDescription($descrip, $tool['name']);
		}

		// Set project
		if (empty($project)) {
			$this->project = $_SESSION['User']['activeProject'];
		} else {
			//TODO Check project exists
			if (isProject($project)) {
				$this->project = $project;
			} else {
				$_SESSION['errorData']['Warning'][] = "Given project code '$project' not valid. Setting job as part of last active project.";
				$this->project = $_SESSION['User']['activeProject'];
			}
		}

		return $this;
	}


	/**
	 * Set description
	 * @param string $descrip Short execution description to annotate execution directory
	 */
	public function setDescription($descrip, $toolName = 0)
	{
		if (strlen($descrip))
			$this->description = $descrip;
		elseif ($toolName)
			$this->description = "Execution directory for tool " . $toolName;
		else
			$this->description = "Execution directory";
	}

	public function setLog($filename = "")
	{
		if (strlen($filename)) {
			$filename = basename($filename);
			$filePathInfo = pathinfo($filename);
			if ($filePathInfo['extension'] != "log") {
				$filename .= ".log";
			}

			$this->logName = $filename;
		} else {
			$this->logName = $GLOBALS['tool_log_file'];
		}

		if ($this->hasExecutionFolder) {
			$this->__setWorking_dir($this->execution);
		} else {
			$this->__setWorking_inTmp($this->toolId);
		}
	}

	/**
	 * Set working directory where log_file, submission_file and control_file will be located
	 * @param string $execution Execution name used to set the working directory name
	 * @param boolean $overwrite If false, an alternative name $execution[_NN] for the working directory is set
	 */

	public function __setWorking_dir($execution, $overwrite = 0)
	{
		$this->logger->info("Setting working directory for execution '$execution'");
		$dataDirPath = getAttr_fromGSFileId($_SESSION['User']['dataDir'], "path");
		$localWorkingDir = "$dataDirPath/$execution";

		if (!$overwrite) {
			$prevs = $GLOBALS['filesCol']->findOne(['path' => $localWorkingDir, 'owner' => $_SESSION['User']['id']]);
			if ($prevs) {
				for ($n = 1; $n < 99; $n++) {
					$executionN = $execution . "_" . $n;
					$localWorkingDir = "$dataDirPath/$executionN";
					$prevs = $GLOBALS['filesCol']->findOne(['path' => $localWorkingDir, 'owner' => $_SESSION['User']['id']]);
					if ($prevs) {
						$execution = $executionN;
						break;
					}
				}
			}
		}

		$this->execution = $execution;
		$this->working_dir = "{$this->root_dir}/{$this->project}/{$this->execution}";
		$this->logName = $this->logName ?: $GLOBALS['tool_log_file'];

		$this->config_file    = "{$this->working_dir}/{$GLOBALS['tool_config_file']}";
		$this->stageout_file  = "{$this->working_dir}/{$GLOBALS['tool_stageout_file']}";
		$this->submission_file = "{$this->working_dir}/{$GLOBALS['tool_submission_file']}";
		$this->log_file       = "{$this->working_dir}/{$this->logName}";
		$this->metadata_file  = "{$this->working_dir}/{$GLOBALS['tool_metadata_file']}";
		$this->stdout_file    = $this->working_dir . "/job_output.log";
		$this->stderr_file    = $this->working_dir . "/job_error.log";

		// for interactive visualizer
		$this->input_dir_virtual = $this->root_dir_virtual . "/" . $this->project . "/" . $this->execution . "/uploads";

		$this->config_file_virtual    = "{$this->root_dir_virtual}/{$this->project}/{$this->execution}/{$GLOBALS['tool_config_file']}";
		$this->stageout_file_virtual  = "{$this->root_dir_virtual}/{$this->project}/{$this->execution}/{$GLOBALS['tool_stageout_file']}";
		$this->metadata_file_virtual  = "{$this->root_dir_virtual}/{$this->project}/{$this->execution}/{$GLOBALS['tool_metadata_file']}";
		$this->log_file_virtual       = "{$this->root_dir_virtual}/{$this->project}/{$this->execution}/{$this->logName}";
	}


	public function __setWorking_inTmp($prefixDir = 0)
	{

		if (!$prefixDir)
			$prefixDir = "tool_";

		$execution = $prefixDir . "_" . rand(10000, 99999);

		$this->execution      = $execution;
		$this->working_dir    = $this->root_dir . "/" . $this->project . "/" . $GLOBALS['tmpUser_dir'] . $this->execution;

		if (!$this->logName) {
			$this->logName = $GLOBALS['tool_log_file'];
		}

		$this->config_file    = $this->working_dir . "/" . $GLOBALS['tool_config_file'];
		$this->stageout_file  = $this->working_dir . "/" . $GLOBALS['tool_stageout_file'];
		$this->submission_file = $this->working_dir . "/" . $GLOBALS['tool_submission_file'];
		$this->log_file       = $this->working_dir . "/" . $this->logName;
		$this->metadata_file  = $this->working_dir . "/" . $GLOBALS['tool_metadata_file'];


		$this->config_file_virtual    = $this->root_dir_virtual . "/" . $this->project . "/" . $GLOBALS['tmpUser_dir'] . $this->execution . "/" . $GLOBALS['tool_config_file'];
		$this->stageout_file_virtual  = $this->root_dir_virtual . "/" . $this->project . "/" . $GLOBALS['tmpUser_dir'] . $this->execution . "/" . $GLOBALS['tool_stageout_file'];
		$this->metadata_file_virtual  = $this->root_dir_virtual . "/" . $this->project . "/" . $GLOBALS['tmpUser_dir'] . $this->execution . "/" . $GLOBALS['tool_metadata_file'];
		$this->log_file_virtual       = $this->root_dir_virtual . "/" . $this->project . "/" . $GLOBALS['tmpUser_dir'] . $this->execution . "/" . $this->logName;
	}


	/**
	 * Create working directory
	 */
	public function createWorking_dir()
	{
		if (is_null($this->working_dir)) {
			$this->logger->error("Cannot create working_dir. Not set yet");
			throw new UnexpectedValueException("Cannot create working_dir. Not set yet");
		}

		$dirPath = str_replace($GLOBALS['dataDir'] . "/", "", $this->working_dir);
		if (!is_dir($this->working_dir)) {
			$this->_id = 1;
			if ($this->hasExecutionFolder) {
				try {
					$this->_id = createGSDirBNS($dirPath);
				} catch (UnexpectedValueException $e) {
					$this->logger->error("Cannot create execution folder: '$this->working_dir'");
					throw new UnexpectedValueException("Cannot create execution folder: '$this->working_dir'" . $e->getMessage());
				}
			}

			if (!mkdir($this->working_dir, 0777, true)) {
				$this->logger->error("Failed to create directory: '$this->working_dir'");
				throw new UnexpectedValueException("Failed to create directory: '$this->working_dir'");
			}

			chmod($this->working_dir, 0777);
			// if exists, recover working dir id
		} else {
			if ($this->hasExecutionFolder) {
				$this->logger->error("Cannot set job. Requested execution folder (" . basename($dirPath) . ") already exists.");
				throw new UnexpectedValueException("Cannot set job. Requested execution folder (" . basename($dirPath) . ") already exists.");
			}

			$this->_id = 1;
		}

		// set dir metadata
		if ($this->_id != 1) {
			if (!is_dir($this->working_dir)) {
				$this->logger->error("Cannot write and set new execution directory: '$this->working_dir' with id '$this->_id'");
				throw new UnexpectedValueException("Cannot write and set new execution directory: '$this->working_dir' with id '$this->_id'");
			}

			$input_ids = [];
			array_walk_recursive($this->input_files, function ($v, $k) use (&$input_ids) {
				$input_ids[] = $v;
			});
			$input_ids = array_unique($input_ids);
			$projDirMeta = [
				'description'     => $this->description,
				'input_files'     => $input_ids,
				'tool'            => $this->toolId,
				'submission_file' => $this->submission_file,
				'log_file'        => $this->log_file,
				'arguments'       => array_merge($this->arguments, $this->input_paths_pub)
			];

			try {
				addMetadataToFile($this->_id, $projDirMeta);
			} catch (UnexpectedValueException $e) {
				$this->logger->error("Project folder created. But cannot set metadata for '$this->working_dir' with id '$this->_id'");
				throw new UnexpectedValueException("Project folder created. But cannot set metadata for '$this->working_dir' with id '$this->_id'. " . $e->getMessage());
			}
		}
	}


	/**
	 * Creates tool configuration JSON
	 * @param array $tool Fill in config file: input_files, arguments and output_files
	 */
	public function setConfiguration_file($tool)
	{
		if (is_null($this->working_dir)) {
			$this->logger->error("Cannot create tool configuration file. No 'working_directory' set");
			throw new UnexpectedValueException("Cannot create tool configuration file. No 'working_directory' set");
		}

		$data = [
			'input_files' => [],
			'arguments' => [
				["name" => "execution", "value" => $this->root_dir_virtual . "/" . $this->project . "/" . $this->execution],
				["name" => "project", "value" => $this->root_dir_virtual . "/" . $this->project . "/" . $this->execution],
				["name" => "description", "value" => $this->description],
			],
			'output_files' => []
		];

		foreach ($this->input_files as $key => $values) {
			foreach ($values as $value) {
				array_push(
					$data['input_files'],
					[
						"name"           => $key,
						"value"          => $value,
						"required"       => $tool['input_files'][$key]['required'],
						"allow_multiple" => $tool['input_files'][$key]['allow_multiple']
					]
				);
			}
		}

		foreach ($this->input_files_pub as $key => $values) {
			foreach ($values as $v) {
				array_push(
					$data['input_files'],
					[
						"name"           => $key,
						"value"          => $v,
						"required"       => $tool['input_files_public_dir'][$key]['required'],
						"allow_multiple" => $tool['input_files_public_dir'][$key]['allow_multiple']
					]
				);
			}
		}

		foreach ($this->arguments as $key => $value) {
			array_push($data['arguments'], ["name" => $key, "value" => $value]);
		}

		if ($tool['output_files']) {
			foreach ($tool['output_files'] as $key => $value) {
				if (isset($value['file']['path'])) {
					$value['file']['path'] = $this->root_dir_virtual . "/" . $this->project . "/" . $this->execution . "/" . $value['file']['path'];
				}

				$data['output_files'][] = $value;
			}
		}

		$file = fopen($this->config_file, "w");
		if ($file === false) {
			$this->logger->error("Failed to create tool configuration file '$this->config_file''.");
			throw new UnexpectedValueException("Failed to create tool configuration file '$this->config_file''.");
		}

		fwrite($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
		fclose($file);
	}


	/**
	 * Set Arguments
	 * @param array $arguments Arguments as received from inputs.php
	 */
	public function setArguments($arguments, $tool = [])
	{
		foreach ($arguments as $arg_name => $arg_value) {
			if (count($tool)) {
				// checking coherence between JSON and REQUEST
				if (is_null($tool['arguments'][$arg_name])) {
					$this->logger->error("Argument '$arg_name' not found in tool '$this->toolId' definition");
					$_SESSION['errorData']['Internal'][] = "There was an internal error launching the tool.";
					redirect($GLOBALS['BASEURL'] . "workspace/");
				}

				if ($arg_value == "") {
					if ($tool['arguments'][$arg_name]['required']) {
						$this->logger->error("No value given for argument '$arg_name'");
						$_SESSION['errorData']['Internal'][] = "There was an internal error launching the tool.";
						redirect($GLOBALS['BASEURL'] . "workspace/");
					}

					continue;
				}

				switch ($tool['arguments'][$arg_name]['type']) {
					case "enum":
						if (is_null($tool['arguments'][$arg_name]['enum_items']) || (is_null($tool['arguments'][$arg_name]['enum_items']['name']))) {
							$this->logger->error("Invalid argument enum in tool definition. '$arg_name' has no 'enum_items' or 'enum_items['name]");
							$_SESSION['errorData']['Internal'][] = "There was an internal error launching the tool.";
							redirect($GLOBALS['BASEURL'] . "workspace/");
						}

						if (!in_array($arg_value, $tool['arguments'][$arg_name]['enum_items']['name'])) {
							$this->logger->error("Invalid argument. In '$arg_name' these values are accepted [" . implode(", ", $tool['arguments'][$arg_name]['enum_items']['name']) . "], but found $arg_value");
							$_SESSION['errorData']['Internal'][] = "There was an internal error launching the tool.";
							redirect($GLOBALS['BASEURL'] . "workspace/");
						}

						break;

					case "enum_multiple":
						if (is_null($tool['arguments'][$arg_name]['enum_items']) || (is_null($tool['arguments'][$arg_name]['enum_items']['name']))) {
							$this->logger->error("Invalid argument enum in tool definition. '$arg_name' has no 'enum_items' or 'enum_items['name]");
							$_SESSION['errorData']['Internal'][] = "There was an internal error launching the tool.";
							redirect($GLOBALS['BASEURL'] . "workspace/");
						}

						if (!is_array($arg_value)) {
							$arg_value = [$arg_value];
						}

						foreach ($arg_value as $v) {
							if (!in_array($v, $tool['arguments'][$arg_name]['enum_items']['name'])) {
								$this->logger->error("Invalid argument. In '$arg_name' these values are accepted [" . implode(", ", $tool['arguments'][$arg_name]['enum_items']['name']) . "], but found " . implode(", ", $arg_value));
								$_SESSION['errorData']['Internal'][] = "There was an internal error launching the tool.";
								redirect($GLOBALS['BASEURL'] . "workspace/");
							}
						}

						break;

					case "boolean":
						if ($arg_value === true || $arg_value == "on" || $arg_value == "1" || $arg_value == 1) {
							$arg_value = true;
						} elseif ($arg_value === false || $arg_value == "off" || $arg_value == "0" || $arg_value == 0) {
							$arg_value = false;
						} else {
							$_SESSION['errorData']['Error'][] = "Invalid argument. In '$arg_name' a boolean was expected, but found: $arg_value";
							$this->logger->error("Invalid argument. In '$arg_name' a boolean was expected, but found: $arg_value");
							$_SESSION['errorData']['Internal'][] = "There was an internal error launching the tool.";
							redirect($GLOBALS['BASEURL'] . "workspace/");
						}

						break;

					case "integer":
						if (!is_numeric($arg_value)) {
							$this->logger->error("Invalid argument. In '$arg_name' an integer was expected, but found: $arg_value");
							$_SESSION['errorData']['Internal'][] = "There was an internal error launching the tool.";
							redirect($GLOBALS['BASEURL'] . "workspace/");
						}

						$arg_value = intval($arg_value);
						break;

					case "number":
						if (!is_numeric($arg_value)) {
							$this->logger->error("Invalid argument. In '$arg_name' a number was expected, but found: $arg_value");
							$_SESSION['errorData']['Internal'][] = "There was an internal error launching the tool.";
							redirect($GLOBALS['BASEURL'] . "workspace/");
						}

						break;

					case "hidden":
					case "string":
						if (is_array($arg_value)) {
							$this->logger->error("Invalid argument. In '$arg_name' a string was expected, but found an array: " . implode(",", $arg_value));
							$_SESSION['errorData']['Internal'][] = "There was an internal error launching the tool.";
							redirect($GLOBALS['BASEURL'] . "workspace/");
						}

						$arg_value = strval($arg_value);
						break;

					default:
						$this->logger->error("Invalid argument type in tool definition. '$arg_name' is of type " . $tool['arguments'][$arg_name]['type']);
						$_SESSION['errorData']['Internal'][] = "There was an internal error launching the tool.";
						redirect($GLOBALS['BASEURL'] . "workspace/");
				}
			}

			$this->arguments[$arg_name] = $arg_value;
		}

		return 1;
	}


	/**
	 * Set inputFiles
	 * @param array $input_files  Input_files as received from inputs.php
	 * @param array $tool Tool array containing input_files type and requirements
	 * @param array $metadata Files metadata extracted from DB
	 */
	public function setInput_files($input_files, $tool = [], $metadata = [])
	{
		foreach ($input_files as $input_name => $filenames) {
			if (count($tool) && count($metadata)) {
				if (!is_array($filenames)) {
					$filenames = [$filenames];
				}

				foreach ($filenames as $filename) {
					// checking coherence between JSON and REQUEST
					if (is_null($tool['input_files'][$input_name])) {
						$this->logger->error("Input file '$input_name' not found in tool definition. '$this->toolId' is not properly registered");
						$_SESSION['errorData']['Internal'][] = "There was an internal error launching the tool.";
						redirect($GLOBALS['BASEURL'] . "workspace/");
					}

					if (empty($filename)) {
						if ($tool['input_files'][$input_name]['required'] === true) {
							$this->logger->error("No file given for '$input_name'");
							$_SESSION['errorData']['Internal'][] = "There was an internal error launching the tool.";
							redirect($GLOBALS['BASEURL'] . "workspace/");
						}

						if (($k = array_search($filename, $filenames)) !== false) {
							unset($filenames[$k]);
						}

						continue;
					}

					if (is_null($metadata[$filename]) && $tool['input_files'][$input_name]['required'] === true) {
						$_SESSION['errorData']['Error'][] = "Given file in '$input_name' has no metadata";
						$this->logger->error("Given file in '$input_name' has no metadata");
						$_SESSION['errorData']['Internal'][] = "There was an internal error launching the tool.";
						redirect($GLOBALS['BASEURL'] . "workspace/");
					}
				}
			}

			if (count($filenames)) {
				$this->input_files[$input_name] = $filenames;
			}
		}
	}

	/**
	 * Set inputFiles from public directory
	 * @param array $input_files_public Input_files_public_dir as received from inputs.php
	 * @param array $tool Tool array containing input_files type and requirements
	 * @param array $metadata_pub Files metadata extracted from DB
	 */


	public function setInput_files_public($input_files_public, $tool = array(), $metadata_pub = array())
	{
		foreach ($input_files_public as $input_name => $input_values) {
			$fns = array();
			if (count($tool) && count($metadata_pub)) {
				if (!is_array($input_values)) {
					$input_values = array($input_values);
				}

				foreach ($input_values as $input_value) {
					if (empty($input_value)) {
						$this->logger->error("No value given public file '$input_name'");
						$_SESSION['errorData']['Internal'][] = "There was an internal error launching the tool.";
						redirect($GLOBALS['BASEURL'] . "workspace/");
					}

					// checking coherence between JSON and REQUEST
					if (is_null($tool['input_files_public_dir'][$input_name])) {
						$this->logger->error("Input file public '$input_name' not found in tool definition. '$this->toolId' is not properly registered");
						$_SESSION['errorData']['Internal'][] = "There was an internal error launching the tool.";
						redirect($GLOBALS['BASEURL'] . "workspace/");
					}

					$fn = array_search($metadata_pub, array('path' => $input_value));
					if ($fn === false) {
						$this->logger->error("Input file public '$input_name' with value '$input_value' not found in public directory");
						$_SESSION['errorData']['Internal'][] = "There was an internal error launching the tool.";
						redirect($GLOBALS['BASEURL'] . "workspace/");
					}

					array_push($fns, $fn);
				}
			}

			$this->input_files_pub[$input_name] = $fns;
			$this->input_paths_pub[$input_name] = $input_values[0];
		}
	}


	/**
	 * Creates metadata JSON
	 */
	public function setMetadata_file($metadata, $metadata_pub = [])
	{
		if (is_null($this->working_dir)) {
			$this->logger->error("Cannot create metadata file. No 'working_dir' set");
			throw new UnexpectedValueException("Cannot create metadata file. No 'working_dir' set");
		}

		$fileMuGs = [];
		// add input_files metadata
		foreach ($metadata as $file) {
			// convert metadata to DMP format
			$fileMuG = $this->fromVREfile_toMUGfile($file);

			if ($fileMuG['data_source'] == "EGA") {
				$fileMuG['path'] = "/clean_files/" . $file['ega_path']; // hardcoded ega path
			}

			if ($fileMuG['path']) {
				$fileMuG['path'] = $this->root_dir_virtual . "/" . $fileMuG['path'];
			}

			if ($fileMuG['parentDir']) {
				$parent_path = getAttr_fromGSFileId($fileMuG['parentDir'], "path");
				if (isset($parent_path)) {
					$fileMuG['parentDir'] = $this->root_dir_virtual . "/" . $parent_path;
				}
			}

			array_push($fileMuGs, $fileMuG);
		}

		// add input_files public metadata
		if (count($metadata_pub)) {
			foreach ($metadata_pub as $fileMuG) {
				$fileMuG['path'] ??= $this->pub_dir_virtual . "/" . $fileMuG['path'];
				if ($fileMuG['parentDir']) {
					$parent_path = getAttr_fromGSFileId($fileMuG['parentDir'], "path");
					if (isset($parent_path)) {
						$fileMuG['parentDir'] = $this->root_dir_virtual . "/" . $parent_path;
					}
				}

				array_push($fileMuGs, $fileMuG);
			}
		}

		$file = fopen($this->metadata_file, "w");
		if ($file === false) {
			$this->logger->error('Failed to create metadata file for tool execution: ' . $this->metadata_file);
			throw new UnexpectedValueException('Failed to create metadata file for tool execution: ' . $this->metadata_file);
		}

		fwrite($file, json_encode($fileMuGs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
		fclose($file);
	}


	/**
	 * Creates execution Command Line and Submission File
	 */
	public function prepareExecution($tool, $metadata, $metadata_pub = [])
	{
		if ($tool['external'] === false) {
			if ($this->launcher == "SGE") {
				$cmd = $this->setBashCmd_withoutApp($tool, $metadata);
				$this->createSubmitFile_SGE($cmd);
			} else {
				$this->logger->error("Internal tool not properly registered. Launcher for '" . $this->toolId . "' is set to \"" . $this->launcher . "\". Case not implemented.");
				throw new UnexpectedValueException("Internal tool not properly registered. Launcher for '" . $this->toolId . "' is set to \"" . $this->launcher . "\". Case not implemented.");
			}
		} else {
			$this->setConfiguration_file($tool);
			$this->setMetadata_file($metadata, $metadata_pub);
			if (!is_file($this->config_file) && !is_file($this->metadata_file)) {
				$this->logger->error("Cannot set tool command line. It required configuration file ($this->config_file) and metadata file ($this->metadata_file)");
				throw new UnexpectedValueException("Cannot set tool command line. It required configuration file ($this->config_file) and metadata file ($this->metadata_file)");
			}

			switch ($this->launcher) {
				case "SGE":
					$cmd  = $this->setBashCmd_SGE($tool);
					$this->createSubmitFile_SGE($cmd);

					break;
				case "docker_SGE":
					$cmd  = $this->setBashCommandDockerSge($tool);
					$this->createSubmitFile_SGE($cmd);

					break;
				case "ega_demo":
					$cmd  = $this->setBashCmd_docker_EGA($tool);
					$this->createSubmitFile_EGA($cmd);

					break;
				case "Slurm":
					$cmd = $this->setHPCRequest($this->cloudName, $tool, $_POST['username']);
					if (!$cmd) {
						return 0;
					}


					break;
				default:
					$this->logger->error("Tool '$this->toolId' not properly registered. Launcher for '$this->toolId' is set to \"$this->launcher\". Case not implemented.");
					throw new UnexpectedValueException("Tool '$this->toolId' not properly registered. Launcher for '$this->toolId' is set to \"$this->launcher\". Case not implemented.");
			}
		}
	}

	protected function setBashCmd_SGE($tool)
	{
		if (is_null($tool['infrastructure']['executable'])) {
			$this->logger->error("Tool '$this->toolId' not properly registered. Missing 'executable' property");
			throw new UnexpectedValueException("Tool '$this->toolId' not properly registered.");
		}

		return $tool['infrastructure']['executable'] .
			" --config "         . $this->config_file_virtual .
			" --in_metadata "    . $this->metadata_file_virtual .
			" --out_metadata "   . $this->stageout_file_virtual .
			" --log_file "       . $this->log_file_virtual;
	}


	protected function getFreePort()
	{
		$networkIP = $GLOBALS['NETWORK_IP'];
		$startPort = $GLOBALS['interactive_range_start_port'];
		$endPort = $startPort + $GLOBALS['max_parallel_independent_tools'];

		for ($port = $startPort; $port <= $endPort; $port++) {
			$connection = @fsockopen($networkIP, $port);
			if ($connection) {
				fclose($connection);
				continue;
			}

			return $port;
		}

		return null;
	}


	protected function setBashCommandDockerSgeInteractive($tool, $cmd_envs)
	{
		$this->job_type = "interactive";
		$container_port = $tool['infrastructure']['container_port'];
		$hostPort = $this->getFreePort();
		if ($hostPort === null) {
			$this->logger->error("No free ports available to run the interactive tool.");
			throw new UnexpectedValueException("No free ports available to run the interactive tool.");
		}

		$checkEnvironment = <<<EOF
			FREE_PORT=$hostPort

			current_user=\$(whoami)
			current_groups=\$(groups)
			checking=\$(getent group | grep docker)
			docker_socket_permissions=\$(ls -l /var/run/docker.sock)

			echo "Free port: \$FREE_PORT"
			echo "Current user: \$current_user"
			echo "Groups: \$current_groups"
			echo "Checking: \$checking"
			echo "Docker socket permissions: \$docker_socket_permissions"
		EOF;

		$configureDockerGroup = <<<EOF
			if echo "\$current_groups" | grep -q "docker"; then
				echo "User \$current_user is already in the 'docker' group."
			else
				echo "User \$current_user is not in the 'docker' group. Attempting to add..."

				sudo usermod -aG docker "\$current_user"

				if [ \$? -eq 0 ]; then
					echo "User \$current_user has been added to the 'docker' group."
					echo "Please log out and log back in for the group changes to take effect."
				else
					echo "Failed to add user \$current_user to the 'docker' group."
				fi
			fi
		EOF;

		$runContainer = <<<EOF
			CONTAINER_ID=\$(docker run \
			--rm \
			--privileged \
			-v /var/run/docker.sock:/var/run/docker.sock -d \
			--net=\$NET_NAME --name $this->containerName \
			$cmd_envs \
			-v {$this->pub_dir_volumes}:{$GLOBALS['shared']}public_tmp/ \
			-v {$this->root_dir_volumes}:{$GLOBALS['shared']}userdata_tmp/{$_SESSION['User']['id']} \
			--hostname $this->containerName \
			-p \$FREE_PORT:{$tool['infrastructure']['container_port']} {$tool['infrastructure']['container_image']});
		EOF;

		$checkContainerStatus = <<<EOF
			if ! docker top \$CONTAINER_ID &>/dev/null; then
				printf '%s | %s\n' "$(date)" "Container crashed unexpectedly...";
				exit 1;
			fi

			if ! docker inspect --format='{{.State.Running}}' \$CONTAINER_ID | grep -q true; then
				printf '%s | %s\n' "$(date)" "Container not running anymore";
				exit 1;
			fi
		EOF;

		$reportContainerInfo = <<<EOF
			CONTAINER_URL="http://$this->containerName:$container_port"
			printf '%s | %s\n' "\$(date)" "ContainerID: \$CONTAINER_ID";
			printf '%s | %s\n' "\$(date)" "ExposedPort: \$FREE_PORT";
			printf '%s | %s\n' "\$(date)" "ContainerURL: \$CONTAINER_URL";
		EOF;

		$monitorContainer = <<<EOF
			docker logs -f \$CONTAINER_ID &> $this->log_file_virtual &

			printf '%s | %s\n' "\$(date)" "Waiting for the service URL to become available in the internal network...";
			if timeout 420 wget --retry-connrefused --tries=10 --waitretry=100 -O /dev/null \$CONTAINER_URL; then
				printf '%s | %s\n' "\$(date)" "Service UP";
			else
				printf '%s | %s\n' "\$(date)" "Service TIMEOUT (7 minutes)";
			fi

			printf '%s | %s\n' "\$(date)" "Wait while container is running...";
			exit_code="\$(docker wait \$CONTAINER_ID)";
			printf '%s | Container has stopped (exit code = %s) \n' "\$(date)" "\$exit_code";

			echo '# End time:' \$(date) >> $this->log_file_virtual;
		EOF;

		return $checkEnvironment . "\n" . $configureDockerGroup . "\n" . $runContainer . "\n" . $checkContainerStatus . "\n" . $reportContainerInfo . "\n" . $monitorContainer;
	}


	protected function setBashCommandDockerCompose($tool)
	{
		$this->job_type = "interactive";
		$dockerComposeFile = $GLOBALS['toolsPath'] . $tool['infrastructure']['docker_path'];
		$container_port = $tool['infrastructure']['container_port'];
		$hostPort = $this->getFreePort();
		if ($hostPort === null) {
			$_SESSION['errorData']['Internal Error'][] = "No free ports available to run the interactive tool.";
			$this->logger->error("No free ports available to run the interactive tool.");
			throw new UnexpectedValueException("No free ports available to run the interactive tool.");
		}


		$this->containerName = $tool['infrastructure']['container_image'];
		$monitorContainer = <<<EOF
			CONTAINER_URL="http://$this->containerName:$container_port"
			whoami;
			printf '%s | %s\n' "\$(date)" "Waiting for the service URL to become available in the internal network...";
			if timeout 420 wget --retry-connrefused --tries=10 --wait=7 -O /dev/null \$CONTAINER_URL; then
				printf '%s | %s\n' "\$(date)" "Service UP";
			else
				printf '%s | %s\n' "\$(date)" "Service TIMEOUT (7 minutes)";
			fi

			printf '%s | %s\n' "\$(date)" "Wait while container is running...";
			exit_code="\$(docker wait $this->containerName)";
			printf '%s | Container has stopped (exit code = %s) \n' "\$(date)" "\$exit_code";

			echo '# End time:' \$(date) >> $this->log_file_virtual;
		EOF;
		$cmd = "HOST_PORT=$hostPort docker compose -f $dockerComposeFile up -d";

		return $cmd . "\n" . $monitorContainer;
	}


	protected function setBashCommandDockerSge($tool)
	{
		if (is_null($tool['infrastructure']['executable']) && is_null($tool['infrastructure']['container_image'])) {
			$this->logger->error("Tool '$this->toolId' not properly registered. Missing 'executable' or 'container_image' properties");
			throw new UnexpectedValueException("Tool '$this->toolId' not properly registered.");
		}

		$timestamp = date('Y-m-d_H-i-s');
		$this->containerName = $tool['infrastructure']['container_image'] . "_" . $_SESSION['User']['id'] . "_" . $timestamp;
		$cmd_envs = "";
		foreach ($tool['infrastructure']['container_env'] as $env_key => $env_value) {
			$cmd_envs .= "-e $env_key=$env_value ";
		}

		foreach ($tool['infrastructure']['volumes'] as $hostDir => $containerDir) {
			$userHomeDir = $GLOBALS['shared'] . "userdata_tmp/{$_SESSION['User']['id']}" . "/" . $this->project;
			$cmd_envs .= "-v $userHomeDir" . "$hostDir:$containerDir ";
		}

		if ($tool['infrastructure']['interactive']) {
			if ($tool['infrastructure']['docker_type'] == "compose") {
				$cmd = $this->setBashCommandDockerCompose($tool, $cmd_envs);
			} else {
				$cmd = $this->setBashCommandDockerSgeInteractive($tool, $cmd_envs);
			}
		} else {
			$cmd_vre = $tool['infrastructure']['executable'] .
				" --config "         . $this->config_file_virtual .
				" --in_metadata "    . $this->metadata_file_virtual .
				" --out_metadata "   . $this->stageout_file_virtual .
				" --log_file "       . $this->log_file_virtual;


			$cmd =  "docker run --privileged -v /var/run/docker.sock:/var/run/docker.sock -d" .
				" " . $cmd_envs .
				"--memory=" . $tool['infrastructure']['memory'] . "g" .
				" -v " . $this->pub_dir_volumes . ":" . $GLOBALS['shared'] . "public_tmp/ " .
				" -v " . $this->root_dir_volumes . ":" . $GLOBALS['shared'] . "userdata_tmp/{$_SESSION['User']['id']}" .
				" " . $tool['infrastructure']['container_image'] . " $cmd_vre";
		}

		return $cmd;
	}


	protected function setBashCmd_docker_EGA($tool)
	{
		if (is_null($tool['infrastructure']['executable']) && is_null($tool['infrastructure']['container_image'])) {
			$this->logger->error("Tool '$this->toolId' not properly registered. Missing 'executable' or 'container_image' properties");
			throw new UnexpectedValueException("Tool '$this->toolId' not properly registered.");
		}

		$cmd_vre = $tool['infrastructure']['executable'] .
			" --config "       . $this->config_file_virtual .
			" --in_metadata "  . $this->metadata_file_virtual .
			" --out_metadata " . $this->stageout_file_virtual .
			" --log_file "     . $this->log_file_virtual;

		$cmd_envs = "";
		foreach ($tool['infrastructure']['container_env'][0] as $env_key => $env_value) {
			$cmd_envs .= "-e $env_key=$env_value ";
		}

		$vaultKey = $_SESSION['userVaultInfo']['vaultKey'];
		$vaultAddress = $GLOBALS['vaultUrl'] . "/" . $GLOBALS['secretPath'] . $_SESSION['User']['secretsId'] . '/EGA';
		$userFolder = "/shared_data/userdata/" . $_SESSION['User']['id'];
		$configFilePath = $userFolder . '/env.yml';
		$configContent = "VAULT_TOKEN={$vaultKey}\nVAULT_ADDRESS={$vaultAddress}\n";

		if (file_put_contents($configFilePath, $configContent) === false) {
			$this->logger->error("Failed to write configuration file: $configFilePath");
			throw new UnexpectedValueException("Failed to write configuration file: $configFilePath");
		}

		$cmd = "docker run --device /dev/fuse --security-opt apparmor:unconfined --cap-add SYS_ADMIN -v /var/run/docker.sock:/var/run/docker.sock " .
			" " . $cmd_envs .
			" -v " . $this->pub_dir_host .                            ":" . $GLOBALS['shared'] . "public_tmp/ " .
			" -v " . $this->root_dir_host . "/" . $_SESSION['User']['id'] . ":" . $GLOBALS['shared'] . "userdata_tmp/" . $_SESSION['User']['id'] .
			" --tmpfs " . "/clean_files:rw,uid=1000,gid=1000" .
			" --env-file " . $configFilePath .
			" --network=new_vre_open-vre" .
			" -v " . $this->scripts_dir_host . ":/shared_scripts_tmp" .
			" " . $tool['infrastructure']['container_image'] . " $cmd_vre";

		return $cmd;
	}


	protected function setBashCmd_withoutApp($tool, $metadata)
	{
		if (is_null($tool['infrastructure']['executable'])) {
			$this->logger->error("Tool '$this->toolId' not properly registered. Missing 'executable' property");
			throw new NotFoundException("Tool '$this->toolId' not properly registered. Missing 'executable' property");
		}

		$cmd = $tool['infrastructure']['executable'];
		foreach ($this->input_files as $input_name => $fileIds) {
			foreach ($fileIds as $fnId) {
				$filePath  = $metadata[$fnId]['path'];
				$filename = $GLOBALS['dataDir'] . "/$filePath";
				$cmd .= " --$input_name $filename";
			}
		}

		// Add to Cmd: --argument_name value
		foreach ($this->arguments as $key => $value) {
			$cmd .= " --$key $value";
		}

		return $cmd;
	}


	protected function setBashCmd_Slurm($tool, $metadata, $launcherInfo)
	{

		// Ensure that the tool has a registered module to be loaded
		if (is_null($tool['infrastructure']['module'])) {
			$_SESSION['errorData']['Internal Error'][] = "Tool '$this->toolId' not properly registered. Missing 'module' property.";
			return 0;
		}

		//Module name
		$module = $tool['infrastructure']['module'];


		// First cmd
		$cmd = "module load $module && sbatch ";

		//Setting the header of the SLURM script
		$cmd .= "--job-name=" . escapeshellarg($this->toolId) . " ";  // Job name
		if (isset($launcherInfo['launcher']['access_credentials']['username'])) {
			$username = $launcherInfo['launcher']['access_credentials']['username'];
			$remoteOutputDir = "/home/bsc/" . substr($username, 0, 6) . "/$username/MN4/$username";
			$cmd .= "--output=" . escapeshellarg($remoteOutputDir . "/%x_%j.out") . " ";
		} else {
			$_SESSION['errorData']['Internal Error'][] = "Launcher info missing username details.";
		}

		// Check and add the partition (queue)
		if (isset($launcherInfo['launcher']['partition']) && !empty($launcherInfo['launcher']['partition'])) {
			$partition = $launcherInfo['launcher']['partition'];
			$cmd .= "--partition=" . escapeshellarg($partition) . " ";
		} else {
			$_SESSION['errorData']['Internal Error'][] = "Launcher info missing partition/queue details.";
		}

		// Constructing the command 
		$executable = $tool['infrastructure']['executable'];
		$cmd .= "--wrap=\"" . $executable;


		// Adding the inputs
		foreach ($this->input_files as $input_name => $fnIds) {
			$_SESSION['errorData']['Debug'][] = "Processing input:" . $input_name;
			$_SESSION['errorData']['Debug'][] = "File IDs: " . print_r($fnIds, true);
			foreach ($fnIds as $fnId) {

				$_SESSION['errorData']['Debug'][] = "File: " . $fnId;
				$fn = $metadata[$fnId]['path'];
				// $rfn = $GLOBALS['dataDir'] . "/$fn";
				$cmd .= " " . escapeshellarg($fnId);
			}
		}

		// Arguments??
		foreach ($this->arguments as $k => $v) {
			$cmd .= " --$k " . escapeshellarg($v);
		}



		$cmd .= "\"";
		return $cmd;
	}


	protected function createSubmitFile_SGE($cmd)
	{
		$workingDir = $this->working_dir;
		$bashFilename = $this->submission_file;
		$logFilename = $this->log_file;

		$fout = fopen($bashFilename, "w");
		if ($fout === false) {
			$this->logger->error('Failed to create tool configuration file: ' . $bashFilename);
			throw new UnexpectedValueException('Failed to create queue submission file: ' . $bashFilename);
		}

		fwrite($fout, "#!/bin/bash\n");
		fwrite($fout, "# Generated by MuG VRE\n");
		fwrite($fout, "cd $workingDir\n");

		fwrite($fout, "\n# Running $this->toolId tool ...\n");
		fwrite($fout, "\necho '# Start time:' \$(date) > $logFilename\n");

		fwrite($fout, "\n$cmd >> $logFilename 2>&1\n");
		fwrite($fout, "\necho '# End time:' \$(date) >> $logFilename\n");
		fclose($fout);
	}


	protected function createSubmitFile_EGA($cmd)
	{
		$workingDir = $this->working_dir;
		$bashFilename = $this->submission_file;
		$logFilename = $this->log_file;

		if (!is_file($bashFilename)) {
			$this->logger->error("Failed to create queue submission file. " . "File '$bashFilename' does not exist");
			throw new UnexpectedValueException("Failed to create queue submission file. " . "File '$bashFilename' does not exist");
		}

		$fout = fopen($bashFilename, "w");
		if ($fout === false) {
			$this->logger->error('Failed to create tool configuration file: ' . $bashFilename);
			throw new UnexpectedValueException('Failed to create tool configuration file: ' . $bashFilename);
		}

		fwrite($fout, "#!/bin/bash\n");
		fwrite($fout, "# Generated by  VRE\n");

		fwrite($fout, "\n# Running $this->toolId tool ...\n");

		fwrite($fout, "cd $workingDir\n");
		fwrite($fout, "\necho '# Start time:' \$(date) > $logFilename\n");


		fwrite($fout, "\n$cmd >> $logFilename 2>&1\n");
		fwrite($fout, "\necho '# End time:' \$(date) >> $logFilename\n");

		fclose($fout);
	}

	/**
	 * Submits
	 * @param string $inputs_request _REQUEST data from inputs.php form
	 */
	public function submit($tool)
	{
		$jobLauncher = $this->getLauncher_Info($this->cloudName)['launcher']['job_manager'] ?? $tool['infrastructure']['clouds'][$this->cloudName]['launcher'];
		switch ($jobLauncher) {
			case "SGE":
			case "ega_demo":
			case "docker_SGE":
				return $this->enqueue($tool);
			default:
				$this->logger->error("Tool '$this->toolId' not properly registered. Launcher for '$this->toolId' is set to: \"" . $tool['infrastructure']['clouds'][$this->cloudName]['launcher'] . "\". Case not implemented.");
				throw new UnexpectedValueException("Tool '$this->toolId' not properly registered. Launcher for '$this->toolId' is set to: \"" . $tool['infrastructure']['clouds'][$this->cloudName]['launcher'] . "\". Case not implemented.");
		}
	}


	protected function enqueue($tool)
	{
		$launcherInfo = $this->getLauncher_Info($this->cloudName);
		if (is_null($launcherInfo)) {
			$this->logger->error("Launcher information is incomplete or missing.");
			throw new UnexpectedValueException("Launcher information is incomplete or missing.");
		}

		$memory = $launcherInfo['memory'] ?? $tool['infrastructure']['memory'];
		$cpus = $launcherInfo['cpus'] ?? $tool['infrastructure']['cpus'];
		$queue = $launcherInfo['queue'] ?? $tool['infrastructure']['clouds'][$this->cloudName]['queue'];
		$this->logger->info("Resolved Parameters: Queue=$queue, CPUs=$cpus, Memory=$memory");

		$pid = execJob($this->working_dir, $this->submission_file, $queue, $cpus, $memory,  $this->stdout_file, $this->stderr_file);
		$this->logger->info("Tool job submitted to SGE queue '$queue' (PID=$pid)");
		$this->pid = $pid;

		return $pid;
	}


	protected function getPathRelativeToRoot($path)
	{
		if (preg_match('/^\//', $path)) {
			return preg_replace('/^\//', "", str_replace($GLOBALS['dataDir'] . "/" . $_SESSION['User']['id'] . "/", "", $path));
		} else {
			return preg_replace('/^\//', "", str_replace($_SESSION['User']['id'] . "/", "", $path));
		}
	}

	/**
	 * Convert internal VRE file format into DM MuG file
	 * @file  VRE file object, resulting from merging MuGVRE Mongo collections Files + FilesMetadata
	 */
	protected function fromVREfile_toMUGfile($file)
	{
		$mugfile = [];
		$compressions = $GLOBALS['compressions'];
		$mugfile['_id'] = $file['_id'];

		if (isset($file['path'])) {
			if (preg_match('/^\//', $file['path']) || preg_match('/^' . $_SESSION['User']['id'] . '/', $file['path'])) {
				$path = explode("/", $file['path']);
				$mugfile['file_path'] = implode("/", array_slice($path, -3, 3));
			} else {
				$mugfile['file_path'] = $file['path'];
			}
		} else {
			$mugfile['file_path'] = null;
		}

		$mugfile['file_type'] = $file['format'] ?? "UNK";
		$mugfile['data_type'] = $file['data_type'] ?? null;
		$mugfile['data_source'] = $file['data_source'] ?? null;

		if (isset($file['path'])) {
			$ext = pathinfo($file['path'], PATHINFO_EXTENSION);
			$ext = preg_replace('/_\d+$/', "", $ext);
			$ext = strtolower($ext);
			$mugfile['compressed'] = in_array($ext, array_keys($compressions)) ? $compressions[$ext] : 0;
		}

		$mugfile['sources'] = $file['input_files'] ?? [];
		if (!is_array($file['input_files'])) {
			$mugfile['sources'] = [$file['input_files']];
		}

		$mugfile['user_id'] = $file['owner'] ?? $_SESSION['User']['id'];
		$mugfile['creation_time'] = $file['mtime'] ?? new MongoDB\BSON\UTCDateTime(strtotime("now") * 1000);

		$mugfile['taxon_id'] = $file['taxon_id'] ?? (isset($file['refGenome'])
			? ($this->refGenome_to_taxon[$file['refGenome']] ?? 0)
			: 0);

		unset($file['_id']);
		unset($file['path']);
		unset($file['mtime']);
		unset($file['format']);
		unset($file['data_type']);
		unset($file['tracktype']);
		unset($file['submission_file']);
		unset($file['log_file']);
		unset($file['input_files']);
		unset($file['owner']);

		$mugfile['meta_data'] = $file;
		if (isset($mugfile['meta_data']['refGenome'])) {
			$mugfile['meta_data']['assembly'] = $mugfile['meta_data']['refGenome'];
			unset($mugfile['meta_data']['refGenome']);
		}

		return $mugfile;
	}


	/**
	 *
	 */
	protected function array_to_object($array)
	{
		$obj = new stdClass;
		foreach ($array as $k => $v) {
			if (strlen($k)) {
				if (is_array($v)) {
					$obj->{$k} = $this->array_to_object($v); //RECURSION
				} else {
					$obj->{$k} = $v;
				}
			}
		}
		return $obj;
	}


	/**
	 *  Set Cloudname to the default value, as specified in the tool definition
	 *  TODO Choose cloud according where the data is.
	 */
	protected function set_cloudName($tool = array())
	{
		$available_clouds = array_keys($GLOBALS['clouds']);
		if (!count($available_clouds)) {
			$_SESSION['errorData']['Error'][] = "Internal Error: No cloud infrastructure available in the current VRE installation.";
			return 0;
		}

		if (isset($tool['infrastructure']['clouds'])) {
			// 1, set cloudName from default cloud, as tool specifies
			foreach ($tool['infrastructure']['clouds'] as $name => $toolInfo) {
				if ($toolInfo['default_cloud'] === true) {
					if (in_array($name, $available_clouds)) {
						$this->cloudName = $name;
						break;
					}
				}
			}

			// 2, set cloudName from current cloud, if it is in tool specification
			if (!$this->cloudName && isset($GLOBALS['cloud'])) {
				foreach ($tool['infrastructure']['clouds'] as $name => $toolInfo) {
					if ($name == $GLOBALS['cloud']) {
						if (in_array($name, $available_clouds)) {
							$this->cloudName = $name;
							break;
						}
					}
				}
			}
			// 3, set cloudName from clouds list in tool specification, the first found available
			if (! $this->cloudName) {
				foreach ($tool['infrastructure']['clouds'] as $name => $cloudInfo) {
					if (in_array($name, $available_clouds)) {
						$this->cloudName = $name;
						$_SESSION['errorData']['Warning'][] = "Tool has no the default cloud infrastructure set or available. Taking instead '$this->cloudName', but the tool execution may fail.";
						break;
					}
				}
			}
		}
		if (! $this->cloudName) {
			// 4, set cloudName from the server available_clouds, the first
			$this->cloudName = $available_clouds[0];
			$_SESSION['errorData']['Warning'][] = "Tool has no the cloud infrastructure set. Taking '$this->cloudName', but the tool execution may fail.";
		}
		return 1;
	}


	/**
	 * Recreate metadata for input files not included in DMP/Mongo
	 * @param array $input_files Input_files_public_dir as received from inputs.php
	 * @param array $tool Tool array containing input_files type and requirements
	 * @param array $metadata Files metadata extracted from DB
	 */
	public function createMetadata_from_Input_files_public($input_files_public, $tool)
	{

		$metadata_public = array();

		foreach ($input_files_public as $input_name => $input_value) {
			if (count($tool)) {
				// checking coherence between JSON and REQUEST
				if (is_null($tool['input_files_public_dir'][$input_name])) {
					$this->logger->error("Input file public '$input_name' not found in tool definition. '$this->toolId' is not properly registered");
					$_SESSION['errorData']['Internal'][] = "There was an internal error launching the tool.";
					redirect($GLOBALS['BASEURL'] . "workspace/");
				}

				if ($input_value != "") {
					switch ($tool['input_files_public_dir'][$input_name]['type']) {
						case 'enum':
							if (is_null($tool['input_files_public_dir'][$input_name]['enum_items']) || (is_null($tool['input_files_public_dir'][$input_name]['enum_items']['name']))) {
								$this->logger->error("Invalid input_files_public_dir enum in tool definition. '$input_name' has no 'enum_items' or 'enum_items['name].");
								$_SESSION['errorData']['Internal'][] = "There was an internal error launching the tool.";
								redirect($GLOBALS['BASEURL'] . "workspace/");
							}

							if (!in_array($input_value, $tool['input_files_public_dir'][$input_name]['enum_items']['name'])) {
								$this->logger->error("Invalid input_files_public_dir. In '$input_name' these values are accepted [" . implode(", ", $tool['input_files_public_dir'][$input_name]['enum_items']['name']) . "], but found $input_value");
								$_SESSION['errorData']['Internal'][] = "There was an internal error launching the tool.";
								redirect($GLOBALS['BASEURL'] . "workspace/");
							}

							$input_value = strval($input_value);
							break;
						case 'hidden':
						case 'string':
							if (is_array($input_value)) {
								$this->logger->error("Invalid file public. In '$input_name' a string was expected, but found an array: " . implode(",", $input_value));
								$_SESSION['errorData']['Internal'][] = "There was an internal error launching the tool.";
								redirect($GLOBALS['BASEURL'] . "workspace/");
							}
							$input_value = strval($input_value);
							break;
						default:
							$this->logger->error("Input file public '$input_name' has unsupported type (" . $tool['input_files_public_dir'][$input_name]['type'] . "). '$this->toolId' is not properly registered");
							$_SESSION['errorData']['Internal'][] = "There was an internal error launching the tool.";
							redirect($GLOBALS['BASEURL'] . "workspace/");
					}

					$rfn_public = $this->pub_dir . "/$input_value";
					if (!is_file($rfn_public) && !is_dir($rfn_public) && !preg_match('/\$\(.+\)/', $rfn_public)) {
						$_SESSION['errorData']['Error'][] = "Input file public '$input_name' not found in public directory: $rfn_public";
						$this->logger->error("Input file public '$input_name' not found in public directory: $rfn_public");
						$_SESSION['errorData']['Internal'][] = "There was an internal error launching the tool.";
						redirect($GLOBALS['BASEURL'] . "workspace/");
					}

					// get fn and  metadata from DMP #TODO : right now this data is not registered!!
					// create fake metadata
					$fn  = createLabel() . "_dummy";
					$file = array(
						'_id'       => $fn,
						'path' => $input_value,
						'meta_data' => array(),
					);

					if (isset($tool['input_files_public_dir'][$input_name]['data_type']) && is_array($tool['input_files_public_dir'][$input_name]['data_type'])) {
						$file['data_type'] = $tool['input_files_public_dir'][$input_name]['data_type'][0];
					}
					if (isset($tool['input_files_public_dir'][$input_name]['format']) && is_array($tool['input_files_public_dir'][$input_name]['format'])) {
						$file['format'] = $tool['input_files_public_dir'][$input_name]['format'][0];
					}
					$file['owner'] = "public";
					if (is_file($rfn_public)) {
						$file['type'] = "file";
					}
					if (is_dir($rfn_public)) {
						$file['type'] = "dir";
					}
					$metadata_public[$fn] = $file;
				}
			}
		}

		return $metadata_public;
	}

	/**
	 * Assign tool VM size (image type) according the demanded CPUS and RAM 
	 * @cpus integer requested VM cores
	 * @mem  integer requested VM RAM memory
	 */
	protected function setImageType($cpus_requested, $mem_requested)
	{
		$cpus = 0;
		$mem = 0;
		// if not flavors list defined, complain and try default flavor
		if (count($GLOBALS['clouds'][$this->cloudName]['imageTypes']) === 0) {
			$cpus = 4;
			$mem = 8;
			$flavor_name = "large";
			$_SESSION['errorData']['Internal'][] = "Cannot set job virtual machine size for cloud '" . $this->cloudName . "'. Trying with '$flavor_name' ($cpus cores and $mem GB RAM). If job fails, report us please";
			$flavor = ["id" => $flavor_name, "name" => $flavor_name, "disk" => null];
			$flavor['cpus']   = $cpus;
			$flavor['memory'] = $mem;

			return $flavor;
		}

		// navigate flavors list to find the flavor better fits requested mem and cpus
		// first find flavor with the minimal RAM
		foreach ($GLOBALS['clouds'][$this->cloudName]['imageTypes'] as $mem_flavor => $flavors_list_mem) {
			if ($mem_requested > $mem_flavor) {
				continue;
			}

			$mem = $mem_flavor;
			break;
		}

		if (!$mem) {
			$_SESSION['errorData']['Warning'][] = "Cannot set job virtual machine with $cpus_requested cores and $mem_requested GB RAM for cloud '" . $this->cloudName . "'. Assigning maximum RAM = $mem_flavor GB";
			$mem = $mem_flavor;
		}

		// second  find flavor with the minimal cores
		foreach ($GLOBALS['clouds'][$this->cloudName]['imageTypes'][$mem] as $cpus_flavor => $flavor_list_cpu) {
			if ($cpus_requested > $cpus_flavor) {
				continue;
			}

			$cpus = $cpus_flavor;
			break;
		}

		if (!$cpus) {
			$_SESSION['errorData']['Warning'][] = "Cannot set job virtual machine with $cpus_requested cores and $mem_requested GB RAM for cloud '" . $this->cloudName . "'. Assigning maximum cores = $cpus_flavor";
			$cpus = $cpus_flavor;
		}

		$flavor = $GLOBALS['clouds'][$this->cloudName]['imageTypes'][$mem][$cpus];
		$flavor['cpus'] = $cpus;
		$flavor['memory'] = $mem;

		return $flavor;
	}


	public function getSSHCred($vaultUrl, $accessToken, $vaultRolename, $username, $remote_dir, $siteId)
	{
		#retrieve the credential and update the site collection with it
		$vaultClient = new VaultClient($vaultUrl, $accessToken, $vaultRolename, $username);
		$vaultKey = $_SESSION['userVaultInfo']['vaultKey'];
		$credentials = $vaultClient->retrieveDatafromVault($vaultKey, $vaultUrl, $GLOBALS['secretPath'], $_SESSION['User']['secretsId'], 'SSH');
		if ($credentials) {
			$sshPrivateKey = $credentials['priv_key'];
			$sshPublicKey = $credentials['pub_key'];
			$sshUsername = $credentials['hpc_username'];
			$sshId = $credentials['_id'];

			// Set up the credentials array for the RemoteSSH class
			$sshCredentials = [
				'private_key' => $sshPrivateKey,
				'public_key' => $sshPublicKey,
				'username' => $sshUsername
			];

			// Retrieve site info from the sites collection
			$siteDocument = $GLOBALS['sitesCol']->findOne(['_id' => $siteId]);
			// Assuming the site document exists, update the launcher section with SSH credentials
			if ($siteDocument) {
				$siteDocument['launcher']['access_credentials']['username'] = $sshUsername;
				$siteDocument['launcher']['access_credentials']['private_key'] = $sshPrivateKey;
				$siteDocument['launcher']['access_credentials']['public_key'] = $sshPublicKey;
				// Save the updated site document back to the collection
				$updateResult = $GLOBALS['sitesCol']->updateOne(['_id' => $siteId], ['$set' => $siteDocument]);
				$updatedSiteDocument = $GLOBALS['sitesCol']->findOne(['_id' => $siteId]);

				return true;
			} else {
				return array('error' => 'Site document not found for site ID: ' . $siteId);
			}
		} else {
			return array('error' => 'Failed to retrieve SSH credentials from Vault, not present.');
		}
	}


	protected function setHPCRequest($cloudName, $tool, $username)
	{
		if ($cloudName == 'marenostrum') {
			$vaultUrl = $GLOBALS['vaultUrl'];
			$accessToken = $_SESSION['userToken']['access_token'];
			$vaultRolename = $_SESSION['userVaultInfo']['vaultRolename'];

			//Get the credentials
			$remoteSSH = $this->getSSHCred($vaultUrl, $accessToken, $vaultRolename, $username, null, $cloudName);
			if (isset($remoteSSH['error'])) {
				$_SESSION['errorData']['Internal Error'][] = "Failed to retrieve SSH credentials: " . $remoteSSH['error'];
				return 0;
			}

			//Retrieve the launcher details
			$launcherInfo = $this->getLauncher_Info($cloudName);
			if (!$launcherInfo || empty($launcherInfo)) {
				$_SESSION['errorData']['Internal Error'][] = "Cannot set tool command line. Launcher details are not available.";
				return 0;
			}

			//Set Bash command for Slurm
			$cmd = $this->setBashCmd_Slurm($tool, $metadata, $launcherInfo);
			if (!$cmd) {
				return 0;
			}


			return $cmd; //Return the command if everything is fine for MN
		} else {
			//For future HPC environments
			$_SESSION['errorData']['Internal Error'][] = "Cloud environment '$cloudName' is not supported yet.";
			return 0;
		}
	}


	function getLauncher_Info($siteId)
	{
		$siteDocument = $GLOBALS['sitesCol']->findOne(['_id' => $siteId]);
		if (is_null($siteDocument)) {
			return null;
		}

		return [
			'site_id' => $siteDocument['_id'],
			'name' => $siteDocument['name'],
			'launcher' => $siteDocument['launcher']
		];
	}
}
