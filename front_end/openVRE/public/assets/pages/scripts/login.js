var baseURL = $('#base-url').val();

var Login = function() {

    var handleLogin = function() {

        $('.login-form').validate({
            errorElement: 'span', //default input error message container
            errorClass: 'help-block', // default input error message class
            focusInvalid: false, // do not focus the last invalid input
            rules: {
                usermail: {
                    required: true,
					email: true
                },
                password: {
                    required: true
                }/*,
                remember: {
                    required: false
                }*/
            },

            messages: {
                usermail: {
                    required: "Email is required."
                },
                password: {
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
				$('#login-button').html('Logging in...');
				
				$.ajax({
           			type: "POST",
           			url: baseURL + "applib/checkUser.php",
           			data: $('.login-form').serialize(), 
           			success: function(data) {
						d = data.replace(/(\r\n|\n|\r|\t)/gm,"");
               			if(d == '1'){
							form.submit();  
						}else{
							$('#err-msg-login').fadeIn(300);
							$('#login-button').prop('disabled', false);
							$('#login-button').html('Login');
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

    var handleForgetPassword = function() {
        $('.forget-form').validate({
            errorElement: 'span', //default input error message container
            errorClass: 'help-block', // default input error message class
            focusInvalid: false, // do not focus the last invalid input
            ignore: "",
            rules: {
                emailf: {
                    required: true,
                    email: true
                }
            },

            messages: {
                emailf: {
                    required: "Email is required."
                }
            },

            invalidHandler: function(event, validator) { //display error alert on form submit
              //$('.alert-danger', $('.forget-form')).show();
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
              //alert('success');
                //form.submit();
				$('#forgot-submit-btn').prop('disabled', true);
				$('#forgot-submit-btn').html('Submitting...');
				
				$.ajax({
           			type: "POST",
           			url: baseURL + "applib/forgotPassword.php",
           			data: $('.forget-form').serialize(), 
           			success: function(data) {
						d = data.replace(/(\r\n|\n|\r|\t)/gm,"");
               			if(d == '1'){
							$('#succ-snd-email').fadeIn(300);
							$('#err-snd-email').fadeOut(300);
							$('#err-mail-prvd').fadeOut(300);
							$('#forgot-submit-btn').prop('disabled', false);
							$('#forgot-submit-btn').html('Submit');
							$('input[name="emailf"]').val('');
						}else if(d == '2'){
							$('#succ-snd-email').fadeOut(300);
							$('#err-snd-email').fadeIn(300);
							$('#err-mail-prvd').fadeOut(300);
							$('#forgot-submit-btn').prop('disabled', false);
							$('#forgot-submit-btn').html('Submit');
						}else{
							$('#succ-snd-email').fadeOut(300);
							$('#err-snd-email').fadeOut(300);
							$('#err-mail-prvd').fadeIn(300);
							$('#forgot-submit-btn').prop('disabled', false);
							$('#forgot-submit-btn').html('Submit');

						}
					}
         		});

            }
        });

        $('.forget-form input').keypress(function(e) {
            if (e.which == 13) {
                if ($('.forget-form').validate().form()) {
                    $('.forget-form').submit();
                }
                return false;
            }
        });

        jQuery('#forget-password').click(function() {
            jQuery('.login-form').hide();
            jQuery('.forget-form').show();
        });

        jQuery('#back-btn').click(function() {
            jQuery('.login-form').show();
            jQuery('.forget-form').hide();
        });

    }

    var handleRegister = function() {

        function format(state) {
            if (!state.id) { return state.text; }
            var $state = $(
             '<span><img src="' + baseURL + 'assets/global/img/flags/' + state.element.value.toLowerCase() + '.png" class="img-flag" /> ' + state.text + '</span>'
            );

            return $state;
        }

        if (jQuery().select2 && $('#country_list').size() > 0) {
            $("#country_list").select2({
	            placeholder: '<i class="fa fa-map-marker"></i>&nbsp;Select a Country',
	            templateResult: format,
                templateSelection: format,
                width: 'auto',
	            escapeMarkup: function(m) {
	                return m;
	            }
	        });


	        $('#country_list').change(function() {
	            $('.register-form').validate().element($(this)); //revalidate the chosen dropdown value and show error or success message for the input
	        });
    	}

        $('.register-form').validate({
            errorElement: 'span', //default input error message container
            errorClass: 'help-block', // default input error message class
            focusInvalid: false, // do not focus the last invalid input
            ignore: "",
            rules: {

                Name: {
                    required: true
                },
                Surname: {
                    required: true
                },
                Inst: {
                    required: true
                },
                Email: {
                    required: true,
                    email: true
                },
                Country: {
                    required: true
                },
                pass1: {
                    required: true
                },
                pass2: {
                    equalTo: "#register_password"
                },
            },

            messages: { // custom messages for radio buttons and checkboxes
                tnc: {
                    required: "Please accept TNC first."
                }
            },

            invalidHandler: function(event, validator) { //display error alert on form submit

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
                if (element.attr("name") == "tnc") { // insert checkbox errors after the container
                    error.insertAfter($('#register_tnc_error'));
                } else if (element.closest('.input-icon').size() === 1) {
                    error.insertAfter(element.closest('.input-icon'));
                } else {
                    error.insertAfter(element);
                }
            },

            submitHandler: function(form) {
				$('#register-submit-btn').prop('disabled', true);
				$('#register-submit-btn').html('Submitting...');
				
				$.ajax({
           			type: "POST",
           			url: baseURL + "applib/newUserFromSignin.php",
           			data: $('.register-form').serialize(), 


           			success: function(data) {
						d = data.replace(/(\r\n|\n|\r|\t)/gm,"");
               			if(d == '1'){
         					$('input#hidden-usermail-field').val($('input[name="Email"]').val());
							$('input#hidden-password-field').val($('input[name="pass1"]').val());
							form.submit();  
						}else{
							$('#err-msg-signup').fadeIn(300);
							$('#register-submit-btn').prop('disabled', false);
							$('#register-submit-btn').html('Submit');
						}
					}
         		});

            }
        });

        $('.register-form input').keypress(function(e) {
            if (e.which == 13) {
                if ($('.register-form').validate().form()) {
                    $('.register-form').submit();
                }
                return false;
            }
        });

        jQuery('#register-btn').click(function() {
            jQuery('.login-form').hide();
            jQuery('.register-form').show();
        });

        jQuery('#register-back-btn').click(function() {
            jQuery('.login-form').show();
            jQuery('.register-form').hide();
        });
    }

    return {
        //main function to initiate the module
        init: function() {

            handleLogin();
            handleForgetPassword();
            handleRegister();

        }

    };

}();

jQuery(document).ready(function() {
    Login.init();
});
