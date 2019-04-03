
var fileName = '';
var baseURL = $('#base-url').val();

function openTADbit(fileID, label){
  $('#container-tad').html('');
  $('#modalTADkit .modal-title').html(label);
  $('#modalTADkit').modal({ show: 'true' });
  //fileName = '/files/' + path;

  $.ajax({
		
		type: "POST",
		url: baseURL + "applib/getFilePath.php",
		data: "id=" + fileID,
		success: function(data) {
			
			d = data.replace(/(\r\n|\n|\r|\t)/gm,"");

			obj = JSON.parse(d);

			//console.log(baseURL + 'files/' + obj.path);

//$('#container-tad').html('<tadkit-viewer id="viewer" color="93AEBF" previews=\'[{"file_type": "tad","file_url": "visualizers/tadkit/tadkit-viewer/samples/tk-example-dataset-2K.json"}]\'></tadkit-viewer>');

$('#container-tad').html('<tadkit-viewer id="viewer" color="93AEBF" previews=\'[{"file_type": "tad","file_url": "' + baseURL + 'files/' + obj.path + '?v=' + Math.random() + '"}]\'></tadkit-viewer>');
//$('#container-tad').html(obj.path);
		}

	});
}

$(document).ready(function() {

  $('#modalTADkit').on('hidden.bs.modal', function (e) {
    $('#container-tad').html('');
  });

  function handleResize(){ if(typeof stage != 'undefined') stage.handleResize(); }
  window.addEventListener( "resize", handleResize, false );

});
