<?php


class DataTransfer {

    public $_id;
    public $title;
    public $execution;         // User defined. Correspond to the execution folder name
    public $project;           // User defined. Correspond to the project
    public $toolId;
    public $root_dir_virtual;  // User dataDir. Mounted to VMs in PMES. Already there im SGE. Path as seen by VMs
    public $pub_dir_virtual;   // Public dir mounted to VMs. Path as seen by VMs  
    public $pub_dir_fs;        // Public dir on MN.
    public $root_dir_fs;       // User DataDir on MN.
    public $cloudName;         // Cloud name where tool should be executed. Available clouds set in GLOBALS['clouds']
    public $description;
    public $working_dir;
    public $output_dir;
    public $launcher;

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


    //private $vaultHost;
    //private $vaultPort;
    //private $vaultToken;

    
    public function __construct($tool,$input_files,$execution="",$project="",$descrip="",$output_dir=""){


        // Setting Tooljob
        $this->toolId    = $tool['_id'];
        $this->title     = $tool['name'] ." job";
        $this->execution = $execution;
	$this->project   = $project;
	$this->input_files = $input_files;

	//$this->vaultHost = 'vault';
	//$this->vaultPort = 8200;
	//$this->vaultToken = 'root';

        // Set paths in VRE
        $this->root_dir  = $GLOBALS['dataDir']."/".$_SESSION['User']['id'];
        $this->pub_dir   = $GLOBALS['pubDir'];

        // Set paths in the virtual machine
	$this->set_cloudName($tool);
	// will come out the marenostrum_dt (dtrclone)
	// will do another function?
	$this->launcher         = $tool['infrastructure']['clouds'][$this->cloudName]['launcher'];
	// will come out DTRCLONE
        switch ($this->launcher){
            case "SGE":
            case "docker_SGE":
                $this->root_dir_virtual = $GLOBALS['clouds'][$this->cloudName]['dataDir_virtual']. "/".$_SESSION['User']['id'];
                $this->root_dir_mug      = $GLOBALS['clouds'][$this->cloudName]['dataDir_virtual'];
                $this->pub_dir_virtual  = $GLOBALS['clouds'][$this->cloudName]['pubDir_virtual'];
                break;
            case "DTRCLONE":
                $this->root_dir_virtual = $GLOBALS['clouds'][$this->cloudName]['dataDir_virtual'];
		$this->pub_dir_virtual  = $GLOBALS['clouds'][$this->cloudName]['pubDir_virtual'];
		$this->root_dir_fs = $GLOBALS['clouds'][$this->cloudName]['dataDir_fs'];
		$this->pub_dir_fs = $GLOBALS['clouds'][$this->cloudName]['pubDir_fs'];
		$this->auth = $GLOBALS['clouds'][$this->cloudName]['auth'];
		$this->http_host = $GLOBALS['clouds'][$this->cloudName]['http_host'];
		$this->port = $GLOBALS['clouds'][$this->cloudName]['port'];
		break;
	    case "Slurm":
		    $this->root_dir_df = $GLOBALS['clouds'][$this->cloudName]['mn_dir'] .  "/".substr($_SESSION['User']['linked_accounts']['SSH']['hpc_username'], 0, 5). "/".$_SESSION['User']['linked_accounts']['SSH']['hpc_username']. "/".$GLOBALS['clouds'][$this->cloudName]['dataDir_fs'];
		    $this->pub_dir_fs = $GLOBALS['clouds'][$this->cloudName]['mn_dir'] .  "/".substr($_SESSION['User']['linked_accounts']['SSH']['hpc_username'], 0, 5)."/".$_SESSION['User']['linked_accounts']['SSH']['hpc_username']. "/".$GLOBALS['clouds'][$this->cloudName]['pubDir_fs'];
		    $this->auth = $GLOBALS['clouds'][$this->cloudName]['auth'];
		    $this->http_host = $GLOBALS['clouds'][$this->cloudName]['http_host'];
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


    protected function getTool($toolId){
        $tool   = $GLOBALS['toolsCol']->findOne(array('_id' => $toolId));
        if (empty($tool)){
                $_SESSION['errorData']['Tooljob'][]="Tool '$toolId' is not registered. Cannot submit execution. Please, contact <a href=\"mailto:".$GLOBALS['helpdeskMail']."\">us</a>";
                return 0;
        }
        //$this->tool= (object) $tool;
        $this->tool= $this->array_to_object($tool);
    }


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

    
    public function getList($input_files){

	    $list=[];
	    foreach ($input_files as $id => $input_file){
                    $f = getGSFile_fromId($input_file);
		    //echo $f . "<br> FILE";
		    $result = $this->getUrifrom($f);
		    //echo "<br> Result";
		    //var_dump($result);
		    $list[$id] = $result; 
	    }
	    return $list;

    }


    public function checkLoc($input_files){

	    $firstLoc = null;

	    foreach ($input_files as $input_file){
		   $id = $input_file['_id'];
		   $location = $item['location'];

		   if ($firstLoc == null) {
			   $firstLoc = $location;
		   } else {
			   if ($location !== $firstLoc) {
				   return false;
			   }

		   }

            }
	    
	    
	    return true;

    }
 
    
    public function getUrifrom($obj){
	    if(!isset($obj['file_url'])) {
		    $_SESSION['ErrorData']['Error'][]="URI not found in object. Expected 'uri' atribute in object File";
	    }
	    $array = [];
	    $array['_id'] = $obj['_id'];
	    $array['local_path'] = $obj['path'];
	    $parts = parse_url($obj['file_url']);
	    $array['protocol'] = $parts['scheme'];
	    $array['location'] = $parts['host'];
	    $array['path'] = $parts['path'];
	
	    return $array;
    }
    


protected function set_cloudName($tool = array()) {
    $available_clouds = array_keys($GLOBALS['clouds']);

    // 1, set cloudName from tool specification, the first found available
    if (!$this->cloudName && isset($tool['infrastructure']['clouds'])) {
        foreach ($tool['infrastructure']['clouds'] as $name => $cloudInfo) {
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
    if (!$this->cloudName) {
        foreach ($tool['infrastructure']['clouds'] as $name => $cloudInfo) {
            if (in_array($name, $available_clouds)) {
                $this->cloudName = $name;
                $_SESSION['errorData']['Warning'][] = "Tool has no default cloud infrastructure set or available. Taking instead '$this->cloudName', but the tool execution may fail.";
                break;
            }
        }
    }

    // 4, set cloudName from the server available_clouds, the first
    if (!$this->cloudName) {
        $this->cloudName = $available_clouds[0];
        $_SESSION['errorData']['Warning'][] = "Tool has no cloud infrastructure set. Taking '$this->cloudName', but the tool execution may fail.";
    }

    return 1;
}
	



public function handleFileLocation($location, $file_path, $local_file_path, $vaultUrl, $vaultToken, $vaultRole) {
	
	if (isset($_SESSION['userToken']['access_token']) && !empty($_SESSION['userToken']['access_token'])) {
		$accessToken = $_SESSION['userToken']['access_token'];	
		
		print "</br> $vaultUrl </br>";

		$vaultClient = new VaultClient($vaultUrl, $vaultToken, $accessToken, $vaultRole, $_POST['username']);
		$vaultKey = $_SESSION['User']['Vault']['vaultKey'];
		if (empty($vaultKey)) {
			$_SESSION['errorData']['Error'][] = "No key to access Vault, check the User credentials.";
			return 0;
     	   	}
		switch ($location){
		
			case "swift": 
				if ($local_file_path) {
				//	$credentials= $this->handleSwiftCase($accessToken, $vaultClient, $vaultKey);
				//	var_dump($credentials);
					//return $credentials;
					//$dest = $this->handleSwiftPathFile($credentials, $file_path, $this->root_dir_df, $this->http_host);
                                	//if ($dest == 0) {
                                        $_SESSION['errorData']['Warning'][] = "Files have been copied from Swift to run the Tool locally";
                                	//} else if ($dest == 1) {
                                        //	$_SESSION['errorData']['Warning'][] = "Files have been copied from Swift to run the Tool locally.";
		
				} else {
					$_SESSION['errorData']['Warning'][] = "Files are not available locally, check the Catalogue session to download them.";
					$credentials= $this->handleSwiftCase($accessToken, $vaultClient, $vaultKey);
				}	
				break;
			case "MN":
				$credentials = $this->handleSSHCase($accessToken, $vaultClient, $vaultKey, $vaultUrl);
				var_dump($credentials);
				print $this->http_host;
				$dest = $this->handleSSHPathFile($credentials, $file_path, $this->root_dir_df, $this->http_host);
				if ($dest == 0) {
					$_SESSION['errorData']['Warning'][] = "MareNostrum doesn't need file transfer, continuing.";
				} else if ($dest == 1) {
					$_SESSION['errorData']['Warning'][] = "Files have been copied to run the Tool in MareNostrum.";
				}
				break;
			case "others":
				break;

			default:
				break;
		}
	} else {
		return  0;
		 //$_SESSION['errorData']['Error'][] = "Access Token is missing or empty.";
	}
}



	protected function handleSwiftCase($accessToken, $vaultClient, $vaultKey) {
		echo "SWIFT case is true <br></br>";
		//
		//Assuming location is the same for all files
		$vaultKey = $_SESSION['User']['Vault']['vaultKey'];
		$vaultUrl = $GLOBALS['vaultUrl'];
		echo "Vault Key: $vaultKey<br>";
		echo "Vault Url: $vaultUrl<br>";

		$credentials = $vaultClient->retrieveDatafromVault('Swift', $vaultKey, $vaultUrl, 'secret/mysecret/data/', $_SESSION['User']['_id'] . '_credentials.txt');
// ($system, $clientToken, $url, $secretPath, $filename)
		echo "System <br></br>";
    		echo strtoupper("swift");

    		echo "Token <br></br>";
    		echo $vaultKey;

    		echo "Vault Url <br></br>";
    		echo $vaultUrl;

		var_dump($credentials);
		return $credentials;
		// Rest of the code to download the data using the credentials
	}


	public function handleSSHCase($accessToken, $vaultClient, $vaultKey, $vaultUrl){
		
		
		echo "SSH case is true <br></br>";
		//var_dump($_SESSION['User']);

		echo "Vault Key: $vaultKey<br>";
                echo "Vault Url: $vaultUrl<br>";

		$credentials = $vaultClient->retrieveDatafromVault('SSH', $vaultKey, $vaultUrl, 'secret/mysecret/data/', $_SESSION['User']['_id'] . '_credentials.txt');

		//var_dump($credentials);
		return $credentials;
		// Rest of the code to check if there is the path/file or it is necessary to download them there from locally
	
	}

	public function handleSSHPathFile($credentials, $file_path, $remote_dir, $http_server) {
		
		$isDirectory = pathinfo($file_path, PATHINFO_EXTENSION) === '';
		
		if ($isDirectory) {
			//print "Drama dir {$remote_dir}";
			return 0;
		} else {
        		//Logic to copy the file to the SSH server
			$remoteSSH = new RemoteSSH($credentials, $remote_dir, $http_server );
			$success = $this->copyFileToSSH_SFTP($path_file, $remoteDir, $credentials, $http_server);

        		if ($success) {
            			// File copied successfully, continue with the tool
				//$this->handleTool($remoteDir . basename($path_file), $accessToken, $vaultClient, $vaultKey, $vaultUrl);
				return 1;
        		} else {
            			// Handle the case when the file copy fails
            			$_SESSION['errorData']['Error'][] = "Failed to copy the file to the SSH server.";
        		}
		}

	}


}
