<?php require __DIR__ . "/../../config/bootstrap.php"; ?>


<html>

<head>
    <base href="<?php echo $GLOBALS['BASEURL']; ?>" />
</head>

<body>
    <p>Application not correctly be loaded. Please, check your advertisement blocker set up</p>
    <input type="hidden" id="base-url" value="<?php echo $GLOBALS['BASEURL']; ?>" />
    <script src="assets/global/plugins/jquery.min.js" type="text/javascript"></script>
    <script src="assets/global/plugins/js.cookie.min.js" type="text/javascript"></script>
    <script src="assets/pages/scripts/cookie-header.js" type="text/javascript"></script>
</body>

</html>
