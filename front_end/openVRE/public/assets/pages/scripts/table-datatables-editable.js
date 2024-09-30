var baseURL = $('#base-url').val();

//console.log(baseURL);

var TableDatatablesEditable = function () {

    var handleTable = function () {

		function markSelected(str, id){
			var strData = str.replace('value="' + id + '"', 'value="' + id + '" selected');
			return strData;
		}

        function restoreRow(oTable, nRow) {
            var aData = oTable.fnGetData(nRow);
            var jqTds = $('>td', nRow);

            for (var i = 0, iLen = jqTds.length; i < iLen; i++) {
                oTable.fnUpdate(aData[i], nRow, i, false);
            }

            oTable.fnDraw();
        }

				function bytesToSize(bytes) {
					var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
					if (bytes == 0) return '0 Byte';
					var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
					return Math.round(bytes / Math.pow(1024, i), 2) + ' ' + sizes[i];
				};

        function editRow(oTable, nRow) {
            var aData = oTable.fnGetData(nRow);
            var jqTds = $('>td', nRow);
			var idCountry = jqTds[4].innerHTML.split("*")[1];
			var idTypeUser = jqTds[5].innerHTML.split("*")[1];
            jqTds[1].innerHTML = '<input type="text" class="form-control input-xsmall input-sm" value="' + aData[1] + '">';
            jqTds[2].innerHTML = '<input type="text" class="form-control input-xsmall input-sm" value="' + aData[2] + '">';
            jqTds[3].innerHTML = '<input type="text" class="form-control input-xsmall input-sm" value="' + aData[3] + '">';
            jqTds[4].innerHTML = markSelected(countriesSelect, idCountry);
			jqTds[5].innerHTML = markSelected(rolesSelect, idTypeUser);
            jqTds[7].innerHTML = '<input type="number" class="form-control input-xsmall input-sm" min="1" max="100" value="' + aData[7] + '">';
            jqTds[8].innerHTML = '<div class="btn-group"><button class="btn btn-xs green dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false"> Actions <i class="fa fa-angle-down"></i></button><ul class="dropdown-menu pull-right" role="menu"><li><a class="edit" href="javascript:;"><i class="fa fa-save"></i> Save user</a></li><li><a class="cancel" href="javascript:;"><i class="fa fa-times-circle"></i> Cancel edition</a></li></ul></div>';
        }

	

        function editNewRow(oTable, nRow) {
            var aData = oTable.fnGetData(nRow);
            var jqTds = $('>td', nRow);
            jqTds[0].innerHTML = '<input type="text" class="form-control input-sm" value="' + aData[1] + '">';
            jqTds[1].innerHTML = '<input type="text" class="form-control input-xsmall input-sm" value="' + aData[1] + '">';
            jqTds[2].innerHTML = '<input type="text" class="form-control input-xsmall input-sm" value="' + aData[2] + '">';
            jqTds[3].innerHTML = '<input type="text" class="form-control input-xsmall input-sm" value="' + aData[3] + '">';
			jqTds[4].innerHTML = countriesSelect; 
            jqTds[5].innerHTML = rolesSelect;
            jqTds[6].innerHTML = '&nbsp;';
            //jqTds[7].innerHTML = '&nbsp;';
						jqTds[7].innerHTML = '<input type="number" class="form-control input-xsmall input-sm" value="50">';
            jqTds[8].innerHTML = '<div class="btn-group"><button class="btn btn-xs green dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false"> Actions <i class="fa fa-angle-down"></i></button><ul class="dropdown-menu pull-right" role="menu"><li><a class="edit" href="javascript:;"><i class="fa fa-save"></i> Save new user</a></li><li><a class="cancel" href="javascript:;"><i class="fa fa-times-circle"></i> Cancel</a></li></ul></div>';
        }

        function saveRow(oTable, nRow) {
            var jqInputs = $('input', nRow);
            var jqSelects = $('select', nRow);
			if((jqInputs[0].value != '')
            && (jqInputs[1].value != '')
            && (jqInputs[2].value != '')
            && (jqSelects[0].value != '')
            && (jqSelects[1].value != '')
            && (jqInputs[3].value != '')
            && (jqInputs[3].value <= 100)
            && (jqInputs[3].value >= 1)) {
            	oTable.fnUpdate(jqInputs[0].value, nRow, 1, false);
            	oTable.fnUpdate(jqInputs[1].value, nRow, 2, false);
            	oTable.fnUpdate(jqInputs[2].value, nRow, 3, false);
            	oTable.fnUpdate($('#select-countries option[value="' + jqSelects[0].value + '"]').text() + '<div style="display:none;">*' + jqSelects[0].value + '*</div>', nRow, 4, false);
				if (jqSelects[1].value == 0) {
                oTable.fnUpdate('<div class="btn-group"><button disabled class="btn btn-xs blue dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false" style="opacity:1;"> Admin <i class="fa fa-circle-thin"></i></button></div>', nRow, 5, false);
              	}else {
              	oTable.fnUpdate('<div class="btn-group">' +
                '<button class="btn btn-xs btn-default ' + rolesColor[jqSelects[1].value] + ' dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false"> ' + $('#select-type-user option[value="' + jqSelects[1].value + '"]').text() +
                    ' <i class="fa fa-angle-down"></i>' +
                '</button>' + rolesList + '</div>' +
				'<div style="display:none;">*' + jqSelects[1].value + '*</div>', nRow, 5, false);
              	}
            	oTable.fnUpdate(jqInputs[3].value, nRow, 7, false);
				if (jqSelects[1].value == 0) {
				oTable.fnUpdate('', nRow, 8, false);
				}else{
            	oTable.fnUpdate('<div class="btn-group"><button class="btn btn-xs green dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false"> Actions <i class="fa fa-angle-down"></i></button><ul class="dropdown-menu pull-right" role="menu"><li><a class="edit" href="javascript:;"><i class="fa fa-pencil"></i> Edit User</a></li>  <li><a class="enable"  href="javascript:;"><i class="fa fa-ban"></i> Disable user</a></li>  <li><a class="" href="javascript:deleteUser(\''+jqInputs[0].value+'\');"><i class="fa fa-trash"></i> Delete user</a></li>   </ul></div>', nRow, 8, false);
				}
            	oTable.fnDraw();
				return true;
            }else{
              $('#myModal2').modal('show');
              return false;
            }

        }

        var patternMail = /^([a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+(\.[a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+)*|"((([ \t]*\r\n)?[ \t]+)?([\x01-\x08\x0b\x0c\x0e-\x1f\x7f\x21\x23-\x5b\x5d-\x7e\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|\\[\x01-\x09\x0b\x0c\x0d-\x7f\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))*(([ \t]*\r\n)?[ \t]+)?")@(([a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.)+([a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.?$/i;

        function saveNewRow(oTable, nRow) {
            var jqInputs = $('input', nRow);
            var jqSelects = $('select', nRow);
            if((jqInputs[0].value != '')
            && patternMail.test(jqInputs[0].value)
            && (jqInputs[1].value != '')
            && (jqInputs[2].value != '')
            && (jqInputs[3].value != '')
            && (jqSelects[0].value != '')
            && (jqSelects[1].value != '')) {
              oTable.fnUpdate('<a href="mailto:' + jqInputs[0].value + '">' + jqInputs[0].value + '</a>', nRow, 0, false);
              oTable.fnUpdate(jqInputs[1].value, nRow, 1, false);
              oTable.fnUpdate(jqInputs[2].value, nRow, 2, false);
              oTable.fnUpdate(jqInputs[3].value, nRow, 3, false);
              oTable.fnUpdate($('#select-countries option[value="' + jqSelects[0].value + '"]').text() + '<div style="display:none;">*' + jqSelects[0].value + '*</div>', nRow, 4, false);
              if (jqSelects[1].value == 0) {
                oTable.fnUpdate('<div class="btn-group"><button disabled class="btn btn-xs blue dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false" style="opacity:1;"> Admin <i class="fa fa-circle-thin"></i></button></div>', nRow, 5, false);
              }else {
              oTable.fnUpdate('<div class="btn-group">' +
                '<button class="btn btn-xs btn-default ' + rolesColor[jqSelects[1].value] + ' dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false"> ' + $('#select-type-user option[value="' + jqSelects[1].value + '"]').text() +
                    ' <i class="fa fa-angle-down"></i>' +
                '</button>' + rolesList + '</div>' + 
                '<div style="display:none;">*' + jqSelects[1].value + '*</div>', nRow, 5, false);
              }
              oTable.fnUpdate('new user', nRow, 6, false);
              oTable.fnUpdate(diskLimit, nRow, 7, false);
              if(jqSelects[1].value == 0) oTable.fnUpdate('', nRow, 8, false);
              else oTable.fnUpdate('<div class="btn-group"><button class="btn btn-xs green dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false"> Actions <i class="fa fa-angle-down"></i></button><ul class="dropdown-menu pull-right" role="menu"><li><a class="edit" href="javascript:;"><i class="fa fa-pencil"></i> Edit User</a></li>  <li><a class="enable"  href="javascript:;"><i class="fa fa-ban"></i> Disable user</a></li>  <li><a class="" href="javascript:deleteUser(\''+jqInputs[0].value+'\');"><i class="fa fa-trash"></i> Delete user</a></li>  </ul></div>', nRow, 8, false);
              oTable.fnDraw();
              return true;
            }else{
              $('#myModal2').modal('show');
              return false;
            }
        }

        var table = $('#sample_editable_1');

        var oTable = table.dataTable({

            // Uncomment below line("dom" parameter) to fix the dropdown overflow issue in the datatable cells. The default datatable layout
            // setup uses scrollable div(table-scrollable) with overflow:auto to enable vertical scroll(see: assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.js).
            // So when dropdowns used the scrollable div should be removed.
            //"dom": "<'row'<'col-md-6 col-sm-12'l><'col-md-6 col-sm-12'f>r>t<'row'<'col-md-5 col-sm-12'i><'col-md-7 col-sm-12'p>>",

            "lengthMenu": [
                [5, 10, 20, -1],
                [5, 10, 20, "All"] // change per page values here
            ],

            // Or you can use remote translation file
            //"language": {
            //   url: '//cdn.datatables.net/plug-ins/3cfcc339e89/i18n/Portuguese.json'
            //},

            // set the initial value
            "pageLength": 10,

            "language": {
                "lengthMenu": " _MENU_ records"
            },
            "columnDefs": [{ // set default column settings
                'orderable': true,
                'targets': [0]
            }, {
                "searchable": true,
                "targets": [0]
            },{ // set default column settings
                'orderable': false,
                'targets': [8]
            }, {
                "searchable": false,
                "targets": [8]
            }],
            "order": [
                [0, "asc"]
            ] // set first column as a default sort by asc
        });

        var tableWrapper = $("#sample_editable_1_wrapper");

        var nEditing = null;
        var nNew = false;

        $('#sample_editable_1_new').click(function (e) {
            e.preventDefault();

            if (/*nNew && */nEditing) {
                $('#myModal1').modal('show');
            }else{
              var aiNew = oTable.fnAddData(['', '', '', '', '', '', '', '', '']);
              var nRow = oTable.fnGetNodes(aiNew[0]);
              editNewRow(oTable, nRow);
              nEditing = nRow;
              nNew = true;
            }

        });

        table.on('click', '.cancel', function (e) {
            e.preventDefault();
            if (nNew) {
                oTable.fnDeleteRow(nEditing);
                nEditing = null;
                nNew = false;
            } else {
                restoreRow(oTable, nEditing);
                nEditing = null;
            }
        });

        table.on('click', '.edit', function (e) {
            e.preventDefault();

            /* Get the row as a parent of the link that was clicked on */
            var nRow = $(this).parents('tr')[0];

            if (nEditing !== null && nEditing != nRow) {
                if(nNew){
                  $('#myModal3').modal('show');
                }else{
                  /* Currently editing - but not this row - restore the old before continuing to edit mode */
                  restoreRow(oTable, nEditing);
                  editRow(oTable, nRow);
                  nEditing = nRow;
                  nNew = false;
                }

            } else if (nEditing == nRow && this.innerHTML.indexOf("Save user") != -1 ) {
                /* Editing this row and want to save it */
                var jqInputs = $('input', nRow);
                var jqSelects = $('select', nRow);
                var jqTds = $('>td', nRow);

                if(saveRow(oTable, nEditing)) {
                  nEditing = null;
                  nNew = false;

                  var strData = 'id=' + jqTds[0].innerText + '&name=' + jqInputs[1].value + '&surname=' + jqInputs[0].value + '&inst=' + jqInputs[2].value + '&disk=' + jqInputs[3].value + '&country=' + jqSelects[0].value + '&type=' + jqSelects[1].value;
                  strData = strData.replace(/ /g, '+');
					
				  if(jqSelects[1].value != 0){
				  	var actionsButton = $('button', $('td:last', nRow)[0]);
				  	actionsButton[0].innerHTML = 'Sending...';
				  	actionsButton.prop('disabled', true);
				  }

				  $.ajax({
           			type: "POST",
           			url: baseURL + "applib/modifyUserData.php",
           			data: strData, 
           			success: function(data) {
						d = data.replace(/(\r\n|\n|\r|\t)/gm,"");
               			if(d == '1'){
						}else{
							$('#myModal5').modal('show');	
						}
						if(jqSelects[1].value != 0){
							actionsButton[0].innerHTML = 'Actions <i class="fa fa-angle-down"></i>';
							actionsButton.prop('disabled', false);
						}
					}
         		  });

				}

            } else if (nEditing == nRow && this.innerHTML.indexOf("Save new user") != -1 ) {
                /* Editing this row and want to save it */
                var jqInputs = $('input', nRow);
                var jqSelects = $('select', nRow);

                if(saveNewRow(oTable, nEditing)) {
                  nEditing = null;
                  var strData = 'Email=' + jqInputs[0].value + '&Name=' + jqInputs[2].value + '&Surname=' + jqInputs[1].value + '&Inst=' + jqInputs[3].value + '&Country=' + jqSelects[0].value + '&Type=' + jqSelects[1].value;
                  strData = strData.replace(/ /g, '+');
                  console.log(strData);
				
				  if(jqSelects[1].value != 0){
				  	var actionsButton = $('button', $('td:last', nRow)[0]);
				  	actionsButton[0].innerHTML = 'Sending...';
				  	actionsButton.prop('disabled', true);
				  }

				  $.ajax({
           			type: "POST",
           			url: baseURL + "applib/newUserFromAdmin.php",
           			data: strData, 
           			success: function(data) {
						d = data.replace(/(\r\n|\n|\r|\t)/gm,"");
               			if(d == '1'){
						}else{
							$('#myModal6').modal('show');
            				oTable.fnDeleteRow(nRow);
						}
						if(jqSelects[1].value != 0){
							actionsButton[0].innerHTML = 'Actions <i class="fa fa-angle-down"></i>';
							actionsButton.prop('disabled', false);
						}
					}
         		  });


                }
                nNew = false;

            } else {
                /* No edit in progress - let's start one */
                editRow(oTable, nRow);
                nEditing = nRow;
                nNew = false;
            }
        });

		
		table.on('click', '.enable', function (e) {
            e.preventDefault();

            var nRow = $(this).parents('tr')[0];

            /*if (nEditing !== null && nEditing == nRow) {
		        
				$('#myModal4').modal('show');

            } 
			else if (nEditing === null &&*/ 
			
			if(this.innerHTML.indexOf("Enable user") != -1 ) {
				
				var actionsButton = $('button', $('td:last', nRow)[0]);	
				var actionsList = 	$('ul', $('td:last', nRow)[0]);
				
				actionsButton[0].innerHTML = 'Sending...';
				actionsButton.prop('disabled', true);
		
				$.ajax({
           			type: "POST",
           			url: baseURL + "applib/changeStatusOfUser.php",
           			data: 'id=' + $('td:first', nRow)[0].innerText.split('\n')[0] + '&s=1', 
           			success: function(data) {
						d = data.replace(/(\r\n|\n|\r|\t)/gm,"");
               			if(d == '1'){
							var newActions =  '<div class="btn-group">'+
											  '<button class="btn btn-xs green dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false"> Actions'+
                                               ' <i class="fa fa-angle-down"></i>'+
                                              '</button>'+
                                              '<ul class="dropdown-menu pull-right" role="menu">'+
                                                '<li>'+
                                                  '<a class="" href="admin/editUser.php?id='+$('td:first', nRow)[0].innerText.split('\n')[1]+'"><i class="fa fa-pencil"></i> Edit User</a>'+
                                                '</li>'+
                                                '<li>'+
                                                '<a class="enable" href="javascript:;"><i class="fa fa-ban"></i> Disable user</a></li>'+
                                                '<li>'+
                                                '<a class="" href="javascript:deleteUser(\''+$('td:first', nRow)[0].innerText.split('\n')[1]+'\');"><i class="fa fa-trash"></i> Delete user</a></li>'+
                                               '</ul>'+
											   '</div>';
							oTable.fnUpdate(newActions, nRow, 8, false);
						}else{
							$('#myModal5').modal('show');	
						}	
						actionsButton[0].innerHTML = 'Actions <i class="fa fa-angle-down"></i>';
						actionsButton.prop('disabled', false);
					}
         		});

            } else if (/*nEditing === null &&*/ this.innerHTML.indexOf("Disable user") != -1 ) {

				var actionsButton = $('button', $('td:last', nRow)[0]);				
				var actionsList = 	$('ul', $('td:last', nRow)[0]);
				
				actionsButton[0].innerHTML = 'Sending...';
				actionsButton.prop('disabled', true);
		
				$.ajax({
           			type: "POST",
           			url: baseURL + "applib/changeStatusOfUser.php",
           			data: 'id=' + $('td:first', nRow)[0].innerText.split('\n')[0] + '&s=0', 
           			success: function(data) {
						d = data.replace(/(\r\n|\n|\r|\t)/gm,"");
               			if(d == '1'){
							var newActions =  '<div class="btn-group">'+
											  '<button class="btn btn-xs red dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false"> Actions'+
                                               ' <i class="fa fa-angle-down"></i>'+
                                              '</button>'+
                                              '<ul class="dropdown-menu pull-right" role="menu">'+
                                                '<li>'+
                                                '<a class="enable" href="javascript:;"><i class="fa fa-check-circle"></i> Enable user</a></li>'+
                                               '</ul>'+
											   '</div>';
							oTable.fnUpdate(newActions, nRow, 8, false);
						}else{
							$('#myModal5').modal('show');	
						}	
						actionsButton[0].innerHTML = 'Actions <i class="fa fa-angle-down"></i>';
						actionsButton.prop('disabled', false);
					}
         		});
	
			
            } 
        });

		table.on('click', '.role-usr', function (e) {
            e.preventDefault();

            var nRow = $(this).parents('tr')[0];

			var classList = $(this).attr('class').split(/\s+/);
			var idRole = classList[1].substring(4,7);
			var nameRole = this.innerHTML;
			var jqTds = $('>td', nRow);
			var oldRole = jqTds[5].innerHTML.split("*")[1];
			var actionsButton = $('button', $('td:eq(5)', nRow)[0]);
				
			actionsButton[0].innerHTML = 'Sending...';
			actionsButton.prop('disabled', true);

			$.ajax({
           		type: "POST",
           		url: baseURL + "applib/changeTypeOfUser.php",
           		data: 'id=' + $('td:first', nRow)[0].innerText + '&t=' + idRole + '&ot=' + oldRole, 
           		success: function(data) {
					d = data.replace(/(\r\n|\n|\r|\t)/gm,"");
               		if(d == '1'){
               			if(idRole == 0) {
               				var newRoles = '<div class="btn-group">'+
										'<button disabled class="btn btn-xs btn-default ' + rolesColor[idRole]  + ' dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false" style="opacity:1;"> ' + nameRole +
                                        	' <i class="fa fa-circle-thin"></i>' +
                                        '</button>' + 
                                        '</div>';
							oTable.fnUpdate('', nRow, 8, false);

						}else{
							var newRoles = '<div class="btn-group">'+
										'<button class="btn btn-xs btn-default ' + rolesColor[idRole]  + ' dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false"> ' + nameRole +
                                        	' <i class="fa fa-angle-down"></i>' +
                                        '</button>' + 
                                        rolesList + 
                                        '<div style="display:none;">*' + idRole  + '*</div>' +
                                        '</div>';
                        }

						oTable.fnUpdate(newRoles, nRow, 5, false);

					}else{
						$('#myModal5').modal('show');	
					}	
					actionsButton[0].innerHTML = nameRole + ' <i class="fa fa-angle-down"></i>';
					actionsButton.prop('disabled', false);
				}
         	});

		});
	
    }

    return {

        //main function to initiate the module
        init: function () {
            handleTable();
        }

    };

}();

var userID;

function deleteUser(user){
  $('#modalDelete .modal-body').html('Are you sure you want to delete the selected user and ALL her / his data? This operation cannot be undone!');
  $('#modalDelete').modal({ show: 'true' });
	userID = user;
}


jQuery(document).ready(function() {
    TableDatatablesEditable.init();

		$('#modalDelete').find('.modal-footer .btn-modal-del').on('click', function(){
			location.href= baseURL + "applib/delUser.php?id=" + userID;
		});

});
