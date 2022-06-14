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

        var ega_outbox = []
        $.ajax({
            url: 'applib/eush_ega.php?action=listDatasets',           
            type: "POST",
            dataType: "json",
            async: false,
            success: function(data){
                for(var w = 0; w < data.length; w++){
                    console.log(data[w])
                    //ega_outbox.push(data[w].split("/")[1])
                    ega_outbox.push(data[w])
                }
            }
        });  

        console.log("EGA datasets")
        console.log(ega_outbox)


        var ds_sites = []

        for (var i = 0; i < ega_outbox.length; i++) {
            console.log("ega_outbox[i]")
            console.log(ega_outbox[i])
            ds_sites.push('applib/eush_ega.php?action=getEGADatasetsFromDSis&ds_id='+ega_outbox[i]+'')
        }

        console.log("ds_sites:")
        console.log(ds_sites)

        // These two hardcoded elements should be removed (and ega_outbox two last elements will come from Laia's script call)
        
        // Hardcoded ds_sites
        ds_sites.push("applib/eush_ega.php?action=getEGADatasetsFromDSid&ds_id=EGAD00000000001")
        ds_sites.push("applib/eush_ega.php?action=getEGADatasetsFromDSid&ds_id=EGAD00000000002")
        // Add ids to ega_outbox
        ega_outbox.push("EGAD00000000001")
        ega_outbox.push("EGAD00000000002")
        console.log("ds_sites after")
        console.log(ds_sites)
        
        var ds_metadata = []
        for (var q = 0; q < ds_sites.length; q++) {
            $.ajax({
                url: ds_sites[q],
                type: "POST",
                dataType: "json",
                async: false,
                success: function(data){
                    console.log("DATA!")
                    console.log(data)
                    if(Object.keys(data).length === 0){
                        ds_metadata.push(null)
                    } else {
                        ds_metadata.push(JSON.parse(data).response.result[0])
                    }  
                }
            });    
        }
        console.log("ds_metadata")
        console.log(ds_metadata)
        
        //GENERAL DATATABLE
        $('#egaTable').DataTable( {
            "ajax": {
                url: 'applib/eush_ega.php?action=getEGAFiles',
                dataSrc: function (jsonData) {
                    var table = []
                    for (var i = 0; i < ds_metadata.length; i++) {

                        if(ds_metadata[i] != null) {
                            console.log("nope")
                            datasetEGAId = ds_metadata[i]["egaStableId"]
                            datasetDescription = ds_metadata[i]["description"]
                            datasetTechnology = ds_metadata[i]["technology"]
                            datasetSamples = ds_metadata[i]["numSamples"]
                        } else {
                            console.log("yes")
                            datasetEGAId = ega_outbox[i]
                            datasetDescription = ""
                            datasetTechnology = ""
                            datasetSamples = ""
                        }

                        obj = { "dataset_id" : datasetEGAId, 
                                "dataset_description" : datasetDescription, 
                                "dataset_technology" : datasetTechnology,
                                "dataset_samples" : datasetSamples
                        }   

                        table.push(obj)
                    }
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
                { "title": "Samples", "targets": 3 },
                { render: function(data, type, row) {
                    // Here we should put an href to the specific project.
                    return '<a href="/vre/getdata/eush_ega/eush_ega_datasets.php?ds_id='+row.dataset_id+'"> '+row.dataset_id+' </a>'
                }, "targets": 0}
            ]        
        });
    });

        $("#workflowsReload").click(function() {
                reload();
    });
});


