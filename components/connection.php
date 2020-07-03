<?php 

// Import components
include "pwd.php";

//connect to designhub database or display error
$mysqli = new mysqli($db_host, $db_username, $db_password, $db_name);
if($mysqli->connect_errno)
{
	echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
}
$mysqli->set_charset("utf8");

?>