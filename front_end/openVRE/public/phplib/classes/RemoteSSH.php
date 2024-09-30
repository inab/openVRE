<?php

//require_once 'vendor/autoload.php';

use phpseclib3\Net\SSH2;
use phpseclib3\Crypt\RSA;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SFTP;


// Opening the SSH session with MN and access to Swift Object Storage to copy the data
// Inputs: 
//  - Private Key
//  - Username
//  - Application credentials (in a file or not)
//  - Remote path (where to launch command and where to store the data) ? see below
//  - Should we make a new dir for each job launched???
//  Put them all together in a Python file?
//  Put them all together in a YAML file?
//   >> Copy and save credentials in a YAML file?
//   >> Input file would be a file with all the credentials already? Already in PHP
//   >> Use the credentials variable to access to the SSH and save the session
//   >> In the same session, create a tmp folder to store data (remote location specified or random?)
//   >> Copy the data there  --> name of the dir same as job ID? so to keep it consistent?
//   >> Exec command
//   >> Copy the result file to DB or back to Swift 
//   >> Remove tmp folder from MN 



class RemoteSSH { 
    //private $ssh;
    private $port;                // standard 22 port for ssh connection
    private $username;            // bsc username for MN  
    private $credentials;         //Array with credentials
    //private $os_credential;       // path of credential sh file
    private $remote_credential;   // path of remote dir
    private $hhtp_server;
    // private $public_key;
    //private $privateKey;          // path of private Key
    //private $clone_sh;
    //private $clone_sh_path;
    //private $tmp_path;
    //protected $ssh_session;


    //public function __construct($tool,$input_files,$execution="",$project="",$descrip="",$output_dir="")
    //public function __construct($host, $port, $username, $privateKey, $fileList)
    public function __construct($credentials, $remote_dir, $port = 22, $http_server = null, $execution="",$project="",$descrip="",$output_dir="")
    {
	    
	//$this->root_dir_fs = $GLOBALS['clouds'][$this->cloudName]['mn_dir']. "/".$_SESSION['User']['linked_accounts']['MN']['username']. "/".$GLOBALS['clouds'][$this->cloudName]['dataDir_fs'];
	// "/home/\$REMOTE_USERNAME/openvre/userdata/"
        //$this->pub_dir_fs = $GLOBALS['clouds'][$this->cloudName]['mn_dir']. "/".$_SESSION['User']['linked_accounts']['MN']['username']."/".$GLOBALS['clouds'][$this->cloudName]['pubDir_fs'];
	//$this->auth = $GLOBALS['clouds'][$this->cloudName]['auth'];
    //    $this->host = $GLOBALS['clouds'][$this->cloudName]['http_host'];
        $this->port = $GLOBALS['clouds'][$this->cloudName]['port'];	
	
	
	$this->host = $http_server;
        $this->port = $port;
	$this->username = $username;
	$this->credentials = $credentials;
        // $this->public_key = file_get_contents($public_key_file);  
        //$this->privateKey = file_get_contents($privateKey);
    	//$this->fileList = $input_files;
        //$this->ssh_session = new SSH2($host, $port);
        //$key = new RSA();
        //$key->loadKey($privateKey);
        //if (!$this->ssh_session->login($username, $key)) {
        //    throw new Exception('Login failed');
	//}

	return $this;
    }

    public function getSession()
    {
        return $this->ssh_session;
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


    public function printList($fileList){
	    foreach ($this->fileList as $file) {
		    echo "cazzz" . $file;
		    var_dump($file);
		    $json = json_decode($file, true);
            	    $file_uri = $json['uri'];
            	    $parts = explode(':', $file_uri,3);
              	    $protocol = $parts[0];
	     	    $location = $parts[1];
	    	    $path= $parts[2];

            	    echo "_id: " . $json['_id'] . "\n";
	    	    echo "protocol: " . $protocol . "\n";
	    	    echo "location: " . $location . "\n"; 
            	    echo "path: " . $path . "\n";
            	    foreach ($json as $key => $value) {
			    if ($key !== '_id' && $key !== 'uri') {
				    echo $key . ": " . $value . "\n";
                }
            }
        }
    }


    public function getUrifrom($obj){
            if(!isset($obj['uri'])) {
                    throw new Exception("URI not found in object.");
            }

            $array = [];
            $array['_id'] = $obj['_id'];
            $parts = explode(":",$obj['uri']);
            //$array['uri'] = $obj['uri'];
            if (count($parts) < 2) {
                    throw new Exception("Invalid string format. Cannot split into protocol, location and path.");
            }
            $array['protocol'] = $parts[0];
            $array['location'] = $parts[1];
            $array['path'] = $parts[2];

            return $array;
    }



    public function execCommand($username, $os_credential, $clone_sh)
    {
	// need to copy the credential file and remove it at the end? or look for already the file if it is there? 
	    // copying the files and running command
	$this->username = $username;
	$this->os_credential = $os_credential;
	$this->clone_sh = $clone_sh;
	$tmp_path= '/home/' . substr($username, 0, 5) . '/' . $username . '/tmp';	///$this->cred_file=end(glob("$os_credential/*.sh"));
 	
	$cred_paths=pathinfo($os_credential);
        $cred_file=$cred_paths['filename'] . '.' . $cred_paths['extension'];

        $clone_paths=pathinfo($clone_sh);
        $clone_rem=$clone_paths['filename'] . '.' . $clone_paths['extension'];

        $remote_credential = "$tmp_path" . '/' . "$cred_file";
	$clone_sh_path="$tmp_path" .'/'. "$clone_rem";

		
	$this->accessDT($username, $tmp_path, $os_credential,$remote_credential, $clone_sh, $clone_sh_path);

	$command = "bash $clone_sh_path $remote_credential";	
	return $this->ssh_session->exec($command);
    }


    public function getCredentials() {
        return $this->credentials;
    }

    public function accessDT($username, $tmp_path, $os_credential, $remote_credential, $clone_sh, $clone_sh_path)
    {
	// copy the content of the credential file and put it in the same file but in remote
	    
	$this->username = $username;
	$this->os_credential = $os_credential;
	$this->remote_credential = $remote_credential;
	$this->tmp_path=$tmp_path;
	$this->clone_sh = $clone_sh;
	$this->clone_sh_path = $clone_sh_path;
	
	// copying files

	echo $tmp_path, "\n";
	$command1='mkdir -p' . ' ' . $tmp_path;
	$command2='echo ' .escapeshellarg(file_get_contents($os_credential)).' > ' .  $remote_credential;
	$command3='echo ' .escapeshellarg(file_get_contents($clone_sh)). ' > ' .  $clone_sh_path;
	$this->ssh_session->exec($command1);
	$this->ssh_session->exec($command2);
	$this->ssh_session->exec($command3);
	
// 	return $this->ssh_session->put($remote_credential,  file_get_contents($os_credential));

    }

	public function copyFileToSSH($localFile, $remoteDir, $credentials, $http_server) {

		// Connect to the remote server
		
		$key = PublicKeyLoader::load($credentials['priv_key']);
		$ssh = new SSH2($http_server, '22');
					
		print ($http_server);	

		if (!$ssh->login($credentials['hpc_username'], $key)) {
			$_SESSION['errorData']['Error'][] = "Failed to connect to the SSH server.";
			return false;    
		}

	    	// Path to the local file
		$localFilePath = $localFile;

	    	// Get the file name from the local path
		$fileName = basename($localFilePath);


		// Check if the remoteDir is existing, otherwise creating it
		$directoryCheck = $ssh->exec('if [ -d "' . $remoteDir . '" ]; then echo "exists"; else echo "not exists"; fi');

		if (trim($directoryCheck) !== 'exists') {
			// If not, create it
			$mkdirResult = $ssh->exec('mkdir -p ' . escapeshellarg($remoteDir));
			
			if (trim($mkdirResult) !== '') {	
            		$_SESSION['errorData']['Error'][] = "Failed to create remote directory.";
            		return false;
			}
		}
		$fileCheck = $ssh->exec('if [ -d "' . $remoteDir . $fileName . '" ]; then echo "exists"; else echo "not exists"; fi');
		// Check if the file already exists on the remote server
		if (trim($fileCheck) !== 'exists') {
			// Uploading the file in the remoteDir
	//		if ($ssh->put($remoteDir . $fileName, $localFilePath)) {
	//			return $remoteDir . $filename;
	//		} else {
	//			$_SESSION['errorData']['Error'][] = "Failed to copy the file to the SSH server.";
	//			return false;
	//		}

		} else {
			$_SESSION['errorData']['Warning'][] = "File '$fileName' already exists in the remote directory.";
			return $remoteDir . $filename;
    		}
				
	}

    public function copyFileToSSH_SFTP($localFile, $remoteDir, $credentials, $http_server) {

	    $key = PublicKeyLoader::load($credentials['priv_key']);
	    $sftp = new SFTP($http_server);

	    if (!$sftp->login($credentials['hpc_username'], $key)) {
                        $_SESSION['errorData']['Error'][] = "Failed to connect to the SSH server.";
                        return false;
	    }

	    // Path to the local file
	    $localFilePath = $localFile;
	    // Get the file name from the local path
    	    $fileName = pathinfo($localFilePath, PATHINFO_BASENAME);
	    // Check if the remote directory exists
	  
	    $remoteFilePath = $remoteDir . "/". $fileName;

	    if (!$sftp->file_exists($remoteDir)) {
		// If not, create it
		    if (!$sftp->mkdir($remoteDir, 0755, true)) 
			    $_SESSION['errorData']['Error'][] = "Failed to create remote directory.";
			    return false;	    
	    }

	    // Check if the file already exists on the remote server
	    
	    if ($sftp->file_exists($remoteFilePath)) {	    
		    $_SESSION['errorData']['Warning'][] = "File '$fileName' already exists in the remote directory.";
		    return $remoteFilePath;
	    } else {
		    $_SESSION['errorData']['Warning'][] = "File '$fileName' copying in the remote directory.";
		    if (file_exists($localFilePath)) {
		    	$localFileContent = file_get_contents($localFilePath);
		    	print ('Okay');
		    	print ($localFilePath);
		    	print ('Contiene');
		    	print ($localFileContent);
		    	if ($sftp->put($remoteFilePath, $localFileContent)) {
				return $remoteFilePath;
			} else {
				$_SESSION['errorData']['Error'][] = "Failed to copy the file to the SSH server.";		
				return false;
			}
		    } else {
			    $_SESSION['errorData']['Error'][] = "Failed to copy the file to the SSH server, not accessible.";
			    return false; 
	
		    } 

	    }

    }



   public function __destruct() {
        // Close SSH session when object is destroyed
        if ($this->ssh_session) {
            $this->ssh_session->disconnect();
            $this->ssh_session = null;
        }
    }

}
