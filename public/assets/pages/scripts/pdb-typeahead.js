var baseURL = $('#base-url').val();

var ComponentsTypeahead = function () {

    var handleTwitterTypeahead = function() {

		var pdbs = new Bloodhound({
      datumTokenizer: function(d) { return Bloodhound.tokenizers.whitespace(d.name); },
      queryTokenizer: Bloodhound.tokenizers.whitespace,
      limit: 10,
      remote: {
        url: baseURL  + 'applib/getPDBList.php?q=%QUERY',
        wildcard: '%QUERY',
		    filter: function(list) {
          return $.map(list, function(pdb_code) { return { name: pdb_code }; });
        }
      }
    });

    pdbs.initialize();

    $('#idcode').typeahead({
		  hint: true,
		  highlight: true
		}, {
      name: 'pdb_code',
      displayKey: 'name',
      source: pdbs.ttAdapter(),
      limit: 10000
    })
    .bind('typeahead:select', function(ev, suggestion){
    	// loadMonomers(suggestion.name);
    	//console.log(suggestion);
    	//TODO: flag avisant que he fet un select, si estÃ  a false no deixo fer submit
			$('#send_data').prop('disabled', false);
    }).bind('typeahead:render', function(ev) {
			//var select = '<option value="">Please select first a PDB ID</option>';
			//$('#ligand_code_pdb').attr('disabled', true);
			//$('#ligand_code_pdb').html(select);
			$('#send_data').prop('disabled', true);
    }).on('typeahead:asyncrequest', function() {
			$('.Typeahead-spinner').show();
		})
		.on('typeahead:asynccancel typeahead:asyncreceive', function() {
			$('.Typeahead-spinner').hide();
		});

    }

    return {
        //main function to initiate the module
        init: function () {
            handleTwitterTypeahead();
        }
    };

}();

var db = '';

jQuery(document).ready(function() {

		$("#databank").change(function() {
			db = $(this).val();
			$("#idcode").prop('disabled', false);
		});

   ComponentsTypeahead.init();
});
