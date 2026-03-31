<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>挖矿学单词 - 游戏大厅</title>
    <link rel="stylesheet" href="mining_style.css">
</head>
<body>

    <div id="game-container">
        
        <div class="top-bar">
            <button class="back-btn" onclick="goBack()">◀ 返回</button>
            <div class="coin-display">
                <span class="coin-icon">💰</span>
                <span class="coin-amount" id="coin-count">1250</span>
            </div>
        </div>

        <div class="center-area">
            <h1 class="game-title">挖矿学单词</h1>
            <!-- 角色容器 - 所有图层叠加 -->
            <div class="character-container">
                <img src="people.png" alt="身体" id="layer_body" class="character-layer base-layer" />
                <img src="people.png" alt="鞋子" id="layer_shoes" class="character-layer" style="display: none;" />
                <img src="people.png" alt="裤子" id="layer_pants" class="character-layer" style="display: none;" />
                <img src="people.png" alt="上衣" id="layer_top" class="character-layer" style="display: none;" />
                <img src="people.png" alt="裙子" id="layer_dress" class="character-layer" style="display: none;" />
                <img src="people.png" alt="套装" id="layer_suit" class="character-layer" style="display: none;" />
                <img src="people.png" alt="头发" id="layer_hair" class="character-layer" style="display: none;" />
                <img src="people.png" alt="眼睛" id="layer_eye" class="character-layer" style="display: none;" />
                <img src="people.png" alt="眉毛" id="layer_eyebrows" class="character-layer" style="display: none;" />
                <img src="people.png" alt="鼻子" id="layer_nose" class="character-layer" style="display: none;" />
                <img src="people.png" alt="嘴巴" id="layer_mouse" class="character-layer" style="display: none;" />
                <img src="people.png" alt="墨镜" id="layer_glass" class="character-layer" style="display: none;" />
                <img src="people.png" alt="头饰" id="layer_head" class="character-layer" style="display: none;" />
                <img src="people.png" alt="角色整体" id="layer_character" class="character-layer" style="display: none;" />
                <img src="people.png" alt="背景" id="layer_background" class="character-layer" style="display: none;" />
            </div>
        </div>

        <div class="menu-buttons">
            <button class="menu-btn" onclick="startGame('single')">单人</button>
            <button class="menu-btn" onclick="startGame('double')">双人</button>
            <button class="menu-btn" onclick="openMenu('collection')">收藏</button>
            <button class="menu-btn" onclick="openMenu('achievement')">成就</button>
        </div>

    </div>

    <script src="mining_script.js"></script>
</body>
</html>