var baseURL = $('#base-url').val();

var FormValidation = function () {

  $.validator.addMethod("regx", function(value, element, regexpr) {
    if(!value) return true;
    return regexpr.test(value);
  });

  var handleValidationForm = function() {

			var form = $('#newProject');
			//var error = $('.alert-form-error1', form);

			form.validate({
					errorElement: 'span', //default input error message container
					errorClass: 'help-block', // default input error message class
					focusInvalid: false, // do not focus the last invalid input
					ignore: [],
					rules: {
						pr_name: {
								required: true,
						},
						/*pr_ldesc: {
							required:true,
						},*/
						pr_keywords: {
              regx: /^[a-zA-Z0-9]{1,}(,[a-zA-Z0-9]{1,})*$/,
							//required: true,
						},
          },
          messages: {
            pr_keywords: {
              regx: "The format must be: keyword1,keyword2,keyword3 (only numbers and/or letters and no spaces between comma and keyword)"
            }
					},

					invalidHandler: function (event, validator) { //display error alert on form submit
							//error.show();
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

					success: function (label, e) {
						$(e).parent().removeClass('has-error');
						$(e).parent().parent().removeClass('has-error');
						$(e).parent().parent().parent().removeClass('has-error');

							/*label
									.closest('.form-group').removeClass('has-error'); // set success class to the control group*/
					},

					submitHandler: function (form) {
						//console.log($(form).serialize());
						location.href = baseURL + "applib/manageProjects.php?" + $(form).serialize();	
				}

			});

    }


  return {
  			//main function to initiate the module
  			init: function () {

  					handleValidationForm();

  			}

      };

  }();

  $(document).ready(function() {
      FormValidation.init();
  });
