$(document).ready(function() {

    $("#errorsTool").hide();

    console.log('bbbbbbbb');
    console.log('Before AJAX call');
    var urlJSON = 'applib/objStorage_openstack.php';
    var credential_data = '';
    console.log(urlJSON);
    table = [];

    $('#loading-datatable').show();
	$.ajax({
		async: false,
		type: 'GET',
		url: urlJSON,
		data: {'action': 'getOpenstackUser'}
	}).done(function(data) {
		console.log('Response data:', data);
		var credential_data = data;
		//console.log(credential_data);
		var containers = [];
		
		var matches = credential_data.match(/\| (.+?)\s+\|/g);
		if (matches) {
			matches.forEach(function(match) {
				var containerName = match.replace(/^\|\s+|\s+\|$/g, '');
				//containers.push(containerName);
				if (containerName.trim() !== "Name") {
					containers.push(containerName);
				}
			});
		}
		console.log("Containers: ", containers);

		var dropdown = $('#containerDropdown');
		dropdown.empty();

		containers.forEach(function(container) {
			var option = $('<option></option>').text(container);
			dropdown.append(option);	
		});

		dropdown.show();
		$('#loading-datatable').hide();
		if (data.status === 'success' && data.fileId) {
			$.ajax({
				type: 'POST',
				url: 'applib/objStorage_openstack.php',
				data: {
					action: 'logSuccess',
					successMessage: 'File downloaded successfully. File ID: ' + data.fileId + ' is present in the workspace.'
				}
			}).done(function(response) {
				console.log('Success logged successfully:', response);
			}).fail(function(jqXHR, textStatus, errorThrown) {
				console.error('Success logging failed:', textStatus, errorThrown);
			});
		}

	}).fail(function(jqXHR, textStatus, errorThrown) {
		console.log(urlJSON);
		console.error('AJAX request failed:', textStatus, errorThrown);   
		console.log('Response text:', jqXHR.responseText);
		$('#loading-datatable').hide();
		$("#errorsTool").show();

		$.ajax({
			type: 'POST',
			url: 'applib/objStorage_openstack.php',
			data: {
				action: 'logError',
				errorMessage: 'AJAX request failed: ' + textStatus + ' - ' + errorThrown,
				responseText: jqXHR.responseText
			}
		}).done(function(response) {
			console.log('Error logged successfully:', response);
		}).fail(function(jqXHR, textStatus, errorThrown) {
			console.error('Error logging failed:', textStatus, errorThrown);
		});


	});
	   
    console.log('After AJAX call');
    console.log('hello');
    
    var table = [];

    $('#containerDropdown').change(function() {
	    var selectedContainer = $(this).val();
	    console.log(selectedContainer);
	    //var container_files = [];
	    $('#loading-datatable').show()
	    fetchFiles(selectedContainer);
    });



function fetchFiles(container) {
            var urlJSON = 'applib/objStorage_openstack.php';
	$.ajax({
                    type: 'POST',
                    url: urlJSON,
                    async: false,
                    data: { 'action': 'getContainerFiles',
                            'container': container
                    },
                    success: function(response) {
                            console.log("Container: " + container)
                            console.log("Response: " + response)
                    
                            if (response) {
                                    console.log("Server response:");                        
                                    console.log(response);                          
                                    try {   
                                            var files = JSON.parse(response);                       
                                            console.log("Files:");                          
                                            console.log(files);
                                            console.log(typeof files);
                    
			                    // Populate table with files and container info
					   // if (typeof files === 'object' && files !== null) { 
					    populateTable(files, container);
			//		    } else {
			//			    console.error("Files is not a valid object.");
			//		    } 
                                    } catch (e) {                                  
                                            console.error("Error parsing JSON response:", e);                       
                                    }              
                            } else {                        
                                    console.log("Empty response received from server");             
                            }
                    },
                    error: function(xhr, status, error) {
                            console.error('Error fetching files:', error);
                    },
		    complete: function() {
			    $('#loading-datatable').hide(); // Hide loading indicator when AJAX request is complete
		    }
            });
    }




	function populateTable(files, container) {
		var tableBody = document.getElementById("workflow-data");

                tableBody.innerHTML = "";
                if (typeof files === 'string') {
                        try {
                                files = JSON.parse(files);

                        } catch (e) {
                                console.error("Error parsing files string as JSON:", e);
                                return;
                        }
                }

                files.forEach(function(file) {
                        var row = tableBody.insertRow();
                        var nameCell = row.insertCell();
                        //var fileUrl = container + '/' + file.Name.trim();
                        nameCell.textContent = file.Name;

                        // dowload button
                        var actionCell = row.insertCell();
                        var downloadButton = document.createElement('button');
                        actionCell.style.textAlign = "right";
                        downloadButton.classList.add('btn', 'btn-primary', 'btn-sm');
                        downloadButton.textContent = 'Download';
                        downloadButton.addEventListener('click', function() {
                                downloadFile(container, file.Name);
                        });
                        actionCell.appendChild(downloadButton);
                });
        }


        function downloadFile(container, fileName) {
		$('#loading-datatable').show();
                $.ajax({
                        type: 'POST',
                        url: 'applib/objStorage_openstack.php',
                        data: {
                                action: 'downloadFile',
                                fileName: fileName,
                                container: container
                        },
                        success: function(response) {
                                console.log('Raw response:', response);
                                console.log('Response type:', typeof response);
                                console.log('Filename:', fileName);
                                console.log('container:', container);
                                response = JSON.parse(response);
                                console.log('Response:', response);
				$('#loading-datatable').show();

                                try {
                                        if (response && response.status === 'success') {
                                        // Handle the file download response
                                                var link = document.createElement('a');
                                                link.href = response.fileName; // URL returned by the server
                                                link.download = response.fileName; // Filename returned by the server
						$('#loading-datatable').hide();

                                                //document.body.appendChild(link);
                                                //link.click();
                                                //document.body.removeChild(link);

						// Log success message
						$.ajax({
							type: 'POST',
							url: 'applib/objStorage_openstack.php',
							data: {
								action: 'logSuccess',
								successMessage: 'File downloaded successfully. File ID: ' + response.fileId + ' is present in the workspace.'
							}
						}).done(function(logResponse) {
							console.log('Success logged successfully:', logResponse);
							 location.reload();
						}).fail(function(jqXHR, textStatus, errorThrown) {
							console.error('Success logging failed:', textStatus, errorThrown);
						});


                                        } else {
                                                console.error('Invalid response:', response.status);
						$.ajax({
							type: 'POST',
							url: 'applib/objStorage_openstack.php',
							data: {
								action: 'logError',
								errorMessage: 'Invalid response received.',
								responseText: JSON.stringify(response)
							}
						}).done(function(logResponse) {
							console.log('Error logged successfully:', logResponse);
							$('#loading-datatable').hide();
							// Reload the page to show the error message
							location.reload();
						}).fail(function(jqXHR, textStatus, errorThrown) {
							console.error('Error logging failed:', textStatus, errorThrown);
						});
					}

                                } catch (e) {
                                        console.error('Failed to parse JSON response:', e);
					$.ajax({
						type: 'POST',
						url: 'applib/objStorage_openstack.php',
						data: {
							action: 'logError',
							errorMessage: 'Failed to parse JSON response.',
							responseText: e.toString()
						}
					}).done(function(logResponse) {
						console.log('Error logged successfully:', logResponse);
						// Reload the page to show the error message
						location.reload();
					}).fail(function(jqXHR, textStatus, errorThrown) {
						console.error('Error logging failed:', textStatus, errorThrown);
					});
                                }
                        },
                        error: function(xhr, status, error) {
                                console.error('Error downloading file:', error);

				$.ajax({
					type: 'POST',
					url: 'applib/objStorage_openstack.php',
					data: {
						action: 'logError',
						errorMessage: 'Error downloading file: ' + error,
						responseText: xhr.responseText
					}
				}).done(function(logResponse) {
					console.log('Error logged successfully:', logResponse);
					location.reload();
				}).fail(function(jqXHR, textStatus, errorThrown) {
					console.error('Error logging failed:', textStatus, errorThrown);
				});
                        },
			complete: function() {
				$('#loading-datatable').hide(); // Hide loading indicator when file download request is complete
			}
                });
        }







/*
    getUrlParameter = function getUrlParameter(sParam) {
        var sPageURL = window.location.search.substring(1),
            sURLVariables = sPageURL.split('&'),
            sParameterName,
            i;
    
        for (i = 0; i < sURLVariables.length; i++) {
            sParameterName = sURLVariables[i].split('=');
    
            if (sParameterName[0] === sParam) {
                return sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
            }
        }
    };

    project_id = getUrlParameter('project_id');
    subject_id = getUrlParameter('subject_id');
    console.log(project_id)
    console.log(subject_id)

    var urlJSON = 'applib/eush_eurobioimaging.php?action=getExperiments&project_id='+project_id+'&subject_id='+subject_id+''
    var table = []

    $.ajax({
        type: 'POST',
        url: urlJSON,
        async: false,
        data: {'action': 'getExperiments'}
        }).done(function(json) {
            var experiment_data = json;
            var exp_parsed = JSON.parse(experiment_data)
            var array = null
            Object.keys(exp_parsed).forEach(function(key) {
                array = exp_parsed[key];
            })
            array = array["Result"]
            for (var i = 0; i < array.length; i++) {
                // Experiment ID
                id = array[i]["ID"]
                // Experiment Date
                date = array[i]["date"]
            
                obj = { "experimentID" : id, 
                        "date" : date
                }        
                table.push(obj)
            }      
    })
    console.log("table: ", table)

    // We don't need this as we will select just one experiment.
    var sites = []
    for (var i = 0; i < table.length; i++) {
        sites.push('applib/eush_eurobioimaging.php?action=getExperimentsFormat&project_id='+project_id+'&subject_id='+subject_id+'&experiment_id='+table[i].experimentID+'')
    }
    console.log("Sites:", sites)
    var exp_format = []
    for (var i = 0; i < sites.length; i++) {
        $.ajax({
            url: sites[i],
            type: "POST",
            dataType: "json",
            async: false,
            success: function(data){
                exp_format.push(data)
            }
        });    
    }
    console.log("FORMAT:")
    console.log(exp_format)
    var exp_format_parsed = JSON.parse(exp_format[0])
    console.log("PARSED FORMAT:")
    console.log(exp_format_parsed)
    var sub_children = null
    var scan_list = []
    var children_list = exp_format_parsed["items"][0]["children"][0]["items"]
    var total_size = 0
    var formats_list = []

    for(var i = 0; i < children_list.length; i++) {
        sub_children = children_list[i]["children"][0]["items"]

        uri = sub_children[0]["data_fields"]["URI"]
        
        // Here we get the scan name: According to public XNAT endpoints
        scan_list.push(uri.split("/")[7]);
        console.log("format")
        console.log(scan_list)
        // This one for a non-harcoded version....

        //commentato fino alla parentesi for(var j = 0; j < sub_children.length; j++) {
            uri = sub_children[j]["data_fields"]["URI"]
            scan.uri.split("/")[7];
            console.log("uri: ", uri)
            console.log("elements: ", res)
        } questa

        // And here, we calculate the total size of the files (ALL SCANS).
        for(var j = 0; j < sub_children.length; j++) {
            total_size += sub_children[j]["data_fields"]["file_size"]
        }
    }

    // Do we want to display all scans names? 
    scan_list_formatted = scan_list.join("<br />")
    // Or just the total scans
    var children_list_total = children_list.length
    
    format = exp_format_parsed["items"][0]["children"][0]["items"][1]["children"][0]["items"][0]["data_fields"]["format"]
    //file_size = exp_format_parsed["items"][0]["children"][0]["items"][1]["children"][0]["items"][0]["data_fields"]["file_size"]
    console.log("format: ", format)
    console.log("formats list: ", formats_list)
    //console.log("file_size: ", file_size)
    console.log("total_size: ", total_size)
    console.log("total_scans: ". children_list_total)

    commentato $.ajax({
                type: 'GET',
                url: urlJSON2,
                data: {'action': 'getExperimentsType'}
                }).done(function(json) {
                    var experiment_data = json;
                    console.log(experiment_data)
                    var exp_parsed = JSON.parse(experiment_data)
                    var array = null
                    Object.keys(exp_parsed).forEach(function(key) {
                        array = exp_parsed[key];
                    })
                    array = array["Result"]
                    console.log("array: ", array)
                    for (var i = 0; i < array.length; i++) {
                        console.log(array[i]["ID"])
                        // Experiment ID
                        id = array[i]["ID"]
                        // Experiment Date
                        date = array[i]["date"]
                    
                        obj = { "experimentID" : id, 
                                "date" : date
                        }        
                        table.push(obj)
                    }  fino a qua
    commentato
    obj = { 
            "experimentID" : id, 
            "date" : date,
            "size" : total_size,
            "format" : format, 
            "scans" : children_list_total   
    }fino a qua

    commentato      Get all the scans individually
    
    var urlJSON_files = 'applib/eush_eurobioimaging.php?action=getExperimentsFiles&project_id='+project_id+'&subject_id='+subject_id+'&experiment_id='+id+''
    var scans = null
    $.ajax({
        type: 'POST',
        url: urlJSON_files,
        async: false,
        data: {'action': 'getExperimentsFiles'}
    }).done(function(json) {
        scans = JSON.parse(json)
        scans = scans["ResultSet"]["Result"]

    })
    console.log(scans) fino a qua
    //var urlJSON_files = 'applib/eush_eurobioimaging.php?action=getExperimentsFiles&project_id='+project_id+'&subject_id='+subject_id+'&experiment_id='+id+''
    //var urlJSON_files = 'applib/getData.php?uploadType=repository&url='+id+'.zip&repo=euroBioImaging&data_type=bioimage&file_type=ZIP'
    //LAIA EXAMPLE:
    
	var urlJSON_files = 'applib/getData.php?uploadType=repository&data_type=bioimage&compressed=ZIP&file_type='+format+'&url='+encodeURI('https://xnat.bmia.nl/data/archive/projects/'+project_id+'/subjects/'+subject_id+'/experiments/'+id+'/scans/ALL/files?format=zip')
    console.log("1: ", urlJSON_files)
    var options = {
        "experimentID" : id,
        "date" : date,
        "size" : total_size,
        "format" : format,
        "scans" : children_list_total,
        "download" : urlJSON_files,
        "ajax": {
            url: 'applib/eush_eurobioimaging.php?action=getExperimentsFormat&project_id='+project_id+'&subject_id='+subject_id+'&experiment_id='+id+'',
            dataSrc: function (jsonData) {
                //console.log(jsonData)
                var table = []
                    
                obj = { "experimentID" : id, 
                        "date" : date,
                        "size" : total_size, 
                        "format" : format,
                        "scans" : children_list_total,
                        "download" : urlJSON_files
                }        
                table.push(obj)
                console.log("2: ", obj.download)
                return table;
            }
        },
        autoWidth: false,
        "columns" : [
            { "data" : "experimentID"},
            { "data" : "date" },
            { "data" : "size"},
            { "data" : "format" },
            { "data" : "scans" },
            { "data" : "download" }
        ],
        "columnDefs": [
            //targets are the number of corresponding columns
            { "title": "experimentID", "targets": 0 },
            { "title": "Date", "targets": 1 },
            { "title": "Total size", "targets": 2 },
            { "title": "Format", "targets": 3 },
            { "title": "Total scans", "targets": 4 },
            { "title": "", "targets": 5 },
            { render: function(data, type, row) {
                // Here we should put an href to the specific project.
                console.log(urlJSON_files)
                 //return '<a href="'+urlJSON_files+'"> Download </a>'
                 return '<a href="'+urlJSON_files+'"  class="btn  green" > <i class="fa fa-cloud-upload font-white" data-original-title="Import dataset to workspace"></i> &nbsp; IMPORT</a>'
            }, "targets": 5}
        ]
    }

        //get info on the currently logged user
        var urlJSON = "applib/eush_eurobioimaging.php";
        $.ajax({
                type: 'POST',
                url: urlJSON,
                data: {'action': 'getUser'}
        }).done(function(data) {
        var currentUser = data;

        //GENERAL DATATABLE
        $('#experimentsTable').DataTable(options);
        });

        $("#workflowsReload").click(function() {
                reload();
        }); */
}); 


