<?php
function render_daily_talk_hero($selected_accent = null) {
    $subtitle = $selected_accent 
        ? "Explore " . ucfirst(htmlspecialchars($selected_accent)) . " Nuances" 
        : "Explore Comprehensive-Accent Nuances";
    ?>
    <header class="hero">
        <h1>DAILY TALK</h1>
        <p><?= $subtitle ?></p>
    </header>
    
}
<?php
/**
 * Issue #341: 渲染口音选择容器
 */
function render_accent_container_start() {
    echo '<div class="accent_section"><div class="accent-grid">';
}

function render_accent_container_end() {
    echo '</div></div>';
}

/**
 * Issue #343: 获取口音对应的国旗路径
 */
function get_accent_flag_img($accent) {
    $flag_map = [
        'american' => 'us.png', 
        'british' => 'gb.png', 
        'australian' => 'au.png',
        'chinese' => 'cn.png', 
        'arabic' => 'eg.png', 
        'italian' => 'it.png',
        'japanese' => 'jp.png', 
        'russian' => 'ru.png', 
        'comprehensive-accent' => 'globe.png',
    ];
    $key = strtolower(trim($accent));
    $img_file = 'default.png';
    foreach ($flag_map as $keyword => $file) {
        if (strpos($key, $keyword) !== false) {
            $img_file = $file;
            break;
        }
    }
    return $img_file;
}

/**
 * Issue #342: 渲染单个口音选择卡片
 */
function render_accent_card($accent) {
    $img = get_accent_flag_img($accent);
    ?>
    <div class="accent-card" onclick="location.href='?accent=<?= urlencode($accent) ?>'">
        <div class="accent-icon">
            <img src="<?= $img ?>" alt="<?= htmlspecialchars($accent) ?>" 
                 style="width: 95px; height: 63.33px; border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
        </div>
        <div class="accent-name"><?= htmlspecialchars($accent) ?></div>
    </div>
    <?php
}

function group_daily_talk_versions($db_result) {
    $video_list = [];
    while($row = $db_result->fetch_assoc()) {
        $title = trim($row['title']); 
        if (!isset($video_list[$title])) {
            $video_list[$title] = [
                'title' => $title,
                'speaker' => $row['speaker'],
                'cover_url' => $row['cover_url'],
                'versions' => []
            ];
        }
        $mode = trim($row['subtitle_mode']); 
        $video_list[$title]['versions'][$mode] = trim($row['video_url']);
    }
    return array_values($video_list); 
}

?>