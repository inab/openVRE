<?php

require __DIR__."/../../config/bootstrap.php";
redirectOutside();

$toolList = getVisualizers_List();

sort($toolList);

?>

<?php require "../htmlib/header.inc.php"; ?>

<body class="page-header-fixed page-sidebar-closed-hide-logo page-content-white page-container-bg-solid page-sidebar-fixed">
  <div class="page-wrapper">

  <?php require "../htmlib/top.inc.php"; ?>
	<?php require "../htmlib/menu.inc.php"; ?>

<!-- BEGIN CONTENT -->
                <div class="page-content-wrapper">
                    <!-- BEGIN CONTENT BODY -->
                    <div class="page-content" id="body-help">
                        <!-- BEGIN PAGE HEADER-->
                        <!-- BEGIN PAGE BAR -->
                        <div class="page-bar">
                            <ul class="page-breadcrumb">
															<li>
				  <a href="/home/">Home</a>
				  <i class="fa fa-circle"></i>
			      </li>
                              <li>
                                  <span>Help</span>
                                  <i class="fa fa-circle"></i>
                              </li>
                              <li>
                                  <span>Visualizers</span>
                              </li>
                            </ul>
                        </div>
                        <!-- END PAGE BAR -->
                        <!-- BEGIN PAGE TITLE-->
                        <h1 class="page-title"> Select a visualizer
                        </h1>
                        <!-- END PAGE TITLE-->
												<!-- END PAGE HEADER-->

												<p>
                        </p>
												<div class="portfolio-content portfolio-3">

														<?php 
														
														$kw = array();
														foreach($toolList as $t) { 
															foreach($t['keywords'] as $tk) $kw[] = $tk;
														}

														$kw = array_unique($kw);
														sort($kw);	

														?>

                            <div class="clearfix">
                                <div id="js-filters-lightbox-gallery2" class="cbp-l-filters-button cbp-l-filters-left">
																		<div data-filter="*" class="cbp-filter-item-active cbp-filter-item btn blue btn-outline uppercase">All</div>
		
																		<?php foreach($kw as $k) { ?>
																		<div data-filter=".<?php echo $k; ?>" class="cbp-filter-item btn blue btn-outline uppercase"><?php echo /*str_replace("-", " ", $k);*/ $k; ?></div>
																		<?php } ?>																	
	
                                </div>
                            </div>
														<div id="js-grid-lightbox-gallery" class="cbp">
		
																<?php 

																foreach($toolList as $t) { 

																$kw = implode(" ", $t['keywords']);

																if (strpos($kw, 'visualization') === false) $type = 'tools';
																else $type = 'visualizers';

																?>

																	<div class="cbp-item <?php echo $kw; ?>">
																	<!-- REMOVE cbp-singlePageInline to go to new page -->
                                    <a href="help/toolhelp.php?tool=<?php echo $t["_id"]; ?>&sec=help" class="cbp-caption cbp-singlePageInline" data-title="<?php echo $t['title']; ?>" rel="nofollow">
                                        <div class="cbp-caption-defaultWrap">
                                            <img src="<?php echo $type; ?>/<?php echo $t['_id']; ?>/assets/home/logo.png" alt="">
                                        </div>
                                        <div class="cbp-caption-activeWrap">
                                            <div class="cbp-l-caption-alignLeft">
                                                <div class="cbp-l-caption-body">
																								<div class="cbp-l-caption-title"><?php echo $t['title']; ?></div>
                                                    <div class="cbp-l-caption-desc"><?php echo $t['short_description']; ?></div>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
																	</div>

																<?php } ?>

                            </div>

                    </div>
                    <!-- END CONTENT BODY -->
                </div>
                <!-- END CONTENT -->

<?php 

require "../htmlib/footer.inc.php"; 
require "../htmlib/js.inc.php";

?>
