//var baseURL = $('#base-url').val();

var SampleData = function() {

		$("#sampleData").change(function() {

			$(".sample-description").hide();
	
			$("#" + $(this).val()).show();

		});

    var handleSampleData = function() {

        $('#sampleDataForm').validate({
            errorElement: 'span', //default input error message container
            errorClass: 'help-block', // default input error message class
            focusInvalid: false, // do not focus the last invalid input

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

							$("#btn-sample").prop('disabled', true);	
							$("#btn-sample").html('<i class="fa fa-spinner fa-pulse fa-spin"></i> Importing example dataset, please don\'t close the tab.');

            	form.submit();

            }
        });

        $("#sampleData").rules("add", {
					required:true, 
					messages: {
						required:"You must select an example dataset from the list."
					}
				});

        $('#sampleDataForm input').keypress(function(e) {
            if (e.which == 13) {
                if ($('#sampleDataForm').validate().form()) {
                    $('#sampleDataForm').submit(); //form validation success, call ajax form submit
                }
                return false;
            }
        });
    }

    return {
        //main function to initiate the module
        init: function() {

           handleSampleData();

        }

    };

}();

jQuery(document).ready(function() {
    SampleData.init();
});
