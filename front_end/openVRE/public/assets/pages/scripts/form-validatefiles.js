var baseURL = $('#base-url0').val();

var current_block = 0;
var feedback_from_file = [];

function showValidation(op) {
  var block = op.value;
  $("#summary" + current_block).fadeOut(300);
  $("#alert-message" + current_block).fadeOut(300);
  $('#feedback-file' + current_block).fadeOut(300);
  $("#formInputs" + current_block).fadeOut(300, function(){
  	$("#formInputs" + block).fadeIn(300);
  	$("#summary" + block).fadeIn(300);
  	$("#alert-message" + block).fadeIn(300);
	if(feedback_from_file.indexOf(parseInt(block)) != -1)$('#feedback-file' + block).fadeIn(300);
  	current_block = block;
  });

}

function formBlockState(state, block, input_type, id){
	if(state == 'disabled'){
		$(block + id).fadeOut();
	 	$(block + id + " " + input_type).prop('disabled', true);
	}else{
		$(block + id).fadeIn();
	 	$(block + id + " " + input_type).prop('disabled', false);
	}
}

function customfromFormat(op, id){

  var filetype = op;

	$('#refGenome' + id).prop("disabled", true);
	$('#refGenomeTR' + id).hide();

	$('#taxonG' + id).hide();
	$('#taxonName' + id).prop("disabled", true);
	$('#taxonName' + id).val('');
	$('#taxonID' + id).prop("disabled", true);
	$('#taxonID' + id).val('');

	//if($('#have_taxon_id').val() != 1) $('input[name="taxon_id"]').val('');

	$('.paired' + id).prop("disabled", true);
	$('#pairedTR' + id).hide();

	$('.sorted' + id).prop("disabled", true);
	$('#sortedTR' + id).hide();

	// call ajax with op and load datatype select 

	$.ajax({
			type: "POST",
			url: baseURL + "applib/getDataTypes.php",
			data: "filetype=" + filetype, 
			success: function(data) {
				var obj = JSON.parse(data);
				obj_len = Object.keys(obj).length;

				if(obj_len > 0) {

					var select = '<option value="">Select the data type</option>';

					$.each( obj, function( key, value ) {

							select += '<option';
							$.each( value, function( k, v ) {

								if(k == '_id') select += ' value="' + v + '">';
								if(k == 'name') select += v;

							});

							select += '</option>';

					});

					$('#data_type_sel' + id).prop("disabled", false);
					$('#data_type_sel' + id).html(select);

					if($('#data_type_selected').length) {

						$('#data_type_sel' + id).find('option[value="' + $('#data_type_selected').val() + '"]').attr("selected",true).trigger('change');

					}

					$('#dataType' + id).show();

				} else {

					$('#data_type_sel' + id).prop("disabled", true);
					$('#dataType' + id).hide();

				}			
	
			}
		});
	

	 /*if (format == "BAM"){
	formBlockState('enabled', '#pairedTR', 'input', id);
	formBlockState('enabled', '#sortedTR', 'input', id);
    if($("#sortedTR" + id + " input[type='radio']:checked").val() == 'unsorted') $("#sortInfo" + id).fadeIn();
  }else{
    formBlockState('disabled', '#pairedTR', 'input', id);
	formBlockState('disabled', '#sortedTR', 'input', id);
    $("#sortInfo" + id).fadeOut();
  }*/


  //if (format == "UNK" || format == "FASTA" /*|| format == "FASTQ"*/ || format == "TXT" || format == "PDB" || format == "DCD" || format == "GRO" || format == "JSON" || format == "GEM" || format == "PARMTOP" || format == "MDCRD"){
  /*if (format == "UNK" || format == "TXT" || format == "PDF"){
	formBlockState('disabled', '#refGenomeTR', 'select', id);
  }else{
	formBlockState('enabled', '#refGenomeTR', 'select', id);
  }*/

  /*if (format == "WIG" || format == "BEDGRAPH"){
    $("#formatInfo" + id).fadeIn();
  }else{
  	$("#formatInfo" + id).fadeOut();
  }*/

}

function customfromDataType(op, id){

  var datatype = op;

	if(datatype == "") {

		$('#refGenome' + id).prop("disabled", true);
		$('#refGenomeTR' + id).hide();

		$('#taxonG' + id).hide();
		$('#taxonName' + id).prop("disabled", true);
		$('#taxonName' + id).val('');
		$('#taxonID' + id).prop("disabled", true);
		$('#taxonID' + id).val('');
		$('input[name="taxon_id"]').val('');

		$('.paired' + id).prop("disabled", true);
		$('#pairedTR' + id).hide();

		$('.sorted' + id).prop("disabled", true);
		$('#sortedTR' + id).hide();

		return;		

	}
 
	// call ajax with op and load taxon ID and/or Assembly and/or paired / sorted (BAM) 

	$.ajax({
			type: "POST",
			url: baseURL + "applib/getDataTypeFeatures.php",
			data: "datatype=" + datatype + "&filetype=" + $('#format' + id).val(), 
			success: function(data) {
				var obj = JSON.parse(data);

				//console.log(obj);

				if(obj.assembly) {
					
					$('#refGenome' + id).prop("disabled", false);
					$('#refGenomeTR' + id).show();

				}else{

					$('#refGenome' + id).prop("disabled", true);
					$('#refGenomeTR' + id).hide();
				
				}

				if(obj.taxon_id) {
					
					$('#taxonG' + id).show();
					$('#taxonName' + id).prop("disabled", false);

				}else{

					$('#taxonG' + id).hide();
					$('#taxonName' + id).prop("disabled", true);
					$('#taxonName' + id).val('');
					$('#taxonID' + id).prop("disabled", true);
					$('#taxonID' + id).val('');
					$('input[name="taxon_id"]').val('');
				
				}

				if(obj.paired) {
					
					$('.paired' + id).prop("disabled", false);
					$('#pairedTR' + id).show();

				}else{

					$('.paired' + id).prop("disabled", true);
					$('#pairedTR' + id).hide();
				
				}

				if(obj.sorted) {
					
					$('.sorted' + id).prop("disabled", false);
					$('#sortedTR' + id).show();

				}else{

					$('.sorted' + id).prop("disabled", true);
					$('#sortedTR' + id).hide();
				
				}


							
				/*var obj = JSON.parse(data);
				obj_len = Object.keys(obj).length;

				if(obj_len > 0) {

					var select = '<option value="">Select the data type</option>';

					$.each( obj, function( key, value ) {

							select += '<option';
							$.each( value, function( k, v ) {

								if(k == '_id') select += ' value="' + v + '">';
								if(k == 'name') select += v;

							});

							select += '</option>';

					});

					$('#data_type_sel' + id).prop("disabled", false);
					$('#data_type_sel' + id).html(select);
					$('#dataType' + id).show();

				} else {

					$('#data_type_sel' + id).prop("disabled", true);
					$('#dataType' + id).hide();

				}		*/	
	
			}
		});

}


function showHideSortInfo(op, id){

  if(op == 1){
    	$("#sortInfo" + id).fadeIn();
	}else{
		$("#sortInfo" + id).fadeOut();
	}

}

function checkIfAllValidated(){
	$('#myModal1').modal('show');
}

Array.prototype.remove = function() {
    var what, a = arguments, L = a.length, ax;
    while (L && this.length) {
        what = a[--L];
        while ((ax = this.indexOf(what)) !== -1) {
            this.splice(ax, 1);
        }
    }
    return this;
};


//var titleFeedbackBox = ['ERROR', 'SUMMARY', 'SUCCESS', 'INFO'];
var titleFeedbackBox = ['ERROR', 'SUCCESS', 'SUMMARY' , 'INFO'];
//var stateLabel = ['ERROR', 'READY', 'VALIDATED', 'PROCESSING'];
var stateLabel = ['ERROR', 'VALIDATED', 'READY' , 'PROCESSING'];

function showProcessValidation(obj, id) {
	
	feedback_from_file.push(id);
	
	// show message
	$('#feedback-file' + id + ' h4').html(titleFeedbackBox[obj.state]);	
	$('#feedback-file' + id + ' span').html(obj.msg);
	for(var k in fileMessageColor) $('#feedback-file' + id).removeClass(fileMessageColor[k]);
	$('#feedback-file' + id).addClass(fileMessageColor[obj.state]);
	$('#feedback-file' + id).show();

	// change state
	for(var k in fileStateColor) $('#file' + id  + '-state').removeClass(fileStateColor[k]);
	$('#file' + id  + '-state').addClass(fileStateColor[obj.state]);
	$('#file' + id  + '-state').html(stateLabel[obj.state]);


	console.log(obj.state);
	
	switch(obj.state){
		case 0: // enable send button
				$('#formInputs' + id + ' .disable-form').fadeOut();
				$('#formInputs' + id + ' .btn-send-data').html(
					//'<input type="button" class="btn green snd-metadata-btn" value="SEND METADATA" onclick="sendMetadata(' + id  + ', 1);" style="position:relative;z-index:20;" >'	
					'<input type="submit" class="btn green snd-metadata-btn" value="SEND METADATA AGAIN" style="position:relative;z-index:20;" >'
				);
				$("#op" + id ).val('1');
				break;
		case 2: // change send metadata button for validate button
				$('#formInputs' + id + ' .btn-send-data').html(
					//'<input type="button" class="btn green val-metadata-btn" value="VALIDATE METADATA" onclick="sendMetadata(' + id  + ', 2);" style="position:relative;z-index:20;" >'	
					'<input type="submit" class="btn green val-metadata-btn" value="ACCEPT CHANGES" style="position:relative;z-index:20;" >'
				);
				$("#op" + id ).val('2');
				$('#formInputs' + id + ' .disable-form').fadeIn();
				break;
		case 1: // disable all buttons and form
				$('#formInputs' + id + ' .val-metadata-btn').fadeOut(200);
				$('#formInputs' + id + ' .snd-metadata-btn').fadeOut(200);
				$('#formInputs' + id + ' .disable-form').fadeIn();
				$("#op" + id ).val('1');
				totalNumBlocks --;
				break;
		case 3: // disable all buttons and form
				$('#formInputs' + id + ' .val-metadata-btn').fadeOut(200);
				$('#formInputs' + id + ' .disable-form').fadeIn();
				$("#op" + id ).val('1');
				totalNumBlocks --;
				break;

	}

	if(totalNumBlocks == 0) {
		$('#bottom-no-validated-files').hide();
		$('#bottom-validated-files').show();
	}

	arrayFiles.remove(parseInt(id));

	if((obj.state != 2) && (obj.state != 0)){

		if(arrayFiles.length > 0) {

			var newIDX = arrayFiles[0];

			$("input[name=idx][value=" + newIDX + "]").prop('checked', true);

			showValidation(document.getElementsByName("idx")[newIDX]);

		} else {

			location.href= baseURL + 'workspace/';

		}

	}

}



/*function sendMetadata(id, op) {

	// mandatory field ref genome not completed
	if(($('#data_type_sel' + id).val() == '') && !$('#data_type_sel' + id).prop('disabled')) {

		console.log(id);
		
		$('#data_type_sel' + id + ' .warn1').show();
		$('#data_type_sel' + id).css('border-color', '#e73d4a');		
	
	}else{
		
		// clean error messages
		$('#refGenomeTR' + id + ' .warn-ref-gen').hide();
		$('#refGenomeTR' + id + ' select').css('border-color', '#c2cad8');	
	
		// disable send / validate button
		if(op == 1) {
			$('#formInputs' + id + ' .snd-metadata-btn').prop('disabled', true);
			$('#formInputs' + id + ' .snd-metadata-btn').val('SENDING METADATA...');
		}else{
			$('#formInputs' + id + ' .val-metadata-btn').prop('disabled', true);
			$('#formInputs' + id + ' .val-metadata-btn').val('VALIDATING METADATA...');
		}

		// generate query
		data = $('#uploadFiles #formInputs' + id + ' input, #uploadFiles #formInputs' + id + ' select, #uploadFiles #formInputs' + id + ' textarea').serialize() + '&op=' + op;
		var re1 = new RegExp("paired" + id, "g");
		data = data.replace(re1, 'paired');
		var re2 = new RegExp("sorted" + id, "g");
		data = data.replace(re2, 'sorted');

		console.log(data);

		$.ajax({
			type: "POST",
			url: baseURL + "applib/processValidation.php",
			data: data, 
			success: function(data) {
				d = data.replace(/(\r\n|\n|\r|\t)/gm,"");
				var json = JSON.parse(d);
				showProcessValidation(json, id);
			}
		});

	}
	
}*/



/*sendMetadata = function(id, op) {

	var data = $('#uploadFiles #formInputs' + id).serialize();
		  						//location.href = baseURL + "applib/launchTool.php?" + data;
                	console.log(data);

}*/


var ValidateForm = function() {

    var handleForm = function() {

        $.each($('.uploadFiles'), function() {

      	//$('.uploadFiles').validate({
				$(this).validate({
            errorElement: 'span', //default input error message container
            errorClass: 'help-block', // default input error message class
            focusInvalid: false, // do not focus the last invalid input
            ignore: [],
            rules: {
                data_type: {
                    required: true
                },
                format: {
                    required: true
                },

								taxon_name_id: {
                    required: true
                },
								taxon_id_name: {
                    required: true
                },
								refGenome: {
                    required: true
                }

            },
						/*messages: {
							data_type: {
								required: "The data type name is mandatory."
							}
						},*/


            invalidHandler: function(event, validator) { //display error alert on form submit
                $('.err-nd', $('#uploadFiles' + formSelected)).show();
                $('.warn-nd', $('#uploadFiles' + formSelected)).hide();
            },

            highlight: function(element) { // hightlight error inputs
                $(element)
                    .closest('.form-group').addClass('has-error'); // set error class to the control group
            },

            success: function(label, e) {
                $(e).parent().removeClass('has-error');
                /*$(e).parent().parent().removeClass('has-error');
                $(e).parent().parent().parent().removeClass('has-error');*/
            },

            errorPlacement: function(error, element) {

							if((element.attr("name") === 'taxon_name_id') || (element.attr("name") === 'taxon_id_name')){
								error.insertAfter($("#label-taxon" + formSelected));
							} else {
								error.insertAfter(element);
							}
						},

            submitHandler: function(form) {


							//var formSelected = $("#idx").val();

							if($("#op").val() == 1) {
								$('#formInputs' + formSelected + ' .snd-metadata-btn').prop('disabled', true);
								$('#formInputs' + formSelected + ' .snd-metadata-btn').val('SENDING METADATA...');
							}else{
								$('#formInputs' + formSelected + ' .val-metadata-btn').prop('disabled', true);
								$('#formInputs' + formSelected + ' .val-metadata-btn').val('VALIDATING METADATA...');
							}

							//console.log($(".snd-metadata-btn"));

							if($('input[name="taxon_id"]').val() == "") console.log($("#taxonName").val())  

             	var data = $('#uploadFiles' + formSelected).serialize();
							data = data.replace(/%5B/g,"[");
              data = data.replace(/%5D/g,"]");
              //console.log(data);

							$.ajax({
								type: "POST",
								url: baseURL + "applib/processValidation.php",
								data: data, 
								success: function(data) {
									d = data.replace(/(\r\n|\n|\r|\t)/gm,"");
									var json = JSON.parse(d);
									showProcessValidation(json, formSelected);
								}
							});

            }
        });

				});

		}

    return {
        //main function to initiate the module
        init: function() {
            handleForm();
        }

    };

}();



var totalNumBlocks = 0;

var formSelected = 0;

var arrayFiles = [];

jQuery(document).ready(function() {
	// force to load first form properly
	$('#formInputs0').fadeIn();
	$('.formInputs').each(function(index) {
		var val = $(this).find('.formatSelector option:selected').val();
		customfromFormat(val, index);
		totalNumBlocks++;
	});

	$('#myModal1').on('click', '.btn-modal-ok', function(e) {
		location.href = baseURL + 'workspace/';
	});

	/*$(".snd-metadata-btn").click(function() {
		
		formSelected = $(this).attr("id");

	});*/

	for(i = 0; i < $("#numFiles").val(); i ++) arrayFiles.push(i);


	 ValidateForm.init();


		$('.snd-metadata-btn').on('click', function(){  // capture the click   

			var id = $(this).attr("id").substr(3,5);

			formSelected = id;
			//console.log($('#uploadFiles' + id));
        
	/*		$("#data_type_sel" + id).rules("add", { required:true });
			$("#taxonName" + id).rules("add", { required:true });
			$("#taxonID" + id).rules("add", { required:true });
			$("#refGenome" + id).rules("add", { required:true });*/

			//$('#uploadFiles' + id).valid();

			$('#uploadFiles' + id).submit();


			//console.log($("#data_type_sel" + id));

		//	$('#uploadFiles').submit();
  
    }); 

		$(".data-type-selector").select2({
			placeholder: "Select data type",
			width: '100%',
			minimumResultsForSearch: 1
		});

		$(".file-type-selector").select2({
			placeholder: "Select file type",
			width: '100%',
			minimumResultsForSearch: 1
		});

});
