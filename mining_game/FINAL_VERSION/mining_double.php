<?php
// Import shared coin system functions for the mining game
require_once __DIR__ . '/coin_common.php';

// Make sure the user is logged in before entering the two-player page
coin_require_login();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <!-- Basic page settings -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Title shown in the browser tab -->
    <title>Two-Player Battle - Mining Game</title>

    <!-- Stylesheet for the double-player mining page -->
    <link rel="stylesheet" href="mining_double.css">

    <!-- Socket.IO library used for real-time multiplayer communication -->
    <script src="https://cdn.socket.io/4.5.4/socket.io.min.js"></script>
</head>
<body>
    
    <!-- Full-screen loading overlay shown while the room and assets are loading -->
    <div id="loading-screen">
        <div class="loading-inner">
            <!-- Main loading title -->
            <h1 class="loading-title">DOUBLE BATTLE</h1>

            <!-- Loading subtitle, updated dynamically by JavaScript -->
            <p class="loading-subtitle" id="loading-subtitle">Connecting room...</p>

            <!-- Loading progress bar -->
            <div class="loading-bar">
                <div class="loading-bar-fill" id="loading-bar-fill"></div>
            </div>

            <!-- Loading percentage text -->
            <div class="loading-percent" id="loading-percent">0%</div>
        </div>
    </div>

    <!-- Top game header showing player scores, timer, and room number -->
    <div class="header">
        <!-- Left side: current player's score -->
        <div class="info-panel my-info">
            <div class="info-row">
                <span class="avatar">Me</span>
                <span class="icon icon-money"></span>$ <span id="my-score">0</span>
            </div>
        </div>
        
        <!-- Center: countdown timer and room display -->
        <div class="center-panel">
            <div class="timer-box" id="game-timer">60</div>
            <div class="room-display">Room <span id="room-id-display">----</span></div>
        </div>
        
        <!-- Right side: opponent's score -->
        <div class="info-panel oppo-info">
            <div class="info-row">
                <span class="icon icon-money" style="background:#e74c3c;"></span>$ <span id="oppo-score">0</span>
                <span class="avatar" style="background:#e74c3c;">Opponent</span>
            </div>
        </div>
    </div>

    <!-- Main game area -->
    <div id="game-container">
    <div class="viewport">
        <!-- Canvas where the mining battle is rendered -->
        <canvas id="doubleCanvas"></canvas>

        <!-- Left player's outfit preview card -->
        <div class="miner-look-card miner-look-left" id="leftMinerLookCard">
            <div class="miner-look-stage" id="leftMinerLookStage">
                <div class="miner-look-empty" id="leftMinerLookEmpty">Loading left look...</div>
            </div>
        </div>

        <!-- Right player's outfit preview card -->
        <div class="miner-look-card miner-look-right" id="rightMinerLookCard">
            <div class="miner-look-stage" id="rightMinerLookStage">
                <div class="miner-look-empty" id="rightMinerLookEmpty">Loading right look...</div>
            </div>
        </div>

        <!-- Control hint shown on screen -->
        <div class="controls-hint">PRESS [DOWN] OR [SPACE] TO SHOOT</div>
    </div>
</div>

    <!-- Quiz modal shown when a player grabs a mine object -->
    <div id="quiz-modal">
        <!-- Quiz status / object type indicator -->
        <div id="player-indicator">MINING...</div>

        <!-- Current word being asked -->
        <div id="word-display">word</div>

        <!-- Four answer option buttons -->
        <div class="options-grid">
            <button class="opt-btn" onclick="handleAnswer(0)">A</button>
            <button class="opt-btn" onclick="handleAnswer(1)">B</button>
            <button class="opt-btn" onclick="handleAnswer(2)">C</button>
            <button class="opt-btn" onclick="handleAnswer(3)">D</button>
        </div>

        <!-- Quiz progress bar -->
        <div class="progress-wrapper"><div id="progress-bar"></div></div>

        <!-- Target progress text -->
        <p class="target-text">GOAL: ANSWER <span id="target-num">0/0</span> QUESTIONS</p>
    </div>
    
    <!-- Floating button for turning BGM on or off -->
    <button id="bgm-toggle" class="bgm-toggle" type="button">BGM ON</button>

    <!-- Background music player -->
    <audio id="bgm-player" loop preload="auto">
        <source src="高梨康治 - FAIRY TAIL メインテーマ.mp3" type="audio/mpeg">
    </audio>

    <!-- Main JavaScript file for double-player mining gameplay -->
    <script src="mining_double.js"></script>
</body>
</html>