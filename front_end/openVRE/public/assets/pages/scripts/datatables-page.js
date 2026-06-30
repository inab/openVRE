var toolSelected = $("#toolSelected").val();
var table;
var activeSortCol = null;
var activeSortDir = 'asc';

function updateSortHeaders(col, dir) {
  var $headers = $('#workspace thead tr.heading th');
  $headers.removeClass('sorting sorting_asc sorting_desc');
  $headers.each(function(i) {
    if (i >= 1 && i <= 6) {
      $(this).addClass('sorting');
    }
  });
  if (col !== null && col !== undefined) {
    activeSortCol = col;
    activeSortDir = dir;
    $headers.eq(col).removeClass('sorting').addClass(dir === 'asc' ? 'sorting_asc' : 'sorting_desc');
  } else {
    activeSortCol = null;
    activeSortDir = 'asc';
  }
}

function isTreeDescendant(rowId, ancestorId) {
  return rowId.indexOf(ancestorId + '.') === 0;
}

function getTreeRowId($row) {
  return $row.attr('data-tt-id');
}

function getTreeParentId($row) {
  return $row.attr('data-tt-parent-id');
}

function setFolderRowIcon($tr, isOpen) {
  var $td = $('td:first-child', $tr);
  var $icon = $td.find('.collapse-folder');
  if (!$icon.length) {
    return;
  }
  if ($td.hasClass('highlighted_folder')) {
    if (isOpen) {
      $icon.html('<i class="fa fa-folder-open fa-stack-1x font-blue-oleo" style="left:-5px;top: -9px;"></i>' +
        '<i class="fa fa-folder-open-o font-green" style="position: absolute;left: 4px;top: -9px;"></i>');
    } else {
      $icon.html('<i class="fa fa-folder fa-stack-1x font-blue-oleo" style="left:-5px;top: -9px;"></i>' +
        '<i class="fa fa-folder-o font-green" style="position: absolute;left: 4px;top: -9px;"></i>');
    }
  } else {
    $icon.removeClass('fa-folder fa-folder-open').addClass(isOpen ? 'fa-folder-open' : 'fa-folder');
  }
}

function syncFolderRowIcon($tr) {
  if (!$tr.find('input.foldercheck').length) {
    return;
  }
  setFolderRowIcon($tr, !isFolderEffectivelyClosed($tr));
}

function isFolderEffectivelyClosed($tr) {
  if ($tr.hasClass('folder-off')) {
    return true;
  }
  var id = getTreeRowId($tr);
  if (!id) {
    return false;
  }
  var hasChild = false;
  var hasVisibleChild = false;
  $(table.rows().nodes()).each(function() {
    if (getTreeParentId($(this)) == id) {
      hasChild = true;
      if ($(this).is(':visible')) {
        hasVisibleChild = true;
        return false;
      }
    }
  });
  return hasChild && !hasVisibleChild;
}

function treeIdToCssClass(treeId) {
  return treeId.toString().replace(/\./g, '_');
}

var treeSortApplying = false;

function applyTreeSiblingSort(col, dir) {
  if (treeSortApplying) {
    return;
  }
  treeSortApplying = true;
  try {
  var tbody = document.querySelector('#workspace tbody');
  if (!tbody) {
    return;
  }

  var rows = tbody.querySelectorAll('tr[data-tt-id]');
  var byParent = {};
  var sortKeys = {};
  var i, row, pid, rowId;

  for (i = 0; i < rows.length; i++) {
    row = rows[i];
    rowId = row.getAttribute('data-tt-id');
    if (rowId && rowId.indexOf('.') === -1) {
      pid = '__root__';
    } else {
      pid = row.getAttribute('data-tt-parent-id') || '__root__';
    }
    if (!byParent[pid]) {
      byParent[pid] = [];
    }
    byParent[pid].push(row);
    var td = row.children[col];
    if (!td) {
      sortKeys[rowId] = '';
    } else {
      var order = td.getAttribute('data-order');
      sortKeys[rowId] = (order !== undefined && order !== '')
        ? order.toLowerCase()
        : (td.textContent || '').replace(/\s+/g, ' ').trim().toLowerCase();
    }
  }

  Object.keys(byParent).forEach(function(parentId) {
    byParent[parentId].sort(function(a, b) {
      var ka = sortKeys[a.getAttribute('data-tt-id')];
      var kb = sortKeys[b.getAttribute('data-tt-id')];
      var cmp = ka < kb ? -1 : (ka > kb ? 1 : 0);
      if (cmp !== 0) {
        return dir === 'asc' ? cmp : -cmp;
      }
      // Stable tie-break: keep tree order within siblings
      var ta = a.getAttribute('data-tt-id') || '';
      var tb = b.getAttribute('data-tt-id') || '';
      return ta < tb ? -1 : (ta > tb ? 1 : 0);
    });
  });

  var ordered = [];

  function collectChildren(parentId) {
    var children = byParent[parentId] || [];
    for (var j = 0; j < children.length; j++) {
      ordered.push(children[j]);
      var id = children[j].getAttribute('data-tt-id');
      if (id) {
        collectChildren(id);
      }
    }
  }

  collectChildren('__root__');

  var seen = {};
  for (i = 0; i < ordered.length; i++) {
    seen[ordered[i].getAttribute('data-tt-id')] = true;
  }
  for (i = 0; i < rows.length; i++) {
    rowId = rows[i].getAttribute('data-tt-id');
    if (!seen[rowId]) {
      ordered.push(rows[i]);
    }
  }

  for (i = 0; i < ordered.length; i++) {
    tbody.appendChild(ordered[i]);
  }

  hideCollapsedFolderDescendants();
  } finally {
    treeSortApplying = false;
  }
}

function treeDepthFromId(treeId) {
  if (!treeId) {
    return 0;
  }
  return treeId.split('.').length - 1;
}

function syncTreeRowIndent() {
  if (!table) {
    return;
  }
  var info = table.page.info();
  var filtered = info.recordsDisplay < info.recordsTotal;

  $(table.rows({ search: 'applied' }).nodes()).each(function() {
    var $row = $(this);
    var treeId = getTreeRowId($row);
    if (!treeId) {
      return;
    }
    var depth = treeDepthFromId(treeId);
    var isFolder = $row.find('input.foldercheck').length > 0;
    var $fileCell = $row.children('td').eq(1);
    var indentPx = filtered ? 0 : depth * 10;

    if (isFolder) {
      $fileCell.css('padding-left', '0px');
      $fileCell.find('.truncate').css('padding-left', indentPx + 'px');
    } else if (depth > 0) {
      $fileCell.css('padding-left', indentPx + 'px');
    } else {
      $fileCell.css('padding-left', '0px');
    }
  });
}

function hideCollapsedFolderDescendants() {
  if (!table) {
    return;
  }
  var $collapsed = $('#workspace tbody tr.folder-off');
  if (!$collapsed.length) {
    return;
  }
  $collapsed.each(function() {
    var trID = getTreeRowId($(this));
    if (!trID) {
      return;
    }
    $('#workspace tbody tr[data-tt-id]').each(function() {
      var id = getTreeRowId($(this));
      if (id && isTreeDescendant(id, trID)) {
        $(this).hide();
      }
    });
  });
}

function hasCheckedDescendantFiles(folderTreeId) {
  var found = false;
  $(table.rows().nodes()).each(function() {
    var $r = $(this);
    var rid = getTreeRowId($r);
    if (rid && rid !== folderTreeId && isTreeDescendant(rid, folderTreeId)) {
      var $fileCb = $('input[type=checkbox]:not(.foldercheck):not(:disabled)', $r);
      if ($fileCb.length && $fileCb.is(':checked')) {
        found = true;
        return false;
      }
    }
  });
  return found;
}

function parseFolderLabel(text) {
  if (text.indexOf('0uploads') !== -1) return 'uploads';
  return text.replace(/(\n\t|\n|\t)/gm, '*').split('*').filter(function(v) { return v; })[0] || '';
}

function folderLabel(id) {
  if (!id) return '';
  if (allFolders[id]) return allFolders[id];
  var $r = $('tr[data-tt-id="' + id + '"]');
  if (!$r.length) return '';
  return allFolders[id] = parseFolderLabel($r.find('td').eq(1).text()) || '';
}

function folderPathForRow($row) {
  var path = [], id = getTreeParentId($row), n = 0;
  while (id && n++ < 50) {
    var name = folderLabel(id);
    if (name) path.unshift(name);
    var $f = $('tr[data-tt-id="' + id + '"]');
    id = $f.length ? getTreeParentId($f) : null;
  }
  return path.join(' / ');
}

function fileRecordFromRow($row) {
  if ($row.find('input.foldercheck').length) return null;
  var fileId = $row.find('td:first input.checkboxes:not(.foldercheck)').val();
  var $cell = $row.find('td').eq(1);
  var fileName = ($cell.find('.enabled').first().text() || $cell.find('a, span').first().text())
    .replace(/\u00a0/g, ' ').replace(/\s+/g, ' ').trim();
  var rowId = getTreeRowId($row);
  if (!rowId || !fileId || fileId === '1' || !fileName) return null;
  return {
    folderId: getTreeParentId($row),
    folderName: folderPathForRow($row),
    fileName: fileName,
    fileId: fileId,
    rowId: rowId,
    checked: false,
    metadata: ''
  };
}

function getFileRecord($row) {
  var rowId = getTreeRowId($row);
  for (var i in allFiles) {
    if (allFiles[i].rowId === rowId) {
      allFiles[i].folderName = folderPathForRow($row);
      return allFiles[i];
    }
  }
  var record = fileRecordFromRow($row);
  if (record) allFiles.push(record);
  return record;
}



  // VARIABLES 'GLOBALS' PER stateSave
  var col1SearchValue = '';
  var col2SearchValue = '';
  var col3SearchValue = '';

  // CONFIGURACIÓ DATATABLES
  table = $('#workspace').DataTable({
  //pagingType: "full_numbers",
	pageLength: 20,
	lengthMenu: [[20,50,-1],[20,50,"All"]],
	orderCellsTop: true,
	ordering: true,
	language: {
        emptyTable: 'No files found for the selected tool <i class="icon-question tooltips" data-container="body" data-html="true" data-placement="right" data-original-title="<p align=\'left\' style=\'margin:3px;\'>Please go to the <em>Get Data</em> section to load tool input files.</p><p align=\'left\' style=\'margin:3px;\'>More information on what this tool expects below on the <em>Tools\' Help</em> box, or in the main <em>Help</em> section</p>"></i>'
	},
	/*treetable: {
	  expandable: true
	},*/
	responsive:true,
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
	  { targets: [1], orderData: [ 4, 8, 1 ], orderable: false },
	  { targets: [2], orderData: [ 4, 8, 2 ], orderable: false },
	  { targets: [3], orderData: [ 4, 8, 3 ], orderable: false },
	  { targets: [4], orderData: [ 4, 8, 1 ], orderable: false },
	  { targets: [5], orderData: [ 4, 8, 9 ], orderable: false },
	  //{ targets: [5], orderData: [ 4, 8, 5 ], orderable: false },
	  //{ type: 'file-size', targets: 6, orderData: [ 10, 8, 6 ], orderable: false },
		{ type: 'file-size', targets: 6, orderData: [ 4, 8, 6 ], orderable: false },
	  //{ targets: [6], orderable: false },
	  
	  { targets: [7], orderable: false },
	  // columnes auxiliars d'ordenació (invisibles)
	  { targets: [8], orderable: false, visible: false },
	  { targets: [9], orderable: false, visible: false },
	  { targets: [10], orderable: false, visible: false }
   ],
   "createdRow": function( row, data, dataIndex ) {
			
		 if($(row).data('tt-parent-id') === undefined) {

		 if($(row).data('tt-parent-id') === undefined) {
				$(row).css('font-weight', 'bold');
				$(row).css('color', '#337ab7');
				if($('td:first-child', row).hasClass('highlighted_folder')) {
					/*var folderIcon = '<span class="fa-stack fa-lg" style="height: 0;">' +
  				'<i class="fa fa-folder fa-stack-1x font-blue-oleo" style="left:-5px;top: -9px;"></i>' + 
  				'<i class="fa fa-folder-o font-green" style="position: absolute;left: 5px;top: -9px;"></i>' + 
					'</span>';*/
					var folderIcon = '<span class="fa-stack fa-lg collapse-folder" style="height: 0;">' +
  				'<i class="fa fa-folder-open fa-stack-1x font-blue-oleo" style="left:-5px;top: -9px;"></i>' +
  				'<i class="fa fa-folder-open-o font-green" style="position: absolute;left: 4px;top: -9px;"></i>' +
					'</span>';
				}else{
					//var folderIcon = '<i class="fa fa-folder" aria-hidden="true" style="font-size:18px;margin-left:5px;"></i>';
					var folderIcon = '<i class="fa fa-folder-open collapse-folder" aria-hidden="true" style="font-size:18px;margin-left:5px;"></i>';
				}
		 		$('td:first-child .mt-checkbox', row).after(folderIcon);

		 }else {
		
			 	if(!($(row).children('td').context.innerHTML.indexOf('mt-checkbox') != -1)) {
			 		$(row).css('color', '#87a2b9'); 
					$(row).addClass('row-disabled'); 
				}

		 }

   	   //console.log($(row).data('tt-id'), dataIndex);
		//if($(row).hasClass('leaf') && (!($(row).children('td').context.innerHTML.indexOf('enabled') != -1))) $(row).addClass('row-disabled');                               
   	},
   "initComplete": function (settings, json) {
   		$('#loading-datatable').hide();
   		$('#workspace').show();
   		$(".tooltips").tooltip();
	  	updateSortHeaders(null);
   },
   "drawCallback": function () {
		if (activeSortCol !== null && !treeSortApplying) {
			applyTreeSiblingSort(activeSortCol, activeSortDir);
		}
   },
  }).on('stateSaveParams.dt', function (e, settings, data) {
    data.order = ["1","asc"];
    if (data.columns) {
      for (var i = 0; i < data.columns.length; i++) {
        data.columns[i].search.search = '';
      }
    }
  });

  function handleColumnSort(col) {
    if (col === undefined) {
      return;
    }
    cols[col] = (cols[col] === 'asc' ? 'desc' : 'asc');
    updateSortHeaders(col, cols[col]);
    applyTreeSiblingSort(col, cols[col]);
  }

  $('.mock_button').click(function(e){
    e.preventDefault();
    e.stopPropagation();
    var btn = parseInt($(this).attr('id').substring(11), 10);
    handleColumnSort(btnCol[btn]);
  });

  $('#workspace thead tr.heading th').on('click', function(e) {
    if ($(e.target).closest('.mock_button').length) {
      return;
    }
    var idx = $(this).index();
    if (idx >= 1 && idx <= 6) {
      e.preventDefault();
      handleColumnSort(idx);
    }
  });

  $('#workspace tbody').on('click', '.collapse-folder', function (e) {
    e.preventDefault();
    e.stopPropagation();

    var tr = $(this).closest('tr');
    var trID = getTreeRowId(tr);

    if (!trID) {
      return;
    }

    if (isFolderEffectivelyClosed(tr)) {
      $(tr).removeClass("folder-off");
      var folderAction = "visible";
      setFolderRowIcon(tr, true);
    } else {
      $(tr).addClass("folder-off");
      var folderAction = "hidden";
      setFolderRowIcon(tr, false);
    }

    $(table.rows().nodes()).each(function() {
      var $r = $(this);
      var id = getTreeRowId($r);
      if (!id) return;
      if (folderAction == "hidden") {
        if (isTreeDescendant(id, trID)) $r.hide();
      } else {
        if (getTreeParentId($r) == trID) {
          $r.show();
          if ($r.hasClass("folder-off")) {
            var collapsedId = getTreeRowId($r);
            $(table.rows().nodes()).each(function() {
              var childId = getTreeRowId($(this));
              if (childId && isTreeDescendant(childId, collapsedId)) {
                $(this).hide();
              }
            });
          }
          syncFolderRowIcon($r);
        }
      }
    });
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
		  
  //var folders = $('tr[data-tt-id="1"]', table.rows().nodes());
	//var folders = $('tr:not([data-tt-parent-id])');
  /*folders.prevObject.each(function(index){
	$('#workspace').treetable('expandNode', $(this).attr('data-tt-id'));
  });*/

	var folders = $('#workspace tr[data-tt-id]', table.rows().nodes());
	var foldersIndex = []
	folders.prevObject.each(function(index){
		if($(this).attr('data-tt-id').indexOf('.') == -1) foldersIndex.push($(this).attr('data-tt-id'));	
	});

  // BOTONS D'ORDENACIÓ DE COLUMNES array(File,Format,Proj,Date,Size,Data type)
  var cols = new Array('asc','asc','asc','asc','asc', 'asc', 'asc');
 /* $('.mock_button').click(function(){
		i = $(this).attr("id").substring(11, 13);
  	//folders.prevObject.each(function(index){
		$.each(foldersIndex, function(index, value) {
			//if(!$(this).data('tt-parent-id')) folderId = $(this).attr('data-tt-id');
			//folderId = $(this).attr('data-tt-id');

			//if($(this).attr('data-tt-id').indexOf('.') == -1) folderId = $(this).attr('data-tt-id');

			//console.log(folderId);

			//console.log($(this).data('tt-parent-id'));
			//
			folderId = value;

			if(cols[i] == 'asc'){
				console.log(table.cell({ row: (folderId - 1), column: 8 }).data());

		  	table.cell({ row: (folderId - 1), column: 8 }).data('1000').draw();
		  	//console.log(table.cell({ row: (folderId - 1), column: 8 }).data());
		  	//console.log(folderId + " changed to 1000");
			}else{
		  	table.cell({ row: (folderId - 1), column: 8 }).data('-1000').draw();
		  	//console.log(folderId + " changed to -1000");
			}
	  });
	  cols[i] = (cols[i] =='asc' ? 'desc': 'asc');
	  table.order(i,cols[i]).draw();
				
  });*/

	$('.mock_button').click(function(){
		i = $(this).attr("id").substring(11, 13);
  	folders.prevObject.each(function(index){
			if(cols[i] == 'asc'){
				//console.log(table.cell({ row: index, column: 8 }).data());
				if(table.cell({ row: index, column: 8 }).data() == '-1000')
					table.cell({ row: index, column: 8 }).data('1000').draw();

		  	//table.cell({ row: (folderId - 1), column: 8 }).data('1000').draw();
		  	//console.log(table.cell({ row: (folderId - 1), column: 8 }).data());
		  	//console.log(folderId + " changed to 1000");
			}else{
				if(table.cell({ row: index, column: 8 }).data() == '1000')
					table.cell({ row: index, column: 8 }).data('-1000').draw();

		  	//table.cell({ row: (folderId - 1), column: 8 }).data('-1000').draw();
		  	//console.log(folderId + " changed to -1000");
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
			if ($(this).parent().parent().parent().attr('data-tt-parent-id') == folderId
					 && ($(this).parent().parent().parent().attr('data-tt-parent-id') !== undefined)) $(this).prop('checked', checked);
		});
		if(checked) allFolderChecked.push(folderId);
		else allFolderChecked.remove(folderId);
  }); 

  // SELECT MULTIPLE FILES
  // array with the names of the folders
  var allFolders = [];
  /*folders.prevObject.each(function(index){
  	//console.log($(this));
  	var jqTds = $('>td', $(this));
		allFolders[$(this).attr('data-tt-id')] = jqTds[1].innerText.split('\n')[0];
		//console.log(jqTds[1].innerText.split('\n')[0]);
  });*/

	function noEmpty(value) {
		  return value != "";
	}

  // JSON with the data of the files
  var files = $('tr', table.rows().nodes());
  files.prevObject.each(function(index) {
  	//console.log(table.cells({ row: index, column: 9 }).data()[0]);
  	//
  	//
	var jqTds = $('>td', $(this));
	var namesTds = $('td:nth-child(2) .enabled', $(this));
	var metadata = '';

	if($(this).context.innerHTML.indexOf('fa-folder') != -1) { 
		var foldername = jqTds[1].innerText;
		if(foldername.indexOf('0uploads') != -1) foldername = 'uploads';

		allFolders[$(this).attr('data-tt-id')] = foldername.replace(/(\n\t|\n|\t)/gm,"*").split("*").filter(noEmpty)[0];
		//console.log(jqTds[1].innerText.replace(/(\n\t|\n|\t)/gm,"*").split("*").filter(noEmpty)[0]);
	}

	if(jqTds[1].innerHTML.indexOf('extra_info') != -1){
		metadata = jqTds[1].innerHTML.substring(jqTds[1].innerHTML.lastIndexOf('<table>') + 7, jqTds[1].innerHTML.lastIndexOf('</table>'));
	}
	metadata = metadata.replace(/(\n\t|\n|\t)/gm,"");
	if(namesTds[0] != undefined) var nameFile = namesTds[0].innerText;
	if((!$(this).hasClass('branch')) && (jqTds[0].innerHTML != '<span class="indenter" style="padding-left: 0px;"></span>')) allFiles.push({'folderId':$(this).attr('data-tt-parent-id'), 'folderName':allFolders[$(this).attr('data-tt-parent-id')],'fileName':nameFile, 'fileId':$('>td input', $(this)).val(), 'rowId':$(this).attr('data-tt-id'), 'checked':false, 'metadata':metadata});
  });
  //******************************************
  // console.log(JSON.stringify(allFiles));
 	// console.log(allFolders);
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
            '<a href="javascript:removeFromToolsList(\'tool-' + id  + '\', ' + id_or  + ');" title="Clear file from list" class="btn btn-icon-only red" style="width: 25px;height: 25px;padding-top: 1px;"><i class="fa fa-times-circle"></i></a>'+
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
	
	if(checked) toastModal("The file selected has been added to the Manage Files box below the workspace table.");

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
					if(allFiles[i].fileId !== undefined) {
						if(!allFiles[i].checked) drawToolsList(checked, allFiles[i].rowId.toString().replace('.', ''), allFiles[i].fileName, allFiles[i].folderName, allFiles[i].rowId.toString(), allFiles[i].metadata);
						allFiles[i].checked = true;
					}
				}else{ 
					allFiles[i].checked = false;
					drawToolsList(checked, allFiles[i].rowId.toString().replace('.', ''), allFiles[i].fileName, allFiles[i].folderName, allFiles[i].rowId.toString(), allFiles[i].metadata);
				}
			}
		}
		drawToolsMenu(checked);
		//console.log(JSON.stringify(allFiles));

		if(checked) toastModal("All the files of the selected folder have been added to the Manage Files box below the workspace table.");

  });

  // add / remove all the files of the table to the portlet
  $('input.group-checkable').change(function() {
	var checked = $(this).is(":checked");
	for(i in allFiles){
		if(allFiles[i].rowId) {
			if(checked) {
				console.log(allFiles[i])
				if((allFiles[i].fileId === undefined) || (allFiles[i].folderId === undefined) || (allFiles[i].fileName === undefined)){
				}else{
					if(!allFiles[i].checked) drawToolsList(checked, allFiles[i].rowId.toString().replace('.', ''), allFiles[i].fileName, allFiles[i].folderName, allFiles[i].rowId.toString(), allFiles[i].metadata);
					allFiles[i].checked = true;
				}
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
			var select = $('<select style="width: 100%!important;" class="selector form-control input-sm input-xsmall input-inline"><option value="">All</option></select>');
			column.data().unique().sort().each( function ( d, j ) {
				if(d.indexOf('<span style="display:none;">0</span>') != -1)	d = "uploads";
				if(d.indexOf('<span class=\"truncate\">') != -1) d = d.replace('<span class=\"truncate\">', '').replace('</span>', '');
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

  // COLLAPSE FOLDER -- version 1: by default, only collapse on click
  $('.collapse-folder', table.rows().nodes()).on( 'click', function () {

    var tr = $(this).parent().parent();
    var td = $(this).parent();
    var trID = tr.data("tt-id").toString();

    if($(tr).hasClass("folder-off")) {
      $(tr).removeClass("folder-off");
      var folderAction = "visible";
      //open
      if($(td).hasClass('highlighted_folder')) {
        $(this).html('<i class="fa fa-folder-open fa-stack-1x font-blue-oleo" style="left:-5px;top: -9px;"></i>' +
        '<i class="fa fa-folder-open-o font-green" style="position: absolute;left: 4px;top: -9px;"></i>');
      } else {
        $(this).removeClass('fa-folder');
        $(this).addClass('fa-folder-open');
      }
    } else {
      //collapse
      $(tr).addClass("folder-off");
      var folderAction = "hidden";
      if($(td).hasClass('highlighted_folder')) {
        $(this).html('<i class="fa fa-folder fa-stack-1x font-blue-oleo" style="left:-5px;top: -9px;"></i>' +
        '<i class="fa fa-folder-o font-green" style="position: absolute;left: 4px;top: -9px;"></i>');
      } else {
        $(this).addClass('fa-folder');
        $(this).removeClass('fa-folder-open');
      }
    }

    $(table.rows().nodes()).each(function() {
      var id = $(this).data("tt-id").toString();
      var l1 = id.split(".");
      if(l1.length == 2  && l1[0] == trID) {
        if(folderAction == "hidden") $(this).hide();
        else $(this).show();
      }
    });

  } );
  

  // REFRESH TABLE
  $('a.clearState').on( 'click', function () {
		table.state.clear();
		window.location.reload();
  } );

  // DEACTIVATED
  // COLLAPSE FOLDER -- version 2: by default, collapse big folders
  // TODO: icons open/close

  function collapseFolder(record) {

   var tr = $(record).parent().parent();
   var td = $(record).parent();
   console.log(record);
       
    //var tr = $(this).parent().parent();
    //var td = $(this).parent();

    var trID = "0";
    if (tr.data('tt-id')) {
	trID = tr.data("tt-id").toString();
    }
    if($(tr).hasClass("folder-off")) {
      $(tr).removeClass("folder-off");
      var folderAction = "visible";
      //open
      if($(td).hasClass('highlighted_folder')) {
        $(record).html('<i class="fa fa-folder-open fa-stack-1x font-blue-oleo" style="left:-5px;top: -9px;"></i>' +
        '<i class="fa fa-folder-open-o font-green" style="position: absolute;left: 4px;top: -9px;"></i>');
      } else {
        $(record).removeClass('fa-folder');
        $(record).addClass('fa-folder-open');
      }
    } else {
      //collapse
      $(tr).addClass("folder-off");
      var folderAction = "hidden";
      if($(td).hasClass('highlighted_folder')) {
        $(this).html('<i class="fa fa-folder fa-stack-1x font-blue-oleo" style="left:-5px;top: -9px;"></i>' +
        '<i class="fa fa-folder-o font-green" style="position: absolute;left: 4px;top: -9px;"></i>');
      } else {
        $(this).addClass('fa-folder');
        $(this).removeClass('fa-folder-open');
      }
    }

    $(table.rows().nodes()).each(function() {
      var id = $(this).data("tt-id").toString();
      var l1 = id.split(".");
      if(l1.length == 2  && l1[0] == trID) {
        if(folderAction == "hidden") $(this).hide();
        else $(this).show();
      }
    });

  } 

	
  // collapsing on click
  /*$('.collapse-folder', table.rows().nodes()).click(function() { collapseFolder(this); });
   */

  // collapsing by default FOLDERS with more than 5 files
  /*$.each( $('.collapse-folder', table.rows().nodes()), function(index,record){
      var tr = $(record).parent().parent();
      var id = $(tr).data("tt-id").toString();
      var folder_files=$('[data-tt-parent-id="'+id+'"]');

      if(folder_files.length > 5){
    	collapseFolder(record);
        table.page.len( 50 ).draw();
      }
  });*/


	
	// SELECT2 PROJECT
  $("#select_project").select2({
  	placeholder: "Select project",
  	width: '100%',
  	minimumResultsForSearch: 1
  });

});

// TODO
loadProjectWS = function(id) {
  console.log(id.value);
  location.href = baseURL + "applib/manageProjects.php?op=reload&pr_id=" + id.value;
}

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



