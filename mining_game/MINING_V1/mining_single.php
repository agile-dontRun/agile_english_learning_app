<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>单人模式 - 挖矿学单词</title>
    <link rel="stylesheet" href="mining_single.css">
</head>
<body>

    <!-- 游戏顶部状态栏 -->
    <div class="header">
        <div class="info-panel p1-info p1-color">
            <div class="info-row">
                <span class="icon icon-money"></span>金钱: $ <span id="p1-score">0</span>
            </div>
        </div>
        
        <div class="center-panel">
            <div class="time-level info-row">
                <!-- 动态显示当前地图的名称 -->
                <span id="map-name-display" style="color:#5a3c26;">[地图名]</span>
            </div>
        </div>
        
        <div class="info-panel p2-info" style="flex-direction: row; gap: 10px;">
            <button class="exit-btn" id="pause-btn">暂停</button>
            <button class="exit-btn" onclick="location.href='mining_map.php'">◀ 返回地图</button>
        </div>
    </div>

    <!-- 游戏主画面 -->
    <div id="game-container">
        <div class="viewport p1-viewport">
            <canvas id="p1Canvas"></canvas>
            <div class="controls-hint">按 [↓] 或[空格] 发射 | [P] 暂停</div>
        </div>
    </div>

    <!-- 答题弹窗 -->
    <div id="quiz-modal">
        <div id="player-indicator">正在挖掘...</div>
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
        <p class="target-text">目标: 答对 <span id="target-num">0/0</span> 题</p>
    </div>

    <!-- 暂停遮罩层 -->
    <div id="pause-overlay">
        <div class="pause-box">
            <h2 style="margin-top:0; font-size:36px; color:#2c3e50; text-shadow: 1px 1px 0px #fff;">游戏暂停</h2>
            <div style="display:flex; flex-direction:column; gap:15px; margin-top:20px;">
                <button class="exit-btn" id="resume-btn">继续游戏</button>
                <button class="exit-btn" onclick="location.reload()">重新开始本关</button>
                <button class="exit-btn" onclick="location.href='index.html'">返回游戏大厅</button>
            </div>
        </div>
    </div>

    <script src="mining_single.js"></script>
</body>
</html>
