<?php
require __DIR__ . "/../../config/bootstrap.php";
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

        <!-- BEGIN PAGE HEADER -->
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
              <span>From Example Dataset</span>
            </li>
          </ul>
        </div>
        <!-- END PAGE BAR -->

        <!-- BEGIN PAGE TITLE -->
        <h1 class="page-title">
          From External Object Storage
          <small>List of data repositories and containers available for exploration and downloading.</small>
        </h1>
        <!-- END PAGE TITLE -->
        <!-- END PAGE HEADER -->

        <!-- BEGIN ERRORS DIV -->
        <div id="errorsTool" style="display:none;"></div>

        <div class="row">
          <div class="col-md-12">
            <?php
            $error_data = false;
            if (!empty($_SESSION['errorData'])) {
              $error_data = true;

              if (!empty($_SESSION['errorData']['Info'])) { ?>
                <div class="alert alert-info">
              <?php } else { ?>
                <div class="alert alert-danger">
              <?php }

              foreach ($_SESSION['errorData'] as $subTitle => $txts) {
                print "<strong>$subTitle</strong><br/>";
                foreach ($txts as $txt) {
                  print "<div>$txt</div>";
                }
              }
              unset($_SESSION['errorData']);
              ?>
                </div>
            <?php } ?>
          </div>
        </div>
        <!-- END ERRORS DIV -->

        <h4>Granted Access to:</h4>

        <div class="actions">
                <a id="getCredentialsButton" class="btn green" >Get Credentials</a>
                <a id="workflowsReload" class="btn grey" disabled>Reload</a>
        </div>

        <div id="loading-datatable" class="loadingForm" style="display:none;">
          <div id="loading-spinner">LOADING</div>
          <!-- <div id="loading-text">It could take a few minutes</div> -->
        </div>

        <div class="portlet light portlet-fit bordered" id="general" style="display:none;">
          <div id="workflows" class="portlet-body">
              
            <select id="containerDropdown" class="form-control" style="margin-bottom: 20px;"></select>
            <input type="hidden" id="base-url" value="<?php echo $GLOBALS['BASEURL']; ?>" />

            <table id="workflowsTable" class="table table-striped table-hover table-bordered">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody id="workflow-data"></tbody>
            </table>
          </div>
        </div>
        <!-- END PAGE CONTENT -->

      </div>
      <!-- END CONTENT BODY -->
    </div>
    <!-- END CONTENT WRAPPER -->

  </div>
  <!-- END PAGE WRAPPER -->

  <!-- VIEW JSON PART -->
  <div class="modal fade bs-modal" id="modalAnalysis" tabindex="-1" role="basic" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
          <h4 class="modal-title">Execution Summary</h4>
        </div>
        <div class="modal-body table-responsive"></div>
        <div class="modal-footer">
          <button type="button" class="btn dark btn-outline" data-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <style type="text/css">
    #workflowsTable_filter {
      float: right;
    }

    .btn-block {
      width: 100%;
      font-size: 12px;
      display: block;
      line-height: 1.5;
    }

    .loadingForm {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(255, 255, 255, 0.7);
      z-index: 1000;
    }

    #loading-spinner {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      font-size: 14px;
      color: #333;
    }

    #loading-text {
      position: absolute;
      top: calc(50% + 20px);
      left: 50%;
      transform: translateX(-50%);
      font-size: 14px;
      color: #333;
    } 
  </style>

  <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
  <script>
  // Inject session variables from PHP into JS
  window.currentUserId = "<?php echo $_SESSION['User']['id']; ?>";
</script>
<script src="/assets/pages/scripts/openstack.js"></script>

  <?php
  require "../htmlib/footer.inc.php";
  require "../htmlib/js.inc.php";
  ?>
