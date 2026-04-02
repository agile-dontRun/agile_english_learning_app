<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>成就系统 - 挖矿学单词</title>
    <link rel="stylesheet" href="mining_achievement.css">
</head>
<body>
    <div class="header-bar">
        <button class="back-btn" onclick="location.href='mining_index.php'">◀ 返回大厅</button>
        <div class="coin-display">
            <span>💰</span> <span id="coin-count">0</span>
        </div>
    </div>

    <div class="achievement-container">
        <h1 class="page-title">🏆 矿工荣誉勋章</h1>
        <p class="page-desc">完成特定的挑战，领取丰厚的金币奖励！</p>
        
        <!-- 成就列表将由 JS 自动生成在这里 -->
        <div id="achievement-list" class="achievement-list"></div>
    </div>

    <script src="mining_achievement.js"></script>
</body>
</html>