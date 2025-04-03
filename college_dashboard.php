
<?php
// Add error reporting for troubleshooting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Add cache control headers to prevent browser caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

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
$db_pass = ''; // Ensure this is correct for your setup
$db_name = 'fees_db';

// Create MAIN connection - Reuse this throughout the script
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check MAIN connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Use relative path for the current directory
$imageDir = './';

// Debug information
error_log("Current working directory: " . getcwd());
error_log("College ID from session: " . $collegeId);

// --- Message Variables ---
$success_message = null;
$error_message = null;

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_image'])) {
    if (isset($_FILES['college_image']) && $_FILES['college_image']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg'];
        if (in_array($_FILES['college_image']['type'], $allowedTypes)) {
            $getNameStmt = $conn->prepare("SELECT name FROM colleges WHERE id = ?");
            if (!$getNameStmt) {
                $error_message = "Error preparing statement to get college name: " . $conn->error;
            } else {
                $getNameStmt->bind_param("i", $collegeId);
                if (!$getNameStmt->execute()) {
                    $error_message = "Error executing statement to get college name: " . $getNameStmt->error;
                } else {
                    $nameResult = $getNameStmt->get_result();
                    if ($nameResult->num_rows === 1) {
                        $collegeData = $nameResult->fetch_assoc();
                        $safeCollegeName = preg_replace('/[^a-zA-Z0-9_-]/', '', $collegeData['name']);
                        $collegeName = strtolower($safeCollegeName);
                        $newFileName = $collegeName . '.jpeg';
                        $destination = $imageDir . $newFileName;

                        // Delete the old image if it exists
                        if (file_exists($destination)) {
                            if (!unlink($destination)) {
                                $last_error = error_get_last();
                                $error_message = "Error deleting the existing image. Error: " . ($last_error['message'] ?? 'Unknown error');
                            }
                        }

                        if ($error_message === null) {
                            if (!move_uploaded_file($_FILES['college_image']['tmp_name'], $destination)) {
                                $upload_error = error_get_last();
                                $error_message = "Error saving file. PHP Upload Error Code: " . $_FILES['college_image']['error'] .
                                    ". System Message: " . ($upload_error ? $upload_error['message'] : 'Unknown error');
                            } else {
                                $success_message = "College image updated successfully!";
                                chmod($destination, 0644);
                            }
                        }
                    } else {
                        $error_message = "Error fetching college name (College ID not found?).";
                    }
                }
                $getNameStmt->close();
            }
        } else {
            $error_message = "Invalid file type. Only JPEG (.jpeg) images are allowed. Uploaded type: " . htmlspecialchars($_FILES['college_image']['type']);
        }
    } else {
        $upload_errors = [
            UPLOAD_ERR_INI_SIZE     => "The uploaded file exceeds the upload_max_filesize directive in php.ini.",
            UPLOAD_ERR_FORM_SIZE    => "The uploaded file exceeds the MAX_FILE_SIZE directive specified in the HTML form.",
            UPLOAD_ERR_PARTIAL      => "The uploaded file was only partially uploaded.",
            UPLOAD_ERR_NO_FILE      => "No file was uploaded.",
            UPLOAD_ERR_NO_TMP_DIR => "Missing a temporary folder.",
            UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk.",
            UPLOAD_ERR_EXTENSION    => "A PHP extension stopped the file upload.",
        ];
        $err_code = $_FILES['college_image']['error'] ?? UPLOAD_ERR_NO_FILE;
        $error_message = $upload_errors[$err_code] ?? "Unknown upload error.";
    }
}

// Handle form submission (update college info)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_college'])) {
    $name = $_POST['name'];
    $rating = floatval($_POST['rating']);
    $fees = floatval($_POST['fees']);

    if (empty($name) || $rating < 0 || $rating > 5 || $fees < 0) {
        $error_message = "Invalid input provided for college information.";
    } else {
        $stmt = $conn->prepare("UPDATE colleges SET name = ?, rating = ?, fees = ? WHERE id = ?");
        if (!$stmt) {
            $error_message = "Error preparing update statement: " . $conn->error;
        } else {
            $stmt->bind_param("sddi", $name, $rating, $fees, $collegeId);

            if ($stmt->execute()) {
                $success_message = "College information updated successfully!";
            } else {
                $error_message = "Error updating college: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Handle course association
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['associate_course'])) {
    $course_id = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);

    if ($course_id === false || $course_id <= 0) {
        $error_message = "Invalid Course ID selected.";
    } else {
        $check_sql = "SELECT 1 FROM college_courses WHERE college_id = ? AND course_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        if (!$check_stmt) {
            $error_message = "Error preparing check statement: " . $conn->error;
        } else {
            $check_stmt->bind_param("ii", $collegeId, $course_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {
                $error_message = "Error: Course is already associated with this college!";
            } else {
                $insert_sql = "INSERT INTO college_courses (college_id, course_id) VALUES (?, ?)";
                $insert_stmt = $conn->prepare($insert_sql);
                if (!$insert_stmt) {
                    $error_message = "Error preparing insert statement: " . $conn->error;
                } else {
                    $insert_stmt->bind_param("ii", $collegeId, $course_id);
                    if ($insert_stmt->execute()) {
                        $success_message = "Course successfully associated with college!";
                    } else {
                        $error_message = "Error associating course: " . $insert_stmt->error;
                    }
                    $insert_stmt->close();
                }
            }
            $check_stmt->close();
        }
    }
}

// Handle "Add New Course" submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_course'])) {
    $course_name = trim($_POST['course_name'] ?? '');

    if (!empty($course_name)) {
        // First check if course already exists
        $check_sql = "SELECT id FROM courses WHERE course_name = ?";
        $check_stmt = $conn->prepare($check_sql);

        if (!$check_stmt) {
            $error_message = "Error preparing check statement: " . $conn->error;
        } else {
            $check_stmt->bind_param("s", $course_name);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {
                $error_message = "Course '$course_name' already exists.";
            } else {
                $insert_course_sql = "INSERT INTO courses (course_name) VALUES (?)";
                $insert_course_stmt = $conn->prepare($insert_course_sql);

                if (!$insert_course_stmt) {
                    $error_message = "Error preparing statement to add course: " . $conn->error;
                } else {
                    $insert_course_stmt->bind_param("s", $course_name);

                    if ($insert_course_stmt->execute()) {
                        $success_message = "Course '$course_name' added successfully!";
                    } else {
                        $error_message = "Error adding course: " . $insert_course_stmt->error;
                    }
                    $insert_course_stmt->close();
                }
            }
            $check_stmt->close();
        }
    } else {
        $error_message = "Course name cannot be empty.";
    }
}

// Handle "Disassociate Course" submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['disassociate_course'])) {
    $disassociate_course_id = filter_input(INPUT_POST, 'disassociate_course_id', FILTER_VALIDATE_INT);

    if ($disassociate_course_id === false || $disassociate_course_id <= 0) {
        $error_message = "Invalid Course ID selected for disassociation.";
    } else {
        $delete_sql = "DELETE FROM college_courses WHERE college_id = ? AND course_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);

        if (!$delete_stmt) {
            $error_message = "Error preparing statement to disassociate course: " . $conn->error;
        } else {
            $delete_stmt->bind_param("ii", $collegeId, $disassociate_course_id);

            if ($delete_stmt->execute()) {
                if ($delete_stmt->affected_rows > 0) {
                    $success_message = "Course successfully disassociated from this college!";
                } else {
                    $error_message = "Selected course was not associated with this college.";
                }
            } else {
                $error_message = "Error disassociating course: " . $delete_stmt->error;
            }
            $delete_stmt->close();
        }
    }
}

// Get college details
$stmt = $conn->prepare("SELECT * FROM colleges WHERE id = ?");
if (!$stmt) {
    die("Error preparing statement to get college details: " . $conn->error);
}
$stmt->bind_param("i", $collegeId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    error_log("CRITICAL: College details not found for ID: " . $collegeId);
    header("Location: index.html?error=college_not_found");
    exit;
}
$college = $result->fetch_assoc();
$stmt->close();

// Generate Image URL
$safeCollegeNameForImage = preg_replace('/[^a-zA-Z0-9_-]/', '', $college['name']);
$imageFileName = strtolower($safeCollegeNameForImage) . '.jpeg';
$imageUrl = $imageDir . $imageFileName;
$imageUrlWithCacheBust = $imageUrl . '?v=' . time();
$imageExists = file_exists($imageUrl);

// Get college alerts
$alerts_stmt = $conn->prepare("SELECT email, fees, created_at FROM college_alerts WHERE id = ? ORDER BY created_at DESC");
$college_alerts = [];
if (!$alerts_stmt) {
    error_log("Error preparing statement for college alerts: " . $conn->error);
    $error_message = ($error_message ? $error_message . "<br>" : "") . "Could not fetch college alerts.";
} else {
    $alerts_stmt->bind_param("i", $collegeId);
    if (!$alerts_stmt->execute()) {
        error_log("Error executing statement for college alerts: " . $alerts_stmt->error);
        $error_message = ($error_message ? $error_message . "<br>" : "") . "Could not fetch college alerts.";
    } else {
        $alerts_result = $alerts_stmt->get_result();
        if ($alerts_result->num_rows > 0) {
            while ($row = $alerts_result->fetch_assoc()) {
                $college_alerts[] = $row;
            }
        }
    }
    $alerts_stmt->close();
}

// Get user feedback
$feedback_stmt = $conn->prepare("SELECT feedback_text, feedback_by, created_at FROM college_feedback WHERE college_id = ? ORDER BY created_at DESC");
$college_feedback_list = [];
if (!$feedback_stmt) {
    error_log("Error preparing statement for college feedback: " . $conn->error);
    $error_message = ($error_message ? $error_message . "<br>" : "") . "Could not fetch college feedback.";
} else {
    $feedback_stmt->bind_param("i", $collegeId);
        if (!$feedback_stmt->execute()) {
            error_log("Error executing statement for college feedback: " . $feedback_stmt->error);
            $error_message = ($error_message ? $error_message . "<br>" : "") . "Could not fetch college feedback.";
    } else {
        $feedback_result = $feedback_stmt->get_result();
        if ($feedback_result->num_rows > 0) {
            while ($row = $feedback_result->fetch_assoc()) {
                $college_feedback_list[] = $row;
            }
        }
    }
    $feedback_stmt->close();
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
        body { background-color: #f8f9fa; }
        .dashboard-header { background-color: #2c3e50; color: white; padding: 20px 0; margin-bottom: 30px; }
        .college-card, .alerts-table, .feedback-table, .course-card { box-shadow: 0 4px 8px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        .edit-form { display: none; margin-top: 20px; }
        .college-image-preview { max-width: 200px; height: auto; margin-bottom: 10px; border: 1px solid #ccc; padding: 5px; display: block; }
        .placeholder-image { color: #6c757d; text-align: center; line-height: 100px; background-color: #e9ecef; border: 1px dashed #ccc; }
    </style>
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
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
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card college-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title mb-0">College Information</h5>
                            <div>
                                <button id="editBtn" class="btn btn-sm btn-outline-primary me-2">Edit Info</button>
                            </div>
                        </div>
                        <div id="infoView">
                                <?php if ($imageExists): ?>
                                    <img src="<?php echo $imageUrlWithCacheBust; ?>" alt="<?php echo htmlspecialchars($college['name']); ?>" class="college-image-preview">
                                <?php else: ?>
                                    <div class="college-image-preview placeholder-image">No Image Available</div>
                                    <small class="text-muted">Expected: <?php echo htmlspecialchars($imageUrl); ?></small><br>
                                <?php endif; ?>
                                <p><strong>Name:</strong> <?php echo htmlspecialchars($college['name']); ?></p>
                                <p><strong>Rating:</strong> <?php echo number_format($college['rating'], 1); ?>/5</p>
                                <p><strong>Fees:</strong> ₹<?php echo number_format($college['fees'], 2); ?></p>
                        </div>

                        <form id="editForm" class="edit-form" method="POST" action="college_dashboard.php">
                            <div class="mb-3">
                                <label class="form-label">College Name</label>
                                <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($college['name']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Rating (0.0 - 5.0)</label>
                                <input type="number" class="form-control" name="rating" min="0" max="5" step="0.1" value="<?php echo $college['rating']; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Fees (₹)</label>
                                <input type="number" class="form-control" name="fees" min="0" step="0.01" value="<?php echo $college['fees']; ?>" required>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="button" id="cancelEdit" class="btn btn-outline-secondary me-2">Cancel</button>
                                <button type="submit" name="update_college" class="btn btn-primary">Save Changes</button>
                            </div>
                        </form>

                        <hr class="my-4">
                        <h5>Update College Image</h5>
                         <?php if ($imageExists): ?>
                                    <img src="<?php echo $imageUrlWithCacheBust; ?>" alt="Current Image" class="college-image-preview" >
                                <?php else: ?>
                                    <div class="college-image-preview placeholder-image">No Image Uploaded</div>
                                     <small class="text-muted">Expected: <?php echo htmlspecialchars($imageUrl); ?></small><br>
                                <?php endif; ?>
                        <form method="POST" action="college_dashboard.php" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="college_image" class="form-label">Choose New Image (JPEG only)</label>
                                <input class="form-control" type="file" id="college_image" name="college_image" accept="image/jpeg" required>
                            </div>
                            <button type="submit" name="upload_image" class="btn btn-warning">Upload Image</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row justify-content-center">
             <div class="col-md-8">
                <div class="card course-card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Add New Course</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="college_dashboard.php">
                            <div class="mb-3">
                                <label for="course_name" class="form-label">New Course Name</label>
                                <input type="text" class="form-control" id="course_name" name="course_name" required>
                            </div>
                            <button type="submit" name="add_course" class="btn btn-info">Add Course</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card course-card">
                    <div class="card-header bg-warning">
                        <h5 class="mb-0">Associate Course with This College</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="college_dashboard.php">
                            <div class="mb-3">
                                <label for="course_select" class="form-label">Select Course to Associate</label>
                                <select class="form-select" id="course_select" name="course_id" required>
                                    <option value="">-- Choose Course --</option>
                                    <?php
                                    $assoc_course_ids = [];
                                    $get_assoc_ids_sql = "SELECT course_id FROM college_courses WHERE college_id = ?";
                                    $get_assoc_ids_stmt = $conn->prepare($get_assoc_ids_sql);
                                    if ($get_assoc_ids_stmt) {
                                        $get_assoc_ids_stmt->bind_param("i", $collegeId);
                                        $get_assoc_ids_stmt->execute();
                                        $assoc_ids_result = $get_assoc_ids_stmt->get_result();
                                        while ($assoc_row = $assoc_ids_result->fetch_assoc()) {
                                            $assoc_course_ids[] = $assoc_row['course_id'];
                                        }
                                        $get_assoc_ids_stmt->close();
                                    }

                                    $all_courses_sql = "SELECT id, course_name FROM courses ORDER BY course_name";
                                    $course_result = $conn->query($all_courses_sql);

                                    if ($course_result && $course_result->num_rows > 0) {
                                        $available_courses_count = 0;
                                        while ($course = $course_result->fetch_assoc()) {
                                            if (!in_array($course['id'], $assoc_course_ids)) {
                                                echo "<option value='" . htmlspecialchars($course['id']) . "'>" . htmlspecialchars($course['course_name']) . "</option>";
                                                $available_courses_count++;
                                            }
                                        }
                                        if ($available_courses_count === 0 && $course_result->num_rows > 0) {
                                            echo "<option value='' disabled>All available courses are already associated</option>";
                                        }
                                    } else {
                                        echo "<option value='' disabled>No courses available</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <button type="submit" name="associate_course" class="btn btn-warning">Associate Selected Course</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card course-card">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0">Disassociate Course from This College</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="college_dashboard.php">
                            <div class="mb-3">
                                <label for="disassociate_course_select" class="form-label">Select Course to Disassociate</label>
                                <select class="form-select" id="disassociate_course_select" name="disassociate_course_id" required>
                                    <option value="">-- Choose Course --</option>
                                    <?php
                                    $associated_courses_sql = "SELECT c.id, c.course_name FROM courses c
                                                                        INNER JOIN college_courses cc ON c.id = cc.course_id
                                                                        WHERE cc.college_id = ?
                                                                        ORDER BY c.course_name";
                                    $associated_courses_stmt = $conn->prepare($associated_courses_sql);
                                    if (!$associated_courses_stmt) {
                                        echo "<option value='' disabled>Error loading courses</option>";
                                    } else {
                                        $associated_courses_stmt->bind_param("i", $collegeId);
                                        $associated_courses_stmt->execute();
                                        $associated_courses_result = $associated_courses_stmt->get_result();

                                        if ($associated_courses_result->num_rows > 0) {
                                            while ($assoc_course = $associated_courses_result->fetch_assoc()) {
                                                echo "<option value='" . htmlspecialchars($assoc_course['id']) . "'>" . htmlspecialchars($assoc_course['course_name']) . "</option>";
                                            }
                                        } else {
                                            echo "<option value='' disabled>No courses currently associated</option>";
                                        }
                                        $associated_courses_stmt->close();
                                    }
                                    ?>
                                </select>
                            </div>
                            <button type="submit" name="disassociate_course" class="btn btn-danger">Disassociate Selected Course</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card course-card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">Courses Currently Associated with This College</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $associated_courses_sql = "SELECT c.course_name FROM courses c
                                                    INNER JOIN college_courses cc ON c.id = cc.course_id
                                                    WHERE cc.college_id = ?
                                                    ORDER BY c.course_name";

                        $associated_courses_stmt = $conn->prepare($associated_courses_sql);
                        if (!$associated_courses_stmt) {
                            echo "<p>Error loading courses</p>";
                        } else {
                            $associated_courses_stmt->bind_param("i", $collegeId);
                            $associated_courses_stmt->execute();
                            $associated_courses_result = $associated_courses_stmt->get_result();

                            if ($associated_courses_result->num_rows > 0) {
                                echo "<ul>";
                                while ($assoc_course = $associated_courses_result->fetch_assoc()) {
                                    echo "<li>" . htmlspecialchars($assoc_course['course_name']) . "</li>";
                                }
                                echo "</ul>";
                            } else {
                                echo "<p>No courses currently associated with this college.</p>";
                            }
                            $associated_courses_stmt->close();
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-md-8">
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
                                            <th>Alert Set On</th>
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

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card feedback-table">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">User Feedback</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($college_feedback_list)): ?>
                            <p class="card-text">No feedback available for this college yet.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Feedback</th>
                                            <th>Submitted By</th>
                                            <th>Submitted At</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($college_feedback_list as $feedback): ?>
                                            <tr>
                                                <td><?php echo nl2br(htmlspecialchars($feedback['feedback_text'])); ?></td>
                                                <td><?php echo htmlspecialchars($feedback['feedback_by']); ?></td>
                                                <td><?php echo htmlspecialchars($feedback['created_at']); ?></td>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle edit form visibility
        const editBtn = document.getElementById('editBtn');
        const cancelEditBtn = document.getElementById('cancelEdit');
        const infoViewDiv = document.getElementById('infoView');
        const editForm = document.getElementById('editForm');

        if (editBtn && cancelEditBtn && infoViewDiv && editForm) {
            editBtn.addEventListener('click', function() {
                infoViewDiv.style.display = 'none';
                editForm.style.display = 'block';
                editBtn.style.display = 'none';
            });

            cancelEditBtn.addEventListener('click', function() {
                infoViewDiv.style.display = 'block';
                editForm.style.display = 'none';
                editBtn.style.display = 'inline-block';
            });
        }

        window.setTimeout(function() {
            let alerts = document.querySelectorAll('.alert-dismissible');
            alerts.forEach(function(alert) {
                if (typeof bootstrap !== 'undefined' && bootstrap.

