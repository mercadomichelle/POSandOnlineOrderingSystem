<?php

$host = "localhost";
$user = "root";
$password = "";
$db = "system_db";

$mysqli = new mysqli($host, $user, $password, $db);

if ($mysqli->connect_error) {
    error_log("Database connection error: " . $mysqli->connect_error);
    die("Error connecting to the mysqlibase. Please check the logs for more details.");
}

?>
