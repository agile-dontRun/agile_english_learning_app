<?php
require_once __DIR__ . '/mining_common.php';
coin_require_login();

$userId = coin_current_user_id();
$miningBootstrap = mining_get_bootstrap($conn, $userId);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CHOOSE-MAP - MINING-GAME</title>
    <link rel="stylesheet" href="mining_map.css">
</head>
<body>

    <div id="map-page-container">

        <div class="top-bar">
            <button class="back-btn" onclick="goBack()">BACK</button>
            <div class="coin-display">
                <span class="coin-icon">💰</span>
                <span class="coin-amount" id="coin-count"><?= (int)$miningBootstrap['balance'] ?></span>
            </div>
        </div>

        <div class="map-content">
            <div class="map-bg-container">
                <img src="map.png" alt="Mining map" class="map-bg-image">
                <div class="maps-overlay" id="maps-overlay"></div>
            </div>
        </div>
    </div>
    <script>
        window.MINING_BOOTSTRAP = <?= json_encode($miningBootstrap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    </script>
    <script src="mining_map.js"></script>
</body>
</html>
