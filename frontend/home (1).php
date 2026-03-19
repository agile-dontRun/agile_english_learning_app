<?php
session_start();
// 权限检查：未登录用户跳回登录页
/*
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}
*/
$nickname = $_SESSION['nickname'] ?? '学习者';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Word Garden | 英语学习</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* ===== 雅思选择界面专属样式 ===== */
        .ielts-selection-section {
            width: 100%;
            height: calc(100vh - 80px);
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #f1f8e9;
        }
        .selection-container {
            display: flex;
            gap: 40px;
        }
        .ielts-box { 
            width: 350px; 
            height: 180px; 
            background: white; 
            border-radius: 30px; 
            display: flex; 
            justify-content: center; 
            align-items: center;
            cursor: pointer; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            border: 3px solid transparent; 
            transition: all 0.3s ease;
        }
        .ielts-box:hover { 
            border-color: var(--main-green); 
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(163, 217, 119, 0.3);
        }
        .ielts-title { font-size: 22px; font-weight: bold; color: #5a8a31; text-align: center; }

        /* ===== Daily Talk 居中修正样式 ===== */
        .daily-talk-section {
            width: 100%;
            height: calc(100vh - 80px);
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .center-interaction-area { position: relative; width: 260px; height: 260px; }
        .instruction-bubble {
            position: absolute; top: -100px; right: -80px;
            background: white; border: 2px solid #a3d977;
            border-radius: 30px; padding: 15px 25px;
            color: #5a8a31; font-weight: bold; pointer-events: none;
            white-space: nowrap; z-index: 10;
        }
        .instruction-bubble::after {
            content: ''; position: absolute; bottom: -12px; left: 40px;
            border-width: 12px 12px 0; border-style: solid;
            border-color: white transparent transparent;
        }
    </style>
</head>
<body class="home-page">

    <nav class="navbar">
        <div class="nav-container">
            <button class="nav-item active" data-target="welcome-view">TED TALK</button>
            <button class="nav-item" data-target="ielts-view">IELTS LISTENING</button>
            <button class="nav-item" data-target="daily-talk-view">DAILY TALK</button>
            <button class="nav-item">VOCABULARY</button>
            <button class="nav-item">CALENDAR</button>
            <button class="nav-item">GROUP</button>
            <button class="nav-item">PROFILE</button>
        </div>
    </nav>

    <main class="content-container">
        
        <section id="welcome-view" class="view-section">
            <div class="welcome-flex">
                <div class="illustration-box">
                    <img src="ted_stage.png" alt="TED Stage" class="stage-img">
                </div>
                <div class="thought-cloud">
                    <h2 class="cloud-text">Ideas worth<br>spreading</h2>
                </div>
            </div>
            <div class="enter-area">
                <div class="enter-wrapper" id="btn-enter-ted">
                    <span class="tedx-text">TED<sup>X</sup></span>
                    <div class="play-button">▶</div>
                    <span class="enter-text">ENTER</span>
                </div>
            </div>
        </section>
        
        <section id="video-view" class="view-section" style="display: none;">
            <div class="top-controls">
                <button class="back-btn" data-target="welcome-view">
                    <span class="arrow">⬅</span> BACK
                </button>
            </div>
            <div class="study-banner">listening study (xiangtian, zeyv, zeyuan, <?php echo $nickname; ?>)</div>
            <div class="video-grid">
                <div class="video-card active-card"><div class="play-icon"></div></div>
                <div class="video-card"><div class="play-icon"></div></div>
            </div>
        </section>
        
        <section id="ielts-view" class="ielts-selection-section view-section" style="display: none;">
            <div class="selection-container">
                <div class="ielts-box" onclick="window.location.href='ielts_tests.php?cam=19'">
                    <div class="ielts-title">Cambridge IELTS 19<br>Audio</div>
                </div>
                <div class="ielts-box" onclick="window.location.href='ielts_tests.php?cam=20'">
                    <div class="ielts-title">Cambridge IELTS 20<br>Audio</div>
                </div>
            </div>
        </section>

        <section id="daily-talk-view" class="daily-talk-section view-section" style="display: none;">
            <div class="desk-container">
                <div class="center-interaction-area">
                    <div class="green-square-btn" onclick="location.href='daily_decryption.php'">
                        <img src="static/images/headphone_icon.png" alt="Listen" style="width:100px; filter:invert(1);">
                    </div>
                    <div class="instruction-bubble">
                        Click to get today's<br>listening practice.
                    </div>
                </div>
        
                <div class="history-link" id="history-entry" style="position: absolute; top: 35px; left: 35px; cursor: pointer;">
                    <span class="sun-icon">☀️</span>
                    <span class="text" style="color: #7cb342; font-weight: bold;">History</span>
                </div>
            </div>
        </section>
    </main>

    <aside class="side-controls">
        <div class="ai-assistant">
            <div class="chat-bubble">Hi <?php echo $nickname; ?>, I'm your AI assistant</div>
            <div class="icon-label">
                <img src="ai_icon.png" alt="AI">
                <span>AI assistant</span>
            </div>
        </div>
    </aside>

    <script>
        const views = document.querySelectorAll('.view-section');
        const navItems = document.querySelectorAll('.nav-item');

        // 统一的视图切换逻辑
        function switchView(targetId) {
            if (!targetId) return;

            // 1. 隐藏所有视图
            views.forEach(v => v.style.display = 'none');
            
            // 2. 显示目标视图
            const targetView = document.getElementById(targetId);
            if (targetView) targetView.style.display = (targetId === 'welcome-view') ? 'block' : 'flex';

            // 3. 更新导航栏高亮
            navItems.forEach(item => {
                item.classList.toggle('active', item.getAttribute('data-target') === targetId);
            });
        }

        // 绑定导航栏点击事件
        document.querySelector('.nav-container').addEventListener('click', (e) => {
            const targetId = e.target.getAttribute('data-target');
            if (targetId) switchView(targetId);
        });

        // TED 进入按钮
        document.getElementById('btn-enter-ted').onclick = () => switchView('video-view');
        
        // 通用的返回按钮逻辑
        document.querySelectorAll('.back-btn').forEach(btn => {
            btn.onclick = () => switchView(btn.dataset.target);
        });
    </script>
</body>
</html>