var baseURL = $('#base-url').val();
var isFirstTime = $('#is-first-time').val();

var CountdownToken = function() {

    return {
        //main function to initiate the module
        init: function() {

					var countDownDate = $('#exp-token').val()*1000;

					var iteration = 0;

					var x = setInterval(function() {

						//var now = new Date().getTime();
						var now = ($('#curr-time').val() - iteration)*1000;

						var distance = countDownDate - now;

						var minutes = "0" + Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
						var seconds = "0" + Math.floor((distance % (1000 * 60)) / 1000);

						var d = new Date(countDownDate);
						var h = "0" + d.getHours();
						var m = "0" + d.getMinutes();

						var formattedTime = h.substr(-2) + ':' + m.substr(-2) + ' CET (' + d.getDate() + '/' + (d.getMonth()+1) + '/' + d.getFullYear() + ')';

						$("#token-exp-date").val("Token will expire in " + minutes.substr(-2) + "m " + seconds.substr(-2) + "s, at " + formattedTime);

						if (distance < 0) {
							clearInterval(x);
							$("#token-exp-date").val("This Token is expired...  It needs a refresh!");
						}

						iteration --;

					}, 1000);

        }
    }

}();


var CountdownRefreshToken = function() {

    return {
        //main function to initiate the module
        init: function() {

					var countDownDate = $('#exp-refrtoken').val()*1000;

					var iteration = 0;

					var x = setInterval(function() {

						//var now = new Date().getTime();
						var now = ($('#curr-time').val() - iteration)*1000;


						var distance = countDownDate - now;

						var hours = "0" + Math.floor((distance % (1000 * 60 * 60 * 60)) / (1000 * 60 * 60));
						var minutes = "0" + Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
						var seconds = "0" + Math.floor((distance % (1000 * 60)) / 1000);

						var d = new Date(countDownDate);
						var h = "0" + d.getHours();
						var m = "0" + d.getMinutes();

						var formattedTime = h.substr(-2) + ':' + m.substr(-2) + ' CET (' + d.getDate() + '/' + (d.getMonth()+1) + '/' + d.getFullYear() + ')';

						$("#refrtoken-exp-date").val("Token will expire in " + hours.substr(-2) + "h " + minutes.substr(-2) + "m " + seconds.substr(-2) + "s, at " + formattedTime);

						if (distance < 0) {
							clearInterval(x);
							$("#refrtoken-exp-date").val("This Token is expired...  It needs a refresh!");
						}

						iteration --;

					}, 1000);

        }
    }

}();

var ComponentsClipboard = function() {

    return {
        //main function to initiate the module
        init: function() {
        	var paste_text;

        	$('.mt-clipboard').each(function(){
        		var clipboard = new Clipboard(this);	

        		clipboard.on('success', function(e) {
				    paste_text = e.text;
				    console.log(paste_text);
				});
        	});

        	$('.mt-clipboard').click(function(){
    			if($(this).data('clipboard-paste') == true){
    				if(paste_text){
        				var paste_target = $(this).data('paste-target');
        				$(paste_target).val(paste_text);
        				$(paste_target).html(paste_text);
        			} else {
        				alert('No text was copied or cut.');
        			}
        		} 
    		});
        }
    }

}();

var Profile = function () {

	var handleProfile = function() {

        $('#form-change-profile').validate({
            errorElement: 'span', //default input error message container
            errorClass: 'help-block', // default input error message class
            focusInvalid: false, // do not focus the last invalid input
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
				Country: {
					required: true
				},
				terms: {
					required:true
				},
            },

            messages: {
							terms: { required: "You must accept terms of use" }
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

							if($(element).attr("id") == "terms") {
									error.insertAfter(element.parent());
							}

            },

            submitHandler: function(form) {
				$('#submit-changes').prop('disabled', true);
				$('#submit-changes').html('Saving Changes...');
				
				$.ajax({
           			type: "POST",

           			url: baseURL + "applib/changeProfileData.php",
           			data: $('#form-change-profile').serialize(), 
           			success: function(data) {
					d = data.replace(/(\r\n|\n|\r|\t)/gm,"");
        	       			if(d == '1'){
               					$('#succ-chg-prf').fadeIn(300);
               					$('.profile-usertitle-name').html($('input[name="Name"]').val() + ' ' + $('input[name="Surname"]').val());
							$('.profile-usertitle-job').html($('input[name="Inst"]').val());
							$('.top-menu span.username').html($('input[name="Name"]').val());
							$('.top-menu #avatar-no-picture').html($('input[name="Name"]').val().slice(0,1) + $('input[name="Surname"]').val().slice(0,1));
							$('.profile-userpic #avatar-usr-profile').html($('input[name="Name"]').val().slice(0,1) + $('input[name="Surname"]').val().slice(0,1));
							if(isFirstTime == 1) location.href = baseURL + 'home';

					}else{
						$('#err-chg-prf').fadeIn(300);
					}
					$('#submit-changes').prop('disabled', false);
					$('#submit-changes').html('Save Changes');
				},
				error: function(data){
					$('#err-chg-prf').fadeIn(300);
					$('#submit-changes').prop('disabled', false);
					$('#submit-changes').html('Save Changes');
				}
         		});
         		
            },


        });


        $('#form-change-profile input').keypress(function(e) {
            if (e.which == 13) {
                if ($('#form-change-profile').validate().form()) {
                    $('#form-change-profile').submit(); //form validation success, call ajax form submit
                }
                return false;
            }
        });
    }


	var handlePassword = function() {

        $('#form-change-pwd').validate({
            errorElement: 'span', //default input error message container
            errorClass: 'help-block', // default input error message class
            focusInvalid: false, // do not focus the last invalid input
            rules: {
				oldpass: {
					required: true
				},
				pass1: {
                    required: true
                },
                pass2: {
                    equalTo: "#new-password"
                },

            },

            messages: {
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
				$('#submit-pwd').prop('disabled', true);
				$('#submit-pwd').html('Changing Password...');
				
				$.ajax({
           			type: "POST",
           			url: baseURL + "applib/changeProfilePassword.php",
           			data: $('#form-change-pwd').serialize(), 
           			success: function(data) {
						d = data.replace(/(\r\n|\n|\r|\t)/gm,"");
               			if(d == '1'){
               				$('#succ-chg-pwd').fadeIn(300);
							$('#err-chg-pwd2').fadeOut(300);
						}else{
							$('#err-chg-pwd2').fadeIn(300);	
							$('#succ-chg-pwd').fadeOut(300);
						}
						$('input[name="oldpass"]').val('');
						$('input[name="pass1"]').val('');
						$('input[name="pass2"]').val('');
						$('#submit-pwd').prop('disabled', false);
						$('#submit-pwd').html('Change Password');
					},
					error: function(data){
						$('#err-chg-pwd').fadeIn(300);
						$('#submit-pwd').prop('disabled', false);
						$('#submit-pwd').html('Change Password');
					}
         		});
         		
            }
        });

        $('#form-change-pwd input').keypress(function(e) {
            if (e.which == 13) {
                if ($('#form-change-pwd').validate().form()) {
                    $('#form-change-pwd').submit(); //form validation success, call ajax form submit
                }
                return false;
            }
        });
    }


    return {
        //main function to initiate the module
        init: function () {
        	handleProfile();
        	handlePassword();
        }

    };

}();

jQuery(document).ready(function() {
    Profile.init();
		ComponentsClipboard.init();
		CountdownToken.init();
		CountdownRefreshToken.init();

});


$('#submit-img').click(function(){
	var auxImg = $('.fileinput-preview.fileinput-exists.thumbnail img').attr('src');
	// fer les imatges i les inicials amb display-hide/display-show per posar i treure fàcil
		
    var formData = new FormData($('#form-chg-img')[0]);
    $.ajax({
        url: baseURL + 'applib/uploadAvatar.php',  //Server script to process data
        type: 'POST',
        success: function(data){
			console.log('success: ' + data);
			d = data.replace(/(\r\n|\n|\r|\t)/gm,"");
			switch(d) {
				case '0':$('#err-chg-av').html('Error uploading file.')
						$('#err-chg-av').fadeIn(300);
						$('#succ-chg-av').fadeOut(300);
					   	break;
				case '1':$('#succ-chg-av').fadeIn(300);
						$('#err-chg-av').fadeOut(300);
						$(".img-responsive").attr("src", auxImg);
						$(".img-responsive").removeClass('display-hide');
						$("#avatar-usr-profile").hide();
						$("#avatar-with-picture").attr("src", auxImg);
						$("#avatar-with-picture").removeClass('display-hide');
						$("#avatar-no-picture").hide();
					   	break;
				case '2':$('#err-chg-av').html('Maximum size exceeded. Max allowed size 1MB.')
						$('#err-chg-av').fadeIn(300);
						$('#succ-chg-av').fadeOut(300);
					   	break;
				case '3':$('#err-chg-av').html('Invalid format. Please try with a png or jpg image.')
						$('#err-chg-av').fadeIn(300);
						$('#succ-chg-av').fadeOut(300);
					   	break;
				case '4':$('#err-chg-av').html('You must provide a file.')
						$('#err-chg-av').fadeIn(300);
						$('#succ-chg-av').fadeOut(300);
					   	break;
				case '5':$('#succ-chg-av').html('Profile picture successfully removed.')
						$('#succ-chg-av').fadeIn(300);
						$('#err-chg-av').fadeOut(300);
						$(".img-responsive").hide();
						$("#avatar-usr-profile").show()
						$("#avatar-with-picture").hide();
						$("#avatar-no-picture").show();
					   	break;


			}
		},
        error: function(data){
			console.log('error: ' + data);
		},
        data: formData,
        cache: false,
        contentType: false,
        processData: false
    });
});
