<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Multiplayer Battle - Mining Vocabulary</title>
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
            <!-- Added countdown timer module -->
            <div class="timer-box" id="game-timer">60</div>
            <div class="room-display">Room ID: <span id="room-id-display">----</span></div>
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
            <div class="controls-hint">Press [Down] or [Space] to launch | Grab it before your opponent</div>
        </div>
    </div>

    <!-- Quiz popup remains unchanged structurally -->
    <div id="quiz-modal">
        <div id="player-indicator">Digging...</div>
        <div id="word-display">word</div>
        <div class="options-grid">
            <button class="opt-btn" onclick="handleAnswer(0)">A</button>
            <button class="opt-btn" onclick="handleAnswer(1)">B</button>
            <button class="opt-btn" onclick="handleAnswer(2)">C</button>
            <button class="opt-btn" onclick="handleAnswer(3)">D</button>
        </div>
        <div class="progress-wrapper"><div id="progress-bar"></div></div>
        <p class="target-text">Target: <span id="target-num">0/0</span> correct</p>
    </div>

    <script src="mining_double.js"></script>
</body>
</html>
