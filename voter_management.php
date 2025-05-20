<?php
include('connect.php'); // Database connection
if (isset($_POST['logout'])) {
    session_destroy(); // Destroy the session
    header("Location: staff-dashboard.php"); // Redirect to staff dashboard
    exit();
}
// Fetch 20 tutors from the database
$query = "SELECT tutor_id, name, department, batch FROM tutor_login LIMIT 20";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voter Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #333333;
            --button-color: #2e8b57;
            --light-gray: #f8f9fa;
            --border-radius: 12px;
            --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fb;
            color: #333;
            padding: 20px;
            position: relative;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px;
        }
        
        .header {
            margin-bottom: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 28px;
            font-weight: 600;
            color: #333;
        }
        
        .logout-container {
            position: absolute;
            top: 20px;
            left: 20px;
        }
        
        .logout-btn {
            padding: 12px 24px;
            background-color: #f44336;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.2s;
        }
        
        .logout-btn:hover {
            background-color: #d32f2f;
        }
        
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }
        
        .tutor-card {
            background-color: #fff;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .tutor-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            background-color: var(--primary-color);
            color: white;
            padding: 16px;
            text-align: center;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .tutor-info {
            margin-bottom: 20px;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 8px;
            align-items: center;
        }
        
        .info-label {
            font-weight: 600;
            width: 100px;
            color: #555;
        }
        
        .info-value {
            flex: 1;
        }
        
        .card-footer {
            padding: 16px;
            border-top: 1px solid #eee;
            text-align: center;
        }
        
        .view-btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: var(--button-color);
            color: white;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.2s;
        }
        
        .view-btn:hover {
            background-color: #236b43;
        }
        
        .icon {
            margin-right: 8px;
            width: 16px;
        }
        
        @media (max-width: 768px) {
            .card-grid {
                grid-template-columns: 1fr;
            }
            
            .container {
                padding: 15px;
            }
            
            .logout-container {
                position: static;
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <form method="POST">
            <button type="submit" name="logout" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </form>
    </div>
    
    <div class="container">
        <div class="header">
            <h1>Voter Management Dashboard</h1>
        </div>
        
        <div class="card-grid">
            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                <div class="tutor-card">
                    <div class="card-header">
                        <h3><?php echo $row['name']; ?></h3>
                    </div>
                    <div class="card-body">
                        <div class="tutor-info">
                            <div class="info-row">
                                <div class="info-label">
                                    <i class="fas fa-building icon"></i>Department:
                                </div>
                                <div class="info-value"><?php echo $row['department']; ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">
                                    <i class="fas fa-users icon"></i>Batch:
                                </div>
                                <div class="info-value"><?php echo $row['batch']; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <a href="view_approved_voters.php?tutor_id=<?php echo $row['tutor_id']; ?>" class="view-btn">
                            <i class="fas fa-eye"></i> View Approved Voters
                        </a>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</body>
</html>