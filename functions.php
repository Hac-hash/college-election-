<?php
// Helper function to safely handle htmlspecialchars with potentially NULL values
function h($str) {
    return !is_null($str) ? htmlspecialchars($str, ENT_QUOTES, 'UTF-8') : '';
}

// Helper function to get and update election status
function updateElectionStatus($conn) {
    date_default_timezone_set('Asia/Kolkata'); // Set your time zone here
    $currentDate = new DateTime();
    $statusQuery = "SELECT * FROM election_timeline WHERE election_id = 1";
    $statusResult = mysqli_query($conn, $statusQuery);
    
    if ($statusResult && mysqli_num_rows($statusResult) > 0) {
        $election = mysqli_fetch_assoc($statusResult);
        $status = 'not_started';
        $previous_status = $election['previous_status'];

        $startDate = new DateTime($election['start_date']);
        $endDate = new DateTime($election['end_date']);

        // Debug logging
        error_log("Current Date: " . $currentDate->format('Y-m-d H:i:s'));
        error_log("Start Date: " . $startDate->format('Y-m-d H:i:s'));
        error_log("End Date: " . $endDate->format('Y-m-d H:i:s'));

        if ($currentDate < $startDate) {
            $status = 'not_started';
        } elseif ($currentDate >= $startDate && $currentDate <= $endDate) {
            $status = 'ongoing';
        } else {
            $status = 'ended';
        }

        if ($status !== $previous_status) {
            $updateQuery = "UPDATE election_timeline SET status = '$status', previous_status = '$status' WHERE election_id = 1";
            mysqli_query($conn, $updateQuery);
        }

        error_log("Election Status: $status");
        return $status;
    } else {
        error_log("No timeline found in election_timeline table");
        return 'not_started'; // Default status if no timeline is set
    }
}
?>