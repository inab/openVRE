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

        file_id = getUrlParameter('file_id');
    
        //get info on the currently logged user
    	var urlJSON = "applib/eush_ega.php";
    	$.ajax({
                type: 'POST',
                url: urlJSON,
                data: {'action': 'getUser'}
        }).done(function(data) {

        var currentUser = data;

        //GENERAL DATATABLE
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
        });
    });

        $("#workflowsReload").click(function() {
                reload();
    });
});


