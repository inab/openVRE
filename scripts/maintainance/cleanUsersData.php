<?php
/*
 *  List Mongo Users and 
 *  delete their files according their expiration time.
 *  Delete also users with no registered files.
 *
 */

require "phplib/genlibraries.php";

/*
 * IMPORTANT:
 * - execute with sudo to be able to 'rm' www-data data : sudo php applib/cleanUsersData.php
 * - make sure that mongo 'userscol'(phplib/db.inc.php) and 'shared' global are in agreement (dev/prod)
 *
 */


$caduca_readme = 7;     // expiration for README.md files
$dry_run   = false ;     // true: evaluate files but dont delete them. false; clean files
$soft_mode = true;      // true: only README files are cleaned, consequently cleaning only not-used anon users. false: REGISTERED USERS FILES ARE CLEANED

// disk
$GLOBALS['shared']     = "/data/MuG/"; // MuGdev/";
$GLOBALS['dataDir']    = $GLOBALS['shared']."MuG_userdata/";

// db
$GLOBALS['dbname_VRE']  = "MuGVRE_irb";
require "phplib/db.inc.php";



// evaluate files for all users
$fu = $GLOBALS['usersCol']->find(array('Type' => "3"));
//$fu = $GLOBALS['usersCol']->find(array('id' => array('$in' => array("MuGANON5b9e960ec7644","MuGANON5b8e903f20dbd"))) );

// foreach user
foreach ( array_values(iterator_to_array($fu)) as $v ){

    if (!isset($v['id'])){
        print "\n-----> USER ".$v['_id']. "\n";
        print "ERROR: User ".$v['_id']. " has no MuG id\n";
        continue;
    }

    // check user object is well formed
    if (!isset($v['dataDir']) || !$v['dataDir']){
        print "\n-----> USER ".$v['_id']. "\n";
        print "ERROR: User ".$v['_id']. " has no dataDir var.";

        if ($v['Type'] == 3 ){
            // force delete user
            if ($dry_run === true) { print "Dry run ON - doing nothing\n"; continue;}
            print " Deleting user\n";
            $r = delUser($v['id'],1,true);
            if ($r === false || $r == 0  ){
                print "ERROR deleting user ".$v['id']." - ". join("; ",$_SESSION['errorData']['Error'])."\n";
                continue;
            }
        }else{
            print "Keeping malformed registered user\n";
        }
        continue;
    }

    print "\n-----> USER ".$v['_id']. "(".$v['id'].")\n";

    // set user in session
    if (isset($_SESSION)){unset($_SESSION);}
    $_SESSION['User'] = $v;

    // check dataDir exists in disk
    $rdir =  $GLOBALS['dataDir'].$v['id'];
    if (! is_dir($rdir)){
        print "ERROR: User ".$v['_id']." has no dataDir folder $rdir. ";

        if ($v['Type'] == 3 ){
            // force delete user
            if ($dry_run === true) { print "Dry run ON - doing nothing\n"; continue;}
            print " Deleting user\n";
            $r = delUser($v['id'],1,true);
            if ($r === false || $r == 0  ){
                print "ERROR deleting user ".$v['id']." - ". join("; ",$_SESSION['errorData']['Error'])."\n";
                continue;
            }
        }else{
            print "Keeping malformed registered user\n";
        }
        continue;
    }

    // get user's files/folders
    $files = getGSFilesFromDir(array('_id'=>$v['dataDir']));

    // if no files, delete user
    if ($files === false){
        print "ERROR: User ".$v['_id']." has no files nor folders registered in dataDir '".$v['dataDir']."' - ". join("; ",$_SESSION['errorData']['Error']);
        if ($v['Type'] == 3){
            // force delete user
            print " | Deleting user\n";
            if ($dry_run === true) { print "Dry run ON - doing nothing\n"; continue;}
            $r = delUser($v['id'],1,true);
            if ($r === false || $r == 0 ){
                print "ERROR deleting user ".$v['id']." - ". join("; ",$_SESSION['errorData']['Error'])."\n";
                continue;
            }
        }else{
            print " | Keeping empty registered user\n";
        }
        continue;
    }

    // delete user file's based on expiration date
    foreach ($files as $f){
        if (!isset($f['_id'])){
            continue;
        }
        if (!isset($f['type']) || $f['type'] == "file"){

            print " >> ".$f['_id']." - ".$f['path']."\n";
            $tobedeleted=false;
            $tobedeleted_evenSoftMode=false;

            // ignore if file has no expiration date
            if (!isset($f['expiration'])){
                print " >> >> ERROR: File expiration not set for '".$f['_id']."'. Doing nothing!\n";
                continue;
            }

            // delete READMEs based on shorten expiration dates (mtime + 'caduca_readme')
            if (preg_match('/README.md/',$f['path']) && $v['Type'] == 3 ){

                // ignore files without mtime
                if (!isset($f['mtime'])){
                    print ">> >> ERROR: README '".$f['_id']."' has not mtime. Cannot infer expiration date. Doing nothing!";
                // check expiration based on mtime
                }else{
        			$time_mtime  = strftime('%Y/%m/%d %H:%M', $f['mtime']);
                    $daysold     = intval(( time() - $f['mtime'] ) / (24 * 3600));
                    $days2expire_readme = $caduca_readme - intval(( time() - $f['mtime'] ) / (24 * 3600));

                    if ($days2expire_readme < 0){
                        print " >> >> README has expirated | Mtime = $time_mtime | Days old = $daysold | Days overpassed = $days2expire_readme ";
                        $tobedeleted=true;
                        $tobedeleted_evenSoftMode=true;
                    // file is not expired    
                    }else{
                        print " >> >> README in vigor | Mtime $time_mtime | Days left = $days2expire_readme days";
                    }
                }

                
            // delete user files based on standard expiration dates
            }else{

                // ignore files that have expiration -1
                if (!is_object($f['expiration']) &&  $f['expiration'] == -1){
                    if (is_file($GLOBALS['dataDir']."/".$f['path'])){
                        print " >> >> File has expiration=-1. Doing nothing";
                    }else{
                        // file not in disk, force delete
                        print " >> >> File has expiration=-1 but not in disk |";
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
                            print " >> >> File has expirated | Mtime = $time_mtime | Exp date = $time_exp | Days overpassed = $days2expire | ";
                            $tobedeleted=true;
                        }else{
                            // file not in disk, force delete
                            print " >> >> File has expired and not in disk | Mtime = $time_mtime | Exp date = $time_exp | Days overpassed = $days2expire | ";
                            $tobedeleted=true;
                            $tobedeleted_evenSoftMode=true;
                        }
                    // file is not expired    
                    }else{
                        print " >> >> File in vigor | Mtime $time_mtime | Exp date = $time_exp | Days left = $days2expire days. Doing nothing";
                    }
                }
            }

            // delete file if required
            if (($tobedeleted === true && $soft_mode=== false) || $tobedeleted_evenSoftMode === true ){
                print " Deleting it!\n";
                if ($dry_run === true) { print "Dry run ON - doing nothing\n"; continue;}
                $r = deleteFiles($f['_id'],true); 
                if ($r === false){
                    print " >> >> ERROR deleting file ".$f['path']." - ". join("; ",$_SESSION['errorData']['Error'])."\n";
                    unset($_SESSION['errorData']['Error']);
                    continue;
                }else{
                    print " >> >> File successfully deleted\n";
                }
            }else{
                print " Doing nothing\n";
            }
        }
    }

    // deleting anon users with no data
    if ($v['Type'] != 3){
        print "Registered user. Keeping user\n";
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
            print "ERROR deleting user ".$v['id']."\n";
            var_dump($_SESSION['errorData']);
            continue;
       }

    }else{
        print "User has ".$num_files." files. Keeping user\n";
    }

}
