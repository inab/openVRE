<?php
/*
 * 
 */

require __DIR__."/../../config/bootstrap.php";

redirectOutside();

//
// Check operation and input files

if (!isset($_REQUEST['op'])) {
	header("location:../workspace/");
}
if (!isset($_REQUEST['fn']) && !isset($_REQUEST['fnPath']) && !preg_match('/cancelJob/',$_REQUEST['op']) ) {
	$_SESSION['errorData']['Error'][] = "Selected operation ('".$_REQUEST['op']."') requires at least one file. Any file name received.";
	header("location:../workspace/");
}
if (is_array($_REQUEST['fn']) && $_REQUEST['op'] != 'moveFiles')
	$_REQUEST['fn']=$_REQUEST['fn'][0];
    	
$fileData = $GLOBALS['filesCol']->findOne(array('_id' => $_REQUEST['fn'], 'owner' => $_SESSION['User']['id']));
$fileMeta = $GLOBALS['filesMetaCol']->findOne(array('_id' => $_REQUEST['fn']));
$filePath = getAttr_fromGSFileId($_REQUEST['fn'],'path'); 
$rfn      = $GLOBALS['dataDir']."/$filePath";

// user project directory
$userPath = getAttr_fromGSFileId($_SESSION['User']['dataDir'],"path");

//
// Process operation

if (isset($_REQUEST['op'])){
  switch ($_REQUEST['op']) {

	case 'newFolder':
		$r = createGSDirBNS($GLOBALS['filesCol'],$_REQUEST['fn']);
		if ($r == 0){
			mkdir ($rfn,0777);
			chmod($rfn, 0777);
		}
		break;

	case 'downloadFile' :
		set_time_limit(0); 
		ini_set('memory_limit', '512M');

		if(empty($rfn) || !file_exists($rfn) ){
			$_SESSION['errorData']['Error'][] = "File ".$_REQUEST['fn']." not found in disk anymore or empty <a href=\"javascript:location.reload();\">[ OK ]</a>";
			break;
		}
		downloadFile($rfn);
		exit(0);
		break;

	case 'downloadAll':

		$newName= "files.tar.gz";
		$tmpZip = $GLOBALS['dataDir']."/".$userPath."/".$GLOBALS['tmpUser_dir']."/".basename($newName); 

		$fls = "";

		foreach( $_GET['fn'] as $v ) {
			if($v !== 'undefined'){
				$filePath2 = getAttr_fromGSFileId($v,'path'); 
				$relpath = implode("/", array_slice(split('/',$filePath2), 0, -1));
				$filnam = end(split('/',$filePath2));
				$rfn2      = $GLOBALS['dataDir']."/$relpath";
				$fls .= "-C $rfn2 $filnam ";
				
			}
		}

		$cmd = "/bin/tar czf $tmpZip $fls 2>&1";

		exec($cmd,$output);
		if ( !is_file($tmpZip) ){
			$_SESSION['errorData']['Error'][] = "Uncompressed file not created.";
			if ($output)
				$_SESSION['errorData']['Error'][] = implode(" ", $output)."</br> <a href=\"javascript:location.reload();\">[ OK ]</a>";
			break;
		}
		downloadFile($tmpZip);
		unlink($tmpZip);
		exit(0);
		break;

	case 'downloadtgz' :
		if (filetype($rfn) != 'dir') {
			$_SESSION['errorData']['Error'][] = "Cannot tar ".$_REQUEST['fn']." File is not a directory";
			break;
		}
		$newName= $_REQUEST['fn'].".tar.gz";
		$tmpZip = $GLOBALS['dataDir']."/".$userPath."/".$GLOBALS['tmpUser_dir']."/".basename($newName); 

		$cmd = "/bin/tar -czf $tmpZip -C $rfn .  2>&1";
		exec($cmd,$output);
		if ( !is_file($tmpZip) ){
			$_SESSION['errorData']['Error'][] = "Uncompressed file not created.";
			if ($output)
				$_SESSION['errorData']['Error'][] = implode(" ", $output)."</br> <a href=\"javascript:location.reload();\">[ OK ]</a>";
			break;
		}
		downloadFile($tmpZip);
		unlink($tmpZip);
		exit(0);
		break;

	case 'openPlainFileEdit':
		// read the textfile
		$text = file_get_contents($rfn);
		?>
		<form action="" method="post">
			<textarea style="border:2px solid #92b854; background-color: #fefbfa;"  cols=150 rows=37 name="text"><?php echo htmlspecialchars($text) ?></textarea>
			</br>
			<input type="submit" value="Save edited file"/>
			<input type="reset" />
		</form>
		<?php
		exit;
		break;

	case 'openPlainFile':
		$fileInfo = pathinfo($rfn);
		$contentType = "text/plain";
		$fileExtension = $fileInfo['extension'];
		$content_types_list = mimeTypes();
		if (array_key_exists($fileExtension, $content_types_list))
			$contentType = $content_types_list[$fileExtension];

		if (!$fileData && !preg_match('/\.log/',$rfn) ){
            		break;
	      	}
        	if (!is_file($rfn) || !filesize($rfn)){
	        	$_SESSION['errorData']['error'][]= "'".basename($rfn). "' does not exist anymore or is empty. <a href=\"javascript:deleteMesg('".urlencode($_REQUEST['fn'])."')\">[ Delete ]</a> <a href=\"workspace/workspace.php\">[ OK ]</a>";
        		 break;
		}
		header('Content-Type: ' . $contentType);
		header("Accept-Ranges: bytes");
		header("Access-Control-Allow-Methods:GET, HEAD, DNT");
		header("Content-Disposition: inline; filename=".basename($rfn));
		header("Content-Length: ".filesize($rfn));
		print passthru("/bin/cat \"$rfn\"");
		exit(0);
		break;
	
	case 'openPlainFileFromPath':
		if (!$_REQUEST['fnPath']){
			$_SESSION['errorData']['Error'][]="Cannot open file. Variable 'fnPath' not received. Please, try it latter or mail <a href=\"mailto:helpdesk@multiscalegenomics.eu\">helpdesk@multiscalegenomics.eu</a>";
			break;	
        	}
        	if (preg_match('/^\//',$_REQUEST['fnPath']))
    			$rfn = $_REQUEST['fnPath'];
	        else
        	    $rfn = $GLOBALS['dataDir']."/".$_REQUEST['fnPath'];

		$fileInfo = pathinfo($rfn);
		$contentType = "text/plain";
		$fileExtension = $fileInfo['extension'];
		$content_types_list = mimeTypes();
		if (array_key_exists($fileExtension, $content_types_list))
			$contentType = $content_types_list[$fileExtension];

		//if (!$fileData && !preg_match('/\.log/',$rfn) ){
	        //    		break;
        	//}

	        if (!is_file($rfn) || !filesize($rfn)){
	        	$_SESSION['errorData']['error'][]= "'".basename($rfn). "' does not exist anymore or is empty. <a href=\"javascript:deleteMesg('".urlencode($_REQUEST['fn'])."')\">[ Delete ]</a> <a href=\"workspace/workspace.php\">[ OK ]</a>";
        		 break;
		}

		header('Content-Type: ' . $contentType);
		header("Accept-Ranges: bytes");
		header("Access-Control-Allow-Methods:GET, HEAD, DNT");
		header("Content-Disposition: inline; filename=".basename($rfn));
		header("Content-Length: ".filesize($rfn));
		print passthru("/bin/cat \"$rfn\"");
		exit;

	case 'deleteAll':
    case 'deleteSure':
        $r = deleteFiles($_REQUEST['fn']);
        break;

	case 'deleteDirOk':

        if (basename($filePath) == "uploads" || basename($filePath) == "repository" ){
			$_SESSION['errorData']['error'][]="Cannot delete structural directory '$filePath'.";
            break;
        }
        $r = deleteGSDirBNS($_REQUEST['fn']);

		if ($r == 0){
			$_SESSION['errorData']['error'][]="Cannot delete directory '$filePath' file from repository";
            break;
        }
        exec ("rm -r \"$rfn\" 2>&1",$output);
		if (error_get_last()){
			$_SESSION['errorData']['error'][]=implode(" ",$output);
        }
		break;


	case 'cancelJobPids':
		$r = delJob($_REQUEST['pids']);
		break;

	case 'cancelJobDir':
		$jobList=Array();
		$jobData = getUserJobs($_SESSION['userId']);
		if (count($jobData)){
	  		foreach ($jobData as $data){
				$filesId = (!is_array($data['out'])? Array($data['out']):$data['out']);
	  			foreach ($filesId as $fileId){
					if (isset($fileId) && preg_match('/^'.preg_quote($_REQUEST['fn'],'/').'/',$fileId) ){
						array_push($jobList,$fileId);
						continue;
					}
				}
			}
		}
		if (count($jobList)==0 ){
			$_SESSION['errorData']['error'][]= "Cannot cancel tasks from ".$_REQUEST['fn'].". Not submited jobs found. Have they just finished? <a href=\"".$GLOBALS['managerDir']."/workspace.php\">[ OK ]</a>";
		}else{
			$dirFiles = "<li>- ". implode('</li><li>- ', array_map('basename', $jobList))."</li>";
			$_SESSION['errorData']['error'][]   = "Are you sure you want to cancel all jobs from project '".basename($_REQUEST['fn'])."'? <a href=\"".$GLOBALS['managerDir']."/workspace.php?op=cancelJobDirSure&fn=" . urlencode($_REQUEST['fn']). "\">[ Yes, I'm sure ]</a> <a href=\"".$GLOBALS['managerDir']."/workspace.php\">[ Cancel ]</a> <ul>$dirFiles</ul>";
		}
		break;

	case 'cancelJob':
		$_SESSION['errorData']['error'][] = "Are you sure you want to cancel job for file '" . basename($_REQUEST['fn']) . "'? ";
		$pid = $_REQUEST['pid'];
		//$pid = getPidFromOutfile($_REQUEST['fn']);
		$jobInfo= getRunningJobInfo($pid);
		$SGE_updated = getUserJobs($_SESSION['userId']);
		$succList = "";
		if (isset($jobInfo['jid_successor_list'])){
			foreach (explode(",",trim($jobInfo['jid_successor_list'])) as $pidSucc ){
				if (isset($SGE_updated[$pidSucc])){
					if (!is_array($SGE_updated[$pidSucc]['out']))
						$outSucc =  $SGE_updated[$pidSucc]['out'];
					else
						$outSucc =  $SGE_updated[$pidSucc]['out'][0];
					$succList .= "<li>- ".basename($outSucc)."</li>";
            			}
			}
		}
		if ($succList){
			$_SESSION['errorData']['error'][] .= "The following execution dependencies will also be cancelled:<ul>$succList<ul/>";
		}
		$_SESSION['errorData']['error'][].= "<a href=\"".$GLOBALS['managerDir']."/workspace.php?op=cancelJobSure&fn=" . $_REQUEST['fn'] . "\">[ Yes, I'm sure ]</a> <a href=\"".$GLOBALS['managerDir']."/workspace.php\">[ Cancel ]</a>";
		break;

	case 'cancelJobSure':
		
		$r = delJob($_REQUEST['pid']);
        if (!$r){
            $_SESSION['errorData']['Error'][]= "Cannot cancel task. Unsuccessfully exit of 'deljob' for job $pid.";
        }
        //$r = delJobFromOutfiles($_REQUEST['fn']);
		break;

	case 'cancelJobDirSure':
		$jobList=Array();
        $jobData = getUserJobs($_SESSION['User']['_id']);
        $delJobs_ok = 1;

		if (count($jobData)){
	  	    foreach ($jobData as $jobId =>$data){
			    if ($data['output_dir'] == $rfn){
                    $r = delJob($jobId);
                    if (!$r){
                        $_SESSION['errorData']['Error'][]= "Cannot cancel '".$jobData["execution"]."' task. Unsuccessfully exit of 'deljob' for job $pid.";
                        $delJobs_ok=0;
                        continue;
                    }

			    }
		    }
		}
		if ($delJobs_ok && count($fileData['files'])==0 && !isset($_SESSION['errorData']['SGE'])){
		    $r = deleteGSDirBNS($_REQUEST['fn']);
		    if ($r == 0)
	        	    break;
            exec ("rm -r \"$rfn\" 2>&1",$output);
		    $_SESSION['errorData']['Error'][]=implode(" ",$output);
		}
		break;


	case 'close':
		session_destroy();
		redirect($GLOBALS['homeURL']);
		break;

	case 'unzip':
	case 'untar':
		$ext	  = pathinfo($filePath, PATHINFO_EXTENSION);
		$extClean = preg_replace('/_\d+$/',"",$ext);
		$fn_Tmp   = str_replace(".$ext","",$filePath);
		$proj_dir = dirname($rfn);
		$rfn_Tmp  = "$proj_dir/".basename($fn_Tmp);
		$cmd      = "";

        switch ($extClean) {
            case 'dsrc':
                $cmd ="";
			case 'tar':
				#touch option force tar to update uncompressed files atime - required by the expiration time
				$cmd = "tar --touch -xf \"" . $rfn . "\" 2>&1";
				break;
            case 'zip':
                list($files_compressed,$zip_output) = indexFiles_zip($rfn);
                //check zip content 
                if (count($files_compressed) == 0){
                    $_SESSION['errorData']['Error'][]="No files found in given ZIP file '".basename($rfn)."'";
                    $_SESSION['errorData']['Error'][].= implode("</br>", $zip_output)."</br>";
                    break;
                // do not accept more than 1 file
                }elseif (count($files_compressed) > 1){
                    $_SESSION['errorData']['Error'][]="File '".basename($rfn)."' contains more than one file. Extraction not supported.";
                    $zip_output = str_replace("  ","&ensp;&ensp;",$zip_output);
                    $_SESSION['errorData']['Error'][].= implode("</br>", $zip_output)."</br>";
                    break;
                // do not accept subfolders
                }else{
                    $err=0;
                    foreach ($files_compressed as $name => $info){
                        if (preg_match('/\//',$name)){
                            $_SESSION['errorData']['Error'][]="File '".basename($rfn)."' contains subfolders. Only ZIPs without directory structures are supported.";
                            $zip_output = str_replace("  ","&ensp;&ensp;",$zip_output);
                            $_SESSION['errorData']['Error'][].= implode("</br>", $zip_output)."</br>";
                            $err=1;
                            break;
                        }
                    }
                    if ($err){break;}
                }
                $cmd = "unzip -o \"" . $rfn . "\" -d $proj_dir 2>&1";
                
                /*  // unzip certain file from zip
                $file_compressed_names =  array_keys($files_compressed);
                $cmd = "unzip -j \"$rfn\"  \"".$file_compressed_names[0]."\"  -d \"$proj_dir\"  2>&1";
                 */
				break;
			case 'bz2':
				$cmd = "bzip2 -d \"" . $rfn . "\" 2>&1";
				break;
			case 'gz':
				$cmd = "gunzip -S .$ext -f \"" . $rfn . "\" 2>&1";
				break;
			case 'tgz':
				/*
				//warning compressed subfolders will be merged
				if (!isset($_REQUEST['sure'])){
					exec("tar -tf \"$rfn\"", $output);
					var_dump($output);
					$subfolders = preg_grep('/\/$/',$output);
					if (count($subfolders)> 1 || count($output)> 10 ){
						$msg ="File '".basename($filePath)."' contains '".count($output)."' elements:</br>";
						
						for ($i=0; $i<count($output); $i++){
							$out = $output[$i];
							$lev = preg_match_all('/\//i', $out, $m);
							for ($n=0; $n<=$lev;$n++){ $msg .= "&ensp;&ensp; ";}
							$msg .= " $out</br>";
							if ($i>19){
								$msg .= "&ensp;(...)</br>";
								break;
							}
						}
						$msg .= "All files will be directly extracted into '".basename($proj_dir)."' folder, without considering the tree file structure of the TAR file. Are you sure you want to uncompress '".basename($filePath)."'? <br/> ";	
						$msg .= "<a href=\"".$_SERVER[REQUEST_URI]."&sure=1\">[ Sure ]</a> &ensp;";
						$msg .= "<a href=\"workspace/\">[ Cancel ]</a> &ensp;";
						$_SESSION['errorData']['Warning'][] = $msg;
						break;
					}
				}
				*/
				$cmd = "tar --touch -xzf \"$rfn\" -C \"$proj_dir\" 2>&1";
				break;
			default:
				$_SESSION['errorData']['error'][] = "Cannot uncompress $extClean file. Method not supported";
				
		}

		if ($cmd){
			exec($cmd, $output);

            //$_SESSION['errorData']['Error'][] = "CMD = $cmd";

			if (is_file($rfn_Tmp)){
				$insertData = array(
					'_id'   => $_REQUEST['fn'],
					'owner' => $_SESSION['User']['id'],
					'size'  => filesize($rfn_Tmp),
					'mtime' => new MongoDB\BSON\UTCDateTime(filemtime($rfn_Tmp)*1000),
					'path'  => $fn_Tmp
				);
				$insertMeta = $fileMeta;
				$insertMeta['compress'] = 0;
	
				$r = uploadGSFileBNS($fn_Tmp,$rfn_Tmp,$insertData,$insertMeta,FALSE);
				if ($r == 0)
					break;

				if (is_file($rfn)){
					unlink($rfn);
				}
	
			}elseif (is_dir($rfn_Tmp) ){
				/*
				exec("find $rfn_Tmp -type f 2>&1",$rfn_Tmp_files);
				foreach ($rfn_Tmp_files as $f){
					$f_new = "$proj_dir/".basename($f);
					exec("mv $f $proj_dir 2>&1",$output);
					if (!is_file($f_new)){
						$_SESSION['errorData']['error'][]=" Error inflating ".basename($filePath).". File '$f' contained inside '".basename($filePath)."' cannot be moved to $f_new. Cannot uncompress directory</br>";
						unlink($rfn_Tmp);
						break;
					
					}
					//TODO register each of the files individually. Which metadata?
				}
				*/
				$_SESSION['errorData']['Error'][]=" Error inflating ".basename($filePath).". Directories cannot be uncompressed </br>";
				unlink($rfn_Tmp);
		
			}else{
				$_SESSION['errorData']['Error'][]= "Error wile uncompressing ".basename($filePath).". Outfile not created.<br/>";
				if ($output)
					$_SESSION['errorData']['Error'][].= implode("</br>", $output)."</br>";
			}
		}
		unset($_REQUEST['op']);
		break;

	case 'zip':
		$rfn_TmpZip = dirname($rfn)."/".basename($rfn).".gz";
		$fn_TmpZip  = dirname($filePath)."/".basename($filePath).".gz";

		$cmd = "gzip -f \"$rfn\" 2>&1";
		exec($cmd, $output);
		

		if (file_exists($rfn_TmpZip)){

	    		$insertData=array(
	        	   '_id'   => $_REQUEST['fn'],
		           'owner' => $_SESSION['User']['id'],
		           'size'  => filesize($rfn_TmpZip),
		           'path'  => $fn_TmpZip,
		           'mtime' => new MongoDB\BSON\UTCDateTime(filemtime($rfn_TmpZip)*1000)
			);


			$insertMeta = $fileMeta;
			$insertMeta['compressed'] = "zip";
			
			$r = uploadGSFileBNS($fn_TmpZip,$rfn_TmpZip,$insertData,$insertMeta,FALSE);
			if ($r == 0)
				break;

//			$r = deleteGSFileBNS($_REQUEST['fn']);
//			if ($r == 0)
//				break;

		}else{
			$_SESSION['errorData']['error'][] = "Compressed ZIP file not created.";
			if ($output)
				$_SESSION['errorData']['error'][] .= implode(" ", $output)."</br> <a href=\"javascript:history.go(-1)\">[ OK ]</a>";
		}
		unset($_REQUEST['op']);
		break;

	case 'tar':
//		$cmd = "tar --touch  -cf \"" . $_REQUEST['fn'] . ".tar\" \"" . $_REQUEST['fn'] . "\" 2>&1";
//		chdir($_SESSION['curDir']);
//		exec($cmd, $output);
//		$_SESSION['errorData']['error'][] = implode(" ", $output);
//		chdir($_SESSION['User']->dataDir);
        break;


    case 'moveFiles':
    case 'moveFile':
        if (!isset($_REQUEST['target'])){
		    print('{"error":true, "msg": "Error while moving file. Target path not given."}');die();
            break;
        }

        // Move file/s
        $r = moveFiles($_REQUEST['fn'],$_REQUEST['target']);
                
        if ($r === FALSE){
            $msg = printErrorData();
			print('{"error":true, "msg": "'.$msg.'"}');die();
        }else{
            $_SESSION['errorData']['Info'][]="File/s successfully moved!!";
            print('{"error":false, "msg": "File/s successfully moved!"}');die();
        }

        break;


    case 'moveDir':
        if (!isset($_REQUEST['target'])){
            $_SESSION['errorData']['Error'][]="Error while moving directory. Target path not given.";
						print('{"error":true, "msg": "Error while moving directory. Target path not given."}');die();
            break;
        }
        $rfn_target = $GLOBALS['dataDir']."/".$_REQUEST['target'];

        // Move file in mongo
        $r = moveGSDirBNS($filePath,$_REQUEST['target']);
                
        if ($r == "0"){
            $_SESSION['errorData']['Error'][]="Error while moving directory";
						print('{"error":true, "msg": "Error while moving directory"}');die();
            break;
        }
        // Move dir in disk
        rename($rfn,$rfn_target);
        if (!is_dir($rfn_target)){
            $_SESSION['errorData']['Error'][]="Error while writting moved directory";
            break;
        }
        $_SESSION['errorData']['Info'][]="Directory successfully moved!";
			print('{"error":false, "msg": "Directory successfully moved!"}');die();
        break;
    case 'moveDirAll':
        break;
  }
}

header("location:../workspace/");
//redirect($GLOBALS['BASEURL']."/workspace/");



//scan disk to upload Mongo 
// TODO move function upper, for syncronizing before showing the table but after processingPendingJobs
//syncWorkDir2Mongo($GLOBALS['dataDir']."/".$_SESSION['curDir']);

?>
