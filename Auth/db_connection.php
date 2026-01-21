<?php
session_start();

$host = "localhost";
$username = "root";
$password = "";
$dbname = "digital_school_management_system";

$conn = mysqli_connect($host, $username, $password, $dbname);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
?>