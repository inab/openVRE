
var toolIdentifier = 0;
var fileName = '';
var baseURL = $('#base-url').val();

/*runTool = function(idTool, idFile, nameFile) {

	console.log(idTool);
	
	toolIdentifier = idTool;
	fileName = nameFile;

	var atLeastTwoAreChecked = $('td .mt-checkbox input:checkbox:checked').length > 1;

	if(atLeastTwoAreChecked) {
		$('#myModal1').modal('show');
	}else {
		switch(idTool) {
			case 1: alert('Nucleosome Dynamics');
					break;
			case 2: alert('BigNASim');
					break;
		}
	}	

}

$('#myModal1')
.on('click', '.btn-modal-ok', function(e) {
	$('#myModal1').modal('hide');
	switch(toolIdentifier) {
		case 1: alert('Nucleosome Dynamics ')
				break;
	//console.log(fcheck);
		case 2: alert('BigNASim');
				break;
	}
})
.on('show.bs.modal', function (e) {
  var modal = $(this)
  modal.find('.modal-body').text('You have more than one file selected. If you go ahead, this tool will just be applied to the selected file ' + fileName  + '.')
});*/

Array.prototype.remove = function() {
	var what, a = arguments, L = a.length, ax;
	while (L && this.length) {
			what = a[--L];
			while ((ax = this.indexOf(what)) !== -1) {
					this.splice(ax, 1);
			}
	}
	return this;
};

// remove single file from run tools portlet
removeFromToolsList = function(id, id_or) {
	// remove file
	$('.' + id).remove();
	//$('#workspace tr[data-tt-id="' + id_or + '"] td:first-child .checkboxes', table.rows().nodes()).prop('checked', false);
	$('input[type=checkbox]', table.rows().nodes()).each(function() { 
		if ($(this).parent().parent().parent().attr('data-tt-id') == id_or) {
			$(this).prop('checked', false);
		}
	});
	var folderId = 0; 
	for(i in allFiles){
		if((allFiles[i].rowId) == id_or) {
			allFiles[i].checked = false;
			folderId = allFiles[i].folderId; 
			break;
		}
	}
	if($('#list-files-run-tools').is(':empty')) {
		$('#desc-run-tools').show();
  	  	$('#btn-av-tools').hide();
	  	$('#btn-rmv-all').hide();
	}

	// update folder state if it was the last file
	var fcheck = true;
	$('input[type=checkbox]', table.rows().nodes()).each(function() { 
		if ($(this).parent().parent().parent().attr('data-tt-parent-id') == folderId) {
			//console.log($(this).is(":checked"));
			if($(this).is(":checked")) {
				fcheck = true;
				return false;
			}else{ 
				fcheck = false;
			}
		}
	});
	if(!fcheck) $('tr[data-tt-id=' + folderId + '] input[type=checkbox].foldercheck').prop('checked', false);

}

$(document).ready(function() {

	// load available tools
	$('#av_tools').click(function () {
  	// Only call notifications when opening the dropdown
    if (!$(this).parent().hasClass('open')) {

    	$('#av_tools_list').html('');
		
			var files = '';

			for(i in allFiles){
				if(allFiles[i].checked) {
					files += '&fn[]=' + allFiles[i].fileId;
				}
			}

			files = files.substring(1);

			$.ajax({
				type: "POST",
				url: baseURL + "applib/getAvailableTools.php",
				data: files,
				success: function(data) {
					$('#av_tools_list').css('background', '#fff');
					d = data.replace(/(\r\n|\n|\r|\t)/gm,"");
    					$('#av_tools_list').html(d);
				}
  		});
		}
	});

	// load visualizers
	$('#visualization').click(function () {
  	// Only call notifications when opening the dropdown
    if (!$(this).parent().hasClass('open')) {
	
			$('#visualizers_list').html('');

			var files = '';

			for(i in allFiles){
				if(allFiles[i].checked) {
					files += '&fn[]=' + allFiles[i].fileId;
				}
			}

			files = files.substring(1);

			$.ajax({
				type: "POST",
				url: baseURL + "applib/getVisualizers.php",
				data: files,
				success: function(data) {
					$('#visualizers_list').css('background', '#fff');
					d = data.replace(/(\r\n|\n|\r|\t)/gm,"");
    			$('#visualizers_list').html(d);
				}
  		});
		}
	});


	// remove all the files from run tools portlet
	$('#btn-rmv-all').click(function(){
		//$('#workspace tr td:first-child .checkboxes').prop('checked', false);
		$('.group-checkable').prop('checked', false);
		$('input[type=checkbox]', table.rows().nodes()).each(function() { 
			$(this).prop('checked', false);
		});

		for(i in allFiles){
			allFiles[i].checked = false;
		}
		$('.tool-list-item').remove();
		$('#desc-run-tools').show();
  	  	$('#btn-av-tools').hide();
	  	$('#btn-rmv-all').hide();
	});

});
