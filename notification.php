<?php
session_start();
include('connect.php');
include('send_email.php');
include('functions.php');

// Check if staff is logged in
if (!isset($_SESSION['logged_in'])) {
    header("Location: staff-login.php");
    exit();
}

// Fetch election timeline
$currentDate = date('Y-m-d H:i:s');
$statusQuery = "SELECT * FROM election_timeline WHERE election_id = 1";
$statusResult = mysqli_query($conn, $statusQuery);
$election = mysqli_fetch_assoc($statusResult);

$status = 'not_started';
$previous_status = isset($election['previous_status']) ? $election['previous_status'] : null;

if ($election) {
    if ($currentDate < $election['start_date']) {
        $status = 'not_started';
    } elseif ($currentDate >= $election['start_date'] && $currentDate <= $election['end_date']) {
        $status = 'ongoing';
    } else {
        $status = 'ended';
    }

    // Only trigger notification if status has changed and hasn’t been notified yet
    if ($status !== $previous_status && $previous_status !== null) {
        $message = '';
        if ($status === 'not_started') {
            $message = "Election timeline has been reset.";
        } elseif ($status === 'ongoing') {
            $message = "Election has started on " . $election['start_date'] . "!";
        } elseif ($status === 'ended') {
            $message = "Election has ended on " . $election['end_date'] . "!";
        }

        if ($message) {
            // Check if this status change has already been notified
            $checkNotificationQuery = "SELECT id FROM notifications WHERE message = '" . mysqli_real_escape_string($conn, $message) . "' AND type = 'election_status'";
            $checkNotificationResult = mysqli_query($conn, $checkNotificationQuery);

            if (mysqli_num_rows($checkNotificationResult) === 0) {
                $insert_query = "INSERT INTO notifications (message, type, is_marquee) VALUES ('" . mysqli_real_escape_string($conn, $message) . "', 'election_status', 1)";
                if (mysqli_query($conn, $insert_query)) {
                    // Send emails to all eligible voters
                    $voters_query = "SELECT email FROM voter_reg WHERE status = 'approved'";
                    $voters_result = mysqli_query($conn, $voters_query);
                    $email_failed = false;
                    $email_count = 0;
                    $total_voters = mysqli_num_rows($voters_result);

                    set_time_limit(0); // Prevent timeout for large voter lists
                    while ($voter = mysqli_fetch_assoc($voters_result)) {
                        $emailResult = sendVoterEmail($voter['email'], 6, '', '', '', '', $message);
                        if (!$emailResult) {
                            $email_failed = true;
                            error_log("Failed to send email to {$voter['email']} for election status: $message");
                        } else {
                            $email_count++;
                        }
                        // Small delay to prevent overwhelming the mail server
                        usleep(50000); // 50ms delay
                    }

                    if ($email_failed) {
                        $_SESSION['error_message'] = "Election status notification saved, but some emails failed to send ($email_count/$total_voters sent).";
                    } else {
                        $_SESSION['success_message'] = "Election status notification sent successfully to $email_count/$total_voters voters!";
                    }

                    // Update previous_status only after successful notification
                    $updateQuery = "UPDATE election_timeline SET status = '$status', previous_status = '$status' WHERE election_id = 1";
                    if (!mysqli_query($conn, $updateQuery)) {
                        error_log("Failed to update election timeline: " . mysqli_error($conn));
                        $_SESSION['error_message'] .= " Failed to update election timeline: " . mysqli_error($conn);
                    }
                } else {
                    $_SESSION['error_message'] = "Failed to save election status notification: " . mysqli_error($conn);
                    error_log("Failed to insert notification: " . mysqli_error($conn));
                }
            }
        }
    } elseif ($previous_status === null && $status !== 'not_started') {
        // Initial setup: don’t overwrite previous_status until a transition occurs
        $updateQuery = "UPDATE election_timeline SET status = '$status' WHERE election_id = 1";
        mysqli_query($conn, $updateQuery);
    }
} else {
    $defaultStart = '1970-01-01 00:00:00';
    $defaultEnd = '1970-01-01 00:00:00';
    $insertQuery = "INSERT INTO election_timeline (election_id, start_date, end_date, status, previous_status) 
                    VALUES (1, '$defaultStart', '$defaultEnd', 'not_started', NULL)";
    if (!mysqli_query($conn, $insertQuery)) {
        error_log("Failed to insert default election timeline: " . mysqli_error($conn));
        $_SESSION['error_message'] = "Failed to insert default election timeline: " . mysqli_error($conn);
    }
    $statusResult = mysqli_query($conn, $statusQuery);
    $election = mysqli_fetch_assoc($statusResult);
    $status = 'not_started';
}

// Handle manual notification
if (isset($_POST['send_notification'])) {
    $message = mysqli_real_escape_string($conn, $_POST['message']);
    $insert_query = "INSERT INTO notifications (message, type, is_marquee) VALUES ('$message', 'manual', 1)";
    
    if (mysqli_query($conn, $insert_query)) {
        $voters_query = "SELECT email FROM voter_reg WHERE status = 'approved'";
        $voters_result = mysqli_query($conn, $voters_query);
        $email_failed = false;
        $email_count = 0;
        $total_voters = mysqli_num_rows($voters_result);

        set_time_limit(0);
        while ($voter = mysqli_fetch_assoc($voters_result)) {
            if (!sendVoterEmail($voter['email'], 6, '', '', '', '', $message)) {
                $email_failed = true;
                error_log("Failed to send email to {$voter['email']} for manual notification: $message");
            } else {
                $email_count++;
            }
            usleep(50000); // 50ms delay
        }

        if ($email_failed) {
            $_SESSION['error_message'] = "Notification saved and set as marquee, but some emails failed to send ($email_count/$total_voters sent).";
        } else {
            $_SESSION['success_message'] = "Notification sent successfully to $email_count/$total_voters voters and set as marquee!";
        }
    } else {
        $_SESSION['error_message'] = "Failed to save notification: " . mysqli_error($conn);
        error_log("Failed to save notification: " . mysqli_error($conn));
    }

    header("Location: notification.php");
    exit();
}

// Handle deletion of a specific notification
if (isset($_POST['delete_notification'])) {
    $notification_id = mysqli_real_escape_string($conn, $_POST['notification_id']);
    $delete_query = "DELETE FROM notifications WHERE id = '$notification_id'";
    if (mysqli_query($conn, $delete_query)) {
        $_SESSION['success_message'] = "Notification deleted successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to delete notification: " . mysqli_error($conn);
    }

    header("Location: notification.php");
    exit();
}

// Fetch all notifications for display
$notifications_query = "SELECT * FROM notifications ORDER BY created_at DESC";
$notifications_result = mysqli_query($conn, $notifications_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Notification</title>
    <link rel="stylesheet" href="login.css">
    <style>
        body { display: flex; justify-content: center; align-items: center; min-height: 100vh; background-color: #f4f4f4; position: relative; padding: 20px; }
        .notification-container { background-color: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); width: 100%; max-width: 800px; }
        .notification-container h1 { margin-bottom: 20px; color: #333; }
        .notification-container textarea { width: 100%; height: 150px; padding: 15px; margin-bottom: 20px; border: 1px solid #ccc; border-radius: 5px; }
        .notification-container button { padding: 10px 20px; color: white; border: none; border-radius: 5px; cursor: pointer; margin-bottom: 10px; }
        .notification-container button:hover { opacity: 0.9; }
        .send-btn { background-color: #28a745; }
        .delete-btn { background-color: #dc3545; }
        .send-btn:hover { background-color: #218838; }
        .delete-btn:hover { background-color: #c82333; }
        .return-btn { padding: 10px 20px; background-color: #007bff; color: white; border: none; border-radius: 5px; position: absolute; top: 20px; left: 20px; cursor: pointer; }
        .return-btn:hover { background-color: #0069d9; }
        .notifications-list { margin-top: 30px; }
        .notifications-list h2 { font-size: 1.5em; color: #333; margin-bottom: 15px; }
        .notification-item { background: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; }
        .notification-item p { margin: 0; color: #555; }
        .notification-item form { margin: 0; }
        .success-message { color: #28a745; margin-top: 10px; text-align: center; }
        .error-message { color: #dc3545; margin-top: 10px; text-align: center; }
        .election-status { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); margin-bottom: 20px; text-align: center; }
        .election-status h2 { font-size: 1.5em; color: #333; margin-bottom: 10px; }
        .election-status p { font-size: 1em; color: #555; margin: 5px 0; }
        .status-ongoing { color: #27ae60; font-weight: bold; }
        .status-ended { color: #e74c3c; font-weight: bold; }
        .status-not-started { color: #f39c12; font-weight: bold; }
        .marquee-indicator { color: #27ae60; font-weight: bold; margin-left: 10px; }
    </style>
</head>
<body>
    <a href="staff-dashboard.php"><button class="return-btn">Return to Dashboard</button></a>

    <div class="notification-container">
        <!-- Election Status Section -->
        <section class="election-status">
            <h2>Election Status</h2>
            <p>Current Status: <span class="status-<?php echo $status; ?>"><?php echo ucfirst($status); ?></span></p>
            <?php if ($election && $election['start_date'] != '1970-01-01 00:00:00'): ?>
                <p>Start: <?php echo $election['start_date']; ?></p>
                <p>End: <?php echo $election['end_date']; ?></p>
            <?php else: ?>
                <p>Timeline not set.</p>
            <?php endif; ?>
        </section>

        <h1>Send Notification</h1>
        <form method="POST">
            <textarea name="message" placeholder="Enter your message here..." required></textarea>
            <p>Note: All notifications will be displayed as marquee and emailed to voters</p>
            <button type="submit" name="send_notification" class="send-btn">Send</button>
        </form>

        <!-- Display all notifications -->
        <div class="notifications-list">
            <h2>Notifications</h2>
            <?php if (mysqli_num_rows($notifications_result) > 0): ?>
                <?php while ($notification = mysqli_fetch_assoc($notifications_result)): ?>
                    <div class="notification-item">
                        <p>
                            [<?php echo $notification['created_at']; ?>] 
                            <?php echo htmlspecialchars($notification['message']); ?>
                            <?php if ($notification['is_marquee']): ?>
                                <span class="marquee-indicator">[Marquee]</span>
                            <?php endif; ?>
                        </p>
                        <form method="POST">
                            <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                            <button type="submit" name="delete_notification" class="delete-btn">Delete</button>
                        </form>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No notifications available.</p>
            <?php endif; ?>
        </div>

        <!-- Success and Error Messages -->
        <?php
        if (isset($_SESSION['success_message'])) {
            echo "<p class='success-message'>" . $_SESSION['success_message'] . "</p>";
            unset($_SESSION['success_message']);
        }
        if (isset($_SESSION['error_message'])) {
            echo "<p class='error-message'>" . $_SESSION['error_message'] . "</p>";
            unset($_SESSION['error_message']);
        }
        ?>
    </div>
</body>
</html>