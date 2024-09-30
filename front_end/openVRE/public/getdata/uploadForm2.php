<?php

require __DIR__."/../../config/bootstrap.php";
redirectOutside();

?>

<?php require "../htmlib/header.inc.php"; ?>

<body class="page-header-fixed page-sidebar-closed-hide-logo page-content-white page-container-bg-solid page-sidebar-fixed">
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
				  <a href="/home/">Home</a>
				  <i class="fa fa-circle"></i>
			      </li>
			   <li>
			       <span>Get Data</span>
			       <i class="fa fa-circle"></i>
			   </li>
			   <li>
			       <span>Edit File Metadata</span>
			   </li>
			 </ul>
		      </div>
		      <!-- END PAGE BAR -->
		      <!-- BEGIN PAGE TITLE-->
		      <h1 class="page-title"> Upload Files
			 <small>edit files metadata</small>
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
			}elseif (substr($_REQUEST['fn'][0],0,3) != substr($GLOBALS['AppPrefix'],0,3))  {
				$_SESSION['errorData']['Error'][]="An error occurred while registering your file. No valid file identifier received.";
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
			?>

		    <div class="row">
			<div class="col-md-12">
				<?php  
				$error_data = false;
				if (isset($_SESSION['errorData'])){ 
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

			<div class="alert alert-success">
				<?php if(sizeof($filesData) > 1) { ?>
					Your files have been successfully uploaded. Please add the appropriate metadata to them. 
				<?php } else { ?>
					Your file has been successfully uploaded. Please add the appropriate metadata to it. 
				<?php } ?>	
			</div>

			 <div class="mt-element-step">
				<div class="row step-line">
				    <div class="mt-step-desc">
				    Add metadata to validate the files. Once a file has the <em>Validated</em> state, you will be able to use it in the workspace. 
					If you don't set the metadata at this moment, you can edit the files afterwards clicking the <em>Edit</em> button on the workspace table. 
					</div>
					<?php require "../htmlib/stepsup.inc.php"; ?>	
				</div>
			     </div>

			    <!--<form name="uploadFiles" id="uploadFiles" action="" method="post" enctype="multipart/form-data">
					<input type="hidden" name="op" id="op" value="1">
					<input type="hidden" id="base-url"     value="<?php echo $GLOBALS['BASEURL']; ?>"/>-->

					<input type="hidden" name="numFiles" id="numFiles" value="<?php echo sizeof($filesData); ?>">

			    <div class="note note-info">
				  <div class="mt-radio-list" style="padding-bottom:0;">
					<?php
					$IDX    = (isset($_REQUEST['idx'])?$_REQUEST['idx']:key($_REQUEST['fn']));
					//print "<br/>filesData[$IDX] is<br/>";
					//var_dump($filesData);
					$FN     = $filesData[$IDX]['_id'];
					//print "<br/>FN<br/>";
					//var_dump($FN);
					$state  = Array();
					foreach ( $filesData as $idx => $v) {
						$fn     = $filesData[$idx]['_id'];
						$fnPath = $filesData[$idx]['path'];
		
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

						$state[$idx] = ((isset($_SESSION['validation'][$fn]['checkParams']) && $_SESSION['validation'][$fn]['checkParams'] == 1)? "disabled":"");
						$checked     = (($IDX == $idx)? "checked":"");
						$blockRadio  = ((!$checked && isset($filesData[$idx]['validated']) && $filesData[$idx]['validated'])?"disabled":"");
						$file        = formatData($filesData[$idx]);
						$validationState = "NOT VALIDATED";
						$classState = $GLOBALS['STATES_COLOR'][4];
						if (isset($file['validated']) && $file['validated']){
							$validationState ="VALIDATED";
							$classState = $GLOBALS['STATES_COLOR'][2];
						}elseif (isset($_SESSION['validation'][$fn]['checkParams'])){
							$validationState = (($_SESSION['validation'][$fn]['checkParams']==1)?"READY":"CHECK ERRORS");
							$classState = (($_SESSION['validation'][$fn]['checkParams']==1)?$GLOBALS['STATES_COLOR'][1]:$GLOBALS['STATES_COLOR'][1]);
						}else{
							$validationState = "NOT VALIDATED";
							$classState = $GLOBALS['STATES_COLOR'][4];
						}

						$filetypes = getFileTypesList();

						?>
					      <label class="mt-radio mt-radio-outline">
						<h4><?php echo  basename($fnPath);?> &bull; 
						<small>
						<strong><?php echo $file['size'];?></strong>
						<?php echo $file['mtime'];?></small>
						 &bull; <span id="file<?php echo $idx; ?>-state" class="<?php echo $classState; ?> file-state"><?php echo  $validationState;?></span>
						</h4>
						<input type="radio" name="idx" id="idx" value="<?php echo $idx;?>" <?php echo $checked." ".$blockRadio;?> onclick="showValidation(this)" />
						<input type="hidden" name="fn[]" id='fn' value="<?php echo $fn;?>"/>
						<span></span>
					      </label>
				        <?php } ?>
				  </div>
			    </div>


			    <?php
			    //TODO add $defs as global like reference genomes?¿
			    //metadata default values

			    //extract or guess file formats
			    foreach ($filesData as $idx => $v ) {
				$fn            = $filesData[$idx]['_id'];
				$fileExtension = "";
				if (isset($_REQUEST['format']) && isset($_REQUEST['format'][$idx]) && $_REQUEST['format'][$idx]){
					$fileExtension=$_REQUEST['format'][$idx];
				}elseif (isset($filesMeta[$idx]['format']) && $filesMeta[$idx]['format'] ){
					$fileExtension=$filesMeta[$idx]['format'];
				}elseif(isset($filesData[$idx]['_id'])){
                    $fnPath = $filesData[$idx]['path'];
                    list($fileExtension,$_REQUEST['compressed'][$idx],$fileBaseName) = getFileExtension($fnPath);
		    $compressionType = $_REQUEST['compressed'][$idx];
                    if ($compressionType){
                        $_REQUEST['compressed'][$idx]=1;
                    }
                    /*
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
                     */
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

				<form name="uploadFiles<?php echo $idx;?>" id="uploadFiles<?php echo $idx;?>" class="uploadFiles" action="#" method="post" enctype="multipart/form-data">
					<input type="hidden" name="op" id="op<?php echo $idx;?>" value="1">
					<input type="hidden" id="base-url<?php echo $idx;?>"     value="<?php echo $GLOBALS['BASEURL']; ?>"/>

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
								<select id="format<?php echo $idx;?>" name="format" onchange="customfromFormat(this.value, <?php echo $idx;?>)" class="form-control formatSelector file-type-selector">
								<option value="" >Select the file format</option>
								<?php foreach($filetypes as $ft) { ?>
								<option value="<?php echo $ft['_id']; ?>" <?php if (in_array($fileExtension,$ft['extension'])){echo "selected";}?>><?php echo $ft['_id']; ?></option>
								<?php } ?>

						 <!--<option value="" >Select the file format</option>
						 <option value="BAM"  <?php if ($fileExtension =="BAM") {echo "selected";}?> >BAM</option>
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
						 <option value="DCD"  <?php if ($fileExtension =="DCD") {echo "selected";}?> >DCD</option>
						 <option value="GRO"  <?php if ($fileExtension =="GRO") {echo "selected";}?> >GRO</option>
							<option value="GEM"  <?php if ($fileExtension =="GEM") {echo "selected";}?> >GEM</option>
							<option value="PARMTOP"  <?php if (in_array($fileExtension,Array("PARMTOP","PRMTOP","TOP"))){echo "selected";}?> >PARMTOP</option>
						<option value="MDCRD"  <?php if (in_array($fileExtension,Array("MDCRD","TRJ"))){echo "selected";}?> >MDCRD</option>
						 <option value="UNK"  <?php if ($fileExtension =="UNK"){echo "selected";}?> >OTHER</option>-->
								</select>
				  </div>

					<div class="form-group display-hide" id="dataType<?php echo $idx;?>">
				        <label>Data Type <i class="icon-question tooltips" data-container="body" data-placement="right" data-original-title="Data type description"></i></span></label>
				        <select name="data_type" id="data_type_sel<?php echo $idx;?>" class="form-control data-type-selector" onchange="customfromDataType(this.value, <?php echo $idx;?>)" disabled>
								</select>
								<!--<span class="help-block font-red warn1" style="display:none;">This field is required.</span>-->
				  </div>

					<!--<div class="form-group display-hide" id="taxonG<?php echo $idx;?>">
				        <label>Taxon  <i class="icon-question tooltips" data-container="body" data-placement="right" data-original-title="Taxon description"></i></span></label> 
				        <input type="text" id="taxon<?php echo $idx;?>" class="form-control taxon" placeholder="Please enter the taxon name" />
					</div>-->

					<div class="form-group display-hide" id="taxonG<?php echo $idx;?>">
          	<label class="control-label" id="label-taxon<?php echo $idx;?>">Taxon <i class="icon-question tooltips" data-container="body" data-html="true" data-placement="right" data-original-title="<p align='left' style='margin:0'>Insert the taxon for this file. You can provide it by name, ID, or avoid this step.</p>"></i></label>
						<div class="input-group">
							<input type="text" class="form-control field_dependency<?php echo $idx;?> field_dependency<?php echo $idx;?>_1 taxon_name" name="taxon_name_id" id="taxonName<?php echo $idx;?>" placeholder="Please enter the taxon name" disabled>
							<input type="text" class="form-control field_dependency<?php echo $idx;?> field_dependency<?php echo $idx;?>_2 taxon_id" style="display:none;" name="taxon_id_name" id="taxonID<?php echo $idx;?>" placeholder="Please enter the taxon ID" disabled>
							<input type="text" class="form-control field_dependency<?php echo $idx;?> field_dependency<?php echo $idx;?>_3" style="display:none;" id="" disabled placeholder="No taxon provided">
							<div class="input-group-btn">
									<img class="Typeahead-spin" src="assets/layouts/layout/img/loading-spinner-blue.gif" style="display:none;">	
									<button type="button" class="btn green dropdown-toggle" data-toggle="dropdown"><span class="arg_dependency<?php echo $idx;?>">Taxon Name</span>
											<i class="fa fa-angle-down"></i>
									</button>
									<ul class="dropdown-menu pull-right">
											<li>
													<a class="arg_dependency<?php echo $idx;?>_1" href="javascript:changeArgDependency('<?php echo $idx;?>', '1', true);"> Taxon Name </a>
											</li>
											<li>
													<a class="arg_dependency<?php echo $idx;?>_2" href="javascript:changeArgDependency('<?php echo $idx;?>', '2', true);"> Taxon ID </a>
											</li>
											<li>
													<a class="arg_dependency<?php echo $idx;?>_3" href="javascript:changeArgDependency('<?php echo $idx;?>', '3', false);"> No Taxon </a>
											</li>
									</ul>
							</div>
						</div>
					</div>

					<input type="hidden" name="taxon_id" name="taxon_id<?php $idx; ?>" value="" />
				

					<div class="form-group display-hide" id="refGenomeTR<?php echo $idx;?>">
				        <label>Assembly <i class="icon-question tooltips" data-container="body" data-placement="right" data-original-title="Assembly description"></i></span></label> 
								<select name="refGenome" id="refGenome<?php echo $idx;?>" class="form-control" disabled>
						<option value="">Select the assembly</option>
						<?php
                                                $refList  = (isset($GLOBALS['refGenomes'])? scanDir($GLOBALS['refGenomes']) : array() );
                                                foreach ($refList as $ref){
                                                    if ( preg_match('/^\./', $ref) || !is_dir($GLOBALS['refGenomes']) )
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
                                                }
                                                print "<option value=0>Other</option>";
						?>
					
				        </select>
				  </div>

					<div class="form-group display-hide" id="pairedTR<?php echo $idx;?>">
				        <label>BAM type</label>
				        <div class="mt-radio-inline">
					   <label class="mt-radio mt-radio-outline">
					       <input type="radio" name="paired" class="paired<?php echo $idx;?>" value="paired" checked> Paired-End
					       <span></span>
					   </label>
					   <label class="mt-radio mt-radio-outline">
					       <input type="radio" name="paired" class="paired<?php echo $idx;?>" value="single"> Single-End
					       <span></span>
					   </label>
				        </div>
				  </div>

				  <div class="form-group display-hide" id="sortedTR<?php echo $idx;?>">
				        <label>Coordinates sorting</label>
				        <div class="mt-radio-inline">
					   <label class="mt-radio mt-radio-outline">
					       <input type="radio" name="sorted" class="sorted<?php echo $idx;?>" value="sorted" onclick="showHideSortInfo(0,<?php echo $idx;?>)" checked> Sorted	
					       <span></span>
					   </label>
					   <label class="mt-radio mt-radio-outline">
					       <input type="radio" name="sorted" class="sorted<?php echo $idx;?>" value="unsorted" onclick="showHideSortInfo(1, <?php echo $idx;?>)" > Unsorted
					       <span></span>
					   </label>
				        </div>
				  </div>

				  <div class="alert alert-warning display-hide" id="sortInfo<?php echo $idx;?>">
				      <strong>BAM</strong> file will be authomatically sorted by coordinate order
				  </div>

				  <!--<div class="form-group refGenomeTR" id="refGenomeTR<?php echo $idx;?>">
				        <label>Assembly</label>
				        <span class="tooltip-mt-radio"><i class="icon-question tooltips" data-container="body" data-placement="right" data-original-title="Assembly description"></i></span>
				        <select name="refGenome" class="form-control">
						<option value="">Select the assembly</option>
						<?php
						$refList  = scanDir($GLOBALS['refGenomes']);
						foreach ($refList as $specie){
						  var_dump(is_link($specie));
						  if ( preg_match('/^\./', $specie) || !is_dir($GLOBALS['refGenomes']))
						    continue;
						  $scaffoldList  = scanDir($GLOBALS['refGenomes']."/$specie");

						  foreach ($scaffoldList as $ref){
						    if ( preg_match('/^\./', $ref) || !is_dir($GLOBALS['refGenomes']."/$specie"))
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
				  </div>-->


				  <div class="form-group descriptionTR" id="descriptionTR<?php echo $idx;?>">
				        <label>Description</label>
				        <textarea name="description" id="description<?php echo $idx;?>" class="form-control" rows="6" placeholder="Write a short description here..."><?php echo $filesMeta[$idx]['description'];?></textarea>
				  </div>

				  <!--<input type="hidden" name="validated[]" value="<?php print $filesMeta[$idx]['validated'];?>"/>-->
				  <input type="hidden" name="fn" value="<?php print $fn;?>"/>


				  <div class="form-actions btn-send-data">
						<!--<input type="button" class="btn green snd-metadata-btn" value="SEND METADATA" onclick="sendMetadata(<?php echo $idx; ?>, 1);" style="position:relative;z-index:20;" >-->
						<input type="button" class="btn green snd-metadata-btn" id="snd<?php echo $idx; ?>" value="SEND METADATA" style="position:relative;z-index:20;" >
				  </div>
				
				</div>

				</form>
				
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
				
				<!--
			    <?php
			    if (isset($_SESSION['validation'][$FN])){
				print "SESION['validation'][$FN]['checkParams']<br/>";
				if ($_SESSION['validation'][$FN]['checkParams'] == 1 ){ ?>
				            <input type="submit" id="submit" class="btn green" value="OK" onclick="javascript:document.getElementById('op').value='uploadFileModify'" />
				            <input type="button" id="goWorkspace"  class="btn default" value="Cancel" onclick="location.href = 'home.php'" />
			            	
					    <input type="submit" id="submit" value="Finish" onclick="javascript:document.getElementById('op').value='uploadFileMeta'" />
					
       				<?php 
      				}else{
				        unset($_SESSION['validation']); ?>
				        <input type="submit" id="submit"  value="Validate" onclick="javascript:document.getElementById('op').value='checkParams'" <?php if(isset($_REQUEST['compressed'][$IDX]) && $_REQUEST['compressed'][$IDX]){echo "class=\"disabled\"";} ?> />
				        <input type="button" id="goWorkspace" value="Cancel"   onclick="location.href = 'home.php'" />
				      <?php
      				}
			  }else{ ?>
				<input type="submit" class="btn green" id="submit" value="Validate first time" onclick="javascript:document.getElementById('op').value='checkParams'" <?php if ($_REQUEST['compressed'][$IDX]){echo "class=\"disabled\"";} ?> />
        			<input type="button" class="btn default" value="Cancel"   onclick="location.href = 'home.php'" />
			  <?php
			  } ?>
			  -->

			  <div id="bottom-no-validated-files">

				<div id="desc-state-files">Clicking the button below you will be redirected to your workspace table. Remember that you haven't completed the metadata for all the uploaded files.</div>	
							
				<div id="go-out-uploadform"><input type="button" class="btn default" value="CONTINUE TO WORKSPACE" onclick="checkIfAllValidated();" /></div>
			
			  </div>

			  <div id="bottom-validated-files" class="display-hide">

				<div id="desc-state-files">All the uploaded files have been validated. Please click the button below to go to your workspace.</div>	
							
				<div id="go-out-uploadform"><input type="button" class="btn green" value="CONTINUE TO WORKSPACE" onclick="location.href='workspace/';" /></div>
			
			  </div>


			  </div>		
			<!--</form>-->

			<?php }else{ ?>

			<div id="go-out-uploadform"><input type="button" class="btn default" value="BACK TO UPLOAD FILES" onclick="location.href='getdata/uploadForm.php';" /></div>

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
						<div class="modal-body">You haven't completed the validation of all the files, you should edit the metadata afterwards through the workspace table. </div>
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
