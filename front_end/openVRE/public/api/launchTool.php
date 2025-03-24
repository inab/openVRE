<?php


function launchTool($toolId, $userEmail, $projectName, $inputFiles) {
    $tool = getTool_fromId($toolId,1);
    
    if (empty($tool)) {
        $_SESSION['errorData']['Error'][] = "Tool not found";
        return 0;
    }

    $userInfo = getUserById($userEmail);
    if (empty($userInfo)) {
        $_SESSION['errorData']['Error'][] = "User not found";
        return 0;
    }

    $_SESSION['User'] = $userInfo;
    $_SESSION['curDir'] = $userInfo['id'];

    $executionName = InputTool_getDefExName();
    $description = "API job execution";
    $arguments['argument'] = "text-argument";
    
    $projects = getProjects_byOwner(1, $userInfo['id']);
    foreach ($projects as $item) {
        if (isset($item['name']) && $item['name'] === $projectName) {
            // Set the project attribute and break out of the loop
            $projectDirPath = $item['path'];
            $projectDir = $item['project'];
            break;
        }
    }

    $pathParts = pathinfo($inputFiles[0]); // Waiting for an input file
    $inputFilePath = $projectDirPath . "/uploads/" . $inputFiles[0]; // TODO: change hardcoded path. Assuming input files are in uploads folder

    $inputFileId = getGSFileId_fromPath($inputFilePath);
    $input_files[$pathParts['filename']][] = $inputFileId;

    $jobMeta  = new Tooljob($tool, $executionName, $projectDir, $description);

    $files = [];
    $filesId = [];
    foreach ($input_files as $input_file) {
        if (is_array($input_file)) {
            $filesId = array_merge($filesId,$input_file);
        } else {
            if ($input_file) {
                array_push($filesId,$input_file);
            }
        }
    }

    $filesId = array_unique($filesId);
    foreach ($filesId as $fnId){
        $file = getGSFile_fromId($fnId);
    
        if (!$file){
            continue;
        }
        $files[$file['_id']]=$file;
    
        $associated_files = getAssociatedFiles_fromId($fnId);
        foreach ($associated_files as $assocId){
            $assocFile = getGSFile_fromId($assocId);
            if (!$assocFile){
                $_SESSION['errorData']['Error'][]="File associated to ".basename($file['path'])." ($assocId) does not belong to current user or has been not properly registered. Stopping execution";
              redirect($GLOBALS['BASEURL']."workspace/");
            }
            $files[$assocFile['_id']]=$assocFile;
        }
    }

    $jobMeta->setArguments($arguments, $tool);
    $r = $jobMeta->setInput_files($input_files, $tool, $files);
    if ($r == "0") {
        return ["Error setting input files"];
    }

    $input_files_public = [];
    $files_pub  = $jobMeta->createMetadata_from_Input_files_public($input_files_public, $tool);
    $r = $jobMeta->setInput_files_public($input_files_public, $tool, $files_pub);
    if ($r == "0") {
        $_SESSION['errorData']['Error'][] = "Error setting public files.";
        return 0;
    }

    $workDirId = $jobMeta->createWorking_dir();
    if (!$workDirId) {
        $_SESSION['errorData']['Error'][] = "Error creating working dir.";
        return 0;
    }

    $r = $jobMeta->prepareExecution($tool,$files,$files_pub);
    if ($r == 0) {
        $_SESSION['errorData']['Error'][] = "Error preparing execution.";
        return 0;
    }

    $pid = $jobMeta->submit($tool);
    if (!$pid) {
        $_SESSION['errorData']['Error'][] = "Error submitting job.";
        return 0;
    }

    addUserJob($_SESSION['User']['_id'],(array)$jobMeta,$jobMeta->pid);
    $jobInfoToUser = ["jobTitle" => $jobMeta->title, "project" => $projectName, "execution" => $jobMeta->execution];

    return $jobInfoToUser;
}

