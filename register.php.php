<?php
session_start();
include 'connect.php'; // Ensure this connects to your database

// Fetch available positions
$positionsQuery = "SELECT * FROM positions";
$positionsResult = $conn->query($positionsQuery);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = $_POST['student_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $position_id = $_POST['position_id'];
    $department = $_POST['department'];
    $phone = $_POST['phone'];
    $bio = $_POST['bio'];
    $is_voter = isset($_POST['is_voter']) ? 1 : 0;
    $voter_id = $is_voter ? $_POST['voter_id'] : NULL;

    // ✅ Server-Side Validation for Student ID (max 10 characters)
    if (strlen($student_id) > 10) {
        echo "<script>alert('Error: Student ID must be 10 characters or less.'); window.location.href='register.php';</script>";
        exit();
    }
    if (!preg_match('/^[A-Za-z0-9]+$/', $student_id)) {
        echo "<script>alert('Error: Student ID can only contain letters and numbers.'); window.location.href='register.php';</script>";
        exit();
    }

    // ✅ Server-Side Validation for Phone Number (exactly 10 digits)
    if (!preg_match('/^[0-9]{10}$/', $phone)) {
        echo "<script>alert('Error: Phone number must be exactly 10 digits.'); window.location.href='register.php';</script>";
        exit();
    }

    // ✅ Step 1: Check if the voter is approved
    if ($is_voter && !empty($voter_id)) {
        $checkVoterQuery = "SELECT student_id, status FROM voter_reg WHERE student_id = ?";
        $stmt = $conn->prepare($checkVoterQuery);
        $stmt->bind_param("s", $student_id);
        $stmt->execute();
        $voterResult = $stmt->get_result();
        $voter = $voterResult->fetch_assoc();

        if (!$voter || $voter['status'] != 'approved') {
            echo "<script>alert('Error: You must be an approved voter to register as a candidate.'); window.location.href='register.php';</script>";
            exit();
        }
    } else {
        echo "<script>alert('Error: Please link to an approved voter to proceed.'); window.location.href='register.php';</script>";
        exit();
    }

    // ✅ Step 2: Validate Student ID (check if already exists in candidates)
    $checkStudentQuery = "SELECT student_id FROM candidates WHERE student_id = ?";
    $stmt = $conn->prepare($checkStudentQuery);
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<script>alert('This Student ID is already registered as a candidate.'); window.location.href='register.php';</script>";
        exit();
    }

    // ✅ Step 3: Handle file uploads securely
    $target_dir = "uploads/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // Validate photo file
    $photo_filename = time() . '_' . basename($_FILES["photo"]["name"]);
    $target_photo = $target_dir . $photo_filename;
    $photoFileType = strtolower(pathinfo($target_photo, PATHINFO_EXTENSION));
    if (!in_array($photoFileType, ['jpg', 'jpeg', 'png'])) {
        echo "<script>alert('Invalid file type for photo. Please upload JPG, JPEG, or PNG.'); window.location.href='register.php';</script>";
        exit();
    }

    // Validate eligibility document file
    $eligibility_document = time() . '_' . basename($_FILES["pdf"]["name"]);
    $target_pdf = $target_dir . $eligibility_document;
    $pdfFileType = strtolower(pathinfo($target_pdf, PATHINFO_EXTENSION));
    if ($pdfFileType != 'pdf') {
        echo "<script>alert('Invalid file type for document. Please upload a PDF file.'); window.location.href='register.php';</script>";
        exit();
    }

    // ✅ Step 4: Insert candidate data with 'pending' status
    $stmt = $conn->prepare("INSERT INTO candidates (voter_id, student_id, name, email, position_id, department, phone, bio, photo, eligibility_document, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param("isssisisss", $voter_id, $student_id, $name, $email, $position_id, $department, $phone, $bio, $photo_filename, $eligibility_document);

    if ($stmt->execute()) {
        // Move uploaded files to target directory
        if (move_uploaded_file($_FILES["photo"]["tmp_name"], $target_photo) && 
            move_uploaded_file($_FILES["pdf"]["tmp_name"], $target_pdf)) {
            echo "<script>alert('Candidate registration successful. Awaiting approval.'); window.location.href='index.php';</script>";
        } else {
            echo "<script>alert('Error uploading files. Please try again.'); window.location.href='register.php';</script>";
        }
    } else {
        echo "<script>alert('Error: Unable to register candidate.'); window.location.href='register.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidate Registration - Ballot Box</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }
        
        .header {
            background-color: #1a1a1a;
            color: white;
            padding: 15px 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        
        .logo {
            width: 40px;
            height: 40px;
        }
        
        .header h1 {
            font-size: 24px;
            font-weight: 600;
        }
        
        .container {
            max-width: 800px;
            margin: 30px auto;
            background-color: white;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 30px;
            position: relative;
        }
        
        .form-header h2 {
            font-size: 24px;
            color: #333;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .form-header h2::after {
            content: '';
            display: block;
            width: 80px;
            height: 3px;
            background-color: #333;
            margin: 15px auto 0;
        }
        
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            flex: 1;
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            transition: all 0.3s ease;
            background-color: white;
        }
        
        input:focus, select:focus, textarea:focus {
            border-color: #666;
            outline: none;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
        }
        
        input::placeholder, textarea::placeholder {
            color: #aaa;
        }
        
        .input-icon {
            position: relative;
        }
        
        .input-icon i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
            pointer-events: none;
        }
        
        button {
            background-color: #1a1a1a;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            width: 100%;
            margin-top: 10px;
            transition: background-color 0.3s ease;
        }
        
        button:hover {
            background-color: #333;
        }
        
        .note {
            font-size: 14px;
            color: #777;
            margin-top: 5px;
        }
        
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            .container {
                padding: 20px;
                margin: 20px 10px;
            }
        }
        
        .file-input {
            position: relative;
            display: flex;
            flex-direction: column;
        }
        
        .file-input-button {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            background-color: white;
            transition: all 0.3s ease;
        }
        
        .file-input-button:hover {
            border-color: #666;
        }
        
        .file-input input[type="file"] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        
        .file-name {
            margin-top: 5px;
            font-size: 14px;
            color: #777;
        }
        
        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .button-group button {
            margin-top: 0;
        }
        
        .home-button {
            background-color: #555;
        }
        
        .home-button:hover {
            background-color: #777;
        }
    </style>
    <script>
        function toggleVoterID() {
            var section = document.getElementById("voter_section");
            section.style.display = document.getElementById("is_voter").checked ? "block" : "none";
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Display file names when files are selected
            document.getElementById('photo').addEventListener('change', function() {
                const fileName = this.files[0] ? this.files[0].name : 'No file chosen';
                document.getElementById('photo-name').textContent = fileName;
            });
            
            document.getElementById('pdf').addEventListener('change', function() {
                const fileName = this.files[0] ? this.files[0].name : 'No file chosen';
                document.getElementById('pdf-name').textContent = fileName;
            });

            // Add confirmation alert on form submission
            document.getElementById('candidateForm').addEventListener('submit', function(event) {
                event.preventDefault(); // Prevent the form from submitting immediately

                // Client-side validation for Student ID
                const studentId = document.getElementById('student_id').value;
                if (studentId.length > 10) {
                    alert('Error: Student ID must be 10 characters or less.');
                    return;
                }
                if (!/^[A-Za-z0-9]+$/.test(studentId)) {
                    alert('Error: Student ID can only contain letters and numbers.');
                    return;
                }

                // Client-side validation for Phone Number
                const phone = document.getElementById('phone').value;
                if (!/^[0-9]{10}$/.test(phone)) {
                    alert('Error: Phone number must be exactly 10 digits.');
                    return;
                }

                // Confirmation alert
                const confirmed = confirm("Are you sure you want to submit your candidate registration? Once submitted, no changes can be made.");
                if (confirmed) {
                    this.submit(); // Proceed with form submission
                }
            });
        });
    </script>
</head>
<body>
    <div class="header">
        <img src="image/logo.PNG" alt="Ballot Box Logo" class="logo">
        <h1>Ballot Box</h1>
    </div>
    
    <div class="container">
        <div class="form-header">
            <h2>
                <i class="fas fa-user-plus"></i>
                Candidate Registration
            </h2>
        </div>
        
        <form id="candidateForm" method="POST" action="register.php" enctype="multipart/form-data">
            <div class="form-row">
                <div class="form-group">
                    <label for="student_id">Student ID</label>
                    <div class="input-icon">
                        <input type="text" id="student_id" name="student_id" placeholder="Enter your student ID" maxlength="10" pattern="[A-Za-z0-9]+" required>
                        <i class="fas fa-id-card"></i>
                    </div>
                    <p class="note">Maximum 10 characters (letters and numbers only)</p>
                </div>
                
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <div class="input-icon">
                        <input type="text" id="name" name="name" placeholder="Enter your full name" required>
                        <i class="fas fa-user"></i>
                    </div>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="department">Department</label>
                    <div class="input-icon">
                        <select id="department" name="department" required>
                            <option value="">Select Department</option>
                            <option value="CSE">Computer Science & Engineering</option>
                            <option value="EEE">Electrical & Electronics Engineering</option>
                            <option value="EC">Electronics & Communication Engineering</option>
                            <option value="ME">Mechanical Engineering</option>
                            <option value="CE">Civil Engineering</option>
                        </select>
                        <i class="fas fa-building"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="position_id">Position</label>
                    <div class="input-icon">
                        <select id="position_id" name="position_id" required>
                            <option value="">Select Position</option>
                            <?php while ($row = $positionsResult->fetch_assoc()) { ?>
                                <option value="<?php echo $row['id']; ?>"> <?php echo $row['position_name']; ?> </option>
                            <?php } ?>
                        </select>
                        <i class="fas fa-briefcase"></i>
                    </div>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-icon">
                        <input type="email" id="email" name="email" placeholder="Enter your email address" required>
                        <i class="fas fa-envelope"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <div class="input-icon">
                        <input type="tel" id="phone" name="phone" placeholder="Enter 10-digit phone number" maxlength="10" pattern="[0-9]{10}" required>
                        <i class="fas fa-phone"></i>
                    </div>
                    <p class="note">Exactly 10 digits</p>
                </div>
            </div>
            
            <div class="form-group">
                <label for="bio">Candidate Bio</label>
                <textarea id="bio" name="bio" rows="4" placeholder="Write about yourself, your qualifications, and why you're running for this position..."></textarea>
                <p class="note">Maximum 1000 characters</p>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="photo">Upload Photo</label>
                    <div class="file-input">
                        <label for="photo" class="file-input-button">
                            <i class="fas fa-camera"></i>
                            <span>Choose Photo</span>
                        </label>
                        <input type="file" id="photo" name="photo" accept="image/*" required>
                        <p id="photo-name" class="file-name">No file chosen</p>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="pdf">Eligibility Document</label>
                    <div class="file-input">
                        <label for="pdf" class="file-input-button">
                            <i class="fas fa-file-pdf"></i>
                            <span>Choose PDF</span>
                        </label>
                        <input type="file" id="pdf" name="pdf" accept="application/pdf" required>
                        <p id="pdf-name" class="file-name">No file chosen</p>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>Are you already registered as a voter?</label>
                <input type="checkbox" id="is_voter" name="is_voter" onclick="toggleVoterID()" />
            </div>

            <div class="form-group" id="voter_section" style="display:none;">
                <label for="voter_id">Voter ID (if registered)</label>
                <input type="text" id="voter_id" name="voter_id" placeholder="Enter Voter ID to link">
            </div>

            <div class="button-group">
                <button type="submit">
                    <i class="fas fa-paper-plane"></i> Submit Registration
                </button>
                <button type="button" class="home-button" onclick="window.location.href='index.php'">
                    <i class="fas fa-home"></i> Go to Home
                </button>
            </div>
        </form>
    </div>
</body>
</html>