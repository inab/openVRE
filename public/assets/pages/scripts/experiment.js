
var baseURL = $('#base-url').val();

function openFilesList(id){

	$.ajax({
		type: "POST",
		url: baseURL + "applib/getAEFiles.php",
		data: "id=" + id, 
		success: function(data) {
			
			$('#modalFilesList .modal-body #meta-container').html(data);

			$('#modalFilesList').modal({ show: 'true' });

		}
	});

}

