<?php
/**
 * Daily Talk Module
 * Handles grouped video content (with/without subtitles) in a modal-based player.
 */
session_start();
include 'db_connect.php';

// --- Configuration ---
$limit = 6;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// --- Data Fetching Logic ---
// 1. Get unique titles first to handle grouping correctly across pagination
$title_sql = "SELECT DISTINCT title FROM daily_talks ORDER BY CAST(title AS UNSIGNED) ASC LIMIT $limit OFFSET $offset";
$title_res = $conn->query($title_sql);
$page_titles = [];

while($t = $title_res->fetch_assoc()) { 
    $page_titles[] = $conn->real_escape_string($t['title']); 
}

$video_list = [];
$total_pages = 0;

if (!empty($page_titles)) { 
    // 2. Fetch all versions for the titles we found
    $title_list = "'" . implode("','", $page_titles) . "'";
    $sql = "SELECT * FROM daily_talks WHERE title IN ($title_list) ORDER BY CAST(title AS UNSIGNED) ASC";
    $result = $conn->query($sql);

    $temp_list = [];
    while($row = $result->fetch_assoc()) {
        $t = trim($row['title']); 

        // Grouping logic: create a container for the talk if it doesn't exist
        if (!isset($temp_list[$t])) {
            $temp_list[$t] = [
                'title' => $t,
                'speaker' => $row['speaker'],
                'cover_url' => $row['cover_url'],
                'versions' => []
            ];
        }
        
        // Map the specific version (with/without subs) to its URL
        $mode = trim($row['subtitle_mode']); 
        $temp_list[$t]['versions'][$mode] = trim($row['video_url']);
    }
    
    // Flatten the associative array for easier frontend looping
    $video_list = array_values($temp_list); 

    // 3. Calculate pagination based on unique titles
    $total_rows_res = $conn->query("SELECT COUNT(DISTINCT title) as total FROM daily_talks");
    $total_pages = ceil($total_rows_res->fetch_assoc()['total'] / $limit);
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Word Garden - Daily Talk</title>
    <style>
        :root { --main-green: #a3d977; --dark-green: #5a8a31; --bg: #fdfdf2; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: var(--bg); margin: 0; }

        /* Navigation & Layout */
        .top-bar {
            width: 100%; height: 70px; background: white; 
            display: flex; align-items: center; padding: 0 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05); position: fixed; top: 0; z-index: 100;
            box-sizing: border-box;
        }
        .back-home-btn {
            text-decoration: none; color: var(--dark-green); font-weight: bold;
            display: flex; align-items: center; gap: 8px; font-size: 18px;
            padding: 8px 15px; border-radius: 12px; transition: 0.3s;
        }
        .back-home-btn:hover { background: #f1f8e9; }

        .main-content { display: flex; flex-direction: column; align-items: center; padding-top: 100px; min-height: 100vh; }

        /* Video Grid Component */
        .video-grid { 
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; 
            width: 90%; max-width: 1100px; margin: 40px auto; 
        }

        .video-card { 
            background: white; border-radius: 25px; padding: 15px; cursor: pointer; 
            border: 2px solid transparent; box-shadow: 0 10px 25px rgba(0,0,0,0.05); 
            transition: all 0.3s; text-align: center;
        }
        .video-card:hover { border-color: var(--main-green); transform: translateY(-8px); }
        .video-card img { width: 100%; height: 160px; object-fit: cover; border-radius: 20px; }
        .video-title { font-size: 22px; font-weight: bold; color: #333; margin: 15px 0 5px; }
        
        .status-tag { font-size: 12px; color: #fff; background: var(--main-green); padding: 4px 12px; border-radius: 8px; display: inline-block; }

        /* Overlay Modal Player */
        .modal { 
            display: none; position: fixed; z-index: 1000; left: 0; top: 0; 
            width: 100%; height: 100%; background: rgba(0,0,0,0.9); 
            justify-content: center; align-items: center; flex-direction: column;
        }
        .modal-content { width: 85%; max-width: 900px; position: relative; text-align: center; }
        .close-btn { position: absolute; top: -50px; right: 0; color: white; font-size: 40px; cursor: pointer; }
        video { width: 100%; border-radius: 15px; background: #000; box-shadow: 0 0 40px rgba(163,217,119,0.3); }

        .sub-controls { margin-top: 30px; }
        .sub-btn { 
            padding: 12px 30px; margin: 0 15px; border: 2px solid var(--main-green); 
            background: rgba(255,255,255,0.1); color: white; border-radius: 30px; 
            cursor: pointer; font-weight: bold; transition: 0.3s; 
        }
        .sub-btn.active { background: var(--main-green); border-color: var(--main-green); }

        /* Pagination Component */
        .pagination { margin: 20px 0 60px; }
        .page-link { text-decoration: none; padding: 10px 20px; border-radius: 12px; background: white; color: #666; margin: 0 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .page-link.active { background: var(--main-green); color: white; }
    </style>
</head>
<body>

    <div class="top-bar">
        <a href="home.php" class="back-home-btn">Back</a>
        <div style="flex:1; text-align:center; font-weight:bold; font-size:20px; color:var(--dark-green);">Daily Talk</div>
    </div>

    <div class="main-content">
        <div class="video-grid">
            <?php foreach ($video_list as $video): ?>
                <div class="video-card" onclick="openPlayer(<?php echo htmlspecialchars(json_encode($video), ENT_QUOTES, 'UTF-8'); ?>)">
                    <img src="<?php echo $video['cover_url'] ?: 'static/images/default_cover.png'; ?>" alt="Talk Cover">
                    <div class="video-title"><?php echo htmlspecialchars($video['title']); ?></div>
                    <div class="status-tag">
                        <?php echo count($video['versions']) > 1 ? "Multilingual Subtitles Available" : "Standard Version"; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <nav class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>" class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </nav>
    </div>

    <div id="videoModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closePlayer()">&times;</span>
            <video id="player" controls></video>
            
            <div class="sub-controls">
                <button class="sub-btn" id="btn-without" onclick="switchVer('without_subtitle')">Without Subtitle</button>
                <button class="sub-btn" id="btn-with" onclick="switchVer('with_subtitle')">With Subtitle</button>
            </div>
            <h2 id="video-title-display" style="color: white; margin-top: 20px;"></h2>
        </div>
    </div>

    <script src="ai-agent.js"></script>

    <script>
        /**
         * Simple state management for the current talk
         */
        let activeData = null;

        /**
         * Inits the player with talk data and picks an available version
         */
        function openPlayer(data) {
            activeData = data;
            document.getElementById('videoModal').style.display = 'flex';
            document.getElementById('video-title-display').innerText = data.title;
            
            // Prefer showing the version without subtitles first by default
            if (data.versions['without_subtitle']) {
                switchVer('without_subtitle');
            } else if (data.versions['with_subtitle']) {
                switchVer('with_subtitle');
            }
        }

        /**
         * Handles switching between subtitle versions without reloading the modal
         */
        function switchVer(mode) {
            const video = document.getElementById('player');
            const targetUrl = activeData.versions[mode];

            if (!targetUrl) return; // Guard clause if version doesn't exist

            video.src = targetUrl;
            video.load(); 
            video.play();
        
            // UI Feedback for buttons
            document.getElementById('btn-with').classList.remove('active');
            document.getElementById('btn-without').classList.remove('active');
            
            const targetBtn = document.getElementById(mode === 'with_subtitle' ? 'btn-with' : 'btn-without');
            if(targetBtn) targetBtn.classList.add('active');
        }

        /**
         * Clean up: pause video and hide modal
         */
        function closePlayer() {
            const video = document.getElementById('player');
            video.pause();
            document.getElementById('videoModal').style.display = 'none';
        }

        // Close modal when clicking outside the content
        window.onclick = function(event) {
            const modal = document.getElementById('videoModal');
            if (event.target == modal) {
                closePlayer();
            }
        }
    </script>
</body>
</html>
