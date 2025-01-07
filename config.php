<?php
// config.php

// Database connection settings XAMPP
$servername = "localhost";
$username = "root";
$password = ""; 
$dbname = "feur-tapping-db"; 

// mySQL Workbench Database connection

// $servername = "192.168.10.93";
// $username = "ryaunp ";
// $password = "T3ch@rc1"; 
// $dbname = "feur-tapping-db"; 

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
