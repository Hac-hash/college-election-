<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; 
include 'connect.php'; 

$message = "";

if(isset($_POST['submit'])) {
    $email = trim($_POST['email']);
    
    // Check if email exists in voter_reg table
    $stmt = $conn->prepare("SELECT * FROM voter_reg WHERE email = ? AND status = 'approved'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Generate a secure reset token
        $token = bin2hex(random_bytes(50));
        
        // Store the token in the database
        $stmt = $conn->prepare("UPDATE voter_reg SET reset_token = ? WHERE email = ?");
        $stmt->bind_param("ss", $token, $email);
        $stmt->execute();
        
        // Prepare the reset link
        $reset_link = "http://localhost/miniproject/reset_password.php?token=$token";
        
        // Send email using PHPMailer
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'electionadm2025@gmail.com'; 
            $mail->Password = 'ytwhxdrgxmzacdam'; // App Password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            
            $mail->setFrom('electionadm2025@gmail.com', 'Voting System');
            $mail->addAddress($email);
            $mail->Subject = "Password Reset Request";
            $mail->Body = "Click the link to reset your password: $reset_link";
            $mail->send();
            
            $message = "<p class='success'>✅ Password reset link has been sent to your email!</p>";
        } catch (Exception $e) {
            $message = "<p class='error'>❌ Email sending failed: " . $mail->ErrorInfo . "</p>";
        }
    } else {
        $message = "<p class='error'>❌ Email not found or not approved!</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <style>
        /* General Styling */
      /* Full Page Styling */
body {
    font-family: Arial, sans-serif;
    background: linear-gradient(135deg, #f8f9fa, #e3eaf3); /* Soft gradient */
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    margin: 0;
}

/* Forgot Password Box */
.forgot-container {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0px 5px 20px rgba(0, 0, 0, 0.2); /* Soft shadow */
    text-align: center;
    width: 340px; /* Slightly reduced width for better balance */
    border: 2px solid #ddd;
}

/* Header Styling */
.forgot-container h2 {
    margin-bottom: 15px;
    color: #333;
    font-size: 22px;
}

/* Form Fields */
.form-group {
    text-align: left;
    margin-bottom: 12px;
}

label {
    font-size: 14px;
    font-weight: bold;
    color: #555;
    display: block;
}

/* Input Field */
input {
    width: 95%; /* Shortened input field for better proportion */
    padding: 10px;
    border: 1.5px solid #ccc;
    border-radius: 6px;
    font-size: 14px;
    transition: 0.3s;
}

/* Input Focus Effect */
input:focus {
    border-color: #007bff;
    box-shadow: 0px 0px 8px rgba(0, 123, 255, 0.3);
    outline: none;
}

/* Error & Success Messages */
.error, .success {
    font-weight: bold;
    padding: 10px;
    border-radius: 6px;
    margin-top: 10px;
    font-size: 14px;
}

.error {
    color: red;
    background: #ffebeb;
    border: 1px solid #ff4d4d;
}

.success {
    color: green;
    background: #e8ffe8;
    border: 1px solid #4caf50;
}

/* Submit Button */
.btn {
    width: 100%;
    padding: 10px;
    background-color: black;
    color: white;
    font-size: 16px;
    font-weight: bold;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    margin-top: 15px;
    transition: 0.3s;
}

.btn:hover {
    background-color: #333;
}


    </style>
</head>
<body>

    <div class="forgot-container">
        <h2>Forgot Password</h2>

        <?php if ($message): ?>
            <?php echo $message; ?>
        <?php endif; ?>

        <form action="" method="post">
            <div class="form-group">
                <label for="email">Enter your registered email:</label>
                <input type="email" name="email" id="email" placeholder="Email Address" required>
            </div>

            <button type="submit" name="submit" class="btn">Send Reset Link</button>
        </form>
        <br><br>
<a href="index.php" class="btn">🏠 Home</a>


    </div>

</body>
</html>
