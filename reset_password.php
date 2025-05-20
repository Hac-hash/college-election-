<?php
session_start();
include('connect.php');

if (!isset($_GET['token'])) {
    $_SESSION['error'] = "Invalid or expired reset link.";
    header("Location: student-login.php");
    exit();
}

$token = $_GET['token'];
$error = "";
$success = "";

// Check if the reset token exists in the database
$stmt = $conn->prepare("SELECT email FROM voter_reg WHERE reset_token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['error'] = "Invalid or expired reset link.";
    header("Location: student-login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $password = trim($_POST["password"]);
    $confirm_password = trim($_POST["confirm_password"]);

    // Password validation
    if (strlen($password) < 6) {
        $error = "❌ Password must be at least 6 characters!";
    } elseif ($password !== $confirm_password) {
        $error = "❌ Passwords do not match!";
    } else {
        // Hash the new password
        $new_password = password_hash($password, PASSWORD_BCRYPT);

        // Update password and remove reset token
        $stmt = $conn->prepare("UPDATE voter_reg SET password = ?, reset_token = NULL WHERE reset_token = ?");
        $stmt->bind_param("ss", $new_password, $token);
        if ($stmt->execute()) {
            $success = "✅ Password updated successfully! Redirecting to login...";
            
            // Redirect to login after 3 seconds
            echo "<script>
                    setTimeout(function() {
                        window.location.href = 'student-login.php';
                    }, 3000);
                  </script>";
        } else {
            $error = "❌ Something went wrong. Try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .reset-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 350px;
        }

        h2 {
            color: #333;
        }

        .form-group {
            margin-bottom: 15px;
            text-align: left;
        }

        input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }

        .error-message {
            color: red;
            font-weight: bold;
        }

        .success-message {
            color: green;
            font-weight: bold;
        }

        .btn {
            width: 100%;
            padding: 12px;
            background-color: black;
            color: white;
            font-size: 18px;
            font-weight: bold;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
        }

        .btn:hover {
            background-color: #333;
        }
    </style>
</head>
<body>

    <div class="reset-container">
        <h2>Reset Password</h2>

        <?php if ($error): ?>
            <p class="error-message"><?php echo $error; ?></p>
        <?php endif; ?>

        <?php if ($success): ?>
            <p class="success-message"><?php echo $success; ?></p>
        <?php else: ?>
            <form action="" method="post">
                <div class="form-group">
                    <label for="password">New Password:</label>
                    <input type="password" name="password" id="password" placeholder="Enter new password" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password:</label>
                    <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm new password" required>
                </div>

                <button type="submit" class="btn">Reset Password</button>
            </form>
        <?php endif; ?>
    </div>

</body>
</html>
