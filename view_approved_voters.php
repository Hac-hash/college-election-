<?php
include('connect.php'); // Database connection

// Get tutor ID from URL
if (!isset($_GET['tutor_id'])) {
    echo "<script>alert('Invalid tutor selection.'); window.location.href='voter_management.php';</script>";
    exit();
}

$tutor_id = $_GET['tutor_id'];

// Fetch tutor details
$tutor_query = "SELECT name, department, batch FROM tutor_login WHERE tutor_id = ?";
$stmt = $conn->prepare($tutor_query);
$stmt->bind_param("i", $tutor_id);
$stmt->execute();
$tutor_result = $stmt->get_result();
$tutor = $tutor_result->fetch_assoc();

if (!$tutor) {
    echo "<script>alert('Tutor not found!'); window.location.href='voter_management.php';</script>";
    exit();
}

// Fetch approved voters from the tutor's department & batch
$voter_query = "SELECT student_id, name, email FROM voter_reg WHERE department = ? AND batch = ? AND status = 'approved'";
$stmt = $conn->prepare($voter_query);
$stmt->bind_param("ss", $tutor['department'], $tutor['batch']);
$stmt->execute();
$voter_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approved Voters</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; }
        .container { width: 50%; margin: auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0px 0px 10px gray; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid black; }
        th { background-color: green; color: white; }
        .back-btn { padding: 10px 15px; background: blue; color: white; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin-top: 20px; }
    </style>
</head>
<body>

<div class="container">
    <h2>Approved Voters - <?php echo $tutor['name']; ?> (<?php echo $tutor['department'] . ' - ' . $tutor['batch']; ?>)</h2>

    <table>
        <tr>
            <th>Student ID</th>
            <th>Name</th>
            <th>Email</th>
        </tr>
        <?php while ($row = $voter_result->fetch_assoc()) { ?>
            <tr>
                <td><?php echo $row['student_id']; ?></td>
                <td><?php echo $row['name']; ?></td>
                <td><?php echo $row['email']; ?></td>
            </tr>
        <?php } ?>
    </table>

    <a href="voter_management.php" class="back-btn">Back to Tutors</a>
</div>

</body>
</html>
