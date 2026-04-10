<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

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

$avatar_html = '';
$first_letter = strtoupper(substr($username ? $username : 'U', 0, 1));
if (!empty($db_avatar)) {
    $avatar_html = '<img src="' . htmlspecialchars($db_avatar) . '" alt="Avatar" class="user-avatar-img" onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'flex\';">';
    $avatar_html .= '<div class="user-avatar-placeholder" style="display:none;">' . htmlspecialchars($first_letter) . '</div>';
} else {
    $avatar_html = '<div class="user-avatar-placeholder">' . htmlspecialchars($first_letter) . '</div>';
}

$selected_accent = isset($_GET['accent']) ? $_GET['accent'] : null;

if (!$selected_accent) {
    $accent_sql = "SELECT DISTINCT accents FROM daily_talks WHERE accents IS NOT NULL AND accents != ''";
    $accent_res = $conn->query($accent_sql);
    $accent_list = [];
    if ($accent_res) {
        while ($row = $accent_res->fetch_assoc()) {
            $accent_list[] = $row['accents'];
        }
    }
} else {
    $accent_param = $conn->real_escape_string($selected_accent);
    $limit = 6;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $limit;

    $title_sql = "SELECT DISTINCT title FROM daily_talks WHERE accents = '$accent_param' ORDER BY CAST(title AS UNSIGNED) ASC LIMIT $limit OFFSET $offset";
    $title_res = $conn->query($title_sql);
    $page_titles = [];
    while($t = $title_res->fetch_assoc()) { 
        $page_titles[] = $conn->real_escape_string($t['title']); 
    }

    if (empty($page_titles)) { 
        $video_list = []; $total_pages = 0; 
    } else {
        $title_list = "'" . implode("','", $page_titles) . "'";
        $sql = "SELECT * FROM daily_talks WHERE title IN ($title_list) AND accents = '$accent_param' ORDER BY CAST(title AS UNSIGNED) ASC";
        $result = $conn->query($sql);

        $temp_list = [];
        while($row = $result->fetch_assoc()) {
            $t = trim($row['title']); 
            if (!isset($temp_list[$t])) {
                $temp_list[$t] = [
                    'title' => $t,
                    'speaker' => $row['speaker'],
                    'cover_url' => $row['cover_url'],
                    'versions' => []
                ];
            }
            $mode = trim($row['subtitle_mode']); 
            $temp_list[$t]['versions'][$mode] = trim($row['video_url']);
        }
        $video_list = array_values($temp_list); 
        
        $total_rows_res = $conn->query("SELECT COUNT(DISTINCT title) as total FROM daily_talks WHERE accents = '$accent_param'");
        $total_pages = ceil($total_rows_res->fetch_assoc()['total'] / $limit);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Talk - Spires Academy</title>
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
            --card-shadow: 0 10px 30px rgba(0, 33, 71, 0.08);
            --card-shadow-hover: 0 20px 40px rgba(0, 33, 71, 0.15);
        }

        body { margin: 0; padding: 0; font-family: 'Open Sans', Arial, sans-serif; background-color: var(--bg-light); color: var(--text-dark); }
        h1, h2, h3, h4 { font-family: 'PT Serif', Georgia, serif; letter-spacing: 0.5px; }

     
        .navbar { background-color: var(--oxford-blue); color: var(--white); display: flex; justify-content: space-between; align-items: center; padding: 0 40px; height: 80px; position: sticky; top: 0; z-index: 1000; box-shadow: 0 2px 10px rgba(0,0,0,0.2); }
        .navbar-left { display: flex; align-items: center; height: 100%; }
        .college-logo { height: 50px; width: auto; cursor: pointer; transition: transform 0.3s; }
        .college-logo:hover { transform: scale(1.02); }
        .navbar-links { display: flex; gap: 10px; list-style: none; margin: 0 0 0 40px; padding: 0; height: 100%; align-items: center; }
        .navbar-links > li { display: flex; align-items: center; position: relative; height: 100%; }
        .navbar-links a { color: #ffffff; text-decoration: none; font-family: 'Playfair Display', serif; font-size: 16px; font-weight: 800; padding: 0 20px; height: 100%; display: flex; align-items: center; text-transform: uppercase; letter-spacing: 1.8px; text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.6); transition: all 0.3s ease; -webkit-font-smoothing: antialiased; }
        .navbar-links a:hover { color: var(--oxford-gold); background-color: rgba(255, 255, 255, 0.05); }
        
        .dropdown-menu { display: none; position: absolute; top: 80px; left: 0; background-color: var(--oxford-blue-light); min-width: 220px; box-shadow: 0 8px 16px rgba(0,0,0,0.2); list-style: none !important; padding: 0; margin: 0; border-top: 2px solid var(--oxford-gold); }
        .dropdown-menu li { list-style: none !important; margin: 0; padding: 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .dropdown-menu li:last-child { border-bottom: none; }
        .dropdown-menu li a { color: #e0e0e0 !important; padding: 15px 20px; text-transform: none; justify-content: flex-start; width: 100%; box-sizing: border-box; text-decoration: none !important; display: block; font-weight: 400; height: auto; }
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

        
        .main-content { max-width: 1200px; margin: -50px auto 60px; padding: 0 20px; position: relative; z-index: 10; }


        .accent-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 25px; }
        .accent-card {
            background: white; border-radius: 8px; padding: 50px 30px;
            text-align: center; cursor: pointer; box-shadow: var(--card-shadow);
            transition: 0.4s ease; border-top: 4px solid var(--oxford-gold);
        }
        .accent-card:hover { transform: translateY(-10px); box-shadow: var(--card-shadow-hover); border-top-color: var(--oxford-blue); }
        .accent-icon { font-size: 40px; margin-bottom: 15px; display: block; }
        .accent-name { font-size: 1.5rem; font-weight: 700; color: var(--oxford-blue); text-transform: capitalize; font-family: 'Playfair Display', serif; }

       
        .video-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 30px; }
        .video-card { 
            background: white; border-radius: 8px; padding: 15px; cursor: pointer; 
            box-shadow: var(--card-shadow); transition: 0.4s; text-align: center;
            border: 1px solid var(--border-color);
        }
        .video-card:hover { transform: translateY(-8px); box-shadow: var(--card-shadow-hover); border-color: var(--oxford-blue-light); }
        .video-card img { width: 100%; height: 180px; object-fit: cover; border-radius: 4px; }
        .video-title { font-size: 1.2rem; font-weight: 600; color: var(--oxford-blue); margin: 15px 0 5px; }

   
        .back-nav { margin-bottom: 25px; }
        .btn-back {
            text-decoration: none; color: var(--white); font-weight: 600;
            background: var(--oxford-blue); padding: 10px 24px; border-radius: 4px; box-shadow: var(--card-shadow);
            display: inline-block; transition: 0.3s; text-transform: uppercase; font-size: 13px; letter-spacing: 1px;
        }
        .btn-back:hover { background: var(--oxford-gold); }

       
        .pagination { margin: 40px 0; display: flex; justify-content: center; gap: 10px; }
        .page-link { text-decoration: none; padding: 10px 18px; border-radius: 4px; background: white; color: var(--oxford-blue); border: 1px solid var(--border-color); font-weight: bold; }
        .page-link.active { background: var(--oxford-blue); color: white; border-color: var(--oxford-blue); }
        .page-link:hover:not(.active) { background: #f8fafc; }

       
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,21,45,0.95); justify-content: center; align-items: center; flex-direction: column; }
        .modal-content { width: 90%; max-width: 1000px; position: relative; text-align: center; }
        .close-btn { position: absolute; top: -40px; right: 0; color: white; font-size: 40px; cursor: pointer; transition: color 0.3s; }
        .close-btn:hover { color: var(--oxford-gold); }
        
        
        video { 
            width: 100%; 
            max-height: 75vh; 
            object-fit: contain;
            border-radius: 8px; 
            background: #000; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.5); 
        }
        
        .sub-controls { margin-top: 25px; }
        .sub-btn { padding: 10px 25px; margin: 0 10px; border: 2px solid var(--oxford-gold); background: transparent; color: white; border-radius: 4px; cursor: pointer; font-weight: bold; transition: 0.3s; text-transform: uppercase; font-size: 13px; letter-spacing: 1px; }
        .sub-btn.active { background: var(--oxford-gold); color: var(--oxford-blue); }
        .sub-btn:hover { background: rgba(196, 166, 97, 0.2); }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="navbar-left">
            <a href="home.php"><img src="college_logo.png" alt="Spires Academy Logo" class="college-logo"></a>
            
            <ul class="navbar-links">
                <li><a href="home.php">Home</a></li>
                <li class="dropdown">
                    <a href="#">Study ▾</a>
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
                    <div style="color:var(--oxford-blue); font-weight:bold; font-size:16px;"><?php echo htmlspecialchars($nickname); ?></div>
                </li>
                <li><a href="profile.php">My Profile</a></li>
                <li><a href="logout.php" style="color:#dc3545 !important; font-weight: 600;">Sign Out</a></li>
            </ul>
        </div>
    </nav>

    <header class="hero">
        <h1>DAILY TALK</h1>
        <p><?php echo $selected_accent ? "Explore ".ucfirst(htmlspecialchars($selected_accent))." Nuances" : "Explore Comprehensive-Accent Nuances"; ?></p>
    </header>

    <main class="main-content">
        <?php if (!$selected_accent): ?>
            <div class="accent_section">
                <div class="accent-grid">
                    <?php if (empty($accent_list)): ?>
                        <p style="text-align:center; grid-column: 1/-1;">No accents available yet.</p>
                    <?php else: ?>
                        <?php foreach ($accent_list as $accent): ?>
                          <div class="accent-card" onclick="location.href='?accent=<?php echo urlencode($accent); ?>'">
                            <div class="accent-icon">
                                <img src="<?php 
                                    $flag_map = [
                                        'american' => 'us.png', 'british' => 'gb.png', 'australian' => 'au.png',
                                        'chinese' => 'cn.png', 'arabic' => 'eg.png', 'italian' => 'it.png',
                                        'japanese' => 'jp.png', 'russian' => 'ru.png', 'comprehensive-accent' => 'globe.png',
                                    ];
                                    $key = strtolower(trim($accent));
                                    $img_file = 'default.png';
                                    foreach ($flag_map as $keyword => $file) {
                                        if (strpos($key, $keyword) !== false) {
                                            $img_file = $file;
                                            break;
                                        }
                                    }
                                    echo $img_file;
                                ?>" alt="<?php echo htmlspecialchars($accent); ?>" 
                                style="width: 95px; height: 63.33px; border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                            </div>
                            <div class="accent-name"><?php echo htmlspecialchars($accent); ?></div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        <?php else: ?>
            <div class="back-nav">
                <a href="daily_talk.php" class="btn-back">← Back to Selection</a>
            </div>

            <div class="video-grid">
                <?php if (empty($video_list)): ?>
                    <p style="text-align:center; grid-column: 1/-1;">No videos found for this accent.</p>
                <?php else: ?>
                    <?php foreach ($video_list as $video): ?>
                        <div class="video-card" onclick="openPlayer(<?php echo htmlspecialchars(json_encode($video), ENT_QUOTES, 'UTF-8'); ?>)">
                            <img src="<?php echo $video['cover_url'] ?: 'static/images/default_cover.png'; ?>">
                            <div class="video-title"><?php echo htmlspecialchars($video['title']); ?></div>
                            <span style="font-size:13px; color:var(--text-light); font-weight:600;">Speaker: <?php echo htmlspecialchars($video['speaker']); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?accent=<?php echo urlencode($selected_accent); ?>&page=<?php echo $i; ?>" class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </main>

    <div id="videoModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closePlayer()">&times;</span>
            <video id="player" controls preload="metadata" playsinline></video>
            <div class="sub-controls">
                <button class="sub-btn" id="btn-without" onclick="switchVer('without_subtitle')">Without Subtitle</button>
                <button class="sub-btn" id="btn-with" onclick="switchVer('with_subtitle')">With Subtitle</button>
            </div>
            <h2 id="video-title-display" style="color: white; margin-top: 20px; font-weight: 400; font-family: 'Playfair Display', serif; letter-spacing: 1px;"></h2>
        </div>
    </div>

    <script>
        let activeData = null;
        function openPlayer(data) {
            activeData = data;
            document.getElementById('videoModal').style.display = 'flex';
            document.getElementById('video-title-display').innerText = data.title;
            if (data.versions['without_subtitle']) { switchVer('without_subtitle'); } 
            else if (data.versions['with_subtitle']) { switchVer('with_subtitle'); }
        }
        function switchVer(mode) {
            const video = document.getElementById('player');
            video.src = activeData.versions[mode];
            video.load(); 
            
            video.play();
            document.getElementById('btn-with').classList.remove('active');
            document.getElementById('btn-without').classList.remove('active');
            const targetBtn = document.getElementById(mode === 'with_subtitle' ? 'btn-with' : 'btn-without');
            if(targetBtn) targetBtn.classList.add('active');
        }
        function closePlayer() {
            const video = document.getElementById('player');
            video.pause();
            video.src = "";
            document.getElementById('videoModal').style.display = 'none';
        }
    </script>
</body>
</html>