<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Word Garden</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="home-page">

    <nav class="navbar">
        <div class="nav-container">
            <button class="nav-item active" data-target="welcome-view">TED TALK</button>
            <button class="nav-item">IELTS LISTENING</button>
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
            <div class="study-banner">listening study (xiangtian, zeyv, zeyuan, lilin)</div>
            <div class="video-grid">
                <div class="video-card active-card"><div class="play-icon"></div></div>
                <div class="video-card"><div class="play-icon"></div></div>
            </div>
        </section>

        <section id="daily-talk-view" class="view-section" style="display: none;">
            <div class="desk-container">
                <div class="main-button-wrap" onclick="location.href='daily_decryption.php'">
                    <div class="green-square-btn">
                        <img src="headphone_icon.png" alt="Listen" style="width:100px; filter:invert(1);">
                    </div>
                    <div class="instruction-bubble">
                        Click to get today's<br>listening practice.
                    </div>
                </div>
        
                <div class="history-link" id="history-entry">
                    <span class="sun-icon">☀️</span>
                    <span class="text">History</span>
                </div>
            </div>
        </section>
    </main>

    <aside class="side-controls">
        <div class="ai-assistant">
            <div class="chat-bubble">I'm your AI assistant</div>
            <div class="icon-label">
                <img src="ai_icon.png" alt="AI">
                <span>AI assistant</span>
            </div>
        </div>
    </aside>

    <script>
        
        const views = document.querySelectorAll('.view-section');
        const navItems = document.querySelectorAll('.nav-item');

        function switchView(targetId) {
            if (!targetId) return;

            
            views.forEach(v => v.style.display = 'none');
            const targetView = document.getElementById(targetId);
            if (targetView) targetView.style.display = 'block';

            
            navItems.forEach(item => {
                item.classList.toggle('active', item.getAttribute('data-target') === targetId);
            });
        }

        
        document.querySelector('.nav-container').addEventListener('click', (e) => {
            const targetId = e.target.getAttribute('data-target');
            if (targetId) switchView(targetId);
        });

        
        document.getElementById('btn-enter-ted').onclick = () => switchView('video-view');
        
       
        document.querySelectorAll('.back-btn').forEach(btn => {
            btn.onclick = () => switchView(btn.dataset.target);
        });

    </script>
</body>
</html>