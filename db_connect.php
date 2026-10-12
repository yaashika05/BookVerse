<?php
// db_connect.php
$host = "localhost";
$username = "root";
$password = "";
$database = "bookstore1";

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>