<?php
include 'connect.php';

// Check if the database connection was successful
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Fetch all positions
$positionsQuery = "SELECT id, position_name FROM positions ORDER BY position_name";
$positionsResult = mysqli_query($conn, $positionsQuery);

if (!$positionsResult) {
    die("Error fetching positions: " . mysqli_error($conn));
}

$positions = [];
while ($row = mysqli_fetch_assoc($positionsResult)) {
    $positions[$row['id']] = $row['position_name'];
}

// Fetch selected candidates and group them by position
$selectedCandidatesByPosition = [];
foreach ($positions as $positionId => $positionName) {
    $query = "SELECT sc.student_id, sc.name, sc.photo, sc.department, sc.bio
              FROM selected_candidate sc
              WHERE sc.position_id = ? AND sc.status = 'approved'
              ORDER BY sc.name";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        die("Error preparing statement: " . $conn->error);
    }
    
    $stmt->bind_param("i", $positionId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $candidates = [];
    while ($row = $result->fetch_assoc()) {
        $candidates[] = $row;
    }
    
    if (!empty($candidates)) {
        $selectedCandidatesByPosition[$positionName] = $candidates;
    }
    
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meet the Selected Candidates</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color:rgb(17, 24, 30);
            --secondary-color: #3498db;
            --text-color: #333;
            --background-color: #f4f7f6;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }

        .header {
            background-color: var(--primary-color);
            color: white;
            text-align: center;
            padding: 3rem 0;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            opacity: 0.9;
            z-index: 1;
        }

        .header-content {
            position: relative;
            z-index: 2;
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .header p {
            font-size: 1.1rem;
            opacity: 0.8;
        }

        .candidates-section {
            padding: 4rem 0;
        }

        .position-group {
            margin-bottom: 3rem;
        }

        .position-title {
            text-align: center;
            margin-bottom: 2rem;
            position: relative;
            padding-bottom: 1rem;
        }

        .position-title h2 {
            font-size: 2rem;
            font-weight: 500;
            color: var(--primary-color);
        }

        .position-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 3px;
            background-color: var(--secondary-color);
        }

        .candidates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }

        .candidate-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            padding: 2rem;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .candidate-card:hover {
            transform: translateY(-10px);
        }

        .candidate-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--secondary-color);
            margin-bottom: 1rem;
        }

        .candidate-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .candidate-department {
            color: var(--secondary-color);
            font-weight: 500;
            margin-bottom: 1rem;
        }

        .candidate-bio {
            font-size: 0.9rem;
            color: #666;
            font-style: italic;
        }

        .no-candidates {
            text-align: center;
            padding: 3rem;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 2rem;
            }

            .candidates-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container header-content">
            <h1>Meet the Selected Candidates</h1>
            <p>Elected Representatives</p>
        </div>
    </header>

    <main class="container candidates-section">
        <?php if (empty($selectedCandidatesByPosition)): ?>
            <div class="no-candidates">
                <h2>No Candidates Selected</h2>
                <p>Stay tuned for upcoming announcements.</p>
            </div>
        <?php else: ?>
            <?php foreach ($selectedCandidatesByPosition as $positionName => $candidates): ?>
                <section class="position-group">
                    <div class="position-title">
                        <h2><?php echo htmlspecialchars($positionName); ?></h2>
                    </div>
                    
                    <div class="candidates-grid">
                        <?php foreach ($candidates as $candidate): ?>
                            <div class="candidate-card">
                                <img 
                                    src="uploads/<?php echo htmlspecialchars($candidate['photo']); ?>" 
                                    alt="<?php echo htmlspecialchars($candidate['name']); ?>" 
                                    class="candidate-image"
                                >
                                <h3 class="candidate-name"><?php echo htmlspecialchars($candidate['name']); ?></h3>
                                <p class="candidate-department"><?php echo htmlspecialchars($candidate['department']); ?></p>
                                <p class="candidate-bio">
                                    <?php echo !empty($candidate['bio']) 
                                        ? htmlspecialchars($candidate['bio']) 
                                        : 'Passionate about leadership and community.'; ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
</body>
</html>