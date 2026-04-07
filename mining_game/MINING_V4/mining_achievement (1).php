<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ACHIEVEMENT - MINING_GAME</title>
    <link rel="stylesheet" href="mining_achievement.css">
</head>
<body>
    <div class="header-bar">
        <button class="back-btn" onclick="location.href='mining_index.php'">◀ BACK/button>
        <div class="coin-display">
            <span>💰</span> <span id="coin-count">0</span>
        </div>
    </div>

    <div class="achievement-container">
        <h1 class="page-title">🏆 Miner's Medal of Honor</h1>
        <p class="page-desc">Complete specific challenges and claim generous gold coin rewards！</p>
        
        <!-- The achievement list will be automatically generated here by JS. -->
        <div id="achievement-list" class="achievement-list"></div>
    </div>

    <script src="mining_achievement.js"></script>
</body>
</html>
