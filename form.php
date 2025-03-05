<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Step 1: Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "fees_db"; // Replace with your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Step 2: Fetch the colleges from the 'colleges' table for the dropdown
$sql = "SELECT id, name FROM colleges"; // Fetch 'id' and 'name'
$result = $conn->query($sql);

if (!$result) {
    die("Query failed: " . $conn->error); // Display query error
}

// Step 3: Insert data into the database when the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate input data
    $college_id = filter_input(INPUT_POST, 'college_id', FILTER_VALIDATE_INT);
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $fees = filter_input(INPUT_POST, 'fees', FILTER_VALIDATE_FLOAT);

    if (!$college_id || !$email || !$fees) {
        die("Invalid input data.");
    }

    // Get the current timestamp for 'created_at'
    $created_at = date('Y-m-d H:i:s'); // Format: YYYY-MM-DD HH:MM:SS

    // Prepare SQL statement to insert the data into 'college_alerts' table
    $stmt = $conn->prepare("INSERT INTO college_alerts (id, email, fees, created_at) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $college_id, $email, $fees, $created_at); // "isss" means: integer, string, string, string

    // Execute the query
    if ($stmt->execute()) {
        echo "Data inserted successfully!";
    } else {
        echo "Error: " . $stmt->error;
    }

    // Close the prepared statement
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>College Alert Form</title>
</head>
<body>

    <h1>Submit College Details</h1>

    <form method="POST" action="form.php">
        <!-- Dropdown for selecting the college -->
        <label for="college_id">Choose College:</label><br>
        <select id="college_id" name="college_id" required>
            <option value="">Select a College</option>
            <?php
            // Step 4: Display colleges in the dropdown
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='" . $row['id'] . "'>" . htmlspecialchars($row['name']) . "</option>"; // Use 'name' instead of 'collegename'
                }
            } else {
                echo "<option value=''>No colleges available</option>";
            }
            ?>
        </select><br><br>

        <!-- Input for email -->
        <label for="email">Email ID:</label><br>
        <input type="email" id="email" name="email" required><br><br>

        <!-- Input for fees -->
        <label for="fees">Fees:</label><br>
        <input type="number" id="fees" name="fees" required><br><br>

        <!-- Submit Button -->
        <input type="submit" value="Submit">
    </form>

</body>
</html>
