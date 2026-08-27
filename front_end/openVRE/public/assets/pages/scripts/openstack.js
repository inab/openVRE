$(document).ready(function () {
	$("#errorsTool").hide();
	$("#general").hide();
	$("#containerDropdown").hide();
	$("#loading-datatable").hide();
	var urlJSON = 'applib/objStorage_openstack.php';
	var credential_data = '';
	table = [];

	// Handle "Get Credentials" button click
	$("#getCredentialsButton").on("click", function () {
		const $btn = $(this);
		$btn.prop("disabled", true);
		$("#loading-datatable").show();
		$.ajax({
			async: false,
			type: 'GET',
			url: urlJSON,
			data: { 'action': 'getOpenstackUser' }
		}).done(function (data) {
			$('#loading-datatable').hide();
			if (data.error) {
				console.error('Backend error:', data.message);
				showError(data.message); // show in #errorsTool immediately
				return; // stop here
			}
			var credential_data = data;
			var containers = [];
			var matches = credential_data.match(/\| (.+?)\s+\|/g);
			if (matches) {
				matches.forEach(function (match) {
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
			containers.forEach(function (container) {
				var option = $('<option></option>').text(container);
				dropdown.append(option);
			});
			dropdown.show();
			$('#loading-datatable').hide();
			if (data.status === 'success' && data.fileId) {
				showSuccess('File downloaded successfully! File ID: ' + data.fileId + ' is present in the workspace.');
			}
		}).fail(function (jqXHR, textStatus, errorThrown) {
			console.error('AJAX request failed:', textStatus, errorThrown);
			// console.log('Response text:', jqXHR.responseText);
			const res = JSON.parse(jqXHR.responseText);
			showError('AJAX failed: ' + res.error);
		});


		function fetchFiles(container) {
			var urlJSON = 'applib/objStorage_openstack.php';
			$.ajax({
				type: 'POST',
				url: urlJSON,
				async: false,
				data: {
					'action': 'getContainerFiles',
					'container': container
				},
				success: function (response) {
					if (response) {
						console.log("Server response:");
						console.log(response);
						try {
							var files = JSON.parse(response);
							console.log("Files:");
							console.log(files);
							console.log(typeof files);
							populateTable(files, container);
						} catch (e) {
							console.error("Error parsing JSON response:", e);
						}
					} else {
						console.log("Empty response received from server");
					}
				},
				error: function (xhr, status, error) {
					console.error('Error fetching files:', error);
				},
				complete: function () {
					$('#loading-datatable').hide(); // Hide loading indicator when AJAX request is complete
				}
			});
		}


		function showError(message) {
			const errorsTool = $('#errorsTool');
			errorsTool
				.html('<div class="alert alert-danger"><strong>Error:</strong> ' + message + '</div>')
				.fadeIn();
			$('#loading-datatable').hide();
		}


		function showSuccess(message) {
			const errorsTool = $('#errorsTool');
			errorsTool
				.html('<div class="alert alert-success"><strong>Success:</strong> ' + message + '</div>')
				.fadeIn();
			$('#loading-datatable').hide();
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
			files.forEach(function (file) {
				var row = tableBody.insertRow();
				var nameCell = row.insertCell();
				nameCell.textContent = file.Name;
				var actionCell = row.insertCell();
				var downloadButton = document.createElement('button');
				actionCell.style.textAlign = "right";
				downloadButton.classList.add('btn', 'btn-primary', 'btn-sm');
				downloadButton.textContent = 'Download';
				downloadButton.addEventListener('click', function () {
					downloadFile(container, file.Name);
				});
				actionCell.appendChild(downloadButton)
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
				success: function (response) {
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
							showSuccess('File downloaded successfully! File ID: ' + response.fileId + ' is present in the workspace.');
						} else {
							console.error('Invalid response:', response.status);
							$('#loading-datatable').hide();
							showError('Invalid response received from the server.');
						}
					} catch (e) {
						console.error('Failed to parse JSON response:', e);
						showError('Failed to parse JSON response. Please try again.');
					}
				},
				error: function (xhr, status, error) {
					console.error('Error downloading file:', error);
					$('#loading-datatable').hide();
					showError('Error downloading file: ' + error);
				},
				complete: function () {
					$('#loading-datatable').hide(); // Hide loading indicator when file download request is complete
				}
			});
		}
	});
});

