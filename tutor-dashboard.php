<?php
session_start();
include('connect.php');
include('send_email.php'); // Include the send_email.php file

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

// Check if user is logged in and is a tutor
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'tutor') {
    header("Location: tutor-login.php");
    exit();
}

// Check if tutor_id is set in session
if (!isset($_SESSION['tutor_id'])) {
    echo "<script>alert('Access Denied! Please log in.'); window.location.href='tutor-login.php';</script>";
    exit();
}

// Get logged-in tutor ID
$tutor_id = $_SESSION['tutor_id']; 

// First, fetch the tutor's assigned department and batch
$tutor_info_query = "SELECT department, batch FROM tutor_login WHERE tutor_id = ?";
$stmt = $conn->prepare($tutor_info_query);
$stmt->bind_param("i", $tutor_id);
$stmt->execute();
$tutor_info_result = $stmt->get_result();
$tutor_info = $tutor_info_result->fetch_assoc();

if (!$tutor_info) {
    echo "<div class='message error'>Error: Tutor information not found!</div>";
    exit();
}

// Success message handling
$success_message = "";
if (isset($_SESSION['success'])) {
    $success_message = $_SESSION['success'];
    unset($_SESSION['success']);
}

// Error message handling
// Removed error message display from dashboard

// Handle logout
if (isset($_POST['logout'])) {
    $_SESSION['success'] = "Logout successful!";
    session_destroy();
    header("Location: index.php");
    exit();
}

// Function to generate a Voter ID
function generateVoterID($student_id) {
    return $student_id ?: "VOT" . rand(1000, 9999);
}

// Function to generate a random password
function generatePassword($length = 8) {
    return substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789"), 0, $length);
}

// Handle voter approval
if (isset($_GET['approve_id'])) {
    $student_id = $_GET['approve_id'];

    // Fetch Student ID, Email, Department & Batch
    $query = "SELECT student_id, email, department, batch FROM voter_reg WHERE student_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $email = $row['email'];
        $student_dept = $row['department'];
        $student_batch = $row['batch'];

        // Security check: Verify tutor is authorized for this student
        if ($student_dept != $tutor_info['department'] || $student_batch != $tutor_info['batch']) {
            $_SESSION['error'] = "❌ Unauthorized: You can only approve students from your assigned department and batch.";
            header("Location: tutor-dashboard.php");
            exit();
        }

        // Generate Voter ID & Password
        $generated_voter_id = generateVoterID($student_id);
        $generated_password = generatePassword();
        $hashed_password = password_hash($generated_password, PASSWORD_BCRYPT);

        // Update Voter Table with Voter ID & Password
        $update_query = "UPDATE voter_reg 
                         SET status = 'approved', student_id = ?, password = ? 
                         WHERE student_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("sss", $generated_voter_id, $hashed_password, $student_id);
        $stmt->execute();

        // Check if the student is also a candidate
        $check_candidate_query = "SELECT student_id FROM candidates WHERE student_id = ?";
        $stmt = $conn->prepare($check_candidate_query);
        $stmt->bind_param("s", $student_id);
        $stmt->execute();
        $candidate_result = $stmt->get_result();
        
        if ($candidate_row = $candidate_result->fetch_assoc()) {
            // Student is also a candidate
            $candidate_status_query = "SELECT status FROM candidates WHERE student_id = ?";
            $stmt = $conn->prepare($candidate_status_query);
            $stmt->bind_param("s", $student_id);
            $stmt->execute();
            $status_result = $stmt->get_result();
            $status_row = $status_result->fetch_assoc();
            
            if ($status_row['status'] == 'approved') {
                // Both voter and candidate approved (Scenario 4)
                if (sendVoterEmail($email, 4, $generated_voter_id, $generated_password, $candidate_row['student_id'], $candidate_row['position'])) {
                    $_SESSION['success'] = "✅ Voter Approved! Login details sent via Email.";
                } else {
                    $_SESSION['success'] = "✅ Voter Approved! Email sending failed.";
                }
            } else {
                // Voter approved, candidate pending (Scenario 2)
                if (sendVoterEmail($email, 2, $generated_voter_id, $generated_password)) {
                    $_SESSION['success'] = "✅ Voter Approved! Login details sent via Email.";
                } else {
                    $_SESSION['success'] = "✅ Voter Approved! Email sending failed.";
                }
            }
        } else {
            // Just a voter, no candidate application (Scenario 1)
            if (sendVoterEmail($email, 1, $generated_voter_id, $generated_password)) {
                $_SESSION['success'] = "✅ Voter Approved! Login details sent via Email.";
            } else {
                $_SESSION['success'] = "✅ Voter Approved! Email sending failed.";
            }
        }
        
        header("Location: tutor-dashboard.php");
        exit();
    } else {
        $_SESSION['error'] = "❌ Voter not found!";
        header("Location: tutor-dashboard.php");
        exit();
    }
}

// Handle voter removal
if (isset($_GET['remove_id'])) {
    $student_id = $_GET['remove_id'];
    
    // Security check: Verify tutor is authorized for this student
    $check_auth_query = "SELECT department, batch FROM voter_reg WHERE student_id = ?";
    $stmt = $conn->prepare($check_auth_query);
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $auth_result = $stmt->get_result();
    
    if ($student = $auth_result->fetch_assoc()) {
        if ($student['department'] != $tutor_info['department'] || $student['batch'] != $tutor_info['batch']) {
            $_SESSION['error'] = "❌ Unauthorized: You can only remove students from your assigned department and batch.";
            header("Location: tutor-dashboard.php");
            exit();
        }
        
        // Delete the voter
        $delete_query = "DELETE FROM voter_reg WHERE student_id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("s", $student_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "❌ Voter removed successfully!";
        } else {
            $_SESSION['error'] = "❌ Error removing voter.";
        }
    } else {
        $_SESSION['error'] = "❌ Voter not found!";
    }
    
    header("Location: tutor-dashboard.php");
    exit();
}

// Now fetch only the students from this tutor's department and batch
$query = "SELECT * FROM voter_reg WHERE department = ? AND batch = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $tutor_info['department'], $tutor_info['batch']);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tutor Dashboard</title>
    <link rel="stylesheet" href="login.css">
    <style>
        /* Sidebar */
        .sidebar {
            width: 250px;
            background-color: #1f2833;
            height: 100vh;
            position: fixed;
            padding-top: 30px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .sidebar h1 {
            color: #66fcf1;
            text-align: center;
            font-size: 1.8em;
            margin-bottom: 30px;
        }
        .sidebar a {
            padding: 15px 25px;
            text-decoration: none;
            color: #ffffff;
            display: block;
            font-size: 18px;
            text-align: center;
            width: 90%;
            transition: 0.3s;
        }
        .sidebar a:hover {
            background-color: #45a29e;
        }
        .logout-btn {
            padding: 15px 25px;
            background-color: #dc3545;
            color: white;
            width: 100%;
            border: none;
            cursor: pointer;
            margin-top: auto;
            font-size: 18px;
            text-align: center;
            transition: 0.3s;
        }
        .logout-btn:hover {
            background-color: #c82333;
        }

        /* Content */
        .content {
            margin-left: 250px;
            padding: 50px;
            width: calc(100% - 250px);
            background-color: #f0f0f0;
            min-height: 100vh;
        }
        .content h1 {
            font-size: 2.5em;
            color: #0b0c10;
            margin-bottom: 20px;
        }

        /* Message Styles */
        .message {
            background-color: #dff0d8;
            color: #3c763d;
            padding: 10px;
            margin-bottom: 15px;
            text-align: center;
            font-weight: bold;
            border-radius: 4px;
        }
        .success-message {
            background-color: #dff0d8;
            color: #3c763d;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            text-align: center;
            font-weight: bold;
            font-size: 16px;
            border: 1px solid #c3e6cb;
        }

        /* Toast Notification for Success Message */
        .toast {
            visibility: hidden;
            min-width: 250px;
            background-color: #d4edda;
            color: #155724;
            text-align: center;
            border-radius: 5px;
            padding: 16px;
            position: fixed;
            z-index: 1;
            top: 30px;
            right: 30px;
            font-size: 17px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        .toast.show {
            visibility: visible;
            -webkit-animation: fadein 0.5s, fadeout 0.5s 2.5s;
            animation: fadein 0.5s, fadeout 0.5s 2.5s;
        }
        @-webkit-keyframes fadein {
            from {top: 0; opacity: 0;}
            to {top: 30px; opacity: 1;}
        }
        @keyframes fadein {
            from {top: 0; opacity: 0;}
            to {top: 30px; opacity: 1;}
        }
        @-webkit-keyframes fadeout {
            from {top: 30px; opacity: 1;}
            to {top: 0; opacity: 0;}
        }
        @keyframes fadeout {
            from {top: 30px; opacity: 1;}
            to {top: 0; opacity: 0;}
        }

        /* Voter Table */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        th, td {
            border: 1px solid #ccc;
            padding: 12px;
            text-align: left;
        }
        th {
            background: #45a29e;
            color: white;
        }
        .approve-btn, .remove-btn, .reject-btn {
            padding: 8px 12px;
            border: none;
            cursor: pointer;
            border-radius: 4px;
            color: white;
            margin-right: 5px;
            margin-bottom: 5px;
        }
        .approve-btn {
            background: #28a745;
        }
        .remove-btn {
            background: #dc3545;
        }
        .reject-btn {
            background: #ffc107;
            color: #212529;
        }
        .approve-btn:hover {
            background: #218838;
        }
        .remove-btn:hover {
            background: #c82333;
        }
        .reject-btn:hover {
            background: #e0a800;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: bold;
        }
        .status-pending {
            background-color: #ffc107;
            color: #212529;
        }
        .status-approved {
            background-color: #28a745;
            color: white;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <h1>Tutor Dashboard</h1>
        <a href="tutor-dashboard.php">View Voters</a>
        <form method="POST">
            <button type="submit" name="logout" class="logout-btn">Logout</button>
        </form>
    </div>

    <!-- Content -->
    <div class="content">
        <h2>Welcome, Tutor (<?php echo $tutor_info['department'] . ' - Batch ' . $tutor_info['batch']; ?>)</h2>
        
        <?php if (!empty($success_message) && $success_message !== "Login successful!"): ?>
            <div class="success-message"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <h1>Registered Voters</h1>
        
        <table>
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>Department</th>
                    <th>Batch</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) { 
                ?>
                <tr>
                    <td><?php echo $row['student_id']; ?></td>
                    <td><?php echo $row['name']; ?></td>
                    <td><?php echo $row['department']; ?></td>
                    <td><?php echo $row['batch']; ?></td>
                    <td><?php echo $row['email']; ?></td>
                    <td>
                        <span class="status-badge <?php echo $row['status'] == 'approved' ? 'status-approved' : 'status-pending'; ?>">
                            <?php echo ucfirst($row['status']); ?>
                        </span>
                    </td>
                    <td>
                        <?php 
                        if ($row['status'] == 'pending') { ?>
                            <button onclick="confirmApproval('<?php echo $row['student_id']; ?>')" class="approve-btn">Approve</button>
                            <form method="POST" action="reject_voter.php">
                                <input type="hidden" name="student_id" value="<?php echo $row['student_id']; ?>">
                                <button type="submit" class="reject-btn">Reject</button>
                            </form>
                        <?php } else if ($row['status'] == 'approved') { ?>
                            <button onclick="confirmRemoval('<?php echo $row['student_id']; ?>')" class="remove-btn">Delete</button>
                        <?php } ?>
                    </td>
                </tr>
                <?php 
                    }
                } else {
                ?>
                <tr>
                    <td colspan="7" style="text-align: center;">No voters found for your department and batch.</td>
                </tr>
                <?php } ?>
            </tbody>
        </table>

        <!-- Toast Notification for Login Success Message -->
        <?php if (!empty($success_message) && $success_message === "Login successful!"): ?>
            <div id="toast" class="toast">
                <?php echo $success_message; ?>
            </div>
            <script>
                // Show the toast notification and hide it after 3 seconds
                document.addEventListener('DOMContentLoaded', function() {
                    var toast = document.getElementById("toast");
                    toast.className = "toast show";
                });
            </script>
        <?php endif; ?>
    </div>

    <!-- JavaScript for confirmation dialogs -->
    <script>
        function confirmApproval(studentId) {
            if (confirm("Are you sure you want to approve this voter?")) {
                window.location.href = 'tutor-dashboard.php?approve_id=' + studentId;
            }
        }
        
        function confirmRemoval(studentId) {
            if (confirm("Are you sure you want to delete this voter?")) {
                window.location.href = 'tutor-dashboard.php?remove_id=' + studentId;
            }
        }
    </script>
</body>
</html>