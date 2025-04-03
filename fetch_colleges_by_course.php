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
    die(json_encode(['error' => "Connection failed: " . $conn->connect_error]));
}

// Get course ID from GET parameter
$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

// Fetch colleges offering the specific course, including the fees
$sql = "SELECT DISTINCT c.id, c.name, c.rating, c.fees
           FROM colleges c
           JOIN college_courses cc ON c.id = cc.college_id
           WHERE cc.course_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $course_id);
$stmt->execute();
$result = $stmt->get_result();

$colleges = [];
while ($row = $result->fetch_assoc()) {
    $colleges[] = $row;
}

// Return data as JSON
header('Content-Type: application/json');
echo json_encode($colleges);

$stmt->close();
$conn->close();
?>
