var FormValidation = function () {
		
		var baseURL = $('#base-url').val();

    var handleValidationFormTxt = function() {

            var form = $('#uploadFromTxt');
            var error = $('.alert-form-error1', form);

            form.validate({
                errorElement: 'span', //default input error message container
                errorClass: 'help-block', // default input error message class
                focusInvalid: false, // do not focus the last invalid input
                ignore: "",
                messages: {
                  filename: {
                      required: "You must insert a File Name.",
                  },
                  txtdata: {
                      required: "You must insert your data."
                  }
								},
                rules: {
                    filename: {
                        required: true,
										},
                    txtdata: {
                        required: true,
                    }
                },

                invalidHandler: function (event, validator) { //display error alert on form submit
                    error.show();
                    //App.scrollTo(error, -200);
                },

                errorPlacement: function (error, element) { // render error placement for each input type
                    if (element.closest('.input-icon').size() === 1) {
                        error.insertAfter(element.closest('.input-icon'));
                    } else {
                        if($(element).parent().hasClass('btn-file')) {
                          error.insertAfter($(element).parent().parent().parent());
                        }else{
                          error.insertAfter(element);
                        }
                    }
                },

                highlight: function (element) { // hightlight error inputs

                    $(element)
                        .closest('.form-group').addClass('has-error'); // set error class to the control group
                },

                unhighlight: function (element) { // revert the change done by hightlight
                    $(element)
                        .closest('.form-group').removeClass('has-error'); // set error class to the control group
                },

                success: function (label) {
                    label
                        .closest('.form-group').removeClass('has-error'); // set success class to the control group
                },

                submitHandler: function (form) {

									$('.btn-send-data').hide();
									$('.progress-bar-file').show();
									$('#alert-down-form').fadeOut(300);

									$.ajax({
										type: "POST",
										url: baseURL + "applib/getData.php",
										data: $(form).serialize(), 
										success: function(data) {
		
											var output = data.split(/\n/);
											var d = output[output.length - 1];
											console.log(d);

											if(d.indexOf("ERROR") !== -1) {
						
												$('.btn-send-data').show();
												$('.progress-bar-file').hide();
												$('#alert-down-form').html(d);
												$('#alert-down-form').fadeIn(300);
					
											} else {
				
												$('.progress-bar-file .progress-bar').css('width', '100%');

												setTimeout(function(){ location.href = baseURL + "getdata/uploadForm2.php?fn[]=" + d; }, 500);	

											}		
	
										}
									});

								}

					});

			}

		var handleValidationFormID = function() {

            var form = $('#uploadFromID');
            var error = $('.alert-form-error1', form);

            form.validate({
                errorElement: 'span', //default input error message container
                errorClass: 'help-block', // default input error message class
                focusInvalid: false, // do not focus the last invalid input
                ignore: "",
                messages: {
                  databank: {
                      required: "Please, select first a Data Bank.",
                  },
                  idcode: {
                      required: "Please, select an ID code."
                  }
								},
                rules: {
                    databank: {
                        required: true,
										},
                    idcode: {
                        required: true,
												minlength: 4,
												maxlength: 4,
                    }
                },

                invalidHandler: function (event, validator) { //display error alert on form submit
                    error.show();
                    App.scrollTo(error, -200);
                },

                errorPlacement: function (error, element) { // render error placement for each input type
                    if (element.closest('.input-icon').size() === 1) {
                        error.insertAfter(element.closest('.input-icon'));
                    } else {
                        if($(element).parent().hasClass('btn-file')) {
                          error.insertAfter($(element).parent().parent().parent());
                        }else{
                          error.insertAfter(element);
                        }
                    }
                },

                highlight: function (element) { // hightlight error inputs

                    $(element)
                        .closest('.form-group').addClass('has-error'); // set error class to the control group
                },

                unhighlight: function (element) { // revert the change done by hightlight
                    $(element)
                        .closest('.form-group').removeClass('has-error'); // set error class to the control group
                },

                success: function (label) {
                    label
                        .closest('.form-group').removeClass('has-error'); // set success class to the control group
                },

                submitHandler: function (form) {

									$('#send_data').hide();
									$('.progress-bar-down').show();
									$('#alert-down-form').fadeOut(300);

									$.ajax({
										type: "POST",
										url: baseURL + "applib/getData.php",
										data: $(form).serialize(), 
										success: function(data) {
		
											var output = data.split(/\n/);
											var d = output[output.length - 1];
											console.log(d);

											if(d.indexOf("ERROR") !== -1) {
						
												$('#send_data').show();
												$('.progress-bar-down').hide();
												$('#alert-down-form').html(d);
												$('#alert-down-form').fadeIn(300);
					
											} else {
				
												$('.progress-bar-down .progress-bar').css('width', '100%');

												setTimeout(function(){ location.href = baseURL + "getdata/uploadForm2.php?fn[]=" + d; }, 500);	

											}		
	
										}
									});

								}

					});

			}


		return {
        //main function to initiate the module
        init: function() {

            handleValidationFormTxt();
						handleValidationFormID();

        }

    };	

}();

jQuery(document).ready(function() {
    FormValidation.init();
});

