<?php

function render_oxford_navbar($nickname, $avatar_html) {
    ?>
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
                <li><a href="forum.php">Community</a></li>
            </ul>
        </div>
        <div class="navbar-right dropdown">
            <?php echo $avatar_html; ?>
            <span style="font-size:14px; font-weight:600; color:#e0e0e0;"><?php echo htmlspecialchars($nickname); ?> ▾</span>
            <ul class="dropdown-menu" style="right:0; left:auto;">
                <li style="padding: 20px; background: #f8fafc;">
                    <div style="color:#666; font-size:12px;">Signed in as</div>
                    <div style="color:#002147; font-weight:bold;"><?php echo htmlspecialchars($nickname); ?></div>
                </li>
                <li><a href="profile.php">My Profile</a></li>
                <li><a href="logout.php" style="color:#dc3545 !important;">Sign Out</a></li>
            </ul>
        </div>
    </nav>
    <?php
}


function render_ted_hero($title = "TED Library", $subtitle = "Illuminating Ideas • Refined Comprehension") {
    ?>
    <header class="hero">
        <h1><?php echo $title; ?></h1>
        <p><?php echo $subtitle; ?></p>
    </header>
    <?php
}


/**
 * Issue 2: 转换 YouTube 链接为 Embed 格式
 */
function format_video_embed_url($url) {
    $video_id = '';
    if (preg_match('/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/', $url, $matches)) {
        $video_id = $matches[1];
    } elseif (preg_match('/youtu\.be\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
        $video_id = $matches[1];
    }
    
    if ($video_id) {
        return "https://www.youtube.com/embed/{$video_id}?rel=0&modestbranding=1";
    }
    return htmlspecialchars($url); // 如果不是 YouTube 链接则返回原样
}


/**
 * Issue 3: 渲染单个视频卡片
 */
function render_video_card($row, $page) {
    $mode_text = ($row['subtitle_mode'] === 'with_subtitle') ? 'En Subtitles' : 'Original Audio';
    $detail_url = "TED.php?ted_id=" . $row['ted_id'] . "&page=" . $page;
    ?>
    <div class="video-card" onclick="window.location.href='<?php echo $detail_url; ?>'">
        <div class="video-placeholder">
            <?php if (!empty($row['cover_url'])): ?>
                <img src="<?php echo htmlspecialchars($row['cover_url']); ?>" alt="Cover" class="video-cover">
            <?php endif; ?>
            <div class="play-icon-wrapper"><span class="play-icon">▶</span></div>
        </div>
        <div class="card-info">
            <h4><?php echo htmlspecialchars($row['title']); ?></h4>
            <div class="subtitle-badge"><?php echo $mode_text; ?></div>
        </div>
    </div>
    <?php
}