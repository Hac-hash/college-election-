<?php
include('connect.php');
include('send_email.php'); // Include send_email.php to use sendVoterEmail()

// Handle candidate removal from the selected_candidate table
if (isset($_GET['remove_id'])) {
    $student_id = mysqli_real_escape_string($conn, $_GET['remove_id']);

    // Start a transaction to ensure both deletions happen together
    mysqli_begin_transaction($conn);

    try {
        // Get candidate details before removing
        $candidate_query = "SELECT c.*, email 
                            FROM candidates c
                            JOIN selected_candidate sc ON c.student_id = sc.student_id
                            WHERE c.student_id = ?";
        
        $stmt = mysqli_prepare($conn, $candidate_query);
        mysqli_stmt_bind_param($stmt, "s", $student_id);
        mysqli_stmt_execute($stmt);
        $candidate_result = mysqli_stmt_get_result($stmt);
        $candidate = mysqli_fetch_assoc($candidate_result);

        // Send rejection email if candidate exists
        if ($candidate) {
            $scenario = 5; // Scenario 5: Candidate Rejected
            if (function_exists('sendVoterEmail')) {
                sendVoterEmail($candidate['email'], $scenario, '', '', $student_id, '');
            }
        }

        // Remove all votes associated with the candidate from the votes table
        $remove_votes_query = "DELETE FROM votes WHERE student_id = ?";
        $stmt = mysqli_prepare($conn, $remove_votes_query);
        mysqli_stmt_bind_param($stmt, "s", $student_id);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Error removing candidate votes: " . mysqli_error($conn));
        }

        // Now, remove the candidate from the selected_candidate table
        $remove_candidate_query = "DELETE FROM selected_candidate WHERE student_id = ?";
        $stmt = mysqli_prepare($conn, $remove_candidate_query);
        mysqli_stmt_bind_param($stmt, "s", $student_id);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Error removing candidate: " . mysqli_error($conn));
        }

        // Update candidate status to 'rejected' after removal
        $reset_query = "UPDATE candidates SET status = 'rejected' WHERE student_id = ?";
        $stmt = mysqli_prepare($conn, $reset_query);
        mysqli_stmt_bind_param($stmt, "s", $student_id);
        mysqli_stmt_execute($stmt);

        // Commit the transaction
        mysqli_commit($conn);
        echo "<script>alert('Candidate removed, status reset, and rejection email sent successfully!');</script>";
    } catch (Exception $e) {
        // If there's an error, rollback the transaction
        mysqli_rollback($conn);
        echo $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Remove Selected Candidates</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f4f4f4;
        }
        header {
            margin-bottom: 20px;
            text-align: center;
        }
        .selected-candidate {
            border: 1px solid #ccc;
            padding: 10px;
            margin: 10px 0;
            display: flex;
            align-items: center;
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
        }
        .selected-candidate img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            margin-right: 15px;
            border-radius: 50%;
        }
        .candidate-info {
            flex: 1;
        }
        .remove-button {
            color: #dc3545;
            text-decoration: none;
            font-weight: bold;
            margin-left: 15px;
            padding: 5px 10px;
            border: 1px solid #dc3545;
            border-radius: 4px;
            transition: all 0.3s;
        }
        .remove-button:hover {
            background-color: #dc3545;
            color: white;
        }
        .return-btn {
            margin-top: 20px;
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: background-color 0.3s, transform 0.3s;
        }
        .return-btn:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
        }
        .position-section {
            margin-bottom: 30px;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
        }
        .position-header {
            background-color: #f8f9fa;
            padding: 15px;
            border-bottom: 1px solid #ddd;
            font-size: 18px;
            font-weight: bold;
        }
        .position-candidates {
            padding: 15px;
        }
        .no-candidates {
            padding: 20px;
            text-align: center;
            font-style: italic;
            color: #6c757d;
        }
    </style>
</head>
<body>

<header>
    <h1>Remove Selected Candidates</h1>
</header>

<main>
    <?php
    // Fetch all positions
    $positions_query = "SELECT * FROM positions ORDER BY position_name";
    $positions_result = mysqli_query($conn, $positions_query);
    
    $has_candidates = false;
    
    while ($position = mysqli_fetch_assoc($positions_result)) {
        // Fetch selected candidates for this position
        $selected_query = "SELECT sc.*, p.position_name 
                          FROM selected_candidate sc
                          JOIN positions p ON sc.position_id = p.id
                          WHERE sc.position_id = ?";
        
        $stmt = mysqli_prepare($conn, $selected_query);
        mysqli_stmt_bind_param($stmt, "s", $position['id']);
        mysqli_stmt_execute($stmt);
        $selected_result = mysqli_stmt_get_result($stmt);
        $candidate_count = mysqli_num_rows($selected_result);
        
        if ($candidate_count > 0) {
            $has_candidates = true;
    ?>
        <section class="position-section">
            <div class="position-header">
                <?php echo htmlspecialchars($position['position_name']); ?> 
                <span style="font-size: 14px; color: #6c757d;">
                    (<?php echo $candidate_count; ?> approved candidate<?php echo $candidate_count != 1 ? 's' : ''; ?>)
                </span>
            </div>
            <div class="position-candidates">
                <?php while ($selected_row = mysqli_fetch_assoc($selected_result)) { ?>
                    <div class="selected-candidate">
                        <img src="uploads/<?php echo htmlspecialchars($selected_row['photo']); ?>" alt="Selected Candidate Photo">
                        <div class="candidate-info">
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($selected_row['name']); ?></p>
                            <p><strong>Department:</strong> <?php echo htmlspecialchars($selected_row['department']); ?></p>
                            <p><strong>Student ID:</strong> <?php echo htmlspecialchars($selected_row['student_id']); ?></p>
                        </div>
                        <a href="#" class="remove-button" onclick="confirmRejection('<?php echo htmlspecialchars($selected_row['student_id']); ?>')">Remove</a>
                    </div>
                <?php } ?>
            </div>
        </section>
    <?php
        }
    }
    
    if (!$has_candidates) {
        echo '<div class="no-candidates">No candidates have been approved for voting yet.</div>';
    }
    ?>
    
    <button class="return-btn" onclick="window.location.href='staff-dashboard.php'">Return to Staff Dashboard</button>
</main>

<script>
    function confirmRejection(candidateId) {
        if (confirm("Are you sure you want to remove and reject this candidate?")) {
            window.location.href = 'remove-candidate.php?remove_id=' + candidateId;
        }
    }
</script>

</body>
</html>