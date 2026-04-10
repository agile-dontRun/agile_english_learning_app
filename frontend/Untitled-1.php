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
    <title>Word Garden - English Learning</title>
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
            border-color: #a3d977; /* var(--main-green) 兼容替换 */
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
            position: absolute;
            top: -100px; right: -80px;
            background: white; border: 2px solid #a3d977;
            border-radius: 30px; padding: 15px 25px;
            color: #5a8a31; font-weight: bold;
            pointer-events: none;
            white-space: nowrap; z-index: 10;
        }
        .instruction-bubble::after {
            content: '';
            position: absolute; bottom: -12px; left: 40px;
            border-width: 12px 12px 0; border-style: solid;
            border-color: white transparent transparent;
        }
    </style>
</head>
<body class="home-page">
    
    <nav class="navbar">
        <div class="nav-container">
            <button class="nav-item active" onclick="switchTopNav('welcome-view', this)">TED TALK</button>
            <button class="nav-item" onclick="switchTopNav('ielts-view', this)">IELTS LISTENING</button>
            <button class="nav-item" onclick="switchTopNav('daily-talk-view', this)">DAILY TALK</button>
            <button class="nav-item">VOCABULARY</button>
            <button class="nav-item">CALENDAR</button>
            <button class="nav-item">GROUP</button>
            <button class="nav-item">PROFILE</button>
        </div>
    </nav>

    <main class="content-container">
        
        <section id="welcome-view" class="view-section welcome-section">
            <div class="welcome-flex">
                <div class="illustration-box">
                    <img src="ted_stage.png" alt="TED Stage" class="stage-image">
                </div>
                <div class="cloud-shape">
                    <h2>Ideas worth<br>spreading</h2>
                </div>
            </div>
            <div class="enter-area">
                <div class="enter-wrapper" onclick="showVideoGrid()">
                    <div class="tedx-logo">TED<sup>x</sup></div>
                    <div class="play-circle">
                        <div class="play-triangle"></div>
                    </div>
                    <div class="enter-text">ENTER</div>
                </div>
            </div>
        </section>
        
        <section id="video-view" class="view-section video-section" style="display: none;">
            <div class="top-controls">
                <button class="back-btn" onclick="showWelcomeView()">
                    <span class="arrow">⬅</span> BACK
                </button>
            </div>
            
            <div class="study-banner">
                listening study (xiangtian, zeyv, zeyuan, <?php echo htmlspecialchars($nickname); ?>)
            </div>
            <div class="video-grid">
                <div class="video-card active-card" onclick="showPlayerView()"><div class="play-arrow"></div></div>
                <div class="video-card" onclick="showPlayerView()"><div class="play-arrow"></div></div>
                <div class="video-card" onclick="showPlayerView()"><div class="play-arrow"></div></div>
                <div class="video-card" onclick="showPlayerView()"><div class="play-arrow"></div></div>
                <div class="video-card" onclick="showPlayerView()"><div class="play-arrow"></div></div>
                <div class="video-card" onclick="showPlayerView()"><div class="play-arrow"></div></div>
                <div class="video-card" onclick="showPlayerView()"><div class="play-arrow"></div></div>
                <div class="video-card" onclick="showPlayerView()"><div class="play-arrow"></div></div>
            </div>
            <div class="footer-logo">
                <div class="ted-tag">TED TALK</div>
            </div>
        </section>

        <section id="player-view" class="view-section player-section" style="display: none;">
            <div class="player-layout">
                <div class="side-decorations left-decorations">
                    <div class="sticker tilt-right"><img src="ted 3.png" alt="TED Sticker"></div>
                    <div class="sticker tilt-left"><img src="ted 4.png" alt="TED Sticker"></div>
                    <div class="sticker tilt-right"><img src="ted 5.png" alt="TED Sticker"></div>
                </div>
                <div class="main-player-area">
                    <div class="video-player-box"></div>
                    <button class="practice-btn" onclick="window.location.href='practice.html'">DO SOME LISTENING PRACTICES</button>
                </div>
                <div class="side-decorations right-decorations">
                    <button class="player-back-btn" onclick="showVideoGridFromPlayer()">BACK</button>
                    <div class="sticker tilt-left"><img src="ted 6.png" alt="TED Sticker"></div>
                    <div class="sticker tilt-right"><img src="ted 7.png" alt="TED Sticker"></div>
                </div>
            </div>
        </section>

        <section id="ielts-view" class="view-section ielts-selection-section" style="display: none;">
            <div class="selection-container">
                <div class="ielts-box" onclick="window.location.href='ielts_tests.php?cam=19'">
                    <div class="ielts-title">Cambridge IELTS 19<br>Audio</div>
                </div>
                <div class="ielts-box" onclick="window.location.href='ielts_tests.php?cam=20'">
                    <div class="ielts-title">Cambridge IELTS 20<br>Audio</div>
                </div>
            </div>
        </section>

        <section id="daily-talk-view" class="view-section daily-talk-section" style="display: none;">
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
            <div class="chat-bubble">Hi <?php echo htmlspecialchars($nickname); ?>, I'm your AI assistant</div>
            <div class="icon-label">
                <img src="ai_icon.png" alt="AI">
                <span>AI assistant</span>
            </div>
        </div>
    </aside>

    <script>
        // 顶部导航栏的全局切换逻辑
        const allViews = ['welcome-view', 'video-view', 'player-view', 'ielts-view', 'daily-talk-view'];
        
        function switchTopNav(targetId, navBtn) {
            // 隐藏所有视图
            allViews.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.style.display = 'none';
            });
            
            // 显示选中的视图
            const targetEl = document.getElementById(targetId);
            if (targetEl) {
                targetEl.style.display = (targetId === 'ielts-view' || targetId === 'daily-talk-view') ? 'flex' : 'block';
            }

            // 更新导航栏按钮的高亮状态
            const navItems = document.querySelectorAll('.nav-item');
            navItems.forEach(item => item.classList.remove('active'));
            if (navBtn) navBtn.classList.add('active');
        }

        // --- 以下为 TED TALK 内部的跳转逻辑 (保持 homer.txt 原样) ---

        function showVideoGrid() {
            document.getElementById('welcome-view').style.display = 'none';
            document.getElementById('video-view').style.display = 'block';
            document.getElementById('player-view').style.display = 'none';
        }

        function showWelcomeView() {
            document.getElementById('video-view').style.display = 'none';
            document.getElementById('welcome-view').style.display = 'block';
        }

        function showPlayerView() {
            document.getElementById('video-view').style.display = 'none';
            document.getElementById('player-view').style.display = 'block';
        }

        function showVideoGridFromPlayer() {
            document.getElementById('player-view').style.display = 'none';
            document.getElementById('video-view').style.display = 'block';
        }
    </script>
</body>
</html>