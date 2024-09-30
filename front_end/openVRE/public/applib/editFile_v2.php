<?php
require __DIR__."/../../config/bootstrap.php";
redirectOutside();

#use Swaggest\JsonSchema\Schema;  
        
if($_REQUEST) {
    if(isset($_REQUEST['action']) && $_REQUEST['action']=="save"){               
        $formData = $_REQUEST['data'];
        $path = $_REQUEST['path'];
        saveData($formData, $path);
        
    }else if(isset($_REQUEST['action']) && $_REQUEST['action']=="getFileInfo"){
        $file = $_REQUEST['fn'];
        getFileInfo($file);

    }else if(isset($_REQUEST['action']) && $_REQUEST['action']=="getPathDir"){
        $dir = $_REQUEST['dir'];
        $basePath = $_REQUEST['basePath'];
        getPathDir($dir,$basePath);
    
    }else if (isset($_REQUEST['action']) && $_REQUEST['action']=="uploadMetaData"){
        $file = $_REQUEST['file'];
        
    
        #$schema = Schema::import('https://raw.githubusercontent.com/Acivico/jsonSchema/main/basic_file.json');
        #$schema->in($json);

        validFileUpload($file);
        

    }

        
}else {
    echo '{}';
    exit;
}
?>
