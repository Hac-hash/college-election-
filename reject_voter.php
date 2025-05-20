<?php
session_start();
include('connect.php');

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

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['student_id'])) {
    $student_id = $_POST['student_id'];
    
    // First, fetch the tutor's assigned department and batch
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
    
    // Fetch Student details
    $query = "SELECT * FROM voter_reg WHERE student_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        $student_dept = $row['department'];
        $student_batch = $row['batch'];
        
        // Security check: Verify tutor is authorized for this student
        if ($student_dept != $tutor_info['department'] || $student_batch != $tutor_info['batch']) {
            $_SESSION['error'] = "❌ Unauthorized: You can only reject students from your assigned department and batch.";
            header("Location: tutor-dashboard.php");
            exit();
        }
        
        // Delete the voter registration
        $delete_query = "DELETE FROM voter_reg WHERE student_id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("s", $student_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "❌ Voter registration rejected and removed from the system.";
        } else {
            $_SESSION['error'] = "❌ Error removing voter registration: " . $conn->error;
        }
    } else {
        $_SESSION['error'] = "❌ Student not found!";
    }
    
    header("Location: tutor-dashboard.php");
    exit();
}
?>