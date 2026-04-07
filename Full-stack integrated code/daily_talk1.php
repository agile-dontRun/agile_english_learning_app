/**
 * Issue #341: 渲染 Daily Talk 专属 Hero 区域
 * 根据是否选择了口音动态显示副标题
 */
function render_daily_talk_hero($selected_accent = null) {
    $subtitle = $selected_accent 
        ? "Explore " . ucfirst(htmlspecialchars($selected_accent)) . " Nuances" 
        : "Explore Comprehensive-Accent Nuances";
    ?>
    <header class="hero">
        <h1>DAILY TALK</h1>
        <p><?= $subtitle ?></p>
    </header>
    <?php
}

/**
 * Issue #341: 渲染口音选择容器
 */
function render_accent_container_start() {
    echo '<div class="accent_section"><div class="accent-grid">';
}

function render_accent_container_end() {
    echo '</div></div>';
}