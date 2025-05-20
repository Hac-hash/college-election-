<?php
session_start();
include 'connect.php';

// Initialize $results as an empty array
$results = [];

// Fetch positions data
$positionsQuery = "SELECT id, position_name FROM positions ORDER BY position_name";
$positionsResult = $conn->query($positionsQuery);

if (!$positionsResult) {
    die("Error fetching positions: " . $conn->error);
}

$positions = [];
while ($row = $positionsResult->fetch_assoc()) {
    $positions[$row['id']] = $row['position_name'];
}

if (empty($positions)) {
    error_log("No positions found in the database.");
}

// Fetch total votes per position
$totalVotesQuery = "SELECT position_id, COUNT(id) AS total FROM votes GROUP BY position_id";
$totalVotesResult = $conn->query($totalVotesQuery);

if (!$totalVotesResult) {
    die("Error fetching total votes: " . $conn->error);
}

$totalVotes = [];
while ($row = $totalVotesResult->fetch_assoc()) {
    $totalVotes[$row['position_id']] = $row['total'];
}

// Fetch election timeline and status
$currentTime = date('Y-m-d H:i:s');
$electionQuery = "SELECT start_date, end_date, status FROM election_timeline LIMIT 1";
$electionResult = $conn->query($electionQuery);

$electionStatus = 'not_started'; // Default status
$electionOngoing = false;
$electionEnded = false;

if ($electionResult && $electionResult->num_rows > 0) {
    $election = $electionResult->fetch_assoc();
    $startTime = $election['start_date'];
    $endTime = $election['end_date'];
    $electionStatus = $election['status'];

    $electionOngoing = ($electionStatus === 'ongoing');
    $electionEnded = ($electionStatus === 'ended');
} else {
    error_log("No election timeline found in the database.");
}

// Fetch election results
foreach ($positions as $positionId => $positionName) {
    $query = "SELECT sc.position_id, sc.student_id, sc.name, sc.photo, sc.department, 
              COUNT(v.id) as vote_count
              FROM selected_candidate sc
              LEFT JOIN votes v ON sc.student_id = v.candidate_student_id AND sc.position_id = v.position_id
              WHERE sc.position_id = ? AND sc.status = 'approved'
              GROUP BY sc.position_id, sc.student_id, sc.name, sc.photo, sc.department
              ORDER BY vote_count DESC";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        error_log("Error preparing statement for position $positionId: " . $conn->error);
        continue;
    }

    $stmt->bind_param("i", $positionId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $positionCandidates = [];
    while ($row = $result->fetch_assoc()) {
        $totalPositionVotes = isset($totalVotes[$row['position_id']]) ? $totalVotes[$row['position_id']] : 0;
        $row['percentage'] = ($totalPositionVotes > 0) ? round(($row['vote_count'] / $totalPositionVotes) * 100, 1) : 0;
        $positionCandidates[] = $row;
    }
    
    if (!empty($positionCandidates)) {
        $results[$positionName] = $positionCandidates;
    }
    $stmt->close();
}

// Voter statistics
$totalStudentsQuery = "SELECT COUNT(student_id) as total FROM voter_reg WHERE status = 'approved'";
$totalStudentsResult = $conn->query($totalStudentsQuery);
$totalStudents = $totalStudentsResult ? $totalStudentsResult->fetch_assoc()['total'] : 0;

$votedStudentsQuery = "SELECT COUNT(DISTINCT student_id) as total FROM votes";
$votedStudentsResult = $conn->query($votedStudentsQuery);
$votedStudents = $votedStudentsResult ? $votedStudentsResult->fetch_assoc()['total'] : 0;

$participationRate = ($totalStudents > 0) ? round(($votedStudents / $totalStudents) * 100, 1) : 0;

// Check if results are published
$statusQuery = "SELECT status FROM election_status WHERE status = 'published' LIMIT 1";
$statusResult = $conn->query($statusQuery);
$isPublished = $statusResult ? $statusResult->num_rows > 0 : false;

// Determine if the Publish button should be disabled
$disablePublishButton = $isPublished || $electionOngoing || !$electionEnded;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Results Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #4a90e2;
            --secondary-color: #1a202c;
            --success-color: #38a169;
            --card-bg: #ffffff;
            --text-color: #2d3748;
            --muted-text: #718096;
            --shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
            --border-radius: 16px;
            --gradient-bg: linear-gradient(135deg, #e6fffa 0%, #e6f0fa 100%);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: var(--gradient-bg); color: var(--text-color); line-height: 1.6; padding: 40px; min-height: 100vh; }
        .container { max-width: 1400px; margin: 0 auto; }
        header { background: linear-gradient(90deg, var(--secondary-color), #2d3748); color: white; padding: 2rem; border-radius: var(--border-radius); box-shadow: var(--shadow); margin-bottom: 2.5rem; display: flex; justify-content: space-between; align-items: center; }
        .header-left { text-align: left; }
        h1 { font-size: 2.5rem; font-weight: 700; letter-spacing: -0.5px; }
        .subtitle { font-size: 1.1rem; font-weight: 300; opacity: 0.85; }
        .header-right { display: flex; align-items: center; gap: 1.5rem; }
        .stats-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; margin: 2rem 0; }
        .stat-card { background: var(--card-bg); padding: 1.5rem; border-radius: 12px; box-shadow: var(--shadow); text-align: center; transition: transform 0.3s ease, box-shadow 0.3s ease; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15); }
        .stat-value { font-size: 2rem; font-weight: 700; color: var(--primary-color); margin-bottom: 0.5rem; }
        .stat-label { font-size: 1rem; color: var(--muted-text); font-weight: 400; }
        .controls-container { display: flex; align-items: center; gap: 1.5rem; flex-wrap: wrap; margin: 2rem 0; justify-content: center; }
        .button { background: var(--primary-color); color: white; padding: 0.9rem 1.8rem; border: none; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 0.6rem; font-weight: 600; font-size: 1rem; text-transform: uppercase; letter-spacing: 0.5px; transition: all 0.3s ease; box-shadow: 0 4px 10px rgba(74, 144, 226, 0.3); }
        .button:hover { background: #357abd; transform: translateY(-2px); box-shadow: 0 6px 14px rgba(74, 144, 226, 0.4); }
        .button.small { padding: 0.6rem 1.2rem; font-size: 0.9rem; }
        .button.success { background: var(--success-color); box-shadow: 0 4px 10px rgba(56, 161, 105, 0.3); }
        .button.success:hover { background: #2f855a; box-shadow: 0 6px 14px rgba(56, 161, 105, 0.4); }
        .button:disabled { opacity: 0.6; cursor: not-allowed; transform: none; box-shadow: none; }
        .position-card { background: var(--card-bg); border-radius: var(--border-radius); box-shadow: var(--shadow); margin-bottom: 2rem; overflow: hidden; transition: transform 0.3s ease, box-shadow 0.3s ease; }
        .position-card:hover { transform: translateY(-6px); box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15); }
        .position-header { background: linear-gradient(90deg, var(--primary-color), #63b3ed); color: white; padding: 1rem 1.5rem; font-size: 1.25rem; font-weight: 600; display: flex; justify-content: space-between; align-items: center; border-bottom: 4px solid rgba(255, 255, 255, 0.2); }
        .position-content { padding: 1.5rem; }
        .position-results { display: grid; grid-template-columns: 1fr 350px; gap: 1.5rem; }
        .candidate-card { display: grid; grid-template-columns: 100px 1fr 140px; background: #f9fafb; border-radius: 12px; margin-bottom: 1rem; overflow: hidden; border: 1px solid #e2e8f0; transition: all 0.3s ease; }
        .candidate-card:hover { background: #edf2f7; transform: translateX(6px); box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); }
        .candidate-photo { width: 100px; height: 100px; object-fit: cover; border-right: 1px solid #e2e8f0; }
        .candidate-info { padding: 1rem; }
        .candidate-name { font-size: 1.2rem; font-weight: 600; display: flex; align-items: center; gap: 0.6rem; }
        .winner-indicator { background: var(--success-color); padding: 0.3rem 0.8rem; border-radius: 12px; font-size: 0.85rem; font-weight: 500; color: white; display: flex; align-items: center; gap: 0.3rem; }
        .candidate-department { font-size: 0.95rem; color: var(--muted-text); margin-top: 0.3rem; }
        .vote-stats { padding: 1rem; background: white; display: flex; flex-direction: column; justify-content: center; align-items: center; border-left: 1px solid #e2e8f0; }
        .vote-count { font-weight: 700; font-size: 1.3rem; color: var(--text-color); }
        .percentage { color: var(--muted-text); font-size: 0.9rem; margin: 0.3rem 0; }
        .progress-bar { height: 8px; background: #e2e8f0; border-radius: 4px; width: 100%; overflow: hidden; }
        .progress-fill { height: 100%; background: var(--primary-color); border-radius: 4px; transition: width 0.5s ease; }
        .vote-stats.winner .progress-fill { background: var(--success-color); }
        .chart-container { background: #f9fafb; border-radius: 12px; padding: 1rem; border: 1px solid #e2e8f0; width: 100%; height: 200px; box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.05); }
        .chart-title { font-weight: 600; font-size: 1rem; margin-bottom: 0.75rem; text-align: center; color: var(--text-color); }
        footer { text-align: center; padding: 1.5rem; color: var(--muted-text); margin-top: 2rem; font-size: 0.9rem; background: rgba(255, 255, 255, 0.8); border-radius: var(--border-radius); box-shadow: var(--shadow); }
        .loading-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.6); display: flex; justify-content: center; align-items: center; z-index: 1000; opacity: 0; visibility: hidden; transition: all 0.3s ease; }
        .loading-overlay.active { opacity: 1; visibility: visible; }
        .loading-spinner { width: 50px; height: 50px; border: 5px solid rgba(255, 255, 255, 0.3); border-top-color: var(--primary-color); border-radius: 50%; animation: spinner 0.8s linear infinite; }
        @keyframes spinner { to { transform: rotate(360deg); } }
        @media (max-width: 1024px) { .position-results { grid-template-columns: 1fr; } .chart-container { height: 180px; margin-top: 1rem; } }
        @media (max-width: 768px) {
            header { flex-direction: column; padding: 1.5rem; text-align: center; }
            h1 { font-size: 2rem; }
            .header-right { margin-top: 1rem; }
            .stats-container { grid-template-columns: 1fr 1fr; }
            .candidate-card { grid-template-columns: 80px 1fr 120px; }
            .candidate-photo { width: 80px; height: 80px; }
        }
        @media (max-width: 480px) {
            .stats-container { grid-template-columns: 1fr; }
            .candidate-card { grid-template-columns: 60px 1fr 100px; }
            .candidate-photo { width: 60px; height: 60px; }
            .button { padding: 0.7rem 1.2rem; font-size: 0.9rem; }
        }
    </style>
</head>
<body>
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <div class="container">
        <header>
            <div class="header-left">
                <h1>Election Results Dashboard</h1>
                <div class="subtitle">
                    Real-time Voting Insights
                    <span id="electionStatus">
                        <?php if ($electionStatus === 'ongoing'): ?>
                            <span style="color: #ffcc00;">(Election Ongoing)</span>
                        <?php elseif ($electionStatus === 'ended'): ?>
                            <span style="color: #38a169;">(Election Ended)</span>
                        <?php else: ?>
                            <span style="color: #ff4444;">(Election Not Started)</span>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
            <div class="header-right">
                <a href="staff-dashboard.php" class="button small nav-button">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </div>
        </header>
        
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-value"><?php echo count($results); ?></div>
                <div class="stat-label">Active Positions</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $totalStudents; ?></div>
                <div class="stat-label">Eligible Voters</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $votedStudents; ?></div>
                <div class="stat-label">Voted Students</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $participationRate; ?>%</div>
                <div class="stat-label">Participation Rate</div>
            </div>
        </div>
        
        <div class="controls-container">
            <button class="button success" id="publishButton" onclick="publishResults()" 
                    <?php echo $disablePublishButton ? 'disabled' : ''; ?>>
                <i class="fas fa-bullhorn"></i> 
                <span id="publishButtonText">
                    <?php 
                        if ($isPublished) {
                            echo 'Results Published';
                        } elseif ($electionOngoing) {
                            echo 'Election Ongoing';
                        } elseif (!$electionEnded) {
                            echo 'Election Not Started';
                        } else {
                            echo 'Publish Results';
                        }
                    ?>
                </span>
            </button>
            <button class="button" onclick="refreshResults()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
        
        <?php if (empty($results)): ?>
            <div class="position-card">
                <div class="position-content" style="text-align: center; padding: 3rem;">
                    <i class="fas fa-info-circle" style="font-size: 3rem; color: #a0aec0; margin-bottom: 1rem;"></i>
                    <h3>No Election Results Available</h3>
                    <p style="color: var(--muted-text); font-size: 1.1rem;">No approved candidates or votes recorded yet.</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($results as $position => $candidates): 
                $maxVotes = !empty($candidates) ? $candidates[0]['vote_count'] : 0;
                $totalPositionVotes = isset($totalVotes[$candidates[0]['position_id']]) ? $totalVotes[$candidates[0]['position_id']] : 0;
                $positionId = $candidates[0]['position_id'];
            ?>
            <div class="position-card" data-position="<?php echo htmlspecialchars($position); ?>">
                <div class="position-header">
                    <span><?php echo htmlspecialchars($position); ?></span>
                    <span class="total-votes"><?php echo $totalPositionVotes; ?> Total Votes</span>
                </div>
                <div class="position-content">
                    <div class="position-results">
                        <div>
                            <?php foreach ($candidates as $index => $candidate): 
                                $isWinner = ($candidate['vote_count'] == $maxVotes && $candidate['vote_count'] > 0);
                            ?>
                            <div class="candidate-card" data-candidate-id="<?php echo $candidate['student_id']; ?>">
                                <img src="uploads/<?php echo htmlspecialchars($candidate['photo']); ?>" 
                                     alt="<?php echo htmlspecialchars($candidate['name']); ?>" 
                                     class="candidate-photo">
                                <div class="candidate-info">
                                    <div class="candidate-name">
                                        <?php echo htmlspecialchars($candidate['name']); ?>
                                        <?php if ($isWinner): ?>
                                        <span class="winner-indicator">
                                            <i class="fas fa-trophy"></i> Leading
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="candidate-department"><?php echo htmlspecialchars($candidate['department']); ?></div>
                                </div>
                                <div class="vote-stats <?php echo $isWinner ? 'winner' : ''; ?>">
                                    <div class="vote-count"><?php echo $candidate['vote_count']; ?></div>
                                    <div class="percentage"><?php echo $candidate['percentage']; ?>%</div>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo $candidate['percentage']; ?>%"></div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="chart-container">
                            <div class="chart-title">Vote Distribution</div>
                            <canvas id="chart-<?php echo $positionId; ?>"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <footer>
            <p>© <?php echo date('Y'); ?> Student Election System. All rights reserved.</p>
        </footer>
    </div>

    <script>
        const charts = {};
        document.addEventListener('DOMContentLoaded', function() {
            <?php foreach ($results as $position => $candidates): 
                $positionId = $candidates[0]['position_id'];
                $maxVotes = !empty($candidates) ? $candidates[0]['vote_count'] : 0;
            ?>
                var ctx = document.getElementById('chart-<?php echo $positionId; ?>').getContext('2d');
                charts['<?php echo $positionId; ?>'] = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: [<?php echo implode(',', array_map(function($c) { return "'".addslashes($c['name'])."'"; }, $candidates)); ?>],
                        datasets: [{
                            label: 'Votes',
                            data: [<?php echo implode(',', array_column($candidates, 'vote_count')); ?>],
                            backgroundColor: [<?php 
                                echo implode(',', array_map(function($c) use ($maxVotes) {
                                    return ($c['vote_count'] == $maxVotes && $c['vote_count'] > 0) 
                                        ? "'#38a169'" 
                                        : "'#4a90e2'";
                                }, $candidates));
                            ?>],
                            borderWidth: 0,
                            borderRadius: 4,
                            barThickness: 25
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false }, tooltip: { backgroundColor: 'rgba(45, 55, 72, 0.8)', borderRadius: 6 } },
                        scales: { y: { beginAtZero: true, ticks: { precision: 0, color: '#718096' }, grid: { color: '#edf2f7' }, max: <?php echo max(1, max(array_column($candidates, 'vote_count')) + 1); ?> }, x: { grid: { display: false }, ticks: { color: '#718096' } } },
                        animation: false,
                        transitions: { active: { animation: { duration: 0 } }, resize: { animation: { duration: 0 } }, show: { animations: { x: { duration: 0 }, y: { duration: 0 } } }, hide: { animations: { x: { duration: 0 }, y: { duration: 0 } } } },
                        elements: { bar: { inflateAmount: 0 } },
                        layout: { padding: { left: 10, right: 10, top: 0, bottom: 0 } }
                    }
                });
            <?php endforeach; ?>

            const refreshIntervalSeconds = 120;

            function refreshResults() {
                document.getElementById('loadingOverlay').classList.add('active');
                fetch('get-election-results.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) { console.error('Error:', data.error); document.getElementById('loadingOverlay').classList.remove('active'); return; }
                        updateStatistics(data.statistics);
                        updateResults(data.results);
                        document.getElementById('loadingOverlay').classList.remove('active');
                    })
                    .catch(error => { console.error('Error:', error); document.getElementById('loadingOverlay').classList.remove('active'); });
            }

            function updateStatistics(stats) {
                document.querySelector('.stat-card:nth-child(1) .stat-value').textContent = stats.activePositions;
                document.querySelector('.stat-card:nth-child(2) .stat-value').textContent = stats.eligibleVoters;
                document.querySelector('.stat-card:nth-child(3) .stat-value').textContent = stats.votedStudents;
                document.querySelector('.stat-card:nth-child(4) .stat-value').textContent = stats.participationRate + '%';
            }

            function updateResults(results) {
                Object.keys(results).forEach(position => {
                    const positionCard = document.querySelector(`.position-card[data-position="${position}"]`);
                    if (!positionCard) return;

                    const totalVotesElement = positionCard.querySelector('.total-votes');
                    const candidates = results[position];
                    const maxVotes = candidates.length > 0 ? Math.max(...candidates.map(c => c.vote_count)) : 0;
                    const totalVotes = candidates.reduce((sum, c) => sum + c.vote_count, 0);

                    totalVotesElement.textContent = `${totalVotes} total votes`;

                    candidates.forEach(candidate => {
                        const candidateCard = positionCard.querySelector(`.candidate-card[data-candidate-id="${candidate.student_id}"]`);
                        if (!candidateCard) return;

                        const voteCountElement = candidateCard.querySelector('.vote-count');
                        const percentageElement = candidateCard.querySelector('.percentage');
                        const progressFillElement = candidateCard.querySelector('.progress-fill');
                        const voteStatsElement = candidateCard.querySelector('.vote-stats');
                        const winnerIndicator = candidateCard.querySelector('.winner-indicator');

                        voteCountElement.textContent = candidate.vote_count;
                        percentageElement.textContent = candidate.percentage + '%';
                        progressFillElement.style.width = candidate.percentage + '%';

                        if (candidate.vote_count === maxVotes && candidate.vote_count > 0) {
                            voteStatsElement.classList.add('winner');
                            if (!winnerIndicator) {
                                const indicator = document.createElement('span');
                                indicator.className = 'winner-indicator';
                                indicator.innerHTML = '<i class="fas fa-trophy"></i> Leading';
                                candidateCard.querySelector('.candidate-name').appendChild(indicator);
                            }
                        } else {
                            voteStatsElement.classList.remove('winner');
                            if (winnerIndicator) winnerIndicator.remove();
                        }
                    });

                    const positionId = candidates[0].position_id;
                    if (charts[positionId]) {
                        const chart = charts[positionId];
                        chart.data.datasets[0].data = candidates.map(c => c.vote_count);
                        chart.data.datasets[0].backgroundColor = candidates.map(c => c.vote_count === maxVotes && c.vote_count > 0 ? '#38a169' : '#4a90e2');
                        chart.options.scales.y.max = Math.max(1, maxVotes + 1);
                        chart.update('none');
                    }
                });
            }

            function checkElectionStatus() {
                fetch('check-election-status.php')
                    .then(response => response.json())
                    .then(data => {
                        const publishButton = document.getElementById('publishButton');
                        const publishButtonText = document.getElementById('publishButtonText');
                        const electionStatusSpan = document.getElementById('electionStatus');

                        if (data.results_published) {
                            publishButton.disabled = true;
                            publishButtonText.textContent = 'Results Published';
                        } else if (data.election_status === 'ongoing') {
                            publishButton.disabled = true;
                            publishButtonText.textContent = 'Election Ongoing';
                        } else if (data.election_status === 'not_started') {
                            publishButton.disabled = true;
                            publishButtonText.textContent = 'Election Not Started';
                        } else if (data.election_status === 'ended') {
                            publishButton.disabled = false;
                            publishButtonText.textContent = 'Publish Results';
                        }

                        if (data.election_status === 'ongoing') {
                            electionStatusSpan.innerHTML = '<span style="color: #ffcc00;">(Election Ongoing)</span>';
                        } else if (data.election_status === 'ended') {
                            electionStatusSpan.innerHTML = '<span style="color: #38a169;">(Election Ended)</span>';
                        } else {
                            electionStatusSpan.innerHTML = '<span style="color: #ff4444;">(Election Not Started)</span>';
                        }
                    })
                    .catch(error => console.error('Error checking election status:', error));
            }

            const statusCheckIntervalSeconds = 30;
            setInterval(checkElectionStatus, statusCheckIntervalSeconds * 1000);
            checkElectionStatus();
            setInterval(refreshResults, refreshIntervalSeconds * 1000);
            refreshResults();
        });

        function publishResults() {
            if (confirm('Are you sure you want to publish the election results? This action cannot be undone.')) {
                const button = document.getElementById('publishButton');
                button.disabled = true;
                document.getElementById('loadingOverlay').classList.add('active');

                fetch('publish-results.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = 'winners-poster.php';
                    } else {
                        alert('Error publishing results: ' + data.message);
                        button.disabled = false;
                        document.getElementById('loadingOverlay').classList.remove('active');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while publishing results');
                    button.disabled = false;
                    document.getElementById('loadingOverlay').classList.remove('active');
                });
            }
        }
    </script>
</body>
</html>