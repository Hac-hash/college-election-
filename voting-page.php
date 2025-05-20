<?php
session_start();
include('connect.php');

// Helper function to safely handle htmlspecialchars with potentially NULL values
function h($str) {
    return !is_null($str) ? htmlspecialchars($str, ENT_QUOTES, 'UTF-8') : '';
}

// Check if student is logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: student-login.php");
    exit();
}

// Check if position_id is set in the URL
if (!isset($_GET['position_id'])) {
    header("Location: select_position.php");
    exit();
}

$position_id = $_GET['position_id'];
$student_id = $_SESSION['student_id'];

// Get position name
$position_query = "SELECT position_name FROM positions WHERE id = ?";
$stmt = $conn->prepare($position_query);
$stmt->bind_param("i", $position_id);
$stmt->execute();
$position_result = $stmt->get_result();

if ($position_result->num_rows == 0) {
    // Position not found
    header("Location: select_position.php");
    exit();
}

$position_row = $position_result->fetch_assoc();
$position_name = $position_row['position_name'];

// Check if student has already voted for this position
$check_vote_query = "SELECT * FROM votes WHERE student_id = ? AND position_id = ?";
$stmt = $conn->prepare($check_vote_query);
$stmt->bind_param("si", $student_id, $position_id);
$stmt->execute();
$vote_result = $stmt->get_result();

$already_voted = $vote_result->num_rows > 0;

// Handle vote submission
if (isset($_POST['submit_vote']) && !$already_voted) {
    // Get the position_id and candidate_student_id
    $position_id = $_POST['position_id'];
    $candidate_student_id = $_POST['candidate_student_id'];

    // Begin transaction
    mysqli_begin_transaction($conn);

    try {
        // Insert vote with candidate_student_id to track which candidate was voted for
        $insert_vote_query = "INSERT INTO votes (student_id, position_id, candidate_student_id, vote_time) VALUES (?, ?, ?, NOW())";
        $stmt = $conn->prepare($insert_vote_query);
        $stmt->bind_param("sis", $student_id, $position_id, $candidate_student_id);
        $stmt->execute();

        // Commit transaction
        mysqli_commit($conn);

        // Success message
       // Success message and logout after voting
$_SESSION['message'] = "🎉 Your vote has been successfully recorded! Redirecting...";
echo "<script>
    alert('🎉 Your vote has been successfully recorded! Redirecting...');
    window.location.href = 'select_position.php';
</script>";
exit();

session_destroy();  // Destroy the session to log out
echo "<script>
    alert('✅ Your vote has been recorded! Logging out...');
    window.location.href = 'student-login.php';
</script>";
exit();

    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        $_SESSION['error'] = "Error recording your vote: " . $e->getMessage();
        header("Location: voting-page.php?position_id=" . $position_id);
        exit();
    }
}

// Get candidates for this position
$candidates_query = "SELECT * FROM selected_candidate WHERE position_id = ? AND status = 'approved' ORDER BY name";
$stmt = $conn->prepare($candidates_query);
$stmt->bind_param("i", $position_id);
$stmt->execute();
$candidates_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vote for <?php echo h($position_name); ?> - Ballot Box</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-hover: #1d4ed8;
            --secondary-color: #1e293b;
            --accent-color: #8b5cf6;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --text-color: #334155;
            --light-gray: #f1f5f9;
            --border-color: #e2e8f0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: var(--light-gray);
            color: var(--text-color);
            line-height: 1.7;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .container {
            max-width: 1000px;
            margin: 40px auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            position: relative;
        }

        .header h1 {
            font-size: 2.2rem;
            color: var(--secondary-color);
            margin-bottom: 10px;
            font-weight: 700;
        }

        .header p {
            color: #64748b;
            font-size: 1.1rem;
        }

        .candidates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
        }

        .candidate-card {
            background-color: #fff;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.03);
            transition: all 0.3s ease;
            position: relative;
        }

        .candidate-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.07);
        }

        .candidate-photo {
            width: 100%;
            height: 280px;
            object-fit: cover;
            border-bottom: 1px solid var(--border-color);
        }

        .candidate-details {
            padding: 24px;
        }

        .candidate-name {
            font-size: 1.5rem;
            margin-bottom: 12px;
            color: var(--secondary-color);
            font-weight: 600;
        }

        .candidate-info {
            margin-bottom: 18px;
        }

        .candidate-info p {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
            color: #475569;
        }

        .candidate-info i {
            width: 24px;
            margin-right: 8px;
            color: var(--primary-color);
        }

        .vote-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 14px 20px;
            width: 100%;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
        }

        .vote-btn:hover {
            background-color: var(--primary-hover);
        }

        .vote-btn:disabled {
            background-color: #cbd5e1;
            cursor: not-allowed;
        }
        
        .vote-btn i {
            font-size: 1rem;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            margin-top: 30px;
            background-color: var(--secondary-color);
            color: white;
            padding: 12px 18px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: background-color 0.2s;
            gap: 8px;
        }

        .back-button:hover {
            background-color: #334155;
        }

        .message {
            padding: 16px;
            margin-bottom: 24px;
            border-radius: 8px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .message i {
            font-size: 1.2rem;
        }

        .error {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        .success {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .disabled-message {
            background-color: #f8fafc;
            color: #64748b;
            padding: 16px;
            text-align: center;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 24px;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .candidate-count {
            position: absolute;
            top: 20px;
            right: 20px;
            background-color: rgba(30, 41, 59, 0.7);
            color: white;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .candidates-grid {
                grid-template-columns: 1fr;
            }
            
            .container {
                margin: 20px;
                padding: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Vote for <?php echo h($position_name); ?></h1>
            <p>Select a candidate and submit your vote</p>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="message error">
                <i class="fas fa-exclamation-circle"></i>
                <?php 
                    echo $_SESSION['error']; 
                    unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i>
                <?php 
                    echo $_SESSION['message']; 
                    unset($_SESSION['message']);
                ?>
            </div>
        <?php endif; ?>

        <?php if ($already_voted): ?>
            <div class="disabled-message">
                <i class="fas fa-info-circle"></i>
                <span>You have already voted for this position</span>
            </div>
        <?php endif; ?>

        <?php if ($candidates_result->num_rows > 0): ?>
            <div class="candidate-count">
                <?php echo $candidates_result->num_rows; ?> Candidate<?php echo $candidates_result->num_rows > 1 ? 's' : ''; ?>
            </div>
            
            <div class="candidates-grid">
                <?php while ($candidate = $candidates_result->fetch_assoc()): ?>
                    <div class="candidate-card">
                        <img src="uploads/<?php echo h($candidate['photo']); ?>" alt="<?php echo h($candidate['name']); ?>" class="candidate-photo">
                        <div class="candidate-details">
                            <h3 class="candidate-name"><?php echo h($candidate['name']); ?></h3>
                            
                            <div class="candidate-info">
                                <p><i class="fas fa-graduation-cap"></i> <strong>Department:</strong> <?php echo h($candidate['department']); ?></p>
                                <p><i class="fas fa-id-card"></i> <strong>Student ID:</strong> <?php echo !is_null($candidate['student_id']) ? h($candidate['student_id']) : 'Not provided'; ?></p>
                            </div>

                            <form method="POST" action="voting-page.php?position_id=<?php echo $position_id; ?>" onsubmit="return confirmVote();">
    <input type="hidden" name="position_id" value="<?php echo $position_id; ?>">
    <input type="hidden" name="candidate_student_id" value="<?php echo $candidate['student_id']; ?>">
    <button type="submit" name="submit_vote" class="vote-btn" <?php echo $already_voted ? 'disabled' : ''; ?>>
        <?php if ($already_voted): ?>
            <i class="fas fa-check-circle"></i> Vote Casted
        <?php else: ?>
            <i class="fas fa-vote-yea"></i> Vote for this Candidate
        <?php endif; ?>
    </button>
</form>

<script>
    function confirmVote() {
        return confirm("Are you sure you want to vote for this candidate?");
    }
</script>

                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="disabled-message">
                <i class="fas fa-exclamation-triangle"></i>
                <span>No approved candidates available for this position</span>
            </div>
        <?php endif; ?>

        <a href="select_position.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Positions
        </a>
    </div>
</body>

</html>