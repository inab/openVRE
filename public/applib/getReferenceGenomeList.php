<?php                                                                                                                                                                                   
require __DIR__."/../../config/bootstrap.php"; 
redirectOutside();                                                                                                                                                                      
                                                                                                                                                                                        
                                                                                                                                                                                        
$refList  = scanDir($GLOBALS['refGenomes']);                                                                                                                                            
print "<option selected value=''>Please select a reference genome</option>";
                                                                                                            
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

