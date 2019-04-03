var createDatatableTools = function () {

  var handleDatatableTools = function() {

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

    table = $('#tools-list').DataTable({
      lengthMenu: [[20,-1],[20,"All"]],
       // set the initial value
       "pageLength": 20,
       //"searchHighlight": true,
       "order": [
           [1, "asc"]
       ], // set first column as a default sort by asc
       "initComplete": function (settings, json) {
         $("#loading-datatable").hide();
         $('#tools-list').show();
       },
       "columns": [
            {
                "className":      'details-control',
                "orderable":      false,
                "defaultContent": '',
                "render": function () {
                  return '<i class="fa fa-plus-square font-green" aria-hidden="true"></i>';
                },
            },
            { "defaultContent": '' },
            { "defaultContent": '' },
            { "defaultContent": '' }
        ],
        "columnDefs": [
            { "targets": [ 0 ], "searchable": false },
            { "targets": [ 4 ], "visible": false },
            { "targets": [ 5 ], "visible": false },
            { "targets": [ 6 ], "visible": false },
            { "targets": [ 7 ], "visible": false },
            { "targets": [ 8 ], "visible": false, "searchable": false },
	          { "targets": [ 9 ], "visible": false, "searchable": false }
        ]
    });

    // create table with secondary data on clicking row
    function format ( d ) {
      var operations = d[4].split("~");
      var launch_buttons = "";
      $.each(operations, function( index, value ) {

      	if(d[9]) {
        	launch_buttons += '<a href="visualizers/' + d[8] + '/input.php" class="btn btn-sm green uppercase" style="margin-bottom:10px;"><i class="fa fa-rocket"></i>&nbsp;&nbsp;' + value + '</a><br>';
				} else {
        	launch_buttons += '<a href="tools/' + d[8] + '/input.php?op=' + index + '" class="btn btn-sm green uppercase" style="margin-bottom:10px;"><i class="fa fa-rocket"></i>&nbsp;&nbsp;' + value + '</a><br>';
				}
      });

      if(operations.length > 1) var op_name = "Operations";
      else var op_name = "Operation";

      // `d` is the original data object for the row
      return '<table cellpadding="3" cellspacing="0" border="0" style="width:70%;" class="table">'+
          '<tr class="second-level-tr">'+
              '<td style="width:20%;">' + op_name +':</td>'+
              '<td style="width:80%;">' + launch_buttons + '</td>'+
          '</tr>'+
          '<tr class="second-level-tr">'+
              '<td>Long description:</td>'+
              '<td>' + d[5] + '</td>'+
          '</tr>'+
          '<tr class="second-level-tr">'+
              '<td>Keywords:</td>'+
              '<td>' + d[6] + '</td>'+
          '</tr>'+
      '</table>';
    }

    // on click row show secondary data
    $('#tools-list tbody').on('click', 'tr.first-level-tr', function () {
        var tr = $(this).closest('tr');
        var tdi = tr.find("i.fa");
        var row = table.row( tr );

        if ( row.child.isShown() ) {
            // This row is already open - close it
            row.child.hide();
            tr.removeClass('shown');
            tdi.first().removeClass('fa-minus-square');
            tdi.first().addClass('fa-plus-square');
        }
        else {
            // Open this row
            row.child( format(row.data()) ).show();
            tr.addClass('shown');
            tdi.first().removeClass('fa-plus-square');
            tdi.first().addClass('fa-minus-square');
        }

    } );

    var mtkw = [];

    // for column 6, search when selecting keyword
    table.columns().every( function () {
        var that = this;
        var body = $( table.table().body() );

        $( "#sel-keyword").on( 'change', function () {

          //if($(this).val() !== null ) $(table.column(6).nodes()).highlight($(this).val());

          if ( that.selector.cols === 6 && $(this).val() !== null ) {

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

        // filter by metaKWs
        $( "#main_keys button").on( 'click', function () {

          var kw = $(this).text().replace(/ *\([^)]*\) */g, "")

          if ( that.selector.cols === 7 && mtkw !== null ) {

            if(!$(this).hasClass("active")) {

              mtkw.push(kw);
              $(this).toggleClass("active");

            } else {

              $(this).removeClass("active");
              $(this).blur();
              mtkw.remove(kw);

            }

            var keys = "";
            $.each(mtkw, function( index, value ) {
              keys += "(?=.*" + value + ")";
            });

              that
                  .search( keys, true, false, true )
                  .draw();

          }

        } );

    } );

    // global search
    $( "#simp-search").on( 'keyup change', function () {

        /*var body = $( table.table().body() );
        body.highlight( table.search($(this).val()).draw() );*/
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

});

