<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['college_id'])) {
    header("Location: index.html");
    exit;
}

$collegeId = $_SESSION['college_id'];

// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'fees_db';

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission (update college info)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_college'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $rating = floatval($_POST['rating']);
    $fees = floatval($_POST['fees']);

    $stmt = $conn->prepare("UPDATE colleges SET name = ?, rating = ?, fees = ? WHERE id = ?");
    $stmt->bind_param("sdii", $name, $rating, $fees, $collegeId);

    if ($stmt->execute()) {
        $success_message = "College information updated successfully!";
    } else {
        $error_message = "Error updating college: " . $conn->error;
    }
}

// Handle course association
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['associate_course'])) {
    $course_id = intval($_POST['course_id']);

    $check_sql = "SELECT * FROM college_courses WHERE college_id = ? AND course_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $collegeId, $course_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $error_message = "Course already associated with this college!";
    } else {
        $insert_sql = "INSERT INTO college_courses (college_id, course_id) VALUES (?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("ii", $collegeId, $course_id);

        if ($insert_stmt->execute()) {
            $success_message = "Course successfully associated with college!";
        } else {
            $error_message = "Error associating course: " . $conn->error;
        }
    }
}

// Get college details
$stmt = $conn->prepare("SELECT * FROM colleges WHERE id = ?");
$stmt->bind_param("i", $collegeId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    header("Location: index.html");
    exit;
}

$college = $result->fetch_assoc();

// Get college alerts for this college
$alerts_stmt = $conn->prepare("SELECT email, fees, created_at FROM college_alerts WHERE id = ? ORDER BY created_at DESC");
$alerts_stmt->bind_param("i", $collegeId);
$alerts_stmt->execute();
$alerts_result = $alerts_stmt->get_result();
$college_alerts = [];
if ($alerts_result->num_rows > 0) {
    while ($row = $alerts_result->fetch_assoc()) {
        $college_alerts[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($college['name']); ?> Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .dashboard-header {
            background-color: #2c3e50;
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
        }
        .college-card {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .edit-form {
            display: none; /* Hidden by default */
            margin-top: 20px;
        }
        .alerts-table {
            margin-top: 20px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="dashboard-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1><?php echo htmlspecialchars($college['name']); ?> Dashboard</h1>
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card college-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title">College Information</h5>
                            <button id="editBtn" class="btn btn-sm btn-outline-primary">Edit</button>
                        </div>
                        <div id="infoView">
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($college['name']); ?></p>
                            <p><strong>Rating:</strong> <?php echo $college['rating']; ?>/5</p>
                            <p><strong>Fees:</strong> ₹<?php echo number_format($college['fees'], 2); ?></p>
                        </div>

                        <form id="editForm" class="edit-form" method="POST" action="college_dashboard.php">
                            <div class="mb-3">
                                <label class="form-label">College Name</label>
                                <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($college['name']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Rating (0-5)</label>
                                <input type="number" class="form-control" name="rating" min="0" max="5" step="0.1" value="<?php echo $college['rating']; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Fees</label>
                                <input type="number" class="form-control" name="fees" min="0" step="0.01" value="<?php echo $college['fees']; ?>" required>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="button" id="cancelEdit" class="btn btn-outline-secondary me-2">Cancel</button>
                                <button type="submit" name="update_college" class="btn btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card mb-4">
                    <div class="card-header bg-warning text-white">
                        <h5 class="mb-0">Associate Course with College</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="form-group mb-3">
                                <label for="course_select">Select Course</label>
                                <select class="form-select" id="course_select" name="course_id" required>
                                    <option value="">Choose Course</option>
                                    <?php
                                    $course_result = $conn->query("SELECT id, course_name FROM courses ORDER BY course_name");
                                    while ($course = $course_result->fetch_assoc()) {
                                        echo "<option value='{$course['id']}'>{$course['course_name']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <button type="submit" name="associate_course" class="btn btn-warning">Associate Course</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12 mb-4">
                <div class="card alerts-table">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">College Alerts</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($college_alerts)): ?>
                            <p class="card-text">No alerts found for this college.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Email</th>
                                            <th>Fees Threshold</th>
                                            <th>Created At</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($college_alerts as $alert): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($alert['email']); ?></td>
                                                <td>₹<?php echo number_format($alert['fees'], 2); ?></td>
                                                <td><?php echo htmlspecialchars($alert['created_at']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

            <div class="col-12 mb-4">
                <div class="card alerts-table">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">College Alerts</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($college_alerts)): ?>
                            <p class="card-text">No alerts found for this college.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Email</th>
                                            <th>Fees Threshold</th>
                                            <th>Created At</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($college_alerts as $alert): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($alert['email']); ?></td>
                                                <td>₹<?php echo number_format($alert['fees'], 2); ?></td>
                                                <td><?php echo htmlspecialchars($alert['created_at']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-12 mb-4">
                <div class="card feedback-table">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">User Feedback</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $feedback_stmt = $conn->prepare("SELECT feedback_text, feedback_by, created_at FROM college_feedback WHERE college_id = ? ORDER BY created_at DESC");
                        $feedback_stmt->bind_param("i", $collegeId);
                        $feedback_stmt->execute();
                        $feedback_result = $feedback_stmt->get_result();
                        $college_feedback_list = [];
                        if ($feedback_result->num_rows > 0) {
                            while ($row = $feedback_result->fetch_assoc()) {
                                $college_feedback_list[] = $row;
                            }
                        }
                        ?>
                        <?php if (empty($college_feedback_list)): ?>
                            <p class="card-text">No feedback available for this college yet.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Feedback</th>
                                            <th>By</th>
                                            <th>Created At</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($college_feedback_list as $feedback): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($feedback['feedback_text']); ?></td>
                                                <td><?php echo htmlspecialchars($feedback['feedback_by']); ?></td>
                                                <td><?php echo htmlspecialchars($feedback['created_at']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                        <?php $feedback_stmt->close(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle edit form visibility
        document.getElementById('editBtn').addEventListener('click', function() {
            document.getElementById('infoView').style.display = 'none';
            document.getElementById('editForm').style.display = 'block';
            this.style.display = 'none';
        });

        document.getElementById('cancelEdit').addEventListener('click', function() {
            document.getElementById('infoView').style.display = 'block';
            document.getElementById('editForm').style.display = 'none';
            document.getElementById('editBtn').style.display = 'block';
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle edit form visibility
        document.getElementById('editBtn').addEventListener('click', function() {
            document.getElementById('infoView').style.display = 'none';
            document.getElementById('editForm').style.display = 'block';
            this.style.display = 'none';
        });

        document.getElementById('cancelEdit').addEventListener('click', function() {
            document.getElementById('infoView').style.display = 'block';
            document.getElementById('editForm').style.display = 'none';
            document.getElementById('editBtn').style.display = 'block';
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>
