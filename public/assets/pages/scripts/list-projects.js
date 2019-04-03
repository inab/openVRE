var projectName = '';
var baseURL = $('#base-url').val();

function deleteProject(project, name){

	$.ajax({
			type: "GET",
			url: baseURL + "applib/manageProjects.php",
			data: "op=deleteMsg&pr_id=" + project, 
			success: function(data) {
				$('#modalDeleteProject').modal({ show: 'true' });

				$('#modalDeleteProject .modal-body').html("<p>Are you sure you want to delete the project <strong>" + name + "</strong> " + 
					"and <strong>ALL</strong> its executions and files? This action cannot be undone. List of executions you will remove:</p>" + 
					data);

			}
		});

	projectName = project;
}

var createDatatableTools = function () {

  var handleDatatableTools = function() {

    table = $('#projects-list').DataTable({
      lengthMenu: [[20,-1],[20,"All"]],
       // set the initial value
       "pageLength": 20,
       "order": [
           [2, "desc"]
       ], // set first column as a default sort by asc
       "initComplete": function (settings, json) {
         $("#loading-datatable").hide();
         $('#projects-list').show();
       },
        "columnDefs": [
						{ "targets": [ 3 ], "sortable": false },
            { "targets": [ 4 ], "visible": false },
        ]
    });

    // for column 6, search when selecting keyword
    table.columns().every( function () {
        var that = this;
        var body = $( table.table().body() );

        $( "#sel-keyword").on( 'change', function () {

          //if($(this).val() !== null ) $(table.column(6).nodes()).highlight($(this).val());

          if ( that.selector.cols === 4 && $(this).val() !== null ) {

            var keys = "";
            $.each($(this).val(), function( index, value ) {
              keys += "(?=.*" + value + ")";
            });

              that
                  .search( keys, true, false, true )
                  .draw();
          }

          if($(this).val() === null) {
              that
                  .search( "", true, false, true )
                  .draw();
          }

        } );

    } );

    // global search
    $( "#simp-search").on( 'keyup change', function () {

        table.search($(this).val()).draw()

    } );



  }

  return {
  			//main function to initiate the module
  			init: function () {

  					handleDatatableTools();

  			}

      };

}();

var createSelect2 = function () {

  var handleSelect2 = function() {

    $("#sel-keyword").select2({
      placeholder: "Select or write keyword(s)",
      width: '100%',
      minimumResultsForSearch: 1
    });

  }

  return {
  			//main function to initiate the module
  			init: function () {

  					handleSelect2();

  			}

      };

}();

$(document).ready(function() {

  createDatatableTools.init();
  createSelect2.init();

	$('#modalDeleteProject').find('.modal-footer .btn-modal-del').on('click', function(){
		$('#modalDeleteProject').find('.modal-footer .btn-modal-del').prop('disabled', true);
		$('#modalDeleteProject').find('.modal-footer .btn-modal-del').html('Deleting...');

		location.href = baseURL + "applib/manageProjects.php?op=delete&pr_id=" + projectName;

	});

});

