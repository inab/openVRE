<?php
require __DIR__."/../../config/bootstrap.php";
redirectOutside();

$asRoot = checkAdmin();

if($_REQUEST["type"] != 2) {
    // EXTRACT FILE METADATA FROM DMP FILE
    $mt   = getGSFile_fromId($_REQUEST["id"],"",$asRoot);
    $tool = getTool_fromId($mt["tool"],1);
}else{
    // EXTRACT JOB METADATA FROM USER JOBS
    $login = ($_REQUEST['user']?$_REQUEST['user']:$_SESSION['User']['_id']);
	$job = getUserJobPid($login,$_REQUEST["id"]);
    $mt = $job[$_REQUEST["id"]];
    $job_path = getAttr_fromGSFileId($mt["_id"],"path",$asRoot);
    if ($job_path){
        $mt["path"]=$job_path;
    }
    
}

// check Metadata
if (!$mt){
    print "Sorry, no metadata accessible for this resource";
    exit(0);
}

?> <h3>Item Metadata</h3><?php 


// File Project and Name 
if($mt["path"] != "") {
    $name_th = ((isset($mt['type']) && $mt['type']=="dir")? "Directory Name":"File Name");
    $p = explode("/", $mt['path']);
    $project_name="foo project";
    if (isset($mt['project']) && isProject($mt['project'])){
        $project = getProject($mt['project']);
        $project_name = $project['name'];
    }
    
?>
<table class="table table-striped table-bordered">
    <tbody>
    <tr>
        <th><b><?php echo $name_th;?></b></th>
    </tr>
	<tr>
        <td><?php print printFilePath_fromPath($mt['path'],$asRoot);?></td>
    </tr>
    </tbody>
</table>
<?php
}

// Description

if($mt["description"] != "") {
?>
<table class="table table-striped table-bordered">
	<tbody><tr>
			<th><b>Description </b></th>
	</tr>
	<tr>
			<td><?php echo nl2br ($mt["description"]); ?></td>
	</tr>
	</tbody>
</table>
<?php
}

// Validated

if(!isset($mt["validated"]) || $mt["validated"]) {
    $mt['validated'] = "TRUE";
}else{
    $mt['validated'] = "FALSE";
}
if ($mt['validated'] == "FALSE"){
?>
<table class="table table-striped table-bordered">
	<tbody><tr>
			<th><b>Valid Metadata
                <i class="icon-question tooltips" data-container="body" data-html="true" data-placement="right" data-original-title="<p align='left' style='margin:0'>This file has missing or incorrect metadata. Please, click 'Validate Metadata' and fill in the form.</p>"></i>
            </b></th>
        	</tr>
			<tr>
            <td><?php echo nl2br ($mt["validated"]);?>
                &nbsp;&nbsp;
                <?php if($mt["validated"] == "FALSE") { ?> 
                    <a style="margin-left:10px;" href="getdata/editFile.php?fn[]=<?php echo $mt["_id"]; ?>" class="btn btn-xs green">Validate Metadata</a>
                <?php } ?>
            </td>
	    	</tr>
	</tbody>
</table>
<?php
}


// Data_type, file_type, and OTHER METADATA (for files)

if($_REQUEST["type"] == 1) {
    // show data_type, file_type
    $dt = $GLOBALS['dataTypesCol']->findOne(array('_id' => $mt["data_type"]));

    // show taxon_id and assembly
    if(!isset($mt["oeb_dataset_id"])){
       $datasetId = "N/A";
    }else{
        $datasetId = $mt['oeb_dataset_id'];
    }
    if(!isset($mt["oeb_community_ids"])){
        $community  = "N/A";
    }else{
	$communities = getCommunities();
	if (!is_array($mt['oeb_community_ids'])){$mt['oeb_community_ids'] = array($mt['oeb_community_ids']);}
	foreach ($mt['oeb_community_ids'] as $comm){
	    if ( isset($communities[$comm]) ){
		$community = '<a href="https://openebench.bsc.es/html/scientific/'.$comm.'" target="_blank">'.$communities[$comm]["acronym"].'</a> ';
	    }else{
		$community = "$comm";
	    }
	}
    }

    ?>
    <table class="table table-striped table-bordered">
        <tbody>
        <tr>
    	    <th style="width:50%;"><b>Data Type</b></th>
    	    <th><b>File Type</b></th>
    	</tr>
    	<tr>
   			<td><?php echo $dt["name"];if(!isset($dt["name"])) echo "N/A"; ?></td>
   			<td><?php echo $mt["format"]; if(!isset($mt["format"])) echo "N/A"; ?></td>
    	</tr>
    	</tbody>
    </table>

    <?php
    if(isset($mt["paired"]) || isset($mt["sorted"])){
    ?>
    <table class="table table-striped table-bordered">
        <tbody>
        <tr>
    	    <th style="width:50%;"><b>BAM type</b></th>
    	    <th><b>BAM sorted</b></th>
    	</tr>
    	<tr>
   			<td><?php echo $mt["paired"]. " end"; ?></td>
   			<td><?php if($mt["sorted"] == "sorted"){echo "TRUE";}else{echo "FALSE";} ?></td>
    	</tr>
    	</tbody>
    </table>
    <?php
    }
}
?>

<?php

// Expiration date


$expiration ="";
if (isset($mt['expiration'])){
    if (is_object($mt['expiration'])){
    	$expiration_date = $mt['expiration']->toDateTime()->format('U');
        $days2expire = intval(( $expiration_date - time() ) / (24 * 3600));
        $mt['expiration'] = strftime('%Y/%m/%d %H:%M', $expiration_date);
        if ($days2expire < 0 ){
            $expiration = "File/folder has not expiration date";
        }else{
            if ($days2expire < 7)
                $expiration = $mt['expiration'] ." ( in <span style=\"color:#b30000;font-weight:bold;\">".$days2expire."</span> days)";
            else
                $expiration  =$mt['expiration'] ." ( in $days2expire days)";
        }
    }elseif ($mt['expiration'] == -1){
        $expiration = "This file/folder never expires";
    }else{
        $expiration = $mt['expiration'];
    }
}else{
    $expiration = "File/folder has not expiration date";
}
?>

<table class="table table-striped table-bordered">
	<tbody><tr>
			<th><b>File expiration</b></th>
				       </tr>
			<tr>
				<td><?php echo $expiration; ?></td>
                
			</tr>
	</tbody>
</table>


<?php

// Tool

if(isset($mt["tool"]) ) {
?>
<table class="table table-striped table-bordered">
	<tbody><tr>
			<th><b>Generated by Tool</b></th>
				       </tr>
			<tr>
				<td><?php echo $tool["name"]; ?></td>
                
			</tr>
	</tbody>
</table>

<?php
}


// Input File/s or URL

if(isset($mt['input_files']) || $mt['source_url'] ) {
?>

<table class="table table-striped table-bordered">
    <tbody>
    <tr>
	    <th><b>File source/s</b></th>
    </tr>
    <tr>
        <td><?php
	if (count($mt['input_files'])){ ?>
             <ul class="feeds" id="list-files-run-tools">
																		
		<?php
                foreach ($mt['input_files'] as $input_name => $inps ){
                  if (!is_array($inps)){
                    $inps = array($inps);
                  }
                  foreach ( $inps as $inp ){
                    if ($inp == "0"){
			print "Data externally loaded or created";
		    }else{
                        $path = getAttr_fromGSFileId($inp, 'path',$asRoot);
			if ($path){
                            print printFilePath_fromPath($path,$asRoot);
			}else{
			    print "Data provenance lost. Internally annotated as $inp";
			}
                    }
                  }
	       }?>
	     </ul>
	<?php } ?>
        </td>
    </tr>
	
    <?php if(isset($mt['source_url']) ) { ?>

        <tr>
	    <th><b>Source URL</b></th>
        </tr>
        <tr>
	    <td><a href="<?php echo $mt["source_url"];?>" target="_blank" ><?php echo $mt["source_url"];?></a></td>
        </tr>
    <?php } ?>

    </tbody>
</table>
<?php
}

// Associated Files

if(isset($mt["associated_files"])) {
?>
<table class="table table-striped table-bordered">
	<tbody><tr>
			
			<th><b>Associated Files</b></th>
	       </tr>
			<tr>
				
                <td><?php
											if (count($mt['associated_files'])){
												?>
												<ul class="feeds" id="list-files-run-tools">
																			
												<?php
											 foreach ($mt['associated_files'] as $inp){
                                                $path = getAttr_fromGSFileId($inp, 'path',$asRoot);
                                                print printFilePath_fromPath($path,$asRoot);

											 }
												?>
											</ul>
											<?php
                     }else{
                         echo "";
                    }?>
                </td>
			</tr>
	</tbody>
</table>
<?php
}


// Arguments

if(isset($mt["arguments"])) {
?>
<table class="table table-striped table-bordered">
	<tbody><tr>
			<th><b>Arguments</b></th>
				       </tr>
			<tr>
                <td>
                    <table class="table table-bordered">
                <?php
                foreach ($mt["arguments"] as $k => $v){
                    if (isset($tool['arguments'][$k]['description'])){
                        $k = $tool['arguments'][$k]['description'];
                    }
                    if(gettype($v) == "array") {
						echo "<tr><td>$k</td><td>";
						foreach($v as $val) echo $val."<br>";
						echo "</td></tr>";
                    }elseif(gettype($v) == "boolean") {
                        $v = ($v?"TRUE":"FALSE");
                        echo "<tr><td>$k</td><td>$v</td></tr>";
                    } else {
						echo "<tr><td>$k</td><td>$v</td></tr>";
					}
                }
                ?>
                    </table>
                </td>
                
			</tr>
	</tbody>
</table>
<?php
}


// cloudName, memory and cpus
$mem = "";
$cpus = "";
if(!isset($mt['cloudName']) || strlen($mt["cloudName"]) == 0)
    $mt['cloudName'] = $GLOBALS['cloud'];

if(isset($mt['imageType'])){
    $mem  = $mt['imageType']['memory'];
    $cpus = $mt['imageType']['cpus'];
}elseif(isset($tool['infrastructure']['memory'])){
    $mem  = $tool['infrastructure']['memory'];
    $cpus = $tool['infrastructure']['cpus'];
}

if($mt["cloudName"] != "") {
?>
<table class="table table-striped table-bordered">
	<tbody><tr>
    	    <th style="width:50%;"><b>Cloud infrastructure</b></th>
            <th><b><?php if ($mem){echo "Resources";}?></b></th>
	</tr>
			<tr>
				<td><?php echo $mt['cloudName'];?></td>
                <td><?php if ($mem){echo "Cores: $cpus &nbsp; RAM: $mem GB";}?></td>
			</tr>
	</tbody>
</table>
<?php
}
?>




<!--- DATA ONLY FOR DEVEL USERS -->

<?php
if ($_SESSION['User']['Type']== 0 || $_SESSION['User']['Type'] == 1){
?>

<h3>Development data</h3>


<table class="table table-striped table-bordered">
	<tbody><tr>
			<th style="background-color: #e7ecf1;"><b>Metadata resource - <a href="http://multiscale-genomics.readthedocs.io/projects/mg-dm-api/rest.html" title="Data Management RESTful API" target="_blank">DMP</a></b></th>
	</tr>
			<tr>
            <td>
                <?php if ($_SESSION['User']['Type']== 0 || $_SESSION['User']['Type'] == 1){ ?>
                    <br/>
                    <pre style="font-size:0.7em;margin:10px 25px;"><?php echo json_encode($mt, JSON_PRETTY_PRINT);?></pre>
                <?php } ?>
            </td>
			</tr>
	</tbody>
</table>


<table class="table table-striped table-bordered">
	<tbody><tr>
			<th style="background-color: #e7ecf1;"><b>Resource location</b></th>
	</tr>
			<tr>
            <td><?php
            if ($mt['path']){echo $mt['path'];}else{echo "Job has no execution directory !!";}?>
            </td>
			</tr>
	</tbody>
</table>

<?php
                
if($_REQUEST["type"] == 2) { ?>
<table class="table table-striped table-bordered">
	<tbody><tr>
			<th style="background-color: #e7ecf1;"><b>Job identifier</b></th>
	</tr>
	<tr>
			<td><?php echo $_REQUEST['id'];?></td>
	</tr>
	</tbody>
</table>

<?php
}

}
?>
<!--- END OF DEVEL USERS -->



<!--- BLUE BUTTONS -->

<div id="meta-log">
        <?php
        // Log button
        if(file_exists($mt['log_file'])) { ?>
			<a href="workspace/workspace.php?op=openPlainFileFromPath&fnPath=<?php echo urlencode($mt['log_file']); ?>" class="btn green" target="_blank"><i class="fa fa-file-text-o"></i> VIEW LOG FILE </a>
		<?php }else{ ?>
			<a href="javascript:;" class="btn grey tooltips" data-container="body" data-html="true" data-placement="bottom" data-original-title="<p align='left' style='margin:0'>Fie not available</p>"><i class="fa fa-exclamation-triangle"></i> VIEW LOG FILE </a>
		<?php } 
    
        
        // Devel buttons

        if(($_SESSION['User']['Type'] == 0) || ($_SESSION['User']['Type'] == 1)) { ?>

			<?php if(file_exists($mt['submission_file'])) { ?>
			<a href="workspace/workspace.php?op=openPlainFileFromPath&fnPath=<?php echo urlencode($mt['submission_file']); ?>" class="btn green" target="_blank"><i class="fa fa-paper-plane"></i> VIEW SUBMIT FILE </a>
			<?php }else{ ?>
			<a href="javascript:;" class="btn grey tooltips" data-container="body" data-html="true" data-placement="bottom" data-original-title="<p align='left' style='margin:0'>Fie not available</p>"><i class="fa fa-exclamation-triangle"></i> VIEW SUBMIT FILE </a>
			<?php } ?>
			<?php if(file_exists($mt['config_file'])){ ?>
			<a href="workspace/workspace.php?op=openPlainFileFromPath&fnPath=<?php echo urlencode($mt['config_file']); ?>" class="btn green" target="_blank"><i class="fa fa-cog"></i> VIEW CONFIG FILE </a>
			<?php }elseif(file_exists($GLOBALS['dataDir'].$mt['path']."/".$GLOBALS['tool_config_file']) ) { ?>
			<a href="workspace/workspace.php?op=openPlainFileFromPath&fnPath=<?php echo urlencode($mt['path']."/".$GLOBALS['tool_config_file']); ?>" class="btn green" target="_blank"><i class="fa fa-cog"></i> VIEW CONFIG FILE </a>
			<?php }else{ ?>
			<a href="javascript:;" class="btn grey tooltips" data-container="body" data-html="true" data-placement="bottom" data-original-title="<p align='left' style='margin:0'>Fie not available</p>"><i class="fa fa-exclamation-triangle"></i> VIEW CONFIG FILE </a>
			<?php } ?>
			<?php if(file_exists($mt['metadata_file']) ){ ?>
			<a href="workspace/workspace.php?op=openPlainFileFromPath&fnPath=<?php echo urlencode($mt['metadata_file']); ?>" class="btn green" target="_blank"><i class="fa fa-tags"></i> VIEW META FILE </a>
			<?php }elseif(file_exists($GLOBALS['dataDir'].$mt['path']."/".$GLOBALS['tool_metadata_file']) ) { ?>
			<a href="workspace/workspace.php?op=openPlainFileFromPath&fnPath=<?php echo urlencode($mt['path']."/".$GLOBALS['tool_metadata_file']); ?>" class="btn green" target="_blank"><i class="fa fa-tags"></i> VIEW META FILE </a>
			<?php }else{ ?>
			<a href="javascript:;" class="btn grey tooltips" data-container="body" data-html="true" data-placement="bottom" data-original-title="<p align='left' style='margin:0'>Fie not available</p>"><i class="fa fa-exclamation-triangle"></i> VIEW META FILE </a>
			<?php } ?>
			<?php if(file_exists($mt['stageout_file']) ){ ?>
			<a href="workspace/workspace.php?op=openPlainFileFromPath&fnPath=<?php echo urlencode($mt['stageout_file']); ?>" class="btn green" target="_blank"><i class="fa fa-line-chart"></i> VIEW RESULTS FILE </a>
			<?php }elseif(file_exists($GLOBALS['dataDir'].$mt['path']."/".$GLOBALS['tool_stageout_file']) ) { ?>
			<a href="workspace/workspace.php?op=openPlainFileFromPath&fnPath=<?php echo urlencode($mt['path']."/".$GLOBALS['tool_stageout_file']); ?>" class="btn green" target="_blank"><i class="fa fa-line-chart"></i> VIEW RESULTS FILE </a>
			<?php }else{ ?>
			<a href="javascript:;" class="btn grey tooltips" data-container="body" data-html="true" data-placement="bottom" data-original-title="<p align='left' style='margin:0'>Fie not available</p>"><i class="fa fa-exclamation-triangle"></i> VIEW RESULTS FILE </a>
			<?php }
        } ?>
</div>

