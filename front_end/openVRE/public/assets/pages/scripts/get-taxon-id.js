var baseURL = $('#base-url0').val();

function changeArgDependency(dep, op, enable) {

	$.each($('.field_dependency' + dep) , function() {
	
		$(this).hide();
		$(this).prop("disabled", true);
		$(this).val('');
		$('input[name="taxon_id"]').val('');

	});

	$('.field_dependency' + dep + '_' + op).show();
	$('.field_dependency' + dep + '_' + op).prop("disabled", false);
	if(!enable) {
		$('.field_dependency' + dep + '_' + op).prop("disabled", true);
		$('.field_dependency' + dep + '_' + op).parent().parent().removeClass('has-error');
		$('.field_dependency' + dep + '_' + op).parent().parent().next('.help-block');

	}

	$('.arg_dependency' + dep).html($('.arg_dependency' + dep + '_' + op)[0].innerText);
	
}



var TaxonNameTypeahead = function () {

    var handleTwitterTypeahead = function() {

		var taxons_name = new Bloodhound({
      datumTokenizer: function(d) { return Bloodhound.tokenizers.whitespace(d.name); },
      queryTokenizer: Bloodhound.tokenizers.whitespace,
      limit: 20,
      remote: {
				url: 'https://www.ebi.ac.uk/ena/data/taxonomy/v1/taxon/suggest-for-search/%QUERY',
        wildcard: '%QUERY',
		    filter: function(list) {
          return $.map(list, function(taxon) { return { name: taxon.scientificName, id:taxon.taxId }; });
        }
      }
    });

    taxons_name.initialize();

    $('.taxon_name').typeahead({
		  hint: true,
		  highlight: true
		}, {
      name: 'taxon',
      displayKey: 'name',
      display: function(item){ return item.name + ' (' + item.id + ')'},
      source: taxons_name.ttAdapter(),
      limit: 100
    })
    .bind('typeahead:select', function(ev, suggestion){
    	$('input[name="taxon_id"]').val(suggestion.id);
    }).bind('typeahead:render', function(ev) {
    }).on('typeahead:asyncrequest', function() {
			$('.Typeahead-spin').show();
		})
		.on('typeahead:asynccancel typeahead:asyncreceive', function() {
			$('.Typeahead-spin').hide();
		});

    }

    return {
        //main function to initiate the module
        init: function () {
            handleTwitterTypeahead();
        }
    };

}();


var TaxonIDTypeahead = function () {

    var handleTwitterTypeahead = function() {

		var taxons_id = new Bloodhound({
      datumTokenizer: function(d) { return Bloodhound.tokenizers.whitespace(d.name); },
      queryTokenizer: Bloodhound.tokenizers.whitespace,
      limit: 1,
      remote: {
				url: 'https://www.ebi.ac.uk/ena/data/taxonomy/v1/taxon/tax-id/%QUERY',
        wildcard: '%QUERY',
		    filter: function(list) {
          //return $.map(list, function(taxon) { return { name: taxon.scientificName, id:taxon.taxId }; });
					return $.map(list, function(taxon) { return { name: list.scientificName, id:list.taxId }; });
					//return { name: "q", id:"1" };
        }
      }
    });

    taxons_id.initialize();

    $('.taxon_id').typeahead({
		  hint: true,
		  highlight: true
		}, {
      name: 'taxon',
      displayKey: 'name',
      display: function(item){ return item.id + ' (' + item.name + ')' },
      source: taxons_id.ttAdapter(),
      limit: 1
    })
    .bind('typeahead:select', function(ev, suggestion){
			//console.log(suggestion);
    	$('input[name="taxon_id"]').val(suggestion.id);
    }).bind('typeahead:render', function(ev) {
    }).on('typeahead:asyncrequest', function() {
			$('.Typeahead-spin').show();
		})
		.on('typeahead:asynccancel typeahead:asyncreceive', function() {
			$('.Typeahead-spin').hide();
		});

    }

    return {
        //main function to initiate the module
        init: function () {
            handleTwitterTypeahead();
        }
    };

}();


jQuery(document).ready(function() {
	TaxonIDTypeahead.init();
  TaxonNameTypeahead.init();
});
