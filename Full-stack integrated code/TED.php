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
// A. Get user info (for navbar avatar, keep consistency)
// ==========================================
$username = ''; $nickname = 'Student'; $db_avatar = '';
$stmt_u = $conn->prepare("SELECT username, nickname, avatar_url FROM users WHERE user_id = ?");
if ($stmt_u) {
    $stmt_u->bind_param("i", $user_id);
    $stmt_u->execute();
    $u_data = $stmt_u->get_result()->fetch_assoc();
    if ($u_data) {
        $username = $u_data['username'];
        $nickname = !empty($u_data['nickname']) ? $u_data['nickname'] : $username;
        $db_avatar = $u_data['avatar_url'];
    }
    $stmt_u->close();
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

// --- Pagination and data logic (retain original functionality) ---
$limit = 8;                                      
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$total_sql = "SELECT COUNT(*) as total FROM ted_talks";
$total_result = $conn->query($total_sql);
$total = $total_result ? $total_result->fetch_assoc()['total'] : 0;
$total_pages = ceil($total / $limit);

$ted_id = isset($_GET['ted_id']) && is_numeric($_GET['ted_id']) ? (int)$_GET['ted_id'] : 0;
$talk = null;
$sibling = null;
$back_page = $page;

if ($ted_id > 0) {
    $stmt = $conn->prepare("SELECT ted_id, title, speaker, subtitle_mode, video_url FROM ted_talks WHERE ted_id = ?");
    $stmt->bind_param("i", $ted_id);
    $stmt->execute();
    $talk = $stmt->get_result()->fetch_assoc();

    if (!$talk) { header("Location: TED.php"); exit(); }

    $stmt2 = $conn->prepare("SELECT ted_id, subtitle_mode FROM ted_talks WHERE title = ? AND subtitle_mode != ? LIMIT 1");
    $stmt2->bind_param("ss", $talk['title'], $talk['subtitle_mode']);
    $stmt2->execute();
    $sib_res = $stmt2->get_result();
    if ($sib_res->num_rows > 0) { $sibling = $sib_res->fetch_assoc(); }
} else {
    $stmt = $conn->prepare("SELECT ted_id, title, speaker, subtitle_mode, cover_url FROM ted_talks ORDER BY ted_id ASC LIMIT ? OFFSET ?");
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
    <title>TED Library - Spires Academy</title>
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

        body { margin: 0; padding: 0; font-family: 'Open Sans', sans-serif; background-color: var(--bg-light); color: var(--text-dark); }
        h1, h2, h3, h4 { font-family: 'PT Serif', Georgia, serif; letter-spacing: 0.5px; }

        /* ===== 1. Navbar (fully follow Oxford Style) ===== */
        .navbar { background-color: var(--oxford-blue); color: var(--white); display: flex; justify-content: space-between; align-items: center; padding: 0 40px; height: 80px; position: sticky; top: 0; z-index: 1000; box-shadow: 0 2px 10px rgba(0,0,0,0.2); }
        .navbar-left { display: flex; align-items: center; height: 100%; }
        .college-logo { height: 50px; width: auto; cursor: pointer; transition: transform 0.3s; }
        .college-logo:hover { transform: scale(1.02); }

        .navbar-links { display: flex; gap: 10px; list-style: none; margin: 0 0 0 40px; padding: 0; height: 100%; align-items: center; }
        .navbar-links > li { display: flex; align-items: center; position: relative; height: 100%; }
        .navbar-links a { 
            color: #ffffff; 
            text-decoration: none; 
            font-family: 'Playfair Display', serif;
            font-size: 16px; 
            font-weight: 800; 
            padding: 0 20px; 
            height: 100%; 
            display: flex; 
            align-items: center; 
            text-transform: uppercase; 
            letter-spacing: 1.8px; 
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.6);
            transition: all 0.3s ease; 
            -webkit-font-smoothing: antialiased;
        }
        .navbar-links a:hover { color: var(--oxford-gold); background-color: rgba(255, 255, 255, 0.05); }

        /* Dropdown menu */
        .dropdown-menu { display: none; position: absolute; top: 80px; left: 0; background-color: var(--oxford-blue-light); min-width: 220px; box-shadow: 0 8px 16px rgba(0,0,0,0.2); list-style: none; padding: 0; margin: 0; border-top: 2px solid var(--oxford-gold); }
        .navbar-links li:hover .dropdown-menu { display: block; }
        .dropdown-menu li a { font-family: 'Playfair Display', serif; padding: 15px 20px; text-transform: none; color: #e0e0e0 !important; font-size: 15px; font-weight: 400; height: auto; display: block; }
        .dropdown-menu li a:hover { background-color: var(--oxford-blue) !important; color: var(--white) !important; padding-left: 25px; }

        /* Top-right user avatar */
        .navbar-right { display: flex; align-items: center; gap: 10px; cursor: pointer; height: 100%; position: relative; }
        .user-avatar-img { width: 40px; height: 40px; border-radius: 50%; border: 2px solid var(--oxford-gold); object-fit: cover; }
        .user-avatar-placeholder { width: 40px; height: 40px; border-radius: 50%; background-color: var(--oxford-gold); color: var(--oxford-blue); display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 18px; border: 2px solid var(--oxford-gold); box-sizing: border-box; line-height: 1; }
        
        .navbar-right .dropdown-menu { 
            background-color: var(--white); 
            border-top: none; 
            border-radius: 0 0 8px 8px; 
            overflow: hidden;
            font-family: 'Playfair Display', serif; 
        }
        .navbar-right:hover .dropdown-menu { display: block; right: 0; left: auto; }
        .navbar-right .dropdown-menu li a { color: var(--oxford-blue) !important; font-weight: 700; letter-spacing: 0.5px; }
        .navbar-right .dropdown-menu li a:hover { background-color: #f8fafc !important; color: var(--oxford-gold) !important; }

        /* ===== 2. Hero section (Oxford-inspired redesign) ===== */
        .hero {
            background: url('hero_bg2.png') center/cover no-repeat; 
            color: var(--white); 
            text-align: center; 
            padding: 140px 20px;
            text-shadow: 0 2px 8px rgba(0,0,0,0.8);
        }
        .hero h1 { 
            font-family: 'Playfair Display', serif;
            font-size: 5rem; 
            font-weight: 800; 
            margin: 0 0 20px; 
            text-transform: uppercase; 
            letter-spacing: 5px; 
            text-shadow: 2px 4px 10px rgba(0, 0, 0, 0.8);
        }
        .hero p { 
            font-family: 'Playfair Display', serif; 
            font-size: 1.4rem; 
            font-weight: 400; 
            font-style: italic; 
            max-width: 800px; 
            margin: 0 auto; 
            text-shadow: 1px 2px 5px rgba(0, 0, 0, 0.8); 
        }

        /* ===== 3. Video grid and content ===== */
        .main-content { max-width: 1200px; margin: -50px auto 60px; padding: 0 20px; position: relative; z-index: 10; }
        .video-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 30px; }
        
        .video-card {
            background: var(--white); border-radius: 8px; overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05); transition: all 0.4s ease;
            cursor: pointer; border-top: 4px solid var(--oxford-gold);
        }
        .video-card:hover { transform: translateY(-10px); box-shadow: 0 15px 40px rgba(0,0,0,0.12); border-top-color: var(--oxford-blue); }

        .video-placeholder { width: 100%; aspect-ratio: 16/9; background: #000; position: relative; overflow: hidden; }
        .video-cover { width: 100%; height: 100%; object-fit: cover; opacity: 0.85; transition: opacity 0.3s; }
        .video-card:hover .video-cover { opacity: 1; }

        .play-icon-wrapper {
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
            width: 50px; height: 50px; background: rgba(196, 166, 97, 0.9);
            border-radius: 50%; display: flex; justify-content: center; align-items: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3); transition: 0.3s;
        }
        .play-icon { color: var(--oxford-blue); font-size: 20px; margin-left: 3px; }

        .card-info { padding: 25px; text-align: center; }
        .card-info h4 { margin: 0 0 15px 0; color: var(--oxford-blue); font-size: 1.3rem; line-height: 1.4; height: 3em; overflow: hidden; font-weight: 800; }
        .subtitle-badge {
            font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;
            color: var(--oxford-gold); border: 1px solid var(--oxford-gold); padding: 4px 12px; border-radius: 2px; display: inline-block;
        }

        /* Player container */
        .player-container { background: var(--white); border-radius: 8px; padding: 50px; box-shadow: 0 20px 50px rgba(0,0,0,0.05); max-width: 1000px; margin: 0 auto; border-top: 4px solid var(--oxford-blue); }
        .video-player-box { width: 100%; aspect-ratio: 16 / 9; background: #000; border-radius: 4px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.2); margin: 35px 0; }
        .video-player-box iframe { width: 100%; height: 100%; border: none; }

        /* Button system */
        .btn { padding: 12px 28px; border-radius: 4px; font-weight: 800; cursor: pointer; transition: 0.3s; text-decoration: none; display: inline-block; text-transform: uppercase; font-family: 'Playfair Display', serif; letter-spacing: 1.5px; font-size: 13px; }
        .btn-primary { background: var(--oxford-blue); color: var(--white); border: 1px solid var(--oxford-blue); }
        .btn-primary:hover { background: var(--oxford-blue-light); }
        .btn-accent { background: var(--white); color: var(--oxford-blue); border: 1px solid var(--oxford-blue); }
        .btn-practice { background: var(--oxford-gold); color: var(--oxford-blue); margin-top: 30px; padding: 18px 50px; font-size: 16px; width: 100%; box-sizing: border-box; }
        .btn-practice:hover { background: var(--oxford-gold-light); }

        /* Pagination */
        .pagination { margin-top: 60px; display: flex; justify-content: center; align-items: center; gap: 20px; }
        .pagination a { text-decoration: none; color: var(--oxford-blue); background: var(--white); padding: 12px 30px; border-radius: 4px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); font-weight: 800; font-family: 'Playfair Display', serif; transition: 0.3s; border: 1px solid var(--border-color); }
        .pagination a:hover { background: var(--oxford-blue); color: var(--white); border-color: var(--oxford-blue); }

      
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
                        <li><a href="listening.php">Listening Center</a></li>
                        <li><a href="reading.php">Reading Room</a></li>
                        <li><a href="emma_server/speakAI.php">Emma Speaking</a></li>
                        <li><a href="writing.php">Writing Lab</a></li>
                       
                    </ul>
                </li>
                <li class="dropdown">
                    <a href="#">Games ▾</a>
                    <ul class="dropdown-menu">
                        <li><a href="vocabulary_game.php">Vocabulary Game</a></li>
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
                    <div style="color:var(--text-light); font-size:12px; margin-bottom:5px;">Signed in as</div>
                    <div style="color:var(--oxford-blue); font-weight:bold; font-size:16px; text-transform:uppercase;"><?php echo htmlspecialchars($nickname); ?></div>
                </li>
                <li><a href="profile.php">My Profile</a></li>
                <li><a href="logout.php" style="color:#dc3545 !important; font-weight: 600;">Sign Out</a></li>
            </ul>
        </div>
    </nav>

    <header class="hero">
        <h1>TED Library</h1>
        <p>Illuminating Ideas • Refined Comprehension</p>
    </header>

    <main class="main-content">
        
        <?php if ($ted_id === 0): ?>
            <div class="video-grid">
                <?php if ($video_result->num_rows > 0): ?>
                    <?php while ($row = $video_result->fetch_assoc()): 
                        $mode_text = ($row['subtitle_mode'] === 'with_subtitle') ? 'En Subtitles' : 'Original Audio';
                    ?>
                    <div class="video-card" onclick="window.location.href='TED.php?ted_id=<?= $row['ted_id'] ?>&page=<?= $page ?>'">
                        <div class="video-placeholder">
                            <?php if (!empty($row['cover_url'])): ?>
                                <img src="<?= htmlspecialchars($row['cover_url']) ?>" alt="Cover" class="video-cover">
                            <?php endif; ?>
                            <div class="play-icon-wrapper"><span class="play-icon">▶</span></div>
                        </div>
                        <div class="card-info">
                            <h4><?= htmlspecialchars($row['title']) ?></h4>
                            <div class="subtitle-badge"><?= $mode_text ?></div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="grid-column: 1/-1; text-align: center; color: var(--text-light); font-family: 'Playfair Display', serif; font-size: 1.2rem;">
                        No resources found in the library currently.
                    </p>
                <?php endif; ?>
            </div>

            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="TED.php?page=<?= $page-1 ?>">« Previous</a>
                <?php endif; ?>
                <span style="font-weight: 800; color: var(--oxford-blue); font-family: 'Playfair Display', serif;">Page <?= $page ?> / <?= $total_pages ?></span>
                <?php if ($page < $total_pages): ?>
                    <a href="TED.php?page=<?= $page+1 ?>">Next »</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="player-container">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 20px;">
                    <a href="TED.php?page=<?= $back_page ?>" class="btn btn-accent">← Return to Library</a>
                    <?php if ($sibling): ?>
                        <a href="TED.php?ted_id=<?= $sibling['ted_id'] ?>&page=<?= $back_page ?>" class="btn btn-primary">
                            Switch to <?= $sibling['subtitle_mode'] === 'with_subtitle' ? 'Subtitled' : 'Original' ?>
                        </a>
                    <?php endif; ?>
                </div>

                <div style="text-align: center; margin-top: 40px;">
                    <h2 style="color: var(--oxford-blue); margin: 0; font-size: 2.2rem; font-weight:800;"><?= htmlspecialchars($talk['title']) ?></h2>
                    <p style="color: var(--oxford-gold); margin: 15px 0; font-family: 'Playfair Display', serif; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; font-size: 0.9rem;">
                        Speaker: <?= htmlspecialchars($talk['speaker'] ?? 'Guest Scholar') ?>
                    </p>
                </div>

                <div class="video-player-box">
                    <?php
                    $video_src = htmlspecialchars($talk['video_url']);
                    if (preg_match('/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/', $talk['video_url'], $m) ||
                        preg_match('/youtu\.be\/([a-zA-Z0-9_-]+)/', $talk['video_url'], $m)) {
                        $video_src = 'https://www.youtube.com/embed/' . $m[1] . '?rel=0&modestbranding=1';
                    }
                    ?>
                    <iframe src="<?= $video_src ?>" allowfullscreen></iframe>
                </div>

                <div style="text-align: center;">
                    <button class="btn btn-practice" onclick="window.location.href='practice.php?ted_id=<?= $talk['ted_id'] ?>'">
                        Proceed to Comprehensive Practice
                    </button>
                </div>
            </div>
        <?php endif; ?>

    </main>

    <div class="ai-assistant">
        <div class="chat-bubble">Greetings, <?= htmlspecialchars($nickname) ?>. Ready for an intellectual discovery?</div>
        <div class="ai-icon-circle">AI</div>
    </div>

</body>
</html>