var baseURL = $('#base-url').val();

var DataTableMyTools = function() {

	var handleDataTableMyTools = function() {

		var table = $('#my-new-tools');

		var oTable = table.dataTable({

				"lengthMenu": [
						[5, 15, 20, -1],
						[5, 15, 20, "All"] // change per page values here
				],

				"order": [
						[5, "desc"]
				],

				"columnDefs": [{
						'orderable': false,
						'targets': [0,1,2,3,4]
				}, 
				{ targets: [5], visible: false },
				],
			
		});

	}

	return {
        //main function to initiate the module
        init: function() {
            handleDataTableMyTools();
        }

    };


}();

var SubmitTool = function() {

	var handleSubmitTool = function() {

		$('#submit-tool').validate({
            errorElement: 'span', //default input error message container
            errorClass: 'help-block', // default input error message class
            focusInvalid: false, // do not focus the last invalid input
            ignore: [],
            rules: {
							comments:{
                    required: true
                }
            },
						messages: {
							
						},

            highlight: function(element) { // hightlight error inputs
                $(element)
                    .closest('.form-group').addClass('has-error'); // set error class to the control group
            },

            success: function(label, e) {
                $(e).parent().removeClass('has-error');
                $(e).parent().parent().removeClass('has-error');
                $(e).parent().parent().parent().removeClass('has-error');
            },

            errorPlacement: function(error, element) {
            	if($(element).hasClass("select2-hidden-accessible")) {
            		console.log($(element).parent());
            		error.insertAfter($(element).parent().find("span.select2"));
							} else {
								error.insertAfter(element);
							}
						},

            submitHandler: function(form) {
                  /*var data = $('#submit-tool').serialize();
                	console.log(data);*/
							form.submit();
            }
        });

	}

	return {
        //main function to initiate the module
        init: function() {
            handleSubmitTool();
        }

    };


}();

var UploadLogo = function() {

	var handleUploadLogo = function() {

		$("input:file").change(function (){
			var toolid = $(this).attr('id').substr(10);
			$("#uplogo_" + toolid).submit();
     });

	}

	return {
        //main function to initiate the module
        init: function() {
            handleUploadLogo();
        }

    };


}();

function submitTool(toolid) {

	$("#toolid-modal").val(toolid);

	$('#modalSubmitTool').modal({ show: 'true' });
	$('#modalSubmitTool .modal-title').html('Submit Tool <strong>' + toolid + '</strong>');
	$('#modalSubmitTool .modal-body #st-title').html('You are about to submit the <strong>' + toolid + '</strong> tool, please fill the comments to send a message to our technical team');

}

function removeTool(toolid) {

	//$("#toolid-modal").val(toolid);

	$('#modalRemoveTool').modal({ show: 'true' });
	$('#modalRemoveTool .modal-title').html('Remove Tool <strong>' + toolid + '</strong>');
	$('#modalRemoveTool .modal-body').html('Are you sure you want to remove permanently the <strong>' + toolid + '</strong> tool? This action cannot be undone');

	$("#btn-rmv-tool").attr("href", "applib/deleteToolDev.php?toolid=" + toolid);

}

$(document).ready(function() {

	DataTableMyTools.init();
	SubmitTool.init();
	UploadLogo.init();

});
