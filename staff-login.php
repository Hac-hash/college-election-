<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('connect.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['user_id'];
    $password = $_POST['password'];
    
    // Check if it's an admin login
    $stmt = $conn->prepare("SELECT * FROM staff_login WHERE staff_id = ? AND password = ?");
    $stmt->bind_param("ss", $user_id, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $_SESSION['logged_in'] = true;
        $_SESSION['user_role'] = 'admin';
        $_SESSION['user_id'] = $user_id;
        $_SESSION['success'] = "Login successful!";
        header("Location: staff-dashboard.php");
        exit();
    } /*else {
        // If not admin, check if it's a tutor
        $stmt = $conn->prepare("SELECT * FROM tutor_login WHERE tutor_id = ? AND password = ?");
        $stmt->bind_param("ss", $user_id, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $_SESSION['logged_in'] = true;
            $_SESSION['user_role'] = 'tutor';
            $_SESSION['user_id'] = $user_id;
            $_SESSION['success'] = "Login successful!";
            header("Location: tutor-dashboard.php");
            exit();
        } else {
            $_SESSION['error'] = "Invalid ID or password!";
        }
    }*/

    // If authentication fails, redirect back with error
    header("Location: staff-login.php");
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login - Ballot Box</title>
    <link rel="stylesheet" href="reg.css">
    <style>
        /* Your existing style remains the same */
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

        input[type="text"], input[type="password"], select {
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

        /* Error message styling */
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            text-align: center;
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
              <!--   <li><a href="student-login.php" class="nav-link">Voter Login</a></li>-->
                   
                <li class="dropdown">
                    <a href="#" class="nav-link"> Login</a>
                        <div class="dropdown-content">
                            <a href="staff-login.php">Admin</a>
                        <a href="tutor-login.php">Tutor</a>
                 

    </div>
</li>

              
               <!-- Register Button with Sub-button 
                <li class="dropdown">
                    <a href="#" class="nav-link">Register</a>
                    <div class="dropdown-content">
                        <a href="register.php"> Candidate</a>
                        <a href="voter_register.php"> Voter</a>
                        
                    </div>
                </li>
                <li class="dropdown">
                    <a href="#" class="nav-link">Result</a>
                    <div class="dropdown-content">
                  <a href="check-status.php">Check Voter Status</a>
                  <a href="display-candidate.php">Meet the Candidate</a> --><!-- New Sub-button -->
                  
                  </div>
                  </li>
            </ul>
        </nav>
    </header>
    
    <!-- Main Content -->
    <main>
        <section>
            <h2>Admin Login</h2>
            
            <?php if(isset($_SESSION['error'])): ?>
                <div class="error-message">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            
            <form action="staff-login.php" method="post">
                <div class="form-group">
                    <label for="user_id">User ID:</label>
                    <input type="text" id="user_id" name="user_id" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <!-- Add the role selection dropdown here 
                <div class="form-group">
                    <label for="role">Login as:</label>
                    <select id="role" name="role" required>
                        <option value="admin">Admin</option>
                        <option value="tutor">Tutor</option>
                    </select>
                </div> -->

                <button type="submit" name="submit">Login</button>
            </form>
        </section>
    </main>
</body>
</html>