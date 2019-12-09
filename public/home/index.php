<?php

require __DIR__ . "/../../config/bootstrap.php";
redirectOutside();

$tls = getTools_List(1);
$tlsProv = getTools_List(0);
$vslzrs = getVisualizers_List(1);
$vslzrsProv = getVisualizers_List(0);

$appList = array_merge($tls, $tlsProv, $vslzrs, $vslzrsProv);
$vslzrList = array_merge($vslzrs, $vslzrsProv);

sort($appList);

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
              <span>Home</span>
            </li>
          </ul>
        </div>
        <!-- END PAGE BAR -->
        <!-- BEGIN PAGE TITLE-->
        <!-- <h1 class="page-title"> Homepage</h1> -->
        <!-- END PAGE TITLE-->
        <!-- END PAGE HEADER-->

        <p>
        </p>
        <div class="portfolio-content portfolio-3">

          <input type="hidden" id="base-url" value="<?php echo $GLOBALS['BASEURL']; ?>" />

          <?php
	  // create list of keywords
          $kw = array();
          foreach ($appList as $t) {
            foreach ($t['keywords'] as $tk) {
              $kw[] = $tk;
            }
          }
          $kw = array_unique($kw);
	  sort($kw);
	  // create list of tool ids 
	  $vslzrIds = array_column(array_values($vslzrList),"_id");

          ?>

          <div class="clearfix">
            <div id="js-filters-lightbox-gallery2" class="cbp-l-filters-button cbp-l-filters-left">
              <div data-filter="*" class="cbp-filter-item-active cbp-filter-item btn blue btn-outline uppercase">All</div>

              <?php foreach ($kw as $k) { ?>
                <div data-filter=".<?php echo $k; ?>" class="cbp-filter-item btn blue btn-outline uppercase"><?php echo str_replace("_", " ", $k);
                                                                                                              $k; ?></div>
              <?php } ?>

            </div>
          </div>
          <div id="js-grid-lightbox-gallery" class="cbp">

            <?php

            foreach ($appList as $t) {

              $kw = (isset($t['keywords'])?implode(" ", $t['keywords']):"");

	      $type= (in_array($t['_id'],$vslzrIds)? "visualizers" : "tools");

              ?>

              <div class="cbp-item <?php echo $kw; ?>">
                <!-- REMOVE cbp-singlePageInline to go to new page -->
                <a href="<?php echo $type; ?>/<?php echo $t['_id']; ?>/assets/home/index.html" class="cbp-caption cbp-singlePageInline" data-title="<?php echo $t['title']; ?>" rel="nofollow">
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
