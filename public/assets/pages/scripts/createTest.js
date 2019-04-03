var baseURL = $('#base-url').val();

$.validator.addMethod("regx", function(value, element, regexpr) { 
		if(!value) return true;
    return regexpr.test(value);
});

var ValidateForm = function() {

    var handleForm = function() {

				$('#workflowtype').change(function() {
				
					if($(this).val() == "compss") {
						$('#tool_lib_block').show();
						$('#tool_lib').prop('disabled', false);
					} else {
						$('#tool_lib_block').hide();
						$('#tool_lib').prop('disabled', true);
					}
				
				});


        $('#create-test').validate({
            errorElement: 'span', //default input error message container
            errorClass: 'help-block', // default input error message class
            focusInvalid: false, // do not focus the last invalid input
            ignore: [],
            rules: {
							execution: { required:true },
							tool_executable: { required:true },
							//tool_lib: { required:true }
            },
						messages: {
							
						},

            highlight: function(element) { // hightlight error inputs
                $(element)
                    .closest('.form-group').addClass('has-error'); // set error class to the control group
            },

            success: function(label, e) {
                $(e).parent().removeClass('has-error');
                $(e).parent().parent().removeClass('has-error');
                $(e).parent().parent().parent().removeClass('has-error');
            },

            errorPlacement: function(error, element) {
            	if($(element).hasClass("select2-hidden-accessible")) {
            		console.log($(element).parent());
            		error.insertAfter($(element).parent().find("span.select2"));
							} else {
								error.insertAfter(element);
							}
						},

            submitHandler: function(form) {
              /*    var data = $('#create-test').serialize();
                	console.log(data);*/
							form.submit();
            }
        });

				$(".input-file-path").each(function() {
					$(this).rules("add", {
						regx: /^\/.{1,}$/,
						messages: {
							regx: "This field must be filled in a path format like /path/to/disk.",
						}
					});
				});

				$(".input-files").each(function() {
					$(this).rules("add", {
						required:true
					});
				});


				
        
		}

    return {
        //main function to initiate the module
        init: function() {
            handleForm();
        }

    };

}();

var Select2Init = function() {

	var handleSelect2 = function() {

		$(".select-multiple").select2({
	  placeholder: "Select one or more",
		width: '100%'
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
  ValidateForm.init();

});
