<?php
session_start();
require_once 'db_connect.php';

// 1. Login verification
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ==========================================
// A. Get user profile (for navbar avatar - fully synchronized with Listening logic)
// ==========================================
$username = ''; $nickname = 'Student'; $db_avatar = '';
$stmt = $conn->prepare("SELECT username, nickname, avatar_url FROM users WHERE user_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_data = $stmt->get_result()->fetch_assoc();
    if ($user_data) {
        $username = $user_data['username'];
        $nickname = !empty($user_data['nickname']) ? $user_data['nickname'] : $username;
        $db_avatar = $user_data['avatar_url'];
    }
    $stmt->close();
}

// Dynamic avatar logic
$avatar_html = '';
$first_letter = strtoupper(substr($username ? $username : 'U', 0, 1));
if (!empty($db_avatar)) {
    $avatar_html = '<img src="' . htmlspecialchars($db_avatar) . '" alt="Avatar" class="user-avatar-img" onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'flex\';">';
    $avatar_html .= '<div class="user-avatar-placeholder" style="display:none;">' . htmlspecialchars($first_letter) . '</div>';
} else {
    $avatar_html = '<div class="user-avatar-placeholder">' . htmlspecialchars($first_letter) . '</div>';
}

// ==========================================
// B. IELTS business logic (maintain original functionality)
// ==========================================
$cam = isset($_GET['cam']) && is_numeric($_GET['cam']) ? (int)$_GET['cam'] : 0;
$test = isset($_GET['test']) && is_numeric($_GET['test']) ? (int)$_GET['test'] : 0;
$part_id = isset($_GET['part_id']) && is_numeric($_GET['part_id']) ? (int)$_GET['part_id'] : 0;

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
    <title>IELTS Practice - Spires Academy</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=Lora:wght@700&display=swap" rel="stylesheet">
    <style>
        :root {
            --oxford-blue: #002147;
            --oxford-blue-light: #003066;
            --oxford-gold: #c4a661;
            --oxford-gold-light: #d4b671;
            --white: #ffffff;
            --bg-light: #f4f7f6;
            --text-dark: #333333;
            --text-light: #666666;
            --border-color: #e0e0e0;
        }

        body { margin: 0; padding: 0; font-family: 'Open Sans', Arial, sans-serif; background-color: var(--bg-light); color: var(--text-dark); }
        h1, h2, h3 { font-family: 'PT Serif', Georgia, serif; letter-spacing: 0.5px; }

        /* ===== 1. Navbar (fully replicated from Listening.php) ===== */
        .navbar { background-color: var(--oxford-blue); color: var(--white); display: flex; justify-content: space-between; align-items: center; padding: 0 40px; height: 80px; position: sticky; top: 0; z-index: 1000; box-shadow: 0 2px 10px rgba(0,0,0,0.2); }
        .navbar-left { display: flex; align-items: center; height: 100%; }
        .college-logo { height: 50px; width: auto; cursor: pointer; transition: transform 0.3s; }
        .college-logo:hover { transform: scale(1.02); }

        .navbar-links { display: flex; gap: 10px; list-style: none; margin: 0 0 0 40px; padding: 0; height: 100%; align-items: center; }
        .navbar-links > li { display: flex; align-items: center; position: relative; height: 100%; }
        .navbar-links a { 
            color: #ffffff; text-decoration: none; font-family: 'Playfair Display', serif;
            font-size: 16px; font-weight: 800; padding: 0 20px; height: 100%; display: flex; align-items: center; 
            text-transform: uppercase; letter-spacing: 1.8px; text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.6);
            transition: all 0.3s ease; -webkit-font-smoothing: antialiased;
        }
        .navbar-links a:hover { color: var(--oxford-gold); background-color: rgba(255, 255, 255, 0.05); }

        .dropdown-menu { display: none; position: absolute; top: 80px; left: 0; background-color: var(--oxford-blue-light); min-width: 220px; box-shadow: 0 8px 16px rgba(0,0,0,0.2); list-style: none !important; padding: 0; margin: 0; border-top: 2px solid var(--oxford-gold); }
        .dropdown-menu li { list-style: none !important; margin: 0; padding: 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .dropdown-menu li a { color: #e0e0e0 !important; padding: 15px 20px; text-transform: none; justify-content: flex-start; width: 100%; box-sizing: border-box; text-decoration: none !important; display: block; font-weight: 400; height: auto; text-shadow: none; letter-spacing: 0.5px;}
        .dropdown-menu li a:hover { background-color: var(--oxford-blue) !important; color: var(--white) !important; padding-left: 25px; }
        .navbar-links li:hover .dropdown-menu, .dropdown:hover .dropdown-menu { display: block; }

        .navbar-right { display: flex; align-items: center; gap: 10px; cursor: pointer; height: 100%; position: relative; }
        .user-avatar-img { width: 40px; height: 40px; border-radius: 50%; border: 2px solid var(--oxford-gold); object-fit: cover; }
        .user-avatar-placeholder { width: 40px; height: 40px; border-radius: 50%; background-color: var(--oxford-gold); color: var(--oxford-blue); display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 18px; border: 2px solid var(--oxford-gold); line-height: 1; box-sizing: border-box; }
        .navbar-right .dropdown-menu { background-color: var(--white); border-top: none; border-radius: 0 0 8px 8px; overflow: hidden; font-family: 'Playfair Display', serif; }
        .navbar-right .dropdown-menu li div[style*="font-size:12px"] { font-family: 'Playfair Display', serif !important; font-style: italic; letter-spacing: 0.5px; color: #888 !important; }
        .navbar-right .dropdown-menu li div[style*="font-size:16px"] { font-family: 'Playfair Display', serif !important; font-weight: 800; color: var(--oxford-blue) !important; letter-spacing: 1px; text-transform: uppercase; }
        .navbar-right .dropdown-menu li a { font-family: 'Playfair Display', serif !important; font-weight: 700; font-size: 15px; color: var(--oxford-blue) !important; letter-spacing: 0.5px; transition: all 0.2s ease; }
        .navbar-right .dropdown-menu li a:hover { background-color: #f8fafc !important; color: var(--oxford-gold) !important; padding-left: 25px; }

        /* ===== 2. Hero section (Oxford Style) ===== */
        .hero {
            background: url('hero_bg2.png') center/cover no-repeat; 
            color: var(--white); text-align: center; padding: 140px 20px;
            text-shadow: 0 2px 8px rgba(0,0,0,0.8);
        }
        .hero h1 { 
            font-family: 'Playfair Display', serif; font-size: 5rem; font-weight: 800; 
            margin: 0 0 20px; text-transform: uppercase; letter-spacing: 5px; 
            text-shadow: 2px 4px 10px rgba(0, 0, 0, 0.8);
        }
        .hero p { font-family: 'Playfair Display', serif; font-size: 1.4rem; font-weight: 400; font-style: italic; max-width: 800px; margin: 0 auto; text-shadow: 1px 2px 5px rgba(0, 0, 0, 0.8); }

        /* ===== 3. Content layout ===== */
        .main-content { max-width: 1200px; margin: -50px auto 100px; padding: 0 20px; position: relative; z-index: 10; }

        .grid-view { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 25px; }
        .card {
            background: white; border-radius: 8px; padding: 40px 20px; text-align: center; cursor: pointer;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05); transition: all 0.4s;
            border-top: 4px solid var(--oxford-gold); font-weight: 800; color: var(--oxford-blue);
            font-family: 'Playfair Display', serif; letter-spacing: 1px;
        }
        .card:hover { transform: translateY(-8px); box-shadow: 0 15px 40px rgba(0,0,0,0.1); border-top-color: var(--oxford-blue); }

        .breadcrumb-nav { display: flex; align-items: center; gap: 15px; margin-bottom: 30px; }
        .btn-back {
            background: var(--oxford-blue); color: white; border: none; padding: 10px 20px;
            border-radius: 4px; cursor: pointer; font-weight: 600; text-decoration: none; font-size: 13px; text-transform: uppercase;
        }
        .btn-back:hover { background: var(--oxford-gold); }

        .question-container { background: white; border-radius: 8px; padding: 50px; box-shadow: 0 10px 40px rgba(0,0,0,0.05); border-top: 4px solid var(--oxford-blue); }
        .img-item { max-width: 100%; display: block; margin: 30px auto; border: 1px solid var(--border-color); border-radius: 4px; }
        .q-row { margin: 20px 0; display: flex; align-items: center; gap: 15px; }
        .q-input { padding: 12px 15px; border: 2px solid var(--border-color); border-radius: 4px; width: 250px; outline: none; transition: 0.3s; }
        .q-input:focus { border-color: var(--oxford-gold); }

        .submit-btn {
            background: var(--oxford-blue); color: white; border: none; padding: 18px 50px;
            border-radius: 4px; font-weight: 800; cursor: pointer; margin-top: 30px; font-size: 15px;
            text-transform: uppercase; letter-spacing: 2px; font-family: 'Playfair Display', serif;
        }
        .submit-btn:hover { background: var(--oxford-gold); transform: translateY(-2px); }

        /* ===== 4. Player bar ===== */
        .player-bar {
            position: fixed; bottom: 0; width: 100%; background: rgba(0, 33, 71, 0.95);
            backdrop-filter: blur(10px); padding: 20px 50px; box-shadow: 0 -5px 20px rgba(0,0,0,0.1);
            display: flex; align-items: center; gap: 30px; z-index: 1000; box-sizing: border-box; color: white;
        }
        .player-info strong { color: var(--oxford-gold); display: block; margin-bottom: 5px; font-family: 'Playfair Display', serif; }
        audio { flex: 1; height: 35px; filter: invert(100%) hue-rotate(180deg); }

        /* AI Assistant */
        .side-controls { position: fixed; bottom: 120px; right: 40px; z-index: 100; }
        .ai-assistant { display: flex; align-items: center; gap: 15px; }
        .chat-bubble {
            background: white; color: var(--oxford-blue); padding: 15px 25px; border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1); font-size: 14px; border-left: 4px solid var(--oxford-gold);
            font-family: 'Playfair Display', serif; font-style: italic; font-weight: 600;
        }
        .ai-icon-circle {
            width: 60px; height: 60px; background: var(--oxford-blue); border-radius: 50%;
            display: flex; justify-content: center; align-items: center; color: white; font-weight: bold; border: 2px solid var(--oxford-gold);
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="navbar-left">
            <a href="home.php"><img src="college_logo.png" alt="Spires Academy Logo" class="college-logo"></a>
            <ul class="navbar-links">
                <li><a href="home.php">Home</a></li>
                <li class="dropdown">
                    <a href="#" style="color:var(--oxford-gold);">Study ▾</a>
                    <ul class="dropdown-menu">
                        <li><a href="listening.php" style="color:var(--oxford-gold)!important; font-weight: bold;">Listening</a></li>
                        <li><a href="reading.php">Reading</a></li>
                        <li><a href="emma_server/speakAI.php">Speaking</a></li>
                        <li><a href="writing.php">Writing</a></li>
                        <li><a href="vocabulary.php">Vocabulary</a></li>
                    </ul>
                </li>
                <li class="dropdown">
                    <a href="#">Games ▾</a>
                    <ul class="dropdown-menu">
                        <li><a href="galgame/galgame/index.html">Story game</a></li>
                    </ul>
                </li>
                <li><a href="forum.php">Community</a></li>
            </ul>
        </div>

        <div class="navbar-right dropdown">
            <?php echo $avatar_html; ?>
            <span style="font-size:14px; font-weight:600; color:#e0e0e0;"><?php echo htmlspecialchars($nickname); ?> ▾</span>
            <ul class="dropdown-menu" style="right:0; left:auto; margin-top:0; min-width:220px;">
                <li style="padding: 20px; background: #f8fafc; cursor:default;">
                    <div style="color:var(--text-light); font-size:12px; margin-bottom:5px; font-family:'Playfair Display', serif; font-style:italic;">Signed in as</div>
                    <div style="color:var(--oxford-blue); font-weight:bold; font-size:16px; text-transform:uppercase;"><?php echo htmlspecialchars($nickname); ?></div>
                </li>
                <li><a href="profile.php">My Profile</a></li>
                <li><a href="logout.php" style="color:#dc3545 !important; font-weight: 600;">Sign Out</a></li>
            </ul>
        </div>
    </nav>

    <header class="hero">
        <h1>IELTS Listening</h1>
        <p>Master your Cambridge exams with academic precision</p>
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
                <span style="font-weight: 800; color: var(--oxford-blue); font-family: 'Playfair Display', serif;">Cambridge <?= $cam ?></span>
            </div>
            <div class="grid-view">
                <?php foreach ($tests as $t): ?>
                    <div class="card" onclick="location.href='?cam=<?= $cam ?>&test=<?= $t ?>'">TEST <?= $t ?></div>
                <?php endforeach; ?>
            </div>

        <?php elseif ($part_id === 0): ?>
            <div class="breadcrumb-nav">
                <a href="?cam=<?= $cam ?>" class="btn-back">← Back to Tests</a>
                <span style="font-weight: 800; color: var(--oxford-blue); font-family: 'Playfair Display', serif;">Cambridge <?= $cam ?> / Test <?= $test ?></span>
            </div>
            <div class="grid-view">
                <?php foreach ($parts as $p): ?>
                    <div class="card" onclick="location.href='?cam=<?= $cam ?>&test=<?= $test ?>&part_id=<?= $p['part_id'] ?>'">PART <?= $p['part_no'] ?></div>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <div class="breadcrumb-nav">
                <button onclick="history.back()" class="btn-back">← Back</button>
                <span style="font-weight: 800; color: var(--oxford-blue); font-family: 'Playfair Display', serif;">Part <?= $current_part['part_no'] ?> - <?= htmlspecialchars($current_part['title']) ?></span>
            </div>

            <div class="question-container">
                <?php foreach ($images as $img): ?>
                    <img src="<?= htmlspecialchars($img) ?>" class="img-item">
                <?php endforeach; ?>

                <form id="quiz-form" style="margin-top:40px;">
                    <?php foreach ($questions as $q): ?>
                        <div class="q-row">
                            <strong style="width: 50px; color: var(--oxford-blue); font-family: 'Playfair Display', serif;">Q<?= $q['question_no'] ?>:</strong>
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
            <span style="font-size: 13px; color: #ccc;"><?= htmlspecialchars($current_part['title']) ?></span>
        </div>
        <audio controls src="<?= htmlspecialchars($current_part['audio_url']) ?>"></audio>
    </div>
    <?php endif; ?>

    <aside class="side-controls">
        <div class="ai-assistant">
            <div class="chat-bubble">Focus on the academic keywords, <?= htmlspecialchars($nickname) ?>.</div>
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

            const oldScore = document.getElementById('final-score');
            if (oldScore) oldScore.remove();

            inputs.forEach(input => {
                const qName = input.name;
                const userAnswer = input.value.trim().toLowerCase();
                const correctStr = (correctAnswers[qName] || "").toLowerCase();
                const allowed = correctStr.split('|').map(s => s.trim());

                const existingHint = input.parentNode.querySelector('.hint');
                if (existingHint) existingHint.remove();

                if (allowed.includes(userAnswer) && userAnswer !== "") {
                    input.style.borderColor = "#2d6a4f"; 
                    input.style.background = "#f0f7f4";
                    score++;
                } else {
                    input.style.borderColor = "#c0392b"; 
                    input.style.background = "#fff5f5";
                    const span = document.createElement('span');
                    span.className = 'hint';
                    span.style.cssText = 'color: #c0392b; font-size: 12px; margin-left: 10px; font-weight: 700; font-family: "Playfair Display";';
                    span.innerHTML = ' Correct: ' + correctStr.split('|')[0];
                    input.parentNode.appendChild(span);
                }
            });

            const scoreDiv = document.createElement('div');
            scoreDiv.id = 'final-score';
            scoreDiv.style.cssText = 'text-align: center; font-size: 24px; font-weight: 800; margin-top: 30px; color: var(--oxford-blue); font-family: "Playfair Display";';
            scoreDiv.innerHTML = `Your Score: ${score} / ${inputs.length}`;
            form.appendChild(scoreDiv);
        }
    </script>
</body>
</html>