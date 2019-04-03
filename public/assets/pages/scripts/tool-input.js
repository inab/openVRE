var baseURL = $("#base-url").val();
var table;
var selectedFiles = [];
var fileSelected = 0;
var currentVisInput;
var currentHidInput;
var multipleFiles = false;

function openHelp() {

	var state = $("#modal-dt-help").toggle(100);

	if($("#open-help-btn span")[0].innerHTML.indexOf("plus") != -1) $("#open-help-btn span").html('<i class="fa fa-minus"></i>');
	else $("#open-help-btn span").html('<i class="fa fa-plus"></i>');

}

var createDatatable = function() {

	var handleDatatable = function() {

		table = $('#workspace_st2').DataTable({
			"language": {
        emptyTable: 'No data available in User workspace'
			},
			"lengthMenu": [[10,20],[10,20]],
			"initComplete": function (settings, json) {
					$('#workspace_st2').show();
			},
			"order": [[ 1, "asc" ]],
			"columnDefs": [
				{ targets: [0], orderable: false },
			]

		});

	}

	
	var initTableFilters = function() {

		// FILTERS
		// selects
		$('#workspace_st2 #headerSearch').children().each(function(index,element) {
			if($( this ).hasClass("selector")){
				var column = table.column(index);
				var select = $('<select style="width: 100%!important;" class="selector form-control input-sm input-xsmall input-inline"><option value="">All</option></select>');
				column.data().unique().sort().each( function ( d, j ) {
					if(d.indexOf('<span style="display:none;">0</span>') != -1)	d = "uploads";
					if(d.indexOf('<span class=\"truncate\">') != -1) d = d.replace('<span class=\"truncate\">', '').replace('</span>', '');
					if((d.length) && (d != '&nbsp;')){
						var sel = '';
						select.append( '<option value="'+d+'" ' + sel +'>'+d+'</option>' );
					}
				} );
				$(this).html(select);
				$(this)
					.on( 'change', function () {
						var rgx = $(this).find("select").val();
						if(rgx == 'uploads') {
							var val = "<span style='display:none;'>0</span>uploads";
							var match = 'uploads';
						}else {
							var val = $.fn.dataTable.util.escapeRegex(
								rgx
							);
							var match = '^' + val + '$';
						}
					column
					.search( val ? match : '', true, false )
					.draw();
					} );
			}
		});
		// inputs
		$('#workspace_st2 #headerSearch .inputSearch').each( function () {
			var title = $('#workspace_st2 thead th').eq( $(this).index() ).text();
			if(title){
				$(this).html( '<input value="" style="width: 75%!important;font-size: 12px;font-weight: normal;padding: 2px;margin-left:5px;" class="form-control input-sm input-small input-inline" type="text" onclick="" placeholder="'+title+'" />' );
			}
		} );
		// Apply the filter
		$("#headerSearch input").on( 'keyup change', function () {
				if($( this ).parent().hasClass("inputSearch")){
						table
						.column( $(this).parent().index()+':visible' )
						.search( this.value )
						.draw();
				}
		} );

	}

	return {
        //main function to initiate the module
        init: function() {
            handleDatatable();
						initTableFilters();
        }
    };

}();

var createModal = function() {

    function getParameterByName(name, url) {
    	if (!url) url = window.location.href;
	name = name.replace(/[\[\]]/g, '\\$&');
	var regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)'),
        results = regex.exec(url);
    	if (!results) return null;
    	if (!results[2]) return '';
    	return decodeURIComponent(results[2].replace(/\+/g, ' '));
    }

    var handleModal = function() {

	cleanInput = function (visible_input, input_name, type) {
	
		if(type == 0) {
			$("input[name*='" + visible_input + "']").val('');
		} else {
			$("textarea[name*='" + visible_input + "']").val('');
			$("textarea[name*='" + visible_input + "']").css('height', '34px');
		}
		$("input[name*='" + input_name + "']").val('');
	}

	toolModal = function (visible_input, input_name, dt_list, ft_list, multiple) {

		// visible_input: name of the input which will show the file path
		// input_name: name of the input which value must be changed after selecting item from the table
		// dt_list: list of data types to be shown in tool type
		// ft_list: list of file types to be shown in tool type
		// multiple: if true checkboxes, if false radio buttons
		 
		// file_selected: default file id (in some tools)
		file_selected = [];
		$("input[name*='" + input_name + "']").each(function(k, v) {
			file_selected.push($(v).val());
		})

		$('#modalDTStep2').modal({ show: 'true' });

		$.ajax({
			type: "POST",
			url: baseURL + "applib/getFilesListInputTool.php",
			// type = 0: checkboxes, type = 1, radio buttons
			data: "dt_list=" + JSON.stringify(dt_list) + "&ft_list=" + JSON.stringify(ft_list) + "&multiple=" + multiple + "&file_selected=" + JSON.stringify(file_selected) + "&toolID=" + $("input[name=tool]").val() + "&op=" + getParameterByName('op'),
			success: function(data) {
				var obj = JSON.parse(data);
				$('#modalDTStep2 .modal-content .modal-body').html(obj.table);
				$(".tooltips").tooltip();
				$('#modalDTStep2 .modal-content .modal-body').append(
					'<p><a href="javascript:openHelp();" id="open-help-btn"><span><i class="fa fa-plus"></i></span> Can\'t find your data?</a></p>' + 
					'<div id="modal-dt-help" class="display-hide" style="background-color:rgb(238, 241, 245);padding: 10px 0;">' + 
					'<ul style="line-height:22px;padding-left:30px;">' +
	                                '<li style="list-style-type: none;margin-left:-10px;"><b>Please, make sure...</b></li>' +
               		                '<li style="margin-top:10px;"><b>you have uploaded your input files </b></li>' +
					'<li style="list-style-type: none">Import your data at the workspace by going at the main-left menu <span class="btn green btn-xs"><a style="color:white" href="' + baseURL + 'getdata/uploadForm.php"><i class="fa fa-cloud-upload"></i> Get Data <i class="fa fa-caret-right"></i> Upload files</a></span>' +
					'<li style="list-style-type: none">Or import a sample dataset at <span class="btn green btn-xs"><a style="color:white" href="' + baseURL + 'getdata/sampleDataList.php"><i class="fa fa-cloud-upload"></i> Get Data <i class="fa fa-caret-right"></i> Import example dataset</a></span></li>' +
	                                '<li style="margin-top:10px;"><b>your input files meet the tool requirements</b></li>' +
					'<li style="list-style-type: none">Check the metadata annotation fields "File Format" and "File Type" and make sure they match the tool requirements:</li>' +
	                                '<div style="background-color:white;margin:5px 5px -5px -10px;">'+
					obj.help +
	                                '</div>'+
	                                '<li style="list-style-type: none">Edit your file\'s metadata finding your file in the <span class="btn green btn-xs"><i class="fa fa-desktop"></i> User workspace</span>, and selecting from there the file\'s toolkit: <button class="btn btn-xs blue-madison dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="true"><i class="fa fa-cogs"></i><i class="fa fa-angle-down"></i></button><i class="fa fa-caret-right"></i><span class="btn blue-madison btn-xs"><i class="fa fa-pencil"></i>Edit Metadata</span> </li> ' +
	                                '<li style="list-style-type: none; margin-top:18px;"><a target="_blank" href="' + baseURL + 'tools/' + $("input[name=tool]").val() + '/help/inputs.php" style="margin-top:-3px;">Go to the extended tool\'s help</a></li>' +
	                                '</ul>' +
					'</div>'
				);

				$('#workspace_st2 tbody').on('click', 'tr.row-clickable', function (e) {
					e.preventDefault();
					if(!multiple) { 
						var inptype = 'mt-radio';
					} else {
						var inptype = 'mt-checkbox';
					} 
						var inputbtn = $(this).find('td:first-child label.' + inptype + ' input');
					var checked =  true;
						if(multiple) {
						if(inputbtn.is(':checked')) {
							checked =  false;
						}else {
							checked =  true;
						}
					}
					inputbtn.prop("checked", checked).trigger("change");
				} );
				selectedFiles = obj.selectedFiles;
				currentVisInput = visible_input;
				currentHidInput = input_name;
				multipleFiles = multiple;

				createDatatable.init();
			}

		});
		console.log(visible_input, input_name, dt_list, multiple, file_selected);

	}

	$('#modalDTStep2').on('show.bs.modal', function(e) {

	    $(".btn-modal-dts2-ok").click(function() {

		if(selectedFiles.length > 0) {
			//console.log(selectedFiles);
			if(!multipleFiles) {
				$("input[name='" + currentVisInput + "']").val(selectedFiles[0].filePath + " " + selectedFiles[0].fileName);
				$("input[name='" + currentHidInput + "']").val(selectedFiles[0].fileID);
				//console.log(currentVisInput,currentHidInput );

			} else {
				var vis = "";
				var h = 0;
				var inputs = ""
				$.each(selectedFiles, function(k, v) {
					vis += v.filePath + " " + v.fileName + "\n";
					inputs += '<input type="hidden" class="form-field-enabled" name="' + currentHidInput + '" value="' + v.fileID + '">';
					h ++;
				});
				if(h == 1) height = 34;
				else height = h*34; 
				$("textarea[name*='" + currentVisInput + "']").val(vis);
				$("textarea[name*='" + currentVisInput + "']").css('height', height + 'px');
				$("#hidden_" + currentVisInput).html(inputs); 					
			}
		}
		$('#modalDTStep2').modal("hide");
		fileSelected = 0;
		selectedFiles = [];
	    });
	});

	$('#modalDTStep2').on('hide.bs.modal', function (e) {
		selectedFiles = [];
		fileSelected = 0;
		currentInput = "";
		table.clear().draw();
		$('#modalDTStep2 .modal-content .modal-body').html('<div id="loading-datatable"><div id="loading-spinner">LOADING</div></div>');
		$(".btn-modal-dts2-ok").prop('disabled', true);
	});

	// select checkbox
	changeCheckbox = function(op, name, id, path) {
	    if($(op).is(':checked')) {
		var file = {fileName: name, fileID: id, filePath: path};
		selectedFiles.push(file);
		$(".btn-modal-dts2-ok").prop('disabled', false);
	    } else {
		selectedFiles = selectedFiles.filter(function(el) {
			return el.fileID !== id;
		});
		if(selectedFiles.length == 0) $(".btn-modal-dts2-ok").prop('disabled', true);
		else $(".btn-modal-dts2-ok").prop('disabled', false);
	    }
	    //console.log(selectedFiles);
	}

	// select radio
	changeRadio = function(name, id, path) {
		var file = {fileName: name, fileID: id, filePath: path};
		selectedFiles[0] = file;
		$(".btn-modal-dts2-ok").prop('disabled', false);
	}

    }
    return {
	//main function to initiate the module
	init: function() {
		handleModal();
	}	
    };

}();

var createSelect2 = function() {

	var handleSelect2 = function() {

		$("#select_project").select2({
			placeholder: "Select project",
			width: '100%',
			minimumResultsForSearch: 1
		});

	}

	return {
			//main function to initiate the module
			init: function() {
					handleSelect2();
			}
	};

}();


$(document).ready(function() {

	createSelect2.init();
	createModal.init();

	/*$("#select_project").change(function() {
	
		console.log($(this).val());

	});*/

});
