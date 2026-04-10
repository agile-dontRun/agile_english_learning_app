<?php
session_start();
require_once 'db_connect.php';  

// 1. Login Security Check
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$nickname = $_SESSION['nickname'] ?? 'Learner';

// Pagination Logic (Kept as per your original code)
$limit = 8;                                      
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Fetch Total Count
$total_sql = "SELECT COUNT(*) as total FROM ted_talks";
$total_result = $conn->query($total_sql);
$total = $total_result ? $total_result->fetch_assoc()['total'] : 0;
$total_pages = ceil($total / $limit);

$ted_id = isset($_GET['ted_id']) && is_numeric($_GET['ted_id']) ? (int)$_GET['ted_id'] : 0;
$talk = null;
$sibling = null;
$back_page = $page;

if ($ted_id > 0) {
    // 2. Player Logic (Fetching specific video)
    $stmt = $conn->prepare("SELECT ted_id, title, speaker, subtitle_mode, video_url FROM ted_talks WHERE ted_id = ?");
    $stmt->bind_param("i", $ted_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $talk = $result->fetch_assoc();

    if (!$talk) {
        header("Location: TED.php");
        exit();
    }

    // Sibling toggle logic (With/Without subtitle)
    $stmt2 = $conn->prepare("SELECT ted_id, subtitle_mode FROM ted_talks WHERE title = ? AND subtitle_mode != ? LIMIT 1");
    $stmt2->bind_param("ss", $talk['title'], $talk['subtitle_mode']);
    $stmt2->execute();
    $sib_res = $stmt2->get_result();
    if ($sib_res->num_rows > 0) {
        $sibling = $sib_res->fetch_assoc();
    }
} else {
    // 3. List Logic (Fetching grid items)
    $stmt = $conn->prepare("SELECT ted_id, title, speaker, subtitle_mode FROM ted_talks ORDER BY ted_id ASC LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $video_result = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TED Talk - Word Garden</title>
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
            font-family: 'Segoe UI', sans-serif;
            background: var(--soft-green-bg);
            margin: 0;
            color: var(--text-main);
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
            box-sizing: border-box;
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

        /* ===== 3. Content Layout ===== */
        .main-content {
            max-width: 1200px;
            margin: -50px auto 60px;
            padding: 0 20px;
            position: relative;
            z-index: 10;
        }

        /* Video Grid Styling */
        .video-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
        }
        .video-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            box-shadow: var(--card-shadow);
            transition: all 0.4s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            border: 1px solid transparent;
        }
        .video-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--card-shadow-hover);
            border-color: var(--accent-green);
        }
        .video-placeholder {
            width: 100%;
            aspect-ratio: 16/10;
            background: #f7fdfa;
            border-radius: 15px;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 15px;
        }
        .play-icon { font-size: 40px; color: var(--accent-green); }
        .subtitle-badge {
            font-size: 11px;
            background: var(--soft-green-bg);
            color: var(--primary-green);
            padding: 4px 12px;
            border-radius: 50px;
            font-weight: bold;
            text-transform: uppercase;
        }

        /* Player Section Styling */
        .player-container {
            background: white;
            border-radius: 25px;
            padding: 40px;
            box-shadow: var(--card-shadow);
            max-width: 900px;
            margin: 0 auto;
        }
        .video-player-box {
            width: 100%;
            aspect-ratio: 16 / 9;
            background: #000;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin: 25px 0;
        }
        .video-player-box iframe { width: 100%; height: 100%; border: none; }

        /* Buttons */
        .btn {
            padding: 12px 28px;
            border: none;
            border-radius: 50px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary { background: var(--primary-green); color: white; }
        .btn-accent { background: var(--accent-green); color: white; }
        .btn-danger { background: #e63946; color: white; margin-top: 20px; padding: 18px 45px; font-size: 16px; }
        .btn:hover { opacity: 0.9; transform: scale(1.03); }

        /* Pagination */
        .pagination {
            margin-top: 40px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
        }
        .pagination a {
            text-decoration: none;
            color: var(--primary-green);
            background: white;
            padding: 8px 20px;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            font-weight: 600;
        }

        /* ===== 4. Side AI Assistant ===== */
        .side-controls { position: fixed; bottom: 40px; right: 40px; z-index: 100; }
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
            box-shadow: 0 8px 20px rgba(27, 67, 50, 0.2); color: white; font-weight: bold;
        }
    </style>
</head>
<body>

    <nav class="nav-header">
        <a href="home.php" class="nav-logo">Word Garden</a>
        <div class="nav-links">
            <a href="home.php">Home</a>
            <a href="TED.php" class="active">TED Talk</a>
            <a href="ielts.php">IELTS</a>
            <a href="daily_decryption.php">Daily Talk</a>
            <a href="vocabulary.php">Vocabulary</a>
            <a href="calendar.php">Calendar</a>
            <a href="profile.php">Profile</a>
        </div>
    </nav>

    <header class="hero-mini">
        <h1>TED Talk</h1>
        <p>Expand your mind and polish your listening</p>
    </header>

    <main class="main-content">
        
        <?php if ($ted_id === 0): ?>
            <div class="video-grid">
                <?php if ($video_result->num_rows > 0): ?>
                    <?php while ($row = $video_result->fetch_assoc()): 
                        $mode_text = ($row['subtitle_mode'] === 'with_subtitle') ? 'Subtitles' : 'No Subtitles';
                    ?>
                    <div class="video-card" onclick="window.location.href='TED.php?ted_id=<?= $row['ted_id'] ?>&page=<?= $page ?>'">
                        <div class="video-placeholder">
                            <span class="play-icon">▶</span>
                        </div>
                        <h4 style="margin: 0 0 10px 0; color: var(--primary-green);"><?= htmlspecialchars($row['title']) ?></h4>
                        <div class="subtitle-badge"><?= $mode_text ?></div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="grid-column: 1/-1; text-align: center; color: #666;">No TED talks available currently.</p>
                <?php endif; ?>
            </div>

            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="TED.php?page=<?= $page-1 ?>">Previous</a>
                <?php endif; ?>
                <span style="font-weight: 600;">Page <?= $page ?> of <?= $total_pages ?></span>
                <?php if ($page < $total_pages): ?>
                    <a href="TED.php?page=<?= $page+1 ?>">Next</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="player-container">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <a href="TED.php?page=<?= $back_page ?>" class="btn btn-primary" style="font-size: 14px;">← Back to List</a>
                    <?php if ($sibling): ?>
                        <a href="TED.php?ted_id=<?= $sibling['ted_id'] ?>&page=<?= $back_page ?>" class="btn btn-accent" style="font-size: 14px;">
                            Switch to <?= $sibling['subtitle_mode'] === 'with_subtitle' ? 'Subtitled' : 'Original' ?>
                        </a>
                    <?php endif; ?>
                </div>

                <div style="text-align: center; margin-top: 30px;">
                    <h2 style="color: var(--primary-green); margin: 0;"><?= htmlspecialchars($talk['title']) ?></h2>
                    <p style="color: #666; margin: 10px 0;">Speaker: <?= htmlspecialchars($talk['speaker'] ?? 'Unknown') ?></p>
                </div>

                <div class="video-player-box">
                    <?php
                    $video_src = htmlspecialchars($talk['video_url']);
                    if (preg_match('/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/', $talk['video_url'], $m) ||
                        preg_match('/youtu\.be\/([a-zA-Z0-9_-]+)/', $talk['video_url'], $m)) {
                        $video_src = 'https://www.youtube.com/embed/' . $m[1];
                    }
                    ?>
                    <iframe src="<?= $video_src ?>" allowfullscreen></iframe>
                </div>

                <div style="text-align: center;">
                    <button class="btn btn-danger" onclick="window.location.href='practice.php?ted_id=<?= $talk['ted_id'] ?>'">
                        DO SOME LISTENING PRACTICES
                    </button>
                </div>
            </div>
        <?php endif; ?>

    </main>

    <aside class="side-controls">
        <div class="ai-assistant">
            <div class="chat-bubble">Hi <?= htmlspecialchars($nickname) ?>, let's dive into some great ideas!</div>
            <div class="ai-icon-circle">AI</div>
        </div>
    </aside>

</body>
</html>