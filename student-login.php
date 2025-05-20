<?php
session_start();
include('connect.php');
include('functions.php'); // Include the centralized functions

// Check election status
$electionStatus = updateElectionStatus($conn);

// Fetch election timeline for displaying start/end dates in the message
$timelineQuery = "SELECT start_date, end_date FROM election_timeline WHERE election_id = 1";
$timelineResult = mysqli_query($conn, $timelineQuery);
if (!$timelineResult) {
    die("Error fetching election timeline: " . mysqli_error($conn));
}
$timeline = mysqli_fetch_assoc($timelineResult);

// Debug logging
error_log("Election Status in student-login.php: $electionStatus");
if ($timelineResult && mysqli_num_rows($timelineResult) > 0) {
    error_log("Start Date: " . $timeline['start_date']);
    error_log("End Date: " . $timeline['end_date']);
} else {
    error_log("No timeline found in election_timeline table");
}

// Handle login only if election is ongoing
if ($_SERVER["REQUEST_METHOD"] == "POST" && $electionStatus === 'ongoing') {
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // Query to get student details
    $result = mysqli_query($conn, "SELECT * FROM voter_reg WHERE student_id = '$student_id'");

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);

        // Verify password (check BCRYPT hash)
        if (password_verify($password, $row['password'])) {
            // Check if voter is approved
            if ($row['status'] == 'approved') {
                // Set session variables
                $_SESSION['student_id'] = $row['student_id'];
                $_SESSION['student_name'] = $row['name'];

                // Redirect to voting page
                header("Location: select_position.php");
                exit();
            } else {
                $_SESSION['error'] = "Your account is not approved yet!";
                header("Location: student-login.php");
                exit();
            }
        } else {
            $_SESSION['error'] = "Invalid password!";
            header("Location: student-login.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "Invalid Student ID!";
        header("Location: student-login.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login - Ballot Box</title>
    <link rel="stylesheet" href="reg.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* General Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            color: #333;
        }

        /* Header Section */
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #333;
            padding: 10px 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .logo img {
            width: 50px;
            height: 50px;
            margin-right: 10px;
            vertical-align: middle;
        }

        .logo h1 {
            display: inline;
            font-size: 24px;
            color: white;
            vertical-align: middle;
        }

        nav ul {
            list-style: none;
            display: flex;
        }

        nav ul li {
            margin-right: 20px;
        }

        nav ul li a {
            color: white;
            text-decoration: none;
            font-size: 16px;
            padding: 10px;
            transition: background-color 0.3s ease;
        }

        nav ul li a:hover {
            background-color: #555;
            border-radius: 4px;
        }

        /* Dropdown Styles */
        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #f9f9f9;
            min-width: 160px;
            box-shadow: 0px 8px 16px rgba(0, 0, 0, 0.2);
            z-index: 1;
        }

        .dropdown-content a {
            color: #333;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
        }

        .dropdown-content a:hover {
            background-color: #f1f1f1;
        }

        .dropdown:hover .dropdown-content {
            display: block;
        }

        .dropdown:hover .nav-link {
            background-color: #555;
        }

        /* Centering the Form */
        main {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 80vh;
            padding: 20px;
            background-color: #f4f4f9;
        }

        section {
            background-color: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        h2 {
            color: #333;
            margin-bottom: 20px;
        }

        /* Form Styling */
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        label {
            font-size: 16px;
            color: #333;
        }

        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-top: 8px;
        }

        button[type="submit"] {
            width: 100%;
            padding: 12px;
            font-size: 18px;
            background-color: #333;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        button[type="submit"]:hover {
            background-color: #555;
        }

        button:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        /* Improved Disable Message Styling */
        .disable-message {
            background-color: #fef9e7; /* Soft yellow background */
            border: 1px solid #fde68a;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
            color: #d97706; /* Dark yellow text */
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            text-align: left;
        }

        .disable-message i {
            font-size: 20px;
            color: #f59e0b; /* Yellow icon */
        }

        .disable-message strong {
            color: #b45309; /* Slightly darker yellow for emphasis */
        }

        .disable-message a {
            color: #2563eb; /* Blue link color */
            text-decoration: none;
        }

        .disable-message a:hover {
            text-decoration: underline;
        }

        .message {
            color: #d9534f;
            margin-bottom: 20px;
        }

        /* Media Queries for Responsiveness */
        @media (max-width: 768px) {
            header {
                flex-direction: column;
                align-items: flex-start;
            }

            nav ul {
                flex-direction: column;
                align-items: flex-start;
            }

            main {
                padding: 10px;
            }

            section {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="blur-background"></div>
    <!-- Header Section -->
    <header>
        <div class="logo">
            <img src="image/logo.PNG" alt="Ballot Box Logo">
            <h1>Ballot Box</h1>
        </div>
        <nav>
            <ul>
                <li><a href="index.php" class="nav-link">Home</a></li>
                <li><a href="index.php" class="nav-link">About Us</a></li>
                <li>
                    <?php if ($electionStatus === 'ongoing'): ?>
                        <a href="student-login.php" class="nav-link">Voter Login</a>
                    <?php else: ?>
                        <span class="nav-link" style="color: #ccc; cursor: not-allowed;">Voter Login (Disabled)</span>
                    <?php endif; ?>
                </li>
                <!-- <li class="dropdown">
                    <a href="#" class="nav-link">Login</a>
                    <div class="dropdown-content">
                        <a href="staff-login.php">Admin</a>
                        <a href="tutor-login.php">Tutor</a>
                    </div>
                </li>
                <li class="dropdown">
                    <a href="#" class="nav-link">Register</a>
                    <div class="dropdown-content">
                        <a href="register.php">Candidate</a>
                        <a href="voter_register.php">Voter</a>
                    </div> -->
                </li>
                <li class="dropdown">
                    <a href=" #" class="nav-link">Result</a>
                    <div class="dropdown-content">
                    <a href="check-status.php">Check Voter Status</a>
                        <a href="grievance.php">Grievance/Issue</a>
                        <a href="display-candidate.php">Meet the Candidate</a>
                        <a href="winners-poster.php">Winners</a>
                      
                    </div>
                </li>
            </ul>
        </nav>
    </header>
    <br>
    
    <!-- Main Content -->
    <main>
        <section>
            <h2>Voter Login</h2>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="message"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <?php if ($electionStatus !== 'ongoing'): ?>
                <div class="disable-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <div>
                        <?php
                        if ($electionStatus === 'not_started') {
                            $startDate = isset($timeline['start_date']) ? date('F j, Y, g:i A', strtotime($timeline['start_date'])) : 'TBD';
                            echo "The election hasn’t started yet! Voting will begin on <strong>$startDate</strong>. Please check back then to cast your vote.";
                        } elseif ($electionStatus === 'ended') {
                            echo "The election has concluded. Voting is now closed. You can view the results <a href='winners-poster.php'>here</a> or contact the administrator for more information.";
                        }
                        ?>
                    </div>
                </div>
                <form action="" method="post">
                    <div class="form-group">
                        <label for="student_id">Student ID:</label>
                        <input type="text" id="student_id" name="student_id" disabled>
                    </div>
                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" disabled>
                    </div>
                    <p style="text-align: right;">
                        <a href="forgot_password.php" style="color: #333; text-decoration: none;">Forgot Password?</a>
                    </p>
                    <button type="submit" name="submit" disabled>Login</button>
                </form>
            <?php else: ?>
                <form action="" method="post">
                    <div class="form-group">
                        <label for="student_id">Student ID:</label>
                        <input type="text" id="student_id" name="student_id" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <p style="text-align: right;">
                        <a href="forgot_password.php" style="color: #333; text-decoration: none;">Forgot Password?</a>
                    </p>
                    <button type="submit" name="submit">Login</button>
                </form>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>