<?php
// Start session if needed (optional here unless you use session variables)
session_start();

// Include necessary files
include('connect.php'); // Your database connection file
include('send_email.php'); // Your email sending function
include('functions.php'); // Contains updateElectionStatus() and h()

// Set timezone (already set in updateElectionStatus, but ensuring consistency)
date_default_timezone_set('Asia/Kolkata');

// Log file for debugging
$log_file = 'C:\wamp64\www\election\election_log.txt';

// Get current election status
$status = updateElectionStatus($conn);

// Fetch the election timeline
$query = "SELECT * FROM election_timeline WHERE election_id = 1";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) > 0) {
    $timeline = mysqli_fetch_assoc($result);
    $start_date = $timeline['start_date'];
    $end_date = $timeline['end_date'];
    $start_message_sent = $timeline['start_message_sent'] ?? 0; // Default to 0 if not set
    $end_message_sent = $timeline['end_message_sent'] ?? 0;     // Default to 0 if not set
    $current_datetime = date('Y-m-d H:i:s');

    // Fetch all voters' email addresses (adjust table/column names as per your DB)
    $voters_query = "SELECT email FROM voters";
    $voters_result = mysqli_query($conn, $voters_query);
    $voters = [];
    while ($row = mysqli_fetch_assoc($voters_result)) {
        $voters[] = $row['email'];
    }

    // Log current state
    file_put_contents($log_file, "Checked at: $current_datetime | Status: $status | Start Sent: $start_message_sent | End Sent: $end_message_sent\n", FILE_APPEND);

    // Send start message when election begins
    if ($status === 'ongoing' && $current_datetime >= $start_date && !$start_message_sent) {
        foreach ($voters as $voter_email) {
            $subject = "Election Has Started!";
            $message = "Dear Voter,\n\nThe election has officially started as of $start_date. Please cast your vote before it ends on $end_date.\n\nThank you!";
            sendEmail($voter_email, $subject, $message); // Assuming sendEmail() is in send_email.php
        }

        // Mark start message as sent
        $update_query = "UPDATE election_timeline SET start_message_sent = 1 WHERE election_id = 1";
        mysqli_query($conn, $update_query);
        file_put_contents($log_file, "Start message sent at: $current_datetime\n", FILE_APPEND);
    }

    // Send end message when election ends
    if ($status === 'ended' && $current_datetime >= $end_date && !$end_message_sent) {
        foreach ($voters as $voter_email) {
            $subject = "Election Has Ended!";
            $message = "Dear Voter,\n\nThe election has officially ended as of $end_date. Thank you for your participation!\n\nRegards,\nElection Team";
            sendEmail($voter_email, $subject, $message);
        }

        // Mark end message as sent
        $update_query = "UPDATE election_timeline SET end_message_sent = 1 WHERE election_id = 1";
        mysqli_query($conn, $update_query);
        file_put_contents($log_file, "End message sent at: $current_datetime\n", FILE_APPEND);
    }
} else {
    file_put_contents($log_file, "No election timeline found at: $current_datetime\n", FILE_APPEND);
}

// Close the database connection
mysqli_close($conn);
?>