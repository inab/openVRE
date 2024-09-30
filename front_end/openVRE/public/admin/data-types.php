<?php

require __DIR__."/../../config/bootstrap.php";

redirectToolDevOutside();

$datatypes = getDataTypesList();

$keys = array();
foreach ($datatypes as $key => $row)
{
    $keys[$key] = $row['name'];
}
array_multisort($keys, SORT_ASC, $datatypes);

?>

<?php require "../htmlib/header.inc.php"; ?>

<body class="" style="background-color:#fafafa!important">

<!-- BEGIN CONTENT -->
<div class="page-wrapper">
   <!-- BEGIN CONTENT BODY -->
   <div class="page-content">
	<table class="table table-striped table-bordered"> 
	<thead>
	<tr>
		<th>Data type</th>
		<th>Associated file type (format)</th>
		<th>Data type identifier</th>
	</tr>
	</thead>
	<tbody>
	<?php 
	foreach($datatypes as $dt) { ?>
		<tr>
			<td><?php echo $dt["name"]; ?></td>
			<td><?php echo implode(", ", $dt["file_types"]); ?></td>
			<td><?php echo $dt["_id"]; ?></td>
		</tr>
	<?php }  ?>
	</tbody>
	</table>
   </div>
   <!-- END CONTENT BODY -->

</div>
<!-- END CONTENT -->



<?php 

require "../htmlib/js.inc.php";

?>
