
var fileName = '';
var baseURL = $('#base-url').val();

function openNGL(fileID, label, extension){
  $('#viewport').html('');
  $('#modalNGL .modal-title').html(label);
  $('#modalNGL').modal({ show: 'true' });
  //fileName = '/files/' + path;

  $.ajax({
		
		type: "POST",
		url: baseURL + "applib/getFilePath.php",
		data: "id=" + fileID,
		success: function(data) {
			
			d = data.replace(/(\r\n|\n|\r|\t)/gm,"");

			obj = JSON.parse(d);

			$('#modalNGL').on('shown.bs.modal', function (e) {
				
				$('#viewport').html('');
				$("#loading-viewport").show();

				stage = new NGL.Stage( "viewport", { backgroundColor:"#ddd" } );
				stage.removeAllComponents();

				if(obj.data_type != 'na_traj') {

					stage.loadFile( baseURL + "files/" + obj.path, { defaultRepresentation: false, ext:extension } )
					.then( function( o ){
						o.setSelection('/*');	
						o.addRepresentation( "cartoon", { color: "residueindex", aspectRatio: 4, scale: 1	} );
						o.addRepresentation( "base", { sele: "*", color: "resname" } );
						o.addRepresentation( "ball+stick", { sele: "hetero and not(water or ion)", scale: 3, aspectRatio: 1.5	} );
						stage.centerView();
						$("#loading-viewport").hide();
					} );

				} else {
	
					stage.loadFile( baseURL + "files/" + obj.path, { defaultRepresentation: true, asTrajectory: true } )
					.then( function( o ){
						var traj = o.trajList[0].trajectory;
						var player = new NGL.TrajectoryPlayer( traj, {
								step: 2,
								timeout: 100,
								start: 0,
								end: traj.numframes,
								interpolateType: "linear"
						} );
						traj.setPlayer( player );
						traj.player.play();
						o.addRepresentation( "cartoon", { color: "residueindex", aspectRatio: 4, scale: 1 } );
						o.addRepresentation( "base", { sele: "*", color: "resname" } );
						o.addRepresentation( "ball+stick", { sele: "hetero and not(water or ion)", scale: 3, aspectRatio: 1.5 } );
						stage.centerView();
						$("#loading-viewport").hide();
					} );
					
				}
	
			});

		}

	});
}

$(document).ready(function() {

 /* $('#modalNGL').on('shown.bs.modal', function (e) {
    stage = new NGL.Stage( "viewport", { backgroundColor:"#ddd" } );
    stage.removeAllComponents();
    stage.loadFile( fileName, { defaultRepresentation: false } ).then( function( o ){

      o.addRepresentation( "cartoon", {
        color: "residueindex", aspectRatio: 4, scale: 1
      } );
      o.addRepresentation( "base", {
        sele: "*", color: "resname"
      } );
      o.addRepresentation( "ball+stick", {
        sele: "hetero and not(water or ion)", scale: 3, aspectRatio: 1.5
      } );
      o.centerView(!1);

    } );
  });*/

  $('#modalNGL').on('hidden.bs.modal', function (e) {
    $('#viewport').html('');
  });

  function handleResize(){ if(typeof stage != 'undefined') stage.handleResize(); }
  window.addEventListener( "resize", handleResize, false );

});
