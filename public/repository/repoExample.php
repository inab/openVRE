<?php

require __DIR__."/../../config/bootstrap.php";
redirectOutside();


// Retrieve data to be displayed 
$json = file_get_contents("http://mmb.irbbarcelona.org/bns/simulation.json");
$studies = json_decode($json)->BNSSimulation;

// Print page
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
                                  <a href="home/">Home</a>
                                  <i class="fa fa-circle"></i>
                              </li>
                              <li>
                                  <span>Get Data</span>
                                  <i class="fa fa-circle"></i>
                              </li>
                              <li>
				<span>From Repository</span>
				<i class="fa fa-circle"></i>
			      </li>
			      <li>
				<span>Repository Name</span>
                              </li>
                            </ul>
                        </div>
                        <!-- END PAGE BAR -->
                        <!-- BEGIN PAGE TITLE-->
                        <h1 class="page-title">
				<a href="https://repository/home/page/" target="_blank"><img src="assets/layouts/layout/img/icon.png" width=100></a>
				Repository Title - http://mmb.irbbarcelona.org/bns/simulation.json
                        </h1>
                        <!-- END PAGE TITLE-->
                        <!-- END PAGE HEADER-->
											
	
                        <div class="row">
                            <div class="col-md-12">
                                <!-- BEGIN EXAMPLE TABLE PORTLET-->
                                <div class="portlet light portlet-fit bordered">
                                  <div class="portlet-title">
                                    <div class="caption">
                                        <i class="icon-share font-red-sunglo hide"></i>
                                        <span class="caption-subject font-dark bold uppercase">Browse Experiments</span>
                                    </div>
                                  </div>
				  <div class="portlet-body">
					<div id="loading-datatable"><div id="loading-spinner">LOADING</div></div>

                                        <table class="table table-striped table-hover table-bordered" id="table-repository">
                                            <thead>
                                                <tr>
                                                    <th> ID </th>
                                                    <th> PDB desc. </th>
                                                    <th> System </th>
                                                    <th> PDB </th>
                                                    <th> Type </th>
						    <th> SubType </th>
						    <th> Force Field </th>
						    <th> Solvent </th>
						    <th> Description </th>
						    <th> Time (ns) </th>
						    <th> Actions </th>
                                                </tr>
                                            </thead>
                                            <tbody>
					  <?php
					// display each result row
					foreach($studies as $key => $value):
						$NAFpath = "NAFlex_parmBSC1/".$value->NAFlexId."/INFO/";
						$str = "structure.stripped.pdb";
						$top = "structure.stripped.top";
						$trj1 = "structure.netcdf";
						$trj2 = "structure.stripped.trj";
						$pdbs=array(); // old find(pdbCol)
						$title = "1PDB";
						$name = str_replace("NAFlex", "BIGNASim", $value->NAFlexId);
					  ?>
					<tr>
					    <td>
						<a href="https://mmb.irbbarcelona.org/bns/simulation/<?php echo $value->_id; ?>.html" target="_blank"><?php echo $value->_id; ?></a><br>
					    </td>
					    <td><?php echo $title; ?></td>
					    <td><a href="https://mmb.irbbarcelona.org/bns/system/<?php echo $value->systemId; ?>.html" class="btn btn-xs green" target="_blank"><i class="fa fa-dot-circle-o" aria-hidden="true"></i> System</a></td>
					    <td><a href="http://mmb.irbbarcelona.org/pdb/getStruc.php?idCode=<?php echo $value->PDB; ?>" target="_blank"><?php echo $value->PDB; ?></a></td>
					    <td><?php echo $value->moleculeType; ?></td>
					    <td><?php echo $value->SubType; ?></td>
					    <td><?php echo $value->forceField; ?></td>
					    <td><?php echo $value->Water; ?></td>
					    <td><?php echo $value->description.' '.$value->Format; ?></td>
					    <td><?php echo $value->time; ?></td>
					    <td>
						<?php if(file_exists($GLOBALS['htmlPath'].$NAFpath.$str)) { ?>
							<a href="applib/getData.php?uploadType=repository&url=<?php echo $GLOBALS['URL']; ?>/<?php echo $NAFpath.$str; ?>&repo=bignasim&data_type=na_structure&filename=<?php echo $name; ?>.pdb" class="btn btn-xs green tooltips" style="margin-top:10px;" aria-hidden="true" data-container="body" data-html="true" data-placement="left" data-original-title="<p align='left' style='margin:0'>Click here to import this experiment's structure to the workspace</p>" target="_blank"><i class="fa fa-cloud-upload"></i> STRUCTURE </a><br>	
						<?php } ?>
						<?php if(file_exists($GLOBALS['htmlPath'].$NAFpath.$top)) { ?>
							<a href="applib/getData.php?uploadType=repository&url=<?php echo $GLOBALS['URL']; ?>/<?php echo $NAFpath.$top; ?>&repo=bignasim&data_type=na_traj_top&filename=<?php echo $name; ?>.top" class="btn btn-xs green tooltips" style="margin-top:10px;" aria-hidden="true" data-container="body" data-html="true" data-placement="left" data-original-title="<p align='left' style='margin:0'>Click here to import this experiment's topology to the workspace</p>" target="_blank"><i class="fa fa-cloud-upload"></i> TOPOLOGY </a>	<br>
						<?php } ?>
						<?php if(file_exists($GLOBALS['htmlPath'].$NAFpath.$trj1)) { ?>
							<a href="applib/getData.php?uploadType=repository&url=<?php echo $GLOBALS['URL']; ?>/<?php echo $NAFpath.$trj1; ?>&repo=bignasim&data_type=na_traj_coords&filename=<?php echo $name; ?>.netcdf" class="btn btn-xs green tooltips" style="margin-top:10px;" aria-hidden="true" data-container="body" data-html="true" data-placement="left" data-original-title="<p align='left' style='margin:0'>Click here to import this experiment's trajectory to the workspace</p>" target="_blank"><i class="fa fa-cloud-upload"></i> TRAJECTORY </a>	
						<?php } elseif(file_exists($GLOBALS['htmlPath'].$NAFpath.$trj2)) { ?>
							<a href="applib/getData.php?uploadType=repository&url=<?php echo $GLOBALS['URL']; ?>/<?php echo $NAFpath.$trj2; ?>&repo=bignasim&data_type=na_traj_coords&filename=<?php echo $name; ?>.trj" class="btn btn-xs green tooltips" style="margin-top:10px;" aria-hidden="true" data-container="body" data-html="true" data-placement="left" data-original-title="<p align='left' style='margin:0'>Click here to import this experiment's trajectory to the workspace</p>" target="_blank"><i class="fa fa-cloud-upload"></i> TRAJECTORY </a>	
						<?php } ?>
					    </td>
                        		</tr>
				  <?php
				endforeach;
				 ?>

                                </tbody>
                               </table>
                              </div>
                             </div>
                                <!-- END EXAMPLE TABLE PORTLET-->
                            </div>
                        </div>
                    </div>
                    <!-- END CONTENT BODY -->
                </div>
                <!-- END CONTENT -->

<?php 

require "../htmlib/footer.inc.php"; 
require "../htmlib/js.inc.php";

?>
