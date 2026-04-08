<?php
/**
 * Word Garden - TED Talk Learning Module
 * * This module provides an immersive environment for students to watch TED talks,
 * toggle between subtitle modes, and engage with the Luna AI assistant.
 *
 * @author  Luna Development Team
 * @version 2.1.0
 */

session_start();
require_once 'db_connect.php';  

/* -------------------------------------------------------------------------
   1. AUTHENTICATION SHIELD
   ------------------------------------------------------------------------- */
// Redirect unauthorized users to the landing page to ensure data privacy.
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$nickname = $_SESSION['nickname'] ?? 'Learner';

/* -------------------------------------------------------------------------
   2. PAGINATION & VIEW STATE
   ------------------------------------------------------------------------- */
$limit   = 8; // Number of videos displayed per page
$page    = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$GET['page']) : 1;
$offset  = ($page - 1) * $limit;

// Fetch the total record count for pagination calculation.
$count_query  = "SELECT COUNT(*) as total FROM ted_talks";
$count_result = $conn->query($count_query);
$total_rows   = $count_result ? $count_result->fetch_assoc()['total'] : 0;
$total_pages  = ceil($total_rows / $limit);

/* -------------------------------------------------------------------------
   3. CORE LOGIC: LIST VS PLAYER MODE
   ------------------------------------------------------------------------- */
$ted_id      = isset($_GET['ted_id']) && is_numeric($_GET['ted_id']) ? (int)$_GET['ted_id'] : 0;
$current_talk = null;
$alt_version  = null; 
$back_page    = $page;

if ($ted_id > 0) {
    /**
     * PLAYER MODE
     * Fetch the specific video requested by the user.
     */
    $stmt = $conn->prepare("SELECT ted_id, title, speaker, subtitle_mode, video_url FROM ted_talks WHERE ted_id = ?");
    $stmt->bind_param("i", $ted_id);
    $stmt->execute();
    $current_talk = $stmt->get_result()->fetch_assoc();

    // Fallback if the video ID does not exist in the database.
    if (!$current_talk) {
        header("Location: TED.php");
        exit();
    }

    /**
     * SIBLING DETECTION
     * Find the opposite subtitle version (With/Without) for the current title.
     */
    $stmt_sib = $conn->prepare("SELECT ted_id, subtitle_mode FROM ted_talks WHERE title = ? AND subtitle_mode != ? LIMIT 1");
    $stmt_sib->bind_param("ss", $current_talk['title'], $current_talk['subtitle_mode']);
    $stmt_sib->execute();
    $sib_res = $stmt_sib->get_result();
    if ($sib_res->num_rows > 0) {
        $alt_version = $sib_res->fetch_assoc();
    }
} else {
    /**
     * LIST MODE
     * Retrieve a paginated list of videos for the grid display.
     */
    $stmt_list = $conn->prepare("SELECT ted_id, title, speaker, subtitle_mode, cover_url FROM ted_talks ORDER BY ted_id ASC LIMIT ? OFFSET ?");
    $stmt_list->bind_param("ii", $limit, $offset);
    $stmt_list->execute();
    $video_result = $stmt_list->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TED Talk Center - Word Garden</title>
    <style>
        /* Visual Identity: Premium Green Theme 
           Designed for high readability and focus.
        */
        :root {
            --primary-green: #1b4332;
            --accent-green: #40916c;
            --bg-soft: #f2f7f5;
            --shadow-sm: 0 10px 30px rgba(27, 67, 50, 0.08);
            --shadow-lg: 0 20px 40px rgba(27, 67, 50, 0.15);
        }

        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: var(--bg-soft);
            margin: 0; color: #2d3436;
        }

        /* Fixed Navigation Bar */
        .header-nav {
            width: 100%; height: 70px; background: #fff;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 50px; position: fixed; top: 0; z-index: 1000;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05); box-sizing: border-box;
        }
        .logo { font-size: 22px; font-weight: bold; color: var(--primary-green); text-decoration: none; }
        .nav-links a {
            text-decoration: none; color: #666; font-size: 14px; margin-left: 20px;
            padding: 8px 15px; border-radius: 8px; transition: 0.3s;
        }
        .nav-links a:hover, .nav-links a.active { color: var(--primary-green); background: #f0f7f4; }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #081c15 0%, #1b4332 100%);
            color: #fff; padding: 110px 20px 70px; text-align: center;
        }
        .hero h1 { margin: 0; font-size: 2.5rem; letter-spacing: 1px; }

        /* Main Content Container */
        .content-body { max-width: 1200px; margin: -50px auto 60px; padding: 0 20px; position: relative; z-index: 10; }

        /* Grid & Card System */
        .video-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px; }
        .card {
            background: #fff; border-radius: 20px; padding: 15px; text-align: center;
            box-shadow: var(--shadow-sm); transition: 0.4s ease; border: 1px solid transparent;
        }
        .card:hover { transform: translateY(-8px); box-shadow: var(--shadow-lg); border-color: var(--accent-green); }

        .thumb-wrapper {
            width: 100%; aspect-ratio: 16/10; background: #f0f0f0; border-radius: 15px;
            margin-bottom: 15px; position: relative; overflow: hidden; display: flex; align-items: center; justify-content: center;
        }
        .cover-img { position: absolute; width: 100%; height: 100%; object-fit: cover; z-index: 1; }
        .play-btn {
            position: relative; z-index: 2; width: 50px; height: 50px;
            background: rgba(255,255,255,0.9); border-radius: 50%;
            display: flex; align-items: center; justify-content: center; transition: 0.3s;
        }
        .card:hover .play-btn { background: var(--accent-green); color: #fff; transform: scale(1.1); }

        .badge { font-size: 11px; background: #eef7f2; color: var(--accent-green); padding: 4px 12px; border-radius: 20px; font-weight: bold; }

        /* Video Stage (Player View) */
        .player-stage { background: #fff; border-radius: 25px; padding: 40px; box-shadow: var(--shadow-sm); max-width: 900px; margin: 0 auto; }
        .iframe-container { width: 100%; aspect-ratio: 16/9; background: #000; border-radius: 15px; overflow: hidden; margin: 25px 0; }
        .iframe-container iframe { width: 100%; height: 100%; border: none; }

        /* Buttons & Pagination */
        .btn { padding: 12px 28px; border-radius: 50px; font-weight: bold; cursor: pointer; text-decoration: none; display: inline-block; transition: 0.3s; border: none; }
        .btn-primary { background: var(--primary-green); color: #fff; }
        .btn-accent { background: var(--accent-green); color: #fff; }
        .btn-action { background: #e63946; color: #fff; padding: 18px 45px; margin-top: 20px; }
        
        .pagination { margin-top: 40px; display: flex; justify-content: center; align-items: center; gap: 15px; }
        .pagination a { text-decoration: none; color: var(--primary-green); background: #fff; padding: 8px 20px; border-radius: 10px; font-weight: 600; box-shadow: var(--shadow-sm); }
    </style>
</head>
<body>

    <nav class="header-nav">
        <a href="home.php" class="logo">WORD GARDEN</a>
        <div class="nav-links">
            <a href="home.php">Home</a>
            <a href="TED.php" class="active">TED Talk</a>
            <a href="ielts.php">IELTS</a>
            <a href="vocabulary.php">Vocabulary</a>
            <a href="profile.php">Profile</a>
        </div>
    </nav>

    <header class="hero">
        <h1>TED Talk Center</h1>
        <p>ENLIGHTEN YOUR MIND • MASTER YOUR LISTENING</p>
    </header>

    <main class="content-body">
        
        <?php if (!$current_talk): ?>
            <div class="video-grid">
                <?php if ($video_result->num_rows > 0): ?>
                    <?php while ($row = $video_result->fetch_assoc()): ?>
                        <div class="card" onclick="window.location.href='TED.php?ted_id=<?= $row['ted_id'] ?>&page=<?= $page ?>'">
                            <div class="thumb-wrapper">
                                <?php if (!empty($row['cover_url'])): ?>
                                    <img src="<?= htmlspecialchars($row['cover_url']) ?>" class="cover-img">
                                <?php endif; ?>
                                <div class="play-btn">▶</div>
                            </div>
                            <h4 style="margin: 0 0 10px;"><?= htmlspecialchars($row['title']) ?></h4>
                            <span class="badge"><?= ($row['subtitle_mode'] === 'with_subtitle') ? 'Subtitles' : 'No Subtitles' ?></span>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align: center; width: 100%;">No talks found in the garden yet.</p>
                <?php endif; ?>
            </div>

            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="TED.php?page=<?= $page-1 ?>">Prev</a>
                <?php endif; ?>
                <span style="font-weight: 600;">Page <?= $page ?> / <?= $total_pages ?></span>
                <?php if ($page < $total_pages): ?>
                    <a href="TED.php?page=<?= $page+1 ?>">Next</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="player-stage">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <a href="TED.php?page=<?= $back_page ?>" class="btn btn-primary">← Gallery</a>
                    <?php if ($alt_version): ?>
                        <a href="TED.php?ted_id=<?= $alt_version['ted_id'] ?>&page=<?= $back_page ?>" class="btn btn-accent">
                            Switch to <?= ($alt_version['subtitle_mode'] === 'with_subtitle') ? 'Subtitled' : 'Original' ?>
                        </a>
                    <?php endif; ?>
                </div>

                <div style="text-align: center; margin-top: 30px;">
                    <h2 style="color: var(--primary-green); margin: 0;"><?= htmlspecialchars($current_talk['title']) ?></h2>
                    <p style="color: #666;">Speaker: <?= htmlspecialchars($current_talk['speaker'] ?? 'Unknown') ?></p>
                </div>

                <div class="iframe-container">
                    <?php
                    $url = $current_talk['video_url'];
                    // Logic to convert standard YouTube links to embeddable format.
                    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $url, $m)) {
                        $url = 'https://www.youtube.com/embed/' . $m[1];
                    }
                    ?>
                    <iframe src="<?= htmlspecialchars($url) ?>" allowfullscreen></iframe>
                </div>

                <div style="text-align: center;">
                    <button class="btn btn-action" onclick="window.location.href='practice.php?ted_id=<?= $current_talk['ted_id'] ?>'">
                        CHALLENGE COMPREHENSION
                    </button>
                </div>
            </div>
        <?php endif; ?>

    </main>

    <script src="ai-agent.js?v=<?= time() ?>"></script>

</body>
</html>
