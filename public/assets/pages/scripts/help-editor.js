var baseURL = $('#base-url').val();
var firstTime = true;
var interval;

$(document).ready(function() {

	if($('#developer').val()){

		//$('#html-content-help').css('cursor', 'text');
		//$('#htmlcontent').css('background', '#e1e1e8');

		$(/*#html-content-help,*/ '#bt-edit').click(function() {
			if(firstTime) {
				firstTime = false;
				if($('#visualizer').val() == 1) var is_view = 1;
				else var is_view = 0;
				$('#editor').markdownEditor({
					preview: true,
					height:'calc(100vh - 350px)',
					image: false,
					onPreview: function (content, callback) {
						callback( marked(content) );
					},
					imageUpload: true,
					uploadPath: baseURL + "applib/uploadImagesHelp.php?tool=" + $('#tool').val() + "&is_view=" + is_view
				});
			}

			$('#html-content-help').hide();
			$('#help-content').show();
			$('#tit-static').hide();
			$('#input-tit').show();
			$('#bt-edit').hide();

			$('#help-content').submit(function() {

				$('#title').val($('#input-tit').val());

				$.ajax({
        	type: "POST",
        	url: baseURL + "applib/saveHelp.php",
        	data: $('#help-content').serialize(), 
        	success: function(data) {
						d = data.replace(/(\r\n|\n|\r|\t)/gm,"");
						var obj = JSON.parse(d);

						if(obj.ok) location.reload();
						else alert("Error saving data, please try again!");

					}
        });
        //console.log($('#help-content').serialize());
			});

			interval = setInterval(function() { 
			
				//console.log("auto save");
				$('#help-content button[type="submit"]').html('SAVING...');
				$('#help-content button[type="submit"]').prop('disabled', true);

				$.ajax({
        	type: "POST",
        	url: baseURL + "applib/saveHelp.php",
        	data: $('#help-content').serialize(), 
        	success: function(data) {
						d = data.replace(/(\r\n|\n|\r|\t)/gm,"");
						var obj = JSON.parse(d);

						/*if(obj.ok) location.reload();
						else alert("Error saving data, please try again!");*/

						$('#help-content button[type="submit"]').html('SAVE');
						$('#help-content button[type="submit"]').prop('disabled', false);

					}
        });

			}, 30000);
		

		});

		$('#cancel-edit').click(function() {

			$('#html-content-help').show();
			$('#help-content').hide();
			$('#tit-static').show();
			$('#input-tit').hide();
			$('#bt-edit').show();

			clearInterval(interval);
			

		});

	} else {

		$('#html-content-help').css('cursor', 'default');

	}	

});
