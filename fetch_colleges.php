<?php
// fetch_colleges.php

// Database connection
$servername = "localhost";
$username = "root"; // Default XAMPP username
$password = ""; // Default XAMPP password
$dbname = "fees_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch colleges from the database
$sql = "SELECT id, name, rating,fees FROM colleges";
$result = $conn->query($sql);

$colleges = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $colleges[] = $row;
    }
}

// Return data as JSON
header('Content-Type: application/json');
echo json_encode($colleges);

$conn->close();
?>
