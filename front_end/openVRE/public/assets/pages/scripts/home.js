
var baseURL = $('#base-url').val();

function loadReferenceGenome() {
	$.ajax({
		type: "POST",
		url: baseURL + "applib/getReferenceGenomeList.php",
		data: "data=1", 
		success: function(data) {
			$("#ref-genome-jbrowse").html(data);
		}
	});
}

function changeGenome(op) {
	if(op.value != "") {
		window.open(baseURL + 'visualizers/jbrowse/?direct_refGenome=' + op.value, 'childWindow');
	}
}


function loadToolButtons(tool) {
	$.ajax({
		type: "POST",
		url: baseURL + "applib/getHomeButtons.php",
		data: "tool=" + tool, 
		success: function(data) {
	
			var cont_btns = '';

			$.each(JSON.parse(data), function(k,v) {
				cont_btns += '<a href="tools/' + tool + '/input.php?op=' + k + '"  class="cbp-l-inline-view btn blue btn-outline">' + v + '</a>';
			});		

			$("#btns-exc-tool").html(cont_btns);
		}
	});
}




$(document).ready(function() {

	//console.log($("#ref-genome-jbrowse"));

/*	$("#ref-genome-jbrowse").change(function() {

		console.log($(this).val());

		

	});	*/

});

