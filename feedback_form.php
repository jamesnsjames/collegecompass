<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Give Feedback</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .feedback-form-container {
            max-width: 600px;
            margin: 30px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="container feedback-form-container">
        <h2 class="mb-4"><i class="fas fa-comment-dots me-2"></i> Give Your Feedback</h2>
        <form action="submit_feedback.php" method="POST">
            <div class="mb-3">
                <label for="college_id" class="form-label">Select College</label>
                <select class="form-select" id="college_id" name="college_id" required>
                    <option value="">Select a College</option>
                    <?php
                    // Database connection (same as your other PHP files)
                    $servername = "localhost";
                    $username = "root";
                    $password = "";
                    $dbname = "fees_db";

                    $conn = new mysqli($servername, $username, $password, $dbname);
                    if ($conn->connect_error) {
                        die("Connection failed: " . $conn->connect_error);
                    }

                    $sql = "SELECT id, name FROM colleges ORDER BY name";
                    $result = $conn->query($sql);

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='" . $row["id"] . "'>" . htmlspecialchars($row["name"]) . "</option>";
                        }
                    }
                    $conn->close();
                    ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="feedback_by" class="form-label">Your Name (Optional, leave blank for Anonymous)</label>
                <input type="text" class="form-control" id="feedback_by" name="feedback_by">
            </div>
            <div class="mb-3">
                <label for="feedback_text" class="form-label">Your Feedback</label>
                <textarea class="form-control" id="feedback_text" name="feedback_text" rows="5" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-2"></i> Submit Feedback</button>
        </form>
        <div class="mt-3">
            <a href="index.html" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i> Back to Home</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
