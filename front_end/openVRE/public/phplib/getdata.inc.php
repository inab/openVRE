<?php

/////////////////////////////////
/////// FROM LOCAL
/////////////////////////////////

// upload file from local
function getData_fromLocal()
{
    // set destination working_directory/uploads
    $dataDirPath = getAttr_fromGSFileId($_SESSION['User']['dataDir'], "path");
    $localWorkingDir = "$dataDirPath/uploads";
    $workingDir = $GLOBALS['dataDir'] . "/" . $localWorkingDir;
    $workingDirId = getGSFileId_fromPath($localWorkingDir);

    // check source file/s
    if (empty($_FILES)) {
        $_SESSION['errorData']['upload'][] = "ERROR: Receiving blank. Please select a file to upload";
        die("ERROR: Recieving blank. Please select a file to upload0");
    }

    // check target directory
    if ($workingDirId == "0" || !is_dir($workingDir)) {
        $_SESSION['errorData']['upload'][] = "Target server directory '" . basename($localWorkingDir) . "' does not exist. Please, login again.";
        die("Target server directory '" . basename($localWorkingDir) . "' does not exist. Please, login again.0");
    }

    $fileIds = [];
    // upload each source file	
    $errorCode = $_FILES['file']['error'];
    if ($errorCode) {
        $errMsg = [
            0 => "[UPLOAD_ERR_OK]:  There is no error, the file uploaded with success",
            1 => "[UPLOAD_ERR_INI_SIZE]: The uploaded file exceeds the upload_max_filesize directive in php.ini",
            2 => "[UPLOAD_ERR_FORM_SIZE]: The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form",
            3 => "[UPLOAD_ERR_PARTIAL]: The uploaded file was only partially uploaded",
            4 => "[UPLOAD_ERR_NO_FILE]: No file was uploaded",
            6 => "[UPLOAD_ERR_NO_TMP_DIR]: Missing a temporary folder",
            7 => "[UPLOAD_ERR_CANT_WRITE]: Failed to write file to disk",
            8 => "[UPLOAD_ERR_EXTENSION]: File upload stopped by extension"
        ];

        if (isset($errMsg[$errorCode])) {
            $_SESSION['errorData']['upload'][] = "ERROR [code $errorCode] " . $errMsg[$errorCode];
            die("ERROR [code $errorCode] " . $errMsg[$errorCode] . "0");
        }

        $_SESSION['errorData']['upload'][] = "Unknown upload error";
        die("Unknown upload error 0");
    }

    $size = $_FILES['file']['size'];
    if (!$size || $size == 0) {
        $_SESSION['errorData']['upload'][] = "ERROR: " . $_FILES['file']['name'] . " file size is zero";
        die("ERROR: " . $_FILES['file']['name'] . " file size is zero 0");
    }

    if ($size > return_bytes(ini_get('upload_max_filesize')) || $size > return_bytes(ini_get('post_max_size'))) {
        $_SESSION['errorData']['upload'][] = "ERROR: File size $size larger than UPLOAD_MAX_FILESIZE (" . ini_get('upload_max_filesize') . ") ";
        die("ERROR: File size $size larger than UPLOAD_MAX_FILESIZE (" . ini_get('upload_max_filesize') . ") 0");
    }

    $usedDisk = (int) getUsedDiskSpace();
    $diskLimit = (int) $_SESSION['User']['diskQuota'];
    if ($size > ($diskLimit - $usedDisk)) {
        $_SESSION['errorData']['upload'][] = "ERROR: Cannot upload file. Not enough space left in the workspace";
        die("ERROR: Cannot upload file. Not enough space left in the workspace");
    }

    $filePath = "$workingDir/" . cleanName($_FILES['file']['name']);
    //do not overwrite, rename
    if (is_file($filePath)) {
        foreach (range(1, 99) as $N) { // TODO: should be changed
            if ($pos = strrpos($filePath, '.')) {
                $name = substr($filePath, 0, $pos);
                $extension = substr($filePath, $pos);
            } else {
                $name = $filePath;
            }

            $tmpFilePath = $name . '_' . $N . $extension;
            if (!is_file($tmpFilePath)) {
                $filePath = $tmpFilePath;
                break;
            }
        }
    }

    //actual upload
    if ($_FILES['file']['tmp_name']) {
        if (!move_uploaded_file($_FILES['file']['tmp_name'], $filePath)) {
            $_SESSION['errorData']['upload'] = "Error occurred while moving the uploaded file";
            die("Error occurred while moving the uploaded file");
        };
    }

    if (!is_file($filePath)) {
        $_SESSION['errorData']['upload'][] = "Uploaded file not correctly stored";
        die("Uploaded file not correctly stored");
    }

    chmod($filePath, 0666);
    $fileBasename = basename($filePath);
    $insertData = [
        'owner' => $_SESSION['User']['id'],
        'size'  => filesize($filePath),
        'mtime' => new MongoDB\BSON\UTCDateTime(filemtime($filePath) * 1000)
    ];

    $metaData = [
        'validated' => FALSE
    ];

    $fileId = uploadGSFileBNS("$localWorkingDir/$fileBasename", $filePath, $insertData, $metaData, FALSE);
    if ($fileId == "0") {
        $_SESSION['errorData']['upload'] = "Error occurred while registering the uploaded file";
        die("Error occurred while registering the uploaded file");
    }

    array_push($fileIds, $fileId);

    print implode(",", $fileIds);
}

/////////////////////////////////
/////// FROM URL or ID
/////////////////////////////////


// upload file from URL via CURL
function getData_fromUrl($url, $meta = null)
{
    [$toolArgs, $toolOuts, $output_dir] = prepare_getData_fromURL($url, "uploads", $GLOBALS['BASEURL'] . "/getdata/uploadForm.php#load_from_url", $meta);
    getData_wget_asyncron($toolArgs, $toolOuts, $output_dir, $GLOBALS['BASEURL'] . "/getdata/uploadForm.php#load_from_url");
}

// prepare target directory and file metadata

function prepare_getData_fromURL($url, $outdir, $referer, $meta = [])
{
    //parse out username and password from URL, if any
    $user = 0;
    $pass = 0;
    $url_withCredentials = 0;
    if (preg_match('/(.*\/\/)(.*):(.*)@(.*)/', $url, $matches)) {
        $user = $matches[2];
        $pass = $matches[3];
        $url_withCredentials = $matches[1] . urlencode($user) . ":" . urlencode($pass) . "@" . $matches[4];
        $url  = $matches[1] . $matches[4];
    }

    //validate URL: get status and size and filename
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, TRUE);
    curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
    curl_setopt($ch, CURLOPT_NOBODY, TRUE);
    if ($user && $pass) {
        curl_setopt($ch, CURLOPT_USERPWD, "$user:$pass");
    }

    $curl_data = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($status != 200 && !preg_match('/^3/', $status)) {
        $msg = "Resource URL '$url' is not valid or unaccessible. Status: $status";
        if ($referer == "die") {
            die($msg);
        }

        $_SESSION['errorData']['Error'][] = $msg;
        redirect($referer);
    }

    $filename = preg_match('/^Content-Disposition: .*?filename=(?<f>[^\s]+|\x22[^\x22]+\x22)\x3B?.*$/m', $curl_data, $matches)
        ? trim($matches['f'], ' ";')
        : basename($url);

    if (!$filename) {
        $msg = "Resource URL ('$url') has not a valid HTTP header. Filename not found";
        if ($referer == "die") {
            die($msg);
        }

        $_SESSION['errorData']['Error'][] = $msg;
        redirect($referer);
    }

    $size = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
    $usedDisk = (int) getUsedDiskSpace();
    $diskLimit = (int) $_SESSION['User']['diskQuota'];
    if ($size == 0) {
        $msg = "Resource URL ('$url') is pointing to an empty resource (size = 0)";
        if ($referer == "die") {
            die($msg);
        }

        $_SESSION['errorData']['Error'][] = $msg;
        redirect($referer);
    }

    if ($size > ($diskLimit - $usedDisk)) {
        $msg = "Cannot import file. There will be not enough space left in the workspace (size = " . getSize($size) . ")";
        if ($referer == "die") {
            $_SESSION['errorData']['Error'][] = $msg;
            redirect($GLOBALS['BASEURL'] . "workspace/");
        }

        $_SESSION['errorData']['Error'][] = $msg;
        redirect($referer);
    }

    curl_close($ch);
    // setting output directory
    $dataDirPath = getAttr_fromGSFileId($_SESSION['User']['dataDir'], "path");
    $localWorkingDir = "{$dataDirPath}/{$outdir}";
    $workingDir = $GLOBALS['dataDir'] . "/" . $localWorkingDir;
    $workingDirId = getGSFileId_fromPath($localWorkingDir);

    if ($workingDirId == "0") {
        //creating repository directory. Old users dont have it
        $workingDirId  = createGSDirBNS($localWorkingDir, 1);
        $_SESSION['errorData']['Info'][] = "Creating '$outdir' directory: $localWorkingDir ($workingDirId)";
        if ($workingDirId == "0") {
            $msg = "Cannot create repository directory in $dataDirPath";
            if ($referer == "die") {
                die($msg);
            }

            $_SESSION['errorData']['Error'][] = $msg;
            redirect($referer);
        }

        $IsMetadataAdded = addMetadataBNS($workingDirId, [
            "expiration" => -1,
            "description" => "Remote personal data"
        ]);
        if ($IsMetadataAdded == "0") {
            $msg = "Cannot set '$outdir' directory $localWorkingDir";
            if ($referer == "die") {
                die($msg);
            }

            $_SESSION['errorData']['Error'][] = $msg;
            redirect($referer);
        }

        if (!is_dir($workingDir)) {
            mkdir($workingDir, 0775);
        }
    }

    if (!is_dir($workingDir)) {
        $msg = "Target server directory '$localWorkingDir' is not a directory. Your user account is corrupted. Please, report to <a href=\"mailto:helpdesk@multiscalegenomics.eu\">helpdesk@multiscalegenomics.eu</a>";
        if ($referer == "die") {
            die($msg);
        }

        $_SESSION['errorData']['Error'][] = $msg;
        redirect($referer);
    }

    // Check file already registered
    $filePath = "$workingDir/$filename";
    $filePathLocal = "$localWorkingDir/$filename";
    $fileId = getGSFileId_fromPath($filePathLocal);
    if ($fileId) {
        $_SESSION['errorData']['Error'][] = "Resource file ('" . $url . "') is already available in the workspace: $filePath";
        redirect("../getdata/editFile.php?fn[]=$fileId");
    }
    //output_dir will be where fn is expected to be created
    $output_dir = $workingDir;

    // working_dir will be set in user temporal dir. Checking it
    // TODO Or NO! maybe we decide to run directly on uploads/
    $dirTmp = $GLOBALS['dataDir'] . "/" . $dataDirPath . "/" . $GLOBALS['tmpUser_dir'];
    if (!is_dir($dirTmp) && !mkdir($dirTmp, 0775, true)) {
        $_SESSION['errorData']['error'][] = "Cannot create temporal file '$dirTmp'.Please, try it later.";
    }

    // setting tool	arguments
    $toolArgs  = [
        "url"    => $toolArgs["url"] = $url_withCredentials ?: $url,
        "output" => $filePath
    ];           // Tool is responsible to create outputs in the output_dir

    // setting tool outputs -- metadata to save in DMP during tool output_file registration
    $descrip = "File imported from URL '$url'";
    $taxon = $meta['taxon'] ?? "";
    [$fileExtension, $compressed, $fileBaseName] = getFileExtension($filePath);
    $filetypes = getFileTypeFromExtension($fileExtension);
    $filetype = array_keys($filetypes)[0] ?? "";
    $fileOut = [
        "name" => "file",
        "file_path" => $filePath,
        "data_type" => "",
        "file_type" => $filetype,
        "sources" => [0],
        "taxon_id" => $taxon,
        "meta_data" => [
            "validated" => false,
            "compressed" => $compressed,
            "description" => $descrip
        ]
    ];

    $toolOuts = ["output_files" => [$fileOut]];
    return [$toolArgs, $toolOuts, $output_dir];
}


//function getData_wget($url,$outdir,$referer,$meta=array()) {
function  getData_wget_asyncron($toolArgs, $toolOuts, $output_dir, $referer)
{
    $toolId = "wget";
    $toolInputs = [];
    $filePath = $toolOuts['output_files'][0]["file_path"];
    $logName = basename($filePath) . ".log";

    //TODO: FIXME START - This is a temporal fix. In future, files should not be downloaded, only registered
    $pid = launchToolInternal($toolId, $toolInputs, $toolArgs, $toolOuts, $output_dir, $logName);
    $outdir = basename($output_dir);

    if ($pid == 0) {
        $msg = "File imported from URL '" . basename($filePath) . "' cannot be imported. Error occurred while preparing the job 'Get remote file'";
        if ($referer == "die") {
            die($msg);
        }

        $_SESSION['errorData']['Error'][] = $msg;
        redirect($referer);
    }

    $_SESSION['errorData']['Info'][] = "File from URL '" . basename($filePath) . "' is being imported into the '$outdir' folder below. Please, edit its metadata once the import has finished";
    redirect($GLOBALS['BASEURL'] . "/workspace/");
}

/////////////////////////////////
/////// BUILD FILE TEXT
/////////////////////////////////
function getData_fromTXT()
{
    $filename = $_REQUEST['filename'];
    $data = $_REQUEST['txtdata'];

    // getting working directory
    $dataDirPath = getAttr_fromGSFileId($_SESSION['User']['dataDir'], "path");
    $localWorkingDir = $dataDirPath . "/uploads";

    $workingDir  = $GLOBALS['dataDir'] . "/" . $localWorkingDir;
    $workingDirId = getGSFileId_fromPath($localWorkingDir);

    // check target directory
    if ($workingDirId == "0" || !is_dir($workingDir)) {
        die("ERROR: Target server directory '" . basename($localWorkingDir) . "' does not exist. Please, login again.");
    }

    $filePath = "$workingDir/" . cleanName($filename);
    $size = strlen($data);

    if ($size == 0) {
        die("ERROR: " . $filename . " file size is zero");
    }

    $usedDisk = (int) getUsedDiskSpace();
    $diskLimit = (int) $_SESSION['User']['diskQuota'];
    if ($size > ($diskLimit - $usedDisk)) {
        die("ERROR: Cannot upload file. Not enough space left in the workspace");
    }

    if (is_file($filePath)) {
        foreach (range(1, 99) as $suffixNumber) { // TODO: should be changed to a better solution
            if ($pos = strrpos($filePath, '.')) {
                $name = substr($filePath, 0, $pos);
                $ext = substr($filePath, $pos);
            } else {
                $name = $filePath;
            }

            $tmpFilename = $name . '_' . $suffixNumber . $ext;
            if (!is_file($tmpFilename)) {
                $filePath = $tmpFilename;
                break;
            }
        }
    }

    $file = fopen($filePath, "w+");
    fputs($file, $data);
    fclose($file);

    if (!is_file($filePath)) {
        die("ERROR: Uploaded file not correctly stored.");
    }

    chmod($filePath, 0666);
    $fileBasename = basename($filePath);
    $insertData = [
        'owner' => $_SESSION['User']['id'],
        'size'  => filesize($filePath),
        'mtime' => new MongoDB\BSON\UTCDateTime(filemtime($filePath) * 1000)
    ];

    $metaData = [
        'validated' => FALSE
    ];

    $fileId = uploadGSFileBNS("$localWorkingDir/$fileBasename", $filePath, $insertData, $metaData, FALSE);
    if ($fileId == "0") {
        unlink($filePath);
        die("ERROR: Error occurred while registering the uploaded file.");
    }

    echo $fileId;
}

/////////////////////////////////
/////    DATA FROM REPOSITORY 
/////////////////////////////////

// ONGOING: import data from Resository to Public dir.
// Step 0: if TAR is given, files are unbundled and a directory is registered

function getData_fromRepository_ToPublic($params = array())
{ //url, untar, data_type, file_type, other metadata

    // Get params
    $url      = $params['url'];
    $extract_uncompress = (isset($params['extract_uncompress']) ? $params['extract_uncompress'] : false); // true, false

    $datatype = (isset($params['data_type']) && $params['data_type'] ? $params['data_type'] : "");
    $filetype = (isset($params['file_type']) && $params['file_type'] ? $params['file_type'] : "");
    $descrip  = (isset($params['description']) && $params['description'] ? $params['description'] : "");

    // Get URL headers

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, TRUE);
    $curl_data = curl_exec($ch);

    // Validate HTTP status

    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $url_effective = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

    if ($status != 200 && !preg_match('/^3/', $status)) {
        $_SESSION['errorData']['Error'][] = "Resource URL ('$url') is not valid or unaccessible. Status: $status";
        redirect($_SERVER['HTTP_REFERER']);
    }
    // Get filename from header

    if (! $filename) {
        if (preg_match('/^Content-Disposition: .*?filename=(?<f>[^\s]+|\x22[^\x22]+\x22)\x3B?.*$/m', $curl_data, $m)) {
            $filename = trim($m['f'], ' ";');
        } elseif (preg_match('/^Content-Length:\s*(\d+)/m', $curl_data, $m)) {
            $hasLength = $m[1];
            if ($hasLength) {
                $filename = basename($url);
            }
        }
    }
    if (!$filename) {
        $_SESSION['errorData']['Error'][] = "Resource URL ('$url') is not pointing to a valid filename";
        redirect($_SERVER['HTTP_REFERER']);
    }

    // Check size and available space

    $size   = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
    $usedDisk     = (int)getUsedDiskSpace();
    $diskLimit    = (int)$_SESSION['User']['diskQuota'];
    if ($size == 0) {
        $_SESSION['errorData']['Error'][] = "Resource URL ('$url') is pointing to an empty resource (size = 0)";
        redirect($_SERVER['HTTP_REFERER']);
    }
    if ($size > ($diskLimit - $usedDisk)) {
        $_SESSION['errorData']['Error'][] = "Cannot import file. There will be not enough space left in the workspace (size = $size)";
        redirect($_SERVER['HTTP_REFERER']);
    }
    curl_close($ch);

    // Check repository from workspace

    $dataDirPath = getAttr_fromGSFileId($_SESSION['User']['dataDir'], "path");
    $wd          = $dataDirPath . "/repository";
    $wdP         = $GLOBALS['dataDir'] . "/" . $wd;
    $wdId        = getGSFileId_fromPath($wd);

    if ($wdId == "0" || !is_dir($wdP)) {
        $_SESSION['errorData']['Error'][] = "Target server directory '$wd' is not a directory. Your user account is corrupted. Please, report to <a href=\"mailto:helpdesk@multiscalegenomics.eu\">helpdesk@multiscalegenomics.eu</a>";
        redirect($_SERVER['HTTP_REFERER']);
    }

    // Set output file/folder

    list($fileExtension, $compressExtension, $fileBaseName) = getFileExtension($filename);
    $compression = ($compressExtension && isset($GLOBALS['compressions'][$compressExtension]) ? $GLOBALS['compressions'][$compressExtension] : 0);
    $output = ($compression && $extract_uncompress && preg_match('/TAR/', $compression) ? hash('md5', $url, false) : $filename);

    print "($fileExtension,$compressExtension,$fileBaseName) = getFileExtension($filename)\n<br/>";
    print "output  (file or folder) = $output\n";

    // Check output file/folder already registered

    $fnP  = "$wdP/$output";
    $fn   = "$wd/$output";
    $fnId = getGSFileId_fromPath($fn);

    if ($fnId) {
        // file already here
        $_SESSION['errorData']['Error'][] = "Resource file ('$url') is already available in the workspace: $fnP";
        redirect("../getdata/editFile.php?fn[]=$fnId");

        //Do asyncronous download file (internal tool wget)

    } else {

        //FIXME START - This is a temporal fix. In future, files should not be downloaded, only registered

        //output_dir will be where fn is expeted to be created: repository
        $output_dir = $wdP;

        // working_dir will be set in user temporal dir. Checking it
        $dirTmp = $GLOBALS['dataDir'] . "/" . $dataDirPath . "/" . $GLOBALS['tmpUser_dir'];
        if (! is_dir($dirTmp)) {
            if (!mkdir($dirTmp, 0775, true)) {
                $_SESSION['errorData']['error'][] = "Cannot create temporal file $dirTmp . Please, try it later.";
                $resp['state'] = 0;
                #break;
            }
        }

        // choosing interanl tool 
        $toolId = "wget";

        // setting tool	inputs
        $toolInputs = array();

        // setting tool	arguments. Tool is responsible to create outputs in the output_dir
        $toolArgs  = array(
            "url"    => $url,
            "output" => $fnP
        );
        if ($compression and $extract_uncompress) {
            $compressors = explode(",", $compression);
            foreach ($compressors as $c) {
                if ($c == "TAR") {
                    $toolArgs['archiver']  = $c;
                    continue;
                } else {
                    $toolArgs['compressor']  = $c;
                    continue;
                }
            }
            //			$toolArgs['uncompress_cmd'] =  " tar -xz -C ";
            //			$toolArgs['uncompress_cmd'] =  " tar -xj ";
            //			$toolArgs['uncompress_cmd'] =  " gunzip -c ";
            //			$toolArgs['uncompress_cmd'] =  " bzip2 ";
        }

        // setting tool output metadata. It will be registerd after tool execution
        if (!$descrip) {
            $descrip = "Remote file extracted from <a target='_blank' href=\"$url\">$url</a>";
        }
        if (!$filetype) {
            $filetypes = getFileTypeFromExtension($fileExtension);
            $filetype = (isset(array_keys($filetypes)[0]) ? array_keys($filetypes)[0] : "");
        }
        $validated = ($filetype != "" && $datatype != "" ? true : false); // Can lead to problems

        $fileOut_type = ($compression && $extract_uncompress && preg_match('/TAR/', $compression) ? "dir" : "file");

        $fileOut = array(
            "name"       => "file",
            "type"       => $fileOut_type,
            "file_path"  => $fnP,
            "data_type"  => $datatype,
            "file_type"  => $filetype,
            "sources"    => [0],
            "source_url" => $url,
            "meta_data"  => array(
                "validated"   => $validated,
                "compressed"  => $compressed,
                "description" => $descrip,
            )
        );
        if (isset($params['oeb_dataset_id'])) {
            $fileOut['meta_data']["oeb_dataset_id"] = $params['oeb_dataset_id'];
        }
        if (isset($params['oeb_community_ids'])) {
            $fileOut['meta_data']["oeb_community_ids"] = $params['oeb_community_ids'];
        }
        $toolOuts = array("output_files" => array($fileOut));

        // setting logName
        $logName = basename($fnP) . ".log";

        //calling internal tool
        $pid = launchToolInternal($toolId, $toolInputs, $toolArgs, $toolOuts, $output_dir, $logName);

        if ($pid == 0) {
            $_SESSION['errorData']['Error'][] = "Resource file '" . basename($fnP) . "' cannot be imported. Error occurred while preparing the job 'Get remote file'";
            redirect($_SERVER['HTTP_REFERER']);
        } else {
            $_SESSION['errorData']['Info'][] = "Remote file '" . basename($fnP) . "' imported into the 'repository' folder below. Please, edit its metadata once the job has finished";
            redirect($GLOBALS['BASEURL'] . "workspace/");
        }
    }
}


function process_URL($url)
{
    $response = [
        "status"        => false,
        "size"          => 0,
        "filename"      => false,
        "effective_url" => false
    ];

    $headers_data = get_headers($url, 1);
    if ($headers_data === false) {
        $_SESSION['errorData']['Error'][] = "Resource URL ('$url') is not valid or unaccessible. Server not found";
        return false;
    }

    // corrects url when 301/302 redirect(s) lead(s) to 200
    $response['effective_url'] = isset($headers_data['Location']) && preg_match("/^Location: (.+)$/", $headers_data['Location'], $matches)
        ? $matches[1]
        : $url;

    // grabs last code, in case of redirect(s):
    $response['status'] = preg_match("/^HTTP.* (\d\d\d) /", $headers_data[0], $matches)
        ? $matches[1]
        : $response['status'];

    // grabs filename
    $response['filename'] = isset($headers_data['Content-Disposition']) && preg_match('/filename=(?<f>[^\s]+|\x22[^\x22]+\x22)\x3B?.*$/m', $headers_data['Content-Disposition'], $matches)
        ? $matches[1]
        : $response['filename'];

    $response['size'] = isset($headers_data['Content-Disposition']) && preg_match("/filename=.+/", $headers_data['Content-Disposition']) && $headers_data['Content-Length']
        ? $headers_data['Content-Length']
        : $response['size'];

    $status = substr($headers_data[0], 9, 3);
    if (!preg_match('/(200)/', $headers_data[0]) && !preg_match('/^3/', $status)) {
        $_SESSION['errorData']['Error'][] = "Resource URL ('$url') is not valid or unaccessible. Status: $status";
        redirect($_SERVER['HTTP_REFERER']);
    }

    return $response;
}

// import from Repository (URL) to user workspace
function getData_fromRepository($url, $datatype, $filetype, $description, $oeb_dataset_id, $oeb_community_ids)
{
    $url_data = process_URL($url);
    $status = $url_data['status'];
    if ($status != 200 && !preg_match('/^3/', $status)) {
        $_SESSION['errorData']['Error'][] = "Resource URL ('$url') is not valid or unaccessible. Status: $status";
        redirect($_SERVER['HTTP_REFERER']);
    }

    $filename = $url_data['filename'];
    if (!$filename) {
        $_SESSION['errorData']['Error'][] = "Resource URL ('$url') is not pointing to a valid filename";
        redirect($_SERVER['HTTP_REFERER']);
    }

    $size = (int) $url_data['size'];
    $usedDisk = (int) getUsedDiskSpace();
    $diskLimit = (int) $_SESSION['User']['diskQuota'];
    if ($size == 0) {
        $_SESSION['errorData']['Error'][] = "Resource URL ('$url') is pointing to an empty resource (size = 0)";
        redirect($_SERVER['HTTP_REFERER']);
    }

    if ($size > ($diskLimit - $usedDisk)) {
        $_SESSION['errorData']['Error'][] = "Cannot import file. There will be not enough space left in the workspace (size = $size)";
        redirect($_SERVER['HTTP_REFERER']);
    }

    // setting repository directory
    $dataDirPath = getAttr_fromGSFileId($_SESSION['User']['dataDir'], "path");
    $localWorkingDir = "$dataDirPath/repository";
    $workingDir = $GLOBALS['dataDir'] . "/" . $localWorkingDir;
    $workingDirId = getGSFileId_fromPath($localWorkingDir);

    if ($workingDirId == "0") {
        //creating repository directory. Old users dont have it
        $workingDirId  = createGSDirBNS($localWorkingDir, 1);
        $_SESSION['errorData']['Info'][] = "Creating repository directory: $localWorkingDir ($workingDirId)";

        if ($workingDirId == "0") {
            $_SESSION['errorData']['Internal error'][] = "Cannot create repository directory in $dataDirPath";
            redirect($_SERVER['HTTP_REFERER']);
        }

        $addedMetadata = addMetadataBNS($workingDirId, [
            "expiration" => -1,
            "description" => "Remote personal data"
        ]);
        if ($addedMetadata == "0") {
            $_SESSION['errorData']['Internal error'][] = "Cannot set 'repository' directory $localWorkingDir";
            redirect($_SERVER['HTTP_REFERER']);
        }

        if (!is_dir($workingDir)) {
            mkdir($workingDir, 0775);
        }
    }

    if (!is_dir($workingDir)) {
        $_SESSION['errorData']['Error'][] = "Target server directory '$localWorkingDir' is not a directory. Your user account is corrupted. Please, report to ...";
        redirect($_SERVER['HTTP_REFERER']);
    }

    // Check file already registered
    $filePath  = "$workingDir/$filename";
    $localFilePath = "$localWorkingDir/$filename";
    $filenameId = getGSFileId_fromPath($localFilePath);
    if ($filenameId) {
        $_SESSION['errorData']['Error'][] = "Resource file ('$url') is already available in the workspace: $filePath";
        redirect("../getdata/editFile.php?fn[]=$filenameId");
    }

    //asyncronous download file (internal tool wget)

    //FIXME START - This is a temporal fix. In future, files should not be downloaded, only registered

    //output_dir will be where fn is expeted to be created: repository
    $output_dir = $workingDir;

    // working_dir will be set in user temporal dir. Checking it
    // TODO Or NO! maybe we decide to run directly on uploads/
    $tmpDir = $GLOBALS['dataDir'] . "/" . $dataDirPath . "/" . $GLOBALS['tmpUser_dir'];
    if (!is_dir($tmpDir)) {
        if (!mkdir($tmpDir, 0775, true)) {
            $_SESSION['errorData']['error'][] = "Cannot create temporal file $tmpDir . Please, try it later.";
            #break;
        }
    }

    $toolId = "wget";
    $toolInputs = [];
    $toolArgs  = [
        "url"    => $url,
        "output" => $filePath
    ];

    // setting tool outputs. Metadata will be saved in DB during tool output_file registration
    $description = $description ?: "Remote file extracted from <a target='_blank' href=\"$url\">$url</a>";

    if (!$filetype) {
        [$fileExtension, $compressed, $fileBaseName] = getFileExtension($filePath);
        $filetypes = getFileTypeFromExtension($fileExtension);
        $filetype = array_keys($filetypes)[0] ?? "";
    }

    $validated = $filetype != "" && $datatype != "";
    $fileOut = [
        "name"       => "file",
        "type"       => "file",
        "file_path"  => $filePath,
        "data_type"  => $datatype,
        "file_type"  => $filetype,
        "sources"    => [0],
        "source_url" => $url,
        "meta_data"  => [
            "validated"   => $validated,
            "compressed"  => $compressed,
            "description" => $description,
        ]
    ];

    if (isset($oeb_dataset_id)) {
        $fileOut['meta_data']["oeb_dataset_id"] = $oeb_dataset_id;
    }

    if (isset($oeb_community_ids)) {
        $fileOut['meta_data']["oeb_community_ids"] = $oeb_community_ids;
    }

    $toolOuts = ["output_files" => [$fileOut]];
    $logName = basename($filePath) . ".log";
    $pid = launchToolInternal($toolId, $toolInputs, $toolArgs, $toolOuts, $output_dir, $logName);

    if ($pid == 0) {
        $_SESSION['errorData']['Error'][] = "Resource file '" . basename($filePath) . "' cannot be imported. Error occurred while preparing the job 'Get remote file'";
        redirect($_SERVER['HTTP_REFERER']);
    }

    $_SESSION['errorData']['Info'][] = "Remote file '" . basename($filePath) . "' imported into the 'repository' folder below. Please, edit its metadata once the job has finished";
    redirect($GLOBALS['BASEURL'] . "workspace/");
    //FIXME END
}


/*********************************/
/*                               */
/*      DATA FROM SAMPLE DATA    */
/*                               */
/*********************************/

// list sampleData

function getSampleDataList($status = 1, $filter_tool_status = true)
{
    if ($filter_tool_status) {
        $fa = indexArray($GLOBALS['toolsCol']->find(array('status' => 1), array('_id' => 1)));
        $fu = indexArray($GLOBALS['visualizersCol']->find(array('status' => 1), array('_id' => 1)));
        $tools_active = array_keys(array_merge($fa, $fu));

        // if common/anon user, list sampledata for active tools
        if ($_SESSION['User']['Type'] == UserType::Guest || $_SESSION['User']['Type'] == UserType::Registered) {
            $ft = $GLOBALS['sampleDataCol']->find(array(
                '$or' => array(
                    array("status" => $status, "tool" => array('$not' => array('$exists' => 1))),
                    array("status" => $status, "tool" => array('$in'  => $tools_active))
                )
            ), array('_id' => 1));

            // if admin user, list sampledata regardless tool status    
        } elseif ($_SESSION['User']['Type'] == UserType::Admin) {
            $ft = $GLOBALS['sampleDataCol']->find(array('status' => $status), array('_id' => 1));

            // if tool dev user, list sampledata for active tools + its own tools
        } elseif ($_SESSION['User']['Type'] == UserType::ToolDev) {
            $fr = $GLOBALS['toolsCol']->find(array('status' => 3, '_id' => array('$in' => $_SESSION['User']['ToolsDev'])), array('_id' => 1));
            $tools_owned = array_keys(iterator_to_array($fr));
            $ft = $GLOBALS['sampleDataCol']->find(array(
                '$or' => array(
                    array("status" => $status, "tool" => array('$not' => array('$exists' => 1))),
                    array("status" => $status, "tool" => array('$in'  => array_merge($tools_active, $tools_owned)))
                )
            ), array('_id' => 1));
        }
    } else {
        // list active sample data sets, regardless tool status
        $ft = $GLOBALS['sampleDataCol']->find(array('status' => $status), array('_id' => 1));
    }
    return iterator_to_array($ft);
}


// get sampleData
function getSampleData($sampleData)
{
    return  $GLOBALS['sampleDataCol']->findOne(['_id' => $sampleData]);
}


// import sampleData into into current WS user 
function getData_fromSampleData($params = [])
{
    if (!is_array($params['sampleData'])) {
        $params['sampleData'] = [$params['sampleData']];
    }

    foreach ($params['sampleData'] as $sampleName) {
        $_SESSION['errorData']['Info'][] = "Importing exemple dataset for '$sampleName'";
        $dataDir = $_SESSION['User']['id'] . "/" . $_SESSION['User']['activeProject'];
        if (setUserWorkSpace_sampleData($sampleName, $dataDir) == "0") {
            $_SESSION['errorData']['Warning'][] = "Cannot fully inject exemple dataset into user workspace.";
            redirect($GLOBALS['URL'] . "/getdata/sampleDataList.php");
        }

        $_SESSION['errorData']['Info'][] = "Example data successfuly imported.";
        header("Location:" . $GLOBALS['URL'] . "/workspace/");
    }
}


function getData_fromEGA($datasetIds, $fileIds, $filenames, $fileSizes)
{
    $datasetIdsArray = explode(',', $datasetIds);
    $fileIdsArray = explode(',', $fileIds);
    $filenamesArray = explode(',', $filenames);
    $fileSizesArray = explode(',', $fileSizes);
    $dataDirPath = getAttr_fromGSFileId($_SESSION['User']['dataDir'], "path");
    $localWorkingDir = $dataDirPath . "/uploads";
    for ($i = 0; $i < count($fileIdsArray); $i++) {
        $filePath = "{$datasetIdsArray[$i]}/{$filenamesArray[$i]}";

        $insertData = [
            'owner' => $_SESSION['User']['id'],
            'size' => $fileSizesArray[$i],
            'mtime' => new MongoDB\BSON\UTCDateTime(strtotime("now") * 1000)
        ];

        $metaData = [
            'data_type' => "variants",
            'data_source' => "EGA",
            'ega_path' => $filePath,
            'format' => "VCF",
            'validated' => TRUE,
            'visible' => TRUE
        ];

        $fileBasename = basename($filePath);
        $fileId = uploadGSFileBNS("$localWorkingDir/$fileBasename", $filePath, $insertData, $metaData, FALSE);
        if ($fileId == "0") {
            unlink($filePath);
            die("ERROR: Error occurred while registering the uploaded file.");
        }

        echo "EGA file uploaded";
    }

    return;
}
