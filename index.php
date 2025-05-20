<?php
include('connect.php');

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Fetch all notifications for dropdown
$notifications_query = "SELECT message, created_at FROM notifications ORDER BY created_at DESC";
$notifications_result = mysqli_query($conn, $notifications_query);

if (!$notifications_result) {
    die("Query failed: " . mysqli_error($conn));
}

// Fetch marquee notifications only
$marquee_query = "SELECT message FROM notifications WHERE is_marquee = 1 ORDER BY created_at DESC";
$marquee_result = mysqli_query($conn, $marquee_query);

if (!$marquee_result) {
    die("Query failed: " . mysqli_error($conn));
}

$marquee_text = '';
if (mysqli_num_rows($marquee_result) > 0) {
    $marquee_notifications = [];
    while ($marquee = mysqli_fetch_assoc($marquee_result)) {
        $marquee_notifications[] = htmlspecialchars($marquee['message']);
    }
    $marquee_text = implode(' | ', $marquee_notifications);
} else {
    $marquee_text = 'No marquee notifications available.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ballot Box - Student Voting System</title>
    <link rel="stylesheet" href="reg.css">
    <link rel="stylesheet" href="button.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300..700&display=swap');
        body {
            font-family: "Space Grotesk", sans-serif;
        }
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
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
        }
        .dropdown-content a:hover {
            background-color: #f1f1f1;
        }
        .dropdown-content.notifications {
            min-width: 300px;
            max-height: 300px;
            overflow-y: auto;
            right: 0;
        }
        .dropdown-content .notification-item {
            padding: 10px 15px;
            color: #555;
            font-size: 0.9em;
            border-bottom: 1px solid #eee;
        }
        .dropdown-content .notification-item:last-child {
            border-bottom: none;
        }
        .dropdown-content .notification-item:hover {
            background-color: #f1f1f1;
        }
        .dropdown:hover .dropdown-content {
            display: block;
        }
        .dropdown:hover .nav-link {
            background-color: #555;
        }
        .marquee-container {
            background: #fff;
            padding: 2px;
            margin: 10px 0;
            text-align: center;
            overflow: hidden;
            white-space: nowrap;
        }
        .marquee-text {
            display: inline-block;
            white-space: nowrap;
            color: black;
            animation: marquee 80s linear infinite;
            padding-left: 100%;
        }
        @keyframes marquee {
            0% { transform: translateX(0); }
            100% { transform: translateX(-100%); }
        }
    </style>
</head>
<body>
    <!-- Marquee Section -->
    <?php if (!empty($marquee_text) && $marquee_text !== 'No marquee notifications available.'): ?>
        <div class="marquee-container">
            <div class="marquee-text">
                <?php echo $marquee_text; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Header Section -->
    <header>
        <div class="logo">
            <img src="image/logo.PNG" alt="Ballot Box Logo">
            <h1>Ballot Box</h1>
        </div>
        <nav>
            <ul>
                <li><a href="#" class="nav-link">Home</a></li>
                <li><a href="#about" class="nav-link">About Us</a></li>
                <li><a href="student-login.php" class="nav-link">Voter Login</a></li>
                
                <li class="dropdown">
                    <a href="#" class="nav-link"> Login</a>
                    <div class="dropdown-content">
                        <a href="staff-login.php">Admin</a>
                        <a href="tutor-login.php">Tutor</a>
                    </div>
                </li>
                <li class="dropdown">
                    <a href="#" class="nav-link">Register</a>
                    <div class="dropdown-content">
                        <a href="register.php"> Candidate</a>
                        <a href="voter_register.php"> Voter</a>
                    </div>
                </li>
                <li class="dropdown">
                    <a href="#" class="nav-link">More</a>
                    <div class="dropdown-content">
                        <a href="check-status.php">Check Voter Status</a>
                        <a href="grievance.php">Grievance/Issue</a>
                        <a href="display-candidate.php">Meet the Candidate</a>
                        <a href="winners-poster.php">Winners</a>
                    </div>
                </li>
                <li class="dropdown">
                    <a href="#" class="nav-link"><i class="fas fa-bell"></i></a>
                    <div class="dropdown-content notifications">
                        <?php if (mysqli_num_rows($notifications_result) > 0): ?>
                            <?php while ($notification = mysqli_fetch_assoc($notifications_result)): ?>
                                <div class="notification-item">
                                    [<?php echo $notification['created_at']; ?>] <?php echo htmlspecialchars($notification['message']); ?>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="notification-item">
                                No notifications available.
                            </div>
                        <?php endif; ?>
                    </div>
                </li>
            </ul>
        </nav>
    </header>

    <!-- Main Content Section -->
    <main>
        <section id="main-content">
            <h2>Welcome to Ballot Box</h2>
            <p>This is an online voting system designed to facilitate student elections at Albertian Institute of Science & Technology, Kalamassery. Our platform ensures fair and transparent elections, allowing students to cast their votes easily and securely.</p>
            <img src="image/p1.png" alt="Voting Image" style="width: 50%; height: auto;">
        </section>
        <section id="about">
            <h2>About Us</h2>
            <p>Welcome to the Student Voting System of Albertian Institute of Science & Technology, Kalamassery. This platform allows students to participate in democratic elections within the college, ensuring transparency and fairness in the voting process. The voting system is designed to be user-friendly and accessible to all students in thecollege.</p>
        </section>
    </main>

    <!-- Footer Section -->
    <footer>
        <div class="footer-content">
            <section>
                <h3>About Us</h3>
                <p>This system is developed to facilitate student elections in a seamless and transparent manner.</p>
            </section>
            <section>
                <h3>Contact Us</h3>
                <p>Albertian Institute of Science & Technology, Kalamassery<br>University Road, South Kalamassery, Kalamassery, Kochi, Kerala 682022</p>
                <p>Department: Computer Science & Engineering</p>
                <p>Phone: 8943789868, 123456789</p>
                <p>Email: hod.cse@aisat.ac.in</p>
            </section>
        </div>
    </footer>
</body>
</html>