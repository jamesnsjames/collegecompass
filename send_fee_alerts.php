<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require '/opt/lampp/htdocs/Studenthelpdesk-main/vendor/autoload.php'; // Adjust the path if needed

// Database connection details
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "fees_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch notifications
$sql = "SELECT n.college_id, n.old_fees, n.new_fees, ca.email, ca.fees AS alert_fees, c.name FROM notifications n JOIN college_alerts ca ON n.college_id = ca.id JOIN colleges c ON n.college_id = c.id";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        if ($row['new_fees'] <= $row['alert_fees']) {
            // Send email
            $mail = new PHPMailer(true);

            try {
                //Server settings
                $mail->SMTPDebug = SMTP::DEBUG_OFF;
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'lab202226@gmail.com'; // Replace with your email
                $mail->Password = 'uxjz geuu puik kted'; // Replace with your App Password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port = 465;

                //Recipients
                $mail->setFrom('your_gmail_email@gmail.com', 'College Alert');
                $mail->addAddress($row['email']);

                //Content
                $mail->isHTML(true);
                $mail->Subject = "College Fees Alert!";
                $mail->Body = "The fees for " . $row['name'] . " have reached your desired value: " . $row['new_fees'];
                $mail->AltBody = "The fees for " . $row['name'] . " have reached your desired value: " . $row['new_fees'];

                $mail->send();
                echo "Email sent to: " . $row['email'] . "<br>";
            } catch (Exception $e) {
                echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}<br>";
            }

            // Delete notification
            $delete_sql = "DELETE FROM notifications WHERE college_id = " . $row['college_id'] . " AND old_fees = " . $row['old_fees'] . " AND new_fees = " . $row['new_fees'];
            $conn->query($delete_sql);
        }
    }
}

// Close connection
$conn->close();
?>
