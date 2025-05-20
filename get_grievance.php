<?php
session_start();
include('connect.php');

if (!isset($_SESSION['logged_in'])) {
    header("Location: staff-login.php");
    exit();
}

if (isset($_GET['id'])) {
    $issue_id = $_GET['id'];
    
    $query = "SELECT * FROM issues WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $issue_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'grievance' => $row]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Issue not found']);
    }
    
    $stmt->close();
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing issue ID']);
}
$conn->close();
?>