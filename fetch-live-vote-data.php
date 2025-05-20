<?php
include('connect.php');

// Query to get the vote count for each candidate along with the department
$query = "SELECT selected_candidate.name, selected_candidate.photo, selected_candidate.department, COUNT(votes.student_id) as vote_count 
          FROM selected_candidate 
          LEFT JOIN votes ON selected_candidate.student_id = votes.student_id 
          GROUP BY selected_candidate.student_id";
$result = mysqli_query($conn, $query);

// Output the live vote data as HTML
if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "
        <div class='candidate-vote'>
            <div>
                <img src='uploads/" . htmlspecialchars($row['photo']) . "' alt='Candidate Photo' style='width: 50px; height: 50px; border-radius: 50%;'>
                <strong>" . htmlspecialchars($row['name']) . "</strong><br>
                <small>Department: " . htmlspecialchars($row['department']) . "</small>
            </div>
            <div>
                Votes: " . htmlspecialchars($row['vote_count']) . "
            </div>
        </div>
        ";
    }
} else {
    echo "<p>No votes have been cast yet.</p>";
}
?>
