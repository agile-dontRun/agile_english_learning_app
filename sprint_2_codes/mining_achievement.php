<?php
require_once __DIR__ . '/mining_common.php';
coin_require_login();

$userId = coin_current_user_id();
$miningBootstrap = mining_get_bootstrap($conn, $userId);
$claimedAchievements = mining_get_claimed_achievement_ids($conn, $userId);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ACHIEVEMENT - MINING_GAME</title>
    <link rel="stylesheet" href="mining_achievement.css">
</head>
<body>
    
    <div id="loading-screen">
        <div class="loading-inner">
            <h1 class="loading-title">ACHIEVEMENTS</h1>
            <p class="loading-subtitle">Loading rewards...</p>

            <div class="loading-bar">
                <div class="loading-bar-fill" id="loading-bar-fill"></div>
            </div>

            <div class="loading-percent" id="loading-percent">0%</div>
        </div>
    </div>
    
    <div class="header-bar">
        <button class="back-btn" onclick="location.href='mining_index.php'">BACK</button>
        <div class="coin-display">
            <span>💰</span> <span id="coin-count"><?= (int)$miningBootstrap['balance'] ?></span>
        </div>
    </div>

    <div class="achievement-container">
        <h1 class="page-title">Miner's Medal of Honor</h1>
        <p class="page-desc">Complete specific challenges and claim gold coin rewards.</p>
        <div id="achievement-list" class="achievement-list"></div>
    </div>
    
    <button id="bgm-toggle" class="bgm-toggle" type="button">BGM OFF</button>
    <audio id="bgm-player" loop preload="auto">
        <source src="ConcernedApe - Pelican Town.mp3" type="audio/mpeg">
    </audio>
    
    <script>
        window.MINING_ACHIEVEMENT_BOOTSTRAP = {
            balance: <?= (int)$miningBootstrap['balance'] ?>,
            claimedAchievements: <?= json_encode(array_values($claimedAchievements), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
        };
    </script>
    <script src="mining_achievement.js"></script>
</body>
</html>
