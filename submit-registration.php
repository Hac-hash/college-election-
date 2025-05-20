<?php
session_start();
include('connect.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = trim($_POST['student_id']);
    $name = trim($_POST['name']);
    $gender = trim($_POST['gender']);
    $department = trim($_POST['department']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $position_id = trim($_POST['position_id']);
    $bio = trim($_POST['bio']);
    $is_voter = isset($_POST['is_voter']) ? 1 : 0;
    $voter_id = $is_voter ? trim($_POST['voter_id']) : NULL;

    // Check if voter is registered and approved
    $checkVoterQuery = "SELECT id, batch FROM voter_reg WHERE student_id = ? AND status = 'approved'";
    $stmt = $conn->prepare($checkVoterQuery);
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $voterResult = $stmt->get_result();

    if ($voterResult->num_rows == 0) {
        echo "<script>alert('Error: You must be a registered voter to apply as a candidate.'); window.location.href='register.php';</script>";
        exit();
    } else {
        $voter = $voterResult->fetch_assoc();
        $voter_batch = $voter['batch'];
        if ($voter_batch != '11') {
            echo "<script>alert('Error: Only final-year students (Batch 11) are allowed to apply as candidates!'); window.location.href='register.php';</script>";
            exit();
        }
    }

    // ✅ Handle file uploads (photo & PDF)
    $target_dir = "uploads/";
    $photo_filename = time() . '_' . basename($_FILES["photo"]["name"]);
    $pdf_filename = time() . '_' . basename($_FILES["pdf"]["name"]);
    $target_photo = $target_dir . $photo_filename;
    $target_pdf = $target_dir . $pdf_filename;

    // Create the directory if it doesn't exist
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // ✅ Move uploaded files
    if (move_uploaded_file($_FILES["photo"]["tmp_name"], $target_photo) && 
        move_uploaded_file($_FILES["pdf"]["tmp_name"], $target_pdf)) {

        // Insert candidate data with file names and pending status
        $query = "INSERT INTO candidates (student_id, name, gender, department, email, phone, position_id, bio, photo, eligibility_document, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssssss", $student_id, $name, $gender, $department, $email, $phone, $position_id, $bio, $photo_filename, $pdf_filename);

        if ($stmt->execute()) {
            $candidate_id = $conn->insert_id;  // Get newly inserted Candidate ID

            // ✅ Link Candidate with Voter if Voter ID Provided
            if ($is_voter && !empty($voter_id)) {
                // Check if the provided Voter ID is valid
                $checkVoterQuery = "SELECT id FROM voter_reg WHERE id = ?";
                $stmt = $conn->prepare($checkVoterQuery);
                $stmt->bind_param("i", $voter_id);
                $stmt->execute();
                $voterResult = $stmt->get_result();

                if ($voterResult->num_rows > 0) {
                    // ✅ Link voter_id to candidate after registration
                    $linkQuery = "UPDATE candidates SET voter_id = ? WHERE id = ?";
                    $stmt = $conn->prepare($linkQuery);
                    $stmt->bind_param("ii", $voter_id, $candidate_id);
                    $stmt->execute();

                    // ✅ Link candidate_id to voter_reg for dual roles
                    $updateVoterQuery = "UPDATE voter_reg SET candidate_id = ? WHERE id = ?";
                    $stmt = $conn->prepare($updateVoterQuery);
                    $stmt->bind_param("ii", $candidate_id, $voter_id);
                    $stmt->execute();
                }
            }

            echo "<script>alert('Candidate registration successful. Awaiting approval.'); window.location.href='index.php';</script>";
        } else {
            echo "<script>alert('Error: Unable to register candidate.'); window.location.href='register.php';</script>";
        }
    } else {
        echo "<script>alert('Error uploading files. Please try again.'); window.location.href='register.php';</script>";
    }
}
?>
