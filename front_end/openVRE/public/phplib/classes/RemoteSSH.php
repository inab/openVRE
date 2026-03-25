<?php

namespace OpenVRE\SSH;

use phpseclib3\Net\SSH2;
use phpseclib3\Crypt\PublicKeyLoader;

// Opening the SSH session with MN and access to Swift Object Storage to copy the data
// Inputs: 
//  - Private Key
//  - Username
//  - Application credentials (in a file or not)
//  - Remote path (where to launch command and where to store the data) ? see below
//  - Should we make a new dir for each job launched??
//  Put them all together in a Python file?
//  Put them all together in a YAML file?
//   >> Copy and save credentials in a YAML file?
//   >> Input file would be a file with all the credentials already? Already in PHP
//   >> Use the credentials variable to access to the SSH and save the session
//   >> In the same session, create a tmp folder to store data (remote location specified or random?)
//   >> Copy the data there  --> name of the dir same as job ID? so to keep it consistent
//   >> Exec command
//   >> Copy the result file to DB or back to Swift 
//   >> Remove tmp folder from MN 




class RemoteSSH { 
    //private $ssh;
    private $port;                // standard 22 port for ssh connection
    private $credentials;         //Array with credentials
	public $fileList;
    private $ssh_session;

    public function __construct($credentials,  $port = 22, $http_server = null)
    {
        $this->host = $http_server;
        $this->port = $port;
        //$this->username = $username;
        $this->credentials = $credentials;
		$this->fileList = array(); 
    }

    public function getSession()
    {
        return $this->ssh_session;
    }

    public function getList($input_files){

        $list = array();
        foreach ($input_files as $id => $input_file){
            $f = getGSFile_fromId($input_file);
            $result = $this->getUrifrom($f);
            $list[$id] = $result;
        }
        return $list;

    }

    public function checkLoc($input_files){
        $firstLoc = null;
        foreach ($input_files as $input_file){
            $id = $input_file['_id'];
            $location = $id['location'];
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
            $json = json_decode($file, true);
            $file_uri = $json['uri'];
            $parts = explode(':', $file_uri, 3);
            $protocol = $parts[0];
            $location = $parts[1];
            $path = $parts[2];
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
            throw new \Exception("URI not found in object.");
        }
        $array = array();
        $array['_id'] = $obj['_id'];
        $parts = explode(":",$obj['uri']);
        if (count($parts) < 2) {
            throw new \Exception("Invalid string format. Cannot split into protocol, location and path.");
        }
        $array['protocol'] = $parts[0];
        $array['location'] = $parts[1];
        $array['path'] = $parts[2];
        return $array;
	}

	public function prepareSyncCommand(array $dataLocations, array $sshCredentials, string $userProjPath): array
    {
        if (empty($dataLocations)) {
            $_SESSION['errorData']['Error']="prepareSync: No files to transfer.";
            return "";
        }
        if (empty($sshCredentials)) {
            $_SESSION['errorData']['Error']="prepareSync: No credentials for the transfer.";
            return "";
        }
        $username = $sshCredentials['username'];
        $sshPrivateKey = trim($sshCredentials['private_key']);
        $formattedKey = $this->formatSSHPrivateKey($sshPrivateKey);
        $tempKeyFile = tempnam(sys_get_temp_dir(), 'ssh_key_');
        file_put_contents($tempKeyFile, $formattedKey);
        chmod($tempKeyFile, 0600);
        $commands = [];
        foreach ($dataLocations as &$file) {
            error_log("prepareSync - location: " . json_encode($file));
            $server = $file['site_details']['server'];
            $destinationPath = $this->constructingDestination_MN($file['site_details']['root_path'],$username, $userProjPath); 
            $file['remote_path'] = $destinationPath;
            $commands[] = "rsync -avz -e 'ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -i $tempKeyFile' --progress {$file['absolute_path']} {$username}@{$server}:{$destinationPath}";
        }
        unset($file);
        #return implode(" && ", $commands);
        return [$dataLocations, implode(" && ", $commands)];
    }

	private function constructingDestination_MN( string $rootPath, string $username, string $userProjPath, string $filename = '') {

        //Constructing MN Path
        // Taking the numeric part from Username
        $numericPart = substr($username, 3);
        $numericPartWithoutZero = ltrim($numericPart, '0'); // To adjust to old path of MN4 still maintained in MN5
        $dynamicDir1 = substr($numericPartWithoutZero, 0, 2);
        $dynamicDir2 = substr($numericPartWithoutZero, 0, 5);
        // Now construct the full destination path dynamically
        if (empty($filename)) {
            $destinationPath = "{$rootPath}bsc{$dynamicDir1}/MN4/bsc{$dynamicDir1}/bsc{$dynamicDir2}/{$userProjPath}/uploads";
        } else {
            $destinationPath = "{$rootPath}bsc{$dynamicDir1}/MN4/bsc{$dynamicDir1}/bsc{$dynamicDir2}/{$userProjPath}/uploads/{$filename}";
        }
        return $destinationPath;
    }
   
        /**
     * Execute rsync command using retrieved SSH credentials.
     *
     * @param array $sshCredentials SSH credentials (private key, public key, username)
     * @param string $syncCommand The rsync command to execute
     * @return string The output of the rsync command or an error message
     */

     public function executeRsyncCommand($sshCredentials, $syncCommand, $dataLocations, $userProjPath){
        // Extract SSH credentials
        $sshPrivateKey = trim($sshCredentials['private_key']);
        $username = $sshCredentials['username'];
        $server = $dataLocations[0]['site_details']['server']; // Assuming all files go to the same server
        $remotePath = $dataLocations[0]['site_details']['root_path'];
        $remoteDir = $this->constructingDestination_MN($remotePath, $username, $userProjPath);
        error_log("DEBUG: SSH connecting to $username@$server");

        // Ensure credentials are valid
        if (empty($sshPrivateKey) || empty($username) || empty($server)) {
            $_SESSION['errorData']['Error']="Error executeRsync: Missing SSH credentials.";
        }
        try {
            // Initialize SSH connection
            $ssh = new SSH2($server);
            $ssh->setTimeout(60);
            // Load private key for authentication
            $formattedKey = $this->formatSSHPrivateKey($sshPrivateKey);
            $key = PublicKeyLoader::load($formattedKey);
            // If loading the private key fails
            if (!$key) {
                $_SESSION['errorData']['Error'] = "Error: Failed to load RSA private key.";
                return 0;
            }
            if (!$ssh->login($username, $key)) {
                $_SESSION['errorData']['Error'] = "Error: SSH authentication failed.";
                return 0;
            }
            // Step 1: Check if the remote directory exists
            $checkDirCommand = "[ -d \"$remoteDir\" ] && echo 'Exists' || echo 'NotExists'";
            $dirStatus = trim($ssh->exec($checkDirCommand));
            // Extract 'Exists' or 'NotExists' from the entire output
            if (preg_match('/(Exists|NotExists)/', $dirStatus, $matches)) {
                $dirStatus = $matches[1]; // Get the matched word
            } else {
                $dirStatus = "Unknown"; // Handle unexpected output
            }
            // Step 2: If directory does not exist, create it
            if ($dirStatus === "NotExists") {  
                $createDirCommand = "mkdir -p \"$remoteDir\" && echo 'Created' || echo 'Failed'";
                error_log("Executing: $createDirCommand");
                $createStatus = trim($ssh->exec($createDirCommand));
                

                if (preg_match('/(Created|Failed)/', $createStatus, $matches)) {
                    $dirStatusAfter = $matches[1]; // Get the matched word
                } else {
                    $dirStatusAfter = "Unknown"; // Handle unexpected output
                }
                error_log("Directory Creation Status:" . htmlspecialchars($dirStatusAfter));

                if (preg_match('/Created/', $dirStatusAfter)) {
                    $_SESSION['errorData']['Info'][] = "Mirror Directory for $remotePath created in the system: $server";
                } else {
                    $_SESSION['errorData']['Info'][] = "Directory creation failed! Check permissions.";
                    return 0;
                }
            } else {
                //$_SESSION['errorData']['Info'][] = "Directory already exists in $server, no need to create it.";
            }
            // Step 3: Execute the rsync command
            error_log("Command to be executed: $syncCommand");
            exec($syncCommand, $output, $returnVar);
            if ($returnVar === 0) {
                // Rsync completed successfully
                // You can optionally log the output or return success message
                error_log("Rsync command executed successfully: " . implode("\n", $output));
                return true;
            } else {
                // Rsync encountered an error
                // You can log the error or return an error message
                error_log("Error: Rsync command failed with status code $returnVar. Output: " . implode("\n", $output));
                return false;
            }
        } catch (\Exception $e) {
            return "SSH Error: " . $e->getMessage();
        }
    }

    public function executeRsyncCommandForWorkingDir($sshCredentials, $localDir, $remoteDir, $server, $singularityImage = null,  $mode = "upload" )
    {
        $sshPrivateKey = trim($sshCredentials['private_key']);
        $username = $sshCredentials['username'];
        if (empty($sshPrivateKey) || empty($username) || empty($server)) {
            $_SESSION['errorData']['Error'][] = "executeRsyncCommand: Missing SSH credentials.";
            return false;
        }
        try {
            $ssh = new SSH2($server);
            $ssh->setTimeout(60);
            $formattedKey = $this->formatSSHPrivateKey($sshPrivateKey);
            $key = PublicKeyLoader::load($formattedKey);
            if (!$key || !$ssh->login($username, $key)) {
                $_SESSION['errorData']['Error'][] = "SSH authentication failed.";
                return false;
            }
            // Check and create remote dir
            $checkDirCommand = "[ -d \"$remoteDir\" ] && echo 'Exists' || echo 'NotExists'";
            $dirStatus = trim($ssh->exec($checkDirCommand));
            $dirStatus = preg_match('/(Exists|NotExists)/', $dirStatus, $matches) ? $matches[1] : "Unknown";
            if ($dirStatus === "NotExists") {
                $createDirCommand = "mkdir -p \"$remoteDir\" && echo 'Created' || echo 'Failed'";
                $createStatus = trim($ssh->exec($createDirCommand));
                $createStatus = preg_match('/(Created|Failed)/', $createStatus, $matches) ? $matches[1] : "Unknown";
                if ($createStatus !== "Created") {
                    $_SESSION['errorData']['Error'][] = "Failed to create remote dir: $remoteDir";
                    return false;
                }
            }
            // Check for Singularity Image
            if ($mode === "upload")  {
                error_log("Looking for Singularity image in path: $singularityImage");
                $checkSifCommand = "[ -f \"$singularityImage\" ] && echo 'SIFExists' || echo 'SIFMissing'";
                $sifStatus = trim($ssh->exec($checkSifCommand));
                $sifStatus = preg_match('/(SIFExists|SIFMissing)/', $sifStatus, $matches) ? $matches[1] : "Unknown";
                if ($sifStatus !== "SIFExists") {
                    $_SESSION['errorData']['Error'][] = "Required Singularity image is missing: $singularityImage";
                    return false;
                }
            }
            // Perform rsync
            $tempKeyFile = tempnam(sys_get_temp_dir(), 'ssh_key_');
            file_put_contents($tempKeyFile, $formattedKey);
            chmod($tempKeyFile, 0600);
            if ($mode === "upload") {
                $rsyncCommand = "rsync -avz -e 'ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -i $tempKeyFile' $localDir/ $username@$server:$remoteDir/";
            } else {
                $rsyncCommand =
                "rsync -avz -e 'ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -i $tempKeyFile' " .
                "$username@$server:$remoteDir/ $localDir/";
            }
           
            exec($rsyncCommand, $output, $returnVar);
            if ($returnVar === 0) {
                error_log("Rsync success: " . implode("\n", $output));
                return true;
            } else {
                error_log("Rsync failed: " . implode("\n", $output));
                return false;
            }
        } catch (\Exception $e) {
            $_SESSION['errorData']['Error'][] = "SSH Exception: " . $e->getMessage();
            return false;
        }
    }

    public static function formatSSHPrivateKey($singleLineKey) {
        // Function to insert newlines every 64 characters in the key
         // First, ensure the key content is well-formatted by removing existing newlines or spaces
        // Extract the BEGIN and END markers
        $start = '-----BEGIN OPENSSH PRIVATE KEY-----';
        $end = '-----END OPENSSH PRIVATE KEY-----';
        // Check if key contains the BEGIN and END markers
        if (strpos($singleLineKey, $start) === false || strpos($singleLineKey, $end) === false) {
            throw new \Exception("Invalid SSH key format: missing BEGIN or END markers.");
        }
        // Extract the key body (between BEGIN and END)
        $keyBody = str_replace(array($start, $end), "", $singleLineKey);
        // Remove any spaces or newlines (in case they were added within the key body)
        $keyBody = str_replace(array("\n", "\r", " "), "", $keyBody);
        // Break the key body into chunks of 64 characters
        $formattedKeyBody = chunk_split($keyBody, 64, "\n");
        // Format the key with the markers and properly chunked key body
        $formattedKey = $start . "\n" . $formattedKeyBody . $end . "\n";
        return $formattedKey;
    }


    public function __destruct() {
        // Close SSH session when object is destroyed
        if ($this->ssh_session) {
            $this->ssh_session->disconnect();
            $this->ssh_session = null;
        }
    }

}