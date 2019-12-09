<?php

require __DIR__."/../../config/bootstrap.php";
redirectOutside();

require "../htmlib/header.inc.php";

$tls = getTools_ListComplete(1);
##### $vslzrs = getVisualizers_ListComplete(1);
$vslzrs=array();

$toolList = array_merge($tls, $vslzrs);

sort($toolList);

?>


<body class="page-header-fixed page-sidebar-closed-hide-logo page-content-white page-container-bg-solid page-sidebar-fixed">
  <div class="page-wrapper">

  <?php
   require "../htmlib/top.inc.php"; 
   require "../htmlib/menu.inc.php";
  

  ?>

  <!-- BEGIN CONTENT -->
		<div class="page-content-wrapper">
		    <!-- BEGIN CONTENT BODY -->
		    <div class="page-content">
			<!-- BEGIN PAGE HEADER-->
			<!-- BEGIN PAGE BAR -->
			<div class="page-bar">
			    <ul class="page-breadcrumb">
						<li>
				  <a href="home/">Home</a>
				  <i class="fa fa-circle"></i>
			      </li>
			      <li>
					<span>Launch Tool / Visualizer</span>
						<i class="fa fa-circle"></i>
						</li>
						<li>
					<span>Select Tool</span>
			      </li>
			    </ul>
			</div>
			<!-- END PAGE BAR -->
			<!-- BEGIN PAGE TITLE-->
			<h1 class="page-title"> Select Tool 
			    
			</h1>
			<!-- END PAGE TITLE-->
			<!-- END PAGE HEADER-->

			<div class="row">
			    <div class="col-md-12">
               	
						<div class="mt-element-step">
							<div class="row step-line">
									<div class="col-md-6 mt-step-col first active">
											<div class="mt-step-number bg-white">1</div>
											<div class="mt-step-title uppercase font-grey-cascade">Select tool</div>
									</div>
									<div class="col-md-6 mt-step-col last">
											<div class="mt-step-number bg-white">2</div>
											<div class="mt-step-title uppercase font-grey-cascade">Configure tool</div>
									</div>
							</div>
						</div>

					</div>
			</div>


				<!-- BEGIN EXAMPLE TABLE PORTLET -->
				<div class="row">
			  <div class="col-md-12 col-sm-12">

				<div class="portlet light bordered">

					<div class="portlet-title">
							<div class="caption">
					    	<i class="icon-share font-dark hide"></i>
					    	<span class="caption-subject font-dark bold uppercase">Select a tool</span>
							</div>

						</div>

            <!-- CHANGE: new id "portlet-ws" -->
				    <div class="portlet-body" id="portlet-ws">

							<?php 
														
							$kw = array();
							foreach($toolList as $t) { 
                                				foreach($t['keywords'] as $tk) $kw[] = $tk;
							}

							sort($kw);	
							$kw = array_count_values($kw);

							?>

              <div class="row">
								<div class="col-md-12" id="main_keys">

									<?php foreach($kw as $k => $v) { ?>
										<button type="button" class="btn green btn-outline"><?php echo $k; ?> (<?php echo $v; ?>)</button>
									<?php } ?>
                  
                </div>
              </div>

							<?php 
													
							// TO MODIFY WITH NEW "secondary kws"	
                            $kwt = array();
							foreach($toolList as $t) { 
								foreach($t['keywords_tool'] as $tk) $kwt[] = $tk;
							}

							$kwt = array_unique($kwt);
							sort($kwt);	

							?>

              <div class="row" style="margin-top:20px;">
								<div class="col-md-6">
  										<div class="form-group">
  											<label class="control-label">Search by text</label>
  											<input class="form-control form-field-enabled valid" type="text" name="simpSearch" id="simp-search" placeholder="Write something"/>
  									</div>
                  </div>
                  <div class="col-md-6">
										<div class="form-group">
											<label class="control-label">Search by keywords</label>
											<select class="form-control form-field-enabled valid" id="sel-keyword" name="selKey[]" aria-invalid="false" multiple="multiple">
          							<option value=""></option>
													<?php foreach($kwt as $k) { ?>
														<option value="<?php echo $k; ?>"><?php echo $k; ?></option>
													<?php } ?>
												</select>
										</div>
									</div>
							</div>

              <div class="row">
								<div class="col-md-12">

                  <div id="loading-datatable"><div id="loading-spinner">LOADING</div></div>

                  <table id="tools-list" class="table table-striped table-bordered table-hover">
            	      <thead>
                      <tr>
                        <th></th>
                      	<th>Tool</th>
                      	<th>Description</th>
                        <th>Author</th>
			<!-- hidden columns -->
                        <th>Operations</th>
                        <th>Long Description</th>
                        <th>Keywords</th>
                        <th>Metakeywords</th>
			<th>id</th>
			<th>visualizer</th>
                      </tr>
                    </thead>
                    <tbody>
											<?php foreach($toolList as $tool) { 
										$comb = getInputFilesCombinations($tool);
												//vaR_dump($tool["input_files_combinations"]["description"]); ?>
												<tr class="first-level-tr">
													<td></td>
													<td><?php echo $tool["name"]; ?></td>
													<td><?php echo $tool["short_description"]; ?></td>
													<td><?php echo $tool["owner"]["author"]; ?></td>
													<td><?php echo $comb; ?></td>
													<!-- TO MODIFY WITH LONG DESCRIPTION -->
													<td><?php echo $tool["long_description"]; ?></td>
													<!-- TO MODIFY WITH NEW "secondary kws" -->
													<td><?php echo implode(", ", $tool["keywords_tool"]); ?></td>
													<td><?php echo implode(", ", $tool["keywords"]); ?></td>
													<td><?php echo $tool["_id"]; ?></td>
													<td><?php if (isset($tool['visualizers'])){echo $tool["visualizer"];} ?></td>
												</tr>
											<?php } ?>
                    </tbody>
                  </table>
                </div>
              </div>

				    </div>
				</div>
				<!-- END EXAMPLE TABLE PORTLET-->


		    </div>
		    <!-- END CONTENT BODY -->
		</div>
		<!-- END CONTENT -->


<?php 

require "../htmlib/footer.inc.php"; 
require "../htmlib/js.inc.php";

?>


