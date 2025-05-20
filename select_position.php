<?php
session_start();
include 'connect.php'; // Ensure you have a database connection file
include 'functions.php'; // Include the centralized functions

// Check election status
$electionStatus = updateElectionStatus($conn);
if ($electionStatus !== 'ongoing') {
    session_destroy(); // End the session
    header("Location: student-login.php");
    exit();
}

// Check if the student is logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: student-login.php");
    exit();
}

$student_id = $_SESSION['student_id']; // Fixed: Use student_id instead of voter_id

// Fetch positions from the database
$sql = "SELECT p.id, p.position_name, 
               (SELECT COUNT(*) FROM votes WHERE votes.student_id = '$student_id' AND votes.position_id = p.id) AS has_voted
        FROM positions p";
$result = $conn->query($sql);

// Check if there are any positions
$hasPositions = ($result && $result->num_rows > 0);

// Handle logout
if (isset($_GET['logout'])) {
    // Destroy the session
    session_destroy();
    // Redirect to login page
    header("Location: student-login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Position - Ballot Box</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f0f0f0;
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Navigation Bar */
        .navbar {
            background-color: #222;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        
        .logo {
            display: flex;
            align-items: center;
            color: white;
            text-decoration: none;
            font-size: 24px;
            font-weight: bold;
        }
        
        .logo img {
            width: 40px;
            height: 40px;
            margin-right: 10px;
        }
        
        .nav-links {
            display: flex;
            list-style: none;
        }
        
        .nav-links li {
            margin-left: 20px;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            font-size: 16px;
            transition: color 0.3s;
        }
        
        .nav-links a:hover {
            color: #ccc;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px;
        }
        
        .page-title {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }
        
        .position-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 60px;
        }
        
        .position-card {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
            position: relative;
        }
        
        .position-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }
        
        .position-card.disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        
        .position-card .position-header {
            padding: 15px;
            background-color: #222;
            color: white;
            font-size: 1.2rem;
            text-align: center;
        }
        
        .position-card .position-body {
            padding: 20px;
            text-align: center;
        }
        
        .position-card .position-icon {
            font-size: 4rem;
            color: #222;
            margin-bottom: 15px;
        }
        
        .position-card .position-description {
            margin-bottom: 20px;
            font-size: 0.9rem;
            color: #666;
        }
        
        .position-card .position-btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #222;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
            text-decoration: none;
            width: 100%;
        }
        
        .position-card .position-btn:hover {
            background-color: #444;
        }
        
        .position-card.disabled .position-btn {
            background-color: #777;
            cursor: not-allowed;
        }
        
        .position-card .voted-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: #2ecc71;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .no-positions {
            text-align: center;
            padding: 50px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        /* Footer with Logout Button */
        .footer {
            background-color: #222;
            padding: 20px;
            text-align: center;
            margin-top: auto;
        }
        
        .logout-btn {
            display: inline-block;
            background-color: #333;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        
        .logout-btn:hover {
            background-color: #444;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .position-grid {
                grid-template-columns: 1fr;
            }
            
            .nav-links {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <a href="#" class="logo">
            <img src="image/logo.PNG" alt="Ballot Box Logo"> Ballot Box
        </a>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <h1 class="page-title">Select a Position to Vote</h1>
        
        <?php if ($hasPositions): ?>
            <div class="position-grid">
                <?php while ($row = $result->fetch_assoc()): 
                    $hasVoted = ($row['has_voted'] > 0);
                    $icon = getPositionIcon($row['position_name']); // Function defined below
                ?>
                    <div class="position-card <?php echo $hasVoted ? 'disabled' : ''; ?>">
                        <?php if ($hasVoted): ?>
                            <span class="voted-badge">Voted</span>
                        <?php endif; ?>
                        <div class="position-header">
                            <?php echo htmlspecialchars($row['position_name']); ?>
                        </div>
                        <div class="position-body">
                            <div class="position-icon">
                                <i class="<?php echo $icon; ?>"></i>
                            </div>
                            <p class="position-description">
                                Vote for your preferred candidate for this position.
                            </p>
                            <?php if (!$hasVoted): ?>
                                <a href="voting-page.php?position_id=<?php echo $row['id']; ?>" class="position-btn">
                                    Vote Now
                                </a>
                            <?php else: ?>
                                <button class="position-btn" disabled>Already Voted</button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-positions">
                <h2>No positions available for voting at this time.</h2>
                <p>Please check back later or contact the administrator.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Footer with Logout Button -->
    <div class="footer">
        <a href="?logout=true" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
    
    <?php
    // Helper function to get appropriate icon for each position
    function getPositionIcon($position) {
        $position = strtolower($position);
        
        if (strpos($position, 'president') !== false) {
            return 'fas fa-user-tie';
        } elseif (strpos($position, 'secretary') !== false) {
            return 'fas fa-clipboard';
        } elseif (strpos($position, 'treasurer') !== false) {
            return 'fas fa-money-bill';
        } elseif (strpos($position, 'vice') !== false) {
            return 'fas fa-user-friends';
        } elseif (strpos($position, 'sport') !== false) {
            return 'fas fa-basketball-ball';
        } elseif (strpos($position, 'art') !== false || strpos($position, 'culture') !== false) {
            return 'fas fa-palette';
        } elseif (strpos($position, 'academic') !== false) {
            return 'fas fa-book';
        } else {
            return 'fas fa-vote-yea';
        }
    }
    ?>
</body>
</html>