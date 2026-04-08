<?php
/**
 * TED Talk Learning Module
 * Handles video listing, pagination, and the individual video player logic.
 */
session_start();
require_once 'db_connect.php';  

// Grab the user's name or default to "Learner"
$nickname = $_SESSION['nickname'] ?? '学习者';

// --- Pagination Setup ---
$limit = 8; 
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Get total count for the pager
$total_sql = "SELECT COUNT(*) as total FROM ted_talks";
$total_result = $conn->query($total_sql);
$total = $total_result ? $total_result->fetch_assoc()['total'] : 0;
$total_pages = ceil($total / $limit);

// --- Specific Video Logic ---
$ted_id = isset($_GET['ted_id']) && is_numeric($_GET['ted_id']) ? (int)$_GET['ted_id'] : 0;
$talk = null;
$sibling = null;
$back_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;

if ($ted_id > 0) {
    // Fetch the specific talk the user clicked on
    $stmt = $conn->prepare("SELECT ted_id, title, speaker, subtitle_mode, video_url FROM ted_talks WHERE ted_id = ?");
    $stmt->bind_param("i", $ted_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $talk = $result->fetch_assoc();

    // If ID is invalid, just kick them back to the main list
    if (!$talk) {
        header("Location: TED.php");
        exit();
    }

    // Look for a "sibling" video (e.g., the same talk but with/without subtitles)
    $stmt2 = $conn->prepare("SELECT ted_id, subtitle_mode FROM ted_talks WHERE title = ? AND subtitle_mode != ? LIMIT 1");
    $stmt2->bind_param("ss", $talk['title'], $talk['subtitle_mode']);
    $stmt2->execute();
    $sib_res = $stmt2->get_result();
    if ($sib_res->num_rows > 0) {
        $sibling = $sib_res->fetch_assoc();
    }
} else {
    // No specific ID? Fetch the list for the gallery view
    $stmt = $conn->prepare("SELECT ted_id, title, speaker, subtitle_mode FROM ted_talks ORDER BY ted_id ASC LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $video_result = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Word Garden - TED Talk</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Essential view switching */
        .view-section {
            width: 100%;
            height: calc(100vh - 80px);
            display: none;
            overflow-y: auto;
        }

        /* Video List Styles */
        #video-view {
            background-color: #fdfae7;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px 0;
            height: calc(100vh - 80px);
            overflow: hidden;
        }

        .video-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            grid-template-rows: repeat(2, 1fr);
            gap: 20px;
            width: 90%;
            max-width: 1200px;
            z-index: 2;
        }

        .video-card {
            background-color: #e0e0e0;
            border: 2px solid #263238;
            aspect-ratio: 16 / 10;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            transition: transform 0.2s;
            position: relative;
            overflow: hidden;
        }

        .video-card:hover {
            transform: scale(1.02);
            background-color: #d1d1d1;
        }

        .play-arrow-svg {
            width: 50px;
            height: 50px;
            fill: #004d40;
        }

        .subtitle-badge {
            position: absolute;
            bottom: 12px;
            left: 12px;
            background: rgba(0,0,0,0.75);
            color: #fff;
            font-size: 12px;
            padding: 4px 10px;
            border-radius: 4px;
            font-weight: bold;
        }

        /* Player UI Styles */
        .player-section {
            background-color: #fff;
            overflow: hidden;
        }

        .player-layout {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            height: 100%;
            padding: 0 50px;
            box-sizing: border-box;
        }

        .main-player-area {
            flex: 1;
            max-width: 900px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 30px;
        }

        .video-player-box {
            width: 100%;
            aspect-ratio: 16 / 9;
            background: #222;
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.15);
            overflow: hidden;
            position: relative;
        }

        .video-player-box iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        .practice-btn {
            padding: 18px 45px;
            background: #e62b1e;
            color: white;
            border: none;
            border-radius: 50px;
            font-weight: bold;
            cursor: pointer;
            font-size: 18px;
            transition: background 0.3s;
        }

        .practice-btn:hover { background: #c42318; }

        .pagination {
            margin-top: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 16px;
        }
        .pagination a {
            padding: 8px 16px;
            background: #5a8a31;
            color: white;
            border-radius: 8px;
            text-decoration: none;
        }

        /* Visual Decor */
        .side-decorations {
            width: 160px;
            display: flex;
            flex-direction: column;
            gap: 30px;
            align-items: center;
        }
        .sticker img { width: 130px; border-radius: 10px; }
        .tilt-right { transform: rotate(8deg); }
        .tilt-left { transform: rotate(-8deg); }
        .footer-logo-main { margin-top: 30px; width: 350px; }
    </style>
</head>
<body class="home-page">

    <nav class="navbar">
        <div class="nav-container">
            <button class="nav-item" data-target="homepage-view">HOMEPAGE</button>
            <button class="nav-item active" data-target="welcome-view">TED TALK</button>
            <button class="nav-item" data-target="ielts-view">IELTS LISTENING</button>
            <button class="nav-item" data-target="daily-talk-view">DAILY TALK</button>
            <button class="nav-item">VOCABULARY</button>
            <button class="nav-item">CALENDAR</button>
            <button class="nav-item">GROUP</button>
            <button class="nav-item">PROFILE</button>
        </div>
    </nav>

    <main class="content-container">
        
        <section id="video-view" class="video-section view-section" 
                 style="display: <?= $ted_id > 0 ? 'none' : 'flex' ?>;">
            
            <div class="history-decor" onclick="window.location.href='home.php'" style="cursor:pointer">
                <img src="static/images/sun_icon.png" alt="History"> <span>History</span>
            </div>
            
            <div class="video-grid">
                <?php if ($ted_id === 0 && $video_result->num_rows > 0): ?>
                    <?php while ($row = $video_result->fetch_assoc()): 
                        $mode_label = ($row['subtitle_mode'] === 'with_subtitle') ? 'With subtitle' : 'Without subtitle';
                    ?>
                    <div class="video-card" 
                         onclick="window.location.href='TED.php?ted_id=<?= $row['ted_id'] ?>&page=<?= $page ?>'">
                        <svg class="play-arrow-svg" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                        <div class="subtitle-badge"><?= $mode_label ?></div>
                    </div>
                    <?php endwhile; ?>
                <?php elseif ($ted_id === 0): ?>
                    <p style="grid-column:1/-1;text-align:center;color:#666;">No TED talks found in this category.</p>
                <?php endif; ?>
            </div>

            <?php if ($ted_id === 0 && $total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="TED.php?page=<?= $page-1 ?>">Previous</a>
                <?php endif; ?>
                <span>Page <?= $page ?> of <?= $total_pages ?></span>
                <?php if ($page < $total_pages): ?>
                    <a href="TED.php?page=<?= $page+1 ?>">Next</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <img src="static/images/ted_talk_red_logo.png" class="footer-logo-main" alt="TED">
        </section>

        <?php if ($ted_id > 0 && $talk): ?>
        <section id="player-view" class="player-section view-section" style="display: flex;">
            <div class="player-layout">
                <div class="side-decorations">
                    <div class="sticker tilt-right"><img src="ted 3.png" alt="decor"></div>
                    <div class="sticker tilt-left"><img src="ted 4.png" alt="decor"></div>
                    <div class="sticker tilt-right"><img src="ted 5.png" alt="decor"></div>
                </div>

                <div class="main-player-area">
                    <div class="talk-header">
                        <h2><?= htmlspecialchars($talk['title']) ?></h2>
                        <div class="mode-info">
                            Speaker: <?= htmlspecialchars($talk['speaker'] ?? 'Unknown') ?> | 
                            <?= $talk['subtitle_mode'] === 'with_subtitle' ? 'Subtitled' : 'Raw Audio' ?>
                        </div>
                        
                        <?php if ($sibling): 
                            $btn_label = ($sibling['subtitle_mode'] === 'with_subtitle') ? 'Subtitled' : 'No-Subtitle';
                        ?>
                        <button class="subtitle-toggle-btn" 
                                onclick="window.location.href='TED.php?ted_id=<?= $sibling['ted_id'] ?>&page=<?= $back_page ?>'">
                            Switch to <?= $btn_label ?> version
                        </button>
                        <?php endif; ?>
                    </div>

                    <div class="video-player-box">
                        <?php
                        // Standardize YouTube URLs for embedding
                        $video_src = htmlspecialchars($talk['video_url']);
                        if (preg_match('/(youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $talk['video_url'], $m)) {
                            $video_src = 'https://www.youtube.com/embed/' . end($m);
                        }
                        ?>
                        <iframe src="<?= $video_src ?>" allowfullscreen></iframe>
                    </div>

                    <button class="practice-btn" onclick="window.location.href='practice.html'">DO SOME LISTENING PRACTICES</button>
                </div>
                
                <div class="side-decorations">
                    <button class="player-back-btn" onclick="window.location.href='TED.php?page=<?= $back_page ?>'">BACK</button>
                    <div class="sticker tilt-left"><img src="ted 6.png" alt="decor"></div>
                    <div class="sticker tilt-right"><img src="ted 7.png" alt="decor"></div>
                </div>
            </div>
        </section>
        <?php endif; ?>
    </main>

    <aside class="side-controls">
        <div class="ai-assistant">
            <div class="chat-bubble">Hi <?= htmlspecialchars($nickname) ?>, I'm AI assistant</div>
            <div class="icon-label"><img src="ai_icon.png" alt="AI"></div>
        </div>
    </aside>

    <script src="ai-agent.js"></script>

    <script>
        /**
         * Navigation Router
         * Listens for clicks on nav items and handles page redirection.
         */
        document.querySelector('.nav-container').addEventListener('click', (e) => {
            const btn = e.target.closest('.nav-item');
            if (!btn) return;

            const targetId = btn.getAttribute('data-target');
            if (targetId === 'welcome-view') {
                window.location.href = 'TED.php';
            } else if (['homepage-view', 'ielts-view', 'daily-talk-view'].includes(targetId)) {
                window.location.href = 'home.php';
            }
        });
    </script>
</body>
</html>
