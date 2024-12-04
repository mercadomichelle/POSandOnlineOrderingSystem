<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$host = "localhost";
$user = "u883064514_admin";
$password = "~Ew5V+?ZYYVX";
$db = "u883064514_system_db";

$mysqli = new mysqli($host, $user, $password, $db);

if ($mysqli->connect_error) {
    error_log("Database connection error: " . $mysqli->connect_error);
    die("Error connecting to the mysqlibase. Please check the logs for more details.");
}
?>