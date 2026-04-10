<?php
session_start();
include 'db_connect.php';

// 1. 登录检测
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// 2. 获取当前选择的口音 (Accent)
$selected_accent = isset($_GET['accent']) ? $_GET['accent'] : null;

// 3. 数据处理逻辑
if (!$selected_accent) {
    // --- 状态 A: 未选择口音，查询数据库中所有的口音种类 ---
    // 假设你在 daily_talks 表中新加的字段名为 'accents'
    $accent_sql = "SELECT DISTINCT accents FROM daily_talks WHERE accents IS NOT NULL AND accents != ''";
    $accent_res = $conn->query($accent_sql);
    $accent_list = [];
    if ($accent_res) {
        while ($row = $accent_res->fetch_assoc()) {
            $accent_list[] = $row['accents'];
        }
    }
} else {
    // --- 状态 B: 已选择口音，显示对应的视频列表 ---
    $accent_param = $conn->real_escape_string($selected_accent);
    
    // 分页逻辑
    $limit = 6;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $limit;

    // 根据口音筛选 title
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
    <title>Daily Talk - Word Garden</title>
    <style>
        /* ===== 统一高级绿调配色 ===== */
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
        }

        /* 导航栏 */
        .nav-header {
            width: 100%; height: 70px; background: white;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 50px; box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            position: fixed; top: 0; z-index: 1000; box-sizing: border-box;
        }
        .nav-logo { font-size: 22px; font-weight: bold; color: var(--primary-green); text-decoration: none; }
        .nav-links { display: flex; gap: 20px; }
        .nav-links a { text-decoration: none; color: #666; font-size: 14px; font-weight: 500; padding: 5px 12px; border-radius: 8px; transition: 0.3s; }
        .nav-links a:hover, .nav-links a.active { color: var(--primary-green); background: #f0f7f4; }

        /* Banner */
        .hero-mini {
            background: linear-gradient(135deg, #081c15 0%, #1b4332 100%);
            color: white; padding: 110px 20px 70px; text-align: center;
        }
        .hero-mini h1 { margin: 0; font-size: 2.4rem; letter-spacing: 1px; }

        .main-content {
            max-width: 1200px; margin: -50px auto 60px; padding: 0 20px;
            position: relative; z-index: 10;
        }

        /* 口音选择卡片 */
        .accent-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
        }
        .accent-card {
            background: white; border-radius: 20px; padding: 50px 30px;
            text-align: center; cursor: pointer; box-shadow: var(--card-shadow);
            transition: 0.4s ease; border: 1px solid transparent;
        }
        .accent-card:hover { transform: translateY(-10px); box-shadow: var(--card-shadow-hover); border-color: var(--accent-green); }
        .accent-icon { font-size: 40px; margin-bottom: 15px; display: block; }
        .accent-name { font-size: 1.5rem; font-weight: 600; color: var(--primary-green); text-transform: capitalize; }

        /* 视频网格 */
        .video-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 30px; }
        .video-card { 
            background: white; border-radius: 20px; padding: 15px; cursor: pointer; 
            box-shadow: var(--card-shadow); transition: 0.4s; text-align: center;
        }
        .video-card:hover { transform: translateY(-8px); box-shadow: var(--card-shadow-hover); }
        .video-card img { width: 100%; height: 180px; object-fit: cover; border-radius: 15px; }
        .video-title { font-size: 1.2rem; font-weight: 600; color: var(--primary-green); margin: 15px 0 5px; }

        /* 返回按钮 */
        .back-nav { margin-bottom: 25px; }
        .btn-back {
            text-decoration: none; color: var(--primary-green); font-weight: 600;
            background: white; padding: 10px 20px; border-radius: 10px; box-shadow: var(--card-shadow);
            display: inline-block; transition: 0.3s;
        }
        .btn-back:hover { background: var(--primary-green); color: white; }

        /* 播放器弹窗 */
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); justify-content: center; align-items: center; flex-direction: column; }
        .modal-content { width: 90%; max-width: 900px; position: relative; text-align: center; }
        .close-btn { position: absolute; top: -50px; right: 0; color: white; font-size: 40px; cursor: pointer; }
        video { width: 100%; border-radius: 15px; background: #000; box-shadow: 0 0 50px rgba(64,145,108,0.3); }
        .sub-controls { margin-top: 30px; }
        .sub-btn { padding: 12px 30px; margin: 0 10px; border: 2px solid var(--accent-green); background: transparent; color: white; border-radius: 30px; cursor: pointer; font-weight: bold; transition: 0.3s; }
        .sub-btn.active { background: var(--accent-green); }

        .pagination { margin: 40px 0; display: flex; justify-content: center; gap: 10px; }
        .page-link { text-decoration: none; padding: 10px 18px; border-radius: 10px; background: white; color: #666; box-shadow: var(--card-shadow); }
        .page-link.active { background: var(--primary-green); color: white; }
    </style>
</head>
<body>

    <nav class="nav-header">
        <a href="home.php" class="nav-logo">Word Garden</a>
        <div class="nav-links">
            <a href="home.php">Home</a>
            <a href="TED.php">TED Talk</a>
            <a href="ielts.php">IELTS</a>
            <a href="daily_decryption.php" class="active">Daily Talk</a>
            <a href="vocabulary.php">Vocabulary</a>
            <a href="calendar.php">Calendar</a>
            <a href="profile.php">Profile</a>
        </div>
    </nav>

    <header class="hero-mini">
        <h1>Daily Talk</h1>
        <p><?php echo $selected_accent ? "Explore ".ucfirst($selected_accent)." English" : "Choose your preferred accent"; ?></p>
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
                                <span class="accent-icon">🌍</span>
                                <div class="accent-name"><?php echo htmlspecialchars($accent); ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        <?php else: ?>
            <div class="back-nav">
                <a href="daily_talk.php" class="btn-back">← Back to Accent Selection</a>
            </div>

            <div class="video-grid">
                <?php if (empty($video_list)): ?>
                    <p style="text-align:center; grid-column: 1/-1;">No videos found for this accent.</p>
                <?php else: ?>
                    <?php foreach ($video_list as $video): ?>
                        <div class="video-card" onclick="openPlayer(<?php echo htmlspecialchars(json_encode($video), ENT_QUOTES, 'UTF-8'); ?>)">
                            <img src="<?php echo $video['cover_url'] ?: 'static/images/default_cover.png'; ?>">
                            <div class="video-title"><?php echo htmlspecialchars($video['title']); ?></div>
                            <span style="font-size:12px; color:#999;">Speaker: <?php echo htmlspecialchars($video['speaker']); ?></span>
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
            <video id="player" controls></video>
            <div class="sub-controls">
                <button class="sub-btn" id="btn-without" onclick="switchVer('without_subtitle')">Without subtitle</button>
                <button class="sub-btn" id="btn-with" onclick="switchVer('with_subtitle')">With subtitle</button>
            </div>
            <h2 id="video-title-display" style="color: white; margin-top: 20px; font-weight: 400;"></h2>
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
            video.load(); video.play();
            document.getElementById('btn-with').classList.remove('active');
            document.getElementById('btn-without').classList.remove('active');
            const targetBtn = document.getElementById(mode === 'with_subtitle' ? 'btn-with' : 'btn-without');
            if(targetBtn) targetBtn.classList.add('active');
        }
        function closePlayer() {
            const video = document.getElementById('player');
            video.pause();
            document.getElementById('videoModal').style.display = 'none';
        }
    </script>
</body>
</html>