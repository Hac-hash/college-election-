<?php
include('connect.php');

$statusMessage = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = $_POST['student_id'];

    // Query to check status in voter_reg table
    $query = "SELECT name, status FROM voter_reg WHERE student_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $voter = $result->fetch_assoc();
        $name = $voter['name'];
        $status = $voter['status'];

        // Updated status message styling
        if ($status == "approved") {
            $statusMessage = "<div class='status-message success'>
                <div class='status-icon'>✓</div>
                <div class='status-content'>
                    <p class='status-name'>Hello, {$name}</p>
                    <p class='status-detail'>Your voter registration is <strong>Approved</strong></p>
                    <p class='status-action'>You can now vote!</p>
                </div>
            </div>";
        } elseif ($status == "pending") {
            $statusMessage = "<div class='status-message pending'>
                <div class='status-icon'>!</div>
                <div class='status-content'>
                    <p class='status-name'>Hello, {$name}</p>
                    <p class='status-detail'>Your registration is <strong>Pending</strong></p>
                    <p class='status-action'>Please contact the administrator</p>
                </div>
            </div>";
        }
    } else {
        // If not found in voter_reg, check rejected_voters table
        $query = "SELECT name FROM rejected_voters WHERE student_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $voter = $result->fetch_assoc();
            $name = $voter['name'];
            $statusMessage = "<div class='status-message error'>
                <div class='status-icon'>✗</div>
                <div class='status-content'>
                    <p class='status-name'>Sorry, {$name}</p>
                    <p class='status-detail'>Your voter registration has been <strong>Rejected</strong></p>
                    <p class='status-action'>Please contact the administrator</p>
                </div>
            </div>";
        } else {
            $statusMessage = "<div class='status-message error'>
                <div class='status-icon'>✗</div>
                <div class='status-content'>
                    <p class='status-name'>No voter found</p>
                    <p class='status-detail'>Student ID <strong>{$student_id}</strong> not found</p>
                    <p class='status-action'>Please check your ID</p>
                </div>
            </div>";
        }
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voter Registration Status</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #1a1a2e;
            --secondary-color: #16213e;
            --accent-color: #0f3460;
            --background-color: #f4f4f4;
            --white: #ffffff;
            --success-color: #2ecc71;
            --pending-color: #f39c12;
            --error-color: #e74c3c;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--background-color);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            line-height: 1.6;
            color: var(--primary-color);
        }

        .container {
            background-color: var(--white);
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
            padding: 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .return-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            text-decoration: none;
            color: var(--secondary-color);
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            transition: color 0.3s ease;
        }

        .return-btn:hover {
            color: var(--accent-color);
        }

        .container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
        }

        .header {
            margin-bottom: 30px;
        }

        .header h2 {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .header p {
            color: var(--secondary-color);
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--secondary-color);
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--accent-color);
        }

        .submit-btn {
            width: 100%;
            padding: 12px;
            background-color: var(--primary-color);
            color: var(--white);
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .submit-btn:hover {
            background-color: var(--secondary-color);
        }

        .status-message {
            margin-top: 20px;
            padding: 15px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            text-align: left;
        }

        .status-message .status-icon {
            font-size: 24px;
            margin-right: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }

        .status-message .status-content {
            flex-grow: 1;
        }

        .status-message .status-name {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .status-message .status-detail {
            font-size: 14px;
            margin-bottom: 5px;
        }

        .status-message .status-action {
            font-size: 12px;
            opacity: 0.8;
        }

        .status-message.success {
            background-color: rgba(46, 204, 113, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(46, 204, 113, 0.3);
        }

        .status-message.success .status-icon {
            background-color: rgba(46, 204, 113, 0.2);
        }

        .status-message.pending {
            background-color: rgba(243, 156, 18, 0.1);
            color: var(--pending-color);
            border: 1px solid rgba(243, 156, 18, 0.3);
        }

        .status-message.pending .status-icon {
            background-color: rgba(243, 156, 18, 0.2);
        }

        .status-message.error {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--error-color);
            border: 1px solid rgba(231, 76, 60, 0.3);
        }

        .status-message.error .status-icon {
            background-color: rgba(231, 76, 60, 0.2);
        }

        @media (max-width: 480px) {
            .container {
                width: 95%;
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="return-btn">← Back to Home</a>
        
        <div class="header">
            <h2>Voter Registration Status</h2>
            <p>Check your current voter registration status</p>
        </div>

        <form method="POST">
            <div class="form-group">
                <label for="student_id">Student ID</label>
                <input 
                    type="text" 
                    name="student_id" 
                    placeholder="Enter your student ID" 
                    required
                >
            </div>
            
            <button type="submit" class="submit-btn">Check Status</button>
        </form>
       <!-- <button onclick="window.location.href='winners-poster.php'" class="cancel-btn">Cancel</button>-->

        <!-- Display Status Message -->
        <?php echo $statusMessage; ?>
    </div>
</body>
</html>