var baseURL = $('#base-url').val();

$.validator.addMethod("regx", function(value, element, regexpr) { 
		if(!value) return true;
    return regexpr.test(value);
});

var VmURL = function() {

    var handleVmURL= function() {

        $('#vm-url').validate({
            errorElement: 'span', //default input error message container
            errorClass: 'help-block', // default input error message class
            focusInvalid: false, // do not focus the last invalid input
            rules: {
            },

            invalidHandler: function(event, validator) { //display error alert on form submit
                //$('#err-mail-pwd', $('.login-form')).show();
            },

            highlight: function(element) { // hightlight error inputs
                $(element)
                    .closest('.form-group').addClass('has-error'); // set error class to the control group
            },

            success: function(label) {
								console.log(label);
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
							/*var data = $('#vm-url').serialize();
                	console.log(data);*/

            }
        });

				$(".mandatory-vm").each(function() {
					$(this).rules("add", {
						regx: /^(ftp|http|https):\/\/[^ "]+$/,
						messages: {
							regx: "This field must be filled with an URL format.",
						},
						required: true
					});
				});

        $('#vm-url input').keypress(function(e) {
            if (e.which == 13) {
                if ($('#vm-url').validate().form()) {
                    $('#vm-url').submit(); //form validation success, call ajax form submit
                }
                return false;
            }
        });
    }

    return {
        //main function to initiate the module
        init: function() {

           handleVmURL();

        }

    };

}();

var ChangeOption = function() {

	var handleChangeOption= function() {

		$('input[name=type]').change(function() {

			var id = $(this).val();
			if(id == "git") var opts = ["git", "vm"];
			else  var opts = ["vm", "git"];

			$("#" + opts[1] + "-block").removeClass("active");
			$("#" + opts[0] + "-block").addClass("active");

			$("#" + opts[1]).prop("disabled", true);
			$("#" + opts[0]).prop("disabled", false);			

			$('span#' + opts[1] + '-error.help-block').closest('.form-group').removeClass('has-error');
      $('span#' + opts[1] + '-error.help-block').remove();

		});	

	}

	return {
        //main function to initiate the module
        init: function() {

           handleChangeOption();

        }

    };

}();

$(document).ready(function() {
    VmURL.init();
		ChangeOption.init();
});
