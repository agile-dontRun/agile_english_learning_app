<?php
require_once __DIR__ . '/coin_common.php';
coin_require_login();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Player Battle - Mining Game</title>
    <link rel="stylesheet" href="mining_double.css">
    <script src="https://cdn.socket.io/4.5.4/socket.io.min.js"></script>
</head>
<body>

    <div class="header">
        <div class="info-panel my-info">
            <div class="info-row">
                <span class="avatar">Me</span>
                <span class="icon icon-money"></span>$ <span id="my-score">0</span>
            </div>
        </div>
        
        <div class="center-panel">
            <div class="timer-box" id="game-timer">60</div>
            <div class="room-display">Room <span id="room-id-display">----</span></div>
        </div>
        
        <div class="info-panel oppo-info">
            <div class="info-row">
                <span class="icon icon-money" style="background:#e74c3c;"></span>$ <span id="oppo-score">0</span>
                <span class="avatar" style="background:#e74c3c;">Opponent</span>
            </div>
        </div>
    </div>

    <div id="game-container">
        <div class="viewport">
            <canvas id="doubleCanvas"></canvas>

        <!-- 我的造型 -->
            <div class="miner-look-card my-miner-look" id="myMinerLookCard">
                <div class="miner-look-stage" id="myMinerLookStage">
                    <div class="miner-look-empty" id="myMinerLookEmpty">Loading look...</div>
                </div>
            </div>

        <!-- 对手造型（先预留，后续如果你要同步对手穿搭，可以直接用） -->
            <div class="miner-look-card oppo-miner-look" id="oppoMinerLookCard" style="display:none;">
                <div class="miner-look-stage" id="oppoMinerLookStage">
                    <div class="miner-look-empty" id="oppoMinerLookEmpty">Opponent look</div>
                </div>
            </div>

            <div class="controls-hint">PRESS [DOWN] OR [SPACE] TO SHOOT</div>
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
        <div class="progress-wrapper"><div id="progress-bar"></div></div>
        <p class="target-text">GOAL: ANSWER <span id="target-num">0/0</span> QUESTIONS</p>
    </div>

    <script src="mining_double.js"></script>
</body>
</html>
