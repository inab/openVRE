<?php

require __DIR__."/../../config/bootstrap.php";
redirectOutside();

$userinfo = posix_getpwnam("lcodo");
var_dump($userinfo);
?>

<?php require "../htmlib/header.inc.php"; ?>

<body class="page-header-fixed page-sidebar-closed-hide-logo page-content-white page-container-bg-solid">
  <div class="page-wrapper">

  <?php require "../htmlib/top.inc.php"; ?>
  <?php require "../htmlib/menu.inc.php"; ?>


<!-- BEGIN CONTENT -->
	       <div class="page-content-wrapper">
		  <!-- BEGIN CONTENT BODY -->
		  <div class="page-content">
		      <!-- BEGIN PAGE HEADER-->
		      <!-- BEGIN PAGE BAR -->
		      <div class="page-bar">
			 <ul class="page-breadcrumb">
			   <li>
			       <span>User Workspace</span>
			       <i class="fa fa-circle"></i>
			   </li>
			   <li>
			       <span>Edit File</span>
			   </li>
			 </ul>
		      </div>
		      <!-- END PAGE BAR -->
		      <!-- BEGIN PAGE TITLE-->
		      <h1 class="page-title"> Edit File
			 <small>edit file metadata</small>
		      </h1>
		      <!-- END PAGE TITLE-->
		      <!-- END PAGE HEADER-->

			<?php
			//recover REQUEST data if comming from failed form processing
			//print "<br/>SESSION<br/>";
			//var_dump($_SESSION);
			/*if (isset($_REQUEST['requestID'])){
				print "<br/>VARDUMP REQUEST INI<br/>";
				var_dump($_REQUEST);
				$requestID = $_REQUEST['requestID'];
				print "<br/>RECOVERING from REQUEST[REQUEST_$requestID]<br/>";
				$_REQUEST = $_SESSION["REQUEST_$requestID"];
				print "<br/>VARDUMP REQUEST FIN<br/>";
				var_dump($_REQUEST);
				unset($_SESSION["REQUEST_$requestID"]);
			}*/
			
			//chek input files from REQUEST[fn]
			$filesData=Array();
			$filesMeta=Array();
			if (!isset($_REQUEST['fn']) || !$_REQUEST['fn'] || !count($_REQUEST['fn']) ) {
				$_SESSION['errorData']['Error'][]="No file selected. Please, select or upload a file to edit.";
				// TODO: Go back to uploadForm.php?
			}else{
				if (!is_array($_REQUEST['fn'])){
				        $_REQUEST['fn']=Array($_REQUEST['fn']);
				}
    			foreach ( $_REQUEST['fn'] as $idx => $v) {
					$file     = $_REQUEST['fn'][$idx];
					$fileData = $GLOBALS['filesCol']    ->findOne(array('_id' => $file, 'owner' => $_SESSION['User']['id']));
					$fileMeta = $GLOBALS['filesMetaCol']->findOne(array('_id' => $file));
	        			if (empty($fileData)){
						$_SESSION['errorData']['Error'][]="Problems while loading data. $file not found in the database.";
						continue;
					}else{
					    $filesData[$idx]=$fileData;
					    $filesMeta[$idx]=$fileMeta;
					}
				}
			}

			/*var_dump($filesData);
			echo '<br><br>';
			var_dump($filesMeta);*/
				
			?>

		    <div class="row">
			<div class="col-md-12">
			<?php  
				$error_data = false;
				if ($_SESSION['errorData']){ 
					$error_data = true;
				?>
				<div class="alert alert-danger">
			        <?php 
				foreach($_SESSION['errorData'] as $subTitle=>$txts){
			        	print "<strong>$subTitle</strong><br/>";
				       foreach($txts as $txt){
				       	print "<div>$txt</div>";
					}
				}
		  		unset($_SESSION['errorData']);
		  		?>
			     </div>
			    <?php } ?>

			<?php  if (!$error_data){ ?>

			 <div class="mt-element-step">
				<div class="row step-line">
				    <div class="mt-step-desc">
				    Add metadata to validate the file. Once the file has the <em>Validated</em> state, you will be able to use it in the workspace. 
					If you don't set the metadata at this moment, you can edit the file afterwards clicking the <em>Edit</em> button on the workspace table. 
					</div>
					<div>&nbsp;</div>
				</div>
			     </div>

			    <form name="uploadFiles" id="uploadFiles" action="" method="post" enctype="multipart/form-data">
			    <input type="hidden" name="op" id="op">
			    <div class="note note-info">
				  <div class="mt-radio-list" style="padding-bottom:0;">
					<?php
					$FN     = $filesData[0]['_id'];
					foreach ( $filesData as $idx => $v) {
						$fn         = $filesData[$idx]['_id'];
						$fnPath     = $filesData[$idx]['path'];
						$validated  = $filesMeta[$idx]['validated'];
		
				        	//get file compression
						//if (!isset($_REQUEST['compressed'][$idx])){
						//	$fileExtensionFN = strtoupper(pathinfo($fn,PATHINFO_EXTENSION));
						//    $fileExtensionFN = preg_replace('/_\d$/',"",$fileExtensionFN);
						//  if (in_array(".".$fileExtensionFN,Array(".BZ2",".GZ",".RAR",".ZIP",".TGZ",".TAR"))){
	    		       			//		$_REQUEST['compressed'][$idx]=1;
						//	}else{
						//		$_REQUEST['compressed'][$idx]=0;
						//	}
						//}

						$file        = formatData($filesData[$idx]);
						switch ($validated){
							case '0':
								$validationState ="NOT VALIDATED";
								$classState = $GLOBALS['STATES_COLOR'][4];
								break;
							case '1':
								$validationState ="VALIDATED";
								$classState = $GLOBALS['STATES_COLOR'][2];
								break;
							case '2':
								$validationState ="READY";
								$classState = $GLOBALS['STATES_COLOR'][1];
								break;
							case '3':
								$validationState ="PROCESSING";
								$classState = $GLOBALS['STATES_COLOR'][3];
								break;
							default:
								$validationState = "NOT VALIDATED";
								$classState = $GLOBALS['STATES_COLOR'][4];	
						}
						
						?>
					      <label class="mt-radio mt-radio-outline" style="padding-left:0; margin-bottom:0; cursor:default;">
						<h4><?php echo  basename($fnPath);?> &bull; 
						<small>
						<strong><?php echo $file['size'];?></strong>
						<?php echo $file['mtime'];?></small>
						 &bull; <span id="file<?php echo $idx; ?>-state" class="<?php echo $classState; ?> file-state"><?php echo  $validationState;?></span>
						</h4>
						<input type="hidden" name="fn[]" id='fn' value="<?php echo $fn;?>"/>
						<!--<span></span>-->
					      </label>
				        <?php } ?>
				  </div>
			    </div>


			    <?php
			    //TODO add $defs as global like reference genomes?¿
			    //metadata default values
			    /*$defs = Array(
					//general
					'format'     => 'UNK',
					'description'=> "",
					'compressed' => 0,
					'validated'  => 0,
					//specific for aligned coords (BW, GFF, FASTQ aligned, etc..)
					'refGenome'  => "",
					//specific for BAM
					'paired'     => 'paired',
					'sorted'     => TRUE,
				);*/

			    //extract or guess file formats
			    foreach ($filesData as $idx => $v ) {
				$fn            = $filesData[$idx]['_id'];
				$fileExtension = "";
				if (isset($_REQUEST['format']) && isset($_REQUEST['format'][$idx]) && $_REQUEST['format'][$idx]){
					$fileExtension=$_REQUEST['format'][$idx];
				}elseif (isset($filesMeta[$idx]['format'])){
					$fileExtension=$filesMeta[$idx]['format'];
				}elseif(isset($filesData[$idx]['_id'])){
					$fnPath = $filesData[$idx]['path'];
					$fileInfo = pathinfo($fnPath);
					if (isset($fileInfo['extension'])){
					      $fileExtension = strtoupper($fileInfo['extension']);
					      $fileExtension = preg_replace('/_\d$/',"",$fileExtension);
					      if (in_array(".".$fileExtension,Array(".BZ2",".GZ",".RAR",".ZIP",".TGZ",".TAR") )){
						  $fileExtension = strtoupper(pathinfo(str_replace(".".$fileInfo['extension'],"",$fnPath), PATHINFO_EXTENSION));
						  $fileExtension = preg_replace('/_\d+$/',"",$fileExtension);
						  $_REQUEST['compressed'][$idx]=1;
					      }else{
						  $_REQUEST['compressed'][$idx]=0;
					      }
					}
				}


				//fill in form defaults from: REQUEST > FILEMETA DB > DEFAULTS $def
				/*foreach ($defs as $attr => $v ){
				       	if (isset($_REQUEST[$attr][$idx])){
						$filesMeta[$idx][$attr]=$_REQUEST[$attr][$idx];
					}elseif (!isset($filesMeta[$idx][$attr])){
						$filesMeta[$idx][$attr]=$v;
					}
				}*/

				?>
				<!-- Start Metadata Division per File. Displayed only if File is active (according radio button)  -->	
				<div class="note note-info formInputs" id="formInputs<?php echo $idx;?>" style="position:relative;">

			      <div class="disable-form display-hide"></div>

				  <!-- While showing changes to be done, make Metadata Division Readonly	
			          <?php if ( $state[$idx]=="disabled" || $_REQUEST['compressed'][$idx] ){ ?>
			            <div id="disableFormInputs" style="position:absolute;z-index:11;top:0;left:0;width:100%;height:100%;background-color:#FFFFFF;opacity: 0.6;"></div>
			          <?php } ?>				
				     TODO "conversions" should be included as "actions" -->

				  <div class="form-group formatTR" id="formatTR<?php echo $idx;?>">
				        <label>File Format</label>
				        <select id="format<?php echo $idx;?>" name="format" onchange="customfromFormat(this.value, <?php echo $idx;?>)" class="form-control formatSelector">
						 <!--<option value="" >Select the file format</option>-->
						 <option value="BAM"  <?php if ($fileExtension =="BAM") {echo "selected";}?> >BAM</option>
						 <!-- <option value="SAM"  <?php if ($fileExtension =="SAM") {echo "selected";}?> >SAM </option> -->
						 <option value="BED"  <?php if ($fileExtension =="BED") {echo "selected";}?> >BED </option>
						 <option value="BEDGRAPH"<?php if ($fileExtension=="BEDGRAPH"){echo "selected";}?>>BEDGRAPH</option>
						 <option value="WIG"  <?php if ($fileExtension =="WIG") {echo "selected";}?> >WIG</option>
						 <option value="BW"   <?php if ($fileExtension =="BW")  {echo "selected";}?> >BIGWIG</option>
						 <option value="GFF"  <?php if (in_array($fileExtension,Array("GTF","GFF"))){echo "selected";}?> >GFF/GTF</option>
						 <option value="GFF3" <?php if ($fileExtension =="GFF3"){echo "selected";}?> >GFF3</option>
						 <option value="FASTQ"<?php if (in_array($fileExtension,Array("FASTQ","FQ"))){echo "selected";}?>>FASTQ</option>
						 <option value="FASTA"<?php if (in_array($fileExtension,Array("FA","FASTA","FN"))){echo "selected";}?>>FASTA</option>
						 <option value="PDB"  <?php if ($fileExtension =="PDB") {echo "selected";}?> >PDB</option>
						 <option value="TXT"  <?php if ($fileExtension =="TXT"){echo "selected";}?> >TXT</option>
						 <option value="UNK"  <?php if ($fileExtension =="UNK"){echo "selected";}?> >OTHER</option>
				        </select>
				  </div>

				  <div class="form-group refGenomeTR" id="refGenomeTR<?php echo $idx;?>">
				        <label>Reference genome</label>
				        <span class="tooltip-mt-radio"><i class="icon-question tooltips" data-container="body" data-placement="right" data-original-title="Reference genome description"></i></span>
				        <select name="refGenome" class="form-control">
						<option value="">Select the reference genome</option>
						<!--<option value="ref1">reference genome 1</option>
						<option value="ref2">reference genome 2</option>
						<option value="ref3">reference genome 3</option>-->
						
						<?php
						$refList  = scanDir($GLOBALS['refGenomes']);
						foreach ($refList as $ref){
						    if ( preg_match('/^\./', $ref) || !is_dir($GLOBALS['refGenomes']))
							continue;
						    if (isset($GLOBALS['refGenomes_names'][$ref]))
							$refName=$GLOBALS['refGenomes_names'][$ref];
						    else
							$refName=$ref;
						    if ($filesMeta[$idx]['refGenome'] == $ref){
							print "<option selected value=\"$ref\">$refName</option>";
						    }else{
							print "<option value=\"$ref\">$refName</option>";
						    }
						} ?>
					
				        </select>
					<span class="help-block font-red warn-ref-gen" style="display:none;">This field is required.</span>	
				  </div>

				  <div class="form-group pairedTR" id="pairedTR<?php echo $idx;?>">
				        <label>BAM type</label>
				        <div class="mt-radio-inline">
					   <label class="mt-radio mt-radio-outline">
					       <input type="radio" name="paired<?php echo $idx; ?>" value="paired" checked> Paired-End
					       <span></span>
					   </label>
					   <label class="mt-radio mt-radio-outline">
					       <input type="radio" name="paired<?php echo $idx; ?>" value="single"> Single-End
					       <span></span>
					   </label>
				        </div>
				  </div>

				  <div class="form-group sortedTR" id="sortedTR<?php echo $idx;?>">
				        <label>Coordinates sorting</label>
				        <div class="mt-radio-inline">
					   <label class="mt-radio mt-radio-outline">
					       <input type="radio" name="sorted<?php echo $idx; ?>" value="sorted" onclick="showHideSortInfo(0,<?php echo $idx;?>)" checked> Sorted	
					       <span></span>
					   </label>
					   <label class="mt-radio mt-radio-outline">
					       <input type="radio" name="sorted<?php echo $idx; ?>" value="unsorted" onclick="showHideSortInfo(1, <?php echo $idx;?>)" > Unsorted
					       <span></span>
					   </label>
				        </div>
				  </div>

				  <div class="alert alert-warning sortInfo" id="sortInfo<?php echo $idx;?>">
				      <strong>BAM</strong> file will be authomatically sorted by coordinate order
				  </div>


				  <div class="form-group descriptionTR" id="descriptionTR<?php echo $idx;?>">
				        <label>Description</label>
				        <textarea name="description" id="description" class="form-control" rows="6" placeholder="Write a short description here..."><?php echo $filesMeta[$idx]['description'];?></textarea>
				  </div>

				  <!--<input type="hidden" name="validated[]" value="<?php print $filesMeta[$idx]['validated'];?>"/>-->
				  <input type="hidden" name="fn" value="<?php print $fn;?>"/>


				  <div class="form-actions btn-send-data">
				  	<input type="button" class="btn green snd-metadata-btn" value="SEND METADATA" onclick="sendMetadata(<?php echo $idx; ?>, 1);" style="position:relative;z-index:20;" >
				  </div>
				
				</div>
				
				<?php
				if (isset($_SESSION['validation'][$fn]['msg'])){ ?>
				   <div class="note note-info">
					<b>Validation summary:</b></br>
				        <ul style="list-style:disc">
				         <?php
					 foreach ($_SESSION['validation'][$fn]['msg'] as $line){
			                    print "<li>$line</li>";
			              	 }?>
				        </ul>
					<i>Press <b>"OK"</b> to record the proposed changes.</i>
            			   </div>
			        <?php }

				if (isset($_SESSION['enqueued'][$idx]['msg'])){ ?>
				   <div class="note note-info">
					<?php echo $_SESSION['enqueued'][$IDX]['msg']?>
				   </div>	
				<?php
				}?>
			    
				<div class="note feedback-file display-hide" id="feedback-file<?php echo $idx; ?>">
                    <h4 class="block"></h4>
					<span></span>
				</div>


				<?php } //end main foreach!!! ?>

			    <div class="form-actions">
			
			  <div id="bottom-validated-files">

				<div id="go-out-uploadform"><input type="button" class="btn default" value="BACK TO WORKSPACE" onclick="location.href='/workspace/';" /></div>
			
			  </div>


			  </div>		
			</form>

			<?php }else{ ?>

			<div id="go-out-uploadform"><input type="button" class="btn default" value="BACK TO WORKSPACE" onclick="location.href='/workspace/';" /></div>


			<?php } //END if NOT ERROR ?>

			</div>
		      </div>
		  </div>
		  <!-- END CONTENT BODY -->
	       </div>
	       <!-- END CONTENT -->

			<div class="modal fade" id="myModal1" tabindex="-1" role="basic" aria-hidden="true">
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
							<h4 class="modal-title">Beware!</h4>
						</div>
						<div class="modal-body">You haven't completed the validation of this file, you should edit the metadata afterwards through the workspace table. </div>
						<div class="modal-footer">
							<button type="button" class="btn dark btn-outline" data-dismiss="modal">Cancel</button>
							<button type="button" class="btn green btn-modal-ok">I'm aware</button>
						</div>
					</div>
					<!-- /.modal-content -->
				</div>
				<!-- /.modal-dialog -->
			</div>


<?php 

require "../htmlib/footer.inc.php"; 
require "../htmlib/js.inc.php";

?>
