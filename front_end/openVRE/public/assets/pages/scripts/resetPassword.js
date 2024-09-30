var baseURL = $('#base-url').val();

var Login = function() {

    var handleReset = function() {

        $('.login-form').validate({
            errorElement: 'span', //default input error message container
            errorClass: 'help-block', // default input error message class
            focusInvalid: false, // do not focus the last invalid input
            rules: {
                usermail: {
                    required: true,
					email: true
                },
                pass1: {
                    required: true
                },
                pass2: {
                    equalTo: "#register_password"
                },
            },

            messages: {
                usermail: {
                    required: "Email is required."
                },
                pass1: {
                    required: "Password is required."
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
				$('#login-button').prop('disabled', true);
				$('#login-button').html('Submitting...');
				
				$.ajax({
           			type: "POST",
           			url: baseURL + "applib/updatePassword.php",
           			data: $('.login-form').serialize(), 
           			success: function(data) {
						d = data.replace(/(\r\n|\n|\r|\t)/gm,"");
               			if(d == '1'){
							$('#succ-msg-pwdchg').fadeIn(300);
							$('.login-guest').fadeIn(300);
							$('.fg-chgpwd').fadeOut(300);
							$('#err-msg-login').fadeOut(300);
							$('#err-msg-link').fadeOut(300);
						}else if(d == '2'){
							$('#err-msg-login').fadeIn(300);
							$('#login-button').prop('disabled', false);
							$('#login-button').html('Submit');
						}else{
							$('#err-msg-link').fadeIn(300);
							$('#login-button').prop('disabled', false);
							$('#login-button').html('Submit');
						}
					}
         		});

            }
        });

        $('.login-form input').keypress(function(e) {
            if (e.which == 13) {
                if ($('.login-form').validate().form()) {
                    $('.login-form').submit(); //form validation success, call ajax form submit
                }
                return false;
            }
        });
    }

    return {
        //main function to initiate the module
        init: function() {

            handleReset();

        }

    };

}();

jQuery(document).ready(function() {
    Login.init();
});
