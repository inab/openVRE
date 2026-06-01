<?php

use League\OAuth2\Client\Token\AccessToken;
use OpenVRE\LoggerFactory;
use OpenVRE\NotFoundException;
use OpenVRE\Oauth2Provider;
use OpenVRE\UserType;
use OpenVRE\VaultClient;


function getProjectLogger()
{
	static $logger = null;

	if ($logger === null) {
		$logger = LoggerFactory::getLogger('Project interface');
	}

	return $logger;
}

function prepUserWorkSpace($homeDir, $projectDir, $sampleData = "", $projectData = array(), $verbose = false, $asRoot = 0)
{
	// set current directory
	$_SESSION['curDir'] = $homeDir;

	// set sampleData default
	if (empty($sampleData)) {
		$sampleData = $GLOBALS['sampleData_default'];
	}

	if (empty($projectDir)) {
		getProjectLogger()->error("Cannot create user workspace $homeDir. No project directory name given.");
		throw new UnexpectedValueException("Cannot create user workspace $homeDir. No project directory name given.");
	}

	if (empty($projectData)) {
		$projectData  = array(
			"name"	 => $GLOBALS['project_default'],
			"description"  => "This is my first VRE project, automatically created when entering into '" . $GLOBALS['NAME'] . "'. It is going to include my first data and executions.",
			"keywords"     => "default"
		);
	}

	// create worskspace data
	$dataDirId = setUserWorkSpace($homeDir, $projectDir, $projectData, $sampleData, $verbose, $asRoot);

	return $dataDirId;
}

function setUserWorkSpace($homeDir, $projectDir, $projectData, $sampleData, $verbose = false, $asRoot = 0)
{
	getProjectLogger()->info("Preparing user workspace named '$homeDir' with sample data '$sampleData'");

	//creating user home directory
	if (!is_dir($GLOBALS['dataDir']) || !is_writable($GLOBALS['dataDir'])) {
		getProjectLogger()->error("Cannot access VRE data. Make sure data device is accessible and writable.");
		throw new UnexpectedValueException("Cannot access VRE data. Make sure data device is accessible and writable.");
	}

	$homeDirP  = $GLOBALS['dataDir'] . "/$homeDir";
	$homeDirId = getGSFileId_fromPath($homeDir, $asRoot);
	if (! isGSDirBNS($GLOBALS['filesCol'], $homeDirId) || ! is_dir($homeDirP)) {
		$homeDirId  = createGSDirBNS($homeDir, 1);
		getProjectLogger()->info("Creating main user directory: $homeDirP ($homeDirId)");
		addMetadataToFile($homeDirId, array(
			"expiration" => -1,
			"description" => "Root user data"
		));

		if (!is_dir($homeDirP)) {
			mkdir($homeDirP, 0775);
		}
	}

	$GLOBALS['filesCol']->updateOne(
		array('_id' => $homeDirId),
		array(
			'$set' => array(
				'lastAccess' => moment()
			)
		)
	);

	// creating user workspace for given project
	$dataDir   = "$homeDir/$projectDir";
	$dataDirP  = $GLOBALS['dataDir'] . "/$dataDir";
	$dataDirId = getGSFileId_fromPath($dataDir, $asRoot);

	if (! isGSDirBNS($GLOBALS['filesCol'], $dataDirId) || ! is_dir($dataDirP)) {
		//creating project directory
		$dataDirId = createProjectDir($dataDir, $dataDirP, $projectData, $asRoot);
		getProjectLogger()->info("Creating project directory: $dataDirP ($dataDirId)");

		if (!empty($sampleData)) {
			//creating uploads directory
			$upDirId  = createGSDirBNS($dataDir . "/uploads", 1);
			getProjectLogger()->info("Creating uploads directory: $dataDir/uploads ($upDirId)");
			addMetadataToFile($upDirId, array(
				"expiration" => -1,
				"description" => "Uploaded personal data"
			));

			if (!is_dir("$dataDirP/uploads")) {
				mkdir("$dataDirP/uploads", 0775);
			}

			//creating repository directory
			$repDirId  = createGSDirBNS($dataDir . "/repository", 1);
			getProjectLogger()->info("Creating repository directory: $dataDir/repository ($repDirId)");
			addMetadataToFile($repDirId, array(
				"expiration" => -1,
				"description" => "Remote personal data"
			));

			if (!is_dir("$dataDirP/repository")) {
				mkdir("$dataDirP/repository", 0775);
			}

			// injecting sample data
			setUserWorkSpace_sampleData($sampleData, $dataDir, $verbose);
		}
	}

	$GLOBALS['filesCol']->updateOne(
		array('_id' => $dataDirId),
		array(
			'$set' => array(
				'lastAccess' => moment()
			)
		)
	);

	return $dataDirId;
}


function setUserWorkSpace_sampleData($sampleName, $dataDir)
{
	$sampleData = getSampleData($sampleName);
	if (is_null($sampleData)) {
		getDataLogger()->error("No dataset named '$sampleName' found.");
		throw new NotFoundException("No dataset named '$sampleName' found.");
	}

	// validate sample Data integrity
	$datafolders = scanDir($GLOBALS['sampleDataPath'] . "/" . $sampleData['sample_path']);
	if (!in_array("uploads", $datafolders)) {
		getDataLogger()->error("Sample data '" . $sampleData['name'] . "' has no 'uploads' folder");
		throw new UnexpectedValueException("Sample data '" . $sampleData['name'] . "' has no 'uploads' folder");
	}

	$metadataPath = $GLOBALS['sampleDataPath'] . "/" . $sampleData['sample_path'] . "/.sample_metadata.json";
	if (!is_file($metadataPath)) {
		getDataLogger()->error("Sample data '" . $sampleData['name'] . "' has no metadata (.sample_metadata.json) to load -> $metadataPath ");
		throw new UnexpectedValueException("Sample data '" . $sampleData['name'] . "' has no metadata (.sample_metadata.json) to load -> $metadataPath ");
	}

	// read sample Data metadata
	$metadataArray = json_decode(file_get_contents($metadataPath), true);
	foreach ($metadataArray as $metadata) {
		if (is_null($metadata['path'])) {
			getDataLogger()->warning("Wrong sample data '" . $sampleData['name'] . "' metadata contains elements without 'path' attribute. Ignoring them.");
			continue;
		}

		save_fromSampleDataMetadata($metadata, $dataDir, $sampleName, "folder");

		// TODO: check if it is necessary
		// looking for files in the folder 
		$sampleDataPath = $GLOBALS['sampleDataPath'] . "/" . $sampleData['sample_path'] . "/" . $metadata['path'];
		$metaFilePath  = "$sampleDataPath/.sample_metadata.json";

		if (!is_file($metaFilePath)) {
			getDataLogger()->warning("Sample data '" . $sampleData['name'] . "' has no metadata in $sampleDataPath to load. Empty directory.");
			continue;
		}

		$metadataArray = json_decode(file_get_contents($metaFilePath), true);
		if (count($metadataArray) == 0) {
			getDataLogger()->warning("Sample data '" . $sampleData['name'] . "' has malformated json in folder '$sampleDataPath'");
			continue;
		}

		foreach ($metadataArray as $meta_file) {
			if (is_null($meta_file['path'])) {
				getDataLogger()->warning("Sample data '" . $sampleData['name'] . "' contains elements without 'path' attribute. Ignoring them.");
				continue;
			}

			save_fromSampleDataMetadata($meta_file, $dataDir, $sampleName, "file");
		}
	}

	getDataLogger()->info("Sample data '" . $sampleData['name'] . "' successfully injected into user workspace.");
}


function save_fromSampleDataMetadata($metadata, $dataDir, $sampleName, $type)
{
	if (isset($metadata['mongo']) && $metadata['mongo'] === false) {
		return;
	}

	$sampleData = getSampleData($sampleName);
	$sampleDataPath = $GLOBALS['sampleDataPath'] . "/" . $sampleData['sample_path'] . "/" . $metadata['path'];
	$dataDirPath = $GLOBALS['dataDir'] . "/$dataDir";
	$userDataPath = $dataDirPath . "/" . $metadata['path'];

	// Saving to disk
	if ($type == "file") {
		if (!is_file($sampleDataPath)) {
			if (is_dir($sampleDataPath)) {
				getProjectLogger()->error("Sample data file '" . $metadata['path'] . "' is a subfolder. Not supported. Ignoring it.");
				throw new UnexpectedValueException("Sample data file '" . $metadata['path'] . "' is a subfolder. Not supported. Ignoring it.");
			}

			getProjectLogger()->error("Sample data file '" . $metadata['path'] . "' not in Sample Data directory ($sampleDataPath). Ignoring it.");
			throw new UnexpectedValueException("Sample data file '" . $metadata['path'] . "' not in Sample Data directory ($sampleDataPath). Ignoring it.");
		}

		if (!is_file($userDataPath)) {
			copy($sampleDataPath, $userDataPath);
		}
	} elseif ($type == "folder") {
		if (!is_dir($sampleDataPath)) {
			if (is_file($sampleDataPath)) {
				getProjectLogger()->error("Sample data folder '" . $metadata['path'] . "' not grouped below any folder. Ignoring it.");
				throw new UnexpectedValueException("Sample data folder '" . $metadata['path'] . "' not grouped below any folder. Ignoring it.");
			}

			getProjectLogger()->error("Sample data folder '" . $metadata['path'] . "' not in Sample Data directory. Ignoring it.");
			throw new UnexpectedValueException("Sample data folder '" . $metadata['path'] . "' not in Sample Data directory. Ignoring it.");
		}

		if (!is_dir($userDataPath)) {
			mkdir($userDataPath, 0775);
		}

		$sampleMetadataFiles = array_filter(scandir($sampleDataPath), function ($i) {
			return preg_match('/^\.\w+/', $i);
		});

		if (count($sampleMetadataFiles)) {
			foreach ($sampleMetadataFiles as $metadataFile) {
				if ($metadataFile == ".sample_metadata.json") {
					continue;
				}

				copy($sampleDataPath . "/" . $metadataFile, $userDataPath . "/" . $metadataFile);
			}
		}
	} else {
		getProjectLogger()->error("Sample data '" . $metadata['path'] . "' cannot be injected.");
		throw new UnexpectedValueException("Sample data '" . $metadata['path'] . "' cannot be injected.");
	}


	// adapt sample data metadata
	$metadata['path'] = "$dataDir/" . $metadata['path'];
	$metadata['owner'] =  dirname($dataDir);
	$metadata['meta_data']['validated'] = true;
	if (isset($metadata['meta_data']['submission_file'])) {
		$metadata['meta_data']['submission_file'] = "$dataDirPath/" . $metadata['meta_data']['submission_file'];
	}

	if (isset($metadata['meta_data']['log_file'])) {
		$metadata['meta_data']['log_file'] = "$dataDirPath/" . $metadata['meta_data']['log_file'];
	}

	if (isset($metadata['meta_data']['associated_files'])) {
		$associatedFileIds = [];
		foreach ($metadata['meta_data']['associated_files'] as $associatedFile) {
			$assocPath = "$dataDir/$associatedFile";
			$assocId = getGSFileId_fromPath($assocPath, 1);
			array_push($associatedFileIds, $assocId);
		}

		$metadata['meta_data']['associated_files'] = $associatedFileIds;
	}

	// register sample data
	$fileId = getGSFileId_fromPath($metadata['path'], 1);
	if (is_null($fileId)) {
		//convert metadata from MuGfile to VREfile
		[$file, $modifiedMetadata] = getVREfile_fromFile($metadata);

		//saving metadata
		if ($type == "folder") {
			$newId = createGSDirBNS($metadata['path'], 1);
			addMetadataToFile($newId, $modifiedMetadata);
			getProjectLogger()->info("Sample data imported in your workspace. New Project: '" . basename($modifiedMetadata['path']) . "'");
		} elseif ($type == "file") {
			uploadGSFileBNS($metadata['path'], $userDataPath, $file, $modifiedMetadata, false);
			if (isset($modifiedMetadata['path']) && preg_match('/uploads/', $modifiedMetadata['path'])) {
				getProjectLogger()->info("Sample data imported in your <strong>uploads</strong> folder. New File: '<strong>" . basename($metadata['path']) . "</strong>'");
			}
		}
	} else {
		getProjectLogger()->info("Sample data already in your workspace. Data '" . basename($metadata['path']));
	}
}


function getFilesToDisplay($dirSelection)
{
	$filesPending = processPendingFiles($_SESSION['User']['_id']);
	$files = getGSFilesFromDir($dirSelection, 1);

	if (empty($files)) {
		getProjectLogger()->error("Cannot update dashboard.");
		throw new UnexpectedValueException("Cannot update dashboard.");
	}

	// Filter user pending files not belonging to active project
	foreach ($filesPending as $r) {
		if ($r['project'] != $_SESSION['User']['activeProject']) {
			unset($filesPending[$r['_id']]);
		}
	}

	// Merge pending files and mongo data
	foreach ($filesPending as $r) {
		// Update $files[parentId][files]
		if (is_null($filesPending[$r['_id']]['parentDir'])) {
			getProjectLogger()->warning("Pending file " . $filesPending[$r['_id']]['path'] . " has no parentDir");
			continue;
		}

		$parentId = $filesPending[$r['_id']]['parentDir'];

		if (!isset($files[$parentId])) {
			if ($r['pending']) {
				unset($filesPending[$r['_id']]);
			} else {
				getProjectLogger()->warning("Cannot display '" . $filesPending[$r['_id']]['path'] . "'. FS inconsistency. Its parent folder ($parentId) does not exist anymore or is unaccessible.");
				unset($filesPending[$r['_id']]);
			}

			continue;
		}
		array_push($files[$parentId]['files'], $r['_id']);
	}

	getProjectLogger()->debug("Files to display: " . json_encode(array_keys($files)));
	getProjectLogger()->debug("Pending files: " . json_encode(array_keys($filesPending)));

	return array_merge($files, $filesPending);
}


function filterFiles_by_dataType($filesAll, $filter_data_types = array())
{

	// Filter files by data_types

	if ($filter_data_types || is_array($filter_data_types)) {
		$filesFiltered = array();
		$dirs_filtered = array();
		//filter out files with unselected data_types
		foreach ($filesAll as $fn => $file) {
			if (isset($file['data_type']) and  in_array($file['data_type'], $filter_data_types)) {
				$filesFiltered[$fn] = $filesAll[$fn];
				array_push($dirs_filtered, $file['parentDir']);
			}
		}
		//filter out empty dirs
		foreach ($filesAll as $fn => $file) {
			if (isset($file['parentDir']) and  in_array($file['_id'], $dirs_filtered)) {
				$filesFiltered[$fn] = $filesAll[$fn];
			}
		}
		$filesAll = $filesFiltered;
	}

	return $filesAll;
}


//add datatable tree nodes and hidden cols values
function addTreeTableNodesToFiles($filesAll)
{
	$n = 1;
	foreach ($filesAll as $r) {
		// Add Tree Nodes
		if (isset($r['files'])) {
			if (isset($filesAll[$r['_id']]['tree_id']) && $filesAll[$r['_id']]['tree_id'])
				continue;
			
			$filesAll[$r['_id']]['tree_id']     = $n;
			$filesAll[$r['_id']]['size']	= calcGSUsedSpaceDir($r['_id']);
			$filesAll[$r['_id']]['size_parent'] = $filesAll[$r['_id']]['size'];
			//$filesAll[$r['_id']]['mtime_parent'] = (isset($r['atime']) ? $r['atime']->toDateTime()->format('U') : $r['mtime']);
			$filesAll[$r['_id']]['mtime_parent'] =
				((isset($r['atime']) && ($t = $r['atime']->toDateTime()->getTimestamp()) > 0) 
					? $t
					: ((isset($r['mtime']) && $r['mtime'] > 0) 
						? $r['mtime']
						: (!empty($filesAll[$r['_id']]['mtime_parent'])
							? $filesAll[$r['_id']]['mtime_parent']
							: time())));	
			if ($filesAll[$r['_id']]['mtime_parent'] == 0) {
					$_SESSION['errorData']['Error'][] =
						"MTIME_PARENT STILL ZERO for " . $r['_id'] .
						" | atime=" . (isset($r['atime']) ? $r['atime']->toDateTime()->getTimestamp() : 'NULL') .
						" | mtime=" . ($r['mtime'] ?? 'NULL') .
						" | existing=" . ($filesAll[$r['_id']]['mtime_parent'] ?? 'NULL');
				}			
			$i = 1;
			foreach ($r['files'] as $rr) {
				$filesAll[$rr]['tree_id']       = "$n.$i";
				$filesAll[$rr]['tree_id_parent'] = $n;
				$filesAll[$rr]['size_parent']   = $filesAll[$r['_id']]['size_parent'];
				$filesAll[$rr]['mtime_parent']  = $filesAll[$r['_id']]['mtime_parent'];
				$i++;
			}
			$n++;
		} else {
			if (isset($r['pending'])) {
				$dir = $r['parentDir'];
				$filesAll[$dir]['pending'] = "true";
			}
		}
	}

	return $filesAll;
}

function printTable($filesAll = array())
{
	$autorefresh = 0;
?>

	<table id="workspace" class="display" cellspacing="0" width="100%">

		<?php
		print parseTemplate($_REQUEST, getTemplate('/TreeTblworkspace/header.htm'));

		?>
		<tbody><?php

				foreach ($filesAll as $r) {
					// is dir
					if (isset($r['files'])) {
						if (preg_match('/\/\./', $r['_id'])) {
							continue;
						}
						if (isset($r['pending'])) {
							if (basename($r['path']) == "uploads") {
								print parseTemplate(formatData($r), getTemplate('/TreeTblworkspace/TR_folder_uploads.htm'));
							} elseif (basename($r['path']) == "repository") {
								print parseTemplate(formatData($r), getTemplate('/TreeTblworkspace/TR_folder_repository.htm'));
							} else {
								print parseTemplate(formatData($r), getTemplate('/TreeTblworkspace/TR_folderPending.htm'));
							}
						} elseif (basename($r['path']) == "uploads") {
							print parseTemplate(formatData($r), getTemplate('/TreeTblworkspace/TR_folder_uploads.htm'));
						} elseif (basename($r['path']) == "repository") {
							print parseTemplate(formatData($r), getTemplate('/TreeTblworkspace/TR_folder_repository.htm'));
						} elseif (count($r['files']) == 0) {
							print parseTemplate(formatData($r), getTemplate('/TreeTblworkspace/TR_folder_empty.htm'));
						} else {
							print parseTemplate(formatData($r), getTemplate('/TreeTblworkspace/TR_folder.htm'));
						}
						// is job
					} elseif (isset($r['pending'])) {
						if ($r['pending'] == "ACTIVE SESSION") {
							print parseTemplate(formatData($r), getTemplate('/TreeTblworkspace/TR_fileInteractive.htm'));
						} else {
							print parseTemplate(formatData($r), getTemplate('/TreeTblworkspace/TR_filePending.htm'));
						}
						$autorefresh = 1;
						// is file
					} elseif (isset($r['_id'])) {
						if ($r['validated']) {
							print parseTemplate(formatData($r), getTemplate('/TreeTblworkspace/TR_file.htm'));
						} elseif (!file_exists($r['path'])) {
							print parseTemplate(formatData($r), getTemplate('/TreeTblworkspace/TR_fileDisabledRemote.htm'));
						} else {
							print parseTemplate(formatData($r), getTemplate('/TreeTblworkspace/TR_fileDisabled.htm'));
						}
					} else {
						//empty mongo entry;
					}
				}
				?>
		</tbody>

	</table>

	<?php
	if ($autorefresh) {
		print "<input type=\"hidden\" id=\"autorefresh\" value=\"$autorefresh\"/>\n";
	}

	// Convert the PHP array to JSON and output it to the browser console
	?>
	<script>
		var filesAll = <?php echo json_encode($filesAll); ?>;
		console.log(filesAll);
	</script>

<?php
}

function printLastJobs($filesAll = array())
{
	$timestamps = array();
	foreach ($filesAll as $key => $node) {
		$timestamps[$key] = $node["mtime"];
	}
	array_multisort($timestamps, SORT_DESC, $filesAll);

?>

	<ul class="feeds">
		<?php
		$wehavejobs = false;
		foreach ($filesAll as $r) {
			if (isset($r['files'])) {
				if (preg_match('/\/\./', $r['_id'])) {
					continue;
				}

				if (isset($r['pending'])) {
				} elseif ((basename($r['path']) == "uploads") || (basename($r['path']) == "repository")) {
				} elseif (isset($r['files'][0]) && !strpos($r['files'][0], "dummy")) {
					print parseTemplate(formatData($r), getTemplate('/LastJobsworkspace/LJ_folder.htm'));
					$wehavejobs = true;
				}
			} elseif (isset($r['pending'])) {
				if (basename($r['path']) != "repository") {
					print parseTemplate(formatData($r), getTemplate('/LastJobsworkspace/LJ_folderPending.htm'));
					$wehavejobs = true;
				}
			} elseif (isset($r['_id'])) {
			} else {
				//empty mongo entry;
			}
		}

		if (!$wehavejobs) echo "You have not launched any job yet.";

		?>
	</ul>

<?php
}

function getToolsByDT($data_type, $status = 1)
{
	$tl = $GLOBALS['toolsCol']->find(array('external' => true, 'status' => array('$in' => [$status, 3])));
	if ($_SESSION['User']['Type'] == UserType::ToolDev->value) {
		$tools_list = iterator_to_array($tl, false);
		foreach ($tools_list as $key => $tool) {
			if ($tool["status"] == 3 && !in_array($tool["_id"], $_SESSION['User']["ToolsDev"])) {
				unset($tools_list[$key]);
			}
		}

		$tl = $tools_list;
	}

	$arrTools = [];
	foreach ($tl as $tool) {
		if (isset($tool["input_files_combinations_internal"])) {
			$combinations = $tool["input_files_combinations_internal"];
			foreach ($combinations as $comb) {
				if (sizeof($comb) == 1) {
					foreach ($comb[0] as $k => $v) {
						if ($k == $data_type) {
							$aux = array($tool["_id"], $tool["name"]);
							$arrTools[] = $aux;
						}
					}
				}
			}
		} else if (sizeof($tool["input_files"]) == 1) {

			if (in_array($data_type, $tool["input_files"][0]["data_type"])) {

				$aux = array($tool["_id"], $tool["name"]);

				$arrTools[] = $aux;
			}
		}
	}

	return $arrTools;
}


function formatData($data)
{
	//_id id_URL
	if (is_null($data['_id'])) {
		return $data;
	}

	$data['_id_URL'] = urlencode($data['_id']);

	//mtime atime
	if (isset($data['mtime'])) {
		if (is_object($data['mtime'])) {
			$data['mtime'] = $data['mtime']->toDateTime()->format('U');
		}

		$data['mtime'] = datefmt_format(getDateTimeFormat(), $data['mtime']);
		$hoursleft = (time() - (int) $data['mtime']) / 3600;

		if ($hoursleft < 1) {
			$data['lastuploaded'] = '<span class="badge badge-success" title="File recently added"> ' . round($hoursleft, 2) . "h </span>";
		} else {
			$data['lastuploaded'] = '';
		}
	} else {
		$data['mtime'] = "";
	}

	// remote paths for time also
	if (!empty($data['remote_paths'])) {
		foreach ($data['remote_paths'] as $entry) {
			if (isset($entry['date'])) {
				$remoteMtime = $entry['date'];
				if (is_object($remoteMtime)) {
					$remoteMtime = $remoteMtime->toDateTime()->format('U');
				}
				$remoteMtimeFormatted = datefmt_format(getDateTimeFormat(), $remoteMtime);
	
				// Append to mtime display
				$data['mtime'] .= " <br><span style='font-size:10px; color:#16a085;'>[Remote: {$remoteMtimeFormatted}]</span>";
			}
		}
	}
	/* to be changed to not being = 0 for remote

	if (isset($data['atime'])) {
		if (is_object($data['atime'])) {
			$data['atime'] = $data['atime']->toDateTime()->format('U');
		}

		$data['atime'] = datefmt_format(getDateTimeFormat(), $data['atime']);
		$data['mtime'] = $data['atime'];
	}
	*/

	if (isset($data['atime'])) {

		$ts = is_object($data['atime'])
			? $data['atime']->toDateTime()->getTimestamp()
			: $data['atime'];

		if ($ts > 0) {
			$dt = new DateTime("@$ts");
			$formatted = datefmt_format(getDateTimeFormat(), $dt);
			$data['atime'] = $formatted;
			$data['mtime'] = $formatted;
		}
	}

	//format
	if (!isset($data['format']))
		$data['format'] = "";
	//type
	if (!isset($data['type']))
		$data['type'] = "file";
	//expiration
	if (isset($data['expiration'])) {
		if (!is_object($data['expiration']) && $data['expiration'] == -1) {
			$data['expiration'] = "File/folder does not expire";
		} else {
			if (is_object($data['expiration'])) {
				$data['expiration'] = $data['expiration']->toDateTime()->format('U');
			}

			$days2expire = intval(($data['expiration']  - time()) / (24 * 3600));
			$data['expiration'] = datefmt_format(getDateTimeFormat(), $data['expiration']);
			if ($days2expire < 7) {
				$data['expiration'] = $data['expiration'] . "( in <span style=\"color:#b30000;font-weight:bold;\">" . $days2expire . "</span> days)";
			} else {
				$data['expiration'] = $data['expiration'] . "( in $days2expire days)";
			}
		}
	} else {
		$data['expiration'] = "No expiration date";
	}

	//size
	if (isset($data['files']) && is_null($data['size'])) {
		$data['size'] = calcGSUsedSpaceDir($data['_id']);
	}
	if (isset($data['size']) && is_numeric($data['size'])) {
		$sz = 'BKMGTP';
		$factor = floor((strlen($data['size']) - 1) / 3);
		$data['size']	= sprintf("%.2f %s", $data['size'] / pow(1024, $factor), @$sz[$factor]);
	} else {
		$data['size'] = "";
	}
	//size for remote_paths

	if (!empty($data['remote_paths'])) {
		foreach ($data['remote_paths'] as $entry) {
			if (isset($entry['size']) && is_numeric($entry['size'])) {
				$factor = floor((strlen($entry['size']) - 1) / 3);
				$remoteSizeFormatted = sprintf("%.2f %s", $entry['size'] / pow(1024, $factor), @$sz[$factor]);
	
				// Append on a new line
				$data['size'] .= "<br><span style='font-size:10px; color:#16a085;'>[Remote: {$remoteSizeFormatted}]</span>";
			}
		}
	}


	//execution dir
	if (isset($data['parentDir'])) {
		$data['parentDir'] = getAttr_fromGSFileId($data['parentDir'], 'path');
		if (is_null($data['parentDir'])) {
			$_SESSION['errorData']['Warning'][] = "Accessing data not belonging to your account! Some permission issues may arise";
		}
		if ($data['type'] == "file") {
			$parentDir_explode = explode("/", $data['parentDir']);
			$executionName = array_pop($parentDir_explode);
		} else {
			$path_explode = explode("/", $data['path']);
			$executionName = array_pop($path_explode);
		}
		if ($executionName == 'uploads') {
			$executionName = "<span style='display:none;'>0</span>uploads";
			$data['longexecutionname'] = 'uploads';
			$data['short_execution'] = "<span style='display:none;'>0</span>uploads";
		} else {
			$data['short_execution'] = maxlength(basename($executionName), 15);
			$data['longexecutionname'] = $executionName;
		}
		$data['execution'] = $executionName;
	}

	//project name
	$p_code = "";
	if (preg_match('/\/(__PROJ[^\/]*)/', $data['path'], $match)) {
		$p_code = $match[1];
	}
	$p = getProject($p_code);
	$data['project'] = $p['name'];

	// description
	if (isset($data['description']) && strlen($data['description']) > 50) {
		$data['description'] = substr($data['description'], 0, 50) . '...';
	}

	//filename
	if (isset($data['pending'])) {
		if (is_null($data['files'])) {
			$data['filename'] = $data['title'];
			$data['longfilename'] = $data['title'];
		} else {
			$data['filename'] = maxlength(basename($data['path']), 15);
			$data['longfilename'] = basename($data['path']);
		}
	} else {
		$data['filename'] = maxlength(basename($data['path']), 15);
		$data['longfilename'] = basename($data['path']);
	}


	//file_url
		
	if (isset($data['file_url']) || isset($data['path'])) {

		$has_remote = false;

		$filename = $data['longfilename'] 
			?? basename($data['path'] ?? $data['file_url'] ?? 'unknown');

		$state = $data['state'] ?? '';

		if (isset($data['path']) && strpos($data['path'], '/gpfs/') !== false) {
			$has_remote = true;
		}


		if ($has_remote) {
			$data['show_file_url'] =
				"<span class=\"$state\" 
					style=\"color:#7f8c8d; cursor:not-allowed;\"
					title=\"Remote file (not accessible)\">
					&nbsp;&nbsp;&nbsp;$filename
				</span>";
		} else {
			$data['show_file_url'] =
				"<a class=\"$state\" 
					href=\"workspace/workspace.php?op=openPlainFile&fn={$data['_id_URL']}\" 
					title=\"open file $filename\" target=\"_blank\">
					&nbsp;&nbsp;&nbsp;$filename
				</a>";
		}
		
	}
	//remote_path && location
	$locationMap = [
		'MareNostrum' => 'MN',
		'mn4'         => 'MN',
	];

	if (isset($data['remote_paths'])) {
		$html = '';
		$seen = [];
		foreach ($data['remote_paths'] as $entry) {  
			$remotePath = $entry['remote_path'] ?? '';
			$location   = $entry['location'] ?? 'unknown';
			$key = $location . '|' . $remotePath;

			if (isset($seen[$key])) {
				continue;
			}
			$seen[$key] = true;
			//for each entry get the location and show the location
			$locKey = strtolower($entry['location'] ?? 'unknown');
			$short  = $locationMap[$locKey] ?? strtoupper(substr($locKey, 0, 3)); // map the location to $locationMaps or do the 3 first letter of the system in MongoDB
			$remote_file = basename($entry['remote_path']);
			$html .=
            "<span style='margin-left:6px;'>
                <i class='fa fa-exchange' style='color:#16a085;' 
                    title='Transferred to: {$entry['location']}'>
                </i>
                <span style='font-weight:bold; font-size:11px; margin-left:2px; color:#16a085;'>
				{$short}: {$remote_file}
                </span>
            </span>";
		}
		
		$data['show_remote_path'] = $html;

	} else {
    	$data['show_remote_path'] = ''; // nothing if no remote copy
	}


	if ($data['filename'] && !is_url($data['path'])) {
		$rfn      = $GLOBALS['dataDir'] . "/" . $data['path'];
		if (!is_file($rfn) && !is_dir($rfn)) {
			$data['filename'] = "ERROR-" . $data['filename'];
		}
	}

	if (isset($data['submission_file'])) {
		$data['execDetails'] = "<tr><td>Execution details:</td><td><a href=\"javascript:callShowSHfile('" . $data['tool'] . "','" . $data['submission_file'] . "');\">Analysis parameters</a></td></tr>";
	} else {
		$data['execDetails'] = "";
	}

	if (isset($data['log_file'])) {
		if (preg_match('/^\//', $data['log_file'])) {
			$data['log_file'] = str_replace($GLOBALS['dataDir'] . "/", "", $data['log_file']);
		}

		$viewLog_state = "enabled";
		if ((isset($data['pending']) && ($data['pending'] == "HOLD" || $data['pending'] == "PENDING")) || (!is_file($GLOBALS['dataDir'] . "/" . $data['log_file']) && !is_link($GLOBALS['dataDir'] . "/" . $data['log_file']))) {
			$viewLog_state = 'disabled';
		}

		$data['viewLog'] = "<tr><td>Log file:</td><td><a target=\"_blank\" href=\"workspace/workspace.php?op=openPlainFileFromPath&fnPath=" . urlencode($data['log_file']) . "\" class=\"$viewLog_state\">View</a></td></tr>";
	} else {
		$data['viewLog'] = "";
	}

	$data['tools_button'] = 'none';

	// tools list
	if (isset($data['data_type']) && ($data['data_type'] != "")) {
		$tList = getToolsByDT($data['data_type'], 1);
		$data['tools_list'] = '<ul class="dropdown-menu pull-right" role="menu">';
		if (sizeof($tList) > 0) {
			foreach ($tList as $t) {
				$data['tools_list'] .= '<li><a href="tools/' . $t[0] . '/input.php?fn[]=' . $data['_id_URL'] . '" class="' . $t[0] . '">' . file_get_contents('../tools/' . $t[0] . '/assets/ws/icon.php') . ' ' . $t[1] . '</a></li>';
			}
			$data['tools_button'] = 'block';
		} else {
			$data['tools_list'] .= '<li><a href="javascript:;">No tools available for this Data Type</a></li>';
			$data['tools_button'] = 'none';
		}

		$data['tools_list'] .= '</ul>';
	}

	//data_type
	if (isset($data['data_type']) && $data['data_type']) {
		$dt_name = getDataTypeName($data['data_type']);
		$data['file_data_type'] = $dt_name;
		$data['short_file_data_type'] = maxlength(basename($dt_name), 20);
		$data['data_type'] = "<tr><td>Data type:</td><td>" . $dt_name . "</td></tr>";
	} else {
		$data['data_type'] = "";
	}

	//notes
	if (isset($data['notes']) && strlen($data['notes'])) {
		$data['notes'] = "<tr><td>Notes:</td><td>" . $data['notes'] . "</td></tr>";
	} else {
		$data['notes'] = "";
	}

	//paired sorted refGenome
	if (isset($data['paired']) ||  isset($data['sorted'])) {
		$row = "<tr><td>BAM properties:</td><td>";
		if (isset($data['paired'])) {
			$row .= $data['paired'];
		}

		if (isset($data['sorted'])) {
			$row .= "&nbsp;" . $data['sorted'];
		}

		$row .= "</td></tr>";
		$data['paired'] = $row;
	} else {
		$data['paired'] = "";
	}

	if (isset($data['refGenome'])) {
		$data['refGenome'] = "<tr><td>Assembly:</td><td>" . $data['refGenome'] . "</td></tr>";
	} else {
		$data['refGenome'] = "";
	}

	//state and metadataLink
	if (isset($data['validated']) && $data['validated']) {
		$data['state'] = 'enabled';
		$data['metadataLink'] = "<li><a href=\"getdata/editFile.php?fn[]=" . $data['_id_URL'] . "\"><i class=\"fa fa-pencil\"></i> Edit file metadata</a></li>";
	} else {
		$data['state'] = 'disabled';
		$data['metadataLink'] = "<li><a href=\"getdata/editFile.php?fn[]=" . $data['_id_URL'] . "\"><i class=\"fa fa-exclamation-triangle\"></i> Validate file</a></li>";
	}

	$data['renameLink'] = "<li><a href=\"javascript:rename('" . $data['_id_URL'] . "');\"><i class=\"fa fa-i-cursor\"></i> Rename</a></li>";
	$data['moveLink'] = "<li><a href=\"javascript:move('" . $data['_id_URL'] . "');\"><i class=\"fa fa-exchange\"></i> Move</a></li>";

	//tools list (old school version :) delete

	//visualization
	if (isset($data['format'])) {

		$data['vis_button'] = 'block';
		$data['vis_button'] = 'none';

		$visualizers = getVisualizers_ListComplete();
		foreach ($visualizers as $vis) {
			if (in_array($data['format'], $vis["preview"])) {
				$data['vis_button'] = 'block';
				switch ($vis["_id"]) {

					case "ngl":
						$ext = 'pdb';
						if ($pos = strrpos($data['longfilename'], '.')) {
							$name = substr($data['longfilename'], 0, $pos);
							$ext = substr($data['longfilename'], $pos);
						} else {
							$name = $data['longfilename'];
						}

						$e = ltrim($ext, ".");
						$data['PDBView'] = "<li><a href=\"javascript:openNGL('" . $data['_id'] . "', '" . $name . "', '" . $e . "');\"><i class=\"fa fa-window-maximize\"></i> Preview in 3D</a></li>";
						break;

					case "tadkit":
						if ($pos = strrpos($data['longfilename'], '.')) {
							$name = substr($data['longfilename'], 0, $pos);
							$ext = substr($data['longfilename'], $pos);
						} else {
							$name = $data['longfilename'];
						}

						$data['PDBView'] = "<li><a href=\"javascript:openTADbit('" . $data['_id'] . "', '" . $name . "');\"><i class=\"fa fa-window-maximize\"></i> Preview in 3D</a></li>";
						break;
				}
			}

			if (in_array($data['format'], $vis["accepted_file_types"])) {
				$data['vis_button'] = 'block';
				switch ($vis["_id"]) {
					case "ngl":
						$data['NGLView'] = "<li><a href=\"visualizers/ngl/?user=" . $_SESSION['User']['id'] . "&fn[]=" . $data['_id'] . "\" target='_blank'><i class=\"fa fa-codepen\" ></i> View in NGL</a></li>";
						break;

					case "jbrowse":
						$data['jbrowseLink'] = "<li><a target=\"_blank\" href=\"" . $_SESSION['BASEURL'] . "visualizers/jbrowse/index.php/?user=" . $_SESSION['User']['id'] . "&fn[]=" . $data['_id'] . "\"><i class=\"fa fa-align-right\"></i> View in JBrowse</a></li>";
						break;

					case "tadkit":
						$data['tadkitLink'] = "<li><a target=\"_blank\" href=\"visualizers/tadkit/index.php/?user=" . $_SESSION['User']['id'] . "&fn=" . $data['_id'] . "\"><i class=\"fa fa-cubes fa-rotate-180\"></i> View in TADkit</a></li>";
						break;
				}
			}
		}
	}
	//input_files
	if (isset($data['input_files'])) {
		$ins = $data['input_files'];
		$data['input_files'] = "<tr><td>Input files:</td><td>";
		if (count($ins)) {
			foreach ($ins as $in) {
				$f = getGSFile_fromId($in);
				if ($f == 0) {
					getProjectLogger()->error("File $in not found");
					continue;
				}

				$data['input_files'] .= "<div>";
				$inFolders = explode("/", dirname($f['path']));
				for ($i = count($inFolders) - 1; $i >= 1; $i--) {
					$data['input_files'] .= "<span class=\"text-info\" style=\"font-weight:bold;\">" . $inFolders[$i] . "/</span>";
				}
				$data['input_files'] .= basename($f['path']) . "</div>";
			}
		}
		$data['input_files'] .= "</td></tr>";
	}
	//rerunLink
	if (isset($data['input_files']) && isset($data['tool'])) {
		$tool = $GLOBALS['toolsCol']->findOne(array('_id' => $data['tool']));
		if (!empty($tool)) {
			$formPath  = "tools/" . $data['tool'] . "/input.php";
			$data['rerunLink'] = "<li><a href=\"$formPath?rerunDir=" . $data['_id_URL'] . "\"><i class=\"fa fa-share\"></i> Rerun Project</a></li>";
		}
	}

	//analyses tool
	if (isset($data['tool'])) {
		$tool = $GLOBALS['toolsCol']->findOne(array('_id' => $data['tool']));
		if (!empty($tool))
			$data['tool'] = "<tr><td>Tool:</td><td>" . $tool['name'] . "</td></tr>";
	}
	//compressed
	$ext = pathinfo($data['path'], PATHINFO_EXTENSION);
	$ext = preg_replace('/_\d+$/', "", $ext);
	$content_type  = (array_key_exists($ext, mimeTypes()) ? mimeTypes()[$ext] : "application/octet-stream");
	$data['openFunction'] = ($content_type == "text/plain" || $ext == "pdf" || preg_match('/image/', $content_type) || preg_match('/(e|o)\d+/', $ext) || in_array($data['format'], array("FASTQ", "FASTA")) ? "openPlainFile" : "downloadFile");
	$data['compressionLink'] = "";
	if (! in_array($data['format'], array("BAM", "PNG", "JPG"))) {
		switch (strtolower($ext)) {
			case 'tar':
				$func   = "untar";
				$img    = "fa fa-expand";
				$linkTxt = "Uncompress";
				break;
			case 'gz':
			case 'zip':
				$func   = "unzip";
				$img    = "fa fa-expand";
				$linkTxt = "Uncompress";
			case 'tgz':
				$func   = "untar";
				$img    = "fa fa-expand";
				$linkTxt = "Uncompress";
				break;
			case 'bz2':
				$func   = "bzip2";
				$img    = "fa fa-expand";
				$linkTxt = "Uncompress";
			default:
				$func   = "zip";
				$img    = "fa fa-file-zip-o";
				$linkTxt = "Compress";
		}
		$data['compressionLink'] = "<li><a  href=\"workspace/workspace.php?op=$func&fn=" . $data['_id_URL'] . "\" class=\"enabled\"><i class=\"$img\"></i> $linkTxt</a></li>";
		//$data['compressionLink'] = "<li><a  href=\"javascript:;\" class=\"disabled\"><i class=\"$img\"></i> $linkTxt</a></li>";
	}
	return $data;
}


//update Mongo lastjobs
function updatePendingFiles($sessionId)
{
	$SGE_updated = array(); // jobs to be monitored in next round. Stored in SESSION. Updated by checkPendingJobs.php (called by ajax)

	// get jobs from mongo[users][lastjobs]
	$lastjobs = getUserJobs($sessionId);

	if (count($lastjobs)) {
		foreach ($lastjobs as $job) {
			if (is_null($job['_id'])) {
				continue;
			}

			$pid = $job['pid'];

			//get qstat info
			getProjectLogger()->info("Start processPendingFiles -> getRunningJobInfo $pid. Log= " . $job['log_file']);
			$jobProcess = getRunningJobInfo($pid, $job['launcher']);

			//job keeps running: maintain original job data
			if (count($jobProcess)) {
				//keep monitoring
				$job['state']  = $jobProcess['state'];
				$SGE_updated[$pid] = $job;

				//job not running : edit SGE_updated to register the change
				// and consequently reload workspace (checkPendingJobs.php)
			} else {
				getProjectLogger()->info("Automatic job update detects job $pid is not running anymore");
				$SGE_updated[$pid] = $job;
				$SGE_updated[$pid]['state'] = "NOT_RUNNING";
			}
		}
	}

	//update session and save to mongo
	saveUserJobs($sessionId, $SGE_updated);
}


function processRunningJobInfo($job, $jobProcess, $pid, $title, $descrip, &$filesPending, $SGE_updated)
{
	getProjectLogger()->debug("Start processRunningJobInfo $pid.  Job data: " . json_encode($job));

	//set dummy id
	$dummyId  = $job['pid'] . "_dummy";

	//get dummy parentDir
	if ($job['hasExecutionFolder']) {
		// show job in execution dir
		$parentDir = fromAbsPath_toPath($job['working_dir']);
	} else {
		// show job in output_dir (infered from stageout_data)
		$parentDir = 0;
		if ($job['stageout_data']) {
			$output_file_1 = $job['stageout_data']['output_files'][0];
			if ($output_file_1 && $output_file_1['path']) {
				$parentDir = fromAbsPath_toPath(dirname($output_file_1['path']));
			}
		}

		if (!$parentDir) {
			$parentDir = $_SESSION['User']['id'] . "/" . $_SESSION['User']['activeProject'] . "/uploads";
		}
	}

	//set dummy file
	$fileDummy = array(
		'_id'     => $dummyId,
		'pid'     => $pid,
		'title'   => $title,
		'mtime'   => new MongoDB\BSON\UTCDateTime(strtotime($jobProcess['submission_time']) * 1000),
		'size'    => "",
		'visible' => 1,
		'tool'    => $job['toolId'],
		'project' => $job['project'],
		'parentDir' => getGSFileId_fromPath($parentDir),
		'description' => $descrip,
		'pending' => $jobProcess['state'],
		'submission_file'  => fromAbsPath_toPath($job['submission_file']),
		'log_file'    => fromAbsPath_toPath($job['log_file']),
		'stdout_file' => fromAbsPath_toPath($job['stdout_file']),
		'stderr_file' => fromAbsPath_toPath($job['stderr_file']),
		'job_type'    => $job['job_type']
	);

	if ($jobProcess['state'] == "RUNNING" && $job['job_type'] == "interactive") {
		$fileDummy['pending'] = "ACTIVE SESSION";
		$fileDummy['toolContainerName'] = $_SESSION['User']['lastjobs'][$pid]['containerName'];
	}

	//list job in workspace
	$filesPending[$dummyId] = $fileDummy;

	//update job state in mongo
	$job['state'] = $jobProcess['state'];
	$SGE_updated[$pid] = $job;

	return $SGE_updated;
}


function processFinishedJobInfo($job, $pid, $title, &$filesPending)
{
	getProjectLogger()->info("Workspace reload detects job $pid is not running anymore");

	unset($_SESSION['errorData']);
	$job_in_err = 0;

	//get tool info
	$tool = getTool_fromId($job['toolId'], 1);
	if (is_null($tool)) {
		getProjectLogger()->error("Tool '" . $job['toolId'] . "' received from JobTool not registered");
		getProjectLogger()->error("Cannot obtain results from '$title' in folder '" . basename($job['working_dir']) . "'. Job metadata is not valid.");
		getProjectLogger()->error("Failed to register $pid job outfiles. Job metadata has toolId '" . $job['toolId'] . "'");
		$job_in_err = 1;
		return;
	}

	getProjectLogger()->debug("Building output from toolINFO + stageout_file + stageout_data.");

	// build output list merging: stageout_file + stageout_data + tool defintion data
	$outs_files = build_outputs_list($tool, $job['stageout_data'], $job['stageout_file']);
	getProjectLogger()->debug("Finished building output from toolINFO + stageout_file + stageout_data: " . json_encode($outs_files));
	if (empty($outs_files)) {
		getProjectLogger()->warning("Failed to register $pid job outfiles. Output file list empty.");
		$job_in_err = 1;
	}

	// checking each expected job output
	foreach ($outs_files as $out_name => $outs_data) {
		// evaluate output_file requirement
		$out_def = $tool['output_files'][$out_name];

		//check requirement : allow multiple
		if (!isset($out_def['allow_multiple']) || !$out_def['allow_multiple']) {
			if (count($outs_data) > 1) {
				getProjectLogger()->warning("Tool definition does not allow multiple instances for '$out_name', but the execution returned " . count($outs_data) . ". Registering only one of them.");
			}

			$outs_data = array($outs_data[0]);
		}

				// start 	
				foreach ($outs_data as $out_data) {
					if ($debug) {
						print "<br/> START OUTPUT ITEM REGISTRATION FOR THE FOLLOWING OUT_DATA:<br/>\n";
						var_dump($out_data);
						print "<\br>_____________\n";
						var_dump($out_data['path']);
						print "<\br>_____________\n";
						var_dump($out_data['meta_data']);
						print "<\br>_____________\n";
					}

					if (!isset($out_data['path']) || empty($out_data['path'])) {
						// Recover from remote_paths
						if (isset($out_data['meta_data']['remote_paths'][0]['remote_path'])) {
							$remote_path = $out_data['meta_data']['remote_paths'][0]['remote_path'];
							if ($debug) {
								print "<br/>Recovering path from remote_paths: $remote_path<br/>";
								$_SESSION['errorData']['Error'][] = "Recovering path from remote_paths: $remote_path";
							}
							// this is right (?)
							$out_data['path'] = $remote_path;

						} else {
							if ($is_required) {
								$_SESSION['errorData']['Error'][] = "Job output file ($out_name) not created";
								$msg = "Job output file ($out_name) not created";
								$msg .= ". No 'path' and no usable 'remote_paths' found.";
								$msg .= ". Job metadata: " . print_r($out_data, true);
								$_SESSION['errorData']['Error'][] = $msg;
								log_addOutregister($pid, $msg);
								$job_in_err = 1;
							}
							continue;
							}
					}


					// resolve virtual path to local absolute path
					$rfn = resolvePath_toLocalAbsolutePath($out_data['path'], $job);

					$outPath  = fromAbsPath_toPath($rfn);
					$fileId   = getGSFileId_fromPath($outPath);
					if ($debug)
						print "PID = [$pid] path=" . $out_data['path'] . " --> fn=$outPath rfn=$rfn . Has Id? $fileId <br/>\n";


					//convert stage out data into MuGFile

			//associated_files and associated_id/_master: convert to fileIds 
			$metaReferences = array();
			if (isset($out_data['meta_data']['associated_id']) || isset($out_data['meta_data']['associated_master'])) {
				$assoc = (isset($out_data['meta_data']['associated_id']) ? $out_data['meta_data']['associated_id'] : $out_data['meta_data']['associated_master']);
				$assoc_rfn = resolvePath_toLocalAbsolutePath($assoc, $job);
				$assoc_fn  = fromAbsPath_toPath($assoc_rfn);
				$assoc_id  = getGSFileId_fromPath($assoc_fn);

				if ($assoc_id == "0") {
					$out_data['meta_data']['associated_id'] = $assoc;
				} else {
					$metaReferences[$assoc_id] = "associated_id";
					$out_data['meta_data']['associated_id'] = $assoc_id;
				}

				if (isset($out_data['meta_data']['associated_master'])) {
					unset($out_data['meta_data']['associated_master']);
				}
			}

			if (isset($out_data['meta_data']['associated_files'])) {
				$assocs = array();
				foreach ($out_data['meta_data']['associated_files'] as $assoc) {
					$assoc_rfn = resolvePath_toLocalAbsolutePath($assoc, $job);
					$assoc_fn  = fromAbsPath_toPath($assoc_rfn);
					$assoc_id  = getGSFileId_fromPath($assoc_fn);
					if ($assoc_id == "0") {
						array_push($assocs, $assoc);
					} else {
						array_push($assocs, $assoc_id);
						$metaReferences[$assoc_id] = "associated_files";
					}
				}

				$out_data['meta_data']['associated_files'] = $assocs;
			}

			// job successfully finished and already in mongo. Update medatada
			if ($fileId) {
				getProjectLogger()->debug("JOB $pid finished successfully.");
				getProjectLogger()->debug("Updating only outfile $out_name '$rfn' metadata from job $pid");
				list($out_vre, $metadata) = getVREfile_fromFile($out_data);
				addMetadataToFile($fileId, $metadata);
			} elseif (is_file($rfn) || is_dir($rfn) || isset($out_data['meta_data']['remote_paths'][0]['remote_path'])) { // job successfully finished but not yet on mongo. Save output
				if (!$tool['external']) {
					$out_data['meta_data']['validated'] = true;
				}

				list($out_vre, $metadata) = getVREfile_fromFile($out_data);
				try {
					$has_remote = isset($out_data['meta_data']['remote_paths'][0]['remote_path']);
					if ($has_remote) {
						$fileInfo = saveResults($rfn, $metadata, $job); // use GPFS path
					} else {
						$fileInfo = saveResults($outPath, $metadata, $job);
					}
					
					getProjectLogger()->debug("Job output outfile ($out_name) generated (" . basename($rfn) . ").");
				} catch (Exception $e) {
					$_SESSION['errorData']['Error'][] = "Job output file (" . basename($rfn) . ") generated, but with wrong metadata.";
				}

				if (is_array($fileInfo)) {
					$fileId = $fileInfo['_id'];
					if ($metadata['visible']) {
						$filesPending[$fileId] = $fileInfo;
					}
				}
			} else {
				getProjectLogger()->error("Failed to register outfile $out_name '$rfn'. File not found in disk");
				$job_in_err = 1;
			}

			// Update metadata of other files referring current fileId  (associated files)
			if ($job_in_err == 0 &&  count($metaReferences)) {
				foreach ($metaReferences as $assoc_id => $assoc_type) {
					$file_assoc = getGSFile_fromId($assoc_id, "onlyMetadata");
					if ($assoc_type == "associated_files") {
						$file_assoc['associated_id'] = $fileId;
					}

					if ($assoc_type == "associated_id") {
						$assocs = array();
						foreach ($file_assoc['associated_files'] as $a) {
							if (preg_match('/\//', $a)) {
								array_push($assocs, $fileId);
							} else {
								array_push($assocs, $a);
							}
						}

						$file_assoc['associated_files'] = $assocs;
					}

					addMetadataToFile($assoc_id, $file_assoc);
				}
			}
		}
	}

	// jobs nor finished nor running: in error OR deleted OR SESSION[sge] not updated

	if ($job_in_err) {
		getProjectLogger()->error("Failed to register all job outfiles");
		getProjectLogger()->error("JOB $pid FINISHED but with errors");

		$logFileP = $job['log_file'];
		$logFile  = fromAbsPath_toPath($job['log_file']);

		// force flash disk status
		scandir($GLOBALS['dataDir'] . $_SESSION['User']['id'] . "/" . $job['project']);

		// job has log
		if (is_file($logFileP)) {
			$logId  = getGSFileId_fromPath($logFile);
			if (is_null($logId)) {
				$logMeta['description'] = "Job log file";
				$logMeta['format']      = "ERR";
				$metaDataLog = prepMetadataLog($logMeta, $logFile);
				try {
					$logInfo = saveResults($logFile, $metaDataLog, $job);
					$filesPending[$logInfo['_id']] = $logInfo;
				} catch (Exception $e) {
					$_SESSION['errorData']['Error'][] = $e->getMessage();
				}
			}
		}
	} else {
		getProjectLogger()->debug("JOB $pid finished successfully.");
	}
}


function processPendingFiles($sessionId)
{
	$SGE_updated = array(); // jobs to be monitored. Stored in SESSION. Updated by checkPendingJobs.php (called by ajax)
	$filesPending = array(); // files to be listed

	// get jobs from mongo[users][lastjobs]
	$lastjobs = getUserJobs($sessionId);
	if (empty($lastjobs)) {
		getProjectLogger()->debug("No pending jobs");
		return [];
	}

	// classify jobs
	foreach ($lastjobs as $job) {
		if (!isset($job['pid'])) {
			continue;
		}

		$pid = $job['pid'];

		//get qstat info
		getProjectLogger()->info("Start processPendingFiles -> getRunningJobInfo $pid. Log= " . $job['log_file']);
		$jobProcess = getRunningJobInfo($pid, $job['launcher'], $job['cloudName']);
		$title   = $job['title'] ?? "Job " . $job['execution'];
		$descrip = getJobDescription($job['description'], $jobProcess, $lastjobs);

		//set as running job
		if (empty($jobProcess)) {
			processFinishedJobInfo($job, $pid, $title, $filesPending);
		} else {
			$SGE_updated = processRunningJobInfo($job, $jobProcess, $pid, $title, $descrip, $filesPending, $SGE_updated);
		}
	}

	getProjectLogger()->debug("SGE_updated before saveUserJobs: " . json_encode($SGE_updated));
	//update session and save to mongo
	saveUserJobs($sessionId, $SGE_updated);
	return $filesPending;
}


function saveResults($filePath, $metaData = array(), $job = array(), $rfn = 0, $asRoot = 0)
{

	// NOT saving internal or temporal files
	//if (in_array($ext,$GLOBALS['internalResults']) || preg_match('/^\./',basename($filePath)) ){
	//	return 1;
	//}
	// check if file is local or remote
	$is_remote = preg_match('/^\/gpfs\//', $filePath);

	getProjectLogger()->debug("saveResults(" . $filePath . ", " . json_encode($metaData) . ", " . json_encode($job) . ", " . $rfn . ", " . $asRoot . ")");
	// check given filePath
	if ($rfn == 0) {
		if ($is_remote) {
			$rfn = $filePath;
		} else { {
			$rfn  = $GLOBALS['dataDir'] . "/" . $filePath;
		}
	}
		
	}

	if (preg_match('/^\//', $filePath) && !$is_remote) {
		$rfn      = $filePath;
		$filePath = str_replace($GLOBALS['dataDir'] . "/", "", $rfn);
		getProjectLogger()->debug("File path replaced to " . $filePath);
	}


	if (!$is_remote && (!is_file($rfn) && !is_dir($rfn))) {
		getProjectLogger()->error("Execution result '$rfn' does not exist. Cannot save it into database");
		throw new UnexpectedValueException("Execution result '$rfn' does not exist. Cannot save it into database");
	}

	if (is_file($rfn) && filesize($rfn) === 0) {
		getProjectLogger()->error("Execution result '$rfn' has size 0. Cannot save it into database");
		throw new UnexpectedValueException("Execution result '$rfn' has size 0. Cannot save it into database");
	}

	$metaData = prepMetadataResult($metaData, $filePath, $job);
	$parentPath = dirname($filePath);

	if ($is_remote) {
		 $parentPath = fromAbsPath_toPath($job['output_dir']);
		 $parentId = getGSFileId_fromPath($parentPath, $asRoot);
		 if (!$parentId) {
			$_SESSION['errorData']['Error'][] =
				"Cannot attach remote file '" . basename($filePath) . "' to job output directory '$parentPath'";
			return 0;
		}
	} else {
		$parentId = getGSFileId_fromPath($parentPath, $asRoot);
		if (!$parentId) {
			if (isset($job['hasExecutionFolder']) && $job['hasExecutionFolder'] === false) {
				$parentPath = fromAbsPath_toPath($job['output_dir']);
				$parentId = getGSFileId_fromPath($parentPath, $asRoot);
			}
			if (!$parentId) {
				$_SESSION['errorData']['Error'][] =
					"Cannot save result '" . basename($filePath) . "' at '$parentPath'. Parent directory not accessible";
				return 0;
			}
		}
	}

	#save Data
	$fileId      = createLabel();
	$insert_type = isset($metaData['type'])
		? $metaData['type']
		: (is_dir($rfn) ? "dir" : "file");
	$size        = $insert_type == "dir"
		? getDirectorySize($rfn)
		: filesize($rfn);
	$child_files = isset($metaData['files'])
		? $metaData['fields']
		: (is_dir($rfn) ? array() : false);
	if ($is_remote) {
		$mtime = new MongoDB\BSON\UTCDateTime(); // NOW
	} else {
		$mtime = new MongoDB\BSON\UTCDateTime(filemtime($rfn) * 1000);
	}

	$insertData = array(
		'_id'   => $fileId,
		'type'  => $insert_type,
		'owner' => $_SESSION['User']['id'],
		'size'  => $size,
		'path'  => $filePath,
		'project' => $job['project'],
		'mtime' => $mtime,
		'parentDir' => $parentId
	);

	if ($child_files !== false) {
		$insertData['files'] = $child_files;
	}

	try {
		uploadGSFileBNS($filePath, $rfn, $insertData, $metaData, $asRoot);
		$insertData['mtime'] = $insertData['mtime']->toDateTime()->format('U');
		return array_merge($insertData, $metaData);
	} catch (Exception $e) {
		$_SESSION['errorData']['mongoDB'][] = "Cannot save execution result 'basename($filePath)' into database. Stored only on disk";
		throw new UnexpectedValueException("Cannot save execution result 'basename($filePath)' into database. Stored only on disk. " . $e->getMessage());
	}
}


function  build_outputs_list($tool, $stageout_job, $stageout_file)
{
	getProjectLogger()->debug("build_outputs_list(" . json_encode($tool) . ", " . json_encode($stageout_job) . ", " . json_encode($stageout_file) . ")");

	// check tool output_files
	if (!$tool['infrastructure']['interactive'] && !(isset($tool['output_files']) || count($tool['output_files']) == 0)) {
		getProjectLogger()->error("Tool " . $tool['name'] . " has not list of 'output_files'. Invalid tool registration");
		getProjectLogger()->error("Cannot obtain results from execution '" . dirname($stageout_file) . "'");
		return [];
	}

	// parse stageout file
	$stageout_meta = array();
	if (isset($stageout_file) && is_file($stageout_file)) {
		$content = file_get_contents($stageout_file);
		$data = json_decode($content, true);
		if (empty($data) || empty($data['output_files'])) {
			getProjectLogger()->warning("Tool stageout file '" . basename($stageout_file) . "' is empty or bad formatted");
		}

		//index by name
		foreach ($data['output_files'] as $out) {
			if (!isset($out['name'])) {
				getProjectLogger()->warning("Tool stageout file '" . basename($stageout_file) . "' is bad formatted. Missing 'name' in 'output_files' list");
				continue;
			}

			if (!isset($stageout_meta[$out['name']])) {
				$stageout_meta[$out['name']] = array();
			}

			array_push($stageout_meta[$out['name']], $out);
		}
	} elseif ($tool['external'] !== false) {
		$_SESSION['errorData']['Warning'][] = date("h:i:s") . ": Tool stageout file '" . $stageout_file . "' is not found";
		getProjectLogger()->warning("Tool stageout file '" . $stageout_file . "' is not found");
	}

	// check stageout data
	$stageout_data = array();
	if ($stageout_job && isset($stageout_job['output_files'])) {
		foreach ($stageout_job['output_files'] as $out) {
			if (!isset($out['name'])) {
				getProjectLogger()->warning("Tool job has stageout data is bad formatted. Missing 'name' in 'output_files' list");
				continue;
			}

			if (!isset($stageout_data[$out['name']])) {
				$stageout_data[$out['name']] = array();
			}

			array_push($stageout_data[$out['name']], $out);
		}
	}
	if ($debug)	{
		
		print "\n__________FROM FILE________________\n";
		print json_encode($stageout_meta, JSON_PRETTY_PRINT);

		print "\n__________FROM JOB________________\n";
		print json_encode($stageout_data, JSON_PRETTY_PRINT);

		// Merge FILE + JOB (job overrides file)
		$stageout_meta = array_merge($stageout_meta, $stageout_data);

		print "\n__________MERGED (FILE + JOB)________________\n";
		print json_encode($stageout_meta, JSON_PRETTY_PRINT);
	}
	
	// merging file data from tool and stageout_file
	$outs_meta = array();
	foreach ($tool['output_files'] as $out_name => $out_data) {
		$outs_meta[$out_name] = array();
		if (!isset($out_data['file'])) {
			$out_data['file'] = array();
			getProjectLogger()->error("Tool has no file attribute for output_file '$out_name'");
		}

		if (!isset($stageout_meta[$out_name])) {
			getProjectLogger()->error("Tool stageout file/data has no metadata for output_file '$out_name'.");
			array_push($outs_meta[$out_name], $out_data);
			continue;
		}

		foreach ($stageout_meta[$out_name] as $stg_data) {
			//create  merged file data
			if (isset($out_data['file']['input_files'])) {
				unset($out_data['file']['input_files']);
			}

			if (isset($stg_data['name'])) {
				unset($stg_data['name']);
			}

			$file_merged  = array_merge_recursive_distinct($out_data['file'], $stg_data);
			array_push($outs_meta[$out_name], $file_merged);
		}
	}

	return $outs_meta;
}


function topDir()
{
	return ($_SESSION['curDir'] == $_SESSION['userId']);
}

function upDir()
{
	if (!topDir())
		$_SESSION['curDir'] = dirname($_SESSION['curDir']);
}

function downDir($fn)
{
	$fnData = $GLOBALS['filesCol']->findOne(array('_id' => $fn));
	if (! empty($fnData)) {
		if (isset($fnData['type']) && $fnData['type'] == "dir") {
			$_SESSION['curDir'] = $fn;
		} else {
			$_SESSION['errorData'][error][] = "Cannot change directory. $fn is not a directory ";
		}
	}
}

// return sum of FS or Mongo directory (in bytes)

function getUsedDiskSpace($userId = '', $source = "fs")
{
	if (!$userId) {
		$userId = $_SESSION['User']['id'];
	}

	if ($source != "fs") {
		if (!preg_match('/^\//', $userId)) {
			$userId = $GLOBALS['dataDir'] . "/" . $userId;
		}

		$data = explode("\t", exec("du -sb $userId"));
		return $data[0];
	}

	return calcGSUsedSpace($userId);
}


// return sum of FS directory (in bytes)
function getDirectorySize($fn)
{
	if (empty($fn)) {
		return 0;
	}

	if (!preg_match('/^\//', $fn)) {
		$fn = $GLOBALS['dataDir'] . "/" . $fn;
	}

	$data = explode("\t", exec("du -sb $fn"));
	return $data[0];
}


function formatSize($bytes)
{
	$types = array('B', 'KB', 'MB', 'GB', 'TB');
	for ($i = 0; $bytes >= 1024 && $i < (count($types) - 1); $bytes /= 1024, $i++);
	return round($bytes, 2) . "" . $types[$i];
}


function downloadFile($rfn)
{
	getProjectLogger()->info("Downloading file " . $rfn);
	$fileInfo      = pathinfo($rfn);
	$filename      = $fileInfo['basename'];
	$fileExtension = $fileInfo['extension'];
	$fileExtension = preg_replace('/_\d+$/', "", $fileExtension);
	$content_type  = (array_key_exists($fileExtension, mimeTypes()) ? mimeTypes()[$fileExtension] : "application/octet-stream");
	$size = filesize($rfn);
	$offset = 0;
	$length = $size;

	if (isset($_SERVER['HTTP_RANGE'])) {
		preg_match('/bytes=(\d+)-(\d+)?/', $_SERVER['HTTP_RANGE'], $matches);
		$offset = intval($matches[1]);
		$length = intval($matches[2]) - $offset;

		$fhandle = fopen($rfn, 'r');
		fseek($fhandle, $offset); // seek to the requested offset, this is 0 if it's not a partial content request
		$data = fread($fhandle, $length);
		fclose($fhandle);

		header('HTTP/1.1 206 Partial Content');
		header('Content-Range: bytes ' . $offset . '-' . ($offset + $length) . '/' . $size);
	}
	header("Content-Disposition: attachment;filename=" . $filename);
	header('Content-Type: ' . $content_type);
	header("Accept-Ranges: bytes");
	header("Pragma: public");
	header("Expires: -1");
	header("Cache-Control: no-cache");
	header("Cache-Control: public, must-revalidate, post-check=0, pre-check=0");
	header("Content-Length: " . filesize($rfn));
	$chunksize = 8 * (1024 * 1024); //8MB (highest possible fread length)

	if ($size > $chunksize) {
		$handle = fopen($rfn, 'rb');
		$buffer = '';
		while (!feof($handle) && (connection_status() === CONNECTION_NORMAL)) {
			$buffer = fread($handle, $chunksize);
			print $buffer;
			ob_flush();
			flush();
		}
		if (connection_status() !== CONNECTION_NORMAL) {
			echo "Connection aborted";
		}
		fclose($handle);
	} else {
		ob_clean();
		flush();
		readfile($rfn);
	}

	getProjectLogger()->info("Downloaded file " . $rfn);
	exit(0);
}


function refresh_token($force = false)
{
	if (!$_SESSION['userToken']->getToken()) {
		ob_clean();
		header('Location: ' . $GLOBALS['BASEURL'] . '/htmlib/errordb.php?msg=Authentification Session Expired. <a href=' . $GLOBALS['URL'] . '>Login again</a>');
	}

	$existingToken = $_SESSION['userToken'];
	$provider = new Oauth2Provider(['redirectUri' => $GLOBALS['URL'] . $_SERVER['PHP_SELF']]);

	if ($force || $existingToken->hasExpired()) {
		try {
			$newToken = $provider->getAccessToken('refresh_token', ['refresh_token' => $existingToken->getRefreshToken()]);
		} catch (Exception $e) {
			$_SESSION['errorData']['Error'][] = "Cannot validate token from refresh token.";
			$_SESSION['errorData']['Error'][] = $e->getMessage();
			return false;
		}

		// load new token in session
		$_SESSION['userToken'] = $newToken;
		return true;
	} else {
		$_SESSION['errorData']['Warning'][] = "Access token not expired yet. <a href='applib/refreshToken.php?force=1'>Force refresh</a>";
		return false;
	}
}


function mimeTypes()
{
	$mime_types = array(
		"log" => "text/plain",
		"txt" => "text/plain",
		"md"  => "text/plain",
		"err" => "text/plain",
		"out" => "text/plain",
		"csv" => "text/plain",
		"gff" => "text/plain",
		"gff3" => "text/plain",
		"wig" => "text/plain",
		"bed" => "text/plain",
		"json" => "text/plain",
		"bedgraph" => "text/plain",
		"tre" => "text/plain",
		"nxt" => "text/plain",
		"nwt" => "text/plain",
		//"sh" => "application/x-sh",
		"sh" => "text/plain",
		"pdb" => "chemical/x-pdb",
		"crd" => "chemical/x-pdb",
		"xyz" => "chemical/x-xyz",
		"cdf" => "application/octet-stream",
		"xtc" => "application/octet-stream",
		"trr" => "application/octet-stream",
		"gro" => "application/octet-stream",
		"dcd" => "application/octet-stream",
		"exe" => "application/octet-stream",
		"gtar" => "application/octet-stream",
		"bam" => "application/octet-stream",
		"sam" => "application/octet-stream",
		"tar" => "application/x-tar",
		"gz" => "application/application/x-gzip",
		"tgz" => "application/application/x-gzip",
		"z" => "application/octet-stream",
		"rar" => "application/octet-stream",
		"bz2" => "application/x-gzip",
		"zip" => "application/zip",
		"h" => "text/plain",
		"htm" => "text/html",
		"html" => "text/html",
		"gif" => "image/gif",
		"bmp" => "image/bmp",
		"ico" => "image/x-icon",
		"jfif" => "image/pipeg",
		"jpe" => "image/jpeg",
		"jpeg" => "image/jpeg",
		"jpg" => "image/jpeg",
		"rgb" => "image/x-rgb",
		"svg" => "image/svg+xml",
		"png" => "image/png",
		"tif" => "image/tiff",
		"tiff" => "image/tiff",
		"ps" => "application/postscript",
		"eps" => "application/postscript",
		"js" => "application/x-javascript",
		"pdf" => "application/pdf",
		"doc" => "application/msword",
		"xls" => "application/vnd.ms-excel",
		"ppt" => "application/vnd.ms-powerpoint",
		"tsv" => "text/tab-separated-values"
	);
	return $mime_types;
}

/*
function check_key_repeats($key, $hash) {
	if (is_null($key) || is_null($hash)) {
		return null;
	}
	if (array_key_exists($key, $hash)) {
		$key++;
		$key = check_key_repeats($key, $hash);
		return $key;
	} else {
		return $key;
	}
}
*/

function return_bytes($val)
{
	$val = trim($val);
	$last = strtolower($val[strlen($val) - 1]);
	$val = intval($val);
	switch ($last) {
		case 'g':
			$val *= 1024;
		case 'm':
			$val *= 1024;
		case 'k':
			$val *= 1024;
	}
	return $val;
}


// resolve virtual path (relative or absolutes) to local absolute path
function resolvePath_toLocalAbsolutePath($path, $job)
{
	$rfn = "";
	// path is an absolute path
	if (preg_match('/^\//', $path)) {
		if (preg_match('/^' . preg_quote($job['root_dir_virtual'], '/') . '/', $path)) {
			if ($job['launcher'] == "SGE" || $job['launcher'] == "ega_demo" || $job['launcher'] == "docker_SGE") {
				$rfn = str_replace($job['root_dir_mug'], $GLOBALS['dataDir'], $path);
			}
			// direct from path
		} else {
			$rfn = $path;
		}
		// path is relative
	} else {
		// path is only a file name (file)
		if (!preg_match('/\//', $path)) {
			$rfn = $job["output_dir"] . "/" . $path;
			// path is relative to user data directory (run/file)
		} elseif (preg_match('/^' . $job['execution'] . '/', $path)) {
			$rfn = dirname($job["output_dir"]) . "/" . $path;
			// path is relative to root directory (userid/prj/run/file)
		} elseif (preg_match('/^' . $_SESSION['User']['id'] . '/', $path)) {
			$rfn = $GLOBALS['dataDir'] . "/" . $path;
			// path contains $(working_dir) tag
		} elseif (preg_match('/(working_dir)/', $path)) {
			$rfn = str_replace("$(working_dir)", $job['working_dir'] . "/", $path);

			// path is relative to app working directory (userid/prj/run/file)
		} else {
			$rfn = $job['working_dir'] . "/" . $path;
		}
	}
	//clean slashes
	$rfn = preg_replace('#/+#', '/', $rfn);

	//return absolute path
	return $rfn;
}


function deleteFiles($fileIds, $force = false)
{
	if (!is_array($fileIds)) {
		$fileIds = [$fileIds];
	}

	getProjectLogger()->info("Deleting files with ids " . implode(',', $fileIds));

	$result = true;
	foreach ($fileIds as $fileId) {
		$file = getGSFile_fromId($fileId);
		if (is_null($file)) {
			getProjectLogger()->error("Cannot delete file with id '$fileId'. Entry not found");
			$result = false;
			continue;
		}

		// check file exists
		$fileLocalPath = $file['path'];
		$filePath = $GLOBALS['dataDir'] . "/$fileLocalPath";
		if (!file_exists($filePath) && !$force && $file['data_source'] != "EGA") {
			getProjectLogger()->error("Cannot delete file with id '" . basename($fileLocalPath) . "'. File not found.");
			$result = false;
			continue;
		}

		// delete file from DMP
		try {
			deleteGSFileBNS($fileId);
		} catch (Exception $e) {
			getProjectLogger()->error("Cannot delete file '" . basename($fileLocalPath) . "'. Cannot delete entry from the repository." . $e->getMessage());
			$result = false;
			continue;
		}

		// delete file from disk
		if (file_exists($filePath) && !unlink($filePath)) {
			getProjectLogger()->error("Errors encountered while deleting file '" . basename($fileLocalPath) . "'.");
			$result = false;
			continue;
		}

		// if is an associated file, update master file
		if (isset($file['associated_id'])) {
			$master_id = $file['associated_id'];
			$master    = getGSFile_fromId($master_id, "onlyMetadata");
			if ($master && ($k = array_search($fileId, $master['associated_files'])) !== false) {
				unset($master['associated_files'][$k]);
				try {
					addMetadataToFile($master_id, $master);
				} catch (Exception $e) {
					getProjectLogger()->error("File '" . basename($fileLocalPath) . "' successfully deleted, but cannot update its master file $master_id metadata");
					$result = false;
					continue;
				}
			}
			// if has associated files, delete them
		} elseif (isset($file['associated_files'])) {
			foreach ($file['associated_files'] as $assoc_id) {
				$deletedFiles = deleteFiles($assoc_id);
				if ($deletedFiles === false) {
					getProjectLogger()->warning("File '" . basename($fileLocalPath) . "' successfully deleted, but not its associated file ($assoc_id).");
					$result = false;
				}
			}
		}
	}

	getProjectLogger()->info("Deleted files with ids " . implode(',', $fileIds));

	return $result;
}


function moveFiles($fns, $target_fn)
{
	$target_fn      = rtrim($target_fn, "/");
	$targetId       = getGSFileId_fromPath($target_fn);
	$target_dir     = "";
	$target_filename = "";

	if (is_array($fns)) { // is array of fn given, target must be a directory
		$multipleFiles  = true;
		if (is_null($targetId) || !is_dir($GLOBALS['dataDir'] . "/$target_fn")) {
			getProjectLogger()->error("Cannot move multiple files into target directory '$target_fn'. Target must be un existing directory");
			throw new UnexpectedValueException("Cannot move multiple files into target directory '$target_fn'. Target must be un existing directory");
		}

		$target_dir = rtrim($target_fn, "/");
	} else { // is a single fn given, target must be a file
		$multipleFiles  = false;
		$fns = array($fns);
		if (isset($targetId)) {
			$_SESSION['errorData']['Error'][] = "Cannot move file into target path '$target_fn'. File already exists";
			getProjectLogger()->error("Cannot move file into target path '$target_fn'. File already exists");
			throw new UnexpectedValueException("Cannot move file into target path '$target_fn'. File already exists");
		} else {
			$target_dir  = rtrim(dirname($target_fn), "/");
			$target_filename = basename($target_fn);
		}
	}

	// move each fn
	foreach ($fns as $fn) {
		$file = getGSFile_fromId($fn);
		if (is_null($file)) {
			getProjectLogger()->error("Cannot move file with id '$fn'. Entry not found");
			throw new NotFoundException("Cannot move file with id '$fn'. Entry not found");
		}

		$file_fn  = $file['path'];
		$file_rfn = $GLOBALS['dataDir'] . "/$file_fn";
		if (!file_exists($file_rfn)) {
			getProjectLogger()->error("Cannot move file named '" . basename($file_fn) . "'. File not found.");
			throw new NotFoundException("Cannot move file named '" . basename($file_fn) . "'. File not found.");
		}

		if ($multipleFiles) {
			$target_filename = basename($file_fn);
		}
		$target_dir_rfn  = $GLOBALS['dataDir'] . "/$target_dir";

		moveGSFileBNS($file_fn, "$target_dir/$target_filename");

		if (!rename($file_rfn, "$target_dir_rfn/$target_filename")) {
			getProjectLogger()->error("Error while writting moved file");
			throw new UnexpectedValueException("Error while writting moved file");
		}

		// move associated ids?
		/*
			if (isset($file['associated_files'])) {
				foreach ($file['associated_files'] as $assoc_id) {
					$assoc = getGSFile_fromId($assoc_id);
					if (isset($assoc)) {
						$r = moveGSFileBNS($assoc['path'], "$target_dir/" . basename($assoc_path));
						if ($r == "0") {
							$_SESSION['errorData']['Warning'][] = "File '" . basename($file_fn) . "' successfully moved, but  not its associated file (" . basename($assoc['path']) . ").";
							$result = false;
						}
					}
				}
			}
		*/
	}
}
