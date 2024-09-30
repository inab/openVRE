
<?php

// Adapt user data, both, in disk and DB, to Projects
// by creating a default project for those users that have none

require "phplib/genlibraries.php";


$ids = array();
if (defined('STDIN')) {
    array_shift($argv);
    $ids = $argv;
    if ($ids[0] == "dryrun"){
        array_shift($ids);
        $_REQUEST['dryrun']=1;
    }else{
        $_REQUEST['dryrun']=0;
    }
}else{
    if(isset($_REQUEST['id'])){
        if (is_array($_REQUEST['id']))
            $ids = $_REQUEST['id'];
        else
            array_push($ids,$_REQUEST['id']);
    }
}
if (!isset($_SESSION['errorData'])){
    $_SESSION['errorData']=array();
}

// ensure dryrun
$_REQUEST['dryrun'] = 1;

// list all users
//$fu = $GLOBALS['usersCol']->find(array());
$fu = $GLOBALS['usersCol']->find(array("_id" => "ebb7713428c9245dbb9277139"));

// for each user
foreach ( array_values(iterator_to_array($fu)) as $u ){

    $id = $u['id'];

    // ckeck if user exists in mongo
    $u = checkUserIDExists($id);
    if (!isset($_SESSION['User'])){
        $_SESSION['User'] = $u;
    }

    if(!isSet($u)) {
        print "Id $id does not exists in DB";
        continue;
    }


    print "<br/>\n-------------<br/>\n USER ID => $id (".$u['_id'].")<br/>\n<br/>\n";

    // ckeck if user exists in disk
    
    $homeDir = $GLOBALS['dataDir'] . "/$id";
    print "- homeDir = $homeDir<br/>\n";
    if (!is_dir($homeDir)){
        print "Id $id has no home dir: $homeDir";
        continue;
        
    }

    // check if user has orphan files (MONGO)
    
    $homeId = getGSFileId_fromPath($id,1);
    print "- homeId = $homeId<br/>\n";
    if (! $homeId){
        print "Id $id has no home dir registered";
        continue;
    }

    $homeFiles = getGSFile_fromId($homeId,"",1);
    print "- Num files in home = ".count($homeFiles['files'])."<br/>\n";
    foreach ($homeFiles['files'] as $f_id){
        $f =  getGSFile_fromId($f_id,"",1);
        print "   - - - ".$f['_id']." - - - ".$f['path']."<br/>\n";
    }

    $projs = getProjects_byOwner(1,$id);
    print "- Num projs in home = ".count(array_keys($projs))."<br/>\n";

    $orphanIds=array();
    foreach ($homeFiles['files'] as $dirId){
        if (!in_array($dirId,array_keys($projs))){
            array_push($orphanIds,$dirId);
        }
    }
    
    print "- Num Orphan dirs = ".count($orphanIds)."<br/>\n";
    foreach ($orphanIds as $f_id){
        $f =  getGSFile_fromId($f_id,"",1);
        print "     - ".$f['_id']." (path ".$f['path'].")<br/>\n";
    }


    // Check if user has orphan files (IN DISK)

    $homeFilesDisk = scanDir($homeDir);
    if (count($homeFilesDisk) < 3){
        print "ERROR: user $id has no files in $homeDir\n";
        continue;
    }
    $orphanPaths = array();
    foreach ($homeFilesDisk as $f){
        //if (preg_match('/^\.\w+/',$f) || (preg_match('/^\w/',$f) && !preg_match('/__PROJ/',$f)) ){
        if ( (preg_match('/^\.\w+/',$f) && !preg_match('/\.dev/',$f)) || (preg_match('/^\w/',$f) && !preg_match('/__PROJ/',$f)) ){
            array_push($orphanPaths,"$id/$f");
        }
    }
    print "- Num Orphan dirs (in disk) = ".count($orphanPaths)."<br/>\n";
    foreach ($orphanPaths as $f_path){
        print "    - $f_path<br/>\n";
    }

    // If orphans and no projs, create foo project
    $proj_code = "";
    $proj_id   = "";
    if ( (count($orphanIds) || count($orphanPaths)) && count($projs)==0 ){
        print "- Creating foo project! - user is old model, have  no other projects<br/>\n";

        if ($_REQUEST['dryrun']){continue;}
                
        $proj_code = createLabel_proj();
        //$proj_code = "__PROJ5b4e0c02decf67.81076887";
        $proj_sd   = "0";
        $proj_data = array("name"=> "MyProject", "keywords"=> "compatibilityMode","description"=> "This is an automatic project that puts together all your data. It has been set to ensure the compatibility of your workspace data with the new MuGVRE - where user's data is organized by 'projects'. You can manage it as any other project!");

        $proj_id = prepUserWorkSpace($id,$proj_code,$proj_sd,$proj_data,FALSE,1);
        //$proj_id = "MuGUSER5a0c0314c20d1_5b4e0c02e1f354.18716647";
        $proj    = getProject($proj_id,1,$id);  
        print "- Foo project created [code (id)] = $proj_code ($proj_id)<br/>\n";

    // If orphans but already  projs, check project
    }elseif ( count($orphanIds) || count($orphanPaths)){
        foreach ($projs as $proj_id => $proj){
            if ($proj['keywords']== "compatibilityMode"){
                $proj_code= basename($proj['path']);
                break;
            }
        }
        // if yes 'compatibilityMode' project, reuse old foo project
        if ($proj_code){
            $proj_id = getGSFileId_fromPath("$id/$proj_code",1);
            print "- Foo project already there. IS compatibilityMode. Reusing it [code (id)] = $proj_code ($proj_id)<br/>\n";

        }else{

        // if no 'compatibilityMode' project, create foo project
            print "- Creating foo project! - user has projects but not 'compatibilityMode' <br/>\n";

            if ($_REQUEST['dryrun']){continue;}

            $proj_code = createLabel_proj();
            $proj_sd   = "0";
            $proj_data = array("name"=> "MyProject", "keywords"=> "compatibilityMode","description"=> "This is an automatic project that puts together all your data. It has been set to ensure the compatibility of your workspace data with the new MuGVRE - where user's data is organized by 'projects'. You can manage it as any other project!");
    
            $proj_id = prepUserWorkSpace($id,$proj_code,$proj_sd,$proj_data,FALSE,1);
            $proj    = getProject($proj_id,1,$id);  
            print "- Foo project created [code (id)] = $proj_code ($proj_id)<br/>\n";
        }
    }



    // Set active workspace to foo project
    if ($proj_code || !$u['dataDir']){
        print "- Setting active project in Users collection";
        if (!$proj_code){
            foreach ($projs as $proj_id => $proj){
                if ($proj['keywords']== "compatibilityMode"){
                    $proj_code= basename($proj['path']);
                    break;
                }
            }
            // if yes 'compatibilityMode' project, set user with it.
            if ($proj_code){
                $proj_id = getGSFileId_fromPath("$id/$proj_code",1);
                if (!$proj_id){
                    $_SESSION['errorData']['error'][]="Cannot update User dataDir. Cannot find a 'compatibilityMode' project identifier ($id/$proj_code).";
                    continue;
                    
                }
                print "- ActiveProject= $proj_code -- dataDir = $proj_id<br/>\n";
            
            }else{
                $_SESSION['errorData']['error'][]="Cannot update User dataDir. Cannot find a 'compatibilityMode' project.";
                continue;
            }
        }

        if ($_REQUEST['dryrun']){continue;}

        if ($proj_code){
            modifyUser($u['_id'],"activeProject",$proj_code);
            modifyUser($u['_id'],"dataDir"      ,$proj_id);
        }
    }

    if ($_REQUEST['dryrun']){continue;}

    // If orphans in mongo, move each dir into foo project
    if ( count($orphanIds)){

        foreach ($orphanIds as $dirId){
            $dir = getGSFile_fromId($dirId,"",1);
            $dir_pathOld = $dir['path'];
            $dir_pathNew = "$id/$proj_code/".basename($dir['path']);
            $rfn_pathOld = $GLOBALS['dataDir']."/".$dir_pathOld;
            $rfn_pathNew = $GLOBALS['dataDir']."/".$dir_pathNew;

            print "    - Moving orphan dir $dir_pathOld -> $dir_pathNew<br/>\n";

            if (!is_dir($rfn_pathOld) ){
                $_SESSION['errorData']['Error'][]="Error moving orphan dir. '$rfn_pathOld' not found in disk";
                continue;
            }
            if (is_dir($rfn_pathNew)){
                $_SESSION['errorData']['Error'][]="Error moving orphan dir.  Dir already in project ($rfn_pathNew)";
                continue;
            }
            // Move dir in DB
            $r = moveGSDirBNS($dir_pathOld,$dir_pathNew,1,$id);
                
            if ($r == "0"){
                $_SESSION['errorData']['Error'][]="Error while registering orphan directory into foo project";
                continue;
            }
            // Move dir in disk
            rename($rfn_pathOld,$rfn_pathNew);
            if (!is_dir($rfn_pathNew)){
                $_SESSION['errorData']['Error'][]="Error while writting orphan directory into foo project";
                continue;
            }
        }
    }
    // If orphans in disk, move each dir into foo project
     
    $homeFilesDisk = scanDir($homeDir);
    $orphanPaths = array();
    foreach ($homeFilesDisk as $f){
        if (preg_match('/^\.\w+/',$f) || (preg_match('/^\w/',$f) && !preg_match('/__PROJ/',$f)) ){
            array_push($orphanPaths,"$id/$f");
        }
    }
    print "- Num Orphan dirs (in disk) = ".count($orphanPaths)."<br/>\n";

    if ( count($orphanPaths)){
        foreach ($orphanPaths as $f_path){
            $rfn_pathOld = $GLOBALS['dataDir']."/$f_path";
            $rfn_pathNew = $GLOBALS['dataDir']."/$id/$proj_code/".basename($f_path);
            print "MOVE IN DISK $rfn_pathOld $rfn_pathNew<br/>\n";
            rename($rfn_pathOld,$rfn_pathNew);
        }

    }



}
print "<br/>\n ------------ERRORS-------<br/>\n";
var_dump($_SESSION['errorData']);
unset($_SESSION['errorData']);
?>
