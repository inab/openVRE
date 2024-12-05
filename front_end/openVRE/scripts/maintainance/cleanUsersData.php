#!/usr/bin/env php
<?php
/*
 *  The present cleaning deletes the files and directories belonging to VRE users from both,
 *  the file system and the VRE database. The criteria to do so is the following:
 *  1- deletes user's data according their expiration time
 *  2- deletes anonymous user's accounts with no data after data cleaning
 *  3- deletes user's temporary data always
 *
 */

/*
 * NOTES:
 * - execute a 'dry_run' to manually check the data that will be cleaned
 * - execute the script with a UNIX user able to delete VRE data (for instance, the same UNIX user serving VRE)
 * - make sure mongo connection and data directory ('dataDir') are correctly set at config/globals.inc.php
 * - file's expiration date is taken from VRE database, except for 'README.md' files, which is bellow configured
 * - some critical settings are taken from config/globals.inc.php (mongo connexion, tmpUser_dir, dataDir)
 */

################# FEEL FREE TO EDIT  ##################


$caduca_strict = 7;  // Expiration for the README.md files automatically created (in days). Ie. 7 days after creation, file expires
                     // Expiration for the other files is not altered and it is read from DB
$dry_run   = true;  // true: evaluate files but dont delete them.
                     // false: real run - activates the cleaning
$soft_mode = false;  // true: only README files are elegible to be cleaned (so non-used anonymous users might be deleted).\
                     // false: all files are elegible to be cleaned
$only_anon = true;  // true: the cleaning only is done over data from anonynous users
                    // false: the cleaning is applied to all users (except for admin users)
                    //        TODO(1): delete user's folder that end up empty after cleaning
$rm_temporary = true;// true: remove temporary data for all users ('tmpUser_dir'), regardless 'only_clean_anon'
                     // false: do not clean temporary data
$verbose   = 0;      // Options: 0|1



################# NO NEED TO EDIT BELOW ##################

print "\n########### START DATE: ".date("Y-m-d H:i:s")." ##################\n";

// loading VRE libraries and configuration
require __DIR__."/../../config/bootstrap.php";

// query users whose files will be cleaned
//$users_query = ($only_anon? array('Type' => "3") : array('Type' => array('$in' => array ("3","2","1"))) );
$users_query = array("_id" => "ECSHANON5fe51c35f2a8b") ;
$fu = $GLOBALS['usersCol']->find($users_query);

$user_data = array(
   array("id" => "EUSHANON5db9d62b17900", "dataDir" => "ECSHANON5fe51c35f2a8b", "Type"=>"3", "_id"=>"X" ) 
);

$errors=array();

// foreach user
//foreach ( array_values(iterator_to_array($fu)) as $v ){
foreach ( $user_data as $v ){

    if (!isset($v['id'])){
        print "\n-----> Cleaning user ".$v['_id']. " (".$v['id'].")\n";
        $msg = "ERROR: User ".$v['_id']. " has no identifier\n";
        print $msg;
        array_push($errors,$msg);
        continue;
    }

    // check user object is well formed
    if (!isset($v['dataDir']) || !$v['dataDir']){
        print "\n-----> Cleaning user ".$v['_id']. " (".$v['id'].")\n";

        if ($v['Type'] == 3 ){
            // force delete user
            if ($dry_run === true) { print "Dry run ON - doing nothing\n"; continue;}
            $msg= "ERROR: User ".$v['_id']. " has no dataDir attribute. Deleting ANON user";
            print $msg;
            array_push($errors,$msg);
            $r = delUser($v['id'],1,true);
            if ($r === false || $r == 0  ){
                $msg="ERROR: cannot delete ANON user ".$v['id'].". ".join("; ",$_SESSION['errorData']['Error'])."\n";
                print $msg;
                array_push($errors,$msg);
                unset($_SESSION['errorData']['Error']);
                continue;
            }
        }else{
            $msg= "ERROR: User ".$v['_id']. " has no dataDir attribute. Keeping malformed user but not cleaning it\n";
            print $msg;
            array_push($errors,$msg);
        }
        continue;
    }

    print "\n-----> Cleaning user ".$v['_id']. " (".$v['id'].")\n";

    // set user in session
    if (isset($_SESSION)){unset($_SESSION);}
    $_SESSION['User'] = $v;

    // check dataDir exists in disk
    $rdir =  $GLOBALS['dataDir'].$v['id'];
    if (! is_dir($rdir)){

        if ($v['Type'] == 3 ){
            // force delete user
            if ($dry_run === true) { print "Dry run ON - doing nothing\n"; continue;}
            $msg= "ERROR: dataDir for user ".$v['_id']." not found ($rdir). Deleting ANON user\n";
            print $msg;
            array_push($errors,$msg);
            $r = delUser($v['id'],1,true);
            if ($r === false || $r == 0  ){
                $msg = "ERROR deleting user ".$v['id'].". ". join("; ",$_SESSION['errorData']['Error'])."\n";
                print $msg;
                array_push($errors,$msg);
                unset($_SESSION['errorData']['Error']);
                continue;
            }
        }else{
            $msg="ERROR: dataDir for user ".$v['_id']." not found ($rdir). Keeping malformed user but not cleaning it\n";
               print $msg;
            array_push($errors,$msg);
        }
        continue;
    }

    // get user's files/folders
    $files = getGSFilesFromDir(array('_id'=>$v['dataDir']));

    // if no files, delete user
    if ($files === false){
        if ($v['Type'] == 3){
            // force delete user
            $msg =  "ERROR: User ".$v['_id']." has no data registered under dataDir '".$v['dataDir']."'. ". join("; ",$_SESSION['errorData']['Error']). ". Deleting ANON user\n";
            print $msg;
            array_push($errors,$msg);
            unset($_SESSION['errorData']['Error']);
            if ($dry_run === true) { print "Dry run ON - doing nothing\n"; continue;}
            $r = delUser($v['id'],1,true);
            if ($r === false || $r == 0 ){
                $msg = "ERROR: cannot delete user ".$v['id'].". ". join("; ",$_SESSION['errorData']['Error'])."\n";
                print $msg;
                array_push($errors,$msg);
                unset($_SESSION['errorData']['Error']);
                continue;
            }
        }else{
            $msg =  "ERROR: User ".$v['_id']." has no data registered under dataDir '".$v['dataDir']."'. ". join("; ",$_SESSION['errorData']['Error']). ". Keeping empty registered user\n";
            print $msg;
            array_push($errors,$msg);
            unset($_SESSION['errorData']['Error']);
        }
        continue;
    }

    // delete user file's based on expiration date
    foreach ($files as $f){
        if (!isset($f['_id'])){
            continue;
        }
        if (!isset($f['type']) || $f['type'] == "file"){

                $msg_fn = "-- ".$f['_id']."  ".$f['path']."\n";

            $tobedeleted=false;
            $tobedeleted_evenSoftMode=false;

            // ignore if file has no expiration date
            if (!isset($f['expiration'])){
                $msg = "ERROR: Expiration date not set for '".$f['_id'].". Not cleaning file. Doing nothing\n";
                print $msg_fn;
                print $msg;
                    array_push($errors,$msg);
                continue;
            }

            // delete READMEs based on shorten expiration dates (mtime + 'caduca_strict')
            //if (preg_match('/README.md/',$f['path']) && $v['Type'] == 3 ){
            if (preg_match('/README.md/',$f['path'])){

                // ignore files without mtime
                if (!isset($f['mtime'])){
                    $msg = "ERROR: Creation time not set for README '".$f['_id']."'. Not cleaning file. Doing nothing.\n";
                    print $msg_fn;
                    print $msg;
                        array_push($errors,$msg);

                // check expiration based on mtime
                }else{
                    $time_mtime  = strftime('%Y/%m/%d %H:%M', $f['mtime']);
                    $daysold     = intval(( time() - $f['mtime'] ) / (24 * 3600));
                    $days2expire_readme = $caduca_strict - intval(( time() - $f['mtime'] ) / (24 * 3600));

                    if ($days2expire_readme < 0){
                        print $msg_fn;
                        print "----- README expirated | Mtime = $time_mtime | Days old = $daysold | Days overpassed = $days2expire_readme\n";
                        $tobedeleted=true;
                        $tobedeleted_evenSoftMode=true;
                    // file is not expired    
                    }else{
                        if ($verbose){
                            print $msg_fn;
                            print "----- README in vigor | Mtime $time_mtime | Days left = $days2expire_readme days. Doing nothing\n";
                        }
                    }
                }

                
            // delete user files based on standard expiration dates
            }else{

                // ignore files that have expiration -1
                if (!is_object($f['expiration']) &&  $f['expiration'] == -1){
                    if (is_file($GLOBALS['dataDir']."/".$f['path'])){
                        if ($verbose){
                            print $msg_fn;
                            print "----- File in vigor | Expiration = -1. Doing nothing\n";
                        }
                    }else{
                        // file not in disk, force delete
                        print $msg_fn;
                        print "----- File in vigor | Expiration = -1\n";
                        $msg = "ERROR: File '".$f['path']."' not found in disk. Forcing DB cleaning.\n";
                        array_push($errors,$msg);
                        print $msg;
                        
                        $tobedeleted=true;
                        $tobedeleted_evenSoftMode=true;
                    }

                // check expiration based on 'caduca' global var
                }else{
                    $days2expire = intval(( $f['expiration']->sec  - time()) / (24 * 3600));
                    $time_exp    = strftime('%Y/%m/%d %H:%M', $f['expiration']->sec);
                    $time_mtime  = strftime('%Y/%m/%d %H:%M', $f['mtime']);

                    // file is expired    
                    if ($days2expire < 0){
                        if (is_file($GLOBALS['dataDir']."/".$f['path'])){
                            print $msg_fn;
                            print "----- File expirated | Mtime = $time_mtime | Exp date = $time_exp | Days overpassed = $days2expire\n";
                            $tobedeleted=true;
                        }else{
                            // file not in disk, force delete
                            print $msg_fn;
                            print "----- File in vigor | Mtime = $time_mtime | Exp date = $time_exp | Days overpassed = $days2expire\n";
                            $msg = "ERROR: File '".$f['path']."' not found in disk. Forcing DB cleaning.\n";
                            array_push($errors,$msg);
                            print $msg;
                            $tobedeleted=true;
                            $tobedeleted_evenSoftMode=true;
                        }
                    // file is not expired    
                    }else{
                        if ($verbose){
                            print $msg_fn;
                            print "----- File in vigor | Mtime $time_mtime | Exp date = $time_exp | Days left = $days2expire days. Doing nothing\n";
                        }
                    }
                }
            }

            // delete file if required
            if (($tobedeleted === true && $soft_mode=== false) || $tobedeleted_evenSoftMode === true ){

                if (is_file($GLOBALS['dataDir']."/".$f['path']) && !is_writable($GLOBALS['dataDir']."/".$f['path'])){
                    print "FATAL: Cannot delete file. '".$GLOBALS['dataDir']."/".$f['path']."' is not writable: permission denied\n";
                    exit(0);
                }
                if ($dry_run === true) { 
                    print "----- Deleting file\n";
                    print "Dry run ON - doing nothing\n";
                    continue;
                }
                // delete file from disk and DB
                $r = deleteFiles($f['_id'],true); 
                if ($r === false){
                    $msg = "ERROR: Cannot delete file ".$f['path'].". ". join("; ",$_SESSION['errorData']['Error'])."\n";
                    array_push($errors,$msg);
                    print $msg;
                    unset($_SESSION['errorData']['Error']);
                    continue;
                }else{
                    print "----- File '".$f['path']."' deleted\n";
                }

                // TODO(1) delete parent dir if results empty. Do not delete uploads?
                //$parent_files_after = getGSFilesFromDir(array('_id'=>$f['parentDir']));
                //if (count($parent_files_after == 0)){}

            }else{
                if ($verbose){ print "----- Doing nothing\n";}
            }
        }
    }

    // deleting anon users with no data
    if ($v['Type'] != 3){
        print "------ Never deleting registered users. Keeping user\n";
        continue;
    }
    // count user's files
    $files_after = getGSFilesFromDir(array('_id'=>$v['dataDir']));
    $num_files   = 0;
    foreach ($files_after as $f){
        if (!isset($f['_id'])){
            continue;
        }
        if (!isset($f['type']) || $f['type'] == "file"){
            $num_files++;
        }
    }
    // delete user
    if ($num_files == 0 ){
        print "User has ".$num_files." files. Deleting user\n";
        if ($dry_run === true){ continue; }
        $r = delUser($v['id'],1,true);
        if ($r === false || $r == 0  ){
            $msg = "ERROR: cannot delete empty user ".$v['id'].". ". join("; ",$_SESSION['errorData']['Error'])."\n";
            array_push($errors,$msg);
            print $msg;
            unset($_SESSION['errorData']['Error']);
            continue;
       }
    }else{
        print "------ User has ".$num_files." files in vigor. Keeping ANON user\n";
    }
}
if ($errors){
    print "\nSummarizing the errors occurred during file cleaning (".count($errors)."):\n - ";
    print join("\n - ",$errors)."\n";
}
print "EXITTTTTTTT --- TODO";
exit(0);
// clean temporary files
if ($rm_temporary === true){
    $tmp_users = $GLOBALS['dataDir']."/*/*/".$GLOBALS['tmpUser_dir'];
    print "\n\nLooking for temporary data ($tmp_users)\n";

    // find and delete files in .tmp older than 'caduca_strict'
    //$cmd="find ".$GLOBALS['dataDir']."/*/*/".$GLOBALS['tmpUser_dir']."  -maxdepth 1  -mtime +".$caduca_strict." -type f -exec rm -fv {} \\;";
    $cmd = "find ".$GLOBALS['dataDir']."/*/*/".$GLOBALS['tmpUser_dir']."  -maxdepth 1  -mtime +".$caduca_strict." -type f ";
    if ($dry_run === false) {
        $cmd.= " -exec rm -fv {} \\;";
    }
    print "-- $cmd\n";
    system($cmd);

    // find and delete directories in .tmp named like '?*_[0-9]*' and older than 'caduca_strict'
    //$cmd="find ".$GLOBALS['dataDir']."/*/*/".$GLOBALS['tmpUser_dir']."  -maxdepth 1  -mtime +".$caduca_strict." -type d -name '?*_[0-9]*' -exec rm -Rfv {} \\;";
    $cmd = "find ".$GLOBALS['dataDir']."/*/*/".$GLOBALS['tmpUser_dir']."  -maxdepth 1  -mtime +".$caduca_strict." -type d -name '?*_[0-9]*' ";
    if ($dry_run === false) {
        $cmd.= " -exec rm -Rfv {} \\;";
    }
    print "-- $cmd\n";
    system($cmd);

    // find and delete TAR files in .tmp bigger than 2GB  // TODO: no need of it when issue with 'massive TARs' is solved
    //$cmd="find ".$GLOBALS['dataDir']."/*/*/".$GLOBALS['tmpUser_dir']."  -maxdepth 1  -size +2G -mtime -".$caduca_strict." -type f -name '*tar.gz' -exec rm -fv {} \\;";
    $cmd = "find ".$GLOBALS['dataDir']."/*/*/".$GLOBALS['tmpUser_dir']."  -maxdepth 1  -size +2G -mtime -".$caduca_strict." -type f -name '*tar.gz' ";
    if ($dry_run === false) {
        $cmd.= " -exec rm -fv {} \\;";
    }
    print "-- $cmd\n";
    system($cmd);
}

print "\n########### END DATE: ".date("Y-m-d H:i:s")." ##################\n";
