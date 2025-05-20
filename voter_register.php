<?php
session_start();
include('connect.php');

// Prevent caching issues (stops back button restoring form)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = trim($_POST['student_id']);
    $name = trim($_POST['name']);
    $gender = trim($_POST['gender']);
    $department = trim($_POST['department']);
    $batch = trim($_POST['batch']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    // ✅ Step 1: Validate Student ID
    if (empty($student_id)) {
        die("Error: Student ID is missing! Check form submission.");
    }

    // ✅ Step 2: Check if Student Already Exists
    $checkQuery = "SELECT * FROM voter_reg WHERE student_id = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        die("Error: Student ID already exists in voter_reg!");
    }

    // ✅ Step 3: Check if Email Already Exists
    $checkEmailQuery = "SELECT * FROM voter_reg WHERE email = ?";
    $stmt = $conn->prepare($checkEmailQuery);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $emailResult = $stmt->get_result();

    if ($emailResult->num_rows > 0) {
        die("Error: Email already exists in voter_reg! Try a different one.");
    }

    // ✅ Step 4: Find the Assigned Tutor (Auto-Assignment)
    $tutor_query = "SELECT tutor_id FROM tutor_login WHERE department = ? AND batch = ? LIMIT 1";
    $stmt = $conn->prepare($tutor_query);
    $stmt->bind_param("ss", $department, $batch); // 'ss' since both are strings
    $stmt->execute();
    $tutor_result = $stmt->get_result();
    $tutor = $tutor_result->fetch_assoc();

    $tutor_id = $tutor ? $tutor['tutor_id'] : NULL;

    if (!$tutor) {
        $_SESSION['error'] = "No tutor assigned for this department and batch. Please contact the administrator.";
        header("Location: voter_register.php");
        exit();
    }

    // ✅ Step 5: Insert New Voter (Only if email and student_id are unique)
    $insertQuery = "INSERT INTO voter_reg (student_id, name, gender, department, batch, email, phone, tutor_id, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending')";
    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param("sssssssi", $student_id, $name, $gender, $department, $batch, $email, $phone, $tutor_id);

    if ($stmt->execute()) {
        // ✅ Registration successful
        echo "<script>alert('Voter registration successful!'); window.location.href='voter_register.php';</script>";
    } else {
        // ❌ Registration failed
        echo "<script>alert('Registration failed! Please try again.'); window.location.href='voter_register.php';</script>";
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Ballot Box</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0">
    <meta http-equiv="Cache-Control" content="post-check=0, pre-check=0">
    <meta http-equiv="Pragma" content="no-cache">
    <style>
        :root {
            --primary: #212529;
            --primary-dark: #121416;
            --secondary: #343a40;
            --success: #2d3436;
            --success-dark: #1e2224;
            --danger: #e63946;
            --danger-dark: #d52b39;
            --warning: #ff9f1c;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --gray-light: #ced4da;
            --white: #ffffff;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --radius: 8px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f5f5;
            color: var(--dark);
            line-height: 1.6;
            min-height: 100vh;
        }

        header {
            background-color: var(--primary);
            color: var(--white);
            padding: 20px 0;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .logo img {
            height: 50px;
            width: auto;
        }

        .logo h1 {
            font-size: 24px;
            font-weight: 600;
            margin: 0;
        }

        main {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .register-section {
            background-color: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 30px;
        }

        h2 {
            color: var(--primary);
            text-align: center;
            margin-bottom: 30px;
            font-size: 28px;
            font-weight: 600;
            position: relative;
            padding-bottom: 10px;
        }

        h2:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background-color: var(--primary);
        }

        .message {
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: var(--radius);
            text-align: center;
            font-weight: 500;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
        }

        input,
        select {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--gray-light);
            border-radius: var(--radius);
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .submit-button {
            grid-column: span 2;
            background-color: var(--primary);
            color: var(--white);
            border: none;
            padding: 14px;
            font-size: 16px;
            font-weight: 500;
            border-radius: var(--radius);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .submit-button:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .home-button {
            display: inline-block;
            padding: 12px 25px;
            background-color: var(--primary);
            color: white;
            border-radius: var(--radius);
            text-decoration: none;
            transition: all 0.3s ease;
            margin-top: 30px;
        }
    </style>
    <script>
        function validateForm() {
            const phone = document.getElementById('phone').value;
            if (phone.length !== 10 || isNaN(phone)) {
                alert("Phone number must be exactly 10 digits.");
                return false;
            }
            return true;
        }
    </script>
</head>

<body>
    <header>
        <div class="logo">
            <img src="image/logo.PNG" alt="Ballot Box Logo">
            <h1>Ballot Box</h1>
        </div>
    </header>

    <main>
        <section class="register-section">
            <h2><i class="fas fa-user-plus"></i> Voter Registration</h2>

            <!-- Success/Error Message (only shown once) -->
            <?php if (isset($_SESSION['message'])) : ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i> <?php echo $_SESSION['message']; ?>
                </div>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])) : ?>
                <div class="message error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <form action="voter_register.php" method="post" enctype="multipart/form-data" class="register-form" onsubmit="return validateForm();">
                <div class="form-group">
                    <label for="student_id">Student ID</label>
                    <input type="text" id="student_id" name="student_id" placeholder="Enter your student ID" required>
                </div>

                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" placeholder="Enter your full name" required>
                </div>

                <div class="form-group">
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender" required>
                        <option value="">Select Gender</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="department">Department</label>
                    <select id="department" name="department" required>
                        <option value="">Select Department</option>
                        <option value="CSE">Computer Science & Engineering</option>
                        <option value="EEE">Electrical & Electronics Engineering</option>
                        <option value="EC">Electronics & Communication Engineering</option>
                        <option value="ME">Mechanical Engineering</option>
                        <option value="CE">Civil Engineering</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="batch">Batch</label>
                    <input type="text" id="batch" name="batch" placeholder="Enter Batch (e.g., 11)" required>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email address" required>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" placeholder="Enter 10-digit phone number" required>
                </div>

                <button type="submit" name="submit" class="submit-button">
                    <i class="fas fa-paper-plane"></i> Register
                </button>
            </form>

            <div class="button-container">
                <a href="index.php" class="home-button">
                    <i class="fas fa-home"></i> Go to Home
                </a>
            </div>
        </section>
    </main>
</body>

</html>
