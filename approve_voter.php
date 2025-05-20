<?php
session_start();
include('connect.php');

include('send-mail.php'); // Include the email functionality file
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Check if user is logged in and is a tutor
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'tutor') {
    header("Location: tutor-login.php");
    exit();
}

// Check if tutor_id is set in session
if (!isset($_SESSION['tutor_id'])) {
    echo "<script>alert('Access Denied! Please log in.'); window.location.href='tutor_login.php';</script>";
    exit();
}

// Get logged-in tutor ID
$tutor_id = $_SESSION['tutor_id'];

// Function to generate a human-readable Voter Code
function generateVoterCode() {
    return "VOT" . rand(1000, 9999);
}

// Function to generate a random password
function generatePassword($length = 8) {
    return substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789"), 0, $length);
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['student_id'])) {
    $student_id = $_POST['student_id'];

    // Fetch tutor's assigned department and batch
    $tutor_info_query = "SELECT department, batch FROM tutor_login WHERE tutor_id = ?";
    $stmt = $conn->prepare($tutor_info_query);
    $stmt->bind_param("i", $tutor_id);
    $stmt->execute();
    $tutor_info_result = $stmt->get_result();
    $tutor_info = $tutor_info_result->fetch_assoc();

    if (!$tutor_info) {
        $_SESSION['error'] = "Error: Tutor information not found!";
        header("Location: tutor-dashboard.php");
        exit();
    }

    // Fetch student details
    $query = "SELECT * FROM voter_reg WHERE student_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        $student_dept = $row['department'];
        $student_batch = $row['batch'];
        $email = $row['email'];
        $voter_status = $row['status'];
        $student_id = $row['student_id'];

        // Check if the tutor is authorized to approve this student
        if ($student_dept != $tutor_info['department'] || $student_batch != $tutor_info['batch']) {
            $_SESSION['error'] = "❌ Unauthorized: You can only approve students from your assigned department and batch.";
            header("Location: tutor-dashboard.php");
            exit();
        }
        
        // Check if voter is already approved
        if ($voter_status == 'approved') {
            $_SESSION['error'] = "❌ This student is already approved as a voter.";
            header("Location: tutor-dashboard.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "❌ Student not found!";
        header("Location: tutor-dashboard.php");
        exit();
    }
 
    require 'vendor/autoload.php'; // Load PHPMailer
    
    // ✅ Function to Send Voter Email Based on Scenarios
    function sendVoterEmail($toEmail, $voterCode, $password, $scenario, $student_id = '', $position = '') {
        $mail = new PHPMailer(true);
    
        try {
            // SMTP Configuration
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'electionadm2025@gmail.com'; // Your Gmail
            $mail->Password = 'ytwhxdrgxmzacdam'; // App Password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
    
            // Email Content
            $mail->setFrom('electionadm2025@gmail.com', 'Election Admin');
            $mail->addAddress($toEmail);
    
            // 🎯 Handle Different Scenarios
            switch ($scenario) {
                case 1:
                    // ✅ Scenario 1: Voter Only Approved, No Candidate Application
                    $mail->Subject = "Voter Registration Approved & Credentials Enclosed";
                    $mail->Body = "Dear Voter,\n\n"
                                . "Congratulations! Your voter registration has been successfully approved.\n\n"
                                . "Here are your login credentials:\n"
                                . "🔹 Voter Code: $voterCode\n"
                                . "🔹 Password: $password\n\n"
                                . "👉 Please log in to the voting system using these credentials to cast your vote.\n\n"
                                . "If you encounter any issues, feel free to contact the Election Support Team.\n\n"
                                . "Best regards,\nElection Admin";
                    break;
    
                case 2:
                    // ✅ Scenario 2: Voter Approved, Candidate Pending
                    $mail->Subject = "Voter Credentials & Candidate Application Status";
                    $mail->Body = "Dear Voter,\n\n"
                                . "Your voter registration has been successfully approved. However, your candidate application is still under review.\n\n"
                                . "Here are your login credentials:\n"
                                . "🔹 Voter Code: $voterCode\n"
                                . "🔹 Password: $password\n\n"
                                . "📢 Candidate Application Status: Pending Approval\n\n"
                                . "👉 Please log in to the voting system to view your status and stay informed.\n\n"
                                . "If you need assistance, please contact the Election Support Team.\n\n"
                                . "Best regards,\nElection Admin";
                    break;
    
                case 3:
                    // ✅ Scenario 3: Both Voter and Candidate Approved
                    $mail->Subject = "Approval Notification: Voter & Candidate Credentials";
                    $mail->Body = "Dear User,\n\n"
                                . "We are pleased to inform you that your voter and candidate applications have been approved successfully.\n\n"
                                . "✅ Voter Credentials:\n"
                                . "🔹 Voter Code: $voterCode\n"
                                . "🔹 Password: $password\n\n"
                                . "✅ Candidate Credentials:\n"
                                . "🔹 Candidate ID: $candidateID\n"
                                . "🔹 Approved Position: $position_name\n\n"
                                . "👉 Please log in to the system to perform your respective roles.\n\n"
                                . "If you encounter any issues, please do not hesitate to contact the Election Support Team.\n\n"
                                . "Best regards,\nElection Admin";
                    break;
    
                // ❌ Scenarios 4 and 5 - No Email Should Be Sent
                case 4:
                    // Candidate Approved, Voter Still Pending (No Email Required)
                    return false; // Skip sending email
                    break;
    
                case 5:
                    // Candidate Approved, Voter Rejected (No Email Required)
                    return false; // Skip sending email
                    break;
    
                default:
                    return false; // Invalid scenario
            }
    
            // Send Email if Required
            return $mail->send();
        } catch (Exception $e) {
            return false; // Email failed
        }
    }
    
    // First, check if we need to modify the voter_reg table
    $check_fields_query = "SHOW COLUMNS FROM voter_reg LIKE 'voter_code'";
    $result = $conn->query($check_fields_query);
    
    // If voter_code column doesn't exist, add it
    if ($result->num_rows == 0) {
        $alter_table_query = "ALTER TABLE voter_reg ADD COLUMN voter_code VARCHAR(20) AFTER password";
        $conn->query($alter_table_query);
    }

    // Generate Voter Code & Password
    $generated_voter_code = generateVoterCode();
    $generated_password = generatePassword();
    $hashed_password = password_hash($generated_password, PASSWORD_BCRYPT);

    // Update Voter Table with voter_code, password and approved status
    $update_query = "UPDATE voter_reg 
                     SET status = 'approved', voter_code = ?, password = ? 
                     WHERE student_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("sss", $generated_voter_code, $hashed_password, $student_id);

    if ($stmt->execute()) {
        // Determine email scenario based on candidate status
        $scenario = 1; // Default: Voter approved only
        
        // Check if candidate_id exists and is not null/empty
        if (!empty($student_id)) {
            // Get candidate status
            $candidate_query = "SELECT status, position FROM candidates WHERE student_id = ?";
            $stmt = $conn->prepare($candidate_query);
            $stmt->bind_param("s", $student_id);
            $stmt->execute();
            $cand_result = $stmt->get_result();
            
            if ($cand_result->num_rows > 0) {
                $cand_row = $cand_result->fetch_assoc();
                $cand_status = $cand_row['status'];
                $position = $cand_row['position'] ?? '';
                
                if ($cand_status == 'pending') {
                    $scenario = 2; // Voter approved, candidate pending
                } elseif ($cand_status == 'approved') {
                    $scenario = 3; // Both approved
                }
            }
        }
        
        // Send appropriate email based on scenario
        if (sendVoterEmail($email, $generated_voter_code, $generated_password, $scenario, $student_id, $position)) {
            $_SESSION['success'] = "✅ Voter approved successfully! Approval email sent.";
        } else {
            $_SESSION['warning'] = "✅ Voter approved successfully, but email could not be sent.";
        }
    } else {
        $_SESSION['error'] = "❌ Error updating voter status: " . $conn->error;
    }

    header("Location: tutor-dashboard.php");
    exit();
}
?>