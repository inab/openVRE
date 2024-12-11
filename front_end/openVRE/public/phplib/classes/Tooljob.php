<?php

class Tooljob {

    public $_id;
    public $title;
    public $execution;         // User defined. Correspond to the execution folder name
    public $project;           // User defined. Correspond to the project
    public $toolId;
    public $pub_dir;           // Public dir mounted to VMs. Path as seen by VRE
    public $root_dir;          // User dataDir. Mounted to VMs in PMES. Already there in SGE. Path as seen by VRE 
    public $root_dir_virtual;  // User dataDir. Mounted to VMs in PMES. Already there im SGE. Path as seen by VMs
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

    public $stageout_data   = [];
    public $input_files     = [];
    public $input_files_pub = [];
    public $input_paths_pub = [];
    public $arguments       = [];
    public $metadata        = [];
    public $pid             = 0;
    public $start_time      = 0;
    public $hasExecutionFolder= true;
    #public $refGenome_to_taxon = Array( "hg38"=>"9606" ,  "hg19"=>"9606", "R64-1-1"=>"4932", "r5_01"=>"7227");


    /**
     * Creates new toolExecutor instance
     * @param string $toolId Tool Id as appears in Mongo
    */
    public function __construct($tool,$execution="",$project="",$descrip="", $arguments_exec="" ,$output_dir=""){
    

    	// Setting Tooljob
    	$this->toolId    = $tool['_id'];
    	$this->title     = $tool['name'] ." job";
        $this->execution = $execution;
        $this->project   = $project;

        // Set paths in VRE
        $this->root_dir  = $GLOBALS['dataDir']."/".$_SESSION['User']['id'];
	$this->pub_dir   = $GLOBALS['pubDir'];
	$this->arguments_exec = $arguments_exec;
	print "<br/>Constructor: Stored execution arguments:</br>";
        var_dump($this->arguments_exec);

        // Set paths in the virtual machine
        //$this->set_cloudName($tool);
	//$this->launcher         = $tool['infrastructure']['clouds'][$this->cloudName]['launcher'];
	//$_SESSION['errorData']['Error'][]="Tool '$this->toolId' '$this->cloudName' '$this->launcher' ";

	if (!empty($this->arguments_exec['site_list']) && count($this->arguments_exec['site_list']) >= 1) {
		$site_list = $this->arguments_exec['site_list'];
		// The first element in site_list is the cloudName
		$this->cloudName = $site_list[0];
		
		// The second element in site_list is the launcher
		$this->launcher = str_replace($this->cloudName . "_", "", $site_list[1]);
		var_dump($this->launcher);
		var_dump($this->cloudName);
    
	} else {
		
		// If not enough information is provided, fall back to default method
		$this->set_cloudName($tool);
		$this->launcher = $tool['infrastructure']['clouds'][$this->cloudName]['launcher'];
	
	}	
        switch ($this->launcher){
            case "SGE":
	    case "docker_SGE":
			$this->root_dir_virtual = $GLOBALS['clouds'][$this->cloudName]['dataDir_virtual']. "/".$_SESSION['User']['id'];
					$this->root_dir_mug     = $GLOBALS['clouds'][$this->cloudName]['dataDir_virtual'];
					$this->pub_dir_virtual  = $GLOBALS['clouds'][$this->cloudName]['pubDir_virtual'];
			$this->pub_dir_volumes  = $GLOBALS['clouds'][$this->cloudName]['pubDir_host'];
			$this->root_dir_volumes  = $GLOBALS['clouds'][$this->cloudName]['dataDir_host']. "/".$_SESSION['User']['id'];
			$this->pub_dir_intern   = rtrim($this->pub_dir_virtual,"/"). "_tmp";
	    case "ega_demo":
                $this->root_dir_virtual = $GLOBALS['clouds'][$this->cloudName]['dataDir_virtual']. "/".$_SESSION['User']['id'];
                $this->pub_dir_virtual  = $GLOBALS['clouds'][$this->cloudName]['pubDir_virtual'];
		$this->root_dir_host    = $GLOBALS['clouds'][$this->cloudName]['dataDir_host'];
		$this->pub_dir_host     = $GLOBALS['clouds'][$this->cloudName]['pubDir_host'];
		$this->scripts_dir_host = $GLOBALS['clouds'][$this->cloudName]['scriptsDir_host'];
		break;
            case "PMES":
                $this->root_dir_virtual = $GLOBALS['clouds'][$this->cloudName]['dataDir_virtual'];
                $this->pub_dir_virtual  = $GLOBALS['clouds'][$this->cloudName]['pubDir_virtual'];
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
                    $this->root_dir_df = $GLOBALS['clouds'][$this->cloudName]['mn_dir'] .  "/".substr($_SESSION['User']['linked_accounts']['MN']['username'], 0, 6). "/".$_SESSION['User']['linked_accounts']['MN']['username']. "/". $GLOBALS['clouds'][$this->cloudName]['dataDir_fs'];
                    $this->pub_dir_fs = $GLOBALS['clouds'][$this->cloudName]['mn_dir'] .  "/".substr($_SESSION['User']['linked_accounts']['MN']['username'], 0, 6)."/".$_SESSION['User']['linked_accounts']['MN']['username']. "/". $GLOBALS['clouds'][$this->cloudName]['pubDir_fs'];
                //$this->pub_dir_virtual  = $GLOBALS['clouds'][$this->cloudName]['pubDir_virtual'];
                //$this->root_dir_fs = $GLOBALS['clouds'][$this->cloudName]['dataDir_fs'];
                //$this->pub_dir_fs = $GLOBALS['clouds'][$this->cloudName]['pubDir_fs'];
                    $this->auth = $GLOBALS['clouds'][$this->cloudName]['auth'];
		    $this->http_host = $GLOBALS['clouds'][$this->cloudName]['http_host'];
		    break;
                //$this->port = $GLOBALS['clouds'][$this->cloudName]['port'];     
            default:
                $_SESSION['errorData']['Error'][]="Tool '$this->toolId' not properly registered. Launcher type is set to '".$this->launcher."'. Case not implemented.";
        }

    	// Creating execution folder
        if ($execution != "0"){
            //create Project Folder 
            $this->hasExecutionFolder = true;
            $this->__setWorking_dir($execution);
            $this->output_dir = $this->working_dir;
        }else{
            //internalTool
            $this->hasExecutionFolder = false;
            $this->__setWorking_inTmp($tool['_id']);
            $this->output_dir = $output_dir;
        }
    
        // Set description
        if ($descrip != "")
    		$this->setDescription($descrip,$tool['name']);

        // Set project
        if ($project == "0" || $project == ""){
            $this->project = $_SESSION['User']['activeProject'];
        }else{
            //TODO Check project exists
            if(isProject($project)){
                $this->project = $project;
            }else{
                $_SESSION['errorData']['Warning'][]="Given project code '$project' not valid. Setting job as part of last active project.";
                $this->project = $_SESSION['User']['activeProject'];
            }
        }


    	return $this;
    }


    /**
     * Fetch tool entry in Mongo 
     * @param string $toolId Tool Id as appears in Mongo
    */
    protected function getTool($toolId){
	$tool   = $GLOBALS['toolsCol']->findOne(array('_id' => $toolId));
	if (empty($tool)){
                $_SESSION['errorData']['Tooljob'][]="Tool '$toolId' is not registered. Cannot submit execution. Please, contact <a href=\"mailto:".$GLOBALS['helpdeskMail']."\">us</a>";
                return 0;
	}
	//$this->tool= (object) $tool;
	$this->tool= $this->array_to_object($tool);
    }


    /**
     * Set description
     * @param string $descrip Short execution description to annotate execution directory
    */
    public function setDescription($descrip,$toolName=0){
        if (strlen($descrip))
                $this->description=$descrip;
	elseif($toolName)
                $this->description="Execution directory for tool ".$toolName;
	else
                $this->description="Execution directory";
    }

    public function setLog($filename = "") {
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

    public function __setWorking_dir($execution, $overwrite = 0) {
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
						// $workingDir = $GLOBALS['dataDir']."/$localWorkingDir"; // TODO: check if needed
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
		$this->submission_file= "{$this->working_dir}/{$GLOBALS['tool_submission_file']}";
		$this->log_file       = "{$this->working_dir}/{$this->logName}";
		$this->metadata_file  = "{$this->working_dir}/{$GLOBALS['tool_metadata_file']}";
		$this->stdout_file    = $this->working_dir."/job_output.log";
        $this->stderr_file    = $this->working_dir."/job_error.log";

			// for interactive visualizer
	$this->input_dir_virtual = $this->root_dir_virtual."/".$this->project."/".$this->execution."/uploads";

		$this->config_file_virtual    = "{$this->root_dir_virtual}/{$this->project}/{$this->execution}/{$GLOBALS['tool_config_file']}";
		$this->stageout_file_virtual  = "{$this->root_dir_virtual}/{$this->project}/{$this->execution}/{$GLOBALS['tool_stageout_file']}";
		$this->metadata_file_virtual  = "{$this->root_dir_virtual}/{$this->project}/{$this->execution}/{$GLOBALS['tool_metadata_file']}";
		$this->log_file_virtual       = "{$this->root_dir_virtual}/{$this->project}/{$this->execution}/{$this->logName}";
    }


    public function __setWorking_inTmp($prefixDir=0){

	if (!$prefixDir)
            $prefixDir = "tool_";

        $execution = $prefixDir."_".rand(10000, 99999);

        $this->execution      = $execution;
        $this->working_dir    = $this->root_dir."/".$this->project."/".$GLOBALS['tmpUser_dir'].$this->execution;

        if (!$this->logName){$this->logName = $GLOBALS['tool_log_file'];}

    	$this->config_file    = $this->working_dir."/".$GLOBALS['tool_config_file'];
    	$this->stageout_file  = $this->working_dir."/".$GLOBALS['tool_stageout_file'];
        $this->submission_file= $this->working_dir."/".$GLOBALS['tool_submission_file'];
        $this->log_file       = $this->working_dir."/".$this->logName;
        $this->metadata_file  = $this->working_dir."/".$GLOBALS['tool_metadata_file'];


        $this->config_file_virtual    = $this->root_dir_virtual."/".$this->project."/".$GLOBALS['tmpUser_dir'].$this->execution."/".$GLOBALS['tool_config_file'];
        $this->stageout_file_virtual  = $this->root_dir_virtual."/".$this->project."/".$GLOBALS['tmpUser_dir'].$this->execution."/".$GLOBALS['tool_stageout_file'];
        $this->metadata_file_virtual  = $this->root_dir_virtual."/".$this->project."/".$GLOBALS['tmpUser_dir'].$this->execution."/".$GLOBALS['tool_metadata_file'];
        $this->log_file_virtual       = $this->root_dir_virtual."/".$this->project."/".$GLOBALS['tmpUser_dir'].$this->execution."/".$this->logName;
    }


    /**

    /**
     * Create working directory
    */
    public function createWorking_dir() {
        if (!$this->working_dir) {
    		$_SESSION['errorData']['Internal Error'][] = "Cannot create working_dir. Not set yet";
    		return 0;
    	}

    	$dirPath = str_replace($GLOBALS['dataDir']."/", "", $this->working_dir);
        $hasExecutionFolder = $this->hasExecutionFolder;
    	// create working dir - disk and db
    	if (!is_dir($this->working_dir)) {
			$this->_id = 1;
            if ($hasExecutionFolder) {
            	$dirId = createGSDirBNS($dirPath);
				if ($dirId == "0") {
					$_SESSION['errorData']['Error'][] = "Cannot create execution folder: '$this->working_dir'";
					return 0;
				}
				
				$this->_id = $dirId;
			}

			if (!mkdir($this->working_dir, 0777, true)) {
				$_SESSION['errorData']['Error'][] = "Failed to create directory: '$this->working_dir'";
				return 0;
			}
			
			chmod($this->working_dir, 0777);

    	// if exists, recover working dir id
		} else {
            if ($hasExecutionFolder) {
    			$dirId = getGSFileId_fromPath($dirPath);
    			$_SESSION['errorData']['Error'][] = "Cannot set job. Requested execution folder (".basename($dirPath).") already exists. Please, set another execution name.<br>";
			
				return 0;
	    	}

			$this->_id = 1;
        }

    	// set dir metadata
		if ($this->_id != 1) {
    		if (!is_dir($this->working_dir)) {
    	        	$_SESSION['errorData']['Error'][] = "Cannot write and set new execution directory: '$this->working_dir' with id '$this->_id'";
    			return 0;
			}
    	
			$input_ids = [];
			array_walk_recursive($this->input_files, function($v, $k) use (&$input_ids){ $input_ids[] = $v; });
			$input_ids = array_unique($input_ids);
    		$projDirMeta = [
    			'description'     => $this->description,
    		    'input_files'     => $input_ids,
                'tool'            => $this->toolId,
    			'submission_file' => $this->submission_file,
    			'log_file'        => $this->log_file,
	            'arguments'       => array_merge($this->arguments, $this->input_paths_pub)
			];

    		$addedMetadata = addMetadataBNS($this->_id, $projDirMeta);
    		if ($addedMetadata == "0") {
	            $_SESSION['errorData']['Error'][] = "Project folder created. But cannot set metada for '$this->working_dir' with id '$this->_id'";
                return 0;
            }
        }

    	return $this->_id;
    }


    /**
     * Creates tool configuration JSON
     * @param array $tool Fill in config file: input_files, arguments and output_files
    */
    public function setConfiguration_file($tool) {
		$configFilename = $this->config_file;
		if (!$this->working_dir) {
			$_SESSION['errorData']['Internal Error'][] = "Cannot create tool configuration file. No 'working_directory' set";
			return 0;
		}

		$data = [
			'input_files' => [],
			'arguments'=> [
				["name" => "execution", "value"=> $this->root_dir_virtual."/".$this->project."/".$this->execution],
				["name" => "project", "value"=> $this->root_dir_virtual."/".$this->project."/".$this->execution],
				["name" => "description", "value"=> $this->description],
			],
			'output_files' => []
		];
		
		foreach ($this->input_files as $key => $values) {
			foreach ($values as $value) {
				array_push($data['input_files'], [
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
				array_push($data['input_files'], [
						"name"           => $key,
						"value"          => $v,
						"required"       => $tool['input_files_public_dir'][$key]['required'],
						"allow_multiple" => $tool['input_files_public_dir'][$key]['allow_multiple']
					]
				);
			}
		}

		foreach ($this->arguments as $key => $value){
			array_push($data['arguments'], ["name" => $key, "value" => $value]);
		}

		if ($tool['output_files']) {
			foreach ($tool['output_files'] as $key => $value) {
				if (isset($value['file']['file_path'])) {
					$value['file']['file_path'] = $this->root_dir_virtual."/".$this->project."/".$this->execution ."/".$value['file']['file_path'];
				}

				$data['output_files'][] = $value;
			}
		}

		try {
			$F = fopen($configFilename, "w");
			if (!$F) {
				throw new Exception("Failed to create tool configuration file $configFilename");
			}
		} catch (Exception $e) {
			$_SESSION['errorData']['Internal Error'][] = $e->getMessage();
			return 0;
		}

		fwrite($F, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
		fclose($F);

		return $configFilename;
    }


    /**
     * Set Arguments
     * @param array $arguments Arguments as received from inputs.php
    */
    public function setArguments($arguments, $tool = []) {
		foreach ($arguments as $arg_name => $arg_value) {
			if (count($tool)) {
				// checking coherence between JSON and REQUEST
				if (!isset($tool['arguments'][$arg_name])) {
					$_SESSION['errorData']['Internal'][] = "Argument '$arg_name' not found in tool definition. '$this->toolId' is not properly registered";
					return 0;
				}

				// checking arguments requirements (TODO create 'validateArguments')
				if ($arg_value == "") {
					if ($tool['arguments'][$arg_name]['required']) {
						$_SESSION['errorData']['Error'][] = "No value given for argument '$arg_name'";
						return 0;
					}

					continue;
				}

				switch ($tool['arguments'][$arg_name]['type']) {
					case "enum":
						if (!isset($tool['arguments'][$arg_name]['enum_items']) || (!isset($tool['arguments'][$arg_name]['enum_items']['name']))) {
							$_SESSION['errorData']['Internal'][] = "Invalid argument enum in tool definition. '$arg_name' has no 'enum_items' or 'enum_items['name].";
							return 0;
						}

						if (!in_array($arg_value, $tool['arguments'][$arg_name]['enum_items']['name'])) {
							$_SESSION['errorData']['Error'][] = "Invalid argument. In '$arg_name' these values are accepted [".implode(", ",$tool['arguments'][$arg_name]['enum_items']['name'])."], but found $arg_value";
							return 0;
						}

						break;

					case "enum_multiple":
						if (!isset($tool['arguments'][$arg_name]['enum_items']) || (!isset($tool['arguments'][$arg_name]['enum_items']['name']))){
							$_SESSION['errorData']['Internal'][] = "Invalid argument enum in tool definition. '$arg_name' has no 'enum_items' or 'enum_items['name].";
							return 0;
						}

						if (!is_array($arg_value)) {
							$arg_value = [$arg_value];
						}

						foreach ($arg_value as $v) {
							if (!in_array($v, $tool['arguments'][$arg_name]['enum_items']['name'])) {
								$_SESSION['errorData']['Error'][] = "Invalid argument. In '$arg_name' these values are accepted [".implode(", ", $tool['arguments'][$arg_name]['enum_items']['name'])."], but found ".implode(", ", $arg_value);
								return 0;
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
							return 0;
						}

						break;

					case "integer":
						if (!is_numeric($arg_value)) {
							$_SESSION['errorData']['Error'][] = "Invalid argument. In '$arg_name' an integer was expected, but found: $arg_value";
							return 0;
						}

						$arg_value = intval($arg_value);
						break;

					case "number":
						if (!is_numeric($arg_value)) {
							$_SESSION['errorData']['Error'][] = "Invalid argument. In '$arg_name' a number was expected, but found: $arg_value";
							return 0;
						}

						break;

					case "hidden":
					case "string":
						if (is_array($arg_value)) {
							$_SESSION['errorData']['Error'][] = "Invalid argument. In '$arg_name' a string was expected, but found an array: ".implode(",",$arg_value);
							return 0;
						}

						$arg_value = strval($arg_value);
						break;

					//case "enum": //TODO: check if the correct is the previous one
					default:
						$_SESSION['errorData']['Internal'][] = "Invalid argument type in tool definition. '$arg_name' is of type ".$tool['arguments'][$arg_name]['type'];
						return 0;
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
    public function setInput_files($input_files, $tool = [], $metadata = []) {
		foreach ($input_files as $input_name => $filenames) {
			if (count($tool) && count($metadata)) {
				if (!is_array($filenames)) {
					$filenames = [$filenames];
				}

				foreach ($filenames as $filename) {
					// checking coherence between JSON and REQUEST
					if (!isset($tool['input_files'][$input_name])) {
						$_SESSION['errorData']['Internal'][]="Input file '$input_name' not found in tool definition. '$this->toolId' is not properly registered";
						return 0;
					}

					if (!$filename) {
						if ($tool['input_files'][$input_name]['required'] === true ) {
							$_SESSION['errorData']['Error'][] = "No file given for '$input_name'";
							return 0;
						}

						if (($k = array_search($filename, $filenames)) !== false) {
							unset($filenames[$k]);
						}

						continue;
					}

					if (!isset($metadata[$filename])) {
						if ($tool['input_files'][$input_name]['required'] === true) {
							$_SESSION['errorData']['Error'][] = "Given file in '$input_name' has no metadata";
							return 0; // Comentarlo si no hay metadatos
						}
					}
					// checking input_file integrity
		/*			$ok = $this->validateInput_file($tool['input_files'][$input_name], $metadata[$filename]);
					if (! $ok){
						$_SESSION['errorData']['Error'][]="Input file '$input_name' not valid. Stopping '$this->toolId' execution";
						return 0;
					}*/
				}   
			}

			if (count($filenames)) {
				$this->input_files[$input_name] = $filenames;
			}
		}
		/*
		if (count($tool['input_files'])){
			foreach ($tool['input_files'] as $input_name => $input){
				if (!isset($input_files[$input_name]) && $input['required'] ){
					$_SESSION['errorData']['Error'][]="Input file '$input_name' is required. Input not given";
					return 0;
				}
			}
		}*/
		return 1;
    }

    /**
     * Set inputFiles from public directory
     * @param array $input_files_public Input_files_public_dir as received from inputs.php
     * @param array $tool Tool array containing input_files type and requirements
     * @param array $metadata_pub Files metadata extracted from DB
    */


    public function setInput_files_public($input_files_public,$tool=array(),$metadata_pub=array()){
    foreach ($input_files_public as $input_name => $input_values){

        $fns = array();
        //checking  requirements
        if (count($tool) && count($metadata_pub)){
		    if (!is_array($input_values))
			    $input_values=array($input_values);

            foreach ($input_values as $input_value){
            	// checking value not empty
		if (!$input_value){
			$_SESSION['errorData']['Error'][]="No value given public file '$input_name'";
			return 0;
		}
		// checking coherence between JSON and REQUEST
		if (!isset($tool['input_files_public_dir'][$input_name])){
			$_SESSION['errorData']['Internal'][]="Input file public '$input_name' not found in tool definition. '$this->toolId' is not properly registered";
			return 0;
            }
            // replacing file_path by fn in input_files_pub
            $fn = "";
            foreach ($metadata_pub as $f => $file){
                if ($file['file_path'] == $input_value){
                    $fn = $f;
                }
            }
            if ($fn){
                array_push($fns,$fn);
            }else{
                $_SESSION['errorData']['Error'][]="Input file public '$input_name' with value '$input_value' not found in public directory";
                return 0;
            }
    	    // checking input_file integrity
/*	        $ok = $this->validateInput_file($tool['input_files_public'][$input_name], $metadata_pub[$fn]);
		    if (! $ok){
			   	$_SESSION['errorData']['Error'][]="Input file public '$input_name' not valid. Stopping '$this->toolId' execution";
                return 0;
            }
            */
            }
        }
        // setting input_files
	    $this->input_files_pub[$input_name]=$fns;
	    $this->input_paths_pub[$input_name]=$input_values[0];

        }
        return 1;
    }

    /**
     * Store its metadata in Tooljob for recovering it latter, while stageout register
     * Needed when tool has not APP (internal), and no out_metadata is generated. 
     * @param array $outs Array of outputfiles
     * @param array $tool Tool array containing input_files type and requirements TODO
     * @param array $metadata Files metadata extracted from DB TODO
    */
    public function setStageout_data($out_files, $tool = [], $metadata = []) {
		if (!isset($out_files['output_files'])) {
			$_SESSION['errorData']['Error'][] = "Internal tool may have problems registering outfiles: Stageout_data mal formatted";
			return 0;
		}

		$this->stageout_file="";
		foreach ($out_files['output_files'] as $out_name => $info) {
			//Validate out_files against tool document
			//TODO
			
			//Add output file metadata
			$this->stageout_data['output_files'][$out_name] = $info;
		}

		return 1;
    }


    /**
     * Check input files requirements based on format and datatype
     * @param array $inputReq  Input_file as defined in tool collection (derived from tool JSON definition)
     * @param array $inputMetadata File metadata
    */
    protected function validateInput_file($inputReq, $inputMetadata){
	if (!isset($inputReq['file_type']) && !isset($inputReq['data_type']) ){
		$_SESSION['errorData']['Warning'][]="Ommitting format and type control for input file '".$inputReq['name'].". Tool has no 'file_type' nor 'data_type' set.";
		return 1;
	}
	if (!isset($inputMetadata['format']) && !isset($inputReq['data_type']) ){
		$_SESSION['errorData']['Warning'][]="Ommitting format and type control for input file '".$inputReq['name'].". Given file has no 'file_type' nor 'data_type' set.";
		return 1;
	}
	// checking format
	if (isset($inputReq['file_type']) &&  isset($inputMetadata['format'])){
		if (!in_array($inputMetadata['format'],$inputReq['file_type'])){
			$_SESSION['errorData']['Error'][]="Input file '".basename($inputMetadata['path'])."' in '".$inputReq['name']." has format '".$inputMetadata['format']."  and '".implode(", ",$inputReq['file_type'])."' was excepted.";
			return 0;

		}
	}
	// checking datatype
	if (isset($inputReq['data_type']) &&  isset($inputMetadata['data_type'])){
		if (!in_array($inputMetadata['data_type'],$inputReq['data_type'])){
			$_SESSION['errorData']['Error'][]="Input file '".basename($inputMetadata['path'])."' in '".$inputReq['name']." is a '".$inputMetadata['data_type']."  and '".implode(", ",$inputReq['data_type'])."' was excepted.";
			return 0;

		}
	}
	return 1;
    }


    /**
     * Creates metadata JSON
    */
    public function setMetadata_file($metadata, $metadata_pub = []) {
		if (!$this->working_dir) {
			$_SESSION['errorData']['Internal Error'][] = "Cannot create metadata file. No 'working_dir' set";
			return 0;
		}

		$fileMuGs = [];
		// add input_files metadata
		foreach ($metadata as $fileId => $file) {
			// convert metadata to DMP format
			$fileMuG = $this->fromVREfile_toMUGfile($file);

			// adapt metadata to App requirements
			if (isset($fileMuG['sources'])) {
				$source_list = [];
				foreach ($fileMuG['sources'] as $sourceid) {
					if ($sourceid) {
						$source_path = getAttr_fromGSFileId($sourceid, "path");
						if ($source_path) {
							array_push($source_list, $this->root_dir_virtual."/".$source_path);
						}
					}
				}

				$fileMuG['sources'] = $source_list;
			}

			if ($fileMuG['data_source'] == "EGA") {
				$fileMuG['file_path'] = "/clean_files/".$file['ega_path']; // TODO: hardcoded ega path
			}

			if ($fileMuG['file_path']){
				$fileMuG['file_path'] = $this->root_dir_virtual."/".$fileMuG['file_path'];
			}
	

			if ($fileMuG['meta_data']['parentDir']) {
				$parent_path = getAttr_fromGSFileId($fileMuG['meta_data']['parentDir'], "path");
				if ($parent_path) {
					$fileMuG['meta_data']['parentDir'] = $this->root_dir_virtual."/".$parent_path;
				}
			}

			array_push($fileMuGs, $fileMuG);
		}

		// add input_files public metadata
		if (count($metadata_pub)) {
			foreach ($metadata_pub as $fileId => $fileMuG) {
				// adapt metadata to App requirements
				if (isset($fileMuG['sources'])) {
					$source_list = [];
					foreach($fileMuG['sources'] as $sourceid) {
						if ($sourceid) {
							$source_path = getAttr_fromGSFileId($sourceid, "path");
							if ($source_path) {
								array_push($source_list, $this->public_dir_virtual."/".$source_path);
							}
						}
					}

					$fileMuG['sources'] = $source_list;
				}

				$fileMuG['file_path'] ??= $this->pub_dir_virtual."/".$fileMuG['file_path'];
				if ($fileMuG['meta_data']['parentDir']) {
					$parent_path = getAttr_fromGSFileId($fileMuG['meta_data']['parentDir'], "path");
					if ($parent_path) {
						$fileMuG['meta_data']['parentDir'] = $this->root_dir_virtual."/".$parent_path;
					}
				}

				array_push($fileMuGs, $fileMuG);
			}
		}

		$metadataFile = $this->metadata_file;
		try {
			$F = fopen($metadataFile, "w");
			if (!$F) {
				throw new Exception('Failed to create metadata file for tool execution'.$metadataFile);
			}
		} catch (Exception $e) {
			$_SESSION['errorData']['Internal Error'][] = $e->getMessage();
			return 0;
		}

		fwrite($F, json_encode($fileMuGs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
		fclose($F);

		return $metadataFile;
    }


    /**
     * Creates execution Command Line and Submission File
    */
    public function prepareExecution($tool, $metadata, $metadata_pub = []) {
		$launcher = $this->launcher;
		$cloudName = $this->cloudName;

		if ($tool['external'] !== false) {
			$configFilename = $this->setConfiguration_file($tool);
			if ($configFilename == "0") {
				return 0;
			}
		
			$metadataFile = $this->setMetadata_file($metadata, $metadata_pub);
			if ($metadataFile == "0") {
				return 0;
			}

			if (!is_file($this->config_file) && !is_file($this->metadata_file)) {
				$_SESSION['errorData']['Internal Error'][] = "Cannot set tool command line. It required configuration file ($this->config_file) and metadata file ($this->metadata_file)";
				return 0;
			}

			switch ($launcher) {
				case "SGE":
					$cmd  = $this->setBashCmd_SGE($tool);
					if (!$cmd) {
						return 0;
					}
			
					$submissionFilename = $this->createSubmitFile_SGE($cmd); 
					if (!is_file($submissionFilename)) {
						return 0;
					}

					break;

				case "docker_SGE":
					$cmd  = $this->setBashCmd_docker_SGE($tool);
					if (!$cmd) {
						return 0;
					}

					$submissionFilename = $this->createSubmitFile_SGE($cmd);
					if (!is_file($submissionFilename)) {
						return 0;
					}

					break;

				case "PMES":
					$json_data = $this->setPMESrequest($tool);
					if (!$json_data) {
						return 0;
					}

					$submissionFilename = $this->createSubmitFile_PMES($json_data);
					if (!is_file($submissionFilename)) {
						return 0;
					}

					break;

				case "ega_demo":
					$cmd  = $this->setBashCmd_docker_EGA($tool);
					if (!$cmd) {
						return 0;
					}
			
					$submissionFilename = $this->createSubmitFile_EGA($cmd); 
					if (!is_file($submissionFilename)) {
						return 0;
					}

					break;
		
				case "Slurm": 
					//$_SESSION['errorData']['Internal Error'][]="Cannot set tool command line. Case still not implemented.";    
	
					$username = $_POST['username'];
					$cmd = $this->setHPCRequest($cloudName, $tool, $username);
					if (!$cmd) {
						return 0;
					}
					$_SESSION['errorData']['Debug'][] = "CMD:" . $cmd;
					break;
				default:
					$_SESSION['errorData']['Error'][]="Tool '$this->toolId' not properly registered. Launcher for '$this->toolId' is set to \"$launcher\". Case not implemented.";
					return 0;
			}

			return 1;
		}
		
		if ($tool['external'] === false) {
			switch ($launcher) {
				case "SGE":
					$cmd = $this->setBashCmd_withoutApp($tool,$metadata);
					if (!$cmd) {
						return 0;
					}

					$submissionFilename = $this->createSubmitFile_SGE($cmd); 
					if (!is_file($submissionFilename)) {
						return 0;
					}

					break;
			
				case "PMES":
				//TODO
		
				default:
					$_SESSION['errorData']['Error'][] = "Internal Tool '$this->toolId' not properly registered. Launcher for '$this->toolId' is set to \"$launcher\". Case not implemented.";
					return 0;
			}

			return 1;
		}
    }

    protected function setBashCmd_SGE($tool) {
		if (!isset($tool['infrastructure']['executable'])) {
			$_SESSION['errorData']['Internal Error'][] = "Tool '$this->toolId' not properly registered. Missing 'executable' property";
			return 0;
		}

		$cmd = $tool['infrastructure']['executable'] .
					" --config "         .$this->config_file_virtual .
					" --in_metadata "    .$this->metadata_file_virtual .
					" --out_metadata "   .$this->stageout_file_virtual .
					" --log_file "       .$this->log_file_virtual;

		return $cmd;
    }

    protected function setBashCmd_docker_SGE_TOBEDELETED($tool){
        if (!isset($tool['infrastructure']['executable']) && !isset($tool['infrastructure']['container_image'])){
            $_SESSION['errorData']['Internal Error'][]="Tool '$this->toolId' not properly registered. Missing 'executable' or 'container_image' properties";
            return 0;
	}
	#docker run --privileged -v /var/run/docker.sock:/var/run/docker.sock -v /home/user/dockerized_vre/volumes/shared_data/public:/shared_data/public -v /home/user/dockerized_vre/volumes/shared_data/userdata/user1:/shared_data/userdata/user1 re /response_estimation/VRE_RUNNER --config /shared_data/userdata/user1/proj1/runlaia/config.json --in_metadata /shared_data/userdata/user1/proj1/runlaia/in_metadata.json --out_metadata /shared_data/userdata/user1/proj1/runlaia/out_metadata.json --log_file /shared_data/userdata/user1/proj1/runlaia/VRE_RUNNER.log
	

	$cmd_vre = $tool['infrastructure']['executable'] .
                                " --config "         .$this->config_file_virtual .
                                " --in_metadata "    .$this->metadata_file_virtual .
                                " --out_metadata "   .$this->stageout_file_virtual .
				" --log_file "       .$this->log_file_virtual ;

	$cmd = "docker run --privileged" .
		" -v /var/run/docker.sock:/var/run/docker.sock " .
		" -v " . $GLOBALS['pubDir']. ":" . $this->pub_dir_virtual  .
		" -v " . $GLOBALS['dataDir'].":" . $this->root_dir_virtual .
		" ".$tool['infrastructure']['container_image'] . " $cmd_vre";

        return $cmd;
    }


    protected function setBashCmd_docker_SGE_old($tool){
	    
	    if (!isset($tool['infrastructure']['executable']) && !isset($tool['infrastructure']['container_image'])){
		    $_SESSION['errorData']['Internal Error'][]="Tool '$this->toolId' not properly registered. Missing 'executable' or 'container_image' properties";
		    return 0;
	    }
	    
	    $cmd = "FREE_PORT=$(python -c 'import socket; s=socket.socket(); s.bind((\"\", 0)); print(s.getsockname()[1]); s.close()');\n";
	    $cmd_vre = $tool['infrastructure']['executable'] .
		    " --config "       .$this->config_file_virtual .
		    " --in_metadata "  .$this->metadata_file_virtual .
		    " --out_metadata " .$this->stageout_file_virtual ;
		    " --log_file "     .$this->log_file_virtual ;
	    
	    $cmd_envs = "";
	    foreach ($tool['infrastructure']['container_env'][0] as $env_key => $env_value) {
		    $cmd_envs .= "-e $env_key=$env_value ";
	    }

	    if (isset($tool['infrastructure']['interactive'])) {
		    $cmd .= "docker run --privileged -v /var/run/docker.sock:/var/run/docker.sock -d " .
			    " ". $cmd_envs .
			    " -p ". "\$FREE_PORT" . ":". $tool['infrastructure']['container_port'] . #change second one to make it a variable
			    " -v " . $this->pub_dir_host . ":" . $GLOBALS['shared']."public_tmp/ " .
			    " -v " . $this->root_dir_host."/".$_SESSION['User']['id'].":" . $this->root_dir_virtual."/" .
			    " ".$tool['infrastructure']['container_image'] . " $cmd_vre";
	    } else {
		    $cmd = "docker run --privileged -v /var/run/docker.sock:/var/run/docker.sock " .
			    " ". $cmd_envs .
			    " -v " . $this->pub_dir_host .                            ":" . $GLOBALS['shared']."public_tmp/ " .
			    " -v " . $this->root_dir_host."/".$_SESSION['User']['id'].":" . $GLOBALS['shared']."userdata_tmp/". $_SESSION['User']['id'].
			    " ".$tool['infrastructure']['container_image'] . " $cmd_vre";
	    }

	    echo "CMD from setBashCmd_docker_SGE";
	    echo "<br></br>";
	    echo $cmd; 
	    echo "<br></br>";
	    return $cmd;
    }

	protected function setBashCmd_docker_SGE($tool){
        if (!isset($tool['infrastructure']['executable']) && !isset($tool['infrastructure']['container_image'])){
            $_SESSION['errorData']['Internal Error'][]="Tool '$this->toolId' not properly registered. Missing 'executable' or 'container_image' properties";
            return 0;
	}

	$this->container_image = $tool['infrastructure']['container_image'];

	# Set the intermediary mounting points required by the entrypoint.sh (BindFS) of the VRE image
	# https://github.com/inab/vre_template_tool_dockerized/blob/main/template/entrypoint.sh


	# Set ENV variables to be imported to the container
        $cmd_envs="";
        foreach ($tool['infrastructure']['container_env'][0] as $env_key=>$env_value){
                $cmd_envs .= "-e $env_key=$env_value ";
        }

	# Build the FULL command for running the interactive Docker containers
	if (isset($tool['infrastructure']['interactive'])) {
		$this->job_type = "interactive";

		# Set dynamic container name
                $random_string = bin2hex(random_bytes(8)); // Generate a random string
		$container_name = $tool['infrastructure']['container_image'] ."_". $random_string;


		#Constructing Docker executable
		#  Set VRE cmd to be executed inside the container
		#
		#
	
		if (isset($tool['infrastructure']['executable_env'])) {
			$exec_envs="";
			foreach ($tool['infrastructure']['executable_env'] as $key => $value) {
				 if ($key !== 'data_dir') {
					 $exec_envs .= " --$key $value";
				 }
			}
			$cmd_vre = $tool['infrastructure']['executable'] .
				" --data " .$GLOBALS['shared']."userdata_tmp/{$_SESSION['User']['id']}"."/".$this->project."/uploads/" .
				$exec_envs; 
		} else { 
			$cmd_vre = $tool['infrastructure']['executable'];
		}

		# Get the free port using the get_open_port function
		$free_port = shell_exec('python3 /var/www/html/openVRE/public/phplib/classes/get_free_port.py');
		#$tool['infrastructure']['free_port'] = $free_port;
		
		#echo "Free Port: " . $tool['infrastructure']['free_port'] . "<br>";
			
		$updateResult = $GLOBALS['toolsCol']->updateOne(
			['_id' => $tool],   // Find the tool by ID
			['$set' => ['infrastructure.free_port' => $free_port]]  // Save the free port
		);

		if ($updateResult->getModifiedCount() > 0) {       
			echo "Successfully saved free port to MongoDB: " . $free_port . "<br>";
		} else {
			echo "Failed to update MongoDB or no changes made.<br>";
		}

		$cmd=<<<EOF

# Export service to an available port (-p \$FREE_PORT:{$tool['infrastructure']['container_port']}). NOT REQUIRED when using proxy-gt
FREE_PORT=$free_port


#Docker permissions
current_user=\$(whoami)
current_groups=\$(groups)
docker_socket_permissions=\$(ls -l /var/run/docker.sock)

echo "Current user: \$current_user"
echo "Groups: \$current_groups"
echo "Docker socket permissions: \$docker_socket_permissions"

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


# Create or retrieve the network ID for the openVRE_net network. Required when using proxy-gt
#NET_NAME="openvre_net";
NET_NAME={$GLOBALS['vre_network_name']};
ROOT_DIR={$GLOBALS['localVolumes']};
NET_ID=\$(docker network inspect \$NET_NAME --format "{{.Id}}" 2>/dev/null || docker network create --driver bridge "\$NET_NAME");

# Run the Docker container with necessary options and configurations
CONTAINER_ID=\$(docker run \
    --rm \
    --privileged \
    -v /var/run/docker.sock:/var/run/docker.sock -d \
    --net=\$NET_NAME --name $container_name \
    $cmd_envs \
    -v {$this->pub_dir_volumes}:{$GLOBALS['shared']}public_tmp/ \
    -v {$this->root_dir_volumes}/{$_SESSION['User']['id']}:{$GLOBALS['shared']}userdata_tmp/{$_SESSION['User']['id']} \
    -p \$FREE_PORT:{$tool['infrastructure']['container_port']} {$tool['infrastructure']['container_image']} $cmd_vre); 

# Check if the container is running
if ! docker top \$CONTAINER_ID &>/dev/null; then
    printf '%s | %s\n' "$(date)" "Container crashed unexpectedly...";
    exit 1;
fi

if ! docker inspect --format='{{.State.Running}}' \$CONTAINER_ID | grep -q true; then
    printf '%s | %s\n' "$(date)" "Container not running anymore";
    exit 1;
fi

# Report container info to VRE
CONTAINER_NAME=\$(docker inspect --format {{.Name}} \$CONTAINER_ID | cut -d "/" -f 2);
CONTAINER_URL=\$(docker inspect --format "{{ .NetworkSettings.Networks.\$NET_NAME.IPAddress }}:{$tool['infrastructure']['container_port']}" \$CONTAINER_ID);
printf '%s | %s\n' "\$(date)" "ContainerID: \$CONTAINER_ID";
printf '%s | %s\n' "\$(date)" "ContainerName: \$CONTAINER_NAME";
printf '%s | %s\n' "\$(date)" "ContainerURL: \$CONTAINER_URL";
printf '%s | %s\n' "\$(date)" "ExposedPort: \$FREE_PORT";

docker logs -f \$CONTAINER_ID &> $this->log_file_virtual &

printf '%s | %s\n' "\$(date)" "Waiting for the service URL to become available in the internal network...";
#if timeout 420 wget --retry-connrefused -q --tries=300 --waitretry=10 --spider \$CONTAINER_URL; then
if timeout 420 wget --retry-connrefused --tries=300 --waitretry=10 -O /dev/null \$CONTAINER_URL; then
    printf '%s | %s\n' "\$(date)" "Service UP";
else
    printf '%s | %s\n' "\$(date)" "Service TIMEOUT (7 minutes)";
fi

# Wait forever
printf '%s | %s\n' "\$(date)" "Wait while container is running...";
exit_code="\$(docker wait \$CONTAINER_ID)";
printf '%s | Container has stopped (exit code = %s) \n' "\$(date)" "\$exit_code";

echo '# End time:' \$(date) >> $this->log_file_virtual;

exit 0;
EOF;


		echo "CMD from setBashCmd_docker_SGE";
		echo "<br></br>";
		echo $cmd;
		echo "<br></br>";
		echo $cmd_vre;

	}
	else{
		$cmd_vre = $tool['infrastructure']['executable'] .
                                " --config "         .$this->config_file_virtual .
                                " --in_metadata "    .$this->metadata_file_virtual .
                                " --out_metadata "   .$this->stageout_file_virtual ;
                                " --log_file "       .$this->log_file_virtual ;


                $cmd =  "docker run --privileged -v /var/run/docker.sock:/var/run/docker.sock -d " .
			" ". $cmd_envs .
                        " -v " . $this->pub_dir_volumes . ":" . $GLOBALS['shared']."public_tmp/ " .
                        " -v " . $this->root_dir_volumes . ":" .$GLOBALS['shared']."userdata_tmp/{$_SESSION['User']['id']}" .
                        " ".$tool['infrastructure']['container_image'] . " $cmd_vre" ;
        }
        return $cmd;
	}

    protected function setBashCmd_docker_EGA($tool) {
	    if (!isset($tool['infrastructure']['executable']) && !isset($tool['infrastructure']['container_image'])) {
		    $_SESSION['errorData']['Internal Error'][] = "Tool '$this->toolId' not properly registered. Missing 'executable' or 'container_image' properties";
		    return 0;
	    }
	    
	    $cmd = "";
	    $cmd_vre = $tool['infrastructure']['executable'] .
		    " --config "       .$this->config_file_virtual .
		    " --in_metadata "  .$this->metadata_file_virtual .
		    " --out_metadata " .$this->stageout_file_virtual ;
		    " --log_file "     .$this->log_file_virtual ;
	    $cmd_envs = "";
	    foreach ($tool['infrastructure']['container_env'][0] as $env_key => $env_value) {
		    $cmd_envs .= "-e $env_key=$env_value ";
	    }

		$userEmail = $_SESSION['User']['Email'];
		$vaultKey = $_SESSION['User']['Vault']['vaultKey'];
		$vaultAddress = $GLOBALS['vaultDockerUrl'] . $GLOBALS['vaultVersion'] . "/" . $GLOBALS['secretPath'] . $userEmail . $GLOBALS['vaultCredentialsSuffix'];
		$userFolder = "/shared_data/userdata/" . $_SESSION['User']['id'];
		$configFilePath = $userFolder . '/env.yml';
		$configContent = "VAULT_TOKEN={$vaultKey}\nVAULT_ADDRESS={$vaultAddress}\n";

		if (file_put_contents($configFilePath, $configContent) === false) {
			die("Failed to write configuration file: $configFilePath\n");
		}

	    $cmd = "docker run --device /dev/fuse --security-opt apparmor:unconfined --cap-add SYS_ADMIN -v /var/run/docker.sock:/var/run/docker.sock " .
		    " ". $cmd_envs .
		    " -v " . $this->pub_dir_host .                            ":" . $GLOBALS['shared']."public_tmp/ " .
		    " -v " . $this->root_dir_host."/".$_SESSION['User']['id'].":" . $GLOBALS['shared']."userdata_tmp/". $_SESSION['User']['id'].
		    " --tmpfs " . "/clean_files:rw,uid=1000,gid=1000" .
			" --env-file " . $configFilePath .
			" --network=new_vre_open-vre" .
			" -v " . $this->scripts_dir_host . ":/shared_scripts_tmp" .
		    " ".$tool['infrastructure']['container_image'] . " $cmd_vre";
		
	    return $cmd;
    }


    protected function setPMESrequest($tool) {
		$data = [];
		if (!isset($tool['infrastructure']['executable'])) {
			$_SESSION['errorData']['Internal Error'][] = "Tool '$this->toolId' not properly registered. Missing 'executable' property";
			return 0;
		}

		//Setting defaults from tool definition 
		if (!isset($tool['infrastructure']['wallTime'])) {
			$tool['infrastructure']['wallTime'] = "1440"; // 24h
		}

		if (!isset($tool['infrastructure']['interpreter'])) {
			$tool['infrastructure']['interpreter'] = "";  // only required if "Single". Examples:  "bash", "python3" 
		}

		$cloud = $tool['infrastructure']['clouds'][$this->cloudName];
		$cloud['minimumVMs'] ??= "1"; // if workflow_type = "Single" -> 1
		$cloud['maximumVMs'] ??= "1"; // if workflow_type = "Single" -> 1
		$cloud['limitVMs'] ??= "1"; // TODO OBSOLETE (=== maximumVMs)?
		$cloud['initialVMs'] ??= "1"; // if workflow_type = "Single" -> 1
		$cloud['disk'] ??= "1.0"; // TODO OBSOLETE?
		if (!isset($cloud['imageType'])) {
			//Assign imageType (size) from CPUS and RAM
			$flavor = $this->setImageType($tool['infrastructure']['cpus'], $tool['infrastructure']['memory']);
			$cloud['imageType'] = $flavor['id'];
			$tool['infrastructure']['memory'] = $flavor['memory'];
			$tool['infrastructure']['cpus'] = $flavor['cpus'];
			$this->imageType = $flavor;
		}

		//Setting PMES execution user (name,uid,gid, token)
		exec("stat  -c '%u:%g' ".$this->working_dir, $stat_out);
		[$user_uid,$user_gid] = explode(":", $stat_out[0]);
		$user_name = "vre".substr(md5(rand()), 0, 5);
		$token_id = "";
		if ($GLOBALS['clouds'][$this->cloudName]['auth']['required']) {
			switch ($this->cloudName) {
				// get openstack token. TODO: remove openstack and add ega (?)
				case 'mug-ebi':
					$token=0;
					// get token from session
					if (isset($_SESSION['User']['Token_mug_ebi']['id'])) {
						$token = $_SESSION['User']['Token_mug_ebi'];
						if (openstack_isTokenExpired($token)) {
							$token=0;
						}
					}
					// get and save new token
					if (!$token){
						$token  = openstack_getAccessToken();
						if (!isset($token['id'])){
							$_SESSION['errorData']['Error']= "Cannot submit job. Failed to get access token for $this->cloudName username.";
							return $data;
						}
						$_SESSION['User']['Token_mug_ebi'] = $token;
						modifyUser($_SESSION['User']['_id'], 'Token_mug_ebi',$token);
					}
					$token_id = $token['id'];
					break;
				
				// other clouds are opennebula. Auth via certs instead of tokens
				default:
					$_SESSION['errorData']['Error'] = "Cannot submit job. Requested cloud ($this->cloudName) requires authorization but no credentials found for VRE.";
					return $data;
			}
		}

		//Setting executable as PMES requires
		$app_target = dirname($tool['infrastructure']['executable']);
		$app_source = basename($tool['infrastructure']['executable']);

		//Building PMES json data
		$data = [
			[
			"jobName"          => $this->execution, 
			"compssWorkingDir" => $this->root_dir_virtual."/".$this->project."/".$this->execution,
			"wallTime"         => $tool['infrastructure']['wallTime'], 
			"memory"           => $tool['infrastructure']['memory'],
			"cores"            => $tool['infrastructure']['cpus'],
			"minimumVMs"       => $cloud['minimumVMs'], 
			"maximumVMs"       => $cloud['maximumVMs'],
			"limitVMs"         => $cloud['limitVMs'],
			"initialVMs"       => $cloud['initialVMs'],
			"disk"             => $cloud['disk'],
			"inputPaths"       => [],
			"outputPaths"      => [],
			"infrastructure"   =>  $this->cloudName,
			"mountPoints"      => [
						["target"      => $this->root_dir_virtual,
						"device"       => $GLOBALS['clouds'][$this->cloudName]['dataDir_fs']."/".$_SESSION['User']['id'],
						"permissions"  => "rw"
						], 
						["target"     => $this->pub_dir_virtual,
						"device"      => $GLOBALS['clouds'][$this->cloudName]['pubDir_fs'],
						"permissions" => "r"
						]
			], 
			"numNodes"   => "1",                                           //TODO OBSOLETE?
			"user"       => [
							"username"    => $user_name,                     // PMES creates /home/username/
							"credentials" => [
								"pem"         => "/home/pmes/pmes.pem", // in PMES server path
								"key"         => "/home/pmes/pmes.key", // in PMES server path
								"uid"         => $user_uid,                   // PMES writes outputs using this uid
								"gid"         => $user_gid,                   // PMES writes outputs using this gid
								"token"       => $token_id
							]
			],
			"img"        => [
					"imageName" => $cloud['imageName'], 
					"imageType" => $cloud['imageType']
			],
			"app"        => [
					"name"        => $tool['_id'],
					"target"      => $app_target,
					"source"      => $app_source,
					"interpreter" => $tool['infrastructure']['interpreter'],
					"args"  => [
						"config"      => $this->config_file_virtual,
						"in_metadata" => $this->metadata_file_virtual,
						"out_metadata"=> $this->stageout_file_virtual
					],
					"type" => $cloud['workflowType']    // COMPSs || Single
			],				
			"compss_flags" => ["flag" => " -g --summary -d "],
			"compssLogDir" => $this->root_dir_virtual."/".$this->project."/".$this->execution 
			]
		];

		return $data;
    }


    protected function setBashCmd_withoutApp($tool,$metadata) {
		if (!isset($tool['infrastructure']['executable'])) {
				$_SESSION['errorData']['Internal Error'][] = "Tool '$this->toolId' not properly registered. Missing 'executable' property";
				return 0;
		}

		$cmd = $tool['infrastructure']['executable'];
		foreach ($this->input_files as $input_name => $fileIds) {
			foreach ($fileIds as $fnId) {
				$filePath  = $metadata[$fnId]['path'];
				$filename = $GLOBALS['dataDir']."/$filePath";
				$cmd .= " --$input_name $filename";
			}
		}

		// Add to Cmd: --argument_name value
		foreach ($this->arguments as $key => $value){
			$cmd .= " --$key $value";
		}
		
		return $cmd;
    }


	protected function setBashCmd_Slurm($tool, $metadata, $launcherInfo){

	    // Ensure that the tool has a registered module to be loaded
	    if (!isset($tool['infrastructure']['module'])) {
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


    protected function createSubmitFile_SGE($cmd) {
		$workingDir = $this->working_dir;
		$bashFilename = $this->submission_file;
		$logFilename = $this->log_file;

		try {
			$fout = fopen($bashFilename, "w");
			if (!$fout) {
				throw new Exception('Failed to create tool configuration file: '.$bashFilename);
			}
		} catch (Exception $e) {
			$_SESSION['errorData']['Error'][] = "Failed to create queue submission file. ".$e->getMessage();
			return 0;
		}

		fwrite($fout, "#!/bin/bash\n");
		fwrite($fout, "# Generated by MuG VRE\n");
		fwrite($fout, "cd $workingDir\n");
		
		fwrite($fout, "\n# Running $this->toolId tool ...\n");
		fwrite($fout, "\necho '# Start time:' \$(date) > $logFilename\n");

		fwrite($fout, "\n$cmd >> $logFilename 2>&1\n");
		fwrite($fout, "\necho '# End time:' \$(date) >> $logFilename\n");
		fclose($fout);

		return $bashFilename;
    }


    protected function createSubmitFile_PMES($data) {
		$jsonFile   = $this->submission_file;
		try{
			$fout = fopen($jsonFile, "w");
			if (!$fout) {
				throw new Exception('Failed to create tool configuration file: '.$jsonFile);
			}
		} catch (Exception $e) {
			$_SESSION['errorData']['Error'][] = "Failed to create queue submission file. ".$e->getMessage();
			return 0;
		}

		fwrite($fout, json_encode($data,JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
		fclose($fout);
		
		return $jsonFile;
    }

    protected function createSubmitFile_EGA($cmd) {
		$workingDir = $this->working_dir;
		$bashFilename = $this->submission_file;
		$logFilename = $this->log_file;

		try {
			$fout = fopen($bashFilename, "w");
			if (!$fout) {
				throw new Exception('Failed to create tool configuration file: '.$bashFilename);
			}
		} catch (Exception $e) {
			$_SESSION['errorData']['Error'][] = "Failed to create queue submission file. ".$e->getMessage();
			return 0;
		}

		fwrite($fout, "#!/bin/bash\n");
		fwrite($fout, "# Generated by  VRE\n");
		
		fwrite($fout, "\n# Running $this->toolId tool ...\n");

		fwrite($fout, "cd $workingDir\n");
		fwrite($fout, "\necho '# Start time:' \$(date) > $logFilename\n");

		
		fwrite($fout, "\n$cmd >> $logFilename 2>&1\n");
		fwrite($fout, "\necho '# End time:' \$(date) >> $logFilename\n");

		fclose($fout);

		return $bashFilename;
    }

    /**
     * Submits 
     * @param string $inputs_request _REQUEST data from inputs.php form
    */
    public function submit($tool)  {
	    $jobManager = $this->getLauncher_Info($this->cloudName)['launcher']['job_manager'];
	    switch ($jobManager ?? $tool['infrastructure']['clouds'][$this->cloudName]['launcher']) {
			case "SGE":
			case "ega_demo":
			case "docker_SGE":
    		    return $this->enqueue($tool);
        	case "PMES":
    	    	return $this->callPMES();
    	    default:
    	    	$_SESSION['errorData']['Error'][] = "Tool '$this->toolId' not properly registered. Launcher for '$this->toolId' is set to: \"".$tool['infrastructure']['clouds'][$this->cloudName]['launcher']."\". Case not implemented.";
    		    return 0;
    	}
    }	    


    protected function enqueue($tool){
	
	logger("");
	$launcherInfo = $this->getLauncher_Info($this->cloudName); 
	var_dump($launcherInfo);
	if (!$launcherInfo || empty($launcherInfo)) {
        	$_SESSION['errorData']['Error'][] = "Launcher information is incomplete or missing.";	
		return 0;
	}
	$memory = $launcherInfo['memory'] ?? $tool['infrastructure']['memory'];
	$cpus = $launcherInfo['cpus'] ?? $tool['infrastructure']['cpus'];
	$queue = $launcherInfo['queue'] ?? $tool['infrastructure']['clouds'][$this->cloudName]['queue'];
	logger("Resolved Parameters: Queue=$queue, CPUs=$cpus, Memory=$memory");

    	list($pid,$errMesg) = execJob($this->working_dir, $this->submission_file, $queue, $cpus, $memory,  $this->stdout_file, $this->stderr_file);
        if (!$pid){
            log_addError($pid,$errMesg,NULL, $this->toolId,$this->cloudName,"SGE",$cpus,$memory);
            $_SESSION['errorData']['Error'][]="Internal error. Cannot enqueue job.";
            return 0;
        }
        logger("USER:".$_SESSION['User']['_id'].", ID:".$_SESSION['User']['id'].", LAUNCHER:SGE, TOOL:".$this->toolId.", PID:$pid");
        log_addSubmission($pid,$this->toolId,$this->cloudName,"SGE",$cpus,$memory,$this->working_dir);
    
    	$this->pid = $pid;
        return $pid;
    }


    protected function callPMES() {
    	$data_string = file_get_contents($this->submission_file);
        $data = json_decode($data_string, true);
    	[$pid, $errMesg] = execJobPMES($this->cloudName, $data);
    	if (!$pid) {
            log_addError($pid, $errMesg, NULL, $this->toolId, $this->cloudName, "PMES", $data['cores'], $data['memory']);
            $_SESSION['errorData']['Error'][] = "Internal error. Cannot enqueue job.";
            return 0;
        }

    	logger("USER:".$_SESSION['User']['_id'].", ID:".$_SESSION['User']['id'].", LAUNCHER:PMES, TOOL:".$this->toolId.", PID:$pid");
        log_addSubmission($pid, $this->toolId, $this->cloudName, "PMES", $data[0]['cores'], $data[0]['memory'], $this->working_dir);
        $this->pid = $pid;
        $this->start_time = strtotime("now");

        return $pid;
    }


    protected function getPathRelativeToRoot($path){
        if (preg_match('/^\//',$path)){
            return preg_replace('/^\//',"",str_replace($GLOBALS['dataDir']."/".$_SESSION['User']['id']."/","",$path));
        }else{
            return preg_replace('/^\//',"",str_replace($_SESSION['User']['id']."/","",$path)); 
        }
    }   

    /**
     * Convert internal VRE file format into DM MuG file  
     * @file  VRE file object, resulting from merging MuGVRE Mongo collections Files + FilesMetadata
    */
    protected function fromVREfile_toMUGfile($file) {
        $mugfile = [];
		$compressions = $GLOBALS['compressions'];
        $mugfile['_id'] = $file['_id'];

        if (isset($file['path'])) {
			if (preg_match('/^\//', $file['path']) || preg_match('/^'.$_SESSION['User']['id'].'/', $file['path'])) {
                $path = explode("/", $file['path']);
                $mugfile['file_path'] = implode("/", array_slice($path, -3, 3));
			} else {
                $mugfile['file_path'] = $file['path'];
			}
        } else {
            $mugfile['file_path'] = NULL;
		}

		$mugfile['file_type'] = $file['format'] ?? "UNK";
		$mugfile['data_type'] = $file['data_type'] ?? NULL;
		$mugfile['data_source'] = $file['data_source'] ?? NULL;

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
    protected function array_to_object($array) {
	$obj = new stdClass;
	foreach($array as $k => $v) {
	    if(strlen($k)) {
        	if(is_array($v)) {
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
    protected function set_cloudName($tool=array()){
	$available_clouds = array_keys($GLOBALS['clouds']);
	if (!count($available_clouds)){
		$_SESSION['errorData']['Error'][] = "Internal Error: No cloud infrastructure available in the current VRE installation.";
		return 0;
	}
	
	if (isset($tool['infrastructure']['clouds'])){
		// 1, set cloudName from default cloud, as tool specifies
		foreach ($tool['infrastructure']['clouds'] as $name=>$toolInfo){
			if ($toolInfo['default_cloud'] === true){
				if (in_array($name,$available_clouds)){
					$this->cloudName = $name;
					break;
				}
			}
        }

        // 2, set cloudName from current cloud, if it is in tool specification
        if (!$this->cloudName && isset($GLOBALS['cloud']) ){
            foreach ($tool['infrastructure']['clouds'] as $name=>$toolInfo){
                if($name == $GLOBALS['cloud']){
                    if (in_array($name,$available_clouds)){
                           $this->cloudName = $name;
                           break;
                    }
                }
            }
        }
        // 3, set cloudName from clouds list in tool specification, the first found available
		if (! $this->cloudName){
			foreach ($tool['infrastructure']['clouds'] as $name=>$cloudInfo){
				if (in_array($name,$available_clouds)){
					$this->cloudName = $name;
					$_SESSION['errorData']['Warning'][] = "Tool has no the default cloud infrastructure set or available. Taking instead '$this->cloudName', but the tool execution may fail.";
					break;
				}
			}
		}
	}
	if (! $this->cloudName){
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
    public function createMetadata_from_Input_files_public($input_files_public,$tool){

        $metadata_public = array();

	    foreach ($input_files_public as $input_name => $input_value){
    	    if (count($tool)){
    		// checking coherence between JSON and REQUEST
    		if (!isset($tool['input_files_public_dir'][$input_name])){
    			$_SESSION['errorData']['Internal'][]="Input file public '$input_name' not found in tool definition. '$this->toolId' is not properly registered";
			return $metadata_public;
    		}
            if ($input_value!=""){
                $rfn_public = 1;

                // check input_files_public_dir
                switch ($tool['input_files_public_dir'][$input_name]['type']){
                    case 'enum':
    			        if (!isset($tool['input_files_public_dir'][$input_name]['enum_items']) || (!isset($tool['input_files_public_dir'][$input_name]['enum_items']['name']))){
                            $_SESSION['errorData']['Internal'][]="Invalid input_files_public_dir enum in tool definition. '$input_name' has no 'enum_items' or 'enum_items['name].";
                            $rfn_public = 0;
                        }
			            if (!in_array($input_value,$tool['input_files_public_dir'][$input_name]['enum_items']['name']) ){
	    		            $_SESSION['errorData']['Error'][]="Invalid input_files_public_dir. In '$input_name' these values are accepted [".implode(", ",$tool['input_files_public_dir'][$input_name]['enum_items']['name'])."], but found $input_value";
                            $rfn_public = 0;
                        }
            			$input_value = strval($input_value);
                        break;
                    case 'hidden':
                    case 'string':
			            if (is_array($input_value)){
            			    $_SESSION['errorData']['Error'][]="Invalid file public. In '$input_name' a string was expected, but found an array: ".implode(",",$input_value);
                            $rfn_public = 0;
            			}
            			$input_value = strval($input_value);
            			break;
                    default:
                        $_SESSION['errorData']['Internal'][]="Input file public '$input_name' has unsupported type (".$tool['input_files_public_dir'][$arg_name]['type']."). '$this->toolId' is not properly registered";
                        $rfn_public = 0;
                }
                if ($rfn_public == 0 ){
                    continue;
                }

                // find file in public dir
                $rfn_public = $this->pub_dir."/$input_value";
                if (!is_file($rfn_public) && (!is_dir($rfn_public)) && (!preg_match('/\$\(.+\)/',$rfn_public)) ){
                    $_SESSION['errorData']['Error'][]="Input file public '$input_name' not found in public directory: $rfn_public";
                    continue;
                }
                // get fn and  metadata from DMP #TODO : right now this data is not registered!!
                
                // create fake metadata
            	$fn  = createLabel()."_dummy";
                $file = array(
                        '_id'       => $fn,
                        'file_path' => $input_value,
                        'meta_data' => array(),
                        'sources'   => array(0)
                    );
                if (preg_match('/refGenomes\/(.[^\/]+)\//',$input_value,$m)){
                    $refGenome = $m[1];
                    $file['meta_data']['assembly'] = $refGenome;
                    $file['taxon_id'] =(isset($this->refGenome_to_taxon[$refGenome])?$this->refGenome_to_taxon[$refGenome]:0);
                }

                if (isset($tool['input_files_public_dir'][$input_name]['data_type']) && is_array($tool['input_files_public_dir'][$input_name]['data_type'])){
                    $file['data_type']= $tool['input_files_public_dir'][$input_name]['data_type'][0];
                }
                if (isset($tool['input_files_public_dir'][$input_name]['file_type']) && is_array($tool['input_files_public_dir'][$input_name]['file_type'])){
                    $file['file_type']= $tool['input_files_public_dir'][$input_name]['file_type'][0];
                }
                $file['user_id']= "public";
                if (is_file($rfn_public)) { $file['type'] = "file";}
                if ( is_dir($rfn_public)) { $file['type'] = "dir";}
                $metadata_public[$fn]= $file;

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
    protected function setImageType($cpus_requested, $mem_requested) {
    	$cpus = 0;
		$mem = 0;
		// if not flavors list defined, complain and try default flavor
		if (count($GLOBALS['clouds'][$this->cloudName]['imageTypes']) === 0) {
			$cpus = 4;
			$mem = 8;
			$flavor_name = "large";
			$_SESSION['errorData']['Internal'][] = "Cannot set job virtual machine size for cloud '".$this->cloudName."'. Trying with '$flavor_name' ($cpus cores and $mem GB RAM). If job fails, report us please";
			$flavor = ["id"=> $flavor_name, "name" => $flavor_name, "disk" => null];
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
			$_SESSION['errorData']['Warning'][] = "Cannot set job virtual machine with $cpus_requested cores and $mem_requested GB RAM for cloud '".$this->cloudName."'. Assigning maximum RAM = $mem_flavor GB";
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
			$_SESSION['errorData']['Warning'][] = "Cannot set job virtual machine with $cpus_requested cores and $mem_requested GB RAM for cloud '".$this->cloudName."'. Assigning maximum cores = $cpus_flavor";
			$cpus = $cpus_flavor;
		}

		$flavor = $GLOBALS['clouds'][$this->cloudName]['imageTypes'][$mem][$cpus];
		$flavor['cpus'] = $cpus;
		$flavor['memory'] = $mem;

		return $flavor;
    }


    /**
     * Parse submission File
    */
    public function parseSubmissionFile(){
	return 1;
	
    }

    public function getSSHCred($vaultUrl, $vaultToken, $accessToken, $vaultRolename, $username, $remote_dir, $siteId) {
	    #retrieve the credential and update the site collection with it

        $vaultClient = new VaultClient($vaultUrl, $vaultToken, $accessToken, $vaultRolename, $username);
        $vaultKey = $_SESSION['User']['Vault']['vaultKey'];
        $credentials = $vaultClient->retrieveDatafromVault('SSH', $vaultKey, $vaultUrl, 'secret/mysecret/data/', $_SESSION['User']['_id'] . '_credentials.txt');
	//$_SESSION['errorData']['Error'][] = "SSH credentials from Vault: " . print_r($credentials);
	//        error_log($vaultKey, $credentials);
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
		//$_SESSION['errorData']['Error'][] = "SSH: " . print_r($sshCredentials);
                // Assuming the site document exists, update the launcher section with SSH credentials
		if ($siteDocument) {

			//$_SESSION['errorData']['Debug'][] = "Site document before update: " . print_r($siteDocument, true);

                        $siteDocument['launcher']['access_credentials']['username'] = $sshUsername;
                        $siteDocument['launcher']['access_credentials']['private_key'] = $sshPrivateKey;
                        $siteDocument['launcher']['access_credentials']['public_key'] = $sshPublicKey;
			
			//$_SESSION['errorData']['Debug'][] = "SSH Username: " . $sshUsername;
			//$_SESSION['errorData']['Debug'][] = "SSH Private Key (first 20 chars): " . substr($sshPrivateKey, 0, 20);
			//$_SESSION['errorData']['Debug'][] = "SSH Public Key (first 20 chars): " . substr($sshPublicKey, 0, 20);

                        // Save the updated site document back to the collection
			$updateResult = $GLOBALS['sitesCol']->updateOne(['_id' => $siteId], ['$set' => $siteDocument]);
			//$_SESSION['errorData']['Info'][] = "Update result: " . print_r($updateResult->getModifiedCount(), true);

			$updatedSiteDocument = $GLOBALS['sitesCol']->findOne(['_id' => $siteId]);
			//$_SESSION['errorData']['Debug'][] = "Site document after update: " . print_r($updatedSiteDocument, true);


			return true;

                        // aNOTHER FUNCTION Initialize the SSH client with retrieved credentials and site details
                        //$remoteSSH = new RemoteSSH($sshCredentials, $remote_dir, 22, $siteDocument['launcher']['http_server']);
			//$SshCred = $remoteSSH->getCredentials();
			
			
                } else {
                        return array('error' => 'Site document not found for site ID: ' . $siteId);
                }
        } else {
                return array('error' => 'Failed to retrieve SSH credentials from Vault, not present.');
        }
    }


    protected function setHPCRequest($cloudName, $tool, $username){
            if ($cloudName == 'marenostrum') {
                    $vaultUrl = $GLOBALS['vaultUrl'];
                    $vaultToken = $_SESSION['User']['Vault']['vaultToken'];
                    $accessToken = $_SESSION['User']['Token']['access_token'];
                    $vaultRolename = $_SESSION['User']['Vault']['vaultRolename'];

                    //Get the credentials
                    $remoteSSH = $this->getSSHCred($vaultUrl, $vaultToken, $accessToken, $vaultRolename, $username, null, $cloudName);
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


    function getLauncher_Info($siteId) {

        // Retrieve tool document from the tools collection
//      $filterfields=array();
	    $siteDocument = $GLOBALS['sitesCol']->findOne(['_id' => $siteId]);
	//$_SESSION['errorData']['Error'][] = "Site: " . print_r($siteDocument, true);
        if (!$siteDocument) {
		return null;
	}

	$launcherInfo = [
		'site_id' => $siteDocument['_id'],
		'name' => $siteDocument['name'],
		'launcher' => $siteDocument['launcher']
	];
	$_SESSION['errorData']['Info'][] = "Launcher Info: " . print_r($launcherInfo, true);
	return $launcherInfo;

    }

}

?>
