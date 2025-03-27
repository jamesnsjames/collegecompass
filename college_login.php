<?php
header('Content-Type: application/json');
session_start();

// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'fees_db';

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$collegeId = $conn->real_escape_string($data['collegeId'] ?? '');
$password = $data['password'] ?? '';

// Verify credentials
$stmt = $conn->prepare("SELECT * FROM college_logins WHERE college_id = ? AND password = ?");
$stmt->bind_param("is", $collegeId, $password);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $_SESSION['college_id'] = $collegeId;
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
$conn->close();
?>
