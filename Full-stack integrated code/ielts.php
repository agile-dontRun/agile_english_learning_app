<?php
session_start();
require_once 'db_connect.php';

$nickname = $_SESSION['nickname'] ?? 'Student';
$cam = isset($_GET['cam']) && is_numeric($_GET['cam']) ? (int)$_GET['cam'] : 19;
$test = isset($_GET['test']) && is_numeric($_GET['test']) ? (int)$_GET['test'] : 0;


$tests = [];
$stmt = $conn->prepare("SELECT DISTINCT test_no FROM ielts_listening_parts WHERE cambridge_no = ? ORDER BY test_no ASC");
$stmt->bind_param("i", $cam);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $tests[] = $row['test_no'];
}


$parts = [];
if ($test > 0) {
    $stmt2 = $conn->prepare("SELECT part_no, title, audio_url FROM ielts_listening_parts 
                             WHERE cambridge_no = ? AND test_no = ? ORDER BY part_no ASC");
    $stmt2->bind_param("ii", $cam, $test);
    $stmt2->execute();
    $res = $stmt2->get_result();
    while ($row = $res->fetch_assoc()) {
        $parts[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Word Garden - IELTS Listening</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .ielts-page {
            display: flex;
            height: calc(100vh - 80px);
            background: #f8f1e9;
        }

        .sidebar {
            width: 320px;
            background: #f8f1e9;
            padding: 40px 20px;
            border-right: 1px solid #ddd;
            display: flex;
            flex-direction: column;
        }
        .cam-title {
            font-size: 28px;
            font-weight: bold;
            color: #1e3a8a;
            margin-bottom: 30px;
            text-align: center;
        }
        .test-btn {
            background: #dbeafe;
            border: none;
            border-radius: 12px;
            padding: 18px;
            margin-bottom: 12px;
            font-size: 18px;
            font-weight: bold;
            color: #1e40af;
            cursor: pointer;
            transition: all 0.3s;
        }
        .test-btn:hover, .test-btn.active {
            background: #3b82f6;
            color: white;
            transform: translateX(8px);
        }


        .main-area {
            flex: 1;
            background: linear-gradient(135deg, #60a5fa, #3b82f6);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            position: relative;
        }
        .placeholder {
            font-size: 28px;
            font-weight: 500;
            text-align: center;
            max-width: 500px;
        }


        .parts-container {
            width: 90%;
            max-width: 700px;
            text-align: center;
        }
        .test-header {
            font-size: 26px;
            margin-bottom: 30px;
        }
        .parts-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        .part-card {
            background: rgba(255,255,255,0.95);
            color: #1e3a8a;
            border-radius: 16px;
            padding: 24px 20px;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .part-card:hover {
            transform: scale(1.05);
            background: #fff;
        }
        .part-card svg {
            width: 48px;
            height: 48px;
            fill: #1e40af;
            margin-bottom: 12px;
        }

        .player-area {
            margin-top: 40px;
            width: 100%;
        }
        #ielts-player {
            width: 100%;
            background: white;
            border-radius: 12px;
            padding: 8px;
        }
        .now-playing {
            margin: 15px 0;
            font-size: 18px;
            font-weight: bold;
        }

        .bottom-decor {
            position: absolute;
            bottom: 30px;
            display: flex;
            width: 100%;
            justify-content: space-between;
            padding: 0 40px;
        }
        .leaf { font-size: 48px; }
        .group-icon { font-size: 42px; color: white; }
    </style>
</head>
<body class="home-page">

    <nav class="navbar">
        <div class="nav-container">
            <button class="nav-item" data-target="homepage-view">HOMEPAGE</button>
            <button class="nav-item" data-target="welcome-view">TED TALK</button>
            <button class="nav-item active" data-target="ielts-view">IELTS LISTENING</button>
            <button class="nav-item" data-target="daily-talk-view">DAILY TALK</button>
            <button class="nav-item">VOCABULARY</button>
            <button class="nav-item">CALENDAR</button>
            <button class="nav-item">GROUP</button>
            <button class="nav-item">PROFILE</button>
        </div>
    </nav>

    <div class="ielts-page">

        <div class="sidebar">
            <div class="cam-title">CAMBRIDGE <?= $cam ?></div>
            <?php foreach ($tests as $t): ?>
                <button class="test-btn <?= $test == $t ? 'active' : '' ?>" 
                        onclick="window.location.href='ielts.php?cam=<?= $cam ?>&test=<?= $t ?>'">
                    TEST <?= $t ?>
                </button>
            <?php endforeach; ?>
        </div>

  
        <div class="main-area">
            <?php if ($test === 0): ?>
             
                <div class="placeholder">
                    Please select a Test from the left sidebar...
                </div>
            <?php else: ?>
              
                <div class="parts-container">
                    <div class="test-header">
                        Cambridge <?= $cam ?> - Test <?= $test ?>
                    </div>
                    
                    <div class="parts-grid">
                        <?php foreach ($parts as $p): ?>
                        <div class="part-card" 
                             onclick="playPart('<?= htmlspecialchars($p['audio_url']) ?>', 'Part <?= $p['part_no'] ?> - <?= htmlspecialchars($p['title'] ?? '') ?>')">
                            <svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                            <div style="font-size:20px;font-weight:bold;">Part <?= $p['part_no'] ?></div>
                            <div style="font-size:14px;margin-top:8px;"><?= htmlspecialchars($p['title'] ?? 'Listening Part') ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="player-area">
                        <div class="now-playing">
                            Now Playing: <span id="current-part-title">请选择 Part</span>
                        </div>
                        <audio id="ielts-player" controls></audio>
                    </div>
                </div>
            <?php endif; ?>

            <div class="bottom-decor">
                <div class="leaf">🌱</div>
                <div class="group-icon">👥</div>
            </div>
        </div>
    </div>

    <aside class="side-controls">
        <div class="ai-assistant">
            <div class="chat-bubble">Hi <?= htmlspecialchars($nickname) ?>, I'm AI assistant</div>
            <div class="icon-label"><img src="ai_icon.png" alt="AI"></div>
        </div>
    </aside>

    <script>
        function playPart(audioUrl, title) {
            const audio = document.getElementById('ielts-player');
            audio.src = audioUrl;
            audio.play();
            document.getElementById('current-part-title').textContent = title;
        }

        document.querySelector('.nav-container').addEventListener('click', (e) => {
            const target = e.target.getAttribute('data-target');
            if (!target) return;

            if (target === 'ielts-view') {
                window.location.href = `ielts.php?cam=<?= $cam ?>`;   
                return;
            }
            if (['homepage-view', 'welcome-view', 'daily-talk-view'].includes(target)) {
                window.location.href = 'home.php';
            }
        });
    </script>
</body>
</html>