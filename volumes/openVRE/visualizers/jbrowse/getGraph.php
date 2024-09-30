<?php

require "../../phplib/genlibraries.php";

redirectOutside();

$user = $_SESSION['User']['id'];

?>

<html>
<header>

<meta http-equiv="pragma" content="no-cache" />

        <!-- Drupal mmb style -->
        <link rel="stylesheet" href="css/style.css">

        <!-- Own style -->
        <link rel="stylesheet" href="css/estil.css">

        <!-- Information style -->
        <link rel="stylesheet" href="css/information.css">
</header>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>

<script>

function getQueryVariables(user) {                                                                                                                                                                                    
  var query = window.location.search.substring(1);                                                                                                                                                                
  var vars = query.split("&");                                                                                                                                                                                    
  var params_array = {};

  for (var i=0;i<vars.length;i++) {                                                                                                                                                                               
    var pair = vars[i].split("=");                                                                                                                                                                                
    var key = pair[0];
    var value = pair[1];
    params_array[key] = value;
  } 
  params_array['userId'] = user;
  return (params_array);
}


function loadingAjax(div_id,user)
{

    var params_ajax = getQueryVariables(user);

    $("#"+div_id).html('<center><img src="images/loading.gif"><br><br><font color="#006699" face="arial" size="4"><b>Loading ...<br><br>Please Wait ...</b></font></center>');
    $.ajax({
        type: "POST",
        url: "getGraph2.php",
        data: params_ajax,
        success: function(msg){
            $("#"+div_id).html(msg);
        }
    });
}

</script>

<body onload="loadingAjax('myDiv','<?php echo $_SESSION['User']['id']; ?>')">


<h3 style="color:#006080;">Nucleosome Dynamics</h3>
<div class="metaImageSection" style="height:80%">
<div id='myDiv' style="display: flex;flex-wrap: wrap;padding: 15px;border: 1px solid #ddd;border-collapse: separate;-moz-border-radius: 10px;border-radius: 10px;position: relative;background-color: white;justify-content: center;height:80%">


<br/>

</div>

</div>
</body>
</html>
