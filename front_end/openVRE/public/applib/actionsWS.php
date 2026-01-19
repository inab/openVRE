<?php

require __DIR__ . "/../../config/bootstrap.php";

use OpenVRE\LoggerFactory;

redirectOutside();


function getWorkspaceActionsLogger()
{
	static $logger = null;

	if ($logger === null) {
		$logger = LoggerFactory::getLogger('Workspace actions interface');
	}

	return $logger;
}


// Check operation and input files
if (is_null($_REQUEST['op'])) {
	header("location:../workspace/");
}

if (is_null($_REQUEST['fn']) && is_null($_REQUEST['fnPath']) && !preg_match('/cancelJob/', $_REQUEST['op'])) {
	getWorkspaceActionsLogger()->error("Selected operation ('" . $_REQUEST['op'] . "') requires at least one file. Any file name received.");
	header("location:../workspace/");
}

$filePath = getAttr_fromGSFileId($_REQUEST['fn'], 'path');
$rfn      = $GLOBALS['dataDir'] . "/$filePath";

// Process operation
if (isset($_REQUEST['op'])) {
	switch ($_REQUEST['op']) {
		case 'deleteAll':
		case 'deleteSure':
			deleteFiles($_REQUEST['fn']);
			break;

		case 'deleteDirOk':
			if (basename($filePath) == "uploads" || basename($filePath) == "repository") {
				getWorkspaceActionsLogger()->error("Cannot delete structural directory '$filePath'.");
				break;
			}

			deleteGSDirBNS($_REQUEST['fn']);
			exec("rm -r \"$rfn\" 2>&1", $output);
			if (error_get_last()) {
				getWorkspaceActionsLogger()->error("Cannot delete directory '$filePath'. " . implode(" ", $output));
			}

			break;
	}
}
