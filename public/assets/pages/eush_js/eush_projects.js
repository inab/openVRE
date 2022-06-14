$(document).ready(function() {

        $("#errorsTool").hide();

        console.log("ddd");
    
        //get info on the currently logged user
    	var urlJSON = "applib/eush_eurobioimaging.php";
    	$.ajax({
                type: 'POST',
                url: urlJSON,
                data: {'action': 'getUser'}
        }).done(function(data) {
        var currentUser = data;

        // HARDCODED CREDENTIALS: HERE WE HAVE TO ADD A MECHANISM TO STORE USER CREDENTIALS INTO THE VRE.
        
        //GENERAL DATATABLE
        $('#workflowsTable').DataTable( {
            "ajax": {
                url: 'applib/eush_eurobioimaging.php?action=getProjects',
                dataSrc: function (jsonData) {
                    //console.log("jsonData")
                    //console.log(jsonData)
                    // Here we parse JSON objects.
                    parsed = JSON.parse(jsonData)
                    var array = null
                    var table = []
                    Object.keys(parsed).forEach(function(key) {
                        array = parsed[key];
                    })
                    array = array["Result"]
                    //console.log(array)
                    for (var i = 0; i < array.length; i++) {
                        // Project ID
                        id = array[i]["id"]
                        // Project Description
                        description = array[i]["description"]
                        // Project Name
                        name = array[i]["name"]
                        // Project PI
                        pi = array[i]["pi"]

                        
                        if(array[i]["project_access"] !== "public") {
                            continue
                        }
                        if(array[i]["id"] === "stwstrategymmd" || 
                           array[i]["id"] === "stwstrategyps3") {
                            continue
                        }
                        //console.log(id)
                        // Project Access
                        access = array[i]["project_access"]
                    
                        obj = { "id" : id, 
                                "description" : description, 
                                "name" : name, 
                                "pi" : pi,
                                "access" : access
                        }        
                        table.push(obj)
                    }
                    return table;
                }
            },
            autoWidth: false,
            "columns" : [
                { "data" : "id"},
                { "data" : "description" },
                { "data" : "name" },
                { "data" : "pi" },
                { "data" : "access"}
            ],
            "columnDefs": [
                //targets are the number of corresponding columns
                { "title": "ID", "targets": 0 },
                { "title": "Description", "targets": 1 },
                { "title": "Project Name", "targets": 2 },
                { "title": "Principal Inv.", "targets": 3 },
                { "title": "Access", "targets": 4 },
                { render: function(data, type, row) {
                    // Here we should put an href to the specific project.
                    return '<a href="/vre/getdata/eush_bioimages/eush_subjects.php?project_id='+row.id+'"> '+row.id+' </a>'
                }, "targets": 0}
            ]
        });
    //});
        //GENERAL DATATABLE
        /*$('#workflowsTable2').DataTable( {
            "ajax": {
                url: 'applib/eush_eurobioimaging.php?action=getProjects',
                dataSrc: function (jsonData) {
                    //console.log("jsonData")
                    //console.log(jsonData)
                    // Here we parse JSON objects.
                    parsed = JSON.parse(jsonData)
                    var array = null
                    var table = []
                    Object.keys(parsed).forEach(function(key) {
                        array = parsed[key];
                    })
                    array = array["Result"]
                    //console.log(array)
                    for (var i = 0; i < array.length; i++) {
                        // Project ID
                        id = array[i]["id"]
                        // Project Description
                        description = array[i]["description"]
                        // Project Name
                        name = array[i]["name"]
                        // Project PI
                        pi = array[i]["pi"]

                        
                        if(array[i]["project_access"] === "public") {
                            continue
                        }
                        if(array[i]["id"] === "stwstrategymmd" || 
                           array[i]["id"] === "stwstrategyps3") {
                            continue
                        }
                        //console.log(id)
                        // Project Access
                        access = array[i]["project_access"]
                    
                        obj = { "id" : id, 
                                "description" : description, 
                                "name" : name, 
                                "pi" : pi,
                                "access" : access
                        }        
                        table.push(obj)
                    }
                    return table;
                }
            },
            autoWidth: false,
            "columns" : [
                { "data" : "id"},
                { "data" : "description" },
                { "data" : "name" },
                { "data" : "pi" },
                { "data" : "access"}
            ],
            "columnDefs": [
                //targets are the number of corresponding columns
                { "title": "ID", "targets": 0 },
                { "title": "Description", "targets": 1 },
                { "title": "Project Name", "targets": 2 },
                { "title": "Principal Inv.", "targets": 3 },
                { "title": "Access", "targets": 4 },
                { render: function(data, type, row) {
                    // Here we should put an href to the specific project.
                    return '<a href="/vre/getdata/eush_bioimages/eush_subjects.php?project_id='+row.id+'"> '+row.id+' </a>'
                }, "targets": 0}
            ]
        });*/
    });

    $('#workflowsTable3').DataTable( {
        "ajax": {
            url: 'applib/eush_eurobioimaging.php?action=getAuthorizedProjects',
            dataSrc: function (jsonData) {
                //console.log("jsonData")
                //console.log(jsonData)
                // Here we parse JSON objects.
                console.log(jsonData);
                parsed = JSON.parse(jsonData)
                var array = null
                var table = []
                console.log(parsed)
                Object.keys(parsed).forEach(function(key) {
                    array = parsed[key];
                })
                array = array["Result"];
                for (var i = 0; i < array.length; i++) {
                    // Project ID
                    id = array[i]["id"]
                    // Project Description
                    description = array[i]["description"]
                    // Project Name
                    name = array[i]["name"]
                    // Project PI
                    pi = array[i]["pi"]

                    // Project Access
                    access = array[i]["project_access"]
                
                    obj = { "id" : id, 
                            "description" : description, 
                            "name" : name, 
                            "pi" : pi,
                            "access" : access
                    }        
                    table.push(obj)
                }
                return table;
            }
        },
        autoWidth: false,
        "columns" : [
            { "data" : "id"},
            { "data" : "description" },
            { "data" : "name" },
            { "data" : "pi" },
            { "data" : "access"}
        ],
        "columnDefs": [
            //targets are the number of corresponding columns
            { "title": "ID", "targets": 0 },
            { "title": "Description", "targets": 1 },
            { "title": "Project Name", "targets": 2 },
            { "title": "Principal Inv.", "targets": 3 },
            { "title": "Access", "targets": 4 },
            { render: function(data, type, row) {
                // Here we should put an href to the specific project.
                return '<a href="/vre/getdata/eush_bioimages/eush_subjects_auth.php?project_id='+row.id+'"> '+row.id+' </a>'
            }, "targets": 0}
        ]
    });

        $("#workflowsReload").click(function() {
                reload();
    });
});


