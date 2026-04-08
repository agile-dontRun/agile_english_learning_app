<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title> Ore Collection Gallery</title>
    <link rel="stylesheet" href="mining_collection.css"> <!-- 修复：原为 style.css -->
</head>
<body>
    <!-- 左上角返回按钮 -->
    <button class="back-btn" onclick="location.href='mining_index.php'">◀ BACK</button>

    <div class="achievements-container">
        <div class="gallery-header">
            <h1>💎 Ore Collection Hall</h1>
            <p>A testament to the honors of your mining career</p>
            <div class="global-progress">
                <span> Total Collection Progress: <strong id="progress-text">0/8</strong></span>
                <div class="progress-bar-bg">
                    <div id="progress-fill" class="progress-bar-fill"></div>
                </div>
            </div>
        </div>

        <div id="achievements-grid" class="achievements-grid">
            <!-- 卡片由 JS 自动渲染 -->
        </div>

        <div class="controls">
            <button class="btn reset-btn" onclick="resetAchievements()">Reset Progress</button>
        </div>
    </div>

    <script src="mining_collection.js"></script> <!-- 修复：原为 script.js -->
</body>
</html>