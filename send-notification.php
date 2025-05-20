<?php
// send_notifications.php
include('connect.php');
include('send_email.php');

// Set the timezone (replace 'Asia/Kolkata' with your timezone)
date_default_timezone_set('Asia/Kolkata');

function checkAndSendNotifications($conn) {
    $currentTime = date('Y-m-d H:i:s');
    $timelineQuery = "SELECT start_date, end_date, start_notification_sent, end_notification_sent FROM election_timeline WHERE election_id = 1";
    $result = mysqli_query($conn, $timelineQuery);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $start_date = $row['start_date'];
        $end_date = $row['end_date'];
        $start_notification_sent = $row['start_notification_sent'];
        $end_notification_sent = $row['end_notification_sent'];

        // Check if election has just started and notification hasn't been sent
        if ($currentTime >= $start_date && $currentTime < date('Y-m-d H:i:s', strtotime($start_date . ' +1 minute')) && !$start_notification_sent) {
            $voterQuery = "SELECT email FROM voter_reg";
            $voterResult = mysqli_query($conn, $voterQuery);
            
            if ($voterResult && mysqli_num_rows($voterResult) > 0) {
                while ($voter = mysqli_fetch_assoc($voterResult)) {
                    $to = $voter['email'];
                    $subject = "Election Has Started!";
                    $message = "Dear Voter,\n\nThe election has officially started as of $start_date. Please cast your vote before it ends on $end_date.\n\nBest regards,\nElection Team";
                    sendEmail($to, $subject, $message);
                }
            } else {
                error_log("Failed to fetch voters or no voters found: " . mysqli_error($conn));
            }
            mysqli_query($conn, "UPDATE election_timeline SET start_notification_sent = 1 WHERE election_id = 1");
        }

        // Check if election has just ended and notification hasn't been sent
        if ($currentTime >= $end_date && $currentTime < date('Y-m-d H:i:s', strtotime($end_date . ' +1 minute')) && !$end_notification_sent) {
            $voterQuery = "SELECT email FROM voter_reg";
            $voterResult = mysqli_query($conn, $voterQuery);
            
            if ($voterResult && mysqli_num_rows($voterResult) > 0) {
                while ($voter = mysqli_fetch_assoc($voterResult)) {
                    $to = $voter['email'];
                    $subject = "Election Has Ended!";
                    $message = "Dear Voter,\n\nThe election has officially ended as of $end_date. Thank you for your participation!\n\nBest regards,\nElection Team";
                    sendEmail($to, $subject, $message);
                }
            } else {
                error_log("Failed to fetch voters or no voters found: " . mysqli_error($conn));
            }
            mysqli_query($conn, "UPDATE election_timeline SET end_notification_sent = 1 WHERE election_id = 1");
        }
    } else {
        error_log("Failed to fetch election timeline: " . mysqli_error($conn));
    }
}

// Run the function
checkAndSendNotifications($conn);
?>