//var baseURL = $('#base-url').val();

var Helpdesk = function() {

    var handleHelpdesk = function() {

				$('#Request').change(function() {
					if($(this).val() == "tools") {
						$('#Tool').prop('disabled', false);
						$('#row-tools').show();
						$('#label-msg').html("Message details");
					}else if($(this).val() == "tooldev"){
						$('#label-msg').html("Please tell us which kind of tool(s) you want to integrate in the VRE");
					}else{
						$('#Tool').prop('disabled', true);
						$('#row-tools').hide();
						$('#label-msg').html("Message details");
					}
				});


        $('#helpdesk').validate({
            errorElement: 'span', //default input error message container
            errorClass: 'help-block', // default input error message class
            focusInvalid: false, // do not focus the last invalid input
            rules: {
                Request: {
                    required: true,
                },
								Tool: {
                    required: true,
                },
                Subject: {
                    required: true
                },
                Message: {
                    required: true
                }
            },

            invalidHandler: function(event, validator) { //display error alert on form submit
                //$('#err-mail-pwd', $('.login-form')).show();
            },

            highlight: function(element) { // hightlight error inputs
                $(element)
                    .closest('.form-group').addClass('has-error'); // set error class to the control group
            },

            success: function(label) {
                label.closest('.form-group').removeClass('has-error');
                label.remove();
            },

            errorPlacement: function(error, element) {

							if (element.closest('.input-icon').size() === 1) {
                    error.insertAfter(element.closest('.input-icon'));
                } else {
                    error.insertAfter(element);
                }

            },

            submitHandler: function(form) {

            	form.submit();

            }
        });

        $('#helpdesk input').keypress(function(e) {
            if (e.which == 13) {
                if ($('#helpdesk').validate().form()) {
                    $('#helpdesk').submit(); //form validation success, call ajax form submit
                }
                return false;
            }
        });
    }

    return {
        //main function to initiate the module
        init: function() {

           handleHelpdesk();

        }

    };

}();

jQuery(document).ready(function() {
    Helpdesk.init();
});
