<?php 
$servername = "yourdomain.com";
$username = "username";
$password = "password";
$db = "sophiaha_experiment";

$sql = new mysqli($servername, $username, $password);
$sql->connect($servername, $username, $password, $db);

// Check connection
if ($sql->connect_error) {
    die("SQL Connection failed: " . $sql->connect_error);
}
?>