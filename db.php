<?php
$host = "localhost";
$username = "root";      // change if needed
$password = "";          // change if needed
$database = "bago_traffic_db";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>
