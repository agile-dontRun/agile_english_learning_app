<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>矿石收藏陈列室</title>
    <link rel="stylesheet" href="mining_collection.css"> <!-- 修复：原为 style.css -->
</head>
<body>
    <!-- 左上角返回按钮 -->
    <button class="back-btn" onclick="location.href='mining_index.php'">◀ 返回大厅</button>

    <div class="achievements-container">
        <div class="gallery-header">
            <h1>💎 矿石收藏馆</h1>
            <p>你的挖矿生涯荣誉见证</p>
            <div class="global-progress">
                <span>总收集进度: <strong id="progress-text">0/8</strong></span>
                <div class="progress-bar-bg">
                    <div id="progress-fill" class="progress-bar-fill"></div>
                </div>
            </div>
        </div>

        <div id="achievements-grid" class="achievements-grid">
            <!-- 卡片由 JS 自动渲染 -->
        </div>

        <div class="controls">
            <button class="btn reset-btn" onclick="resetAchievements()">重置进度</button>
            <!-- 保留你的测试按钮，方便调试 -->
            <button class="btn test-btn" onclick="simulateUnlock()">随机解锁一个(测试用)</button>
        </div>
    </div>

    <script src="mining_collection.js"></script> <!-- 修复：原为 script.js -->
</body>
</html>