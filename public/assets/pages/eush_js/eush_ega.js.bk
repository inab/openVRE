$(document).ready(function() {

        $("#errorsTool").hide();
        
        //get info on the currently logged user
    	var urlJSON = "applib/eush_ega.php";
    	$.ajax({
                type: 'POST',
                url: urlJSON,
                data: {'action': 'getUser'}
        }).done(function(data) {

        var currentUser = data;

        var files = ["EGAF00001412786", "EGAF00001412787", "EGAF00001412788"]

        var sites = []

        for (var i = 0; i < files.length; i++) {
            sites.push('applib/eush_ega.php?action=getEGADatasets&file_id='+files[i]+'')
        }
        
        var files_format = []
        for (var i = 0; i < sites.length; i++) {
            $.ajax({
                url: sites[i],
                type: "POST",
                dataType: "json",
                async: false,
                success: function(data){
                    files_format.push(data)
                }
            });    
        }
        
        var files_format_parsed = []

        for (var j = 0; j < sites.length; j++) {
            files_format_parsed.push(JSON.parse(files_format[j]).response.result)
        }

        var egaDSId = []
  
        for (var j = 0; j < files_format_parsed.length; j++) {
            egaDSId.push(files_format_parsed[j][0]["egaStableId"])
        }

        var file_query = []
        for (var i = 0; i < egaDSId.length; i++) {
            file_query.push('applib/eush_ega.php?action=getEGAFiles&file_id='+egaDSId[i]+'')
        }

        var ds_metadata = []
        for (var k = 0; k < file_query.length; k++) {
            $.ajax({
                url: file_query[k],
                type: "POST",
                dataType: "json",
                async: false,
                success: function(data){
                    ds_metadata.push(data)
                }
            });    
        }

        var ds_format_parsed = []

        for (var y = 0; y < file_query.length; y++) {
            ds_format_parsed.push(JSON.parse(ds_metadata[y]).response.result)
        }

        var filtered_file_metadata = []

        for (var x = 0; x < ds_format_parsed.length; x++) {
            for(var z = 0; z < file_query.length; z++) {
                if(ds_format_parsed[x][z]["egaStableId"] === files[x]){
                    filtered_file_metadata.push(ds_format_parsed[x][z])
                }
            }
        }

        //GENERAL DATATABLE
        $('#egaTable').DataTable( {
            "ajax": {
                url: 'applib/eush_ega.php?action=getEGAFiles',
                dataSrc: function (jsonData) {
                    var table = []
                    for (var i = 0; i < filtered_file_metadata.length; i++) {
                        // File ID
                        fileEGAId = filtered_file_metadata[i]["egaStableId"]
                        fileFormat = filtered_file_metadata[i]["fileFormat"]
                        fileSize = filtered_file_metadata[i]["fileSize"]

                        obj = { "file_id" : fileEGAId, 
                                "file_format" : fileFormat, 
                                "file_size" : fileSize,
                                "download" : "blabla"
                        }   

                        table.push(obj)
                    }
                    return table;
                }
            },
            autoWidth: false,
            "columns" : [
                { "data" : "file_id"},
                { "data" : "file_format" },
                { "data" : "file_size"},
                { "data" : "download"}
            ],
            "columnDefs": [
                //targets are the number of corresponding columns
                { "title": "File ID", "targets": 0 },
                { "title": "Format", "targets": 1 },
                { "title": "Size", "targets": 2 },
                { "title": "", "targets": 3 },
                { render: function(data, type, row) {
                    // Here we should put an href to the specific file path.
                     return '<a href="filelocator"  class="btn  green" > <i class="fa fa-cloud-upload font-white" data-original-title="Import dataset to workspace"></i> &nbsp; IMPORT</a>'
                }, "targets": 3},
                { render: function(data, type, row) {
                    // Here we should put an href to the specific project.
                    return '<a href="/vre/getdata/eush_ega/eush_ega_datasets.php?file_id='+row.file_id+'"> '+row.file_id+' </a>'
                }, "targets": 0}
            ]
        });
    });

        $("#workflowsReload").click(function() {
                reload();
    });
});


