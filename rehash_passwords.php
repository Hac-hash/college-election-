<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "ballot_box_copy");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all tutors
$sql = "SELECT tutor_id, password FROM tutor_login";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    $tutor_id = $row['tutor_id'];
    $password = $row['password'];

    // Check if the password is not hashed
    if (!preg_match('/^\$2y\$/', $password)) {
        // Hash plain text password
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // Update password in the database
        $update_sql = "UPDATE tutor_login SET password = '$hashed_password' WHERE tutor_id = $tutor_id";
        $conn->query($update_sql);
        echo "Password updated for tutor_id: $tutor_id<br>";
    }
}

echo "All plain text passwords are now hashed!";
$conn->close();
?>
