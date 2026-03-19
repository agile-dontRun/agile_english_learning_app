<?php
session_start();
$nickname = $_SESSION['nickname'] ?? 'Student';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Word Garden</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /*Basic layout*/ 
        .view-section {
            width: 100%;
            height: calc(100vh - 80px);
            display: none; /* Default hidden */
            overflow-y: auto;
        }

        /*HOMEPAGE pattern*/
        .homepage-section {
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #f1f8e9;
        }
        .homepage-img { max-width: 80%; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }

        /* 2. IELTS pattern*/
        .ielts-selection-section { display: flex; justify-content: center; align-items: center; background-color: #f1f8e9; }
        .selection-container { display: flex; gap: 40px; }
        .ielts-box { 
            width: 350px; height: 180px; background: white; border-radius: 30px; 
            display: flex; justify-content: center; align-items: center;
            cursor: pointer; box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            border: 3px solid transparent; transition: all 0.3s ease;
        }
        .ielts-box:hover { border-color: #a3d977; transform: translateY(-10px); }
        .ielts-title { font-size: 22px; font-weight: bold; color: #5a8a31; text-align: center; }

        /*3. DAILY TALK pattern*/
        .daily-talk-section { display: flex; justify-content: center; align-items: center; background-color: #fff; }
        .center-interaction-area { position: relative; width: 260px; height: 260px; }
        .green-square-btn { 
            background: #a3d977; width: 100%; height: 100%; border-radius: 40px; 
            display: flex; justify-content: center; align-items: center; cursor: pointer;
        }
        .instruction-bubble {
            position: absolute; top: -100px; right: -80px; background: white; 
            border: 2px solid #a3d977; border-radius: 30px; padding: 15px 25px;
            color: #5a8a31; font-weight: bold; white-space: nowrap;
        }

        /* 4. TED welcome pattern*/
        .welcome-flex { display: flex; align-items: center; justify-content: center; gap: 50px; padding: 50px; }
        .stage-image { max-width: 600px; }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="nav-container">
            <button class="nav-item active" data-target="homepage-view">HOMEPAGE</button>
            <button class="nav-item" data-target="welcome-view">TED TALK</button>
            <button class="nav-item" data-target="ielts-view">IELTS LISTENING</button>
            <button class="nav-item" data-target="daily-talk-view">DAILY TALK</button>
            <button class="nav-item">VOCABULARY</button>
            <button class="nav-item">CALENDAR</button>
            <button class="nav-item">GROUP</button>
            <button class="nav-item">PROFILE</button>
        </div>
    </nav>

    <main class="content-container">
        
        <section id="homepage-view" class="homepage-section view-section" style="display: flex;">
            <img src="homepage.png" alt="Welcome" class="homepage-img">
        </section>

        <section id="welcome-view" class="welcome-section view-section">
            <div class="welcome-flex">
                <div class="illustration-box">
                    <img src="ted_stage.png" alt="TED Stage" class="stage-image">
                </div>
                <div class="cloud-shape">
                    <h2>Ideas worth<br>spreading</h2>
                </div>
            </div>
            <div class="enter-area">
                <div class="enter-wrapper" onclick="window.location.href='TED.php'">
                    <div class="tedx-logo">TED<sup>x</sup></div>
                    <div class="play-circle"><div class="play-triangle"></div></div>
                    <div class="enter-text">ENTER</div>
                </div>
            </div>
        </section>
        
        <section id="ielts-view" class="ielts-selection-section view-section">
            <div class="selection-container">
                    <div class="ielts-box" onclick="window.location.href='ielts.php?cam=19'">
                         <div class="ielts-title">Cambridge IELTS 19 Audio</div>
                    </div>
                    <div class="ielts-box" onclick="window.location.href='ielts.php?cam=20'">
                        <div class="ielts-title">Cambridge IELTS 20 Audio</div>
                    </div>
            </div>
        </section>

        <section id="daily-talk-view" class="daily-talk-section view-section">
            <div class="center-interaction-area">
                <div class="green-square-btn" onclick="location.href='daily_decryption.php'">
                    <img src="static/images/headphone_icon.png" alt="Listen" style="width:100px; filter:invert(1);">
                </div>
                <div class="instruction-bubble">Click to get today's<br>listening practice.</div>
            </div>
        </section>

    </main>

    <aside class="side-controls">
        <div class="ai-assistant">
            <div class="chat-bubble">Hi <?php echo $nickname; ?>, I'm AI assistant</div>
            <div class="icon-label"><img src="ai_icon.png" alt="AI"></div>
        </div>
    </aside>

    <script>
        
        function switchView(targetId) {
            if (!targetId) return;

            
            const allViews = document.querySelectorAll('.view-section');
            allViews.forEach(v => v.style.display = 'none');
            
            
            const targetView = document.getElementById(targetId);
            if (targetView) {
              
                if (targetId === 'welcome-view') {
                    targetView.style.display = 'block';
                } else {
                    targetView.style.display = 'flex';
                }
            }

            
            const navItems = document.querySelectorAll('.nav-item');
            navItems.forEach(item => {
                item.classList.toggle('active', item.getAttribute('data-target') === targetId);
            });
        }

        
        document.querySelector('.nav-container').addEventListener('click', (e) => {
            const targetId = e.target.getAttribute('data-target');
            if (targetId) switchView(targetId);
        });
    </script>
</body>
</html>