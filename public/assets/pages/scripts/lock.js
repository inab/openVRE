var baseURL = $('#base-url').val();

var Lock = function () {

	var handleLock = function() {

        $('.lock-form').validate({
            errorElement: 'span', //default input error message container
            errorClass: 'help-block', // default input error message class
            focusInvalid: false, // do not focus the last invalid input
            rules: {
                password: {
                    required: true
                },
            },

            messages: {
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
           			data: $('.lock-form').serialize(), 
           			success: function(data) {
						d = data.replace(/(\r\n|\n|\r|\t)/gm,"");
               			if(d == '1'){
							form.submit();  
						}else{
							$('#err-lock-pwd').fadeIn(300);
							$('#login-button').prop('disabled', false);
							$('#login-button').html('Login');
						}
				}
         		});

            }
        });

        $('.lock-form input').keypress(function(e) {
            if (e.which == 13) {
                if ($('.lock-form').validate().form()) {
                    $('.lock-form').submit(); //form validation success, call ajax form submit
                }
                return false;
            }
        });
    }


    return {
        //main function to initiate the module
        init: function () {
        	handleLock();
        }

    };

}();

jQuery(document).ready(function() {
    Lock.init();
});

