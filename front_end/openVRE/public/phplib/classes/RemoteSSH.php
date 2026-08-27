<?php

namespace OpenVRE;

use Monolog\Logger;
use phpseclib3\Net\SSH2;
use phpseclib3\Crypt\PublicKeyLoader;
use UnexpectedValueException;

class RemoteSSH
{
    private Logger $logger;
    public $fileList;
    private $ssh_session;

    public function __construct()
    {
        $this->logger = LoggerFactory::getLogger("Remote SSH interface");
        $this->fileList = array();
    }


    public function prepareSyncCommand(array $dataLocations, array $sshCredentials, string $userProjPath): array
    {
        if (empty($dataLocations)) {
            $this->logger->error("prepareSync: No files to transfer.");
            throw new UnexpectedValueException("No files to transfer.");
        }

        if (empty($sshCredentials)) {
            $this->logger->error("prepareSync: No credentials for the transfer.");
            throw new UnexpectedValueException("No credentials for the transfer.");
        }

        $username = $sshCredentials['username'];
        $sshPrivateKey = trim($sshCredentials['private_key']);
        $formattedKey = $this->formatSSHPrivateKey($sshPrivateKey);
        $tempKeyFile = tempnam(sys_get_temp_dir(), 'ssh_key_');
        file_put_contents($tempKeyFile, $formattedKey);
        chmod($tempKeyFile, 0600);
        $commands = [];
        foreach ($dataLocations as &$file) {
            $this->logger->debug("prepareSync - location: " . json_encode($file));
            $server = $file['site_details']['server'];
            $destinationPath = $this->constructingDestination_MN($file['site_details']['root_path'], $username, $userProjPath);
            $file['remote_path'] = $destinationPath;
            $commands[] = "rsync -avz -e 'ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -i $tempKeyFile' --progress {$file['absolute_path']} {$username}@{$server}:{$destinationPath}";
        }

        unset($file);

        return [$dataLocations, implode(" && ", $commands)];
    }


    private function constructingDestination_MN(string $rootPath, string $username, string $userProjPath, string $filename = '')
    {
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


    public function executeRsyncCommand($sshCredentials, $syncCommand, $dataLocations, $userProjPath): void
    {
        // Extract SSH credentials
        $sshPrivateKey = trim($sshCredentials['private_key']);
        $username = $sshCredentials['username'];
        $server = $dataLocations[0]['site_details']['server']; // Assuming all files go to the same server
        $remotePath = $dataLocations[0]['site_details']['root_path'];
        $remoteDir = $this->constructingDestination_MN($remotePath, $username, $userProjPath);

        $this->logger->debug("SSH connecting to $username@$server");

        // Ensure credentials are valid
        if (empty($sshPrivateKey) || empty($username) || empty($server)) {
            $this->logger->error("executeRsync: Missing SSH credentials.");
            throw new UnexpectedValueException("Missing SSH credentials.");
        }

        // Initialize SSH connection
        $ssh = new SSH2($server);
        $ssh->setTimeout(60);
        // Load private key for authentication
        $formattedKey = $this->formatSSHPrivateKey($sshPrivateKey);
        $key = PublicKeyLoader::load($formattedKey);
        // If loading the private key fails
        if (!$key) {
            $_SESSION['errorData']['Error'] = "Error: Failed to load RSA private key.";
            $this->logger->error("executeRsync: Failed to load RSA private key.");
            throw new UnexpectedValueException("Failed to load RSA private key.");
        }

        if (!$ssh->login($username, $key)) {
            $this->logger->error("executeRsync: SSH authentication failed.");
            throw new UnexpectedValueException("SSH authentication failed.");
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
            $this->logger->debug("Executing: $createDirCommand");
            $createStatus = trim($ssh->exec($createDirCommand));

            if (preg_match('/(Created|Failed)/', $createStatus, $matches)) {
                $dirStatusAfter = $matches[1]; // Get the matched word
            } else {
                $dirStatusAfter = "Unknown"; // Handle unexpected output
            }

            $this->logger->debug("Directory Creation Status:" . $dirStatusAfter);

            if (preg_match('/Created/', $dirStatusAfter)) {
                $_SESSION['errorData']['Info'][] = "Mirror Directory for $remotePath created in the system: $server";
                $this->logger->info("Mirror Directory for $remotePath created in the system: $server");
            } else {
                $this->logger->error("Directory creation failed! Check permissions.");
                throw new UnexpectedValueException("Directory creation failed! Check permissions.");
            }
        }

        // Step 3: Execute the rsync command
        $this->logger->debug("Executing: $syncCommand");
        exec($syncCommand, $output, $returnVar);
        if ($returnVar === 0) {
            $this->logger->debug("Rsync command executed successfully: " . implode("\n", $output));
        } else {
            $this->logger->error("Error: Rsync command failed with status code $returnVar. Output: " . implode("\n", $output));
            throw new UnexpectedValueException("Rsync command failed with status code $returnVar. Output: " . implode("\n", $output));
        }
    }


    public function executeRsyncCommandForWorkingDir($sshCredentials, $localDir, $remoteDir, $server, $singularityImage = null,  $mode = "upload"): void
    {
        $sshPrivateKey = trim($sshCredentials['private_key']);
        $username = $sshCredentials['username'];
        if (empty($sshPrivateKey) || empty($username) || empty($server)) {
            $this->logger->error("executeRsyncCommand: Missing SSH credentials.");
            throw new UnexpectedValueException("Missing SSH credentials.");
        }

        $ssh = new SSH2($server);
        $ssh->setTimeout(60);
        $formattedKey = $this->formatSSHPrivateKey($sshPrivateKey);
        $key = PublicKeyLoader::load($formattedKey);
        if (!$key || !$ssh->login($username, $key)) {
            $this->logger->error("executeRsyncCommand: SSH authentication failed.");
            throw new UnexpectedValueException("SSH authentication failed.");
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
                $this->logger->error("Failed to create remote dir: $remoteDir");
                throw new UnexpectedValueException("Failed to create remote dir: $remoteDir");
            }
        }

        // Check for Singularity Image
        if ($mode === "upload") {
            $this->logger->debug("Looking for Singularity image in path: $singularityImage");
            $checkSifCommand = "[ -f \"$singularityImage\" ] && echo 'SIFExists' || echo 'SIFMissing'";
            $sifStatus = trim($ssh->exec($checkSifCommand));
            $sifStatus = preg_match('/(SIFExists|SIFMissing)/', $sifStatus, $matches) ? $matches[1] : "Unknown";
            if ($sifStatus !== "SIFExists") {
                $this->logger->error("Required Singularity image is missing: $singularityImage");
                throw new UnexpectedValueException("Required Singularity image is missing: $singularityImage");
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
        if ($returnVar !== 0) {
            $this->logger->error("Rsync failed: " . implode("\n", $output));
            throw new UnexpectedValueException("Rsync failed: " . implode("\n", $output));
        }
    }


    public static function formatSSHPrivateKey($singleLineKey)
    {
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
}
