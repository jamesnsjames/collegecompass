<?php
// File: admin.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Default admin credentials
$admin_username = 'admin';
$admin_password = 'admin123';

// Database configuration - UPDATE THESE TO MATCH YOUR SERVER
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'fees_db';

// Test database connection
try {
    $test_conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($test_conn->connect_error) {
        die("Database connection failed: " . $test_conn->connect_error);
    }
    $test_conn->close();
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if ($_POST['username'] === $admin_username && $_POST['password'] === $admin_password) {
        $_SESSION['admin_logged_in'] = true;
        header("Location: admin.php");
        exit;
    } else {
        $login_error = "Invalid credentials";
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php");
    exit;
}

// Handle adding new college
$success_message = $error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_college']) && isset($_SESSION['admin_logged_in'])) {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

    $name = $conn->real_escape_string($_POST['name'] ?? '');
    $rating = floatval($_POST['rating'] ?? 0);
    $fees = floatval($_POST['fees'] ?? 0);

    $sql = "INSERT INTO colleges (name, rating, fees) VALUES ('$name', $rating, $fees)";

    if ($conn->query($sql)) {
        $success_message = "College added successfully!";
    } else {
        $error_message = "Error: " . $conn->error;
    }

    $conn->close();
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>College Compass - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; padding: 20px; }
        .admin-container { max-width: 800px; margin: 0 auto; padding: 30px; background: white; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        .login-form { max-width: 400px; margin: 0 auto; }
        .form-group { margin-bottom: 1.5rem; }
        .table-responsive { max-height: 400px; overflow-y: auto; }
        .debug-info { font-size: 12px; color: #666; margin-top: 20px; border-top: 1px solid #eee; padding-top: 10px; }
        .home-btn-fixed {
            position: fixed;
            top: 20px;
            left: 20px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: #4CAF50; /* Green color */
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 1000;
            font-size: 1.2rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .home-btn-fixed:hover {
            background-color: #45a049; /* Darker shade of green */
        }
    </style>
</head>
<body>
    <div class="home-btn-fixed" onclick="window.location.href='index.html'">
        <i class="fas fa-home"></i>
    </div>
    <div class="admin-container">
        <?php if (!isset($_SESSION['admin_logged_in'])): ?>
            <div class="login-form">
                <h2 class="text-center mb-4">Admin Login</h2>
                <?php if (isset($login_error)): ?>
                    <div class="alert alert-danger"><?php echo $login_error; ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required value="admin">
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required value="admin123">
                    </div>
                    <button type="submit" name="login" class="btn btn-primary w-100">Login</button>
                </form>
            </div>
        <?php else: ?>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Admin Dashboard</h2>
                <a href="?logout" class="btn btn-outline-danger">Logout</a>
            </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Add New College</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-group">
                            <label for="name">College Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="rating">Rating (0-5)</label>
                            <input type="number" class="form-control" id="rating" name="rating" min="0" max="5" step="0.1" required>
                        </div>
                        <div class="form-group">
                            <label for="fees">Fees</label>
                            <input type="number" class="form-control" id="fees" name="fees" min="0" step="0.01" required>
                        </div>
                        <button type="submit" name="add_college" class="btn btn-primary">Add College</button>
                    </form>
                </div>
            </div>

<div class="card mb-4">
    <div class="card-header bg-warning text-white">
        <h5 class="mb-0">Associate Course with College</h5>
    </div>
    <div class="card-body">
        <form method="POST">
            <div class="form-group mb-3">
                <label for="college_select">Select College</label>
                <select class="form-select" id="college_select" name="college_id" required>
                    <option value="">Choose College</option>
                    <?php
                    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
                    $college_result = $conn->query("SELECT id, name FROM colleges ORDER BY name");
                    while ($college = $college_result->fetch_assoc()) {
                        echo "<option value='{$college['id']}'>{$college['name']}</option>";
                    }
                    ?>
                </select>
            </div>
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

<?php
// Handle course-college association
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['associate_course']) && isset($_SESSION['admin_logged_in'])) {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    $college_id = intval($_POST['college_id']);
    $course_id = intval($_POST['course_id']);

    $check_sql = "SELECT * FROM college_courses WHERE college_id = ? AND course_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $college_id, $course_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        echo "<div class='alert alert-warning'>Course already associated with this college!</div>";
    } else {
        $insert_sql = "INSERT INTO college_courses (college_id, course_id) VALUES (?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("ii", $college_id, $course_id);
        
        if ($insert_stmt->execute()) {
            echo "<div class='alert alert-success'>Course successfully associated with college!</div>";
        } else {
            echo "<div class='alert alert-danger'>Error associating course: " . $conn->error . "</div>";
        }
    }

    $conn->close();
}
?>
           

            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Existing Colleges</h5>
                </div>
                <div class="card-body">
                    <?php
                    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
                    if ($conn->connect_error) {
                        die("Connection failed: " . $conn->connect_error);
                    }

                    $result = $conn->query("SELECT id, name, rating, fees FROM colleges ORDER BY name");
                    ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Rating</th>
                                    <th>Fees</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && $result->num_rows > 0): ?>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $row['id']; ?></td>
                                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                                            <td><?php echo $row['rating']; ?></td>
                                            <td><?php echo number_format($row['fees'], 2); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center">No colleges found in database</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php $conn->close(); ?>
                </div>
            </div>
            
            <div class="card mt-4">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0">Registered College Alerts</h5>
    </div>
    <div class="card-body">
        <?php
        $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $alert_result = $conn->query("SELECT id, email, fees, created_at FROM college_alerts ORDER BY created_at DESC");
        ?>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Email</th>
                        <th>Fees Alert</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($alert_result && $alert_result->num_rows > 0): ?>
                        <?php while ($alert = $alert_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $alert['id']; ?></td>
                                <td><?php echo htmlspecialchars($alert['email']); ?></td>
                                <td><?php echo number_format($alert['fees'], 2); ?></td>
                                <td><?php echo $alert['created_at']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center">No alerts found in database</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php $conn->close(); ?>
    </div>
</div>

            
            
            
            
            
            
            
        <?php endif; ?>

        <div class="debug-info">
            <p><strong>Debug Information:</strong></p>
            <p>PHP Version: <?php echo phpversion(); ?></p>
            <p>Session Status: <?php echo session_status(); ?> (<?php
                switch(session_status()) {
                    case PHP_SESSION_DISABLED: echo 'disabled'; break;
                    case PHP_SESSION_NONE: echo 'none'; break;
                    case PHP_SESSION_ACTIVE: echo 'active'; break;
                }
            ?>)</p>
            <p>Logged In: <?php echo isset($_SESSION['admin_logged_in']) ? 'Yes' : 'No'; ?></p>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
