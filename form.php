<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "fees_db"; // Replace with your database name

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch colleges for the dropdown
$sql = "SELECT id, name FROM colleges";
$result = $conn->query($sql);

if (!$result) {
    die("Query failed: " . $conn->error);
}

// Insert data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $college_id = filter_input(INPUT_POST, 'college_id', FILTER_VALIDATE_INT);
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $fees = filter_input(INPUT_POST, 'fees', FILTER_VALIDATE_FLOAT);

    if (!$college_id || !$email || !$fees) {
        echo "<div class='alert alert-danger text-center'>Invalid input data.</div>";
    } else {
        $created_at = date('Y-m-d H:i:s');

        $stmt = $conn->prepare("INSERT INTO college_alerts (id, email, fees, created_at) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $college_id, $email, $fees, $created_at);

        if ($stmt->execute()) {
            echo "<div class='alert alert-success text-center'>Data inserted successfully!</div>";
        } else {
            echo "<div class='alert alert-danger text-center'>Error: " . $stmt->error . "</div>";
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>College Alert Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container {
            max-width: 600px;
            margin-top: 50px;
            padding: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #2c3e50;
        }
        label {
            font-weight: bold;
        }
        .btn-primary {
            background-color: #3498db;
            border: none;
        }
        .btn-primary:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>

<div class="container">
    <h1 class="text-center mb-4"><i class="fas fa-bell"></i> College Alert Form</h1>

    <form method="POST" action="form.php">
        <!-- College Dropdown -->
        <div class="mb-3">
            <label for="college_id" class="form-label">Choose College:</label>
            <select id="college_id" name="college_id" class="form-select" required>
                <option value="">Select a College</option>
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<option value='" . $row['id'] . "'>" . htmlspecialchars($row['name']) . "</option>";
                    }
                } else {
                    echo "<option value=''>No colleges available</option>";
                }
                ?>
            </select>
        </div>

        <!-- Email Input -->
        <div class="mb-3">
            <label for="email" class="form-label">Email ID:</label>
            <input type="email" id="email" name="email" class="form-control" required placeholder="Enter your email">
        </div>

        <!-- Fees Input -->
        <div class="mb-3">
            <label for="fees" class="form-label">Fees:</label>
            <input type="number" id="fees" name="fees" class="form-control" required placeholder="Enter fees amount">
        </div>

        <!-- Submit Button -->
        <div class="text-center">
            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-paper-plane"></i> Submit</button>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>

</body>
</html>

