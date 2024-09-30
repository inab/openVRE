<?php
require __DIR__."/../../../config/bootstrap.php";
redirectOutside();
// get tool details
$toolId = "jbrowse";
$tool   = getVisualizer_fromId($toolId,1);
?>
<?php require "../../htmlib/header.inc.php"; ?>
<body class="page-header-fixed page-sidebar-closed-hide-logo page-content-white page-container-bg-solid page-sidebar-fixed">
  <div class="page-wrapper">
    <?php require "../../htmlib/top.inc.php"; ?>
    <?php require "../../htmlib/menu.inc.php"; ?>
    <!-- BEGIN CONTENT -->
    <div class="page-content-wrapper">
      <!-- BEGIN CONTENT BODY -->
      <div class="page-content">
        <!-- BEGIN PAGE HEADER-->
        <!-- BEGIN PAGE BAR -->
        <div class="page-bar">
          <ul class="page-breadcrumb">
            <li>
              <a href="home/">Home
              </a>
              <i class="fa fa-circle">
              </i>
            </li>
            <li>
              <a href="workspace/">User Workspace
              </a>
            </span>
          <i class="fa fa-circle">
          </i>
          </li>
        <li>
          <span>Visualizers
          </span>
          <i class="fa fa-circle">
          </i>
        </li>
        <li>
          <span>
            <?php echo $tool['name']; ?>
          </span>
        </li>
        </ul>
    </div>
    <!-- END PAGE BAR -->
    <!-- BEGIN PAGE TITLE-->
    <h1 class="page-title"> 
      <?php echo $tool['title']; ?>
    </h1>
    <!-- END PAGE TITLE-->
    <!-- END PAGE HEADER-->
    <div class="row">
      <div class="col-md-12">
	<?php
	// show Error messages if any
	print printErrorDivision();
	?>
      </div>
      <div class="row">
          <div class="col-md-12">
            <div class="mt-element-step">
              <div class="row step-line">
                <div class="col-md-6 mt-step-col first active">
                  <div class="mt-step-number bg-white">1
                  </div>
                  <div class="mt-step-title uppercase font-grey-cascade">Select tool
                  </div>
                </div>
                <div class="col-md-6 mt-step-col last active">
                  <div class="mt-step-number bg-white">2
                  </div>
                  <div class="mt-step-title uppercase font-grey-cascade">Select files
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="portlet light bordered">
          <div class="portlet-title">
            <div class="caption">
              <i class="icon-share font-dark hide">
              </i>
              <span class="caption-subject font-dark bold uppercase">Launch JBrowse without user tracks
              </span>
              <small style="font-size:75%;">
              </small>
            </div>
          </div>
          <div class="portlet-body">
            <div class="row">
              <div class="col-md-12">
                <select  name="" id="ref-genome-jbrowse" class="form-control" onchange="changeGenome(this)">
                  <?php
		    $refList  = scanDir($GLOBALS['refGenomes']);
		    print "<option selected value=''>Click here to execute JBrowse with reference genome</option>";
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
		?>
                </select>
              </div>
            </div>
          </div>
        </div>
        <form action="javascript:;" class="horizontal-form" id="tool-input-form">
          <input type="hidden" name="tool" value="<?php echo $toolId;?>" />
          <input type="hidden" id="base-url" value="<?php echo $GLOBALS['BASEURL']; ?>"/>
          <input type="hidden" id="user" value="<?php echo $_SESSION['User']['id']; ?>"/>
          <!-- BEGIN PORTLET 2: SECTION 1 -->
          <div class="portlet box blue form-block-header">
            <div class="portlet-title">
              <div class="caption">
                <i class="fa fa-check-square-o" >
                </i> Select file(s) to visualize
              </div>
              <div class="actions">
                <button type="button" class="btn white" id="clean-table">
                  <i class="fa fa-trash-o" >
                  </i> Clean Selection
                </button>
              </div>
            </div>
            <div class="portlet-body" >
              <?php
// mirar JS (taula + selecció) 
echo getVisualizerTableList($tool['accepted_file_types'], "jbrowse");
?>
            </div>
          </div>
          <div class="alert alert-warning warn-tool display-hide">
            <strong>Warning!
            </strong> At least one file should be selected.
          </div>
          <div class="form-actions">
            <a href="javascript:openHelp();" id="open-help-btn">
              <span>
                <i class="fa fa-plus">
                </i>
              </span> Can't find your data?
            </a>
            <button type="submit" class="btn blue" id="submit-visualizer" style="float:right;">
              <i class="fa fa-check">
              </i> Launch
            </button>
          </div>
        </form>
        <div id="modal-dt-help" class="display-hide" style="background-color: rgb(255, 255, 255);padding: 10px; margin-top: 35px; width: 100%;">
        </div>
      </div>
    </div>
  </div>
  <!-- END CONTENT BODY -->
  </div>
<!-- END CONTENT -->
<?php 
require "../../htmlib/footer.inc.php";
require "../../htmlib/js.inc.php";
?>
