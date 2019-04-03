var baseURL = $('#base-url').val();

// Open modal with tool config JSON 

callShowToolJson = function(tool) {
	$('#modalAnalysis').modal('show');
	$('#modalAnalysis .modal-body').html('Loading data...');

	$.ajax({
		type: "POST",
		url: baseURL + "applib/showToolJson.php",
		data: "tool=" + tool, 
		success: function(data) {
			$('#modalAnalysis .modal-body').html(data);
		}
	});

}

changeToolStatus = function(tool, op) {

	location.href= baseURL + "applib/changeToolStatusAdmin.php?tool=" + tool + "&status=" + op.value;

}


var DataTableMyTools = function() {

	var handleDataTableMyTools = function() {

		var table = $('#sample_editable_1');

		var oTable = table.dataTable({

				"lengthMenu": [
						[10, 20, -1],
						[10, 20, "All"] // change per page values here
				],

				"order": [
						[0, "asc"]
				],

				"columnDefs": [{
						'orderable': false,
						'targets': [2,3,4]
				}],

		});

	}

	return {
        //main function to initiate the module
        init: function() {
            handleDataTableMyTools();
        }

    };


}();


$(document).ready(function() {

	DataTableMyTools.init();

});

