<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Database connection
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "fees_db";

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $college_id = intval($_POST['college_id']);
    $feedback_text = $conn->real_escape_string($_POST['feedback_text']);
    $feedback_by = empty($_POST['feedback_by']) ? 'Anonymous' : $conn->real_escape_string($_POST['feedback_by']);

    $sql = "INSERT INTO college_feedback (college_id, feedback_text, feedback_by) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $college_id, $feedback_text, $feedback_by);

    if ($stmt->execute()) {
        $success = true;
        $message = "Feedback submitted successfully!";
    } else {
        $success = false;
        $message = "Error submitting feedback: " . $conn->error;
    }

    $stmt->close();
    $conn->close();

    // Redirect back to the feedback form with a message
    header("Location: feedback_form.php?submission=" . ($success ? 'success' : 'error') . "&message=" . urlencode($message));
    exit();
} else {
    // If accessed directly without POST request
    header("Location: index.html");
    exit();
}
?>
