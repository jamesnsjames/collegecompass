<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = ""; 
$dbname = "fees_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch courses from the database
$sql = "SELECT id, location FROM locations ORDER BY location";
$result = $conn->query($sql);
$courses = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }
}

// Return data as JSON
header('Content-Type: application/json');
echo json_encode($courses);

$conn->close();
?>
