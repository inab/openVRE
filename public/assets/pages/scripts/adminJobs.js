var baseURL = $('#base-url').val();

function viewFileMeta(id, name, type, user){

	$('#modalMeta .modal-header .modal-title').html('');
	$('#modalMeta .modal-body #meta-summary').html('');
	$('#modalMeta .modal-footer #btMeta').remove();

	var txtID = '';
	/*if(type == 1) var txtID = 'File';
	else  var txtID = 'Job';*/

	$.ajax({
		type: "POST",
		url: baseURL + "applib/getMetaWS.php",
		data: "id=" + id + "&type=" + type + "&user=" + user, 
		success: function(data) {
			$('#modalMeta .modal-header .modal-title').html(name.toUpperCase() + ' ' + txtID + ' Info');
			$('#modalMeta .modal-body #meta-summary').html(data);
			$(".tooltips").tooltip();

			if(($("#modalMeta #btMeta").length == 0) && (type == 1)) $('#modalMeta .modal-footer').prepend('<a id="btMeta" style="float:left;" href="getdata/editFile.php?fn[]=' + id + '" class="btn green">Edit Metadata</a>');

			$('#modalMeta').modal({ show: 'true' });

		}
	});

}

var DataTableMyTools = function() {

	var handleDataTableMyTools = function() {

		var table = $('#sample_editable_1');

		var oTable = table.dataTable({

                "pageLength": 50,
				"lengthMenu": [
						[5, 20, 50, -1],
						[5, 20, 50, "All"] // change per page values here
				],

				"order": [
						[0, "asc"]
				],

				"columnDefs": [{
						'orderable': false,
						'targets': [0,3,6]
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
