<?php


// check if project exists

function isProject($query,$asRoot=0,$owner=0){

    $query_type = (preg_match('/__PROJ/',$query)?"path":"_id");

    if (!$owner || !$asRoot)
        $owner = $_SESSION['User']['id'];

    // get proj id from proj path
    if ($query_type == "path"){
        $proj_path = (preg_match('/^__PROJ/',$query)?"$owner/$query":$query); 
        $query = getGSFileId_fromPath($proj_path,$asRoot);
    }

    // read 'is_a' attribute
    $is_a = getAttr_fromGSFileId($query,"is_a",$asRoot);
    if ($is_a && $is_a == "project"){ return TRUE; }else{ return FALSE; }

}

// get projects that belongs to a certain onwer

function getProjects_byOwner($asRoot=0,$owner=0){

    if (!$owner || !$asRoot)
        $owner = $_SESSION['User']['id'];

    $filters = Array(
        'owner' => $owner,
        'type'  => "dir",
        'is_a'  => "project"
    );

    $projs = getGSFiles_filteredBy($filters,$asRoot);

    

    return $projs;
}

// get project by id or name

function getProject($query,$asRoot=0,$owner=0){

    $query_type = (preg_match('/__PROJ/',$query)?"path":"_id");

    if (!$owner || !$asRoot)
        $owner = $_SESSION['User']['id'];

    if ($query_type == "_id" ){
        return getGSFile_fromId($query);

    }elseif($query_type == "path"){
        $proj_path = (preg_match('/^__PROJ/',$query)?"$owner/$query":$query);
        $files_proj = getGSFiles_filteredBy(array("path"=> $proj_path),$asRoot);
        return reset($files_proj);

    }else{
        return array();
    }
}


// create new project registry
 
function setProject_OBSOLETE($project_attr=Array(), $asRoot=0,$owner=0){
    
    // set project path
    $proj_code = createLabel_proj(); 
    $proj_fn   = $_SESSION['User']['id']."/$proj_code";
    $proj_rfn  = $GLOBALS['dataDir']."/$proj_fn";


    // create project folder
    $proj_id = createProjectDir($proj_fn,$proj_rfn,$project_attr,$asRoot,$owner);

    return array($proj_id,$proj_code);
}

function updateProject($project_id,$project_attr, $asRoot=0,$owner=0){

    if (!isProject($project_id,$asRoot,$owner)){
        $_SESSION['errorData']['Error'][]= "Given project (code $project_id) not found. Cannot edit it.";
        return 0;
    }
    return addMetadataBNS($project_id, $project_attr);
}


// create random project identifier 

function createLabel_proj(){
    //$label= uniqid($_SESSION['User']['id']."_PROJ",TRUE);
    $label= uniqid("__PROJ",TRUE);
    if (! empty($GLOBALS['filesCol']->findOne(array('_id' => $label))) ){
        //$label= uniqid($_SESSION['User']['id']."_PROJ",TRUE);
        $label= uniqid("__PROJ",TRUE);
    }
    return $label;
}

function createProjectDir($dirfn,$dirrfn,$project_attr=Array(), $asRoot=0,$owner=0){


    // already exists?
    if (is_dir($dirrfn)){
        $_SESSION['errorData']['Error'][]="Cannot create project folder: '$dirfn'. It already exists";
        return 0;
    }

    // register proj dir 

    $dirId = createGSDirBNS($dirfn,$asRoot);
    if ($dirId=="0"){
        $_SESSION['errorData']['Error'][]="Cannot create project folder: '$dirfn'";
	    return 0;
    }

    //  make project directory
    
    mkdir($dirrfn,0777);
    chmod($dirrfn, 0777);


    // set project metadata
    
    if (! isset($project_attr['is_a']))
        $project_attr['is_a'] ="project";
    //if (! isset($project_attr['owner']))
    //    $project_attr['owner'] = $_SESSION['User']['id'];
    if (! isset($project_attr['project_type']))
        $project_attr['project_type'] ="private";
    if (! isset($project_attr['description']))
        $project_attr['description'] ="This is a VRE project";

 
    $r = addMetadataBNS($dirId, $project_attr);
    if ($r == "0"){
        $_SESSION['errorData']['Error'][]="Project folder created. But cannot set metada for '$dirfn' with id '$dirId'";
        return 0;
    }
  	return $dirId;
}

function printProjectContent($project_id,$onlyFolders=FALSE,$asRoot=0,$owner=0){
    
    $html="";

    if (!isProject($project_id,$asRoot,$owner)){
        $_SESSION['errorData']['Error'][]= "Given project (code $project_id) not found.";
        //return $html;
    }

    // get recursively files under given project
    $dirSelection =  array('_id'=> $project_id);
    $files=getGSFilesFromDir($dirSelection,1);

    //  keep only directories
    if ($onlyFolders){
        foreach(array_keys($files) as $f_id){
            if(!isGSDirBNS($GLOBALS['filesCol'],$f_id)){
                unset($files[$f_id]);
            }
        }
    }
    // print paths nicely
    foreach($files as $f){
        $html .= printFilePath_fromPath($f['path']);
    }
    return $html;

}


function deleteProject($project_id){

    $dir = getGSFile_fromId($project_id);
    $rfn_dir = $GLOBALS['dataDir']."/".$dir['path'];

    // delete dir from mongo
    $r = deleteGSDirBNS($project_id);
    if ($r == 0){
        $_SESSION['errorData']['error'][]="Cannot delete project directory entry";
            return 0;
    }
    // delete dir from disk
    exec ("rm -r \"$rfn_dir\" 2>&1",$output);
	if (error_get_last()){
        $_SESSION['errorData']['error'][]=implode(" ",$output);
        return 0;
    }
    return 1;
}
