var gitURL = "https://raw.githubusercontent.com/Acivico/jsonSchema/main/basic_file.json";

function showForm(fn){
    $.ajax({
        type: 'POST',
        url: "applib/editFile_v2.php",
        data: {"fn": fn ,"action": "getFileInfo" }
    }).done(function (data) {                                            
        fileInfo = JSON.parse(data);
        
    }).done(function(){         
        
        var pathDir;
        $.ajax({
            type: 'POST',
            url: "applib/editFile_v2.php",
            data: {"dir": fileInfo['parentDir'],"basePath":fileInfo['path'], "action":"getPathDir"}
            
        }).done(function(data){
            data = JSON.parse(data);
            pathDir = data[0];                
            
            var i = pathDir.indexOf("/") + 1;
            pathDir = pathDir.substr(i,pathDir.length);
            
        }).done(function(){
            editor.getEditor("root._id").setValue(fileInfo['_id']);            
            editor.getEditor("root.owner").setValue(fileInfo['owner']);
            editor.getEditor("root.parentDir").setValue(pathDir);
            editor.getEditor("root.path").setValue(fileInfo['path']);
            editor.getEditor("root.size").setValue(fileInfo['size']);
            editor.getEditor("root.expiration").setValue(fileInfo['expiration']);
            editor.getEditor("root.mtime").setValue(fileInfo['mtime']);
            editor.getEditor("root.project").setValue(fileInfo['project']);
            editor.getEditor("root.format").setValue(fileInfo['format']);
            editor.getEditor("root.description").setValue(fileInfo['description']);                    
            editor.getEditor("root.sources").setValue(fileInfo['sources']);                    
            editor.getEditor("root.type").setValue(fileInfo['type']);                    
            editor.getEditor("root.validated").setValue(fileInfo['validated']);                    
            editor.getEditor("root.data_type").setValue(fileInfo['data_type']);                    
            editor.getEditor("root.visible").setValue(fileInfo['visible']);                    
            editor.getEditor("root.compressed").setValue(fileInfo['compressed']);
        })

    })
}
$(document).ready(function () {
    var fileInfo = "{}";   
    var form = document.getElementsByClassName('idx');
    
    for (var i = 0; i < form.length; i++){
        (function(index){
            form[index].addEventListener("click", function(){
                $('input[type="radio"].idx')
                    showForm($(this).val());
            })
        })(i);
    }
    var fn = document.getElementById('idx').value;    

    $.getJSON(gitURL, function (data) {
        schema = data;
    }).done(function () {
        editor = new JSONEditor(document.getElementById('editor_holder'), {
            theme: 'bootstrap4',
            schema: schema,

            disable_collapse: true, 
            disable_edit_json: true,
            disable_properties: true
        });
        showForm(fn);

        $("#uploadMetaData").change(function(event){
            var uploadedFile = event.target.files[0]; 
            
            if (uploadedFile) {
                
                    var readFile = new FileReader();
                    readFile.onload = function(e) { 
                    var contents = e.target.result;
                    try{
                        var json = JSON.parse(contents);
                    }catch(e){
                        alert("The file must be a JSON");
                    }
                    
                    $.ajax({
                        type: 'POST',
                        url: "applib/editFile_v2.php",
                        data: {"action":"uploadMetaData","file":json}
                    }).done(function(data){
                        if(data == "true"){
                            showForm(json["_id"]);
                        }else{
                            console.log(data);
                        }
                    })
                    };                

                readFile.readAsText(uploadedFile);                
            } else { 
                console.log("Failed to load file");
            }
        });

    })
    document.getElementById("save").addEventListener("click", function () {

        editor.getEditor("root.parentDir").setValue(fileInfo['parentDir']);

        var path = fileInfo['path'];
        const errors = editor.validate();

        if (errors.length) {
            // errors is an array of objects, each with a `path`, `property`, and `message` parameter
            // `property` is the schema keyword that triggered the validation error (e.g. "minLength")
            // `path` is a dot separated path into the JSON object (e.g. "root.path.to.field")
            alert('There are some required fields!');
            console.log(errors);
        }
        else {
            // It's valid!

            var formData = JSON.stringify(editor.getValue());

            $.ajax({
                type: 'POST',
                url: "applib/editFile_v2.php",
                data: { 'data': formData,'path': path,"action": "save" },
                success: function (respuesta) {
                    console.log(respuesta);
                },
                error: function () {
                    console.log("No se ha podido obtener la información");
                }
            })
        }
    })
});