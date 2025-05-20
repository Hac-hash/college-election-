<?php
session_start();
include('connect.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = trim($_POST['student_id']);
    $name = trim($_POST['name']);
    $gender = trim($_POST['gender']);
    $department = trim($_POST['department']);
    $batch = trim($_POST['batch']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $is_candidate = isset($_POST['is_candidate']) ? 1 : 0;
    $candidate_id = $is_candidate ? trim($_POST['candidate_id']) : NULL;

    // ✅ Restrict valid batch input to 11 only
    if ($batch != '11') {
        echo "<script>alert('Error: Only Batch 11 students are allowed to register as voters.'); window.location.href='voter_register.php';</script>";
        exit();
    }

    // ✅ Check if student ID already exists
    $checkStudentQuery = "SELECT * FROM voter_reg WHERE student_id = ?";
    $stmt = $conn->prepare($checkStudentQuery);
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<script>alert('This Student ID is already registered.'); window.location.href='voter_register.php';</script>";
        exit();
    }

    // ✅ Check if email already exists
    $checkEmailQuery = "SELECT * FROM voter_reg WHERE email = ?";
    $stmt = $conn->prepare($checkEmailQuery);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $emailResult = $stmt->get_result();

    if ($emailResult->num_rows > 0) {
        echo "<script>alert('This Email is already registered. Use a different email.'); window.location.href='voter_register.php';</script>";
        exit();
    }

    // ✅ Insert voter data
    $query = "INSERT INTO voter_reg (student_id, name, gender, department, batch, email, phone, status) 
              VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssssss", $student_id, $name, $gender, $department, $batch, $email, $phone);

    if ($stmt->execute()) {
        $voter_id = $conn->insert_id;  // Get newly inserted Voter ID

        // ✅ Link Candidate if Candidate ID is Provided
        if ($is_candidate && !empty($candidate_id)) {
            // Check if the Candidate ID exists
            $checkCandidateQuery = "SELECT id FROM candidates WHERE id = ?";
            $stmt = $conn->prepare($checkCandidateQuery);
            $stmt->bind_param("i", $candidate_id);
            $stmt->execute();
            $candidateResult = $stmt->get_result();

            if ($candidateResult->num_rows > 0) {
                // ✅ Link candidate with voter after voter registration
                $linkCandidateQuery = "UPDATE candidates SET voter_id = ? WHERE id = ?";
                $stmt = $conn->prepare($linkCandidateQuery);
                $stmt->bind_param("ii", $voter_id, $candidate_id);
                $stmt->execute();

                // ✅ Link candidate_id to voter_reg for dual role
                $linkVoterQuery = "UPDATE voter_reg SET candidate_id = ? WHERE id = ?";
                $stmt = $conn->prepare($linkVoterQuery);
                $stmt->bind_param("ii", $candidate_id, $voter_id);
                $stmt->execute();
            }
        }

        echo "<script>alert('Voter registration successful!'); window.location.href='voter_register.php';</script>";
    } else {
        echo "<script>alert('Error: Unable to register voter.'); window.location.href='voter_register.php';</script>";
    }

    $stmt->close();
    $conn->close();
}
?>
