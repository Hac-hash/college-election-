<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load PHPMailer if not already loaded
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    require 'vendor/autoload.php';
}

// Define base URL for dynamic links (replace with your actual URL)
define('BASE_URL', 'https://yourdomain.com'); // Update this to your actual domain

function sendVoterEmail($toEmail, $scenario, $voterCode = '', $password = '', $student_id = '', $position = '', $message = '') {
    $mail = new PHPMailer(true);

    try {
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'electionadm2025@gmail.com';
        $mail->Password = 'ytwhxdrgxmzacdam';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Email Character Set
        $mail->CharSet = 'UTF-8';

        // Email Content
        $mail->setFrom('electionadm2025@gmail.com', 'Election Admin');
        $mail->addAddress($toEmail);

        // Set email format to HTML for all scenarios
        $mail->isHTML(true);

        // Base HTML template components
        $header = '
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Election Notification</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background-color: #f4f4f4;
                    margin: 0;
                    padding: 0;
                }
                .email-container {
                    max-width: 600px;
                    margin: 20px auto;
                    background-color: #ffffff;
                    border-radius: 8px;
                    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                    overflow: hidden;
                }
                .header {
                    background-color: #333;
                    color: #ffffff;
                    padding: 20px;
                    text-align: center;
                }
                .header h1 {
                    margin: 0;
                    font-size: 24px;
                }
                .body {
                    padding: 30px;
                    color: #333;
                }
                .body h2 {
                    font-size: 20px;
                    color: #27ae60;
                    margin-top: 0;
                }
                .body p {
                    font-size: 16px;
                    line-height: 1.6;
                    margin: 10px 0;
                }
                .info-box {
                    background-color: #f9f9f9;
                    padding: 15px;
                    border-left: 4px solid #27ae60;
                    margin: 20px 0;
                    font-size: 16px;
                    color: #555;
                }
                .cta {
                    text-align: center;
                    margin: 20px 0;
                }
                .cta a {
                    display: inline-block;
                    padding: 10px 20px;
                    background-color: #007bff;
                    color: #ffffff;
                    text-decoration: none;
                    border-radius: 5px;
                    font-size: 16px;
                }
                .cta a:hover {
                    background-color: #0056b3;
                }
                .footer {
                    background-color: #f4f4f4;
                    padding: 20px;
                    text-align: center;
                    font-size: 14px;
                    color: #777;
                }
                .footer a {
                    color: #007bff;
                    text-decoration: none;
                }
                .footer a:hover {
                    text-decoration: underline;
                }
            </style>
        </head>
        <body>
            <div class="email-container">
                <!-- Header -->
                <div class="header">
                    <h1>Ballot Box Notification</h1>
                </div>';

        $footer = '
                <!-- Footer -->
                <div class="footer">
                    <p>Best regards,<br>Election Admin Team</p>
                    <p>Albertian Institute of Science & Technology, Kalamassery<br>
                    University Road, South Kalamassery, Kalamassery, Kochi, Kerala 682022<br>
                    Phone: 8943789868, 123456789 | Email: <a href="mailto:hod.cse@aisat.ac.in">hod.cse@aisat.ac.in</a></p>
                </div>
            </div>
        </body>
        </html>';

        // Handle Different Scenarios
        switch ($scenario) {
            case 1:
                // Scenario 1: Voter Approved, No Candidate Application
                $mail->Subject = "Voter Registration Approved & Credentials Enclosed";
                $mail->Body = $header . '
                    <!-- Body -->
                    <div class="body">
                        <h2>Dear Voter,</h2>
                        <p>Congratulations! Your voter registration has been successfully approved.</p>
                        <div class="info-box">
                            <strong>Here are your login credentials:</strong><br>
                            🔹 Voter Code: ' . htmlspecialchars($voterCode) . '<br>
                            🔹 Password: ' . htmlspecialchars($password) . '
                        </div>
                        <p>Please log in to the voting system using these credentials to cast your vote.</p>
                        <div class="cta">
                            <a href="' . BASE_URL . '/student-login.php">Log In Now</a>
                        </div>
                        <p>If you encounter any issues, feel free to contact the Election Support Team at <a href="mailto:hod.cse@aisat.ac.in">hod.cse@aisat.ac.in</a>.</p>
                    </div>' . $footer;

                $mail->AltBody = "Dear Voter,\n\n"
                               . "Congratulations! Your voter registration has been successfully approved.\n\n"
                               . "Here are your login credentials:\n"
                               . "🔹 Voter Code: $voterCode\n"
                               . "🔹 Password: $password\n\n"
                               . "👉 Please log in to the voting system using these credentials to cast your vote.\n\n"
                               . "If you encounter any issues, feel free to contact the Election Support Team at hod.cse@aisat.ac.in.\n\n"
                               . "Best regards,\nElection Admin";
                break;

            case 2:
                // Scenario 2: Voter Approved, Candidate Application Pending
                $mail->Subject = "Voter Credentials & Candidate Application Status";
                $mail->Body = $header . '
                    <!-- Body -->
                    <div class="body">
                        <h2>Dear Voter,</h2>
                        <p>Your voter registration has been successfully approved. However, your candidate application is still under review.</p>
                        <div class="info-box">
                            <strong>Here are your login credentials:</strong><br>
                            🔹 Voter Code: ' . htmlspecialchars($voterCode) . '<br>
                            🔹 Password: ' . htmlspecialchars($password) . '<br><br>
                            📢 Candidate Application Status: Pending Approval
                        </div>
                        <p>Please log in to the voting system to view your status and stay informed.</p>
                        <div class="cta">
                            <a href="' . BASE_URL . '/student-login.php">Log In Now</a>
                        </div>
                        <p>If you need assistance, please contact the Election Support Team at <a href="mailto:hod.cse@aisat.ac.in">hod.cse@aisat.ac.in</a>.</p>
                    </div>' . $footer;

                $mail->AltBody = "Dear Voter,\n\n"
                               . "Your voter registration has been successfully approved. However, your candidate application is still under review.\n\n"
                               . "Here are your login credentials:\n"
                               . "🔹 Voter Code: $voterCode\n"
                               . "🔹 Password: $password\n\n"
                               . "📢 Candidate Application Status: Pending Approval\n\n"
                               . "👉 Please log in to the voting system to view your status and stay informed.\n\n"
                               . "If you need assistance, please contact the Election Support Team at hod.cse@aisat.ac.in.\n\n"
                               . "Best regards,\nElection Admin";
                break;

            case 3:
                // Scenario 3: Candidate Approved
                $mail->Subject = "Candidate Application Approved";
                $mail->Body = $header . '
                    <!-- Body -->
                    <div class="body">
                        <h2>Dear Candidate,</h2>
                        <p>We are pleased to inform you that your candidate application has been approved successfully.</p>
                        <div class="info-box">
                            <strong>Candidate Information:</strong><br>
                            🔹 Candidate ID: ' . htmlspecialchars($student_id) . '<br>
                            🔹 Approved Position: ' . htmlspecialchars($position) . '
                        </div>
                        <p>You are now officially listed as a candidate for the upcoming election.</p>
                        <div class="cta">
                            <a href="' . BASE_URL . '/student-login.php">View Your Profile</a>
                        </div>
                        <p>If you encounter any issues, please do not hesitate to contact the Election Support Team at <a href="mailto:hod.cse@aisat.ac.in">hod.cse@aisat.ac.in</a>.</p>
                    </div>' . $footer;

                $mail->AltBody = "Dear Candidate,\n\n"
                               . "We are pleased to inform you that your candidate application has been approved successfully.\n\n"
                               . "✅ Candidate Information:\n"
                               . "🔹 Candidate ID: $student_id\n"
                               . "🔹 Approved Position: $position\n\n"
                               . "👉 You are now officially listed as a candidate for the upcoming election.\n\n"
                               . "If you encounter any issues, please do not hesitate to contact the Election Support Team at hod.cse@aisat.ac.in.\n\n"
                               . "Best regards,\nElection Admin";
                break;

            case 4:
                // Scenario 4: Voter & Candidate Approved
                $mail->Subject = "Approval Notification: Voter & Candidate Credentials";
                $mail->Body = $header . '
                    <!-- Body -->
                    <div class="body">
                        <h2>Dear User,</h2>
                        <p>We are pleased to inform you that your voter and candidate applications have been approved successfully.</p>
                        <div class="info-box">
                            <strong>Voter Credentials:</strong><br>
                            🔹 Voter Code: ' . htmlspecialchars($voterCode) . '<br>
                            🔹 Password: ' . htmlspecialchars($password) . '<br><br>
                            <strong>Candidate Credentials:</strong><br>
                            🔹 Candidate ID: ' . htmlspecialchars($student_id) . '<br>
                            🔹 Approved Position: ' . htmlspecialchars($position) . '
                        </div>
                        <p>Please log in to the system to perform your respective roles.</p>
                        <div class="cta">
                            <a href="' . BASE_URL . '/student-login.php">Log In Now</a>
                        </div>
                        <p>If you encounter any issues, please do not hesitate to contact the Election Support Team at <a href="mailto:hod.cse@aisat.ac.in">hod.cse@aisat.ac.in</a>.</p>
                    </div>' . $footer;

                $mail->AltBody = "Dear User,\n\n"
                               . "We are pleased to inform you that your voter and candidate applications have been approved successfully.\n\n"
                               . "✅ Voter Credentials:\n"
                               . "🔹 Voter Code: $voterCode\n"
                               . "🔹 Password: $password\n\n"
                               . "✅ Candidate Credentials:\n"
                               . "🔹 Candidate ID: $student_id\n"
                               . "🔹 Approved Position: $position\n\n"
                               . "👉 Please log in to the system to perform your respective roles.\n\n"
                               . "If you encounter any issues, please do not hesitate to contact the Election Support Team at hod.cse@aisat.ac.in.\n\n"
                               . "Best regards,\nElection Admin";
                break;

            case 5:
                // Scenario 5: Candidate Rejected
                $mail->Subject = "Candidate Application Rejected";
                $mail->Body = $header . '
                    <!-- Body -->
                    <div class="body">
                        <h2>Dear Candidate,</h2>
                        <p>We regret to inform you that your candidate application has been reviewed and unfortunately, it has been rejected.</p>
                        <div class="info-box" style="border-left-color: #e74c3c;">
                            ⚠️ Reason: Your application did not meet the necessary eligibility criteria or documentation requirements.
                        </div>
                        <p>If you have any questions or need clarification, please contact the Election Support Team at <a href="mailto:hod.cse@aisat.ac.in">hod.cse@aisat.ac.in</a>.</p>
                    </div>' . $footer;

                $mail->AltBody = "Dear Candidate,\n\n"
                               . "We regret to inform you that your candidate application has been reviewed and unfortunately, it has been rejected.\n\n"
                               . "⚠️ Reason: Your application did not meet the necessary eligibility criteria or documentation requirements.\n\n"
                               . "If you have any questions or need clarification, please contact the Election Support Team at hod.cse@aisat.ac.in.\n\n"
                               . "Best regards,\nElection Admin";
                break;

            case 6:
                // Scenario 6: General Notification (Election Status or Manual Notification)
                $mail->Subject = "Election Update";
                $mail->Body = $header . '
                    <!-- Body -->
                    <div class="body">
                        <h2>Dear Voter,</h2>
                        <p>We have an important update for you from the Election Admin team.</p>
                        <div class="info-box">
                            📢 ' . htmlspecialchars($message) . '
                        </div>
                        <p>Please log in to the voting system to stay updated on the latest election details.</p>
                        <div class="cta">
                            <a href="' . BASE_URL . '/student-login.php">Log In Now</a>
                        </div>
                        <p>If you need assistance, feel free to contact the Election Support Team at <a href="mailto:hod.cse@aisat.ac.in">hod.cse@aisat.ac.in</a>.</p>
                    </div>' . $footer;

                $mail->AltBody = "Dear Voter,\n\n"
                               . "A new notification has been posted:\n\n"
                               . "📢 $message\n\n"
                               . "👉 Please log in to the voting system to stay updated.\n\n"
                               . "If you need assistance, please contact the Election Support Team at hod.cse@aisat.ac.in.\n\n"
                               . "Best regards,\nElection Admin";
                break;

            default:
                return false; // Invalid scenario
        }

        // Send Email
        return $mail->send();
    } catch (Exception $e) {
        error_log("Mail sending failed to $toEmail (Scenario $scenario): " . $e->getMessage());
        return false; // Email failed
    }
}
?>