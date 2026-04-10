<?php
// Import shared mining game functions such as login check and data helpers
require_once __DIR__ . '/mining_common.php';

// Make sure the user is logged in before accessing the achievement page
coin_require_login();

// Get the current logged-in user's ID
$userId = coin_current_user_id();

// Load initial mining-related data for this user, such as coin balance
$miningBootstrap = mining_get_bootstrap($conn, $userId);

// Load the list of achievement IDs that this user has already claimed
$claimedAchievements = mining_get_claimed_achievement_ids($conn, $userId);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <!-- Basic page settings -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Page title shown in browser tab -->
    <title>ACHIEVEMENT - MINING_GAME</title>

    <!-- Achievement page stylesheet -->
    <link rel="stylesheet" href="mining_achievement.css">
</head>
<body>
    
    <!-- Full-screen loading overlay shown before achievement content is ready -->
    <div id="loading-screen">
        <div class="loading-inner">
            <!-- Loading page title -->
            <h1 class="loading-title">ACHIEVEMENTS</h1>

            <!-- Small loading message -->
            <p class="loading-subtitle">Loading rewards...</p>

            <!-- Loading progress bar -->
            <div class="loading-bar">
                <div class="loading-bar-fill" id="loading-bar-fill"></div>
            </div>

            <!-- Loading percentage text -->
            <div class="loading-percent" id="loading-percent">0%</div>
        </div>
    </div>
    
    <!-- Top header bar with back button and coin display -->
    <div class="header-bar">
        <!-- Return to the main mining page -->
        <button class="back-btn" onclick="location.href='mining_index.php'">BACK</button>

        <!-- Show the player's current coin balance -->
        <div class="coin-display">
            <span>💰</span> <span id="coin-count"><?= (int)$miningBootstrap['balance'] ?></span>
        </div>
    </div>

    <!-- Main achievement content area -->
    <div class="achievement-container">
        <!-- Achievement page title -->
        <h1 class="page-title">Miner's Medal of Honor</h1>

        <!-- Small description under the page title -->
        <p class="page-desc">Complete specific challenges and claim gold coin rewards.</p>

        <!-- Achievement cards will be generated here by JavaScript -->
        <div id="achievement-list" class="achievement-list"></div>
    </div>
    
    <!-- Floating button for turning background music on or off -->
    <button id="bgm-toggle" class="bgm-toggle" type="button">BGM OFF</button>

    <!-- Background music player -->
    <audio id="bgm-player" loop preload="auto">
        <source src="ConcernedApe - Pelican Town.mp3" type="audio/mpeg">
    </audio>
    
    <script>
        // Pass initial backend data to JavaScript so the page can render faster
        window.MINING_ACHIEVEMENT_BOOTSTRAP = {
            balance: <?= (int)$miningBootstrap['balance'] ?>,
            claimedAchievements: <?= json_encode(array_values($claimedAchievements), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
        };
    </script>

    <!-- Main JavaScript file for achievement logic -->
    <script src="mining_achievement.js"></script>
</body>
</html>