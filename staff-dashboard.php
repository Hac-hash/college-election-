<?php
session_start();
include('connect.php');

// Check if staff is logged in
if (!isset($_SESSION['logged_in'])) {
    header("Location: staff-login.php");
    exit();
}

// Logout functionality
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// Function to update election status based on timeline
function updateElectionStatus($conn) {
    $currentTime = date('Y-m-d H:i:s');
    $timelineQuery = "SELECT start_date, end_date FROM election_timeline WHERE election_id = 1";
    $result = mysqli_query($conn, $timelineQuery);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $start_date = $row['start_date'];
        $end_date = $row['end_date'];
        
        if ($currentTime < $start_date) {
            $status = 'not_started';
        } elseif ($currentTime >= $start_date && $currentTime <= $end_date) {
            $status = 'ongoing';
        } else {
            $status = 'ended';
        }
        
        $updateQuery = "UPDATE election_timeline SET status = '$status' WHERE election_id = 1";
        mysqli_query($conn, $updateQuery);
    }
}

// Handle timeline setting
if (isset($_POST['set_timeline'])) {
    $start_date = mysqli_real_escape_string($conn, $_POST['start_date']);
    $end_date = mysqli_real_escape_string($conn, $_POST['end_date']);

    // Validate that end_date is after start_date
    if (strtotime($end_date) <= strtotime($start_date)) {
        $_SESSION['error_message'] = "End date must be after start date!";
    } else {
        // Update or insert the timeline
        $checkQuery = "SELECT * FROM election_timeline WHERE election_id = 1";
        $checkResult = mysqli_query($conn, $checkQuery);
        if (mysqli_num_rows($checkResult) > 0) {
            $updateQuery = "UPDATE election_timeline SET start_date = '$start_date', end_date = '$end_date', status = 'not_started' WHERE election_id = 1";
            mysqli_query($conn, $updateQuery);
        } else {
            $insertQuery = "INSERT INTO election_timeline (election_id, start_date, end_date, status) 
                            VALUES (1, '$start_date', '$end_date', 'not_started')";
            mysqli_query($conn, $insertQuery);
        }

        updateElectionStatus($conn);
        $_SESSION['success_message'] = "Election timeline set successfully!";
    }
}

// Fetch current timeline for display
$timelineQuery = "SELECT start_date, end_date, status FROM election_timeline WHERE election_id = 1";
$timelineResult = mysqli_query($conn, $timelineQuery);
$timeline = mysqli_fetch_assoc($timelineResult);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard</title>
    <link rel="stylesheet" href="login.css">
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Arial', sans-serif;
    }
    body {
        display: flex;
        background-color: #e9ecef;
        min-height: 100vh;
    }
    .sidebar {
        width: 250px;
        background-color: #1f2833;
        height: 100vh;
        position: fixed;
        padding-top: 30px;
        box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    .sidebar h1 {
        color: #66fcf1;
        text-align: center;
        margin-bottom: 30px;
        font-size: 1.8em;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 2px;
    }
    .sidebar a {
        padding: 15px 25px;
        text-decoration: none;
        color: #ffffff;
        display: block;
        font-size: 18px;
        font-weight: 500;
        margin-bottom: 15px;
        transition: all 0.3s ease;
        width: 100%;
        text-align: center;
    }
    .sidebar a:hover {
        background-color: #45a29e;
        padding-left: 35px;
        transition: 0.3s;
    }
    .sidebar .logout-btn {
        padding: 15px 25px;
        text-decoration: none;
        background-color: #dc3545;
        color: white;
        text-align: center;
        font-weight: 600;
        display: block;
        margin-top: auto;
        width: 100%;
        border: none;
        border-radius: 5px;
        transition: background-color 0.3s;
    }
    .sidebar .logout-btn:hover {
        background-color: #c82333;
    }
    .content {
        margin-left: 250px;
        padding: 50px;
        width: calc(100% - 250px);
        background-color: #f0f0f0;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    .content h1 {
        color: #0b0c10;
        font-size: 2.5em;
        margin-bottom: 20px;
        font-weight: 700;
    }
    .content p {
        text-align: center;
    }
    .timeline-section {
        background-color: #fff;
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        max-width: 500px;
        width: 100%;
        margin-top: 20px;
    }
    .timeline-section h2 {
        color: #333;
        font-size: 1.5em;
        margin-bottom: 15px;
        text-align: center;
    }
    .timeline-section .current-timeline {
        margin-bottom: 20px;
        padding: 15px;
        background-color: #f9f9f9;
        border-radius: 5px;
    }
    .timeline-section .current-timeline p {
        margin: 5px 0;
        color: #555;
        text-align: center;
    }
    .timeline-section form {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    .timeline-section label {
        font-weight: 500;
        color: #333;
    }
    .timeline-section input {
        padding: 8px;
        border: 1px solid #ccc;
        border-radius: 4px;
        font-size: 1em;
    }
    .timeline-section button {
        padding: 10px;
        background-color: #28a745;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 600;
        transition: background-color 0.3s;
    }
    .timeline-section button:hover {
        background-color: #218838;
    }
    .success-message {
        color: #28a745;
        margin-top: 10px;
        text-align: center;
    }
    .error-message {
        color: #dc3545;
        margin-top: 10px;
        text-align: center;
    }
    </style>
</head>
<body>
    <div class="sidebar">
        <h1>Admin Dashboard</h1>
        <a href="select-tutor.php">Select Tutor</a>
        <a href="select-candidate.php">Select Candidate</a>
        <a href="remove-candidate.php">Remove Candidate</a>
        <a href="voter_management.php">Voter Management</a>
        <a href="notification.php">Send Notification</a>
        <a href="voting-results.php">Results</a>
        <a href="admin-grievances.php">
    <i class="fas fa-ticket-alt"></i>Manage Grievances
    
</a>
        <form method="POST">
            <button type="submit" name="logout" class="logout-btn">Logout</button>
        </form>
    </div>

    <div class="content">
        <h1>Welcome to Admin Dashboard</h1>
       

        <div class="timeline-section">
            <h2>Election Timeline</h2>
            <?php if ($timeline && $timeline['start_date'] != '1970-01-01 00:00:00'): ?>
                <div class="current-timeline">
                    <p><strong>Current Start Date:</strong> <?php echo $timeline['start_date']; ?></p>
                    <p><strong>Current End Date:</strong> <?php echo $timeline['end_date']; ?></p>
                    <p><strong>Status:</strong> <?php echo ucfirst($timeline['status']); ?></p>
                </div>
            <?php else: ?>
                <div class="current-timeline">
                    <p>No timeline set yet.</p>
                </div>
            <?php endif; ?>

            <form method="POST">
                <label for="start_date">Start Date & Time:</label>
                <input type="datetime-local" id="start_date" name="start_date" required>
                <label for="end_date">End Date & Time:</label>
                <input type="datetime-local" id="end_date" name="end_date" required>
                <button type="submit" name="set_timeline">Set Timeline</button>
            </form>

            <?php if (isset($_SESSION['success_message'])): ?>
                <p class="success-message"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></p>
            <?php endif; ?>
            <?php if (isset($_SESSION['error_message'])): ?>
                <p class="error-message"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>