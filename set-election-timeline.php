<?php
session_start();
include('connect.php');
include('send_email.php');
include('functions.php'); // Include the centralized functions

if (!isset($_SESSION['logged_in'])) {
    header("Location: staff-login.php");
    exit();
}

if (isset($_POST['set_timeline'])) {
    $start_date = mysqli_real_escape_string($conn, $_POST['start_date']);
    $end_date = mysqli_real_escape_string($conn, $_POST['end_date']);

    // Validate that end_date is after start_date
    if (strtotime($end_date) <= strtotime($start_date)) {
        $_SESSION['error_message'] = "End date must be after start date!";
        header("Location: set_timeline.php");
        exit();
    }

    // Update or insert the timeline
    $checkQuery = "SELECT * FROM election_timeline WHERE election_id = 1";
    $checkResult = mysqli_query($conn, $checkQuery);
    if (mysqli_num_rows($checkResult) > 0) {
        $updateQuery = "UPDATE election_timeline SET start_date = '$start_date', end_date = '$end_date' WHERE election_id = 1";
        mysqli_query($conn, $updateQuery);
    } else {
        $insertQuery = "INSERT INTO election_timeline (election_id, start_date, end_date, status, previous_status) 
                        VALUES (1, '$start_date', '$end_date', 'not_started', 'not_started')";
        mysqli_query($conn, $insertQuery);
    }

    // Update the election status after setting the timeline
    updateElectionStatus($conn);

    $_SESSION['success_message'] = "Election timeline set successfully!";
    header("Location: staff-dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Election Timeline</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f4f4f4;
        }
        .form-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            width: 400px;
        }
        .form-container h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        .form-container label {
            display: block;
            margin-bottom: 5px;
        }
        .form-container input {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .form-container button {
            width: 100%;
            padding: 10px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .form-container button:hover {
            background-color: #218838;
        }
        .error-message {
            color: red;
            text-align: center;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Set Election Timeline</h2>
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="error-message"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
        <?php endif; ?>
        <form method="POST">
            <label for="start_date">Start Date & Time:</label>
            <input type="datetime-local" id="start_date" name="start_date" required>

            <label for="end_date">End Date & Time:</label>
            <input type="datetime-local" id="end_date" name="end_date" required>

            <button type="submit" name="set_timeline">Set Timeline</button>
        </form>
    </div>
</body>
</html>