
var Select2Init = function() {

	var handleSelect2 = function() {

		$(".select2tools").select2({
			placeholder: "Select one or more tools clicking here",
			width: '100%'
		});

		$('.select2tools').on('change', function() {
			if($(this).find('option:selected').length > 0) {
				$(this).parent().removeClass('has-error');
				$(this).parent().find('.help-block').hide();
			}
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

	Select2Init.init();	

	$('#Type').change(function() {

		var type = $(this).find("option:selected").attr('value');

		if(type == 1) {

			$('#tools').attr('disabled', false);
			$('.tools_select').show();	

		} else {
			
			$('#tools').attr('disabled', true);
			$('.tools_select').hide();

		}	
	
	});

});
