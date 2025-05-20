<?php
session_start();
include 'connect.php';

// Check if results are published
$statusQuery = "SELECT status FROM election_status WHERE status = 'published' LIMIT 1";
$statusResult = $conn->query($statusQuery);
$resultsPublished = $statusResult ? $statusResult->num_rows > 0 : false;

// Fetch winners only if results are published
$winners = [];
if ($resultsPublished) {
    $winnersQuery = "SELECT sc.position_id, p.position_name, sc.student_id, sc.name, sc.photo, sc.department, 
                    COUNT(v.id) as vote_count
                    FROM selected_candidate sc
                    LEFT JOIN votes v ON sc.student_id = v.candidate_student_id AND sc.position_id = v.position_id
                    JOIN positions p ON sc.position_id = p.id
                    WHERE sc.status = 'approved'
                    GROUP BY sc.position_id, sc.student_id, sc.name, sc.photo, sc.department
                    HAVING vote_count = (
                        SELECT COUNT(v2.id)
                        FROM selected_candidate sc2
                        LEFT JOIN votes v2 ON sc2.student_id = v2.candidate_student_id AND sc2.position_id = v2.position_id
                        WHERE sc2.position_id = sc.position_id AND sc2.status = 'approved'
                        GROUP BY sc2.student_id
                        ORDER BY COUNT(v2.id) DESC
                        LIMIT 1
                    )
                    ORDER BY p.position_name";

    $winnersResult = $conn->query($winnersQuery);
    if ($winnersResult) {
        while ($row = $winnersResult->fetch_assoc()) {
            $winners[] = $row;
        }
    } else {
        error_log("Error fetching winners: " . $conn->error);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The New Union - Election Winners</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@700&display=swap" rel="stylesheet">
    <style>
        body { margin: 0; padding: 0; background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); min-height: 100vh; font-family: 'Roboto', sans-serif; display: flex; justify-content: center; align-items: center; }
        .poster { background: white; width: 900px; padding: 40px; border-radius: 20px; box-shadow: 0 0 20px rgba(0,0,0,0.3); position: relative; overflow: hidden; }
        .header { text-align: center; margin-bottom: 40px; color: #2c3e50; }
        .header h1 { font-size: 3.5rem; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 2px; }
        .header .subtitle { font-size: 1.5rem; color: #666; }
        .winners-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; }
        .winner-card { background: #f8f9fa; border-radius: 10px; padding: 20px; text-align: center; transition: transform 0.3s; }
        .winner-card:hover { transform: translateY(-5px); }
        .winner-photo { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; margin: 0 auto 15px; border: 4px solid #3498db; }
        .winner-name { font-size: 1.4rem; color: #2c3e50; margin-bottom: 5px; }
        .winner-position { font-size: 1.1rem; color: #3498db; margin-bottom: 5px; font-weight: 700; }
        .winner-votes { font-size: 0.9rem; color: #666; }
        .not-published { text-align: center; padding: 50px; color: #2c3e50; }
        .not-published h2 { font-size: 2rem; margin-bottom: 20px; }
        .not-published p { font-size: 1.2rem; color: #666; }
        .confetti { position: absolute; width: 100%; height: 100%; top: 0; left: 0; pointer-events: none; }
        @media (max-width: 768px) {
            .poster { width: 90%; padding: 20px; }
            .header h1 { font-size: 2.5rem; }
            .winner-photo { width: 120px; height: 120px; }
        }
    </style>
</head>
<body>
    <div class="poster">
        <div class="header">
            <h1>The New Union</h1>
            <div class="subtitle">Official Election Results <?php echo date('Y'); ?></div>
        </div>

        <?php if ($resultsPublished && !empty($winners)): ?>
            <div class="winners-grid">
                <?php foreach ($winners as $winner): ?>
                    <div class="winner-card">
                        <img src="uploads/<?php echo htmlspecialchars($winner['photo']); ?>" 
                             alt="<?php echo htmlspecialchars($winner['name']); ?>" 
                             class="winner-photo">
                        <div class="winner-name"><?php echo htmlspecialchars($winner['name']); ?></div>
                        <div class="winner-position"><?php echo htmlspecialchars($winner['position_name']); ?></div>
                        <div class="winner-votes"><?php echo $winner['vote_count']; ?> Votes</div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php elseif ($resultsPublished && empty($winners)): ?>
            <div class="not-published">
                <h2>No Winners Determined</h2>
                <p>No candidates received votes or results could not be calculated for the <?php echo date('Y'); ?> election.</p>
            </div>
        <?php else: ?>
            <div class="not-published">
                <h2>Election Results Not Yet Published</h2>
                <p>Please check back later for the official results of the <?php echo date('Y'); ?> election.</p>
            </div>
        <?php endif; ?>

        <?php if ($resultsPublished && !empty($winners)): ?>
            <canvas class="confetti" id="confettiCanvas"></canvas>
        <?php endif; ?>
    </div>

    <?php if ($resultsPublished && !empty($winners)): ?>
    <script>
        const canvas = document.getElementById('confettiCanvas');
        const ctx = canvas.getContext('2d');
        canvas.width = canvas.parentElement.offsetWidth;
        canvas.height = canvas.parentElement.offsetHeight;

        const confetti = [];
        const colors = ['#3498db', '#2ecc71', '#e74c3c', '#f1c40f', '#9b59b6'];

        function createConfetti() {
            return {
                x: Math.random() * canvas.width,
                y: Math.random() * canvas.height - canvas.height,
                size: Math.random() * 5 + 2,
                speed: Math.random() * 3 + 2,
                color: colors[Math.floor(Math.random() * colors.length)],
            };
        }

        for (let i = 0; i < 100; i++) {
            confetti.push(createConfetti());
        }

        function animate() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            confetti.forEach((c, i) => {
                ctx.fillStyle = c.color;
                ctx.beginPath();
                ctx.arc(c.x, c.y, c.size, 0, Math.PI * 2);
                ctx.fill();

                c.y += c.speed;
                if (c.y > canvas.height) {
                    confetti[i] = createConfetti();
                }
            });

            requestAnimationFrame(animate);
        }

        animate();
    </script>
    <?php endif; ?>
</body>
</html>