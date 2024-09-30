var baseURL = $('#base-url').val();

var FormDownRemoteFile = function () {

	 return {
        //main function to initiate the module
        init: function () { 
			
			$('.down-form').validate({
            	errorElement: 'span', //default input error message container
            	errorClass: 'help-block', // default input error message class
            	focusInvalid: false, // do not focus the last invalid input
            	rules: {
                	url: {
                    	required: true,
						url: true
                	},
            	},

            	messages: {
                	url: {
                    	required: "Please insert an url."
                	}
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
					
								$('#btn-down-remote').hide();
								$('.progress-bar-down').show();
								$('#alert-down-form2').fadeOut(300);
							
								//console.log($('.down-form').serialize());
					
								location.href = baseURL + "applib/getData.php?uploadType=" + $("#dfUploadType").val() + "&url=" + $("#dfUrl").val();

								/*$.ajax({
									type: "POST",
									url: baseURL + "applib/getData.php",
									data: $('.down-form').serialize(),
									xhrFields: {
										onprogress: function(e) {
											var output = e.target.responseText.split(/\n/);
											//console.log(output);
											$('.progress-bar-down .progress-bar').css('width', output[output.length - 2] + '%');
										}
									},
									success: function(data) {
										var output = data.split(/\n/);
										var d = output[output.length - 1];
										console.log(d);

										if(d.indexOf("ERROR") !== -1) {

											$('#btn-down-remote').show();
											$('.progress-bar-down').hide();
											$('#alert-down-form2').html(d);
											$('#alert-down-form2').fadeIn(300);
				
										} else {
			
											$('.progress-bar-down .progress-bar').css('width', '100%');

											setTimeout(function(){ location.href = baseURL + "getdata/uploadForm2.php?fn[]=" + d; }, 500);	

										}	

									}
								});*/

            	}

        	});
		}
	}

}();

jQuery(document).ready(function() {    
   FormDownRemoteFile.init();
});
