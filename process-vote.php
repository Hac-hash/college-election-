<?php
session_start();
include 'c
// Ensure student is logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$position_id = $_GET['position_id'] ?? null;

// Redirect if position_id is not provided
if (!$position_id) {
    header("Location: select-position.php");
    exit();
}

// Check if student has already voted for this position
$checkVote = $conn->prepare("SELECT * FROM votes WHERE student_id = ? AND position_id = ?");
$checkVote->bind_param("ii", $student_id, $position_id);
$checkVote->execute();
$voteResult = $checkVote->get_result();

if ($voteResult->num_rows > 0) {
    echo "<script>alert('You have already voted for this position!'); window.location.href='select-position.php';</script>";
    exit();
}

// Fetch candidates for the selected position
$query = $conn->prepare("SELECT id, name, photo FROM candidates WHERE position_id = ?");
$query->bind_param("i", $position_id);
$query->execute();
candidates = $query->get_result();

// Fetch position name
$positionQuery = $conn->prepare("SELECT position_name FROM positions WHERE id = ?");
$positionQuery->bind_param("i", $position_id);
$positionQuery->execute();
$positionResult = $positionQuery->get_result()->fetch_assoc();
$positionName = $positionResult['position_name'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Vote for <?php echo htmlspecialchars($positionName); ?></title>
    <link rel="stylesheet" type="text/css" href="voting.css">
</head>
<body>
    <h2>Vote for <?php echo htmlspecialchars($positionName); ?></h2>
    <form method="POST" action="process-vote.php">
        <input type="hidden" name="position_id" value="<?php echo $position_id; ?>">
        <?php while ($row = $candidates->fetch_assoc()) { ?>
            <div class="candidate-card">
                <img src="uploads/<?php echo htmlspecialchars($row['photo']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
                <p><?php echo htmlspecialchars($row['name']); ?></p>
                <input type="radio" name="candidate_id" value="<?php echo $row['id']; ?>" required>
            </div>
        <?php } ?>
        <button type="submit">Submit Vote</button>
    </form>
    <a href="select-position.php">Back to Position Selection</a>
</body>
</html>
