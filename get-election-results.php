<?php
session_start();

// Check if the user is authorized (e.g., staff)
if (!isset($_SESSION['staff_id'])) {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

include 'connect.php';

// Check if the database connection was successful
if (!$conn) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Initialize response array
$response = [
    'timestamp' => date('Y-m-d H:i:s'),
    'statistics' => [],
    'results' => []
];

// Fetch positions data
$positionsQuery = "SELECT id, position_name FROM positions ORDER BY position_name";
$positionsResult = $conn->query($positionsQuery);

if (!$positionsResult) {
    http_response_code(500);
    echo json_encode(['error' => 'Error fetching positions: ' . $conn->error]);
    exit;
}

$positions = [];
while ($row = $positionsResult->fetch_assoc()) {
    $positions[$row['id']] = $row['position_name'];
}

// If no positions exist, return an empty result
if (empty($positions)) {
    $response['statistics'] = [
        'activePositions' => 0,
        'eligibleVoters' => 0,
        'votedStudents' => 0,
        'participationRate' => 0
    ];
    $response['results'] = [];
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Fetch total votes per position
$totalVotesQuery = "SELECT position_id, COUNT(id) AS total FROM votes GROUP BY position_id";
$totalVotesResult = $conn->query($totalVotesQuery);

if (!$totalVotesResult) {
    http_response_code(500);
    echo json_encode(['error' => 'Error fetching total votes: ' . $conn->error]);
    exit;
}

$totalVotes = [];
while ($row = $totalVotesResult->fetch_assoc()) {
    $totalVotes[$row['position_id']] = $row['total'];
}

// Fetch election results
$results = [];
foreach ($positions as $positionId => $positionName) {
    // Validate positionId (ensure it's an integer)
    if (!is_numeric($positionId) || $positionId <= 0) {
        continue; // Skip invalid position IDs
    }

    $query = "SELECT sc.position_id, sc.student_id, sc.name, sc.photo, sc.department, 
              COUNT(v.id) as vote_count
              FROM selected_candidate sc
              LEFT JOIN votes v ON sc.student_id = v.candidate_student_id AND sc.position_id = v.position_id
              WHERE sc.position_id = ? AND sc.status = 'approved'
              GROUP BY sc.position_id, sc.student_id, sc.name, sc.photo, sc.department
              ORDER BY vote_count DESC";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['error' => 'Error preparing statement: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param("i", $positionId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $positionCandidates = [];
    while ($row = $result->fetch_assoc()) {
        $totalPositionVotes = isset($totalVotes[$row['position_id']]) ? $totalVotes[$row['position_id']] : 0;
        $row['percentage'] = ($totalPositionVotes > 0) ? round(($row['vote_count'] / $totalPositionVotes) * 100, 1) : 0;
        $positionCandidates[] = $row;
    }
    
    if (!empty($positionCandidates)) {
        $results[$positionName] = $positionCandidates;
    }

    $stmt->close();
}

// Voter statistics
$totalStudentsQuery = "SELECT COUNT(student_id) as total FROM voter_reg WHERE status = 'approved'";
$totalStudentsResult = $conn->query($totalStudentsQuery);
if (!$totalStudentsResult) {
    http_response_code(500);
    echo json_encode(['error' => 'Error fetching total students: ' . $conn->error]);
    exit;
}
$totalStudents = $totalStudentsResult->fetch_assoc()['total'] ?? 0;

$votedStudentsQuery = "SELECT COUNT(DISTINCT student_id) as total FROM votes";
$votedStudentsResult = $conn->query($votedStudentsQuery);
if (!$votedStudentsResult) {
    http_response_code(500);
    echo json_encode(['error' => 'Error fetching voted students: ' . $conn->error]);
    exit;
}
$votedStudents = $votedStudentsResult->fetch_assoc()['total'] ?? 0;

$participationRate = ($totalStudents > 0) ? round(($votedStudents / $totalStudents) * 100, 1) : 0;

// Prepare statistics
$statistics = [
    'activePositions' => count($results),
    'eligibleVoters' => $totalStudents,
    'votedStudents' => $votedStudents,
    'participationRate' => $participationRate
];

// Prepare response
$response['statistics'] = $statistics;
$response['results'] = $results;

// Output JSON
http_response_code(200); // OK
header('Content-Type: application/json');
echo json_encode($response);
exit;
?>