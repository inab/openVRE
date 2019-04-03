var baseURL = $("#base-url").val();
var user = $("#user").val();
var tool = $("input[name=tool]").val();
var table;
var selectedFiles = [];

var createDatatable = function() {

	var handleDatatable = function() {

		table = $('#workspace_st2').DataTable({

			"lengthMenu": [[10,20,-1],[10,20, "All"]],
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

	var initTableCheckboxes = function() {

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

		$('#workspace_st2 tbody').on('click', 'tr.row-clickable', function (e) {

				e.preventDefault();

				var inptype = 'mt-checkbox';

				var inputbtn = $(this).find('td:first-child label.' + inptype + ' input');
				var checked =  true;

				if(inputbtn.is(':checked')) {
					checked =  false;
				}	else {
					checked =  true;
				}

				inputbtn.prop("checked", checked).trigger("change");

    } );

		$('#clean-table').click(function() {
		
			$('#workspace_st2 thead input[type=checkbox]').prop('checked', false);

			$('input[type=checkbox]', table.rows().nodes()).prop('checked', false);
			selectedFiles = [];

		});

		toggleAllFiles = function(op) {

			if($(op).is(':checked')) {
				$('input[type=checkbox]', table.rows().nodes()).prop('checked', true).trigger("change");
			} else {
				$('input[type=checkbox]', table.rows().nodes()).prop('checked', false).trigger("change");
			}

		}

	}

	return {
        //main function to initiate the module
        init: function() {
            handleDatatable();
						initTableFilters();
						initTableCheckboxes();
        }
    };

}();

var data_info = false;

function openHelp() {

	if($("#open-help-btn span")[0].innerHTML.indexOf("plus") != -1) {
		
		$("#open-help-btn span").html('<i class="fa fa-minus"></i>');
	
		if(!data_info) {

			$.ajax({

				type: "POST",
				url: baseURL + "applib/getFilesFormatVisualizer.php",
				data: "&id=" + $("input[name=tool]").val(),
				success: function(data) {

					var obj = JSON.parse(data);

					data_info = true;

					$('#modal-dt-help').append(
										'<ul style="line-height:22px;padding-left:30px;">' +
																		'<li style="list-style-type: none;margin-left:-10px;"><b>Please, make sure...</b></li>' +
																		'<li style="margin-top:10px;"><b>you have uploaded your input files </b></li>' +
						'<li style="list-style-type: none">Import your data at the workspace by going at the main-left menu <span class="btn green btn-xs"><a style="color:white" href="' + baseURL + 'getdata/uploadForm.php"><i class="fa fa-cloud-upload"></i> Get Data <i class="fa fa-caret-right"></i> Upload files</a></span>' +
																		'<li style="margin-top:10px;"><b>your input files meet the visualizer requirements</b></li>' +
						'<li style="list-style-type: none">Make sure your files have one of the following formats:</li>' +
																		'<div style="background-color: #eef1f5;margin: 10px 40px 10px 0; padding: 10px; font-weight: bold;">'+
										obj.accepted_file_types.toString() +
																		'</div>'+
																		'<li style="list-style-type: none">Edit your file\'s metadata finding your file in the <span class="btn green btn-xs"><i class="fa fa-desktop"></i> User workspace</span>, and selecting from there the file\'s toolkit: <button class="btn btn-xs blue-madison dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="true"><i class="fa fa-cogs"></i><i class="fa fa-angle-down"></i></button><i class="fa fa-caret-right"></i><span class="btn blue-madison btn-xs"><i class="fa fa-pencil"></i>Edit Metadata</span> </li> ' +
																		'<li style="list-style-type: none; margin-top:18px;"><a target="_blank" href="' + baseURL + 'visualizers/' + $("input[name=tool]").val() + '/help/help.php" style="margin-top:-3px;">Go to the extended visualizer\'s help</a></li>' +
																		'</ul>'
								);

						$("#modal-dt-help").toggle(100);

				}

			});

		} else {

			$("#modal-dt-help").toggle(100);

		}	

	} else {
		$("#open-help-btn span").html('<i class="fa fa-plus"></i>');
		$("#modal-dt-help").toggle(100);
	}

}


$(document).ready(function() {

	createDatatable.init();

	$("#submit-visualizer").click(function(e) {

		if(selectedFiles.length > 0) {

			$('.warn-tool').hide();

			var query = "user=" + user + "&";
			$.each(selectedFiles, function(k, v){
				query += 'fn[]=' + v.fileID + '&';
			});
			query = query.slice(0, -1);

			var target = (tool != 'tadkit' ? 'childWindow': '_blank');
			window.open(baseURL + "visualizers/" + tool + "/?" + query, target);

		} else {

			$('.warn-tool').show();	

		}

	});

});
