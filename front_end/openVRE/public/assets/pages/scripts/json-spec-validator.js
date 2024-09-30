var ComponentsCodeEditors = function () {
    
    var handleDemo = function () {
        var myTextArea = document.getElementById('code_editor');
        myCodeMirror = CodeMirror.fromTextArea(myTextArea, {
        	lineNumbers: true,
          matchBrackets: true,
          styleActiveLine: true,
       		autoCloseBrackets: true,
        	mode: "application/ld+json",
        	lineWrapping: true,
					gutters: ["CodeMirror-lint-markers"],
    			lint: true,
        });

				/*if(getErrorLine(myCodeMirror.getValue()) != 0) {
					jumpToLine(getErrorLine(myCodeMirror.getValue()), myCodeMirror);
					$("#json-val-but").prop("disabled", true); 
				} else { 
					$("#json-val-but").prop("disabled", false); 
				}*/

				myCodeMirror.on("change", function() {
					console.log("change");
					if(getErrorLine(myCodeMirror.getValue()) != 0) {
						//jumpToLine(getErrorLine(myCodeMirror.getValue()), myCodeMirror);
						$("#json-val-but").prop("disabled", true);
						$("#json-val-subm").prop("disabled", true); 
					} else { 
						$("#json-val-but").prop("disabled", false); 
						$("#json-val-subm").prop("disabled", false);
					}
				});

				function getErrorLine(cm) {
					var errors = CodeMirror.lint.json(cm);
					if(errors.length > 0) {
						var errLine = errors[0].message.split('\n')[0].slice(0, -1).split(' ').slice(-1)[0];
						return errLine;
					} else {
						return 0;
					}
				}

				function jumpToLine(i, editor) {

					/*var numLines = Math.round(editor.getWrapperElement().offsetHeight / 20);

					var gap = Math.round(numLines / 2);

					if(i < (editor.lineCount() - (numLines / 2))) var finalLine = i - gap;
					else var finalLine = editor.lineCount();*/

					finalLine = i;

					editor.setCursor(finalLine);
				}

    }

    return {
        //main function to initiate the module
        init: function () {
            handleDemo();
        }
    };

}();

var myCodeMirror;
var baseURL = $('#base-url').val();

jQuery(document).ready(function() {    
   ComponentsCodeEditors.init(); 

	$("#json-val-but").on("click", function() {

		$.ajax({
      type: "POST",
      url: baseURL + "applib/JSONSchemaSpecValidator.php",
			data: "json=" + myCodeMirror.getValue(),
			processData: false,
      success: function(data) {
				
				var obj = JSON.parse(data);

				$('#modalJSONSchema .modal-body').html(obj.msg);
  			$('#modalJSONSchema').modal({ show: 'true' });

  			//if(obj.status == 1) $("#json-val-subm").prop("disabled", false);

			}
	
     });

	});

});
