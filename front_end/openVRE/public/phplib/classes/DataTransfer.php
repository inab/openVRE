<?php

namespace OpenVRE;

use MongoDB\BSON\UTCDateTime;
use Monolog\Logger;
use UnexpectedValueException;

class DataTransfer
{
    private Logger $logger;
    private array $filesId;
    private string $workingDirPath;
    private array $arguments_exec;
    private $singularityImage = "";

    public function __construct(
        array $filesId,
        array $tool,
        string $workingDirPath,
        array $arguments_exec = []
    ) {
        $this->logger = LoggerFactory::getLogger('Data transfer interface');
        $this->filesId = $filesId;
        $this->workingDirPath = $workingDirPath;
        $this->arguments_exec = $arguments_exec;
        $this->singularityImage = $tool['infrastructure']['singularity_image'];
    }


    public function syncFiles(): array
    {
        // Step 1: Get Data Locations
        $dataLocations = $this->getDataLocation();

        // Step 2: Check if there are no files to transfer
        if (empty($dataLocations)) {
            $this->logger->info("No files to transfer.");
            throw new UnexpectedValueException("No files to transfer.");
        }

        $vaultClient = VaultClientFactory::create();
        $sshCredentials = $vaultClient->retrieveDatafromVault(Site::SSH);

        // Step 5: Create the rsync command using data locations
        $localDir = preg_replace('#/+#', '/', $this->workingDirPath);
        if (preg_match('#/shared_data/userdata/([^/]+/[^/]+)/#', $localDir, $matches)) {
            $userProjPath = '/shared_data/userdata/' . $matches[1];
        } else {
            $this->logger->error("Invalid working directory format: $localDir");
            throw new UnexpectedValueException("Invalid working directory format: $localDir");
        }

        $this->logger->debug("syncFiles - localDir: $localDir");
        $this->logger->debug("syncFiles - workingDirPath: " . $this->workingDirPath);
        $this->logger->debug("syncFiles - userProjPath: $userProjPath");

        $remoteSSH = new RemoteSSH();
        list($updatedDataLocations, $syncCommand) = $remoteSSH->prepareSyncCommand($dataLocations, $sshCredentials, $userProjPath);

        // Step 6: Execute the rsync command using SSH credentials
        $remoteSSH->executeRsyncCommand($sshCredentials, $syncCommand, $updatedDataLocations, $userProjPath);
        $mongoUpdate = $this->registerMongoTransferredFile($updatedDataLocations);
        if (!$mongoUpdate) {
            $this->logger->error("Something went wrong with the MongoUpdate for the file new location.");
            throw new UnexpectedValueException("Something went wrong with the MongoUpdate for the file new location.");
        }

        foreach ($updatedDataLocations as $file) {
            $this->logger->info("File {$file['filename']} new location registered to {$file['remote_path']}/{$file['filename']}");
        }

        $this->logger->info("Rsync command executed successfully.");

        return $updatedDataLocations;
    }


    public function syncWorkingDir(): bool
    {
        $this->logger->debug("syncWorkingDir - starting to sync local dir: " . $this->workingDirPath);

        // Step 1: Define local working directory
        $localDir = preg_replace('#/+#', '/', $this->workingDirPath);
        $runId = basename($localDir);

        if (!is_dir($localDir)) {
            $this->logger->error("Local working directory does not exist: $localDir");
            throw new UnexpectedValueException("Local working directory does not exist: $localDir");
        }

        if (preg_match('#/shared_data/userdata/([^/]+/[^/]+)/#', $localDir, $matches)) {
            $userProjPath = $matches[1];
        } else {
            $this->logger->error("Invalid working directory format: $localDir");
            throw new UnexpectedValueException("Invalid working directory format: $localDir");
        }

        $this->logger->debug("syncWorkingDir - localDir: $localDir");

        // Step 2: Get SSH credentials from Vault
        $vaultClient = VaultClientFactory::create();
        $sshCredentials = $vaultClient->retrieveDatafromVault(Site::SSH);
        $siteList = $this->arguments_exec['site_list'] ?? [];
        if (!is_array($siteList) || empty($siteList)) {
            $this->logger->error("No valid site list provided in arguments_exec.");
            throw new UnexpectedValueException("No valid site list provided in arguments_exec.");
        }

        // Determine the site (prefer 'local' if present, otherwise use first site in list)
        $site = explode('_', (in_array('local', $siteList ?? [], true) ? 'local' : ($siteList[0] ?? 'unknown')), 2)[0];
        $siteDetails = $this->getSiteDetailsFromMongoDB($site);
        $rootRemotePath = $siteDetails['root_path'];
        $server =  $siteDetails['server'];
        $remoteUploadPath = $this->constructingDestinationDir_MN($rootRemotePath, $sshCredentials['username']);
        error_log("DEBUG: syncWorkingDir - remoteUploadPath: $remoteUploadPath");
        $remoteRunPath = rtrim($remoteUploadPath, "/") . "/$userProjPath" . "/$runId";
        // Step 4: Rsync full working directory to remote
        $remoteSSH = new RemoteSSH($sshCredentials);
        $singularityImagePath = ($this->singularityImage !== null) ? $remoteUploadPath . "/../public/" . $this->singularityImage : null;
        $rsyncSuccess = $remoteSSH->executeRsyncCommandForWorkingDir($sshCredentials, $localDir, $remoteRunPath, $server, $singularityImagePath, $mode = "upload");
        if ($rsyncSuccess === true) {
            error_log("DEBUG: syncWorkingDir - successfully synced $runId to remote path: $remoteRunPath");
            return true;
        } else {
            error_log("DEBUG: syncWorkingDir - failed syncing $runId to remote path: $remoteRunPath");
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
        // Loop through files and resolve their absolute paths
        foreach ($this->filesId as $fileId => $fileData) {
            // Combine baseDir with file's relative path to form the full path
            $fullPath = $this->generateFinalPath($fileData['path']);
            $absolutePath = realpath($fullPath);
            if ($absolutePath === false) {
                $this->logger->error("realpath() failed: File does not exist or invalid path.");
                throw new UnexpectedValueException("realpath() failed: File does not exist or invalid path.");
            }

            // Get the site (using the first element of site_list or 'local')
            $siteList = $this->arguments_exec['site_list'] ?? [];
            if (!is_array($siteList) || empty($siteList)) {
                $this->logger->error("No valid site list provided in arguments_exec.");
                throw new UnexpectedValueException("No valid site list provided in arguments_exec.");
            }

            // Determine the site (prefer 'local' if present, otherwise use first site in list)
            $site = explode('_', (in_array('local', $siteList ?? [], true) ? 'local' : ($siteList[0] ?? 'unknown')), 2)[0];
            $siteDetails = $this->getSiteDetailsFromMongoDB($site);

            if ($site === 'local') {
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


    public function getSiteDetailsFromMongoDB(string $site): array
    {
        $result = $GLOBALS['sitesCol']->findOne(['_id' => $site]);
        if (empty($result)) {
            $this->logger->error("Site document not found for site ID: $site");
            throw new UnexpectedValueException("Site document not found for site ID: $site");
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
        $allFilesProcessed = true;
        foreach ($updatedDataLocations as $file) {
            $fileId = $file['_id'];
            $location = $file['site'] ?? null;
            $date = new UTCDateTime();
            $size = file_exists($file['absolute_path']) ? filesize($file['absolute_path']) : null;
            $remotePath = $file['remote_path'] . "/" . $file['filename'];
            $fileMongo = $GLOBALS['filesCol']->findOne(['_id' => $fileId]);

            // If no document exists → create a new one with remote_paths array
            if (empty($fileMongo)) {
                $insertData = [
                    '_id' => $fileId,
                    'remote_paths' => [[
                        'remote_path' => $remotePath,
                        'location'    => $location,
                        'date'        => $date,
                        'size'        => $size
                    ]]
                ];
                $GLOBALS['filesCol']->insertOne($insertData);
                continue;
            }

            // Build new array (always rewritten)
            $newRemotePaths = [];
            $found = false;

            if (isset($fileMongo['remote_paths'])) {
                foreach ($fileMongo['remote_paths'] as $rp) {

                    if ($rp['remote_path'] === $remotePath) {
                        // FOUND: update this entry
                        $rp['location'] = $location;
                        $rp['date']     = $date;
                        $rp['size']     = $size;
                        $found = true;
                    }

                    // Always copy existing entry (updated or not)
                    $newRemotePaths[] = $rp;
                }
            }

            // If NOT found → append NEW entry
            if (!$found) {
                $newRemotePaths[] = [
                    'remote_path' => $remotePath,
                    'location'    => $location,
                    'date'        => $date,
                    'size'        => $size
                ];
            }

            // Now always rewrite the array
            $updateResult = $GLOBALS['filesCol']->updateOne(
                ['_id' => $fileId],
                ['$set' => ['remote_paths' => $newRemotePaths]]
            );

            if ($updateResult->getModifiedCount() == 0) {
                $this->logger->warning("No update performed for _id: $fileId");
                $allFilesProcessed = false;
            }
        }

        return $allFilesProcessed;
    }


    private function generateFinalPath($originalPath)
    {
        // Normalize paths: Remove trailing slashes from the working directory
        $workingDir = rtrim($this->workingDirPath, DIRECTORY_SEPARATOR);
        // Ensure originalPath is relative to the base folder (remove extra directories like 'runXXX')
        $originalPath = ltrim($originalPath, DIRECTORY_SEPARATOR);
        // Strip 'runXXX' part from workingDir
        // First, split the workingDir into parts
        $workingDirParts = explode(DIRECTORY_SEPARATOR, $workingDir);
        // Remove the last part which is the 'runXXX' folder
        array_pop($workingDirParts);
        // Rebuild the base directory without the 'runXXX' part
        $baseDirWithoutRun = implode(DIRECTORY_SEPARATOR, $workingDirParts);
        // Extract the original file name with its extension
        $pathInfo = pathinfo($originalPath);
        // Get the original file name with its extension (no change to the extension)
        $finalFileName = $pathInfo['basename'];  // Keeps the original file extension

        // Construct the final path without 'runXXX' part, keeping the original extension
        return $baseDirWithoutRun . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $finalFileName;
    }


    public static function constructingDestinationDir_MN(string $rootPath, string $username)
    {
        //Constructing MN Path
        // Taking the numeric part from Username
        $numericPart = substr($username, 3);
        $numericPartWithoutZero = ltrim($numericPart, '0'); // To adjust to old path of MN4 still maintained in MN5
        $dynamicDir1 = substr($numericPartWithoutZero, 0, 2);
        $dynamicDir2 = substr($numericPartWithoutZero, 0, 5);

        return "{$rootPath}bsc{$dynamicDir1}/MN4/bsc{$dynamicDir1}/bsc{$dynamicDir2}/shared_data/userdata";
    }


    public static function synchronizeDestinationDir_MN(string $rootPath, string $username)
    {
        //Constructing MN Path
        // Taking the numeric part from Username
        $numericPart = substr($username, 3);
        $numericPartWithoutZero = ltrim($numericPart, '0'); // To adjust to old path of MN4 still maintained in MN5
        $dynamicDir1 = substr($numericPartWithoutZero, 0, 2);
        $dynamicDir2 = substr($numericPartWithoutZero, 0, 5);

        return "{$rootPath}bsc{$dynamicDir1}/MN4/bsc{$dynamicDir1}/bsc{$dynamicDir2}";
    }
}
