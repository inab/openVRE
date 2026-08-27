<?php

use OpenVRE\LoggerFactory;
use OpenVRE\NotFoundException;


function getMongoProjectLogger()
{
    static $logger = null;

    if ($logger === null) {
        $logger = LoggerFactory::getLogger('MongoDB project interface');
    }

    return $logger;
}


// check if project exists
function isProject($query, $asRoot = 0, $owner = 0)
{
    if (!$owner || !$asRoot) {
        $owner = $_SESSION['User']['id'];
    }

    // get proj id from proj path
    $query_type = (preg_match('/__PROJ/', $query) ? "path" : "_id");
    if ($query_type == "path") {
        $proj_path = (preg_match('/^__PROJ/', $query) ? "$owner/$query" : $query);
        $query = getGSFileId_fromPath($proj_path, $asRoot);
    }

    return getAttr_fromGSFileId($query, "is_a", $asRoot) === "project";
}


// get projects that belongs to a certain onwer

function getProjects_byOwner($asRoot = 0, $owner = 0)
{

    if (!$owner || !$asRoot)
        $owner = $_SESSION['User']['id'];

    $filters = array(
        'owner' => $owner,
        'type'  => "dir",
        'is_a'  => "project"
    );

    $projs = getGSFiles_filteredBy($filters, $asRoot);



    return $projs;
}

// get project by id or name

function getProject($query, $asRoot = 0, $owner = 0)
{
    $query_type = (preg_match('/__PROJ/', $query) ? "path" : "_id");

    if (!$owner || !$asRoot) {
        $owner = $_SESSION['User']['id'];
    }

    if ($query_type == "_id") {
        return getGSFile_fromId($query);
    } elseif ($query_type == "path") {
        $proj_path = (preg_match('/^__PROJ/', $query) ? "$owner/$query" : $query);
        $files_proj = getGSFiles_filteredBy(array("path" => $proj_path), $asRoot);
        return reset($files_proj);
    } else {
        return array();
    }
}


function updateProject($project_id, $project_attr, $asRoot = 0, $owner = 0)
{
    if (!isProject($project_id, $asRoot, $owner)) {
        $_SESSION['errorData']['Error'][] = "Given project (code $project_id) not found. Cannot edit it.";
        getMongoProjectLogger()->error("Given project (code $project_id) not found. Cannot edit it.");
        throw new NotFoundException("Given project (code $project_id) not found. Cannot edit it.");
    }

    return addMetadataToFile($project_id, $project_attr);
}


// create random project identifier 

function createLabel_proj()
{
    $label = uniqid("__PROJ", true);
    if (! empty($GLOBALS['filesCol']->findOne(array('_id' => $label)))) {
        $label = uniqid("__PROJ", true);
    }
    return $label;
}


function createProjectDir($dirfn, $dirrfn, $project_attr = array(), $asRoot = 0)
{
    if (is_dir($dirrfn)) {
        getMongoProjectLogger()->error("Cannot create project folder: '$dirfn'. It already exists");
        throw new UnexpectedValueException("Cannot create project folder: '$dirfn'. It already exists");
    }

    // register proj dir
    $dirId = createGSDirBNS($dirfn, $asRoot);

    //  make project directory
    mkdir($dirrfn, 0777);
    chmod($dirrfn, 0777);

    // set project metadata
    $project_attr['is_a'] ??= "project";
    $project_attr['project_type'] ??= "private";
    $project_attr['description'] ??= "This is a VRE project";

    addMetadataToFile($dirId, $project_attr);

    return $dirId;
}


function printProjectContent($project_id, $onlyFolders = false, $asRoot = 0, $owner = 0)
{

    $html = "";

    if (!isProject($project_id, $asRoot, $owner)) {
        $_SESSION['errorData']['Error'][] = "Given project (code $project_id) not found.";
        //return $html;
    }

    // get recursively files under given project
    $dirSelection =  array('_id' => $project_id);
    $files = getGSFilesFromDir($dirSelection, 1);

    //  keep only directories
    if ($onlyFolders) {
        foreach (array_keys($files) as $f_id) {
            if (!isGSDirBNS($GLOBALS['filesCol'], $f_id)) {
                unset($files[$f_id]);
            }
        }
    }
    // print paths nicely
    foreach ($files as $f) {
        $html .= printFilePath_fromPath($f['path']);
    }
    return $html;
}


function deleteProject($project_id)
{
    $dir = getGSFile_fromId($project_id);
    $rfn_dir = $GLOBALS['dataDir'] . "/" . $dir['path'];
    deleteGSDirBNS($project_id);

    // delete dir from disk
    exec("rm -r \"$rfn_dir\" 2>&1", $output);
    if (error_get_last()) {
        $_SESSION['errorData']['error'][] = implode(" ", $output);
        getMongoProjectLogger()->error(implode(" ", $output));
        throw new UnexpectedValueException(implode(" ", $output));
    }
}
