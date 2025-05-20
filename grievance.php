<?php
include('connect.php');

if (isset($_GET['message'])) {
    if ($_GET['message'] == 'success') {
        echo "<div style='color: green; padding: 10px; background: #e6ffe6; border: 1px solid #b3ffb3; margin-bottom: 15px;'>
                ✅ Your message has been sent successfully!
              </div>";
    } elseif ($_GET['message'] == 'error') {
        echo "<div style='color: red; padding: 10px; background: #ffe6e6; border: 1px solid #ffb3b3; margin-bottom: 15px;'>
                ❌ Error sending the message. Please try again.
              </div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Grievance - Ballot Box</title>
    <link rel="stylesheet" href="reg.css">
    <link rel="stylesheet" href="button.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300..700&display=swap');
        body {
            font-family: "Space Grotesk", sans-serif;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        form {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        label {
            font-weight: bold;
        }
        input[type="text"], select, textarea {
            width: 100%;
            padding: 10px;
            margin: 8px 0;
            display: inline-block;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .button-group {
            margin-top: 20px;
            text-align: center;
        }
        button[type="submit"] {
            background-color: #0d6efd;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button[type="reset"] {
            padding: 10px 20px;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-left: 10px;
        }
        .back-link {
            display: block;
            margin: 20px 0;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-link">← Back to Home</a>
        
        <h2>Submit Grievance/Issue</h2>
        
        <form action="process_issue.php" method="POST" onsubmit="disableSubmitButton(this)">
            <div>
                <label for="recipient"><strong>To:</strong></label>
                <select name="recipient" required>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div>
                <label for="student_id"><strong>Student Id:</strong></label>
                <input type="text" name="student_id" required>
            </div>
            
            <div>
                <label for="subject"><strong>Subject:</strong></label>
                <input type="text" name="subject" required>
            </div>
            
            <div>
                <label for="issue"><strong>Message:</strong></label>
                <textarea name="issue" rows="5" required></textarea>
            </div>
            
            <div class="button-group">
                <button type="submit" id="submitButton">Send</button>
                <button type="reset">Cancel</button>
            </div>
            
        </form>
    </div>
    

    <script>
        function disableSubmitButton(form) {
            const submitButton = document.getElementById('submitButton');
            submitButton.disabled = true;
            submitButton.innerText = 'Sending...';
            form.submit();
        }
    </script>
   
</body>
</html>