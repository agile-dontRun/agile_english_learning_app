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

?>