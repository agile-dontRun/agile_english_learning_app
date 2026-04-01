<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>双人对战 - 挖矿学单词</title>
    <link rel="stylesheet" href="mining_double.css">
    <script src="https://cdn.socket.io/4.5.4/socket.io.min.js"></script>
</head>
<body>

    <div class="header">
        <div class="info-panel my-info">
            <div class="info-row">
                <span class="avatar">👦 我</span>
                <span class="icon icon-money"></span>$ <span id="my-score">0</span>
            </div>
        </div>
        
        <div class="center-panel">
            <!-- 新增了倒计时模块 -->
            <div class="timer-box" id="game-timer">60</div>
            <div class="room-display">房间号: <span id="room-id-display">----</span></div>
        </div>
        
        <div class="info-panel oppo-info">
            <div class="info-row">
                <span class="icon icon-money" style="background:#e74c3c;"></span>$ <span id="oppo-score">0</span>
                <span class="avatar" style="background:#e74c3c;">🤖 对手</span>
            </div>
        </div>
    </div>

    <div id="game-container">
        <div class="viewport">
            <canvas id="doubleCanvas"></canvas>
            <div class="controls-hint">按 [↓] 或 [空格] 发射 | 抢在对手前面！</div>
        </div>
    </div>

    <!-- 答题弹窗保持不变 -->
    <div id="quiz-modal">
        <div id="player-indicator">正在挖掘...</div>
        <div id="word-display">word</div>
        <div class="options-grid">
            <button class="opt-btn" onclick="handleAnswer(0)">A</button>
            <button class="opt-btn" onclick="handleAnswer(1)">B</button>
            <button class="opt-btn" onclick="handleAnswer(2)">C</button>
            <button class="opt-btn" onclick="handleAnswer(3)">D</button>
        </div>
        <div class="progress-wrapper"><div id="progress-bar"></div></div>
        <p class="target-text">目标: 答对 <span id="target-num">0/0</span> 题</p>
    </div>

    <script src="mining_double.js"></script>
</body>
</html>