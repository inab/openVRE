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
                            <a href="home/">Home</a>
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
                <h1 class="page-title">Edit File</h1>
                <!-- END PAGE TITLE-->
                <!-- END PAGE HEADER-->

                <p>Add metadata to validate the file. Once the file has the Validated state,
                you will be able to use it in the workspace. If you don't set the metadata at this
                moment, you can edit the file afterwards clicking the Edit button on the workspace table.</p>

        <!-- END CONTENT -->
	
	<p style="color:red"> * indicates a required value</p>    
    
    <div class="note note-info">

        <?php
            function byteConvert($bytes)
            {
                if ($bytes == 0)
                    return "0.00 B";
            
                $s = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
                $e = floor(log($bytes, 1024));
            
                return round($bytes/pow(1024, $e), 2).$s[$e];
            }
            
            foreach($_REQUEST['fn'] as $idx => $v){                                
                $file = getGSFile_fromId($_REQUEST['fn'][$idx]);
                $checked = "";
                if($idx == 0){
                    $checked = "checked";
                }                
                $fn = $file["_id"];
                            
                $file['size'] = byteConvert($file['size']);
                
                $file['mtime'] = $file['mtime']->toDateTime()->format('U');
                $file['mtime'] = strftime('%Y/%m/%d %H:%M', $file['mtime']);                
        ?>
 
            <label class="mt-radio mt-radio-outline block">
                <h4 style="display:inline;"><?php echo  basename($file['path']);?>
                <small>
                <strong><?php echo $file['size'];?></strong>
                <span><?php echo $file['mtime'] ?></span></small>           
                </h4>                         
                <input type="radio" name="idx" class="idx" id="idx" value="<?php echo $fn;?>" <?php echo $checked; ?> />
                <input type="hidden" name="fn[]" id='fn' value = "<?php echo $fn;?>"/>
                <span></span>
            </label>
        
        <?php } ?>

    </div>
    <div>
        <input style="margin-left: 88%;" type="file" id="uploadMetaData"></br>
    </div>
    <div class="note note-info" id="editor_holder"></div>        
    
    <button id="save">Submit</button>

	<?php
        require "../htmlib/footer.inc.php";
        require "../htmlib/js.inc.php";
	?>

<style type="text/css">
	.required:after{
		content: " *";
		color: red;
	}
	.invalid-feedback {
        	color: red;
	}
    .block{
        display: block;
    }
</style>
