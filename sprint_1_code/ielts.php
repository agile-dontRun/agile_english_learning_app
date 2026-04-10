<?php
session_start();
require_once 'db_connect.php';

// 1. Login Security Check
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$nickname = $_SESSION['nickname'] ?? 'Learner';

// Initialize parameters
$cam = isset($_GET['cam']) && is_numeric($_GET['cam']) ? (int)$_GET['cam'] : 0;
$test = isset($_GET['test']) && is_numeric($_GET['test']) ? (int)$_GET['test'] : 0;
$part_id = isset($_GET['part_id']) && is_numeric($_GET['part_id']) ? (int)$_GET['part_id'] : 0;

// Fetch Data Logic (Maintained from original)
$books = [];
$res_books = $conn->query("SELECT DISTINCT cambridge_no FROM ielts_listening_parts ORDER BY cambridge_no DESC");
if($res_books) { while($row = $res_books->fetch_assoc()) { $books[] = $row['cambridge_no']; } }

$tests = [];
if ($cam > 0) {
    $stmt = $conn->prepare("SELECT DISTINCT test_no FROM ielts_listening_parts WHERE cambridge_no = ? ORDER BY test_no ASC");
    $stmt->bind_param("i", $cam);
    $stmt->execute();
    $res_tests = $stmt->get_result();
    while($row = $res_tests->fetch_assoc()) { $tests[] = $row['test_no']; }
}

$parts = [];
if ($cam > 0 && $test > 0) {
    $stmt2 = $conn->prepare("SELECT * FROM ielts_listening_parts WHERE cambridge_no = ? AND test_no = ? ORDER BY part_no ASC");
    $stmt2->bind_param("ii", $cam, $test);
    $stmt2->execute();
    $res_parts = $stmt2->get_result();
    while($row = $res_parts->fetch_assoc()) { $parts[] = $row; }
}

$current_part = null;
$images = [];
$questions = [];
if ($part_id > 0) {
    $stmt3 = $conn->prepare("SELECT * FROM ielts_listening_parts WHERE part_id = ?");
    $stmt3->bind_param("i", $part_id);
    $stmt3->execute();
    $current_part = $stmt3->get_result()->fetch_assoc();

    $stmt4 = $conn->prepare("SELECT image_url FROM ielts_part_images WHERE part_id = ? ORDER BY image_order ASC");
    $stmt4->bind_param("i", $part_id);
    $stmt4->execute();
    $res_img = $stmt4->get_result();
    while($row = $res_img->fetch_assoc()) { $images[] = $row['image_url']; }
    
    $stmt5 = $conn->prepare("SELECT * FROM ielts_answers WHERE part_id = ? ORDER BY question_no ASC");
    $stmt5->bind_param("i", $part_id);
    $stmt5->execute();
    $res_ques = $stmt5->get_result();
    while($row = $res_ques->fetch_assoc()) { $questions[] = $row; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IELTS Listening - Word Garden</title>
    <style>
        /* ===== Premium Green Theme System ===== */
        :root {
            --primary-green: #1b4332;
            --accent-green: #40916c;
            --soft-green-bg: #f2f7f5;
            --card-shadow: 0 10px 30px rgba(27, 67, 50, 0.08);
            --card-shadow-hover: 0 20px 40px rgba(27, 67, 50, 0.15);
            --text-main: #2d3436;
        }

        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: var(--soft-green-bg);
            margin: 0;
            color: var(--text-main);
            padding-bottom: 100px; /* Space for footer player */
        }

        /* ===== 1. Navigation Header ===== */
        .nav-header {
            width: 100%;
            height: 70px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 50px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            position: fixed;
            top: 0;
            z-index: 1000;
        }
        .nav-logo { font-size: 22px; font-weight: bold; color: var(--primary-green); text-decoration: none; }
        .nav-links { display: flex; gap: 20px; }
        .nav-links a {
            text-decoration: none;
            color: #666;
            font-size: 14px;
            font-weight: 500;
            padding: 5px 12px;
            border-radius: 8px;
            transition: 0.3s;
        }
        .nav-links a:hover, .nav-links a.active { color: var(--primary-green); background: #f0f7f4; }

        /* ===== 2. Hero Banner ===== */
        .hero-mini {
            background: linear-gradient(135deg, #081c15 0%, #1b4332 100%);
            color: white;
            padding: 110px 20px 70px;
            text-align: center;
        }
        .hero-mini h1 { margin: 0; font-size: 2.4rem; letter-spacing: 1px; }
        .hero-mini p { opacity: 0.8; margin-top: 10px; font-weight: 300; text-transform: uppercase; letter-spacing: 2px; }

        /* ===== 3. Main Content Container ===== */
        .main-content {
            max-width: 1200px;
            margin: -50px auto 60px;
            padding: 0 20px;
            position: relative;
            z-index: 10;
        }

        /* Card Grids */
        .grid-view {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 25px;
        }
        .card {
            background: white;
            border-radius: 20px;
            padding: 40px 20px;
            text-align: center;
            cursor: pointer;
            box-shadow: var(--card-shadow);
            transition: all 0.4s;
            border: 1px solid transparent;
            font-weight: 600;
            color: var(--primary-green);
        }
        .card:hover {
            transform: translateY(-8px);
            box-shadow: var(--card-shadow-hover);
            border-color: var(--accent-green);
        }

        /* Sidebar Replacement (Control Header) */
        .breadcrumb-nav {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
        }
        .btn-back {
            background: white;
            color: var(--primary-green);
            border: none;
            padding: 10px 20px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            box-shadow: var(--card-shadow);
            text-decoration: none;
            font-size: 14px;
        }
        .btn-back:hover { background: #f0f7f4; }

        /* Question Box */
        .question-container {
            background: white;
            border-radius: 25px;
            padding: 50px;
            box-shadow: var(--card-shadow);
        }
        .img-item {
            max-width: 100%;
            display: block;
            margin: 30px auto;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        .q-row {
            margin: 20px 0;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .q-input {
            padding: 12px 15px;
            border: 2px solid #eee;
            border-radius: 10px;
            width: 250px;
            outline: none;
            transition: 0.3s;
        }
        .q-input:focus { border-color: var(--accent-green); }

        /* Submit Button */
        .submit-btn {
            background: var(--primary-green);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 50px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 30px;
            font-size: 16px;
            transition: 0.3s;
        }
        .submit-btn:hover { background: var(--accent-green); transform: translateY(-2px); }

        /* ===== 4. Audio Player Bar ===== */
        .player-bar {
            position: fixed;
            bottom: 0;
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 20px 50px;
            box-shadow: 0 -5px 20px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 30px;
            z-index: 1000;
            box-sizing: border-box;
        }
        .player-info { min-width: 250px; }
        .player-info strong { color: var(--primary-green); display: block; margin-bottom: 5px; }
        audio { flex: 1; height: 35px; }

        /* AI Assistant */
        .side-controls { position: fixed; bottom: 120px; right: 40px; z-index: 100; }
        .ai-assistant { display: flex; align-items: center; gap: 15px; }
        .chat-bubble {
            background: white; color: var(--primary-green);
            padding: 12px 20px; border-radius: 20px 20px 5px 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05); font-size: 14px;
            border: 1px solid #eef5f2;
        }
        .ai-icon-circle {
            width: 60px; height: 60px;
            background: linear-gradient(135deg, var(--accent-green), var(--primary-green));
            border-radius: 50%; display: flex; justify-content: center; align-items: center;
            color: white; font-weight: bold;
        }
    </style>
</head>
<body>

    <nav class="nav-header">
        <a href="home.php" class="nav-logo">Word Garden</a>
        <div class="nav-links">
            <a href="home.php">Home</a>
            <a href="TED.php">TED Talk</a>
            <a href="ielts.php" class="active">IELTS</a>
            <a href="daily_decryption.php">Daily Talk</a>
            <a href="vocabulary.php">Vocabulary</a>
            <a href="calendar.php">Calendar</a>
            <a href="profile.php">Profile</a>
        </div>
    </nav>

    <header class="hero-mini">
        <h1>IELTS Listening</h1>
        <p>Master your Cambridge exams with precision</p>
    </header>

    <main class="main-content">
        
        <?php if ($cam === 0): ?>
            <div class="grid-view">
                <?php foreach ($books as $b): ?>
                    <div class="card" onclick="location.href='?cam=<?= $b ?>'">CAMBRIDGE <?= $b ?></div>
                <?php endforeach; ?>
            </div>

        <?php elseif ($test === 0): ?>
            <div class="breadcrumb-nav">
                <a href="ielts.php" class="btn-back">← Back to Books</a>
                <span style="font-weight: bold; color: var(--primary-green);">Cambridge <?= $cam ?></span>
            </div>
            <div class="grid-view">
                <?php foreach ($tests as $t): ?>
                    <div class="card" onclick="location.href='?cam=<?= $cam ?>&test=<?= $t ?>'">TEST <?= $t ?></div>
                <?php endforeach; ?>
            </div>

        <?php elseif ($part_id === 0): ?>
            <div class="breadcrumb-nav">
                <a href="?cam=<?= $cam ?>" class="btn-back">← Back to Tests</a>
                <span style="font-weight: bold; color: var(--primary-green);">Cambridge <?= $cam ?> / Test <?= $test ?></span>
            </div>
            <div class="grid-view">
                <?php foreach ($parts as $p): ?>
                    <div class="card" onclick="location.href='?cam=<?= $cam ?>&test=<?= $test ?>&part_id=<?= $p['part_id'] ?>'">PART <?= $p['part_no'] ?></div>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <div class="breadcrumb-nav">
                <button onclick="history.back()" class="btn-back">← Back</button>
                <span style="font-weight: bold; color: var(--primary-green);">Part <?= $current_part['part_no'] ?> - <?= htmlspecialchars($current_part['title']) ?></span>
            </div>

            <div class="question-container">
                <?php foreach ($images as $img): ?>
                    <img src="<?= htmlspecialchars($img) ?>" class="img-item">
                <?php endforeach; ?>

                <form id="quiz-form" style="margin-top:40px;">
                    <?php foreach ($questions as $q): ?>
                        <div class="q-row">
                            <strong style="width: 50px; color: var(--primary-green);">Q<?= $q['question_no'] ?>:</strong>
                            <input type="text" class="q-input" name="q<?= $q['question_no'] ?>" placeholder="Type answer here...">
                        </div>
                    <?php endforeach; ?>
                    <div style="text-align: center;">
                        <button type="button" class="submit-btn" onclick="submitAnswers()">Submit and Check Results</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

    </main>

    <?php if ($current_part): ?>
    <div class="player-bar">
        <div class="player-info">
            <strong>Now Playing</strong>
            <span style="font-size: 13px; color: #666;"><?= htmlspecialchars($current_part['title']) ?></span>
        </div>
        <audio controls src="<?= htmlspecialchars($current_part['audio_url']) ?>"></audio>
    </div>
    <?php endif; ?>

    <aside class="side-controls">
        <div class="ai-assistant">
            <div class="chat-bubble">Hi <?= htmlspecialchars($nickname) ?>, focus on the keywords!</div>
            <div class="ai-icon-circle">AI</div>
        </div>
    </aside>

    <script>
        const correctAnswers = {
            <?php foreach ($questions as $q): ?>
                "q<?= $q['question_no'] ?>": "<?= addslashes($q['correct_answer']) ?>",
            <?php endforeach; ?>
        };

        function submitAnswers() {
            const form = document.getElementById('quiz-form');
            const inputs = form.querySelectorAll('input[type="text"]');
            let score = 0;

            inputs.forEach(input => {
                const qName = input.name;
                const userAnswer = input.value.trim().toLowerCase();
                const correctStr = (correctAnswers[qName] || "").toLowerCase();
                const allowed = correctStr.split('|').map(s => s.trim());

                // Clear previous hints
                const existingHint = input.parentNode.querySelector('.hint');
                if (existingHint) existingHint.remove();

                if (allowed.includes(userAnswer) && userAnswer !== "") {
                    input.style.borderColor = "#22c55e"; // Success Green
                    score++;
                } else {
                    input.style.borderColor = "#ef4444"; // Error Red
                    const span = document.createElement('span');
                    span.className = 'hint';
                    span.style.cssText = 'color: #ef4444; font-size: 12px; margin-left: 10px; font-weight: 600;';
                    span.innerHTML = 'Correct: ' + correctStr.split('|')[0];
                    input.parentNode.appendChild(span);
                }
            });
            alert("Assessment Complete!\nYour Score: " + score + " / " + inputs.length);
        }
    </script>
</body>
</html>