<?php
/*
AUTHENTICATE
Called via xhr request from the login.php page
On first login, connect to database and add Google ID
Otherwise, verify Google ID through token.
*/

// Import components
include "components/pwd.php";
include "components/connection.php";
include "components/init-actions.php";

// Google Sign In PHP library
require_once 'google-sign-in-2.2.3/vendor/autoload.php';

// Get $id_token via HTTPS POST.
$id_token = $_POST['idtoken'];

$client = new Google_Client(['client_id' => $google_client_id]);  // Specify the CLIENT_ID of the app that accesses the backend
$payload = $client->verifyIdToken($id_token);

if ($payload) {

    $google_id_query = "SELECT DISTINCT ".$db_prefix."Users.google_id, ".$db_prefix."Users.role FROM ".$db_prefix."Users WHERE ".$db_prefix."Users.google_id = '" . $payload['sub'] . "'";
    $email_query = "SELECT DISTINCT ".$db_prefix."Users.id, ".$db_prefix."Users.email, ".$db_prefix."Users.role FROM ".$db_prefix."Users WHERE ".$db_prefix."Users.email = '". $payload['email'] ."'";

    if (($google_id_result = $mysqli->query($google_id_query)) && ($google_id_result->num_rows > 0)) {
        //start session
        $_SESSION['access_token'] = $id_token;

        $user_row = $google_id_result->fetch_assoc();
        $_SESSION['role'] = $user_row['role'];

    } else if (($email_result = $mysqli->query($email_query)) && ($email_result->num_rows > 0)) {
        //start session
        $_SESSION['access_token'] = $id_token;

        // Add google id to database
        $user_row = $email_result->fetch_assoc();
        $user_id = $user_row['id'];
        $_SESSION['role'] = $user_row['role'];
        
        $update_query = "UPDATE ".$db_prefix."Users SET ".$db_prefix."Users.google_id = '".$payload['sub']."' WHERE ".$db_prefix."Users.id = " . $user_id;
        if ($mysqli->query($update_query)) {
            echo 'Success';
        } else {
            echo 'Login Error';
        }
    } else {
        echo 'Invalid';
    }
} else {
    echo "Invalid";
}

include "components/close-actions.php";

?>