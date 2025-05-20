<?php
session_start();
include('connect.php');

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and get form data
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    $recipient = mysqli_real_escape_string($conn, $_POST['recipient']);
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $issue_text = mysqli_real_escape_string($conn, $_POST['issue']);
    
    // Basic validation
    if (empty($student_id) || empty($recipient) || empty($subject) || empty($issue_text)) {
        error_log("Validation failed: student_id=$student_id, recipient=$recipient, subject=$subject, issue_text=$issue_text");
        header("Location: grievance.php?message=error");
        exit();
    }
    
    // Insert into database
    $query = "INSERT INTO issues (student_id, recipient, subject, issue_text, status, created_at) 
              VALUES (?, ?, ?, ?, 'new', NOW())";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssss", $student_id, $recipient, $subject, $issue_text);
    
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        header("Location: grievance.php?message=success");
        exit();
    } else {
        // Log the database error for debugging
        error_log("Database error: " . $stmt->error);
        $stmt->close();
        $conn->close();
        header("Location: grievance.php?message=error");
        exit();
    }
} else {
    // If not POST request, redirect to grievance form
    header("Location: grievance.php");
    exit();
}
?>