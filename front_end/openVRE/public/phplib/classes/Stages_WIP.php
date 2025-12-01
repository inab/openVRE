<?php
/**
 * ================================================
 * ⚠️ WORK IN PROGRESS (WIP) — DRAFT VERSION ⚠️
 * -----------------------------------------------
 * This file is currently under development.
 * Code structure, logic, and output may change.
 * Do NOT use this version in production environments.
 * -----------------------------------------------
 */

// Define an interface for the stages
interface StageInterface {
    public function execute();  // All stages must have an execute method
}

class StageInData implements StageInterface {
    private $workingDir;
    private $dataMeta;
    private $pipelineDirId;

    public function __construct(Pipeline $pipeline, $files, $tool, $executionPath, $argumentsExec, $pipelineDirId, $debug = false) {
        $this->pipelineDirId = $pipelineDirId;
        $this->workingDir = $this->createWorkingDirInPipeline($pipelineDirId);
        $this->dataMeta = new DataTransfer($files, 'async', $tool, $executionPath, $argumentsExec);
    }

    private function createWorkingDirInPipeline($pipelineDirId) {
        return $pipelineDirId . "/stagein_working_dir_" . uniqid();
    }

    public function execute() {
        // Perform the data transfer and other actions here
        $stageinDataDir = $this->dataMeta->createWorking_dir($this->pipelineDirId);
        list($execCommand, $inputLocations) = $this->dataMeta->syncFiles();
        $result = $this->dataMeta->prepareRSyncExecution();
        return "Stage-In Data executed in {$this->workingDir} with rsync command: $result";
    }
}


class JobExecution implements StageInterface {
    private $jobMeta;
    private $pipelineDirId;
    private $debug;
    private $filesPub;

    public function __construct(Pipeline $pipeline, $tool, $execution, $project, $description, $argumentsExec, $inputFiles, $filesPub, $pipelineDirId, $debug = false) {
        $this->pipelineDirId = $pipelineDirId;
        $this->debug = $debug;
        $this->filesPub = $filesPub;
        
        // Initialize ToolJob
        $this->jobMeta = new ToolJob($tool, $execution, $project, $description, $argumentsExec);
    }

    public function execute() {
        if ($this->debug) {
            echo "<br/>NEW TOOLJOB SET:</br>";
            var_dump($this->jobMeta);
        }

        // Ensure arguments array is set
        if (!isset($_REQUEST['arguments']) || !is_array($_REQUEST['arguments'])) {
            $_REQUEST['arguments'] = [];
        }
        
        // Set arguments
        $this->jobMeta->setArguments($_REQUEST['arguments'], $this->jobMeta);

        // Set input files
        $result = $this->jobMeta->setInput_files($_REQUEST['input_files'], $this->jobMeta, $this->filesPub);
        
        if ($this->debug) {
            echo "<br/>TOOL Input_files are:</br>";
            var_dump($this->jobMeta->input_files);
        }

        if ($result == "0") {
            redirect($_SERVER['HTTP_REFERER']);
        }

        // Create working directory
        $workDirId = $this->jobMeta->createWorking_dir($this->pipelineDirId);
        
        if ($this->debug) {
            echo "<br/></br>WD CREATED SUCCESSFULLY AT: $this->jobMeta->working_dir<br/>";
        }

        if (!$workDirId) {
            redirect($_SERVER['HTTP_REFERER']);
        }

        // Prepare execution
        $executionResult = $this->jobMeta->prepareExecution($this->jobMeta, $_REQUEST['input_files'], $this->filesPub);
        
        if ($this->debug) {
            echo "<br/></br>PREPARE EXECUTION RETURNS ($executionResult). <br/>";
        }

        if ($executionResult == 0) {
            redirect($_SERVER['HTTP_REFERER']);
        }

        // Process public input files
        if (!empty($_REQUEST['input_files_public_dir'])) {
            $this->processPublicInputFiles($_REQUEST['input_files_public_dir']);
        }
    }

    private function processPublicInputFiles($publicDir) {
        // Get metadata for public input files
        $filesPub = $this->jobMeta->createMetadata_from_Input_files_public($publicDir, $this->jobMeta);
        
        if ($this->debug) {
            echo "<br/>TOOL METADATA for Input_files_public are:</br>";
            var_dump($filesPub);
        }

        if (empty($filesPub)) {
            redirect($_SERVER['HTTP_REFERER']);
        }

        // Set public input files
        $result = $this->jobMeta->setInput_files_public($publicDir, $this->jobMeta, $filesPub);

        if ($this->debug) {
            echo "<br/>TOOL Input_files public are:</br>";
            var_dump($this->jobMeta->input_files_pub);
        }

        if ($result == "0") {
            echo "<br/>TOOL Input_files ZERO</br>";
            redirect($_SERVER['HTTP_REFERER']);
        }
    }
}


?>