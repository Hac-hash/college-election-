<?php
include('connect.php');

// Add a tutor to tutor_login instead of tutors
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_tutor'])) {
    $tutor_id = $_POST['tutor_id'];
    $name = $_POST['name'];
    $department = $_POST['department'];
    $batch = $_POST['batch'];
    $password = $_POST['password'];

    // Hash the password before storing
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // Check if tutor already exists in tutor_login
    $check_tutor = mysqli_query($conn, "SELECT * FROM tutor_login WHERE tutor_id = '$tutor_id'");
    if (mysqli_num_rows($check_tutor) == 0) {
        mysqli_query($conn, "INSERT INTO tutor_login (tutor_id, name, department, batch, password) VALUES ('$tutor_id', '$name', '$department', '$batch', '$hashed_password')");
        echo "<script>alert('Tutor added successfully!'); window.location.href='select-tutor.php';</script>";
    } else {
        echo "<script>alert('Tutor with this ID already exists!');</script>";
    }
}

// Delete a tutor
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_tutor'])) {
    $tutor_id = $_POST['tutor_id'];
    mysqli_query($conn, "DELETE FROM tutor_login WHERE tutor_id = '$tutor_id'");
    echo "<script>alert('Tutor deleted successfully!'); window.location.href='select-tutor.php';</script>";
}

// Update tutor
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_tutor'])) {
    $tutor_id = $_POST['tutor_id'];
    $new_id = $_POST['new_tutor_id'];
    $name = $_POST['name'];
    $department = $_POST['department'];
    $batch = $_POST['batch'];
    $password = $_POST['password'];
    
    // Hash new password
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    mysqli_query($conn, "UPDATE tutor_login SET tutor_id='$new_id', name='$name', department='$department', batch='$batch', password='$hashed_password' WHERE tutor_id='$tutor_id'");
    echo "<script>alert('Tutor updated successfully!'); window.location.href='select-tutor.php';</script>";
}

// Fetch all tutors from tutor_login
$tutors_result = mysqli_query($conn, "SELECT * FROM tutor_login");
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Tutors - Ballot Box</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
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
        }
        
        .container {
            width: 90%;
            max-width: 1000px;
            margin: 40px auto;
            background-color: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        
        .header {
            background-color: var(--primary);
            color: var(--white);
            padding: 25px;
            text-align: center;
            position: relative;
        }
        
        .header h2 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        
        .content {
            padding: 30px;
        }
        
        .card {
            background-color: var(--white);
            border-radius: var(--radius);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .card-header {
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .card-header h3 {
            color: var(--primary);
            font-size: 20px;
            font-weight: 600;
            margin: 0;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-col {
            flex: 1;
            min-width: 200px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
        }
        
        input, select {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--gray-light);
            border-radius: var(--radius);
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(33, 37, 41, 0.15);
        }
        
        .btn {
            display: inline-block;
            padding: 12px 20px;
            font-size: 14px;
            font-weight: 500;
            text-align: center;
            text-decoration: none;
            border: none;
            border-radius: var(--radius);
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: var(--white);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .btn-success {
            background-color: var(--success);
            color: var(--white);
        }
        
        .btn-success:hover {
            background-color: var(--success-dark);
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background-color: var(--danger);
            color: var(--white);
        }
        
        .btn-danger:hover {
            background-color: var(--danger-dark);
            transform: translateY(-2px);
        }
        
        .btn-wide {
            width: 100%;
        }
        
        .table-responsive {
            overflow-x: auto;
            margin-top: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        thead {
            background-color: var(--primary);
            color: var(--white);
        }
        
        th, td {
            padding: 15px;
            text-align: left;
        }
        
        tbody tr {
            transition: background-color 0.3s;
        }
        
        tbody tr:nth-child(even) {
            background-color: #f5f5f5;
        }
        
        tbody tr:hover {
            background-color: #e9e9e9;
        }
        
        .actions {
            display: flex;
            gap: 10px;
        }
        
        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 12px;
            font-size: 13px;
            border-radius: var(--radius);
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .action-btn i {
            margin-right: 5px;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            margin-top: 20px;
            transition: all 0.3s;
        }
        
        .back-link:hover {
            color: var(--primary-dark);
        }
        
        .back-link i {
            margin-right: 8px;
        }
        
        .table-form input,
        .table-form select {
            padding: 8px;
            border-radius: 4px;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 0;
            color: var(--gray);
        }
        
        .empty-state i {
            font-size: 60px;
            margin-bottom: 20px;
            color: var(--gray-light);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2><i class="fas fa-chalkboard-teacher"></i> Manage Tutors</h2>
        </div>
        
        <div class="content">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-plus-circle"></i> Add New Tutor</h3>
                </div>
                
                <form method="POST">
                    <div class="form-row">
                        <div class="form-col">
                            <label for="tutor_id">Tutor ID</label>
                            <input type="text" id="tutor_id" name="tutor_id" placeholder="Enter unique ID" required>
                        </div>
                        <div class="form-col">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" placeholder="Enter full name" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label for="department">Department</label>
                            <select id="department" name="department" required>
                                <option value="" disabled selected>Select Department</option>
                                <option value="CSE">CSE</option>
                                <option value="EC">EC</option>
                                <option value="ME">ME</option>
                                <option value="CE">CE</option>
                                <option value="EEE">EEE</option>
                            </select>
                        </div>
                        <div class="form-col">
                            <label for="batch">Batch</label>
                            <input type="text" id="batch" name="batch" placeholder="Enter batch" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" placeholder="Enter secure password" required>
                        </div>
                        <div class="form-col">
                            <label>&nbsp;</label>
                            <button type="submit" name="add_tutor" class="btn btn-primary btn-wide">
                                <i class="fas fa-user-plus"></i> Add Tutor
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> Registered Tutors</h3>
                </div>
                
                <div class="table-responsive">
                    <?php if (mysqli_num_rows($tutors_result) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Tutor ID</th>
                                <th>Name</th>
                                <th>Department</th>
                                <th>Batch</th>
                                <th>Password</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($tutors_result)): ?>
                            <tr>
                                <form method="POST" class="table-form">
                                    <td><input type="text" name="new_tutor_id" value="<?php echo $row['tutor_id']; ?>" required></td>
                                    <td><input type="text" name="name" value="<?php echo $row['name']; ?>" required></td>
                                    <td>
                                        <select name="department" required>
                                            <option value="CSE" <?php if($row['department'] == 'CSE') echo 'selected'; ?>>CSE</option>
                                            <option value="EC" <?php if($row['department'] == 'EC') echo 'selected'; ?>>EC</option>
                                            <option value="ME" <?php if($row['department'] == 'ME') echo 'selected'; ?>>ME</option>
                                            <option value="CE" <?php if($row['department'] == 'CE') echo 'selected'; ?>>CE</option>
                                            <option value="EEE" <?php if($row['department'] == 'EEE') echo 'selected'; ?>>EEE</option>
                                        </select>
                                    </td>
                                    <td><input type="text" name="batch" value="<?php echo $row['batch']; ?>" required></td>
                                    <td><input type="password" name="password" value="<?php echo $row['password']; ?>" required></td>
                                    <td class="actions">
                                        <input type="hidden" name="tutor_id" value="<?php echo $row['tutor_id']; ?>">
                                        <button type="submit" name="update_tutor" class="btn btn-success action-btn">
                                            <i class="fas fa-edit"></i> Update
                                        </button>
                                        <button type="submit" name="delete_tutor" class="btn btn-danger action-btn">
                                            <i class="fas fa-trash-alt"></i> Delete
                                        </button>
                                    </td>
                                </form>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-user-slash"></i>
                        <p>No tutors found. Add your first tutor using the form above.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <a href="staff-dashboard.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Return to Staff Dashboard
            </a>
        </div>
    </div>
</body>
</html>