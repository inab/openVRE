<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption">
                    <i class="icon-share font-dark hide"></i>
                    <span class="caption-subject font-dark bold uppercase">EGA datasets</span>
                </div>
            </div>

            <div class="portlet-body" id="portlet-ws">
                <div class="row">
                    <div class="col-md-12">
                        <table id="datasets-list" class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>Dataset ID</th>
                                    <th>Title</th>
                                    <th>Description</th>
                                    <th>Dataset Types</th>
                                    <th>Technologies</th>
                                    <th>Number of Samples</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dataArray as $item): ?>
                                    <tr class="dataset-row"
                                        data-accession-id="<?php echo htmlspecialchars($item['accession_id']); ?>">
                                        <td><?php echo htmlspecialchars($item['accession_id']); ?></td>
                                        <td><?php echo htmlspecialchars($item['title'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($item['description']); ?></td>
                                        <td><?php echo htmlspecialchars(implode(', ', $item['dataset_types'] ?? ['N/A'])); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars(implode(', ', $item['technologies'] ?? ['N/A'])); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['num_samples']); ?></td>
                                    </tr>
                                    <tr class="file-list-row" style="display: none;">
                                        <td colspan="11">
                                            <div class="file-list-container">
                                                <table class="table table-striped table-bordered table-hover">
                                                    <thead>
                                                        <tr>
                                                            <th></th>
                                                            <th>File ID</th>
                                                            <th>Display name</th>
                                                            <th>Filesize</th>
                                                            <th>Extension</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="file-list-body">
                                                        <!-- Files will be loaded here -->
                                                    </tbody>
                                                </table>
                                                <div class="file-pagination">
                                                    <!-- Pagination controls will be added here -->
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <div class="pagination">
                    <?php if ($currentPage > 1): ?>
                        <a href="/ega?page=<?php echo $currentPage - 1; ?>">&laquo; Previous</a>
                    <?php endif; ?>

                    <?php
                    $start_page = max(1, $currentPage - 10);
                    $end_page = min($total_pages, $currentPage + 9);

                    for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <a href="/ega?page=<?php echo $i; ?>"
                            class="<?php echo $i == $currentPage ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>

                    <?php if ($currentPage < $total_pages): ?>
                        <a href="/ega?page=<?php echo $currentPage + 1; ?>">Next &raquo;</a>
                    <?php endif; ?>
                </div>
            </div>
            <div id="add-to-workspace-container" style="margin-top: 20px;">
                <button id="add-to-workspace">Add to workspace</button>
            </div>
        </div>
        <!-- END EXAMPLE TABLE PORTLET-->
    </div>
    <!-- END CONTENT BODY -->
</div>
<!-- END CONTENT -->


<script>
    document.addEventListener('DOMContentLoaded', function () {
        const rows = document.querySelectorAll('.dataset-row');
        rows.forEach(row => {
            row.addEventListener('click', function () {
                const accessionId = this.getAttribute('data-accession-id');
                const fileListRow = this.nextElementSibling;
                const fileListContainer = fileListRow.querySelector('.file-list-container');
                const fileListBody = fileListContainer.querySelector('.file-list-body');
                const filePagination = fileListContainer.querySelector('.file-pagination');
                fileListBody.innerHTML = ''; // Clear previous file list
                filePagination.innerHTML = ''; // Clear previous pagination

                fetchFiles(accessionId, 0, fileListBody, filePagination);
                fileListRow.style.display = 'table-row'; // Show the file list row
            });
        });
    });

    function fetchFiles(accessionId, offset, fileListBody, filePagination) {
        fetch(`getdata/fetchEgaDatasets.php?action=fetch_files&accession_id=${accessionId}&offset=${offset}&limit=10`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                return response.json();
            })
            .then(data => {
                if (data.error) {
                    alert(data.error);
                    return;
                }

                fileListBody.innerHTML = ''; // Clear previous file list

                data.filter(file => file.locations.includes('crg')).forEach(file => {
                    const row = document.createElement('tr');
                    row.dataset.datasetId = accessionId;

                    // Convert filesize from bytes to MB or GB
                    const filesize = file.filesize;
                    const displaySize = filesize > 1e9 
                        ? (filesize / 1e9).toFixed(2) + ' GB' 
                        : filesize > 1e6 
                            ? (filesize / 1e6).toFixed(2) + ' MB' 
                            : (filesize / 1e3).toFixed(2) + ' KB';

                    row.innerHTML = `
                    <td><input type="checkbox" class="file-select"></td>
                    <td>${file.accession_id}</td>
                    <td>${file.display_name}</td>
                    <td>${displaySize}</td>
                    <td>${file.extension}</td>
                `;

                    fileListBody.appendChild(row);
                });

                // Add pagination controls
                filePagination.innerHTML = '';
                const totalFiles = data.length;
                const filesPerPage = 5;
                const totalFilePages = Math.ceil(totalFiles / filesPerPage);

                if (offset > 0) {
                    const prevButton = document.createElement('button');
                    prevButton.innerText = 'Previous';
                    prevButton.addEventListener('click', () => fetchFiles(accessionId, offset - filesPerPage, fileListBody, filePagination));
                    filePagination.appendChild(prevButton);
                }

                if (offset + filesPerPage < totalFiles) {
                    const nextButton = document.createElement('button');
                    nextButton.innerText = 'Next';
                    nextButton.addEventListener('click', () => fetchFiles(accessionId, offset + filesPerPage, fileListBody, filePagination));
                    filePagination.appendChild(nextButton);
                }
            })
            .catch(error => {
                alert('Error fetching files: ' + error);
            });
    }

    document.getElementById('add-to-workspace').addEventListener('click', function () {
        const selectedDatasetIds = [];
        const selectedFileIds = [];
        const selectedFileNames = [];
        const selectedFileSizes = [];
        document.querySelectorAll('.file-select:checked').forEach(checkbox => {
            const row = checkbox.closest('tr');
            const datasetId = row.dataset.datasetId;
            const fileId = row.cells[1].innerText;
            const displayName = row.cells[2].innerText;
            const fileSize = toBytes(row.cells[3].innerText);
            const file = `${datasetId}/${displayName}`;

            selectedDatasetIds.push(datasetId);
            selectedFileIds.push(fileId);
            selectedFileNames.push(displayName);
            selectedFileSizes.push(fileSize);
        });

        if (selectedDatasetIds.length === 0) {
            alert('No files selected');
            return;
        }

        // Perform the desired operation with selectedFiles
        console.log('Selected files:', selectedDatasetIds);
        // You can add your desired operation here
        addFilesToWorkspace(selectedDatasetIds, selectedFileIds, selectedFileNames, selectedFileSizes);
    });

    function addFilesToWorkspace(datasetIds, fileIds, displayNames, fileSizes) {
        fetch(`applib/getData.php?uploadType=ega&datasetIds=${datasetIds}&fileIds=${fileIds}&displayNames=${displayNames}&fileSizes=${fileSizes}`).then(response => {
            console.log('Response:', response);
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
        }).catch(error => {
            console.error('Error:', error);
        });
    }

    function toBytes(displaySize) {
        const size = parseFloat(displaySize); // Extract the numeric value

        // Check for unit and convert accordingly
        if (displaySize.includes('GB')) {
            return size * 1e9; // Convert GB to bytes
        } else if (displaySize.includes('MB')) {
            return size * 1e6; // Convert MB to bytes
        } else if (displaySize.includes('KB')) {
            return size * 1e3; // Convert KB to bytes
        }

        return null; // Return null if unit is unknown
    }

</script>

