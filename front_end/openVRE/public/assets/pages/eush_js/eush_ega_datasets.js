$(document).ready(function() {

        $("#errorsTool").hide();

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

        //file_id = getUrlParameter('ds_id');

        ds_id = getUrlParameter('ds_id');
        console.log(ds_id)
    
        //get info on the currently logged user
    	var urlJSON = "applib/eush_ega.php";
    	$.ajax({
                type: 'POST',
                url: urlJSON,
                data: {'action': 'getUser'}
        }).done(function(data) {

        var currentUser = data;

        var ega_outbox = []

        console.log("OK 1")
        // HERE WE WILL GE FILEIDS FROM EGA OUTBOX -> USE AS A FILTER LATER ON.
        $.ajax({
            //url: 'applib/eush_ega.php?action=listFiles&ds_id='+ds_id+'', 
            url: 'applib/eush_EGA.php?action=listFiles&datasets_id='+ds_id+'',          
            type: "GET",
            dataType: "json",
            async: false,
            success: function(data){
                for(var w = 0; w < data.length; w++){
                    // HERE WE WILL GE FILEIDS FROM EGA OUTBOX -> USE AS A FILTER LATER ON.
                    console.log("hi there")
                    console.log(data[w])
                    //ega_outbox.push(data[w].split("/")[1])
                    ega_outbox.push(data[w])
                }
            }
        });
        console.log(ega_outbox)
        
        //GENERAL DATATABLE
   //     var filelocator = 'applib/getData.php?uploadType=federated_repository&repository=egaoutbox&repository_path='+id+'/'+id+''
        var filelocator = 'applib/getData.php?uploadType=federated_repository&repository=egaoutbox&repository_path=EGAD50000000024/EGAF50000000016'
        $('#egaTableDS').DataTable( {
            "ajax": {
                url: 'applib/eush_ega.php?action=getEGAFilesFromDSid&ds_id='+ds_id+'',
                dataSrc: function (jsonData) {
                    var table = []

                    var results = JSON.parse(jsonData).response.result
                    console.log("results")
                    console.log(results)
                    // HERE THE FILTER FOR EGA OUTBOX FILEID LIST.
                    var filtered_file_metadata = []
            
                    for (var x = 0; x < results.length; x++) {
                        for(var z = 0; z < ega_outbox.length; z++) {
                            if(results[x]["egaStableId"] === ega_outbox[z]){
                                filtered_file_metadata.push(results[x])
                            }
                        }
                    }

                    // CHANGE RESULTS VAR FOR RESULTS_FILTERED.
                    for (var i = 0; i < results.length; i++) {
                        fileEGAId = results[i]["egaStableId"]
                        fileFormat = results[i]["fileFormat"]
                        fileSize = results[i]["fileSize"]
    
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
                { "data" : "file_id" },
                { "data" : "file_format" },
                { "data" : "file_size" },
                { "data" : "download" }
            ],
            "columnDefs": [
                //targets are the number of corresponding columns
                { "title": "File ID", "targets": 0 },
                { "title": "Format", "targets": 1 },
                { "title": "Size", "targets": 2 },
                { "title": "", "targets": 3 },
                { render: function(data, type, row) {
                    // Here we should put an href to the specific file path.
                     return '<a href="applib/getData.php?uploadType=federated_repository&repository=egaoutbox&size='+row.file_size+'&format='+row.file_format+'&repository_path='+ds_id+'/'+row.file_id+'"  class="btn  green" > <i class="fa fa-cloud-upload font-white" data-original-title="Import dataset to workspace"></i> &nbsp; IMPORT</a>'
                }, "targets": 3}
            ]
        });




        /*
        $('#egaTableDS').DataTable( {
            "ajax": {
                url: 'applib/eush_ega.php?action=getEGADatasets&file_id='+file_id+'',
                dataSrc: function (jsonData) {

                    var table = []
                    var results = []

                    results.push(JSON.parse(jsonData).response.result[0])
                    
                    datasetEGAId = results[0]["egaStableId"]
                    datasetDescription = results[0]["description"]
                    datasetTechnology = results[0]["technology"]
                    datasetSamples = results[0]["numSamples"]
                    
                    obj = { "dataset_id" : datasetEGAId, 
                            "dataset_description" : datasetDescription, 
                            "dataset_technology" : datasetTechnology,
                            "dataset_samples" : datasetSamples
                    }   

                    table.push(obj)
                    
                    return table;
                }
            },
            autoWidth: false,
            "columns" : [
                { "data" : "dataset_id" },
                { "data" : "dataset_description" },
                { "data" : "dataset_technology" },
                { "data" : "dataset_samples" }
            ],
            "columnDefs": [
                //targets are the number of corresponding columns
                { "title": "Dataset ID", "targets": 0 },
                { "title": "Description", "targets": 1 },
                { "title": "Technology", "targets": 2 },
                { "title": "Samples", "targets": 3 }
            ]
        });*/
    });

        $("#workflowsReload").click(function() {
                reload();
    });
});


