<?php
session_start();
include 'connect.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

$statusQuery = "SELECT status FROM election_status WHERE status = 'published' LIMIT 1";
$statusResult = $conn->query($statusQuery);
$isPublished = $statusResult && $statusResult->num_rows > 0;

if ($isPublished) {
    $response['message'] = 'Results are already published.';
} else {
    $updateQuery = "INSERT INTO election_status (status) VALUES ('published') ON DUPLICATE KEY UPDATE status = 'published'";
    if ($conn->query($updateQuery)) {
        $response['success'] = true;
    } else {
        $response['message'] = 'Failed to publish results: ' . $conn->error;
    }
}

echo json_encode($response);
$conn->close();
?>