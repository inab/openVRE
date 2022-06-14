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
        console.log(project_id)

        var options = {
            "project_id" : project_id,
            "ajax": {
                url: 'applib/eush_eurobioimaging.php?action=getAuthorizedSubjects&project_id='+project_id+'',
                dataSrc: function (jsonData) {
                    // Here we parse JSON objects.
                    //console.log("jsonData: ", jsonData)
                    parsed = JSON.parse(jsonData)
                    //console.log("parsed; ", parsed)
                    var array = null
                    var table = []
                    Object.keys(parsed).forEach(function(key) {
                        array = parsed[key];
                    })
                    array = array["Result"]
                    //console.log("array: ", array)
                    for (var i = 0; i < array.length; i++) {
                        console.log(array[i]["ID"])
                        // Subject ID
                        id = array[i]["ID"]
                        // Project Description
                        label = array[i]["label"]
                        // Subject Date
                        date = array[i]["insert_date"]
                    
                        obj = { "subjectID" : id, 
                                "label" : label, 
                                "date" : date
                        }        
                        table.push(obj)
                    }
                    return table;
                }
            },
            autoWidth: false,
            "columns" : [
                { "data" : "subjectID"},
                { "data" : "label" },
                { "data" : "date" }
            ],
            "columnDefs": [
                //targets are the number of corresponding columns
                { "title": "subjectID", "targets": 0 },
                { "title": "Label", "targets": 1 },
                { "title": "Insert Date", "targets": 2 },
                { render: function(data, type, row) {
                    // Here we should put an href to the specific project.
                    return '<a href="/vre/getdata/eush_bioimages/eush_experiments_auth.php?project_id='+project_id+'&subject_id='+row.subjectID+'"> '+row.subjectID+' </a>'
                }, "targets": 0}
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
        $('#subjectsTableAuth').DataTable(options);
        });

        $("#workflowsReload").click(function() {
                reload();
    });
});


