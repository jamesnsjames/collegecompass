
<?php
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

// Get user feedback
$feedback_stmt = $conn->prepare("SELECT feedback_text, feedback_by, created_at FROM college_feedback WHERE college_id = 1 ORDER BY created_at DESC");
$college_feedback_list = [];
if (!$feedback_stmt) {
    error_log("Error preparing statement for college feedback: " . $conn->error);
    $error_message = ($error_message ? $error_message . "<br>" : "") . "Could not fetch college feedback.";
} else {
   
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


  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">








    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SNGIST Arts & Science College</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .college-image {
            display: block;
            max-width: 100%;
            height: auto;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        ul {
            padding-left: 20px;
        }

        li {
            margin-bottom: 10px;
            line-height: 1.6;
            font-size: 16px;
        }

        .footer {
            text-align: center;
            padding: 10px;
            margin-top: 20px;
            background-color: #2c3e50;
            color: white;
        }
        
              body { background-color: #f8f9fa; }
        .dashboard-header { background-color: #2c3e50; color: white; padding: 20px 0; margin-bottom: 30px; }
        .college-card, .alerts-table, .feedback-table, .course-card { box-shadow: 0 4px 8px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        .edit-form { display: none; margin-top: 20px; }
        .college-image-preview { max-width: 200px; height: auto; margin-bottom: 10px; border: 1px solid #ccc; padding: 5px; display: block; }
        .placeholder-image { color: #6c757d; text-align: center; line-height: 100px; background-color: #e9ecef; border: 1px dashed #ccc; }
        
        
        
        
        
    </style>
</head>
<body>

    <div class="container">
        <img src="sngist.jpeg" alt="SNGIST Arts & Science College" class="college-image">
        
        <ul>
            <li><p><strong>SNGIST Arts & Science College</strong> was established in 2006 by Guru Deva Trust, with a vision to create centers of excellence in higher education.</p></li>
            
            <li><p>The college is known for its <strong>highly qualified faculty</strong> with extensive academic and research experience.</p></li>

            <li><p>Providing a <strong>student-centric learning environment</strong>, SNGIST focuses on both academic excellence and holistic development.</p></li>

            <li><p>It offers a variety of <strong>undergraduate and postgraduate programs</strong> catering to different fields of study.</p></li>
            
            <li><p>The institution is equipped with <strong>modern infrastructure, laboratories, and research facilities</strong> to support high-quality education.</p></li>
        </ul>
    </div>

    <div class="footer">
        &copy; 2024 SNGIST Arts & Science College | All Rights Reserved
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
</body>



</html>

