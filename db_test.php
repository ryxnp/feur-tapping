<?php
// Database connection details
$servername = "192.168.10.93"; // Your server IP
$username = "ryaunp";           // Your database username
$password = "T3ch@rc1";         // Your database password
$dbname = "feur-tapping-db";    // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection status
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    echo "Connected successfully to the database '$dbname'.<br>";
}

// Check if the connection is active
if ($conn->ping()) {
    echo "Connection to MySQL database is active.<br>";
} else {
    echo "Connection to MySQL database is closed.<br>";
}

// Example of an update operation (modify this as needed)
$sql = "UPDATE your_table_name SET column_name='new_value' WHERE condition";
if ($conn->query($sql) === TRUE) {
    echo "Record updated successfully.";
} else {
    echo "Error updating record: " . $conn->error;
}

// Close the connection
$conn->close();
?>
