<?php

function getDataLogger()
{
    static $logger = null;

    if ($logger === null) {
        $logger = LoggerFactory::getLogger('Get data interface');
    }

    return $logger;
}


function getData_fromLocal()
{
    $dataDirPath = getAttr_fromGSFileId($_SESSION['User']['dataDir'], "path");
    $localWorkingDir = "$dataDirPath/uploads";
    $workingDir = $GLOBALS['dataDir'] . "/" . $localWorkingDir;
    if (!is_dir($workingDir)) {
        getDataLogger()->error("Target server directory '" . basename($localWorkingDir) . "' does not exist");
        throw new UnexpectedValueException("Target server directory '" . basename($localWorkingDir) . "' does not exist.");
    }

    $workingDirId = getGSFileId_fromPath($localWorkingDir);
    if (is_null($workingDirId)) {
        getDataLogger()->error("Target server directory '" . basename($localWorkingDir) . "' is not registered in the database");
        throw new NotFoundException("Target server directory '" . basename($localWorkingDir) . "' is not registered in the database.");
    }

    if (empty($_FILES)) {
        getDataLogger()->error("Receiving blank in $_FILES");
        throw new UnexpectedValueException("Receiving blank. Please select a file to upload");
    }

    $errorCode = $_FILES['file']['error'];
    if ($errorCode !== UPLOAD_ERR_OK) {
        $errMsg = [
            UPLOAD_ERR_INI_SIZE => "The uploaded file exceeds the upload_max_filesize directive in php.ini",
            UPLOAD_ERR_FORM_SIZE => "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form",
            UPLOAD_ERR_PARTIAL => "The uploaded file was only partially uploaded",
            UPLOAD_ERR_NO_FILE => "No file was uploaded",
            UPLOAD_ERR_NO_TMP_DIR => "Missing a temporary folder",
            UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk",
            UPLOAD_ERR_EXTENSION => "File upload stopped by extension"
        ];

        if (isset($errMsg[$errorCode])) {
            getDataLogger()->error("Error uploading file (code $errorCode): " . $errMsg[$errorCode]);
            throw new UnexpectedValueException("Error uploading file (code $errorCode): " . $errMsg[$errorCode]);
        }
    }

    $size = $_FILES['file']['size'];
    if (!$size || $size == 0) {
        getDataLogger()->error("File " . $_FILES['file']['name'] . " size is zero");
        throw new UnexpectedValueException("File " . $_FILES['file']['name'] . " size is zero");
    }

    if ($size > return_bytes(ini_get('upload_max_filesize')) || $size > return_bytes(ini_get('post_max_size'))) {
        getDataLogger()->error("File size $size larger than UPLOAD_MAX_FILESIZE (" . ini_get('upload_max_filesize') . ") 0");
        throw new OverflowException("File size $size larger than UPLOAD_MAX_FILESIZE (" . ini_get('upload_max_filesize') . ") 0");
    }

    $usedDisk = (int) getUsedDiskSpace();
    $diskLimit = (int) $_SESSION['User']['diskQuota'];
    if ($size > ($diskLimit - $usedDisk)) {
        getDataLogger()->error("Cannot upload file. Not enough space left in the workspace");
        throw new OverflowException("Cannot upload file. Not enough space left in the workspace");
    }

    $filePath = "$workingDir/" . cleanName($_FILES['file']['name']);
    if (is_file($filePath)) {
        getDataLogger()->error("A file with name " . $_FILES['file']['name'] . " already exists in the workspace");
        throw new InvalidArgumentException("A file with name " . $_FILES['file']['name'] . " already exists in the workspace. Please, rename the file and try again.");
    }

    if ($_FILES['file']['tmp_name'] && move_uploaded_file($_FILES['file']['tmp_name'], $filePath) === false) {
        getDataLogger()->error("Error occurred while moving the uploaded file");
        throw new UnexpectedValueException("Error occurred while moving the uploaded file");
    }

    $permissions = 0666;
    chmod($filePath, $permissions);
    $insertData = [
        'owner' => $_SESSION['User']['id'],
        'size'  => filesize($filePath),
        'mtime' => new MongoDB\BSON\UTCDateTime(filemtime($filePath) * 1000)
    ];

    $metaData = [
        'validated' => false
    ];

    $fileBasename = basename($filePath);
    uploadGSFileBNS("$localWorkingDir/$fileBasename", $filePath, $insertData, $metaData);
    getDataLogger()->info("File $fileBasename uploaded");
}


/////////////////////////////////
/////// FROM URL or ID
/////////////////////////////////


// upload file from URL via CURL
function getData_fromUrl($url, $meta = null)
{
    [$toolArgs, $toolOuts, $output_dir] = prepare_getData_fromURL($url, "uploads", $GLOBALS['BASEURL'] . "/getdata/uploadForm.php#load_from_url", $meta);
    getData_wget_asyncron($toolArgs, $toolOuts, $output_dir);
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
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
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

        getDataLogger()->error($msg);
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

        getDataLogger()->error($msg);
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

        getDataLogger()->error($msg);
        redirect($referer);
    }

    if ($size > ($diskLimit - $usedDisk)) {
        $msg = "Cannot import file. There will be not enough space left in the workspace (size = " . getSize($size) . ")";
        if ($referer == "die") {
            getDataLogger()->error($msg);
            redirect($GLOBALS['BASEURL'] . "workspace/");
        }

        getDataLogger()->error($msg);
        redirect($referer);
    }

    curl_close($ch);

    $dataDirPath = getAttr_fromGSFileId($_SESSION['User']['dataDir'], "path");
    $localWorkingDir = "{$dataDirPath}/{$outdir}";
    $workingDir = $GLOBALS['dataDir'] . "/" . $localWorkingDir;
    $workingDirId = getGSFileId_fromPath($localWorkingDir);

    if (is_null($workingDirId)) {
        try {
            $workingDirId  = createGSDirBNS($localWorkingDir, 1);
        } catch (UnexpectedValueException $e) {
            getDataLogger()->error("Cannot create repository directory '$localWorkingDir' in '$dataDirPath'");
            throw $e;
        }

        getDataLogger()->info("Creating '$outdir' directory: $localWorkingDir ($workingDirId)");
        addMetadataToFile($workingDirId, [
            "expiration" => -1,
            "description" => "Remote personal data"
        ]);

        if (!is_dir($workingDir)) {
            mkdir($workingDir, 0775);
        }
    }

    if (!is_dir($workingDir)) {
        getDataLogger()->error("Target server directory '$localWorkingDir' is not a directory. User account of user '{$_SESSION['User']['username']}' is corrupted");
        throw new UnexpectedValueException("Target server directory '$localWorkingDir' is not a directory. Your user account is corrupted.");
    }

    $filePath = "$workingDir/$filename";
    $filePathLocal = "$localWorkingDir/$filename";
    $fileId = getGSFileId_fromPath($filePathLocal);
    if (isset($fileId)) {
        getDataLogger()->error("Resource file '$url' is already available in the workspace: $filePath");
        redirect("../getdata/editFile.php?fn[]=$fileId");
    }

    // working_dir will be set in user temporal dir. Checking it
    // TODO Or NO! maybe we decide to run directly on uploads/
    $dirTmp = $GLOBALS['dataDir'] . "/" . $dataDirPath . "/" . $GLOBALS['tmpUser_dir'];
    if (!is_dir($dirTmp) && !mkdir($dirTmp, 0775, true)) {
        getDataLogger()->error("Cannot create temporal file '$dirTmp'.Please, try it later.");
    }

    $toolArgs  = [
        "url"    => $url_withCredentials ?: $url,
        "output" => $filePath
    ];

    $descrip = "File imported from URL '$url'";
    $taxon = $meta['taxon'] ?? "";
    [$fileExtension, $compressed] = getFileExtension($filePath);
    $filetypes = getFileTypeFromExtension($fileExtension);
    $filetype = array_keys($filetypes)[0] ?? "";
    $fileOut = [
        "name" => "file",
        "path" => $filePath,
        "data_type" => "",
        "format" => $filetype,
        "taxon_id" => $taxon,
        "meta_data" => [
            "validated" => false,
            "compressed" => $compressed,
            "description" => $descrip
        ]
    ];

    $toolOuts = ["output_files" => [$fileOut]];
    return [$toolArgs, $toolOuts, $workingDir];
}


function  getData_wget_asyncron($toolArgs, $toolOuts, $output_dir)
{
    $toolId = "wget";
    $toolInputs = [];
    $filePath = $toolOuts['output_files'][0]["path"];
    $logName = basename($filePath) . ".log";

    //TODO: FIXME START - This is a temporal fix. In future, files should not be downloaded, only registered
    launchToolInternal($toolId, $toolInputs, $toolArgs, $output_dir, $logName);
    $outdir = basename($output_dir);

    getDataLogger()->info("File from URL '" . basename($filePath) . "' is being imported into the '$outdir' folder below. Please, edit its metadata once the import has finished");
    redirect($GLOBALS['BASEURL'] . "workspace/");
}

/////////////////////////////////
/////// BUILD FILE TEXT
/////////////////////////////////
function getData_fromTXT()
{
    $filename = $_REQUEST['filename'];
    $data = $_REQUEST['txtdata'];

    $dataDirPath = getAttr_fromGSFileId($_SESSION['User']['dataDir'], "path");
    $localWorkingDir = $dataDirPath . "/uploads";

    $workingDir  = $GLOBALS['dataDir'] . "/" . $localWorkingDir;
    $workingDirId = getGSFileId_fromPath($localWorkingDir);

    if (is_null($workingDirId) || !is_dir($workingDir)) {
        getDataLogger()->error("Target server directory '" . basename($localWorkingDir) . "' does not exist. Please, login again.");
        throw new UnexpectedValueException("Target server directory '" . basename($localWorkingDir) . "' does not exist. Please, login again.");
    }

    $filePath = "$workingDir/" . cleanName($filename);
    $size = strlen($data);

    if ($size == 0) {
        getDataLogger()->error("File size is zero");
        throw new UnexpectedValueException("File size is zero");
    }

    $usedDisk = (int) getUsedDiskSpace();
    $diskLimit = (int) $_SESSION['User']['diskQuota'];
    if ($size > ($diskLimit - $usedDisk)) {
        getDataLogger()->error("Not enough space left in the workspace");
        throw new UnexpectedValueException("Not enough space left in the workspace");
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
        getDataLogger()->error("Uploaded file not correctly stored.");
        throw new UnexpectedValueException("Uploaded file not correctly stored.");
    }

    chmod($filePath, 0666);
    $fileBasename = basename($filePath);
    $insertData = [
        'owner' => $_SESSION['User']['id'],
        'size'  => filesize($filePath),
        'mtime' => new MongoDB\BSON\UTCDateTime(filemtime($filePath) * 1000)
    ];

    $metaData = [
        'validated' => false
    ];

    uploadGSFileBNS("$localWorkingDir/$fileBasename", $filePath, $insertData, $metaData);
    getDataLogger()->info("File '" . $fileBasename . "' uploaded");
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
        getDataLogger()->error("Resource URL ('$url') is not valid or unaccessible. Server not found");
        throw new UnexpectedValueException("Resource URL ('$url') is not valid or unaccessible. Server not found");
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
        getDataLogger()->error("Resource URL ('$url') is not valid or unaccessible. Status: $status");
        throw new UnexpectedValueException("Resource URL ('$url') is not valid or unaccessible. Status: $status");
    }

    return $response;
}


// import from Repository (URL) to user workspace
function getData_fromRepository($url, $filetype, $description)
{
    $url_data = process_URL($url);
    $filename = $url_data['filename'];
    if (empty($filename)) {
        getDataLogger()->error("Resource URL ('$url') is not pointing to a valid filename");
        throw new UnexpectedValueException("Resource URL ('$url') is not pointing to a valid filename");
    }

    $size = (int) $url_data['size'];
    $usedDisk = (int) getUsedDiskSpace();
    $diskLimit = (int) $_SESSION['User']['diskQuota'];
    if ($size == 0) {
        getDataLogger()->error("Resource URL ('$url') is pointing to an empty resource (size = 0)");
        throw new UnexpectedValueException("Resource URL ('$url') is pointing to an empty resource (size = 0)");
    }

    if ($size > ($diskLimit - $usedDisk)) {
        getDataLogger()->error("Cannot import file. There will be not enough space left in the workspace (size = $size)");
        throw new UnexpectedValueException("Cannot import file. There will be not enough space left in the workspace (size = $size)");
    }

    // setting repository directory
    $dataDirPath = getAttr_fromGSFileId($_SESSION['User']['dataDir'], "path");
    $localWorkingDir = "$dataDirPath/repository";
    $workingDir = $GLOBALS['dataDir'] . "/" . $localWorkingDir;
    $workingDirId = getGSFileId_fromPath($localWorkingDir);

    if (is_null($workingDirId)) {
        try {
            $workingDirId  = createGSDirBNS($localWorkingDir, 1);
        } catch (UnexpectedValueException $e) {
            getDataLogger()->error("Cannot create repository directory '$localWorkingDir' in '$dataDirPath'");
            throw $e;
        }

        getDataLogger()->info("Creating repository directory: $localWorkingDir ($workingDirId)");
        addMetadataToFile($workingDirId, [
            "expiration" => -1,
            "description" => "Remote personal data"
        ]);

        if (!is_dir($workingDir)) {
            mkdir($workingDir, 0775);
        }
    }

    if (!is_dir($workingDir)) {
        getDataLogger()->error("Target server directory '$localWorkingDir' is not a directory. User account of user '{$_SESSION['User']['username']}' is corrupted");
        throw new UnexpectedValueException("Target server directory '$localWorkingDir' is not a directory. Your user account is corrupted.");
    }

    $filePath  = "$workingDir/$filename";
    $localFilePath = "$localWorkingDir/$filename";
    $fileId = getGSFileId_fromPath($localFilePath);
    if (isset($fileId)) {
        getDataLogger()->error("Resource file '$url' is already available in the workspace: $filePath");
        redirect("../getdata/editFile.php?fn[]=$fileId");
    }

    // working_dir will be set in user temporal dir. Checking it
    // TODO Or NO! maybe we decide to run directly on uploads/
    $dirTmp = $GLOBALS['dataDir'] . "/" . $dataDirPath . "/" . $GLOBALS['tmpUser_dir'];
    if (!is_dir($dirTmp) && !mkdir($dirTmp, 0775, true)) {
        getDataLogger()->error("Cannot create temporal file '$dirTmp'.Please, try it later.");
    }

    //asyncronous download file (internal tool wget)
    //FIXME START - This is a temporal fix. In future, files should not be downloaded, only registered

    $toolId = "wget";
    $toolInputs = [];
    $toolArgs  = [
        "url"    => $url,
        "output" => $filePath
    ];

    // setting tool outputs. Metadata will be saved in DB during tool output_file registration
    $description = $description ?: "Remote file extracted from <a target='_blank' href=\"$url\">$url</a>";

    if (empty($filetype)) {
        [$fileExtension] = getFileExtension($filePath);
        $filetypes = getFileTypeFromExtension($fileExtension);
        $filetype = array_keys($filetypes)[0] ?? "";
    }

    $logName = basename($filePath) . ".log";
    launchToolInternal($toolId, $toolInputs, $toolArgs, $workingDir, $logName);

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
        if ($_SESSION['User']['Type'] == UserType::Guest->value || $_SESSION['User']['Type'] == UserType::Registered->value) {
            $ft = $GLOBALS['sampleDataCol']->find(array(
                '$or' => array(
                    array("status" => $status, "tool" => array('$not' => array('$exists' => 1))),
                    array("status" => $status, "tool" => array('$in'  => $tools_active))
                )
            ), array('_id' => 1));

            // if admin user, list sampledata regardless tool status    
        } elseif ($_SESSION['User']['Type'] == UserType::Admin->value) {
            $ft = $GLOBALS['sampleDataCol']->find(array('status' => $status), array('_id' => 1));

            // if tool dev user, list sampledata for active tools + its own tools
        } elseif ($_SESSION['User']['Type'] == UserType::ToolDev->value) {
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
function getSampleData($sampleDataId)
{
    return  $GLOBALS['sampleDataCol']->findOne(['_id' => $sampleDataId]);
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
        setUserWorkSpace_sampleData($sampleName, $dataDir);

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
            'validated' => true,
            'visible' => true
        ];

        $fileBasename = basename($filePath);
        uploadGSFileBNS("$localWorkingDir/$fileBasename", $filePath, $insertData, $metaData);
        getDataLogger()->info("File $fileBasename uploaded");
    }
}
