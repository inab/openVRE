<?php

class RegisterTool {

    public $tool_io;
    public $toolId;
    public $status;
    public $dev_dir;
    public $working_dir;

    // absolute local installation paths
    public $execution;
    public $project;
    public $tool_executable;
    public $tool_lib;

    public $metadata;
    public $input_files;
    public $input_files_pub;
    public $arguments;

    public $configuration_files;
    public $metadata_files;
    public $bash_files;
    public $tar_file;


    /**
     * Creates new tool Register process
     * @param string $tool Tool object as appears in Mongo
    */
    public function __construct($tool_io,$execution=0,$tool_executable=0,$tool_lib=0){
    
    	// Setting RegisterTool
    	$this->tool_io   = $tool_io;
        $this->toolId    = $tool_io['_id'];

        $this->cloudName="mug-irb";
        $this->root_dir_virtual = $GLOBALS['clouds'][$this->cloudName]['dataDir_virtual'];
        $this->pub_dir_virtual  = $GLOBALS['clouds'][$this->cloudName]['pubDir_virtual'];


        // Set default local installation paths
        if (!$execution){
            $_SESSION['errorData']['Warning'][]="Setting a foo value for 'execution'. Please, edit it manually into test files (test.sh & config.json)";
        }
        if (!$tool_executable){
            $_SESSION['errorData']['Warning'][]="'Setting a foo value for \$TOOL_EXECUTABLE'. Please, edit it manually into test files (test.sh)";
        }
        $this->tool_lib        =($tool_lib? $tool_lib : "/packages/to/add/into/pythonpath");
        $this->tool_executable =($tool_executable? $tool_executable : "/main/mg-tool/executable.py");
        $this->execution       =($execution? $execution : "/test/execution/directory");
        if (!preg_match('/^\//', $execution) || !preg_match('/^\//', $tool_lib) || !preg_match('/^\//', $tool_executable) ){
            $_SESSION['errorData']['Warning'][]="Make sure that paths given for files and directories of your local installation are absolute paths.";
            //return 0;
        }
        $this->project ="my_project_id";    

        // Create working_dir
        $this->dev_dir = $GLOBALS['dataDir']."/".$_SESSION['User']['id']."/.dev/".$this->toolId;
        $this->working_dir = $GLOBALS['dataDir']."/".$_SESSION['User']['id']."/.dev/".$this->toolId."/test";
        $this->working_dir = preg_replace('#/+#','/',$this->working_dir);
    	if (!is_dir($this->working_dir)){
            mkpath($this->working_dir);
        }
    	return $this;
    }


    /**
     * Creates tool configuration JSON
     * @param array $tool Fill in config file: input_files, arguments and output_files
    */
    public function setConfiguration_files(){

    $execution = $this->execution;    
    $project   = $this->project;

    // check tool_io
    if (!isset($this->tool_io['_id']) || !isset($this->tool_io['input_files']) || !isset($this->tool_io['arguments']) || !isset($this->tool_io['output_files'])){
        $_SESSION['errorData']['Error'][]="Cannot create test configuration file. The given inputs/outputs definition is not correct";
		return 0;
    }

  	// Set json base
  	$data_base = Array(
   		'input_files'=>Array(),
   		'arguments'=>Array(
   			Array("name"=>"execution",  "value"=> $execution),
   			Array("name"=>"project",    "value"=> $project)
   		),
   		'output_files'=>Array()
    );

    // prepare config for each input_file_combination
    $c=0;
    foreach ($this->tool_io['input_files_combinations'] as $comb){

        // Set rfn
        $operation_id  =  $c."_".preg_replace('/[^A-Za-z0-9]/', '', str_replace(' ', '', $comb['description']));
        $test_dir   = $this->working_dir."/json/$operation_id";
        $config_rfn = "$test_dir/config.json";

        // start json data
        $data  = $data_base;

        // append input_files
        foreach ($this->tool_io['input_files'] as $input_name=>$input){

            if (in_array($input_name,$comb['input_files']) ){
                foreach ($this->input_files[$input_name] as $v){
                    array_push($data['input_files'], Array(
                                            "name"          => $input_name,
                                            "value"         => $v,
                                            "required"      => $this->tool_io['input_files'][$input_name]['required'],
                                            "allow_multiple"=> $this->tool_io['input_files'][$input_name]['allow_multiple']
                                     )
                    );
                }
           }
        }

        // append public input_files
        if (count($this->tool_io['input_files_public_dir'])){
          foreach ($this->tool_io['input_files_public_dir'] as $input_name=>$input){

            if (isset($comb['input_files_public_dir']) && in_array($input_name,$comb['input_files_public_dir'])){
                foreach ($this->input_files_pub[$input_name] as $v){
                    array_push($data['input_files'], Array(
                                            "name"          => $input_name,
                                            "value"         => $v,
                                            "required"      => $this->tool_io['input_files_public_dir'][$input_name]['required'],
                                            "allow_multiple"=> $this->tool_io['input_files_public_dir'][$input_name]['allow_multiple']
                                     )
                    );
                }
           }
          }
        }


        // append arguments
        if (count($this->arguments)){
    	    foreach ($this->arguments as $k=>$v){
    		    array_push($data['arguments'], Array("name"=>$k, "value"=> $v));
            }
        }
        // append output_files from tool json
        if ($this->tool_io['output_files']){
            foreach ($this->tool_io['output_files'] as $k => $v){
    	        if (isset($v['file']['file_path'])){
            		$v['file']['file_path'] = $execution ."/".$v['file']['file_path'];
        	    }
    	        $data['output_files'][] = $v;
            }
        }

    	// write JSON
        try{
            $wd = dirname($config_rfn);
            if(!is_dir($wd)){
                print "MKDIR $wd <br/>";
                mkdir($wd,0777,true);
            }
    	    $F = fopen($config_rfn,"w");
    	    if (!$F) {
    		throw new Exception('Failed to create tool configuration file'.$config_rfn);
    	    }
       	}catch (Exception $e){
    		$_SESSION['errorData']['Internal Error'][]= $e->getMessage();
    		return 0;
	    }

    	fwrite($F, json_encode($data,JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    	fclose($F);
        $c++;
        $this->configuration_files[$comb['description']] = $config_rfn;
    }

	return $this->configuration_files;
    }


    /**
     * Set Arguments
     * @param array $arguments Arguments as received from inputs.php
    */
    public function setArguments($arguments){

    $tool_io = $this->tool_io;    
	foreach ($arguments as $arg_name => $arg_value){
	    //checking  requirements
	    if (count($tool_io)){
		// checking coherence between JSON and REQUEST
		if (!isset($tool_io['arguments'][$arg_name])){
			$_SESSION['errorData']['Internal'][]="Argument '$arg_name' not found in tool definition. '$this->toolId' is not properly registered";
			return 0;
		}
		// checking arguments requirements
		if ($arg_value==""){
		    if ($tool_io['arguments'][$arg_name]['required']){
    			$_SESSION['errorData']['Error'][]="No value given for argument '$arg_name'";
		    	return 0;
		    }else{
    			continue;
		    }
		}
		switch ($tool_io['arguments'][$arg_name]['type']){
		    case "enum":
			if (!isset($tool_io['arguments'][$arg_name]['enum_items']) || (!isset($tool_io['arguments'][$arg_name]['enum_items']['name']))){
			    $_SESSION['errorData']['Internal'][]="Invalid argument enum in tool definition. '$arg_name' has no 'enum_items' or 'enum_items['name].";
		 	    return 0;
			}
			if (!in_array($arg_value,$tool_io['arguments'][$arg_name]['enum_items']['name']) ){
	    		    $_SESSION['errorData']['Error'][]="Invalid argument. In '$arg_name' these values are accepted [".implode(", ",$tool_io['arguments'][$arg_name]['enum_items']['name'])."], but found $arg_value";
			    return 0;
			}
			break;
		    case "enum_multiple":
			if (!isset($tool_io['arguments'][$arg_name]['enum_items']) || (!isset($tool_io['arguments'][$arg_name]['enum_items']['name']))){
			    $_SESSION['errorData']['Internal'][]="Invalid argument enum in tool definition. '$arg_name' has no 'enum_items' or 'enum_items['name].";
		 	    return 0;
			}
			if (!is_array($arg_value))
				$arg_value=array($arg_value);
			foreach ($arg_value as $v){
				if (!in_array($v,$tool_io['arguments'][$arg_name]['enum_items']['name']) ){
			    		$_SESSION['errorData']['Error'][]="Invalid argument. In '$arg_name' these values are accepted [".implode(", ",$tool_io['arguments'][$arg_name]['enum_items']['name'])."], but found  ".implode(", ",$arg_value);
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
     * Set metadata 
     * @param array $input_file_paths Input_files paths as received from inputs.php
     * @param array $tool_io Tool array containing input_files type and requirements
    */
    public function setMetadata_fromTool($input_file_paths,$user_metadata=array(),$is_public=false){

        $tool_io = $this->tool_io;    
        $meta=array();

        $input_files_key = ($is_public?"input_files_public_dir":"input_files");

        foreach ($input_file_paths as $input_name => $file_paths){
		    if (!is_array($file_paths))
                $file_paths=array($file_paths);

            foreach ($file_paths as $file_path){
                if (isset($tool_io[$input_files_key][$input_name])){
                    $fileInput = array("file_type" => $tool_io[$input_files_key][$input_name]["file_type"][0],
                                       "data_type" => $tool_io[$input_files_key][$input_name]["data_type"][0],
                                       "path" => $file_path,
                                       "project" => $this->project);

                    if (!isset($user_metadata[$input_name]))
                        $user_metadata[$input_name]=array();
                    list($fileData,$fileMeta) = setVREFile_fromScratch(array_merge($fileInput,$user_metadata[$input_name]));
                    $meta[$fileData['_id']]= array_merge($fileData,$fileMeta);
                }else{
                    $_SESSION['errorData']['Internal'][]="Error: Input file in '$input_name' not defined at the JSON";
                    continue;
                }
            }
        }
        // setting metadata
        if ($is_public)
            $this->metadata_pub = $meta;
        else
            $this->metadata = $meta;


        return 1;
    }

    /**
     * Set inputFiles
     * @param array $input_files  Input_files as received from inputs.php
    */
    public function setInput_files($input_file_paths,$is_public=false){

    $input_files=array();    
    $tool_io = $this->tool_io;

    if (!$this->metadata){
		$_SESSION['errorData']['Internal'][]="Cannot set Input_files. Metadata not generated yet";
		return 0;
    }
    $metadata = ($is_public? $this->metadata_pub : $this->metadata);

	foreach ($input_file_paths as $input_name => $file_paths){
	    //checking  requirements
	    if (count($tool_io) && count($metadata)){
		    if (!is_array($file_paths))
    			$file_paths=array($file_paths);

            foreach ($file_paths as $file_path){
            // checking value not empty
			if (!$file_path){
				$_SESSION['errorData']['Error'][]="No file given for '$input_name'";
				return 0;
            }
            // get fn from metadata
            foreach ($metadata as $fn => $file){
                if ($file['path'] == $file_path){
                    $input_files[$input_name] = (isset($input_files[$input_name])?array_push($fn,$input_files[$input_name]):array($fn));
                    break;
                }
            }
		    }   
	    }
    }
    // setting input_files
    if ($is_public)
        $this->input_files_pub = $input_files;
    else
        $this->input_files = $input_files;

    return 1;
    }


    /**
     * Set inputFiles from public dir
     * @param array $input_files  Input_files_public_dir  as received from inputs.php
    */
    public function setInput_files_public($input_file_paths){

    $input_files_public=array();    
    $tool_io = $this->tool_io;

    if (!$this->metadata_public){
		$_SESSION['errorData']['Internal'][]="Cannot set Input_files. Metadata not generated yet";
		return 0;
    }
    $metadata = $this->metadata_public;

	foreach ($input_file_paths as $input_name => $file_paths){
	    //checking  requirements
	    if (count($tool_io) && count($metadata)){
		    if (!is_array($file_paths))
    			$file_paths=array($file_paths);

            foreach ($file_paths as $file_path){
            // checking value not empty
			if (!$file_path){
				$_SESSION['errorData']['Error'][]="No file given for '$input_name'";
				return 0;
            }
            // get fn from metadata
            foreach ($metadata as $fn => $file){
                if ($file['path'] == $file_path){
                    $input_files_public[$input_name] = (isset($input_files_public[$input_name])?array_push($fn,$input_files_public[$input_name]):array($fn));
                    break;
                }
            }
		    }   
	    }
    }
    // setting input_files
    $this->input_files_pub = $input_files_public;
    return 1;
    }




    /**
     * Creates metadata JSON
    */
    public function setMetadata_files(){
	if (!$this->working_dir){
		$_SESSION['errorData']['Internal Error'][]="Cannot create metadata file. No 'working_dir' set";
		return 0;
	}
    if (!$this->metadata){
		$_SESSION['errorData']['Internal'][]="Cannot set Input_files. Metadata not generated yet";
		return 0;
    }
    $metadata = $this->metadata;
    $metadata_pub = $this->metadata_pub;

    $fileMuGs=Array();

    // add input_files metadata
    foreach ($metadata as $fnId => $file){
        // convert metadata to DMP format
        $fileMuG = $this->fromVREfile_toMUGfile($file);

        // adapt metadata to App requirements
//        if (isset($fileMuG['input_files'])){
//	        $fileMuG['sources'] = $fileMuG['input_files'];
//	        unset($fileMuG['input_files']);
//        }

        $fileMuGs[$fileMuG['_id']] = $fileMuG;
    }
    // add input_files public metadata
    if (count($metadata_pub)){
    foreach ($metadata_pub as $fnId => $file){

        // convert metadata to DMP format
        $fileMuG = $this->fromVREfile_toMUGfile($file);

        // adapt metadata to App requirements
//      if (isset($fileMuG['input_files'])){
//	        $fileMuG['sources'] = $fileMuG['input_files'];
//	        unset($fileMuG['input_files']);
//      }
        if ($fileMuG['file_path']){
            $fileMuG['file_path'] = $this->pub_dir_virtual."/".$fileMuG['file_path'];
        }
        
        $fileMuGs[$fileMuG['_id']] = $fileMuG;
    }
    }


    // prepare metadata file for each input_file_combination
    $c=0;
    foreach ($this->tool_io['input_files_combinations'] as $comb){

        // Set rfn
        $operation_id  =  $c."_".preg_replace('/[^A-Za-z0-9]/', '', str_replace(' ', '', $comb['description']));
        $metadata_rfn = $this->working_dir."/json/$operation_id/in_metadata.json";

        $data=array();

        // Add metadata for each input_file
        foreach ($this->tool_io['input_files'] as $input_name=>$input){
            if (in_array($input_name,$comb['input_files']) ){
                foreach ($this->input_files[$input_name] as $fnId){
                    array_push($data,$fileMuGs[$fnId]);
                }
            }
        }
        // Add metadata for each public input_file
        if (count($this->tool_io['input_files_public_dir'])){
          foreach ($this->tool_io['input_files_public_dir'] as $input_name=>$input){
            if (isset($comb['input_files_public_dir']) && in_array($input_name,$comb['input_files_public_dir']) ){
                foreach ($this->input_files_pub[$input_name] as $fnId){
                    array_push($data,$fileMuGs[$fnId]);
                }
            }
          }
        }

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

        fwrite($F, json_encode($data,JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        fclose($F);
        $c++;
        $this->metadata_files[$comb['description']] = $metadata_rfn;

    }

      return $this->metadata_files;
    }



    /**
     * Creates execution Command Line and Submission File
    */
    public function setBash_files($workflowtype="single"){

        if (!$workflowtype || !(!preg_match('/single/i',$workflowType) &&  !preg_match('/COMPSs/i',$workflowType))){
            $_SESSION['errorData']['Error'][]="Workflow type not set. Accepted values are: 'Single' and 'COMPSs'.";
            return 0;
        }
           
        // prepare CMDs
    	$cmd  = $this->setBashTestCmd($workflowtype);
        if (!$cmd)
    	    return 0;

        // write CMDs into files
        return  $this->setBashTestFiles($cmd,$workflowtype);
    }


    protected function setBashTestCmd($workflowtype){

    $cmd        = "";

    if (preg_match('/single/i',$workflowtype)){
    	$cmd = "\$TOOL_EXECUTABLE" .
  					" --config \$TEST_DATA_DIR/config.json" .
   					" --in_metadata \$TEST_DATA_DIR/in_metadata.json" .  
                    " --out_metadata \$WORKING_DIR/out_metadata.json" ;
        
    }elseif (preg_match('/COMPSs/i',$workflowtype)){

        $cmd = "runcompss -d --summary " .
                    " --base_log_dir=\$WORKING_DIR".
                    " --lang=python".
                    " --pythonpath=\$TOOL_LIBRARY".
                    " \$TOOL_EXECUTABLE ".
  					" --config \$TEST_DATA_DIR/config.json" .
   					" --in_metadata \$TEST_DATA_DIR/in_metadata.json" .
                    " --out_metadata \$WORKING_DIR/out_metadata.json" ;
    }
    print "CMD = ($workflowtype ) $cmd<br/>";
    return $cmd;
    }


    public function setBashTestFiles($cmd,$workflowtype){

    // prepare metadata file for each input_file_combination
    $c=0;
    foreach ($this->tool_io['input_files_combinations'] as $comb){

        // Set rfn
        $operation_id  =  $c."_".preg_replace('/[^A-Za-z0-9]/', '', str_replace(' ', '', $comb['description']));
	    $bash_rfn      = $this->working_dir."/test_$operation_id.sh";

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
    	fwrite($fout, "\n");
    	fwrite($fout, "###\n");
    	fwrite($fout, "### Testing in a local installation\n");
        fwrite($fout, "### the VRE server CMD\n");
    	fwrite($fout, "###\n");
    	fwrite($fout, "### * Automatically created by VRE *\n");
    	fwrite($fout, "###\n");
    	fwrite($fout, "\n");
    	fwrite($fout, "\n");
    	fwrite($fout, "# Local installation - EDIT IF REQUIRED\n");
    	fwrite($fout, "\n");
    	fwrite($fout, "WORKING_DIR=".$this->execution."\n");
    	fwrite($fout, "TOOL_EXECUTABLE=".$this->tool_executable."\n");
    	if ($this->tool_lib && preg_match('/COMPSs/i',$workflowtype)){fwrite($fout, "TOOL_LIBRARY=".$this->tool_lib."\n");}
    	fwrite($fout, "\n");
    	fwrite($fout, "# Test input files\n");
    	fwrite($fout, "\n");
    	fwrite($fout, "CWD=\"$( cd \"$( dirname \"\${BASH_SOURCE[0]}\" )\" && pwd )\"\n");
    	fwrite($fout, "TEST_DATA_DIR=\$CWD/json/$operation_id\n");
    	fwrite($fout, "\n");
    	fwrite($fout, "\n# Running $this->toolId tool ...\n");
    	fwrite($fout, "\n");
    	fwrite($fout, "if [ -d  \$WORKING_DIR ]; then rm -r \$WORKING_DIR/; mkdir -p \$WORKING_DIR; else mkdir -p \$WORKING_DIR; fi\n");
    	fwrite($fout, "cd \$WORKING_DIR\n");
    	fwrite($fout, "\n");
    	fwrite($fout, "echo \"--- Test execution: \$WORKING_DIR\"\n");
    	fwrite($fout, "echo \"--- Start time: `date`\"\n");
        fwrite($fout, "\n");
        fwrite($fout, "time $cmd > \$WORKING_DIR/tool.log\n");
        fwrite($fout, "\n");
        fclose($fout);

        $c++;
        $this->bash_files[$comb['description']] = $bash_rfn;
    }
    
    return $this->bash_files;
    }


    /**
     * Creates TAR file with all test files generated
    */
    public function tar_test_files(){
        try{
            $tar_file = $this->working_dir."/test.tar";
            $tar_file_compressed  = $this->working_dir."/test.tar.gz";

            if (is_file($tar_file_compressed)){
                unlink($tar_file_compressed);
            }
            $a = new PharData($tar_file);
            $a->buildFromDirectory($this->dev_dir);
            $a->compress(Phar::GZ);
            unlink($tar_file);
            $this->tar_file = $tar_file_compressed;

        }catch (Exception $e) {
            $_SESSION['errorData']['Error'][] = $e;
            return 0;
        }
        return $this->tar_file;
    }


    /**
     * Saves into DB the paths for all test files generated
    */
    public function save_test_files(){

        $all_test_files = array();
        foreach ($this->tool_io['input_files_combinations'] as $comb){
            $all_test_files[$comb['description']] = array( "configuration_file" => str_replace($GLOBALS['dataDir'],"",$this->configuration_files[$comb['description']]),
                                                            "metadata_file"     => str_replace($GLOBALS['dataDir'],"",$this->metadata_files[$comb['description']]),
                                                            "bash_file"         => str_replace($GLOBALS['dataDir'],"",$this->bash_files[$comb['description']]));
        }
        $GLOBALS['toolsDevMetaCol']->updateOne(array('_id' => $this->toolId),
                                            array('$set'   => array('last_status_date' => date('Y/m/d H:i:s'),
                                                                    'step1.date'       => date('Y/m/d H:i:s'),
                                                                    'step1.tool_io_files' => true,
                                                                    'step1.status'        => true,
                                                                    'step1.tool_io_saved' => true,
                                                                    'step1.test_files'    => str_replace($GLOBALS['dataDir'],"",$this->tar_file),
                                                                    'step1.files'      => $all_test_files)
                                                                ));

        return 1;
    }

    /**
     * Saves into DB REQUEST form data
    */
    public function save_form_data($step,$request){

        $GLOBALS['toolsDevMetaCol']->updateOne(array('_id' => $this->toolId),
                                            array('$set'=> array($step.".form_data" => $request)));
        return 1;
    }

    /**
     * Convert internal VRE file format into DM MuG file  
     * @file  VRE file object, resulting from merging  VRE Mongo collections Files + FilesMetadata
    */
    protected function fromVREfile_toMUGfile($file) {
        $mugfile        = array();
		$compressions   = $GLOBALS['compressions'];
        $mugfile['_id'] = $file['_id'];

		//path -> file_path  (absolute path in local installation)
        if (isset($file['path'])){
            if (preg_match('/^\//', $file['path']) ){ 
                $mugfile['file_path'] = $file['path'];
            }else{
                //$_SESSION['errorData']['Warning'][]="Input metadata JSON cannot be correctly set. Expected absolute path but relative received: ".$file['path'];
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


        //print "<br/><br/> MUG FILE DEFINITU <br/>";
        //var_dump($mugfile);
        return $mugfile;
    }

}
?>
