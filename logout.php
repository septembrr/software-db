<?php 
include "components/pwd.php";
?>
<!DOCTYPE html PUBLIC "-\/\/W3C\/\/DTD XHTML 1.0 Strict\/\/EN" "http:\/\/www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html lang="en-US" xml:lang="en" xmlns:msxsl="urn:schemas-microsoft-com:xslt" xmlns:igxlib="urn:igxlibns">
<head>
    <meta name="google-signin-client_id" content="<?= $google_client_id ?>">
</head>
<body>

<script>

    function init() {

        var clientobject = { client_id: $google_client_id };

        gapi.load('auth2', function() {
            gapi.auth2.init(clientobject).then(function(){
                var auth2 = gapi.auth2.getAuthInstance();
                auth2.signOut();
            });
        });
    }
    
</script>
<script src="https://apis.google.com/js/platform.js?onload=init"></script>
<?php

// Start session
session_start();

// Unset session variables
session_unset();

// Destroy session
session_destroy();

// Redirect back to login page
header("Location: login.php");

?>
</body>
</html>