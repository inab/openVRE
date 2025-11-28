<?php


use phpseclib3\Net\SSH2;
use phpseclib3\Crypt\PublicKeyLoader;
use OpenVRE\SSH\RemoteSSH;


class DataTransfer {
    private array $filesId;
    private string $mode; // "async" or "sync"
    private string $toolId;
    private string $inputDirVirtual;
    private string $workingDirPath; 
    private string $execution;
    private string $project;
    private string $description;
    private array $logs = [];
    private array $movedFiles = [];
    private array $siteList;
    private array $arguments_exec;
    private $sshCredentials;

    public function __construct(
        array $filesId,
        string $mode,
        string $toolId,
        string $workingDirPath,
        string $execution = "",
        array $arguments_exec = [],  

    ) {
        $this->filesId = $filesId;
        $this->mode = $mode;
        $this->toolId = $toolId;
        $this->workingDirPath = $workingDirPath;
        $this->execution = $execution;
        $this->arguments_exec = $arguments_exec;   
    }

    /**
     * Calling functions to check the locations of file, and in case copy them to remote system. 
     * Updating also their path in MongoDB.
     *
     * @return boolean
     */
    public function syncFiles(): array {
        // Step 1: Get Data Locations

        $dataLocations = $this->getDataLocation();
        // Step 2: Check if there are no files to transfer
        if ($dataLocations == 0) {
            $_SESSION['errorData']['Info'][] ="No files to transfer.";
            return false; 
        } else {
            $_SESSION['errorData']['Info'][] = "Files are gonna be transferred in remote system.";
            $vaultUrl = $GLOBALS['vaultUrl'];
            $vaultToken = $_SESSION['User']['Vault']['vaultToken'];
            $accessToken = $_SESSION['User']['Token']['access_token'];
            $vaultRolename = $_SESSION['User']['Vault']['vaultRolename'];
            $username = $_SESSION['User']['_id'];
            $sshCredentials = $this->getSSHcredentials($vaultUrl, $vaultToken, $accessToken, $vaultRolename, $username);
            if ($sshCredentials == 0) {
                $_SESSION['errorData']['Error'][] = "Error: Failed to retrieve SSH credentials from Vault.";
                return false;
            }

            // Step 5: Create the rsync command using data locations
            $localDir = preg_replace('#/+#', '/', $this->workingDirPath);
            if (preg_match('#/shared_data/userdata/([^/]+/[^/]+)/#', $localDir, $matches)) {
                $userProjPath = $matches[1]; // Result: PROJECTUSER68245281ad3ee/__PROJ68245281ad3f03.79906233
            } else {
                throw new Exception("Invalid working directory format: $localDir");
            }
            $remoteSSH = new RemoteSSH($sshCredentials);
            list($updatedDataLocations, $syncCommand) = $remoteSSH->prepareSyncCommand($dataLocations, $sshCredentials, $userProjPath);

            if (empty($syncCommand)) {
                $_SESSION['errorData']['Info'][] = "Error: Failed to generate rsync command.";
                return false; 
            }
            foreach ($updatedDataLocations as $file) {
                // Example: Use the updated data locations
                // For instance, logging the updated remote path
                $_SESSION['errorData']['Info'][] = "File {$file['filename']} will be transferred to {$file['remote_path']}";
            }
            // o return de syncommand o añadir al object $dataTransfer
            // ASYNC or SYNC 
            error_log("DEBUG: Updated data locations: " . json_encode($updatedDataLocations));
            // Step 6: Execute the rsync command using SSH credentials        
            $rsyncResult = $remoteSSH->executeRsyncCommand($sshCredentials, $syncCommand, $updatedDataLocations, $userProjPath);

            if ($rsyncResult === true){
                $mongoUpdate = $this->registerMongoTransferredFile($updatedDataLocations);
                if ($mongoUpdate === true) {
                    foreach ($updatedDataLocations as $file) {
                        $_SESSION['errorData']['Info'][] = "File {$file['filename']} new location registered to {$file['remote_path']}/{$file['filename']}";
                        #return true;
                    }
                } else { 
                    $_SESSION['errorData']['Error'][] = "Something went wrong with the MongoUpdate for the file new location.";
                    return [];
                }
            } else {
                $_SESSION['errorData']['Error'][] = "Something went wrong with the Rsync, can't move files to remote location.";
                return [];
            }
        }
        return $updatedDataLocations;     
   
    }


    public function syncWorkingDir(): bool
{
    // Step 1: Define local working directory
    //$localDir = $this->workingDirPath; // e.g., /shared_data/userdata/USER/__PROJxxx/run006
    $localDir = preg_replace('#/+#', '/', $this->workingDirPath); 
    $runId = basename($localDir);   // e.g., run006
    
    if (!is_dir($localDir)) {
        $_SESSION['errorData']['Error'][] = "Local working directory does not exist: $localDir";
        return false;
    }
    //get proj and projuser

    if (preg_match('#/shared_data/userdata/([^/]+/[^/]+)/#', $localDir, $matches)) {
        $userProjPath = $matches[1]; // Result: PROJECTUSER68245281ad3ee/__PROJ68245281ad3f03.79906233
    } else {
        $_SESSION['errorData']['Error'][] = "Invalid working directory format: $localDir";
        throw new Exception();
    }
    $_SESSION['errorData']['Info'][] = "Preparing to sync local dir $localDir to remote MN system...";
    // Step 2: Get SSH credentials from Vault
    $vaultUrl     = $GLOBALS['vaultUrl'];
    $vaultToken   = $_SESSION['User']['Vault']['vaultToken'];
    $accessToken  = $_SESSION['User']['Token']['access_token'];
    $vaultRolename = $_SESSION['User']['Vault']['vaultRolename'];
    $username     = $_SESSION['User']['_id'];
    $sshCredentials = $this->getSSHcredentials($vaultUrl, $vaultToken, $accessToken, $vaultRolename, $username);
    if ($sshCredentials === 0) {
        $_SESSION['errorData']['Error'][] = "Failed to get SSH credentials from Vault.";
        return false;
    }
    $siteList = $this->arguments_exec['site_list'] ?? [];
    if (!is_array($siteList) || empty($siteList)) {
        $_SESSION['errorData']['Error'][] = "No valid site list provided in arguments_exec.";
        return 0;
    }
    // Determine the site (prefer 'local' if present, otherwise use first site in list)
    $site = in_array('local', $siteList, true) ? 'local' : $siteList[0];
    $siteDetails = $this->getSiteDetailsFromMongoDB($site);
        if (!$siteDetails) {
                $_SESSION['errorData']['Info'][] = "Site '{$site}' not found in MongoDB collection!";
            return 0;
        }   
    $rootRemotePath = $siteDetails['root_path'];
    $server =  $siteDetails['server'];
    #username to be changed que ahora se pilla lo del correo? should be PROJECT/__PROJECT/run 
    $remoteUploadPath = $this->constructingDestinationDir_MN($rootRemotePath, $sshCredentials['username']);
    $remoteRunPath = rtrim($remoteUploadPath, "/") . "/$userProjPath" . "/$runId";
    // Step 4: Rsync full working directory to remote
    $remoteSSH = new RemoteSSH($sshCredentials);
    $rsyncSuccess = $remoteSSH->executeRsyncCommandForWorkingDir($sshCredentials, $localDir, $remoteRunPath, $server);
    if ($rsyncSuccess === true) {
        $_SESSION['errorData']['Info'][] = "Successfully synced $runId to remote path: $remoteRunPath";
        return true;
    } else {
        $_SESSION['errorData']['Error'][] = "Failed syncing $runId to remote path: $remoteRunPath";
        return false;
    }
}

    
    /**
     * Get data locations, combining the base directory and file paths.
     *
     * @return array|int Returns an array of paths if conditions are met, otherwise returns 0.
     */
    public function getDataLocation(): array
{
    $dataLocations = [];
    // Assuming you want to use $workingDirPath and $inputDirVirtual to compute the absolute path
    $workingDirPath = $this->workingDirPath ?? '';  // Get the working directory (you might need to pass this from the constructor)
    // Loop through files and resolve their absolute paths
    foreach ($this->filesId as $fileId => $fileData) {
        // Combine baseDir with file's relative path to form the full path
        $fullPath = $this->generateFinalPath($workingDirPath, $fileData['path'] );
        $absolutePath = realpath($fullPath);
        if ($absolutePath === false) {
            $_SESSION['errorData']['Info'][] = "realpath() failed: File does not exist or invalid path.";
            return 0;
        } else {
        }
        // Get the site (using the first element of site_list or 'local')
        $siteList = $this->arguments_exec['site_list'] ?? [];
        if (!is_array($siteList) || empty($siteList)) {
            $_SESSION['errorData']['Error'][] = "No valid site list provided in arguments_exec.";
            return [];
        }
        // Determine the site (prefer 'local' if present, otherwise use first site in list)
        //$site = in_array('local', $siteList, true) ? 'local' : $siteList[0];
        $site = explode('_', (in_array('local', $siteList ?? [], true) ? 'local' : ($siteList[0] ?? 'unknown')), 2)[0];
        $siteDetails = $this->getSiteDetailsFromMongoDB($site);
            if (!$siteDetails) {
                 $_SESSION['errorData']['Info'][] = "Site '{$site}' not found in MongoDB collection!";
                return [];
            }   
        if ($site === 'local') {
            $_SESSION['errorData']['Info'][] = "Skipping file {$fileId} as it is already local.";
            return []; 
        }
        // Append file information to the dataLocations array
        $dataLocations[] = [
            '_id' => $fileId, 
            'filename' => basename($absolutePath), // Extract just the filename from the absolute path
            'site' => $site,
            'absolute_path' => $absolutePath,
            'file_type' => is_dir($absolutePath) ? 'directory' : 'file',
            'site_details' => $siteDetails
        ];
    }
    return $dataLocations;
}


    public function getSSHcredentials($vaultUrl,$vaultToken,$accessToken, $vaultRolename,$username  )
    {
        $vaultClient = new VaultClient($vaultUrl, $accessToken, $vaultRolename, $username);
        $vaultKey = $_SESSION['userVaultInfo']['vaultKey'];
        if (!$vaultKey) {
            $_SESSION['errorData']['Error'][] ="Vault Key is empty, are you sure you saved your credentials?";
            exit;
        }
        $credentials = $vaultClient->retrieveDatafromVault($vaultKey, $vaultUrl, $GLOBALS['secretPath'], $_SESSION['User']['secretsId'], 'SSH');
        if (!$credentials) {
            $_SESSION['errorData']['Error'][] ="Failed to retrieve SSH credentials from Vault, not present.";
            return 0;
        }
        // Extract SSH credentials
        $sshPrivateKey = $credentials['priv_key'];
        $sshPublicKey = $credentials['pub_key'];
        $sshUsername = $credentials['hpc_username'];
        // Store credentials in class properties instead of database
        $this->sshCredentials = [
            'private_key' => $sshPrivateKey,
            'public_key' => $sshPublicKey,
            'username' => $sshUsername
        ];
        return $this->sshCredentials; 
    }


    public function getSiteDetailsFromMongoDB(string $site): array|false {
        $result = $GLOBALS['sitesCol']->findOne(['_id' => $site]);
        if (!$result) {
            return false;
        }
        return [
            'name' => $result['name'] ?? null,
            'server' => $result['launcher']['access_credentials']['server'] ?? null,
            'root_path' => $result['launcher']['access_credentials']['rootpath_default'] ?? null,
            'job_manager' => $result['launcher']['job_manager'] ?? null,
            'container' => $result['launcher']['container'] ?? null
        ];
    }
    

    public function registerMongoTransferredFile($updatedDataLocations): bool
    {
        // Example of logging transfer to MongoDB (actual implementation needed)
        $allFilesProcessed = true;
        foreach ($updatedDataLocations as $file) {
            $fileId = $file['_id'];
            $remotePath = $file['remote_path'] . "/" . $file['filename'];
            //looking for the file in Mongo
            $fileMongo = $GLOBALS['filesCol']->findOne(["_id" => $fileId]);
            if ($fileMongo){
                error_log("File with _id: $fileId found in Mongo");
                $newData = array('$set' => array(
                    'remote_path' => $remotePath,
                ));
                $updatedMongo = $GLOBALS['filesCol']->updateOne(
                    array('_id' => $fileId),
                    $newData
                ); 
                if ($updatedMongo->getModifiedCount() > 0) {
                    error_log("Successfully updated file with _id: $fileId.");
                } else {
                    error_log("No update was made for file with _id: $fileId.");
                    $allFilesProcessed = true;
                }
            } else {
                error_log("File with _id: $fileId not found in MongoDB.");
                $allFilesProcessed = false;
            }
        }
        return $allFilesProcessed;
    }

    private function generateFinalPath($workingDir, $originalPath) {
        // Normalize paths: Remove trailing slashes from the working directory
        $workingDir = rtrim($workingDir, DIRECTORY_SEPARATOR);
        // Ensure originalPath is relative to the base folder (remove extra directories like 'runXXX')
        $originalPath = ltrim($originalPath, DIRECTORY_SEPARATOR);
        // Strip 'runXXX' part from workingDir
        // First, split the workingDir into parts
        $workingDirParts = explode(DIRECTORY_SEPARATOR, $workingDir);
        // Remove the last part which is the 'runXXX' folder
        array_pop($workingDirParts);
        // Rebuild the base directory without the 'runXXX' part
        $baseDirWithoutRun = implode(DIRECTORY_SEPARATOR, $workingDirParts);
        // Combine the cleaned baseDir with the 'uploads' directory and the file name
        $pathParts = explode(DIRECTORY_SEPARATOR, $originalPath);
        // Extract the original file name with its extension
        $pathInfo = pathinfo($originalPath);
        // Get the original file name with its extension (no change to the extension)
        $finalFileName = $pathInfo['basename'];  // Keeps the original file extension
        // Construct the final path without 'runXXX' part, keeping the original extension
        $finalPath = $baseDirWithoutRun . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $finalFileName;
        return $finalPath;
    }

   
    private function constructingDestinationDir_MN ( string $rootPath, string $username, string $filename = '') {

        //Constructing MN Path
        // Taking the numeric part from Username
        $numericPart = substr($username, 3);
        $numericPartWithoutZero = ltrim($numericPart, '0'); // To adjust to old path of MN4 still maintained in MN5
        $dynamicDir1 = substr($numericPartWithoutZero, 0, 2);
        $dynamicDir2 = substr($numericPartWithoutZero, 0, 5);
        // Now construct the full destination path dynamically
        if (empty($filename)) {
            $destinationPath = "{$rootPath}bsc{$dynamicDir1}/MN4/bsc{$dynamicDir1}/bsc{$dynamicDir2}/";
        } else {
            $destinationPath = "{$rootPath}bsc{$dynamicDir1}/MN4/bsc{$dynamicDir1}/bsc{$dynamicDir2}/";
        }
        return $destinationPath;

    }
        
    }


