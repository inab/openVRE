<html>
<head>
  <meta charset="utf-8" />
</head>
<?php

require __DIR__."/../config/bootstrap.php";

// unset SESSION[User]
logoutAnon();

// login through extenal auth server
?>
<body style="background:#006b8f;color:white;font-size: 1.1em;">
<form id="loginToken-form" action="applib/loginToken.php" method="post"></form>
</body>

<script>
document.getElementById("loginToken-form").submit();
</script>
</html>
