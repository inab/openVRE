$(document).ready(function() {

  // VARIABLES 'GLOBALS' PER stateSave
  var col1SearchValue = '';
  var col2SearchValue = '';
  var col3SearchValue = '';

  // CONFIGURACIÓ DATATABLES
  table = $('#workspace').DataTable({
  //pagingType: "full_numbers",
	pageLength: 20,
	lengthMenu: [[5,15,20,-1],[5,15,20,"All"]],
	orderCellsTop: true,
	ordering: true,
	treetable: {
	  expandable: true
	},
	stateSave: true,
	stateLoaded: function (settings, data) {
		//console.log(data.start);
	  if(data.columns[1].search.search != '') {
		col1SearchValue = (data.columns[1].search.search);
	  }
	  if(data.columns[2].search.search != '') {
		col2SearchValue = (data.columns[2].search.search.slice(1,-1));
	  }
	  if(data.columns[3].search.search != '') {
		col3SearchValue = (data.columns[3].search.search.slice(1,-1));
	  }
	},
	select: true,
	columnDefs: [
	  // columnes d'informació
	  { targets: [0], orderable: false },
	  { targets: [1], orderData: [ 3, 8, 1 ], orderable: false },
	  { targets: [2], orderData: [ 3, 8, 2 ], orderable: false },
	  { targets: [3], orderData: [ 3, 8, 1 ], orderable: false },
	  { targets: [4], orderData: [ 9, 8, 4 ], orderable: false },
	  { type: 'file-size', targets: 5, orderData: [ 10, 8, 5 ], orderable: false },
	  { targets: [6], orderable: false },
	  { targets: [7], orderable: false },
	  // columnes auxiliars d'ordenació (invisibles)
	  { targets: [8], orderable: false, visible: false },
	  { targets: [9], orderable: false, visible: false },
	  { targets: [10], orderable: false, visible: false }
   ],
   "createdRow": function( row, data, dataIndex ) {
   	   //console.log($(row).data('tt-id'), dataIndex);
		if($(row).hasClass('leaf') && (!($(row).children('td').context.innerHTML.indexOf('enabled') != -1))) $(row).addClass('row-disabled');                               
   	},
   "initComplete": function (settings, json) {
   		$('#loading-datatable').hide();
   		$('#workspace').show();
		// ***********************
 		//setTimeout(function(){  table.cell({ row: 4, column: 2 }).data('<span class="alert-danger">FINISH!!!</span>').draw(); }, 6000);
  		// ***********************

   },
	"order": [[ 1, "asc" ]]
  }).on('stateSaveParams.dt', function (e, settings, data) {
	  data.order = ["1","asc"];
  });

  // FUNCIÓ CONVERSIÓ FILE SIZE
  jQuery.fn.dataTable.ext.type.order['file-size-pre'] = function ( data ) {

	  var matches = data.match( /^(\d+(?:\.\d+)?)\s*([a-z]+)/i );
	  var multipliers = {
		  b: 1,
		  k: 1000,
		  m: 1000000,
		  g: 1000000000,
		  t: 1000000000000,
		  p: 1000000000000000
	  };

	  var multiplier = multipliers[matches[2].toLowerCase()];
	  return parseFloat( matches[1] ) * multiplier;
  };

  // INICIALITZACIÓ DE NODES A DESPLEGATS (S'HA DE FER PER TOTES LES CARPETES)
		  
  var folders = $('tr', table.rows().nodes());

  folders.prevObject.each(function(index){
	$('#workspace').treetable('expandNode', $(this).attr('data-tt-id'));
  });

  // BOTONS D'ORDENACIÓ DE COLUMNES array(File,Format,Proj,Date,Size,Expires)
  var cols = new Array('asc','asc','asc','asc','asc');
  $('.mock_button').click(function(){
	i = $(this).attr("id").substring(11, 13);
  	  folders.prevObject.each(function(index){
		folderId = $(this).attr('data-tt-id');

		if(cols[i] == 'asc'){
		  table.cell({ row: (folderId - 1), column: 8 }).data('1000').draw();
		  //alert(folderId + " changed to 1000");
		}else{
		  table.cell({ row: (folderId - 1), column: 8 }).data('-1000').draw();
		  //alert(folderId + " changed to -1000");
		}
	  });
	  cols[i] = (cols[i] =='asc' ? 'desc': 'asc');
	  table.order(i,cols[i]).draw();
				
  });

  // BOTONS DE + INFO
  expandInfo = function(op){
	$(op).parent().find('.extra_info').slideToggle();
	$(op).toggleClass('expand_info_up');
  }

  // CHECKBOXES
  // global
  $('#workspace').find('.group-checkable').change(function () {
	var checked = $(this).is(":checked");	
	$('input[type=checkbox]', table.rows().nodes()).prop('checked', checked);	
  });
  
  // folders
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

  var allFolderChecked = [];
  // select all files of a folder 
  $('input[type=checkbox].foldercheck', table.rows().nodes()).change(function() {
	var folderId = $(this).parent().parent().parent().attr('data-tt-id');
    var checked = $(this).is(":checked");
	$('input[type=checkbox]', table.rows().nodes()).each(function() {
		if ($(this).parent().parent().parent().attr('data-tt-parent-id') == folderId) $(this).prop('checked', checked);
	});
	if(checked) allFolderChecked.push(folderId);
	else allFolderChecked.remove(folderId);
  }); 

  /*'a.folder-node', table.rows().nodes()).click(function(){
	var folderId = $(this).parent().parent().parent().attr('data-tt-id');
	if(allFolderChecked.indexOf(folderId) != -1){
		$('input[type=checkbox]', table.rows().nodes()).each(function() {
			if ($(this).parent().parent().parent().attr('data-tt-parent-id') == folderId) $(this).prop('checked', true);
		});
	}
  });*/

  /*$('input[type=checkbox]', table.rows().nodes()).not('.foldercheck').change(function() {
	var folderId = $(this).parent().parent().parent().attr('data-tt-parent-id');
	if(!$(this).is(":checked")){
		var fcheck = true;
		$('input[type=checkbox]', table.rows().nodes()).each(function() {
			if ($(this).parent().parent().parent().attr('data-tt-parent-id') == folderId) {
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
  });*/

  // SELECT MULTIPLE FILES
  // array with the names of the folders
  var allFolders = [];
  folders.prevObject.each(function(index){
  	var jqTds = $('>td', $(this));
		allFolders[$(this).attr('data-tt-id')] = jqTds[1].innerText.split('\n')[0];
		//console.log(jqTds[1].innerText.split('\n')[0]);
  });

  // JSON with the data of the files
  var files = $('tr', table.rows().nodes());
  files.prevObject.each(function(index) {
  	//console.log(table.cells({ row: index, column: 9 }).data()[0]);
	var jqTds = $('>td', $(this));
	var namesTds = $('td:nth-child(2) .enabled', $(this));
	var metadata = '';
	if(jqTds[1].innerHTML.indexOf('extra_info') != -1){
		metadata = jqTds[1].innerHTML.substring(jqTds[1].innerHTML.lastIndexOf('<table>') + 7, jqTds[1].innerHTML.lastIndexOf('</table>'));
	}
	metadata = metadata.replace(/(\n\t|\n|\t)/gm,"");
	if(namesTds[0] != undefined) var nameFile = namesTds[0].innerText;
	if((!$(this).hasClass('branch')) && (jqTds[0].innerHTML != '<span class="indenter" style="padding-left: 0px;"></span>')) allFiles.push({'folderId':$(this).attr('data-tt-parent-id'), 'folderName':allFolders[$(this).attr('data-tt-parent-id')],'fileName':nameFile, 'fileId':$('>td input', $(this)).val(), 'rowId':$(this).attr('data-tt-id'), 'checked':false, 'metadata':metadata});
  });
  //******************************************
  //console.log(JSON.stringify(allFiles));
  //*******************************************

  // check if there's at least one file checked
  checkIfSomeChecked = function(){
  	var ch = false
	for(i in allFiles){
		if(allFiles[i].checked) {
			ch = true;
			break;
		}
	}
	return ch;
  }

  // show / hide tools and buttons of the run tools portlet
  drawToolsMenu = function(ch){
  	if(!ch && !checkIfSomeChecked()){
	  $('#desc-run-tools').show();
  	  $('#btn-av-tools').hide();
	  $('#btn-rmv-all').hide();
	}else{
	  $('#desc-run-tools').hide();
  	  $('#btn-av-tools').show();
	  $('#btn-rmv-all').show();
    }
  }

  // add / remove file to the run tools portlet
  drawToolsList = function(ch, id, fl, fd, id_or, meta){
	if(ch){
	  var str_meta = '';
		meta = meta.replace(/"/g, "\'");
	  
	  if(meta != ''){
		str_meta = 	' <a href="javascript:;" onmouseover="javascript:;" class="popovers" data-trigger="hover" data-container="body" data-content="<table>' + meta  + '</table>" data-original-title="Metadata">' + 
				'<i class="fa fa-info-circle"></i>' + 
				'</a>';
	  }
	  $('#list-files-run-tools').append('<li class="tool-' + id + ' tool-list-item">'+
	  '<div class="col1">'+
		'<div class="cont">'+
        	'<div class="cont-col1">'+
            	'<div class="label label-sm label-info">'+
                	'<i class="fa fa-file"></i>'+
                '</div>'+
            '</div>'+
            '<div class="cont-col2">'+
                '<div class="desc"><span class="text-info" style="font-weight:bold;">' + fd  + ' /</span> ' + fl + str_meta +
				'</div>'+
            '</div>'+
        '</div>'+
      '</div>'+
	  '<div class="col2">'+
		'<div class="label label-sm label-danger" style="float: right;padding:0">'+
            '<a href="javascript:removeFromToolsList(\'tool-' + id  + '\', ' + id_or  + ');" title="Remove file" class="btn btn-icon-only red" style="width: 25px;height: 25px;padding-top: 1px;"><i class="fa fa-trash"></i></a>'+
        '</div>'+
      '</div>'+
	  '</li>');
	  $('.popovers').popover({html:true});
	}else{
	  $('.tool-' + id).remove();
	}

  }

  // show little message after click checkbox
  toastModal = function(msg) {
	toastr.options = {
		closeButton: true,
		debug: false,
		newestOnTop: true,
		progressBar: false,
		positionClass: 'toast-top-right',
		preventDuplicates: false,
		onclick: null,
		timeOut: '5000',
		showEasing: "swing",
		hideEasing: "linear",
		showMethod: "slideDown",
		hideMethod: "fadeOut"
	};	
	toastr["success"](msg);
  }


  // actions associated with the checkBoxes (add / remove file to the portlet and disable folder checkbox)
  changeCheckbox = function(op) {
	// draw on Tools list
	var row = $(op).parent().parent().parent();
	var checked = $(op).is(":checked");
	var flname = '';
	var fdname = '';
	var metadata = '';
	for(i in allFiles){
		if((allFiles[i].rowId) == row.data('tt-id')) {
			flname = allFiles[i].fileName;
			fdname = allFiles[i].folderName;
			metadata = allFiles[i].metadata;
			if(checked) allFiles[i].checked = true;
			else allFiles[i].checked = false;
			break;
		}
	}
	drawToolsMenu(checked);
	drawToolsList(checked, row.data('tt-id').toString().replace('.', ''), flname, fdname, row.data('tt-id').toString(), metadata);
	
	// disable folder check if I'm disabling the last one on the folder
	var folderId = $(op).parent().parent().parent().attr('data-tt-parent-id');
	var fcheck = true;
	$('input[type=checkbox]', table.rows().nodes()).each(function() { 
		if ($(this).parent().parent().parent().attr('data-tt-parent-id') == folderId) {
			if($(this).is(":checked")) {
				fcheck = true;
				return false;
			}else{ 
				fcheck = false;
			}
		}
	});
	//console.log(fcheck);
	if(!fcheck) $('tr[data-tt-id=' + folderId + '] input[type=checkbox].foldercheck').prop('checked', false);
	
	if(checked) toastModal("The file selected has been added to the Run Tools box below the workspace table.");

  }

  /*$('input[type=checkbox]', table.rows().nodes()).not('.foldercheck').change(function() {
	var row = $(this).parent().parent().parent();
	var checked = $(this).is(":checked");
	var flname = '';
	var fdname = '';
	for(i in allFiles){
		if((allFiles[i].rowId) == row.data('tt-id')) {
			flname = allFiles[i].fileName;
			fdname = allFiles[i].folderName;
			if(checked) allFiles[i].checked = true;
			else allFiles[i].checked = false;
			break;
		}
	}
	drawToolsMenu(checked);
	drawToolsList(checked, row.data('tt-id').toString().replace('.', ''), flname, fdname, row.data('tt-id').toString());
	//console.log(JSON.stringify(allFiles));
  });*/

  // add / remove all the files from a folder to the portlet
  $('input[type=checkbox].foldercheck', table.rows().nodes()).change(function() {
	var row = $(this).parent().parent().parent();
	var checked = $(this).is(":checked");
	for(i in allFiles){
		if((allFiles[i].folderId) == row.data('tt-id')) {
			if(checked) {
				if(!allFiles[i].checked) drawToolsList(checked, allFiles[i].rowId.toString().replace('.', ''), allFiles[i].fileName, allFiles[i].folderName, allFiles[i].rowId.toString(), allFiles[i].metadata);
				allFiles[i].checked = true;
			}else{ 
				allFiles[i].checked = false;
				drawToolsList(checked, allFiles[i].rowId.toString().replace('.', ''), allFiles[i].fileName, allFiles[i].folderName, allFiles[i].rowId.toString(), allFiles[i].metadata);
			}
		}
	}
	drawToolsMenu(checked);
	//console.log(JSON.stringify(allFiles));

	if(checked) toastModal("All the files of the selected folder have been added to the Run Tools box below the workspace table.");

  });

  // add / remove all the files of the table to the portlet
  $('input.group-checkable').change(function() {
	var checked = $(this).is(":checked");
	for(i in allFiles){
		if(allFiles[i].rowId) {
			if(checked) {
				if(!allFiles[i].checked) drawToolsList(checked, allFiles[i].rowId.toString().replace('.', ''), allFiles[i].fileName, allFiles[i].folderName, allFiles[i].rowId.toString(), allFiles[i].metadata);
				allFiles[i].checked = true;
			}else{ 
				allFiles[i].checked = false;
				drawToolsList(checked, allFiles[i].rowId.toString().replace('.', ''), allFiles[i].fileName, allFiles[i].folderName, allFiles[i].rowId.toString(), allFiles[i].metadata);
			}
		}
	}
	drawToolsMenu(checked);
  });

  // FILTRES
  // creació selects
  $('#workspace #headerSearch').children().each(function(index,element) {
		if($( this ).hasClass("selector")){
			var column = table.column(index);
			var select = $('<select style="width: 100%!important;" class="selector form-control input-sm input-xsmall input-inline"><option value="">All</option></select>')
			column.data().unique().sort().each( function ( d, j ) {
				if(d.indexOf('<span style="display:none;">0</span>') != -1)	d = "uploads";
				//console.log(d.indexOf('<span style="display:none;">0</span>')); 
				if((d.length) && (d != '&nbsp;')){
		  		var sel = '';
		  		if ((d == col2SearchValue) || (d == col3SearchValue)) sel = ' selected ';
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
  // creació input
	$('#workspace #headerSearch .inputSearch').each( function () {
		var title = $('#workspace thead th').eq( $(this).index() ).text();
		if(title){
			$(this).html( '<input value="' + col1SearchValue + '" style="width: 75%!important;font-size: 12px;font-weight: normal;padding: 2px;margin-left:5px;" class="form-control input-sm input-small input-inline" type="text" onclick="" placeholder="'+title+'" />' );
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

  // REFRESH TABLE
  $('a.clearState').on( 'click', function () {
		table.state.clear();
		window.location.reload();
  } );


});

// every time a folder is expanded or collapsed, we must check if there are checked checkboxes
// this function is outside $ because is called from a function on the dataTables.treeTable.js library (line 137)
checkCheckboxes = function(idNode){
	for(i in allFiles){
		if((allFiles[i].folderId) == idNode) {
			if(allFiles[i].checked) {
				$('input[type=checkbox]', table.rows().nodes()).each(function() {
					if ($(this).parent().parent().parent().attr('data-tt-id') == allFiles[i].rowId) $(this).prop('checked', true);  
				});
			}
		}
	}
}


