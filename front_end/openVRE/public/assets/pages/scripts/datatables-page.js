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

function createFolderIconHtml($nameCell) {
  if ($nameCell.hasClass('highlighted_folder')) {
    return '<span class="fa-stack fa-lg collapse-folder">' +
      '<i class="fa fa-folder-open fa-stack-1x font-blue-oleo" style="left: 0px;top: -9px;"></i>' +
      '<i class="fa fa-folder-open-o font-green" style="position: absolute;left: 0px;top: -9px;"></i>' +
      '</span>';
  }
  return '<i class="fa fa-folder-open collapse-folder" aria-hidden="true"></i>';
}

function ensureFolderRowPresentation($row) {
  var $row = $($row);
  var $nameCell = $row.children('td').eq(1);

  $row.addClass('folder-row');
  $row.css({ 'font-weight': 'bold', color: '#337ab7' });

  var $line = $nameCell.children('.folder-name-line').first();
  if ($line.length && !$line.children('.collapse-folder').length) {
    $line.prepend(createFolderIconHtml($nameCell));
  }
}

function setFolderRowIcon($tr, isOpen) {
  var $nameCell = $tr.children('td').eq(1);
  var $icon = $nameCell.find('.collapse-folder');
  if (!$icon.length) {
    return;
  }
  if ($nameCell.hasClass('highlighted_folder')) {
    if (isOpen) {
      $icon.html('<i class="fa fa-folder-open fa-stack-1x font-blue-oleo" style="left:0px;top: -9px;"></i>' +
        '<i class="fa fa-folder-open-o font-green" style="position: absolute;left: 0px;top: -9px;"></i>');
    } else {
      $icon.html('<i class="fa fa-folder fa-stack-1x font-blue-oleo" style="left:0px;top: -9px;"></i>' +
        '<i class="fa fa-folder-o font-green" style="position: absolute;left: 0px;top: -9px;"></i>');
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
      var parentId = rows[i].getAttribute('data-tt-parent-id');
      var inserted = false;
      if (parentId) {
        for (var j = 0; j < ordered.length; j++) {
          if (ordered[j].getAttribute('data-tt-id') === parentId) {
            ordered.splice(j + 1, 0, rows[i]);
            inserted = true;
            break;
          }
        }
      }
      if (!inserted) {
        ordered.push(rows[i]);
      }
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
    var indentPx = filtered ? 0 : (depth * 30);

    $fileCell.css('padding-left', '0px');
    $fileCell.find('.folder-name-line').css('padding-left', '10px');
    $fileCell.find('.truncate').css('padding-left', '0px');
    $fileCell.find('a, span.enabled, span.disabled').css('padding-left', '0px');

    if (indentPx > 0) {
      if (isFolder) {
        var $folderLine = $fileCell.children('.folder-name-line');
        if ($folderLine.length) {
          $folderLine.css('padding-left', indentPx + 'px');
        } else {
          $fileCell.css('padding-left', indentPx + 'px');
        }
      } else {
        var $label = $fileCell.find('a, span.enabled, span.disabled').first();
        if ($label.length) {
          $label.css('padding-left', indentPx + 'px');
        } else {
          $fileCell.css('padding-left', indentPx + 'px');
        }
      }
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

$(document).ready(function() {

  // VARIABLES 'GLOBALS' PER stateSave
  var col1SearchValue = '';
  var col2SearchValue = '';
  var col3SearchValue = '';

  // BOTONS D'ORDENACIÓ DE COLUMNES (defined before DataTable for stateLoaded)
  var cols = {1:'asc', 2:'asc', 3:'asc', 4:'asc', 5:'asc', 6:'asc'};
  var btnCol = {1:1, 2:2, 3:3, 4:4, 6:5, 7:6};

  table = $('#workspace').DataTable({
  //pagingType: "full_numbers",
	pageLength: 20,
	lengthMenu: [[20,50,-1],[20,50,"All"]],
	orderCellsTop: true,
	// Tree table: column sort reorders siblings in the DOM only (see applyTreeSiblingSort)
	ordering: false,
	language: {
        emptyTable: 'No files found for the selected tool <i class="icon-question tooltips" data-container="body" data-html="true" data-placement="right" data-original-title="<p align=\'left\' style=\'margin:3px;\'>Please go to the <em>Get Data</em> section to load tool input files.</p><p align=\'left\' style=\'margin:3px;\'>More information on what this tool expects below on the <em>Tools\' Help</em> box, or in the main <em>Help</em> section</p>"></i>'
	},
	/*treetable: {
	  expandable: true
	},*/
	responsive:true,
	stateSave: true,
	stateLoaded: function (settings, data) {
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
	  { targets: [8, 9, 10], visible: false }
   ],
   "createdRow": function( row, data, dataIndex ) {
			var $row = $(row);
			var treeId = getTreeRowId($row);
			if (treeId) {
				$row.attr('data-tree-depth', treeDepthFromId(treeId));
			}

		 if($row.find('input.foldercheck').length) {
				ensureFolderRowPresentation($row);

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
		syncTreeRowIndent();
		if ($('#workspace tbody tr.folder-off').length) {
			hideCollapsedFolderDescendants();
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
	  if (!matches) {
		  return 0;
	  }
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

  // SELECT MULTIPLE FILES
  allFolders = {};

  // JSON with the data of the files
  $(table.rows().nodes()).each(function() {
    var $row = $(this);
    var jqTds = $('>td', $row);

    if ($row.find('input.foldercheck').length) {
      allFolders[getTreeRowId($row)] = parseFolderLabel(jqTds[1].innerText);
      return;
    }

    var record = fileRecordFromRow($row);
    if (!record) return;

    if (jqTds[1].innerHTML.indexOf('extra_info') != -1) {
      record.metadata = jqTds[1].innerHTML.substring(
        jqTds[1].innerHTML.lastIndexOf('<table>') + 7,
        jqTds[1].innerHTML.lastIndexOf('</table>')
      ).replace(/(\n\t|\n|\t)/gm, '');
    }
    allFiles.push(record);
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
  drawToolsList = function(ch, id, fl, fd, id_or, meta, fileId){
    if(ch){
      var str_meta = '';
      meta = (meta || '').replace(/"/g, "\'");

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
                  '<div class="desc">' + (fd ? '<span class="text-info" style="font-weight:bold;">' + fd + ' /</span> ' : '') + fl + str_meta +
          '</div>'+
              '</div>'+
          '</div>'+
        '</div>'+
      '<div class="col2">'+
      '<div class="label label-sm label-danger" style="float: right;padding:0">'+
              '<a href="javascript:removeFromToolsList(\'tool-' + id  + '\', \'' + id_or  + '\');" title="Clear file from list" class="btn btn-icon-only red" style="width: 25px;height: 25px;padding-top: 1px;"><i class="fa fa-times-circle"></i></a>'+
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
	var row = $(op).closest('tr');
	var checked = $(op).is(":checked");
	var record = getFileRecord(row);
	if (!record) return;
	record.checked = checked;
	drawToolsMenu(checked);
	drawToolsList(checked, 
    treeIdToCssClass(record.rowId), 
    record.fileName, 
    record.folderName, 
    record.rowId, 
    record.metadata,
    record.fileId
  );

	if (!checked) {
		var treeId = getTreeRowId(row);
		while (treeId && treeId.indexOf('.') !== -1) {
			treeId = treeId.substring(0, treeId.lastIndexOf('.'));
			if (!hasCheckedDescendantFiles(treeId)) {
				$('tr[data-tt-id="' + treeId + '"] input.foldercheck:not(:disabled)').prop('checked', false);
			}
		}
	}

	if(checked) toastModal("The file selected has been added to the Manage Files box below the workspace table.");

  }

  // add / remove all descendant files from a folder to the portlet (recursive)
  $('#workspace tbody').on('change', 'input.foldercheck:not(:disabled)', function() {
		var folderTreeId = getTreeRowId($(this).closest('tr'));
		var checked = $(this).is(":checked");

		$(table.rows().nodes()).each(function() {
			var $r = $(this), rowId = getTreeRowId($r);
			if (!rowId || rowId === folderTreeId || !isTreeDescendant(rowId, folderTreeId)) return;
			$('input[type=checkbox]:not(:disabled)', $r).prop('checked', checked);
			var record = getFileRecord($r);
			if (!record) return;
			if (!checked || !record.checked) {
				drawToolsList(checked, 
          treeIdToCssClass(record.rowId), 
          record.fileName, 
          record.folderName, 
          record.rowId, 
          record.metadata,
          record.fileId
        );
			}
			record.checked = checked;
		});

		if (checked) allFolderChecked.push(folderTreeId);
		else allFolderChecked.remove(folderTreeId);

		drawToolsMenu(checked);

		if (checked) toastModal("All the files of the selected folder have been added to the Manage Files box below the workspace table.");

  });

  // add / remove all the files of the table to the portlet
  $('input.group-checkable').change(function() {
	var checked = $(this).is(":checked");
    for (i in allFiles) {
      var file = allFiles[i];
      if (!file.fileName || !file.fileId || file.fileId === '1') continue;
      if (checked && file.checked) continue;
      file.checked = checked;
      drawToolsList(checked, treeIdToCssClass(file.rowId), file.fileName, file.folderName, file.rowId, file.metadata, file.fileId);
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

  // Clear any column filters restored from saved state (e.g. Execution=uploads hides run folders)
  table.columns().search('');
  table.search('').draw(false);

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
        $(record).html('<i class="fa fa-folder-open fa-stack-1x font-blue-oleo" style="left: 0px;top: -9px;"></i>' +
        '<i class="fa fa-folder-open-o font-green" style="position: absolute;left: 0px;top: -9px;"></i>');
      } else {
        $(record).removeClass('fa-folder');
        $(record).addClass('fa-folder-open');
      }
    } else {
      //collapse
      $(tr).addClass("folder-off");
      var folderAction = "hidden";
      if($(td).hasClass('highlighted_folder')) {
        $(this).html('<i class="fa fa-folder fa-stack-1x font-blue-oleo" style="left: 0px;top: -9px;"></i>' +
        '<i class="fa fa-folder-o font-green" style="position: absolute;left: 0px;top: -9px;"></i>');
      } else {
        $(this).addClass('fa-folder');
        $(this).removeClass('fa-folder-open');
      }
    }

    $(table.rows().nodes()).each(function() {
      var $r = $(this);
      var id = $r.data("tt-id");
      if (!id) return;
      id = id.toString();
      if (folderAction == "hidden") {
        if (isTreeDescendant(id, trID)) $r.hide();
      } else {
        if ($r.data("tt-parent-id") && $r.data("tt-parent-id").toString() == trID) {
          $r.show();
          if ($r.hasClass("folder-off")) {
            $(table.rows().nodes()).each(function() {
              var childId = $(this).data("tt-id");
              if (childId && isTreeDescendant(childId.toString(), $r.data("tt-id").toString())) {
                $(this).hide();
              }
            });
          }
        }
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
		if(allFiles[i].checked && allFiles[i].fileName && isTreeDescendant(allFiles[i].rowId, idNode)) {
			$('input[type=checkbox]', table.rows().nodes()).each(function() {
				if (getTreeRowId($(this).closest('tr')) == allFiles[i].rowId) $(this).prop('checked', true);
			});
		}
	}
}



