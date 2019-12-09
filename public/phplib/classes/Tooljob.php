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
    public $root_dir_mug;      // MuG  dataDir parent of user dataDir. Mounted to VMs in PMES. Already there im SGE. Path as seen by VMs 
    public $pub_dir_virtual;   // Public dir mounted to VMs. Path as seen by VMs  
    public $cloudName;         // Cloud name where tool should be executed. Available clouds set in GLOBALS['clouds']
    public $description;
    public $working_dir;
    public $output_dir;
    public $launcher;
    public $imageType;

    // Paths to files genereted during ToolJob execution
    public $config_file;
    public $config_file_virtual;
    public $stageout_file;
    public $stageout_file_virtual;
    public $submission_file;
    public $metadata_file;
    public $metadata_file_virtual;
    public $log_file;
    public $log_file_virtual;
    public $logName;

    public $stageout_data   = Array();
    public $input_files     = Array();
    public $input_files_pub = Array();
    public $input_paths_pub = Array();
    public $arguments       = Array();
    public $metadata        = Array();
    public $pid             = 0;
    public $start_time      = 0;
    public $hasExecutionFolder= true;
    public $refGenome_to_taxon = Array( "hg38"=>"9606" ,  "hg19"=>"9606", "R64-1-1"=>"4932", "r5_01"=>"7227");


    /**
     * Creates new toolExecutor instance
     * @param string $toolId Tool Id as appears in Mongo
    */
    public function __construct($tool,$execution="",$project="",$descrip="",$output_dir=""){
    

    	// Setting Tooljob
    	$this->toolId    = $tool['_id'];
    	$this->title     = $tool['name'] ." job";
        $this->execution = $execution;
        $this->project   = $project;

        // Set paths in VRE
        $this->root_dir  = $GLOBALS['dataDir']."/".$_SESSION['User']['id'];
    	$this->pub_dir   = $GLOBALS['pubDir'];

        // Set paths in the virtual machine
        $this->set_cloudName($tool);
        $this->launcher         = $tool['infrastructure']['clouds'][$this->cloudName]['launcher'];
        switch ($this->launcher){
            case "SGE":
                $this->root_dir_virtual = $GLOBALS['clouds'][$this->cloudName]['dataDir_virtual']. "/".$_SESSION['User']['id'];
                $this->root_dir_mug      = $GLOBALS['clouds'][$this->cloudName]['dataDir_virtual'];
                $this->pub_dir_virtual  = $GLOBALS['clouds'][$this->cloudName]['pubDir_virtual'];
                break;
            case "PMES":
                $this->root_dir_virtual = $GLOBALS['clouds'][$this->cloudName]['dataDir_virtual'];
                $this->pub_dir_virtual  = $GLOBALS['clouds'][$this->cloudName]['pubDir_virtual'];
                break;
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

    public function setLog($filename=""){

        //set logName
        if (strlen($filename)){
    		$filename = basename($filename);
    		$f = pathinfo($filename);
    		if ($f['extension'] != "log"){
    			$filename = $filename. ".log";
    		}
    		$this->logName = $filename;
    	}else{
    		$this->logName = $GLOBALS['tool_log_file'];
        }

        //set again working dir
        if ($this->hasExecutionFolder){
            $this->__setWorking_dir($this->execution);
        }else{
            $this->__setWorking_inTmp($this->toolId);
        }
    }

   /**
     * Set working directory where log_file, submission_file and control_file will be located
     * @param string $execution Execution name used to set the working directory name
     * @param boolean $overwrite If false, an alternative name $execution[_NN] for the working directory is set
    */

    public function __setWorking_dir($execution, $overwrite=0){

	    $dataDirPath = getAttr_fromGSFileId($_SESSION['User']['dataDir'],"path");
    	$wdFN   = $dataDirPath."/$execution";
    	$wd     = $GLOBALS['dataDir']."/$wdFN";
	
    	if (!$overwrite){
    		$prevs = $GLOBALS['filesCol']->findOne(array('path' => $wdFN, 'owner' => $_SESSION['User']['id']));
    		if ($prevs){
    		    for ($n=1;$n<99;$n++){
               		$executionN=  $execution. "_$n";
       			$wdFN      = "$dataDirPath/$executionN";
       			$prevs     =  $GLOBALS['filesCol']->findOne(array('path' => $wdFN, 'owner' => $_SESSION['User']['id']));
       			if ($prevs){
       			    $execution= $executionN;
    	        	    $wd     = $GLOBALS['dataDir']."/$wdFN";
       			    break;
       		       }
	            }
		}
	}
        $this->execution           = $execution;
        $this->working_dir         = $this->root_dir."/".$this->project."/".$this->execution;

        if (!$this->logName){$this->logName = $GLOBALS['tool_log_file'];}

    	$this->config_file    = $this->working_dir."/".$GLOBALS['tool_config_file'];
    	$this->stageout_file  = $this->working_dir."/".$GLOBALS['tool_stageout_file'];
        $this->submission_file= $this->working_dir."/".$GLOBALS['tool_submission_file'];
        $this->log_file       = $this->working_dir."/".$this->logName;
        $this->metadata_file  = $this->working_dir."/".$GLOBALS['tool_metadata_file'];

        $this->config_file_virtual    = $this->root_dir_virtual."/".$this->project."/".$this->execution."/".$GLOBALS['tool_config_file'];
        $this->stageout_file_virtual  = $this->root_dir_virtual."/".$this->project."/".$this->execution."/".$GLOBALS['tool_stageout_file'];
        $this->metadata_file_virtual  = $this->root_dir_virtual."/".$this->project."/".$this->execution."/".$GLOBALS['tool_metadata_file'];
        $this->log_file_virtual       = $this->root_dir_virtual."/".$this->project."/".$this->execution."/".$this->logName;
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
    public function createWorking_dir(){

        if (!$this->working_dir ){
    		$_SESSION['errorData']['Internal Error'][]="Cannot create working_dir. Not set yet";
    		return 0;
    	}
    	$dirfn = str_replace($GLOBALS['dataDir']."/","",$this->working_dir);

    
        $hasExecutionFolder = $this->hasExecutionFolder;
    	// create working dir - disk and db
    	if (!is_dir($this->working_dir)){
    	
            if ($hasExecutionFolder){
            	$dirId = createGSDirBNS($dirfn);
	        	if ($dirId=="0"){
	               	$_SESSION['errorData']['Error'][]="Cannot create execution folder: '$this->working_dir'";
		    		return 0;
        		}
		    	$this->_id = $dirId;
	    	}else{
    		    $this->_id = 1;
	    	}


        	mkdir($this->working_dir,0777);
        	chmod($this->working_dir, 0777);

    	// if exists, recover working dir id
    	}else{
            if ($hasExecutionFolder){
    			$dirId = getGSFileId_fromPath($dirfn);
    			$_SESSION['errorData']['Error'][]="Cannot set job. Requested execution folder (".basename($dirfn).") already exists. Please, set another execution name.<br>";
		        return 0;
    			//if ($dirId=="0")
    			//	$_SESSION['errorData']['Error'][]="Cannot create execution folder: already in disk but not in mongo. Try using a new execution name other than ".basename($this->working_dir);
    	
    			//$this->_id = $dirId;
	    	}else{
    			$this->_id = 1;
    		}
        }

    	// set dir metadata
    	if ($this->_id != 1){
    		if (!is_dir($this->working_dir)){
    	        	$_SESSION['errorData']['Error'][]="Cannot write and set new execution directory: '$this->working_dir' with id '$this->_id'";
    			return 0;
    		}
    	
    	        $input_ids = array();
    	        array_walk_recursive($this->input_files, function($v, $k) use (&$input_ids){ $input_ids[] = $v; });
    	        $input_ids = array_unique($input_ids);
    	
    		$projDirMeta=array(
    			'description'     => $this->description,
    		        'input_files'     => $input_ids,
                	'tool'            => $this->toolId,
    			'submission_file' => $this->submission_file,
    			'log_file'         => $this->log_file,
	            	'arguments'       => array_merge($this->arguments,$this->input_paths_pub)
            );

    		$r = addMetadataBNS($this->_id, $projDirMeta);
    		if ($r == "0"){
	            $_SESSION['errorData']['Error'][]="Project folder created. But cannot set metada for '$this->working_dir' with id '$this->_id'";
                return 0;
            }
        }
    	return $this->_id;
    }



    /**
     * Creates tool configuration JSON
     * @param array $tool Fill in config file: input_files, arguments and output_files
    */
    public function setConfiguration_file($tool){
	
	$config_rfn = $this->config_file;

	if (!$this->working_dir){
		$_SESSION['errorData']['Internal Error'][]="Cannot create tool configuration file. No 'working_directory' set";
		return 0;
	}

	// Set json base
	$data = Array(
		'input_files'=>Array(),
		'arguments'=>Array(
			Array("name"=>"execution",   "value"=> $this->root_dir_virtual."/".$this->project."/".$this->execution),
			#Array("name"=>"project",    "value"=> $this->project),
			Array("name"=>"project",     "value"=> $this->root_dir_virtual."/".$this->project."/".$this->execution),
			Array("name"=>"description", "value"=> $this->description),
		),
		'output_files'=>Array()
	);
	// append input_files
	//	array_push($data['input_files'], Array("name"=>$input_file->input_name, "value"=> $this->getPathRelativeToRoot($input_file->path)));
	foreach ($this->input_files as $k=>$vs){
	    foreach ($vs as $v){
            array_push($data['input_files'], Array(
                                                "name"          => $k,
                                                "value"         => $v,
                                                "required"      => $tool['input_files'][$k]['required'],
                                                "allow_multiple"=> $tool['input_files'][$k]['allow_multiple']
                                            )
                       );
	   }
	}
	foreach ($this->input_files_pub as $k=>$vs){
	    foreach ($vs as $v){
            array_push($data['input_files'], Array(
                                                "name"          => $k,
                                                "value"         => $v,
                                                "required"      => $tool['input_files_public_dir'][$k]['required'],
                                                "allow_multiple"=> $tool['input_files_public_dir'][$k]['allow_multiple']
                                            )
                       );
	   }
	}
	// append arguments
	foreach ($this->arguments as $k=>$v){
		array_push($data['arguments'], Array("name"=>$k, "value"=> $v));
    }

    // append output_files from tool json
    if ($tool['output_files']){
        foreach ($tool['output_files'] as $k => $v){
    	    if (isset($v['file']['file_path'])){
        		$v['file']['file_path'] = $this->root_dir_virtual."/".$this->project."/".$this->execution ."/".$v['file']['file_path'];
    	    }
	        $data['output_files'][] = $v;
        }
    }

	// write JSON
	try{
	    $F = fopen($config_rfn,"w");
	    if (!$F) {
		throw new Exception('Failed to create tool configuration file'.$config_rfn);
	    }
    	}
	catch (Exception $e){
		$_SESSION['errorData']['Internal Error'][]= $e->getMessage();
		return 0;
	}

	fwrite($F, json_encode($data,JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
	fclose($F);

	return $config_rfn;
    }


    /**
     * Set Arguments
     * @param array $arguments Arguments as received from inputs.php
    */
    public function setArguments($arguments,$tool=Array()){

	foreach ($arguments as $arg_name => $arg_value){
	    //checking  requirements
	    if (count($tool)){
		// checking coherence between JSON and REQUEST
		if (!isset($tool['arguments'][$arg_name])){
			$_SESSION['errorData']['Internal'][]="Argument '$arg_name' not found in tool definition. '$this->toolId' is not properly registered";
			return 0;
		}
		// checking arguments requirements (TODO create 'validateArguments')
		if ($arg_value==""){
		    if ($tool['arguments'][$arg_name]['required']){
			$_SESSION['errorData']['Error'][]="No value given for argument '$arg_name'";
		    	return 0;
		    }else{
			continue;
		    }
		}
		switch ($tool['arguments'][$arg_name]['type']){
		    case "enum":
			if (!isset($tool['arguments'][$arg_name]['enum_items']) || (!isset($tool['arguments'][$arg_name]['enum_items']['name']))){
			    $_SESSION['errorData']['Internal'][]="Invalid argument enum in tool definition. '$arg_name' has no 'enum_items' or 'enum_items['name].";
		 	    return 0;
			}
			if (!in_array($arg_value,$tool['arguments'][$arg_name]['enum_items']['name']) ){
	    		    $_SESSION['errorData']['Error'][]="Invalid argument. In '$arg_name' these values are accepted [".implode(", ",$tool['arguments'][$arg_name]['enum_items']['name'])."], but found $arg_value";
			    return 0;
			}
			break;
		    case "enum_multiple":
			if (!isset($tool['arguments'][$arg_name]['enum_items']) || (!isset($tool['arguments'][$arg_name]['enum_items']['name']))){
			    $_SESSION['errorData']['Internal'][]="Invalid argument enum in tool definition. '$arg_name' has no 'enum_items' or 'enum_items['name].";
		 	    return 0;
			}
			if (!is_array($arg_value))
				$arg_value=array($arg_value);
			foreach ($arg_value as $v){
				if (!in_array($v,$tool['arguments'][$arg_name]['enum_items']['name']) ){
			    		$_SESSION['errorData']['Error'][]="Invalid argument. In '$arg_name' these values are accepted [".implode(", ",$tool['arguments'][$arg_name]['enum_items']['name'])."], but found ".implode(", ",$arg_value);
					return 0;
				}
			}
			break;
		    case "boolean":
			if ($arg_value===true || $arg_value=="on" || $arg_value == "1" || $arg_value == 1)
				$arg_value=true;	
			elseif ($arg_value===false || $arg_value=="off"|| $arg_value == "0" || $arg_value == 0 )
				$arg_value=false;	
			else{
			    $_SESSION['errorData']['Error'][]="Invalid argument. In '$arg_name' a boolean was expected, but found: $arg_value";
		 	    return 0;
			}
			break;
		    case "integer":
			if (!is_numeric($arg_value)){
			    $_SESSION['errorData']['Error'][]="Invalid argument. In '$arg_name' an integer was expected, but found: $arg_value";
		 	    return 0;
			}
			$arg_value = intval($arg_value);
			break;
		    case "number":
			if (!is_numeric($arg_value)){
			    $_SESSION['errorData']['Error'][]="Invalid argument. In '$arg_name' a number was expected, but found: $arg_value";
		 	    return 0;
			}
			break;
		    case "hidden":
		    case "string":
			if (is_array($arg_value)){
			    $_SESSION['errorData']['Error'][]="Invalid argument. In '$arg_name' a string was expected, but found an array: ".implode(",",$arg_value);
		 	    return 0;
			}
			$arg_value = strval($arg_value);
			break;
		    case "enum":
		    default:
			$_SESSION['errorData']['Internal'][]="Invalid argument type in tool definition. '$arg_name' is of type ".$tool['arguments'][$arg_name]['type'];
		 	return 0;
		}
	    }
	    // setting arguments 
	    $this->arguments[$arg_name]=$arg_value;
		
	}
        return 1;
    }



    /**
     * Set inputFiles
     * @param array $input_files  Input_files as received from inputs.php
     * @param array $tool Tool array containing input_files type and requirements
     * @param array $metadata Files metadata extracted from DB
    */
    public function setInput_files($input_files,$tool=array(),$metadata=array()){

	foreach ($input_files as $input_name => $fns){

	    //checking  requirements
	    if (count($tool) && count($metadata)){
		    if (!is_array($fns))
			$fns=array($fns);

            foreach ($fns as $fn){
			// checking coherence between JSON and REQUEST
			if (!isset($tool['input_files'][$input_name])){
				$_SESSION['errorData']['Internal'][]="Input file '$input_name' not found in tool definition. '$this->toolId' is not properly registered";
				return 0;
            } 
            // checking required value not empty
			if (!$fn){
			    if ($tool['input_files'][$input_name]['required'] === true ){
				    $_SESSION['errorData']['Error'][]="No file given for '$input_name'";
    				return 0;
                }else{
                    if (($k = array_search($fn, $fns)) !== false) { unset($fns[$k]);}
                    continue;
                }
			}
			// checking input_file has metadata
			if (!isset($metadata[$fn])){
			    if ($tool['input_files'][$input_name]['required'] === true ){
				    $_SESSION['errorData']['Error'][]="Given file in '$input_name' has no metadata";
    				return 0;
                }
			}
    	    // checking input_file integrity
/*			$ok = $this->validateInput_file($tool['input_files'][$input_name], $metadata[$fn]);
			if (! $ok){
				$_SESSION['errorData']['Error'][]="Input file '$input_name' not valid. Stopping '$this->toolId' execution";
				return 0;
            }*/
		    }   
	    }
        // setting input_files
        if (count($fns)){
    	    $this->input_files[$input_name]=$fns;
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
    public function setStageout_data($out_files,$tool=array(),$metadata=array()){
	if (!isset ($out_files['output_files'])){
		$_SESSION['errorData']['Error'][]="Internal tool may have problems registering outfiles: Stageout_data mal formatted";
		return 0;
	}

	foreach ($out_files['output_files'] as $out_name => $info){
		//Validate out_files against tool document
		//TODO
		
		//Add output file metadata
		$this->stageout_data['output_files'][$out_name]=$info;
	}
	$this->stageout_file="";
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
    public function setMetadata_file($metadata,$metadata_pub=array()){
	if (!$this->working_dir){
		$_SESSION['errorData']['Internal Error'][]="Cannot create metadata file. No 'working_dir' set";
		return 0;
	}

    $fileMuGs=Array();

    // add input_files metadata
    foreach ($metadata as $fnId => $file){
        // convert metadata to DMP format
        $fileMuG = $this->fromVREfile_toMUGfile($file);

        // adapt metadata to App requirements
        if (isset($fileMuG['sources'])){
            $source_list=array();
            foreach($fileMuG['sources'] as $sourceid){
                if ($sourceid){
                    $source_path = getAttr_fromGSFileId($sourceid,"path");
                    if($source_path){array_push($source_list,$this->root_dir_virtual."/".$source_path);}
                }
            }
            $fileMuG['sources'] = $source_list;
        }
        if ($fileMuG['file_path']){
            $fileMuG['file_path'] = $this->root_dir_virtual."/".$fileMuG['file_path'];
        }
        if ($fileMuG['meta_data']['parentDir']){
            $parent_path = getAttr_fromGSFileId($fileMuG['meta_data']['parentDir'],"path");
            if($parent_path){$fileMuG['meta_data']['parentDir'] = $this->root_dir_virtual."/".$parent_path;}
        }
		array_push($fileMuGs,$fileMuG);
    }

    // add input_files public metadata
    if (count($metadata_pub)){
    foreach ($metadata_pub as $fnId => $fileMuG){
        // convert metadata to DMP format
        //$fileMuG = $this->fromVREfile_toMUGfile($file);

        // adapt metadata to App requirements
        if (isset($fileMuG['sources'])){
            $source_list=array();
            foreach($fileMuG['sources'] as $sourceid){
                if ($sourceid){
                    $source_path = getAttr_fromGSFileId($sourceid,"path");
                    if($source_path){array_push($source_list,$this->public_dir_virtual."/".$source_path);}
                }
            }
            $fileMuG['sources'] = $source_list;
        }
        if ($fileMuG['file_path']){
            $fileMuG['file_path'] = $this->pub_dir_virtual."/".$fileMuG['file_path'];
        }
        if ($fileMuG['meta_data']['parentDir']){
            $parent_path = getAttr_fromGSFileId($fileMuG['meta_data']['parentDir'],"path");
            if($parent_path){$fileMuG['meta_data']['parentDir'] = $this->root_dir_virtual."/".$parent_path;}
        }
        array_push($fileMuGs,$fileMuG);
    }
    }
	$metadata_rfn = $this->metadata_file;

	// write JSON
	try{
	    $F = fopen($metadata_rfn,"w");
	    if (!$F) {
		throw new Exception('Failed to create metadata file for tool execution'.$metadata_rfn);
	    }
    	}
	catch (Exception $e){
		$_SESSION['errorData']['Internal Error'][]= $e->getMessage();
		return 0;
	}

	fwrite($F, json_encode($fileMuGs,JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
	fclose($F);

	return $metadata_rfn;
    }



    /**
     * Creates execution Command Line and Submission File
    */
    public function prepareExecution($tool,$metadata,$metadata_pub = array()){

	$launcher = $tool['infrastructure']['clouds'][$this->cloudName]['launcher'];

    //external tool
    if ($tool['external'] !== false){

		$r = $this->setConfiguration_file($tool);
		if ($r=="0")
		    return 0;
	
		$this->setMetadata_file($metadata,$metadata_pub);
		if ($r=="0")
		    return 0;
	
		if (!is_file($this->config_file) && !is_file($this->metadata_file) ){
			$_SESSION['errorData']['Internal Error'][]="Cannot set tool command line. It required configuration file ($this->config_file) and metadata file ($this->metadata_file)";
	            	return 0;
		}

		switch ($launcher){
		    case "SGE":
			$cmd  = $this->setBashCmd_SGE($tool);
			if (!$cmd)
				return 0;
	
			$submission_rfn = $this->createSubmitFile_SGE($cmd); 
			if (!is_file($submission_rfn))
				return 0;
			break;
	
		    case "PMES":
			$json_data = $this->setPMESrequest($tool);
			if (!$json_data)
				return 0;

			$submission_rfn = $this->createSubmitFile_PMES($json_data);
			if (!is_file($submission_rfn))
				return 0;
			break;
	
		    default:
			$_SESSION['errorData']['Error'][]="Tool '$this->toolId' not properly registered. Launcher for '$this->toolId' is set to \"$launcher\". Case not implemented.";
			return 0;
		}

		return 1;	

	//internal tool
	}elseif ($tool['external'] === false){

		switch ($launcher){
	
		    case "SGE":
			$cmd = $this->setBashCmd_withoutApp($tool,$metadata);
			if (!$cmd)
				return 0;

			$submission_rfn = $this->createSubmitFile_SGE($cmd); 
			if (!is_file($submission_rfn))
				return 0;
			break;
		
		    case "PMES":
			//TODO
	
		    default:
			$_SESSION['errorData']['Error'][]="Internal Tool '$this->toolId' not properly registered. Launcher for '$this->toolId' is set to \"$launcher\". Case not implemented.";
			return 0;
		}
		return 1;
	}
    }

    protected function setBashCmd_SGE($tool){
	if (!isset($tool['infrastructure']['executable'])){
            $_SESSION['errorData']['Internal Error'][]="Tool '$this->toolId' not properly registered. Missing 'executable' property";
            return 0;
	}
	$cmd = $tool['infrastructure']['executable'] .
				" --config "         .$this->config_file_virtual .
				" --in_metadata "    .$this->metadata_file_virtual .
				" --out_metadata "   .$this->stageout_file_virtual .
			    	" --log_file "       .$this->log_file_virtual ;
	return $cmd;
    }

    protected function setPMESrequest($tool){

    $data = array();

	if (!isset($tool['infrastructure']['executable'])){
            $_SESSION['errorData']['Internal Error'][]="Tool '$this->toolId' not properly registered. Missing 'executable' property";
            return 0;
	}

	//Setting defaults from tool definition 
	if (!isset($tool['infrastructure']['wallTime']) )
	   $tool['infrastructure']['wallTime'] = "1440"; // 24h

    if (!isset($tool['infrastructure']['interpreter'])){
        $tool['infrastructure']['interpreter']="";  // only required if "Single". Examples:  "bash", "python3" 
    }
    $cloud   = $tool['infrastructure']['clouds'][$this->cloudName];

	if (!isset($cloud['minimumVMs']) )
		   $cloud['minimumVMs'] = "1"; // if workflow_type = "Single" -> 1
	if (!isset($cloud['maximumVMs']) )
		   $cloud['maximumVMs'] = "1"; // if workflow_type = "Single" -> 1
	if (!isset($cloud['limitVMs']) )
		   $cloud['limitVMs']   = "1"; // TODO OBSOLETE (=== maximumVMs)?
	if (!isset($cloud['initialVMs']) )
		   $cloud['initialVMs'] = "1"; // if workflow_type = "Single" -> 1
	if (!isset($cloud['disk']) )
		   $cloud['disk'] = "1.0";     // TODO OBSOLETE?
	if (!isset($cloud['imageType']) ){
        //Assign imageType (size) from CPUS and RAM
        $flavor = $this->setImageType($tool['infrastructure']['cpus'],$tool['infrastructure']['memory']);
        $cloud['imageType']                 = $flavor['id'];
		$tool['infrastructure']['memory']   = $flavor['memory'];
		$tool['infrastructure']['cpus']     = $flavor['cpus'];
        $this->imageType = $flavor;
    }
    

	//Setting PMES execution user (name,uid,gid, token)
	exec("stat  -c '%u:%g' ".$this->working_dir,$stat_out);
	list($user_uid,$user_gid) = explode(":",$stat_out[0]);
	$user_name = "vre".substr(md5(rand()),0,5);

    $token_id="";
    if ($GLOBALS['clouds'][$this->cloudName]['auth']['required']){
            switch ($this->cloudName){
                // get openstack token 
                case 'mug-ebi':
                    $token=0;
                    // get token from session
                    if (isset($_SESSION['User']['Token_mug_ebi']['id'])){
                        $token  = $_SESSION['User']['Token_mug_ebi'];
                        if (openstack_isTokenExpired($token)){
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
                        modifyUser($_SESSION['User']['_id'],'Token_mug_ebi',$token);
                    }
                    $token_id= $token['id'];
                    break;
                
                // other clouds are opennebula. Auth via certs instead of tokens
                default:
                    $_SESSION['errorData']['Error']= "Cannot submit job. Requested cloud ($this->cloudName) requires authorization but no credentials found for VRE.";
                    return $data;
            }
    }


	//Setting executable as PMES requires
	$app_target =  dirname($tool['infrastructure']['executable']);
    $app_source = basename($tool['infrastructure']['executable']);

	//Building PMES json data
	$data = array(
		   array(
		"jobName"          => $this->execution, 
        "compssWorkingDir" => $this->root_dir_virtual."/".$this->project."/".$this->execution,

        "wallTime"   => $tool['infrastructure']['wallTime'], 
		"memory"     => $tool['infrastructure']['memory'],
		"cores"      => $tool['infrastructure']['cpus'],
		"minimumVMs" => $cloud['minimumVMs'], 
		"maximumVMs" => $cloud['maximumVMs'],
		"limitVMs"   => $cloud['limitVMs'],
		"initialVMs" => $cloud['initialVMs'],
		"disk"       => $cloud['disk'],
		"inputPaths" => array(),
		"outputPaths"=> array(),
		"infrastructure" =>  $this->cloudName,
		"mountPoints"=> array(
				    array("target"     => $this->root_dir_virtual,
					   "device"     => $GLOBALS['clouds'][$this->cloudName]['dataDir_fs']."/".$_SESSION['User']['id'],
					   "permissions"=> "rw"
				    ), 
				    array( "target"     => $this->pub_dir_virtual,
					   "device"     => $GLOBALS['clouds'][$this->cloudName]['pubDir_fs'],
					   "permissions"=> "r"
				    )
				), 
		"numNodes"   => "1",                                           //TODO OBSOLETE?
		"user"       => array (
              			"username"   => $user_name,                     // PMES creates /home/username/
                        "credentials"=> array(
					"pem"   => "/home/pmes/pmes.pem", // in PMES server path
					"key"   => "/home/pmes/pmes.key", // in PMES server path
					"uid"   => $user_uid,                   // PMES writes outputs using this uid
					"gid"   => $user_gid,                   // PMES writes outputs using this gid
					"token" => $token_id
					)
				),
		"img"        => array(
				"imageName" => $cloud['imageName'], 
				"imageType" => $cloud['imageType']
				),
      	"app"        => array(
				"name"   => $tool['_id'],
				"target" => $app_target,
                "source" => $app_source,
                "interpreter" => $tool['infrastructure']['interpreter'],
				"args"  => array(
					"config"      => $this->config_file_virtual,
				      //"root_dir"    => $this->root_dir_virtual,
				      //"public_dir"  => $this->pub_dir_virtual,
                      //"log_file"    => $this->log_file_virtual,
						"in_metadata" => $this->metadata_file_virtual,
                        "out_metadata"=> $this->stageout_file_virtual
						),
        			"type" => $cloud['workflowType']    // COMPSs || Single
				),				
	  //"compss_flags" => array( "flag" => " -g --summary --base_log_dir=".$this->root_dir_virtual."/".$this->execution)
        "compss_flags" => array( "flag" => " -g --summary -d "),
        "compssLogDir" => $this->root_dir_virtual."/".$this->project."/".$this->execution 

		)
    );
	return $data;
    }



    protected function setBashCmd_withoutApp($tool,$metadata){
	if (!isset($tool['infrastructure']['executable'])){
            $_SESSION['errorData']['Internal Error'][]="Tool '$this->toolId' not properly registered. Missing 'executable' property";
            return 0;
	}
	$cmd = $tool['infrastructure']['executable'];
	// Add to Cmd: --input_name fn_path
	foreach ($this->input_files as $input_name => $fnIds){
 	    foreach ($fnIds as $fnId){
		$fn  = $metadata[$fnId]['path'];
		$rfn = $GLOBALS['dataDir']."/$fn";
		$cmd .= " --$input_name $rfn";
	    }
	}
	// Add to Cmd: --argument_name value
	foreach ($this->arguments as $k=>$v){
		$cmd .= " --$k $v";
	}
	return $cmd;
    }


    protected function createSubmitFile_SGE($cmd){

	$working_dir= $this->working_dir;
	$bash_rfn   = $this->submission_file;
	$log_rfn    = $this->log_file;


	try{
	    $fout = fopen($bash_rfn,"w");
	    if (!$fout) {
		throw new Exception('Failed to create tool configuration file: '.$bash_rfn);
	    }
    	}
	catch (Exception $e){
		$_SESSION['errorData']['Error'][]="Failed to create queue submission file. ".$e->getMessage();
		return 0;
	}
	fwrite($fout, "#!/bin/bash\n");
	fwrite($fout, "# Generated by MuG VRE\n");
	fwrite($fout, "cd $working_dir\n");
	
	fwrite($fout, "\n# Running $this->toolId tool ...\n");
	fwrite($fout, "\necho '# Start time:' \$(date) > $log_rfn\n");

	
	fwrite($fout, "\n$cmd >> $log_rfn 2>&1\n");
	fwrite($fout, "\necho '# End time:' \$(date) >> $log_rfn\n");
	fclose($fout);

	return $bash_rfn;
    }

    protected function createSubmitFile_PMES($data){

	$json_rfn   = $this->submission_file;


	try{
	    $fout = fopen($json_rfn,"w");
	    if (!$fout) {
		throw new Exception('Failed to create tool configuration file: '.$json_rfn);
	    }
    	}
	catch (Exception $e){
		$_SESSION['errorData']['Error'][]="Failed to create queue submission file. ".$e->getMessage();
		return 0;
	}
	fwrite($fout, json_encode($data,JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
	fclose($fout);
	
	return $json_rfn;
    }

    /**
     * Submits 
     * @param string $inputs_request _REQUEST data from inputs.php form
    */
    public function submit($tool){
	    switch ($tool['infrastructure']['clouds'][$this->cloudName]['launcher']){
    	    case "SGE":
    		    return $this->enqueue($tool);
        		break;
        	    case "PMES":
    	    	return $this->callPMES();
        		break;
    	    default:
    	    	$_SESSION['errorData']['Error'][]="Tool '$this->toolId' not properly registered. Launcher for '$this->toolId' is set to: \"".$tool['infrastructure']['clouds'][$this->cloudName]['launcher']."\". Case not implemented.";
    		    return 0;
    	}
	    return 1;
    }	    


    protected function enqueue($tool){
	
    	logger("");
    	$memory = $tool['infrastructure']['memory'];
    	$cpus   = $tool['infrastructure']['cpus'];
    	$queue  = $tool['infrastructure']['clouds'][$this->cloudName]['queue'];
    
    	list($pid,$errMesg) = execJob($this->working_dir, $this->submission_file, $queue, $cpus, $memory);
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


    protected function callPMES(){

    	$data_string = file_get_contents($this->submission_file);
        $data = json_decode($data_string, true);
    	list($pid,$errMesg)  = execJobPMES($this->cloudName,$data);
    	if (!$pid){
            log_addError($pid,$errMesg,NULL,$this->toolId,$this->cloudName,"PMES",$data['cores'],$data['memory']);
            $_SESSION['errorData']['Error'][]="Internal error. Cannot enqueue job.";
            return 0;
        }

    	logger("USER:".$_SESSION['User']['_id'].", ID:".$_SESSION['User']['id'].", LAUNCHER:PMES, TOOL:".$this->toolId.", PID:$pid");
        log_addSubmission($pid,$this->toolId,$this->cloudName,"PMES",$data[0]['cores'],$data[0]['memory'],$this->working_dir);

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
        $mugfile        = array();
		$compressions   = $GLOBALS['compressions'];
        $mugfile['_id'] = $file['_id'];

		//path -> file_path (relative to user_data_directory)
        if (isset($file['path'])){
			if (preg_match('/^\//', $file['path']) || preg_match('/^'.$_SESSION['User']['id'].'/', $file['path']) ){
                $path = explode("/",$file['path']);
                $mugfile['file_path'] = implode("/",array_slice($path,-3,3));
			}else{
                $mugfile['file_path'] = $file['path'];
			}
        }else{
            $mugfile['file_path'] = NULL;
		}

		// format -> file_type
        if (isset($file['format'])){
            $mugfile['file_type'] = $file['format'];
        }else{
            $mugfile['file_type'] = "UNK";
        }

		// data_type -> data_type
        if (isset($file['data_type'])){
            $mugfile['data_type'] = $file['data_type'];
        }else{
            $mugfile['data_type'] = NULL;
        }

		// compressed -> compressed
        if (isset($file['path'])){
			$ext = pathinfo($file['path'], PATHINFO_EXTENSION);
			$ext = preg_replace('/_\d+$/',"",$ext);
			$ext = strtolower($ext);
            if (in_array($ext,array_keys($compressions)) ){
                $mugfile['compressed'] = $compressions[$ext];
            }else{
                $mugfile['compressed'] = 0;
            }
        }

		// input_files -> sources
        if (isset($file['input_files'])){
		if (!is_array($file['input_files'])){
			$mugfile['sources']=array($file['input_files']);
            	}else{
                	$mugfile['sources']=$file['input_files'];
		}
        }else{
            $mugfile['sources'] = array();
        }

		// owner -> user_id
        if (isset($file['owner']))
            $mugfile['user_id'] = $file['owner'];
        else
            $mugfile['user_id'] = $_SESSION['User']['id'];

		// mtime -> creation_time
        if (isset($file['mtime']))
            $mugfile['creation_time'] = $file['mtime'];
        else
            $mugfile['creation_time'] = new MongoDB\BSON\UTCDateTime(strtotime("now")*1000);

		// taxon_id -> taxon_id
        if (isset($file['taxon_id'])){
			$mugfile['taxon_id'] = $file['taxon_id'];
        }else{
		 	if(!isset($file['refGenome'])){
				$mugfile['taxon_id'] = 0;
            }else{
				//$refGenome_to_taxon = Array( "hg19"=>"9606", "R64-1-1"=>"4932", "r5.01"=>"7227");
                $mugfile['taxon_id'] =(isset($this->refGenome_to_taxon[$file['refGenome']])?$this->refGenome_to_taxon[$file['refGenome']]:0);
			}
		}

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

		// other -> meta_data
                $mugfile['meta_data']  = $file;

		// refGenome -> assembly	
                if (isset($mugfile['meta_data']['refGenome']) ){
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
    protected function setImageType($cpus_requested, $mem_requested){

       $cpus        = 0;
       $mem         = 0;

       // if not flavors list defined, complain and try default flavor
        
       if (!count($GLOBALS['clouds'][$this->cloudName]['imageTypes'])){
           $cpus        = 4;
           $mem         = 8;
           $flavor_name = "large";
           $_SESSION['errorData']['Internal'][]="Cannot set job virtual machine size for cloud '".$this->cloudName."'. Trying with '$flavor' ($cpus cores and $mem GB RAM). If job fails, report us please";
           $flavor = Array("id"=> $flavor_name, "name" => $flavor_name, $disk=> null);
           $flavor['cpus']   = $cpus;
           $flavor['memory'] = $mem;
           return $flavor;
       }

       // navigate flavors list to find the flavor better fits requested mem and cpus

       $mem_flavor;
       // first find flavor with the minimal RAM
       foreach ($GLOBALS['clouds'][$this->cloudName]['imageTypes'] as $mem_flavor => $flavors_list_mem ){
            if ($mem_requested > $mem_flavor)
                continue;
            $mem = $mem_flavor;
            break;
       }
       if (!$mem){
           $_SESSION['errorData']['Warning'][]="Cannot set job virtual machine with $cpus_requested cores and $mem_requested GB RAM for cloud '".$this->cloudName."'. Assigning maximum RAM = $mem_flavor GB";
           $mem = $mem_flavor;
       }

       $cpus_flavor;
       // second  find flavor with the minimal cores
       foreach ($GLOBALS['clouds'][$this->cloudName]['imageTypes'][$mem] as $cpus_flavor => $flavor_list_cpu){
            if ($cpus_requested > $cpus_flavor)
                continue;
            $cpus   = $cpus_flavor;
            break;

       }
       if (!$cpus){
           $_SESSION['errorData']['Warning'][]="Cannot set job virtual machine with $cpus_requested cores and $mem_requested GB RAM for cloud '".$this->cloudName."'. Assigning maximum cores = $cpus_flavor";
           $cpus = $cpus_flavor;
       }

       $flavor = $GLOBALS['clouds'][$this->cloudName]['imageTypes'][$mem][$cpus];
       $flavor['cpus']   = $cpus;
       $flavor['memory'] = $mem;
       return $flavor;
    }


    /**
     * Parse submission File
    */
    public function parseSubmissionFile(){
	return 1;
	
    }
}
?>
