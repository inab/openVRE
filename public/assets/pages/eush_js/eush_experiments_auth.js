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

    project_id = getUrlParameter('project_id');
    subject_id = getUrlParameter('subject_id');
    console.log(project_id)
    console.log(subject_id)

    var urlJSON = 'applib/eush_eurobioimaging.php?action=getAuthorizedExperiments&project_id='+project_id+'&subject_id='+subject_id+''
    var table = []

    $.ajax({
        type: 'POST',
        url: urlJSON,
        async: false,
        data: {'action': 'getAuthorizedExperiments'}
        }).done(function(json) {
            var experiment_data = json;
            var exp_parsed = JSON.parse(experiment_data)
            var array = null
            Object.keys(exp_parsed).forEach(function(key) {
                array = exp_parsed[key];
            })
            
            console.log(array)
            array = array["Result"]

            for (var i = 0; i < array.length; i++) {
                // Experiment ID
                id = array[i]["ID"]
                // Experiment Date
                date = array[i]["insert_date"]
            
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
        sites.push('applib/eush_eurobioimaging.php?action=getAuthorizedExperimentsFormat&project_id='+project_id+'&subject_id='+subject_id+'&experiment_id='+table[i].experimentID+'')
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
    var sub_children = null
    var scan_list = []
    console.log("PARSED FORMAT:")
    console.log(exp_format_parsed)
    var children_list = exp_format_parsed["items"][0]["children"][0]["items"]
    var total_size = 0
    var size_list = []
    var formats_list = []
    console.log("children_list")
    console.log(children_list)
    
    for(var i = 0; i < children_list.length; i++) {
        console.log("children_list[i]")
        console.log(children_list[i])

        sub_children = children_list[i]["children"][0]["items"]
        console.log("sub_children")

        // This one for a non-harcoded version....

        for(var j = 0; j < sub_children.length; j++) {
            uri = sub_children[j]["data_fields"]["URI"]
            console.log("uri")
            console.log(uri)
            scan_list.push(uri.split("/")[8]);
            size_list.push(sub_children[j]["data_fields"]["file_size"])
            total_size += size_list[j]
            formats_list.push(sub_children[j]["data_fields"]["label"])
            console.log("uri")
            console.log(uri)
            console.log("scan_list")
            console.log(scan_list)
            console.log("formats_list")
            console.log(formats_list)
            console.log("size list")
            console.log(size_list)
        }
    }

    // Do we want to display all scans names? 
    scan_list_formatted = scan_list.join("<br />")
    console.log("scan_list_formatted")
    console.log(scan_list_formatted)
    
    // Or just the total scans
    var children_list_total = children_list.length

	var urlJSON_files = 'applib/getData.php?uploadType=repository&data_type=bioimage&compressed=ZIP&file_type='+formats_list[0]+'&url='+encodeURI('https://xnat.bmia.nl/data/archive/projects/'+project_id+'/subjects/'+subject_id+'/experiments/'+id+'/scans/ALL/files?format=zip')
    console.log("1: ", urlJSON_files)
    var options = {
        "experimentID" : id,
        "date" : date,
        "size" : total_size,
        "format" : scan_list_formatted,
        "scans" : children_list_total,
        "download" : urlJSON_files,
        "ajax": {
            url: 'applib/eush_eurobioimaging.php?action=getAuthorizedExperiments&project_id='+project_id+'&subject_id='+subject_id+'&experiment_id='+id+'',
            dataSrc: function (jsonData) {
                //console.log(jsonData)
                var table = []
                    
                obj = { "experimentID" : id, 
                        "date" : date,
                        "size" : total_size, 
                        "format" : scan_list_formatted,
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
        $('#experimentsTableAuth').DataTable(options);
        });

        $("#workflowsReload").click(function() {
                reload();
        });
});


