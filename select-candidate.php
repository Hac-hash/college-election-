<?php
session_start(); // Ensure session is started
include('connect.php');

// Include send_email.php file
include('send_email.php');

// Handle candidate deletion
if (isset($_GET['delete_id'])) {
    $student_id = mysqli_real_escape_string($conn, $_GET['delete_id']);

    // Delete the candidate from the 'selected_candidate' table first (if they exist there)
    $delete_selected_query = "DELETE FROM selected_candidate WHERE student_id = ?";
    $stmt = mysqli_prepare($conn, $delete_selected_query);
    mysqli_stmt_bind_param($stmt, "s", $student_id);
    mysqli_stmt_execute($stmt);

    // Now delete the candidate from the 'candidates' table
    $query = "DELETE FROM candidates WHERE student_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $student_id);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "<script>alert('Candidate deleted successfully');</script>";
    } else {
        echo "Error deleting candidate: " . mysqli_error($conn);
    }
}

// Handle candidate selection
if (isset($_POST['select_candidates'])) {
    $selected_candidates = isset($_POST['selected_candidates']) ? $_POST['selected_candidates'] : [];

    if (empty($selected_candidates)) {
        echo "<script>alert('You must select at least one candidate.');</script>";
    } else {
        // Insert the selected candidates into the 'selected_candidate' table without truncating
        foreach ($selected_candidates as $student_id) {
            // Use prepared statement to get candidate info
            $query = "SELECT * FROM candidates WHERE student_id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "s", $student_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $candidate = mysqli_fetch_assoc($result);

            $name = $candidate['name'];
            $photo = $candidate['photo'];
            $bio = $candidate['bio']; 
            $department = $candidate['department'];
            $student_id = $candidate['student_id'];
            $position_id = $candidate['position_id'];
            $eligibility_document = $candidate['eligibility_document'];
            $status = 'approved'; // Set status to 'approved' by default for selected candidates

            // Important Fix: Check if the candidate is already in 'selected_candidate' using both student_id and position_id
            $check_query = "SELECT * FROM selected_candidate WHERE student_id = ? AND position_id = ?";
            $stmt = mysqli_prepare($conn, $check_query);
            mysqli_stmt_bind_param($stmt, "ss", $student_id, $position_id);
            mysqli_stmt_execute($stmt);
            $check_result = mysqli_stmt_get_result($stmt);

            if (mysqli_num_rows($check_result) == 0) {
                // Insert candidate into 'selected_candidate' with prepared statement
                $insert_query = "INSERT INTO selected_candidate (student_id, name, photo, department, position_id, eligibility_document, bio, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = mysqli_prepare($conn, $insert_query);
                mysqli_stmt_bind_param($stmt, "ssssssss", $student_id, $name, $photo, $department, $position_id, $eligibility_document, $bio, $status);
                
                if (mysqli_stmt_execute($stmt)) {
                    // Update candidate status to 'approved' in candidates table
                    $update_query = "UPDATE candidates SET status = 'approved' WHERE student_id = ?";
                    $stmt = mysqli_prepare($conn, $update_query);
                    mysqli_stmt_bind_param($stmt, "s", $student_id);
                    mysqli_stmt_execute($stmt);

                    // Send approval email using sendVoterEmail() from send_email.php
                    $position_query = "SELECT position_name FROM positions WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $position_query);
                    mysqli_stmt_bind_param($stmt, "s", $position_id);
                    mysqli_stmt_execute($stmt);
                    $position_result = mysqli_stmt_get_result($stmt);
                    $position_data = mysqli_fetch_assoc($position_result);
                    $position_name = $position_data['position_name'] ?? 'Unknown Position';

                    // Send approval email with the correct position name
                    $scenario = 3; // Scenario 3: Candidate Approved
                    if (function_exists('sendVoterEmail')) {
                        sendVoterEmail($candidate['email'], $scenario, '', '', $student_id, $position_name);
                    }
                }
            } else {
                // Candidate is already selected for this position - Skip insertion and show error
                echo "<script>alert('⚠️ Candidate " . $name . " is already approved for this position.');</script>";
            }
        } // End of foreach loop
    
        // Redirect with success parameter
        header("Location: select-candidate.php?success=true");
        exit();
    }
}

// Rest of the code remains the same...
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Candidates</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f4f4f4;
        }
        header {
            text-align: center;
            margin-bottom: 20px;
        }
        .candidate-list {
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .candidates {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }
        .candidate {
            border: 1px solid #ccc;
            padding: 15px;
            border-radius: 5px;
            width: calc(33.33% - 30px);
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.1);
            background-color: #fff;
            transition: transform 0.2s;
            position: relative;
        }
        .candidate:hover {
            transform: scale(1.02);
        }
        .candidate img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 10px;
        }
        .delete-button {
            color: red;
            text-decoration: none;
            font-weight: bold;
            cursor: pointer;
        }
        .delete-button:hover {
            text-decoration: underline;
        }
        button {
            background-color: rgb(8, 10, 11);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: background-color 0.3s, transform 0.3s;
            margin: 10px 0;
        }
        button:hover {
            background-color: rgb(0, 179, 78);
            transform: translateY(-2px);
        }
        .alert {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
        }
        /* Status indicator styles */
        .status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            color: white;
        }
        .status-pending {
            background-color: #ffc107;
        }
        .status-approved {
            background-color: #28a745;
        }
        .status-rejected {
            background-color: #dc3545;
        }
    </style>
</head>
<body>

<header>
    <h1>Select Candidates for Voting</h1>
</header>

<main>
    <?php if (isset($_GET['success'])): ?>
        <div class="alert">Candidates selected successfully!</div>
    <?php endif; ?>

    <form method="POST" action="select-candidate.php" onsubmit="return confirmSelection()">
        <?php
        // Fetch all positions
        $positions_query = "SELECT * FROM positions ORDER BY position_name";
        $positions_result = mysqli_query($conn, $positions_query);
        
        while ($position = mysqli_fetch_assoc($positions_result)) {
            $position_id = $position['id'];
            
            // Fetch candidates for this position (excluding rejected candidates)
            $candidate_query = "SELECT * FROM candidates WHERE position_id = ? AND (status != 'rejected' OR status IS NULL)";
            $stmt = mysqli_prepare($conn, $candidate_query);
            mysqli_stmt_bind_param($stmt, "s", $position_id);
            mysqli_stmt_execute($stmt);
            $candidate_result = mysqli_stmt_get_result($stmt);
            $candidate_count = mysqli_num_rows($candidate_result);
        ?>
            <section class="candidate-list">
                <div class="position-header">
                    <?php echo htmlspecialchars($position['position_name']); ?>
                    <span style="font-size: 14px; color: #6c757d;">
                        (<?php echo $candidate_count; ?> candidate<?php echo $candidate_count != 1 ? 's' : ''; ?>)
                    </span>
                </div>

                <?php if ($candidate_count > 0): ?>
                    <div class="candidates">
                        <?php
                        while ($candidate = mysqli_fetch_assoc($candidate_result)) {
                            // Check if the candidate is already selected
                            $check_query = "SELECT * FROM selected_candidate WHERE student_id = ?";
                            $stmt = mysqli_prepare($conn, $check_query);
                            mysqli_stmt_bind_param($stmt, "s", $candidate['student_id']);
                            mysqli_stmt_execute($stmt);
                            $check_result = mysqli_stmt_get_result($stmt);
                            $is_selected = (mysqli_num_rows($check_result) > 0);
                            
                            // Determine status for badge
                            $status = $candidate['status'] ?? 'pending';
                            $status_class = 'status-' . $status;
                            $status_text = ucfirst($status);
                            
                            echo "
                            <div class='candidate'>
                                <div class='status-badge " . $status_class . "'>" . $status_text . "</div>
                                <img src='uploads/" . htmlspecialchars($candidate['photo']) . "' alt='Candidate Photo'>
                                <p><strong>Name:</strong> " . htmlspecialchars($candidate['name']) . "</p>
                                <p><strong>Student ID:</strong> " . htmlspecialchars($candidate['student_id']) . "</p>
                                <p><strong>Department:</strong> " . htmlspecialchars($candidate['department']) . "</p>
                                <p><strong>Position:</strong> " . htmlspecialchars($position['position_name']) . "</p>
                                <p><strong>Email:</strong> " . htmlspecialchars($candidate['email']) . "</p>
                                 <p><strong>bio:</strong> " . htmlspecialchars($candidate['bio']) . "</p>
                                <p><strong>Phone:</strong> " . htmlspecialchars($candidate['phone']) . "</p>";

                                // Show PDF download link if available
                                if (!empty($candidate['eligibility_document'])) {
                                    echo "<p><strong>Eligibility Document:</strong> 
                                    <a href='uploads/" . htmlspecialchars($candidate['eligibility_document']) . "' target='_blank'>View PDF</a></p>";
                                } else {
                                    echo "<p style='color:red;'>Eligibility PDF Not Uploaded</p>";
                                }
                                
                                echo "<div style='margin-top: 10px;'>
                                <input type='checkbox' name='selected_candidates[]' value='" . $candidate['student_id'] . "' " . 
                                     ($is_selected ? "checked disabled" : "") . "> " . 
                                     ($is_selected ? "<span style='color:green;'>Already Selected</span>" : "Select as candidate ") . "
                                <a href='#' class='delete-button' onclick='confirmDeletion(\"" . $candidate['student_id'] . "\")'>Delete</a>
                                </div>
                            </div>
                            ";
                        }
                        ?>
                    </div>
                <?php else: ?>
                    <div class="no-candidates">No candidates registered for this position yet.</div>
                <?php endif; ?>
            </section>
        <?php } ?>

        <div class="button-container">
            <button type="submit" name="select_candidates">Approve Selected Candidates</button>
            <button type="button" onclick="window.location.href='staff-dashboard.php'">Return to Staff Dashboard</button>
        </div>
    </form>
</main>

<script>
    function confirmSelection() {
        let checkboxes = document.querySelectorAll('input[name="selected_candidates[]"]:checked:not([disabled])');
        if (checkboxes.length === 0) {
            alert("You haven't selected any new candidates.");
            return false;
        }

        if (confirm("Are you sure you want to approve the selected candidates for voting?")) {
            return true; // Proceed with form submission
        }
        return false; // Prevent form submission
    }

    function confirmDeletion(student_id) {
        if (confirm("Are you sure you want to delete this candidate?")) {
            window.location.href = 'select-candidate.php?delete_id=' + student_id; // Redirect to delete candidate
        }
    }
</script>

</body>
</html>