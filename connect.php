<?php
// Database connection details
$servername = "localhost"; // Change this if your database is hosted elsewhere
$username = "root"; // Change this to your database username
$password = ""; // Change this to your database password
$database = "pharma"; // Change this to your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    // Add role column to users table if it doesn't exist
    $checkColumn = "SHOW COLUMNS FROM users LIKE 'role'";
    $result = $conn->query($checkColumn);
    
    if ($result->num_rows === 0) {
        $addColumn = "ALTER TABLE users ADD COLUMN role ENUM('admin', 'staff', 'user') DEFAULT 'user'";
        $conn->query($addColumn);
    }
}
?>
