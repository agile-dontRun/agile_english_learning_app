<?php
require_once __DIR__ . '/mining_common.php';
coin_require_login();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$userId = coin_current_user_id();
$miningBootstrap = mining_get_bootstrap($conn, $userId);
$miningStyleVersion = @filemtime(__DIR__ . '/mining_style.css') ?: time();
$miningScriptVersion = @filemtime(__DIR__ . '/mining_script.js') ?: time();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MINING IN LEARNING - HOME</title>
    <link rel="stylesheet" href="mining_style.css?v=<?= (int)$miningStyleVersion ?>">
</head>
<body>

    <div id="game-container">
        <div class="top-bar">
            <button class="back-btn" onclick="location.href='/galgame/galgame/index.html'">BACK</button>
            <h1 class="game-title">MINING GAME</h1>
            <div class="coin-display">
                <span class="coin-icon">💵</span>
                <span class="coin-amount" id="coin-count"><?= (int)$miningBootstrap['balance'] ?></span>
            </div>
        </div>

        <div class="center-area"></div>

        <div class="menu-buttons">
            <button class="menu-btn" onclick="startGame('single')">SINGLE-GAME</button>
            <button class="menu-btn" onclick="startGame('double')">DOUBLE-GAME</button>
            <button class="menu-btn" onclick="openMenu('collection')">COLLECTION</button>
            <button class="menu-btn" onclick="openMenu('achievement')">ACHIEVEMENT</button>
        </div>

        <div class="ming-look-card">
            <div class="ming-look-title" id="ming-look-title" style="display:none !important;">Current Ming Look</div>
            <div class="ming-look-stage" id="ming-look-stage">
                <div class="ming-look-empty" id="ming-look-empty">No active outfit yet</div>
            </div>
        </div>
    </div>
    <script>
        window.MINING_BOOTSTRAP = <?= json_encode($miningBootstrap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    </script>
    <script src="mining_script.js?v=<?= (int)$miningScriptVersion ?>"></script>
</body>
</html>
