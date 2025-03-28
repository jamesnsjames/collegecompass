<?php
// Replace with your actual database credentials
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

$sql = "SELECT college_id, username FROM college_logins";
$result = $conn->query($sql);

$colleges = array();
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $colleges[] = $row;
    }
}

$conn->close();

// Return the college data as JSON
header('Content-Type: application/json');
echo json_encode($colleges);
?>
