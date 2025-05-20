<?php
session_start();
include('connect.php');

if (!isset($_SESSION['logged_in'])) {
    header("Location: staff-login.php");
    exit();
}

$query = "SELECT * FROM issues ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);

if (isset($_POST['update_status'])) {
    $issue_id = $_POST['issue_id'];
    $status = $_POST['status'];
    $admin_notes = mysqli_real_escape_string($conn, $_POST['admin_notes']);
    
    $update_query = "UPDATE issues SET status = ?, admin_notes = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ssi", $status, $admin_notes, $issue_id);
    
    $stmt->execute();
    $stmt->close();
    header("Location: admin-grievances.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Grievances - Ballot Box</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300..700&display=swap');
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "Space Grotesk", sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        .header {
            background: #ffffff;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }
        .logout-btn {
            background: #dc3545;
            color: white;
            padding: 8px 20px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 14px;
            transition: transform 0.2s, background 0.3s;
        }
        .logout-btn:hover {
            background: #c82333;
            transform: translateY(-2px);
        }
        .container {
            max-width: 1200px;
            margin: 100px auto 40px;
            padding: 0 20px;
        }
        h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 30px;
            font-weight: 600;
        }
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            justify-content: center;
        }
        .tab {
            padding: 10px 25px;
            background: white;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .tab.active, .tab:hover {
            background: #3498db;
            color: white;
            transform: translateY(-2px);
        }
        .grievance-table {
            width: 100%;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 15px;
            text-align: left;
        }
        th {
            background: #2c3e50;
            color: white;
            font-weight: 500;
        }
        tr { transition: background 0.3s; }
        tr:nth-child(even) { background: #f8f9fa; }
        tr:hover { background: #e9ecef; }
        .status-new { background: #ffeaa7; color: #856404; }
        .status-in-progress { background: #74c0fc; color: #0c5460; }
        .status-resolved { background: #b8e994; color: #155724; }
        .status-closed { background: #ced4da; color: #495057; }
        .status-span {
            padding: 5px 15px;
            border-radius: 15px;
            font-size: 14px;
            display: inline-block;
        }
        .view-btn {
            background: #3498db;
            color: white;
            padding: 8px 20px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .view-btn:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1001;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            overflow: auto; /* Allow modal to handle its own scrolling */
        }
        .modal-content {
            background: white;
            margin: 80px auto;
            padding: 30px;
            width: 90%;
            max-width: 700px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            animation: slideIn 0.3s ease-in;
            max-height: 80vh; /* Limit modal height to 80% of viewport height */
            overflow-y: auto; /* Enable vertical scrolling within modal if content overflows */
            position: relative; /* Ensure proper layering */
        }
        .close {
            float: right;
            font-size: 24px;
            color: #7f8c8d;
            cursor: pointer;
            transition: color 0.3s;
        }
        .close:hover { color: #e74c3c; }
        .form-group { margin-bottom: 20px; }
        .form-group label { color: #2c3e50; font-weight: 500; }
        select, textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            margin-top: 8px;
        }
        button[type="submit"] {
            background: #3498db;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s;
        }
        button[type="submit"]:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
        /* Disable body scroll when modal is open */
        body.modal-open {
            overflow: hidden; /* Prevent body scrolling */
            height: 100vh; /* Lock body height */
        }
    </style>
</head>
<body>
    <div class="header">
        <button class="logout-btn" onclick="window.location.href='logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</button>
    </div>
    
    <div class="container">
        <h2>Grievance Management</h2>
        
        <div class="tabs">
            <div class="tab active" onclick="filterGrievances('all')">All</div>
            <div class="tab" onclick="filterGrievances('new')">New</div>
            <div class="tab" onclick="filterGrievances('in-progress')">In Progress</div>
            <div class="tab" onclick="filterGrievances('resolved')">Resolved</div>
            <div class="tab" onclick="filterGrievances('closed')">Closed</div>
        </div>
        
        <table class="grievance-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Date</th>
                    <th>Student ID</th>
                    <th>Recipient</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr class="grievance-row status-<?php echo strtolower(str_replace(' ', '-', $row['status'] ?? 'new')); ?>">
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                            <td><?php echo ucfirst(htmlspecialchars($row['recipient'])); ?></td>
                    <td><?php echo htmlspecialchars($row['subject']); ?></td>
                    <td>
                        <span class="status-span status-<?php echo strtolower(str_replace(' ', '-', $row['status'] ?? 'new')); ?>">
                            <?php echo $row['status'] ? ucfirst($row['status']) : 'New'; ?>
                        </span>
                    </td>
                    <td>
                        <button class="view-btn" onclick="openModal(<?php echo $row['id']; ?>)">
                            <i class="fas fa-eye"></i> View
                        </button>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="7" style="text-align: center; padding: 20px;">No grievances found</td></tr>
        <?php endif; ?>
            </tbody>
        </table>
        
        <div id="grievanceModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal()">×</span>
                <h3>Grievance Details</h3>
                <div id="grievanceDetails"></div>
            </div>
        </div>
    </div>
    
    <script>
        function filterGrievances(status) {
            document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
            event.currentTarget.classList.add('active');
            document.querySelectorAll('.grievance-row').forEach(row => {
                row.style.display = (status === 'all' || row.classList.contains('status-' + status)) ? '' : 'none';
            });
        }
        
        const modal = document.getElementById('grievanceModal');
        
        function openModal(id) {
            document.body.classList.add('modal-open'); // Disable body scrolling
            fetch('get_grievance.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayGrievanceDetails(data.grievance);
                        modal.style.display = "block";
                    } else {
                        alert('Error loading grievance details');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading grievance details');
                });
        }
        
        function closeModal() {
            modal.style.display = "none";
            document.body.classList.remove('modal-open'); // Re-enable body scrolling
        }
        
        function displayGrievanceDetails(grievance) {
            const detailsContainer = document.getElementById('grievanceDetails');
            let statusClass = 'status-' + (grievance.status ? grievance.status.toLowerCase().replace(' ', '-') : 'new');
            let statusText = grievance.status ? grievance.status : 'New';
            
            detailsContainer.innerHTML = `
                <div class="form-group"><strong>ID:</strong> #${grievance.id}</div>
                <div class="form-group"><strong>Student ID:</strong> ${grievance.student_id}</div>
                <div class="form-group"><strong>Recipient:</strong> ${grievance.recipient.charAt(0).toUpperCase() + grievance.recipient.slice(1)}</div>
                <div class="form-group"><strong>Subject:</strong> ${grievance.subject}</div>
                <div class="form-group"><strong>Message:</strong><p style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-top: 8px;">${grievance.issue_text}</p></div>
                <div class="form-group"><strong>Status:</strong> <span class="status-span ${statusClass}">${statusText}</span></div>
                <div class="form-group"><strong>Submitted:</strong> ${new Date(grievance.created_at).toLocaleString()}</div>
                <hr style="margin: 25px 0; border: none; border-top: 1px solid #ecf0f1;">
                <h4>Update Status</h4>
                <form method="POST" action="admin-grievances.php">
                    <input type="hidden" name="issue_id" value="${grievance.id}">
                    <div class="form-group">
                        <label>Change Status:</label>
                        <select name="status">
                            <option value="new" ${statusText === 'New' ? 'selected' : ''}>New</option>
                            <option value="in progress" ${statusText === 'In Progress' ? 'selected' : ''}>In Progress</option>
                            <option value="resolved" ${statusText === 'Resolved' ? 'selected' : ''}>Resolved</option>
                            <option value="closed" ${statusText === 'Closed' ? 'selected' : ''}>Closed</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Admin Notes:</label>
                        <textarea name="admin_notes" rows="4">${grievance.admin_notes || ''}</textarea>
                    </div>
                    <button type="submit" name="update_status">Update Status</button>
                </form>
            `;
        }
        
        window.onclick = function(event) {
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>