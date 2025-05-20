<?php
include 'connect.php';

// Get the current time
$currentTime = date('Y-m-d H:i:s');

// Fetch election timeline
$electionQuery = "SELECT start_time, end_time FROM election_timeline LIMIT 1";
$electionResult = $conn->query($electionQuery);

$response = [
    'election_status' => 'not_started',
    'results_published' => false
];

// Check election timeline
if ($electionResult && $electionResult->num_rows > 0) {
    $election = $electionResult->fetch_assoc();
    $startTime = $election['start_time'];
    $endTime = $election['end_time'];

    if ($currentTime >= $startTime && $currentTime <= $endTime) {
        $response['election_status'] = 'ongoing';
    } elseif ($currentTime > $endTime) {
        $response['election_status'] = 'ended';
    }
}

// Check if results are published
$statusQuery = "SELECT status FROM election_status WHERE status = 'published' LIMIT 1";
$statusResult = $conn->query($statusQuery);
$response['results_published'] = $statusResult ? $statusResult->num_rows > 0 : false;

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit;
?>