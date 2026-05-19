<?php
require __DIR__ . "/../config/bootstrap.php";

// unset SESSION[User]
logoutAnon();

// login through external auth server
?>
<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8" />
</head>

<body style="background:#006b8f;color:white;font-size: 1.1em;">
  <form id="loginToken-form" action="applib/loginToken.php" method="post"></form>
</body>

<script>
  document.getElementById("loginToken-form").submit();
</script>

</html>
