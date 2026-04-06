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
    <title>SINGLE MODEL</title>
    <link rel="stylesheet" href="mining_single.css">
</head>
<body>

    <div class="header">
        <div class="info-panel p1-info p1-color">
            <div class="info-row">
                <span class="icon icon-money"></span>Coins: $ <span id="p1-score"><?= (int)$miningBootstrap['balance'] ?></span>
            </div>
        </div>
        
        <div class="center-panel">
            <div class="time-level info-row">
                <span id="map-name-display" style="color:#5a3c26;">[Map]</span>
            </div>
        </div>
        
        <div class="info-panel p2-info" style="flex-direction: row; gap: 10px;">
            <button class="exit-btn" id="pause-btn">STOP</button>
            <button class="exit-btn" onclick="location.href='mining_map.php'">BACK</button>
        </div>
    </div>

    <div id="game-container">
        <div class="viewport p1-viewport">
            <canvas id="p1Canvas"></canvas>
            <div class="miner-look-card" id="minerLookCard">
                <div class="miner-look-stage" id="minerLookStage">
                    <div class="miner-look-empty" id="minerLookEmpty">Loading look...</div>
                </div>
            </div>
            <div class="controls-hint">PRESS [DOWN] OR [SPACE] BEGIN | [P] STOP</div>
        </div>
    </div>

    <div id="quiz-modal">
        <div id="player-indicator">MINING...</div>
        <div id="word-display">word</div>
        <div class="options-grid">
            <button class="opt-btn" onclick="handleAnswer(0)">A</button>
            <button class="opt-btn" onclick="handleAnswer(1)">B</button>
            <button class="opt-btn" onclick="handleAnswer(2)">C</button>
            <button class="opt-btn" onclick="handleAnswer(3)">D</button>
        </div>
        <div class="progress-wrapper">
            <div id="progress-bar"></div>
        </div>
        <p class="target-text">GOAL: ANSWER <span id="target-num">0/0</span> QUESTIONS</p>
    </div>

    <div id="pause-overlay">
        <div class="pause-box">
            <h2 style="margin-top:0; font-size:36px; color:#2c3e50; text-shadow: 1px 1px 0px #fff;">GAME STOP</h2>
            <div style="display:flex; flex-direction:column; gap:15px; margin-top:20px;">
                <button class="exit-btn" id="resume-btn">CONTINUE</button>
                <button class="exit-btn" onclick="location.reload()">RESTART</button>
                <button class="exit-btn" onclick="location.href='mining_index.php'">BACK</button>
            </div>
        </div>
    </div>

    <script>
        window.MINING_BOOTSTRAP = <?= json_encode($miningBootstrap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    </script>
    <script src="mining_single.js"></script>
</body>
</html>
