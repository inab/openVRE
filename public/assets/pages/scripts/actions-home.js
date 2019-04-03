// delete files / folders
var fileName = '';
var projectName = '';
var option = '';
var intJobStatus;
var baseURL = $('#base-url').val();

var intervalProgress;

var globPID;
var globFileName;

function deleteProject(project, name){

  $.ajax({
	type: "GET",
	url: baseURL + "applib/manageProjects.php",
	data: "op=deleteMsg&pr_id=" + project, 
	success: function(data) {
		$('#modalDeleteProject').modal({ show: 'true' });
			$('#modalDeleteProject .modal-body').html("<p>Are you sure you want to delete the project <strong>" + name + "</strong> " + 
			"and <strong>ALL</strong> its executions and files? This action cannot be undone. List of executions you will remove:</p>" + 
			data);
		}
	});
	projectName = project;
}

function deleteFile(file){
  $('#modalDelete .modal-body').html('Are you sure you want to delete the selected file?');
  $('#modalDelete').modal({ show: 'true' });
  fileName = file;
  option = 'deleteSure';
}

function deleteFolder(folder){
  $('#modalDelete .modal-body').html('Are you sure you want to delete the selected folder and <strong>ALL</strong> its content?');
  $('#modalDelete').modal({ show: 'true' });
  fileName = folder;
  option = 'deleteDirOk';
}

function deleteAllFiles(){
  $('#modalDelete .modal-body').html('Are you sure you want to delete <strong>ALL</strong> the selected files?');
  $('#modalDelete').modal({ show: 'true' });
  option = 'deleteAll';
}

function downloadAllFiles(){
	option = 'downloadAll';
	var fn = "&";
	for(i in allFiles){
		if(allFiles[i].checked) {
			fn += 'fn[]=' + allFiles[i].fileId + '&';
		}
	}
	fn = fn.slice(0, -1);

	location.href= baseURL + "workspace/workspace.php?op=" + option + fn;

}

var pathRename;
var fileRenameID;
var typeRename;

function rename(file){

	$('#modalRename .modal-body .alert-danger').hide();
	$('#modalRename .modal-body .alert-danger').html('');

	$('#modalRename').modal({ show: 'true' });
	fileRenameID = file;

	$.ajax({
		type: "POST",
		url: baseURL + "applib/getDataMove.php",
		data: "id=" + file + "&op=rename", 
		success: function(data) {
			var obj = JSON.parse(data);	
			pathRename = obj.path.replace(/\//g, "%2F") + "%2F";
			typeRename = obj.type;
			$('#modalRename .modal-title').html('Rename <strong>' + obj.name + '</strong>');
			$('#modalRename .modal-body #loading-rename').hide();
			$('#modalRename .modal-body #form-rename input#new-name').val(obj.name);
			$('#modalRename .modal-body #form-rename').show();
		}
	});
}

var pathMoveFile;
var pathMoveDir;
var fileMoveID;
var typeMove;
var usrPrjStr;

function move(file){

	$('#modalMove .modal-body .alert-danger').hide();
	$('#modalMove .modal-body .alert-danger').html('');
	$('#modalMove').modal({ show: 'true' });
	fileMoveID = file;

	$("#project-name").html('');
	$("#execution-name").html('');

	$.ajax({
		type: "POST",
		url: baseURL + "applib/getDataMove.php",
		data: "id=" + file + "&op=move", 
		success: function(data) {
			usrPrjStr = JSON.parse(data);	
			$('#modalMove .modal-title').html('Move <strong>' + usrPrjStr.name + '</strong>');
			if(usrPrjStr.type == "file") {
				$('#modalMove .modal-body p').html('File <strong>'+usrPrjStr.name+'</strong> currently located at <strong>/'+usrPrjStr.project+'/'+usrPrjStr.execution+'/</strong>');
				$('#col-1-move').addClass('col-md-3');
				$('#col-1-move').removeClass('col-md-4');
				$('#col-2-move').addClass('col-md-3');
				$('#col-2-move').removeClass('col-md-4');
				$('#col-3-move').addClass('col-md-3');
				$('#col-3-move').removeClass('col-md-4');
				$('#col-4-move').addClass('col-md-3');
				$('#col-4-move').removeClass('col-md-4');
				$('#col-3-move label').html('File name');
				$('#col-2-move').show();
				$('#col-3-move').show();
			} else {
				$('#modalMove .modal-body p').html('Execution <strong>' +usrPrjStr.name+ '</strong> currently located at <strong>/' +usrPrjStr.project+ '/</strong>');
				$('#col-1-move').addClass('col-md-4');
				$('#col-1-move').removeClass('col-md-3');
				$('#col-2-move').addClass('col-md-4');
				$('#col-2-move').removeClass('col-md-3');
				$('#col-3-move').addClass('col-md-4');
				$('#col-3-move').removeClass('col-md-3');
				$('#col-4-move').addClass('col-md-4');
				$('#col-4-move').removeClass('col-md-3');
				$('#col-3-move label').html('Execution name');
				$('#col-2-move').hide();
				$('#col-3-move').show();
			}
			$.each(usrPrjStr.projects, function(k, v) {
				
				if(v.name == usrPrjStr.project) var sel = "selected";
				$("#project-name").append('<option value="' + v.id + '" ' + sel + '>' + v.name + '</option>');
				if(v.name == usrPrjStr.project) {
					$.each(v.executions, function(k1, v1) {
						if(v1.name == usrPrjStr.execution) var sel = "selected";
						$("#execution-name").append('<option value="' + v1.id + '" ' + sel + '>' + v1.name + '</option>');
					});
				}
			});
			$('#modalMove .modal-body #move-file #new-name-move-file').val(usrPrjStr.name);
			$('#modalMove .modal-body #loading-move').hide();
			$('#modalMove .modal-body #move-file').show();

			typeMove = usrPrjStr.type;
			
		}
	});
}

var filesMoveIDs = '';

function moveAllFiles(){

	$('#modalMove .modal-body .alert-danger').hide();
	$('#modalMove .modal-body .alert-danger').html('');


	$('#modalMove').modal({ show: 'true' });
	$("#project-name").html('');
	$("#execution-name").html('');

	$.ajax({
		type: "POST",
		url: baseURL + "applib/getDataMove.php",
		data: "id=1&op=move", 
		success: function(data) {

			$('#modalMove .modal-title').html('Move selected files');
			$('#modalMove .modal-body p').html('Select the project and execution where you want to move all the selected files:');
			$('#col-1-move').addClass('col-md-4');
			$('#col-1-move').removeClass('col-md-3');
			$('#col-2-move').addClass('col-md-4');
			$('#col-2-move').removeClass('col-md-3');
			$('#col-4-move').addClass('col-md-4');
			$('#col-4-move').removeClass('col-md-3');
			$('#col-3-move').hide();

			usrPrjStr = JSON.parse(data);

			$.each(usrPrjStr.projects, function(k, v) {
				
				$("#project-name").append('<option value="' + v.id + '">' + v.name + '</option>');

				if(k == 0) {

					$.each(v.executions, function(k1, v1) {
						$("#execution-name").append('<option value="' + v1.id + '">' + v1.name + '</option>');
					});
	
				}

			});
				
			$('#modalMove .modal-body #move-file #new-name-move-file').val(usrPrjStr.name);

			$('#modalMove .modal-body #loading-move').hide();
			$('#modalMove .modal-body #move-file').show();

			//typeMove = usrPrjStr.type;

		}
	});

	for(i in allFiles){
		if(allFiles[i].checked) {
			filesMoveIDs += 'fn[]=' + allFiles[i].fileId + '&';
		}
	}
	filesMoveIDs = filesMoveIDs.slice(0, -1);

}


function getProgress() {

	$.ajax({
		type: "POST",
		url: baseURL + "applib/getProgress.php",
		data: "pid=" + globPID, 
		success: function(data) {
		
			console.log(data);
	
			var obj = JSON.parse(data);

			$('#modalProgress .modal-body #meta-progress').html(obj.progress);
			$('#modalProgress .modal-body #meta-log').html(obj.log);
			
			//

			//if(!($("modalProgress").data('bs.modal') || {}).isShown) $('#modalProgress').modal({ show: 'true' });

		}
	});

}

function cancelJob(op, id) {

	App.blockUI({
		boxed: true,
		message: 'Cancelling job, please wait a few seconds.'
	});

	if(op == 'cancelJobDirSure') {
		location.href = 'workspace/workspace.php?op=' + op + '&fn=' +  id;
	} else if(op == 'cancelJobSure') {
		location.href = 'workspace/workspace.php?op=' + op + '&pid=' +  id;	
	}

}

function viewProgress(pid, name, type){

	globPID = pid;
	globFileName = name;

	$('#modalProgress .modal-header .modal-title').html('');
	$('#modalProgress .modal-body #meta-progress').html('');

	switch(type) {

		case 'PENDING': $('#modalProgress .modal-header .modal-title').html(globFileName.toUpperCase() + ' <span>Job Pending</span>');
										$('#modalProgress .modal-body #meta-progress').html('The Job has not yet entered the queue, please be patient.');
										$("#btn-modal-progress").prop("disabled", true);
										$("#btn-modal-log").prop("disabled", true);
										$('#modalProgress').modal({ show: 'true' });
										break;

		case 'FINISHING': $('#modalProgress .modal-header .modal-title').html(globFileName.toUpperCase() + ' <span>Job Finishing</span>');
											$('#modalProgress .modal-body #meta-progress').html('The Job is about to finish, in brief the results will be shown.');
											$("#btn-modal-progress").prop("disabled", true);
											$("#btn-modal-log").prop("disabled", true);
											$('#modalProgress').modal({ show: 'true' });
											break;

		case 'RUNNING': $('#modalProgress .modal-header .modal-title').html(globFileName.toUpperCase() + ' <span>Progress</span>');
										$("#btn-modal-progress").prop("disabled", false);
										$("#btn-modal-log").prop("disabled", false);
										// ************************************
$('#modalProgress .modal-body #meta-progress').html('<div  style=\"text-align:left;\">'+
			'<ul class=\"progress-tracker progress-tracker--vertical\">'+
			'<li class=\"progress-step is-complete\">'+
				'<span class=\"progress-marker progress-progress\">'+
					'<i class=\"fa fa-spinner fa-spin fa-fw\" aria-hidden=\"true\"></i>'+
				'</span>'+
				'<span class=\"progress-text bold progress-msg-progress\">'+
					'Loading log...'+
				'</span>'+
			'</li>');
										$('#modalProgress').modal({ show: 'true' });
										intervalProgress = setInterval( function() { getProgress(); }, 1000 );
										// ************************************
										//getProgress();
										break;

	}

}

function viewFileMeta(id, name, type){

	$('#modalMeta .modal-header .modal-title').html('');
	$('#modalMeta .modal-body #meta-summary').html('');
	$('#modalMeta .modal-footer #btMeta').remove();

	var txtID = '';
	/*if(type == 1) var txtID = 'File';
	else  var txtID = 'Job';*/

	$.ajax({
		type: "POST",
		url: baseURL + "applib/getMetaWS.php",
		data: "id=" + id + "&type=" + type, 
		success: function(data) {
			$('#modalMeta .modal-header .modal-title').html(name.toUpperCase() + ' ' + txtID + ' Info');
			$('#modalMeta .modal-body #meta-summary').html(data);
			$(".tooltips").tooltip();

			if(($("#modalMeta #btMeta").length == 0) && (type == 1)) $('#modalMeta .modal-footer').prepend('<a id="btMeta" style="float:left;" href="getdata/editFile.php?fn[]=' + id + '" class="btn green">Edit Metadata</a>');

			$('#modalMeta').modal({ show: 'true' });

		}
	});

}

// Open modal with analysis parameters
callShowSHfile = function(tool, sh) {

	$('#modalAnalysis').modal('show');
	$('#modalAnalysis .modal-body').html('Loading data...');

	$.ajax({
		type: "POST",
		url: baseURL + "applib/showSHfile.php",
		data: "fn=" + sh + "&tool=" + tool, 
		success: function(data) {
			$('#modalAnalysis .modal-body').html(data);
		}
	});

}

toggleVis = function(layer) {
	$('#' + layer).slideToggle();
}


runTool = function(tool) {
	var query = "";
	for(i in allFiles){
		if(allFiles[i].checked) {
			query += 'fn[]=' + allFiles[i].fileId + '&';
		}
	} 
	query = query.slice(0, -1);
	location.href = baseURL + "tools/" + tool + "/input.php?" + query;
}

runVisualizer = function(tool, user) {
	var query = "user=" + user + "&";
	for(i in allFiles){
		if(allFiles[i].checked) {
			query += 'fn[]=' + allFiles[i].fileId + '&';
		}
	} 
	query = query.slice(0, -1);

	var target = (tool != 'tadkit' ? 'childWindow': '_blank');

	window.open(baseURL + "visualizers/" + tool + "/?" + query, target);

}
	
viewResults = function(execution, tool) {
		
	App.blockUI({
				boxed: true,
		message: 'Creating tool output, this operation may take a while, please don\'t close the tab...'
			});

	$.ajax({
		type: "POST",
		url: baseURL + "/applib/loadOutput.php",
		data:"execution=" + execution + "&tool=" + tool,
		success: function(data) {
			//d = data.replace(/(\r\n|\n|\r|\t)/gm,"");
			/*if(d == '1'){
				setTimeout(function(){ location.href = '/'; }, 1000);	
			}else{
				App.unblockUI();
			}*/

			//console.log(data);

			if(data == '1'){
				setTimeout(function(){ location.href = 'tools/' + tool + '/output.php?execution=' + execution; }, 500);	
			}else if(data == '0') {
				setTimeout(function(){ location.href = 'workspace/'; }, 500);
			}
		}
	});

};

editAllFiles = function() {
	var query = "";
	for(i in allFiles){
		if(allFiles[i].checked) {
			query += 'fn[]=' + allFiles[i].fileId + '&';
		}
	} 
	query = query.slice(0, -1);
	location.href = baseURL + "getdata/uploadForm2.php?" + query;
}

checkJobStatus = function() {
	//console.log("checking job status");
	$.ajax({
			type: "GET",
			url: baseURL + "applib/updateUserJobs.php",
			data: "id=1", 
			success: function(data) {
				var d = JSON.parse(data);
				if(d.hasChanged == 1) location.href= baseURL + "workspace/";
			}
		});
}

$(document).ready(function() {

	if ( $(".job-running").length ) {

        // check jobs in intervals exponentially longer 
        var interval = 10000;
        timer = function() {
            interval=interval*1.5;
            intJobStatus = checkJobStatus();

            interval = interval > 300000 ? 300000 : interval; // max interval 5min
            setTimeout(timer, interval);
        };
        timer();

		//intJobStatus = setInterval(checkJobStatus, 10000);
	} else {
		clearInterval(intJobStatus);
	}

	$('#modalDelete').find('.modal-footer .btn-modal-del').on('click', function(){
		$('#modalDelete').find('.modal-footer .btn-modal-del').prop('disabled', true);
		$('#modalDelete').find('.modal-footer .btn-modal-del').html('Deleting...');

		if((option == 'deleteSure') || (option == 'deleteDirOk')) {
			var fn = "&fn=" + fileName;
		} else if(option == 'deleteAll'){
			var fn = "&";
			for(i in allFiles){
				if(allFiles[i].checked) {
					fn += 'fn[]=' + allFiles[i].fileId + '&';
				}
			}
			fn = fn.slice(0, -1);
		}

		$.ajax({
			type: "GET",
			url: baseURL + "applib/actionsWS.php",
			data: "op=" + option + fn, 
			success: function(data) {
				$('#modalDelete').modal('toggle');	
				//console.log(data);
				location.href= baseURL + "workspace/";
			}
		});

	});

	$('#modalDeleteProject').find('.modal-footer .btn-modal-del').on('click', function(){
		$('#modalDeleteProject').find('.modal-footer .btn-modal-del').prop('disabled', true);
		$('#modalDeleteProject').find('.modal-footer .btn-modal-del').html('Deleting...');

		location.href = baseURL + "applib/manageProjects.php?op=delete&pr_id=" + projectName;

	});
	
	// Optimalisation: Store the references outside the event handler:
  var $window = $(window);
	
  function checkWidthWS() {
		var windowsize = $window.width();
		if (windowsize < 989){
			$('.truncate').css('max-width', '70px');
			$('.truncate2').css('max-width', '70px');
		}else if ((windowsize < 1120) && (windowsize > 990)){
			$('.truncate').css('max-width', '100px');
			$('.truncate2').css('max-width', '140px');
		}else {
			$('.truncate').css('max-width', '140px');
			$('.truncate2').css('max-width', '140px');
		}
  }
	// Execute on load
	checkWidthWS();
	// Bind event listener
	$(window).resize(checkWidthWS);

	
	$('#modalGuest').on('hidden.bs.modal', function () {
		location.href = baseURL + "applib/modifyUserFirstTime.php";
	});

	// modal Progress / Log
	$("#btn-modal-progress").click(function() {

		$(this).addClass("default");
		$(this).removeClass("green");
		$("#btn-modal-log").removeClass("default");
		$("#btn-modal-log").addClass("green");

		$("#meta-progress").show();
		$("#meta-log").hide();

		$('#modalProgress .modal-header .modal-title span').html("Progress");

		//$("#modalProgress .modal-body").animate({ scrollTop: $(document).height() }, 1000);

	});

	$("#btn-modal-log").click(function() {

		$(this).addClass("default");
		$(this).removeClass("green");
		$("#btn-modal-progress").removeClass("default");
		$("#btn-modal-progress").addClass("green");

		$("#meta-progress").hide();
		$("#meta-log").show();

		$('#modalProgress .modal-header .modal-title span').html("Raw Log");

	});

	/*$('#modalProgress').on('show.bs.modal', function (e) {
			$("#modalProgress .modal-body").animate({ scrollTop: $(document).height() }, 1000);
	});*/

	$('#modalProgress').on('hidden.bs.modal', function () {
		clearInterval(intervalProgress);
		$("#meta-progress").show();
		$("#meta-log").hide();
		$("#btn-modal-progress").addClass("default");
		$("#btn-modal-progress").removeClass("green");
		$("#btn-modal-log").removeClass("default");
		$("#btn-modal-log").addClass("green");
	});

	if($("#from").val() != "") {

		$('#modalTool').modal({ show: 'true' });

		$("#btn-sample").click(function() {

			$(this).prop('disabled', true);	
			$(this).html('<i class="fa fa-spinner fa-pulse fa-spin"></i> Importing example dataset, please don\'t close the tab.');

			$("#import-sample").submit();

		});

		$("#tool-modal-help").click(function() {

			$('#modalTool').modal({ show: 'true' });

		});


	}

	$("#submit-rename").click(function() {
	
		$(this).prop("disabled", true);	
		$(this).html('Submitting <i class="fa fa-spinner fa-spin"></i>');

		if(typeRename == "file") {
			var op = "moveFile";
		} else {
			var op = "moveDir";
		}

		$.ajax({
			type: "POST",
			url: baseURL + "workspace/workspace.php",
			data: "op=" + op + "&fn=" + fileRenameID + "&target=" + pathRename + $('#modalRename .modal-body #form-rename input#new-name').val(), 
			success: function(data) {
				var obj = JSON.parse(data);
		
				if(!obj.error) {
					location.href = baseURL + "workspace";
				}	else {
					$('#modalRename .modal-body .alert-danger').show();
					$('#modalRename .modal-body .alert-danger').html('<strong>Error!</strong> ' + obj.msg);
				}
			}
			
		});

	});

	$('#modalRename').on('hidden.bs.modal', function () {
		$('#modalRename .modal-body #loading-rename').show();
		$('#modalRename .modal-body #form-rename').hide();
	});

	$("#project-name").change(function() {

		var exc = $(this).val();

		//console.log(usrPrjStr.projects);

		$.each(usrPrjStr.projects, function(k, v) {

			if(v.id == exc) {

				$("#execution-name").html('');
					
				$.each(v.executions, function(k1, v1) {
					$("#execution-name").append('<option value="' + v1.id + '">' + v1.name + '</option>');
				});

			}
				
		});

	});

	$("#submit-move").click(function() {
	
		$(this).prop("disabled", true);	
		$(this).html('Submitting <i class="fa fa-spinner fa-spin"></i>');

		$.each(usrPrjStr.projects, function(k, v) {

			if(v.id == $("#project-name").val()) {

				pathMoveDir = v.path.replace(/\//g, "%2F") + "%2F";;

				$.each(v.executions, function(k1, v1) {

					if(v1.id == $("#execution-name").val()) {
						
						pathMoveFile = v1.path.replace(/\//g, "%2F") + "%2F";
						return false;

					}

				});

			}

		});

		if(typeMove == "file") {
			var op = "moveFile";
			var query = "op=" + op + "&fn=" + fileMoveID + "&target=" + pathMoveFile + $('#modalMove .modal-body input#new-name-move-file').val();
		} else if(typeMove == "dir") {
			var op = "moveDir";
			var query = "op=" + op + "&fn=" + fileMoveID + "&target=" + pathMoveDir + $('#modalMove .modal-body input#new-name-move-file').val();
		} else {
			var op = "moveFiles";
			var query = "op=" + op + "&" + filesMoveIDs + "&target=" + pathMoveFile;
		}

		console.log(query);

		$.ajax({
			type: "POST",
			url: baseURL + "workspace/workspace.php",
			data: query, 
			success: function(data) {
				var obj = JSON.parse(data);
		
				if(!obj.error) {
					location.href = baseURL + "workspace";
				}	else {
					$("#submit-move").prop("disabled", false);	
					$("#submit-move").html('Submit <i class="fa fa-submit"></i>');
					$('#modalMove .modal-body .alert-danger').show();
					$('#modalMove .modal-body .alert-danger').html('<strong>Error!</strong> ' + obj.msg);
				}
			}
			
		});

	});

	$('#modalMove').on('hidden.bs.modal', function () {
		$('#modalMove .modal-body #loading-move').show();
		$('#modalMove .modal-body #move-file').hide();
		$('#modalMove .modal-body #move-dir').hide();
	});


});

function loadWSTool(op) {
	table.state.clear();	
	location.href = baseURL + "workspace/?tool=" + op.value;

}

function closeModalTool() {
	$('#modalTool').modal('hide');
}
