<?php
/**
 * Word Garden - Main Dashboard
 * * This is the central hub of the platform. It uses a single-page 
 * interface logic to switch between different study modules.
 */

session_start();

// Basic session-based user personalization
$nickname = $_SESSION['nickname'] ?? 'Student';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Word Garden - Your English Hub</title>
    
    <link rel="stylesheet" href="styles.css">

    <style>
        /* Vibrant Green Design System
           Focused on a calm, educational atmosphere.
        */
        :root {
            --primary-green: #5a8a31;
            --light-green: #a3d977;
            --bg-garden: #f1f8e9;
            --white: #ffffff;
            --transition-smooth: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            --shadow-soft: 0 10px 30px rgba(0,0,0,0.05);
        }

        body {
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
            background-color: var(--bg-garden);
            color: #333;
        }

        /* --- Navigation Layout --- */
        .navbar {
            height: 80px;
            background: var(--white);
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            padding: 0 30px;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-container {
            display: flex;
            gap: 10px;
            width: 100%;
            overflow-x: auto;
        }

        .nav-item {
            border: none;
            background: none;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 600;
            color: #777;
            cursor: pointer;
            border-radius: 12px;
            transition: var(--transition-smooth);
            white-space: nowrap;
        }

        .nav-item:hover, .nav-item.active {
            color: var(--primary-green);
            background: #f0f7f0;
        }

        /* --- Section Management --- */
        .view-section {
            width: 100%;
            height: calc(100vh - 80px);
            display: none; /* Controlled via JS */
            overflow-y: auto;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* --- Homepage Module --- */
        .homepage-section {
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: var(--bg-garden);
        }
        .homepage-img { 
            max-width: 75%; 
            border-radius: 24px; 
            box-shadow: 0 20px 50px rgba(0,0,0,0.1); 
        }

        /* --- IELTS Module --- */
        .ielts-selection-section { 
            display: flex; 
            justify-content: center; 
            align-items: center; 
        }
        .selection-container { display: flex; gap: 30px; }
        
        .ielts-box { 
            width: 320px; height: 180px; 
            background: var(--white); 
            border-radius: 24px; 
            display: flex; 
            justify-content: center; 
            align-items: center;
            cursor: pointer; 
            box-shadow: var(--shadow-soft);
            border: 3px solid transparent; 
            transition: var(--transition-smooth);
        }
        .ielts-box:hover { 
            border-color: var(--light-green); 
            transform: translateY(-10px); 
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }
        .ielts-title { font-size: 20px; font-weight: 700; color: var(--primary-green); text-align: center; }

        /* --- Daily Talk Module --- */
        .daily-talk-section { 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            background-color: var(--white); 
        }
        .interaction-hub { position: relative; width: 260px; height: 260px; }
        
        .action-circle-btn { 
            background: var(--light-green); 
            width: 100%; height: 100%; 
            border-radius: 50px; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            cursor: pointer;
            box-shadow: 0 10px 25px rgba(163, 217, 119, 0.4);
            transition: var(--transition-smooth);
        }
        .action-circle-btn:hover { transform: scale(1.05); background: var(--primary-green); }
        
        .hint-bubble {
            position: absolute; top: -110px; right: -50px; 
            background: var(--white); 
            border: 2px solid var(--light-green); 
            border-radius: 20px; 
            padding: 15px 20px;
            color: var(--primary-green); 
            font-weight: bold; 
            box-shadow: var(--shadow-soft);
        }

        /* --- TED Welcome Module --- */
        .ted-welcome { display: flex; align-items: center; justify-content: center; gap: 60px; padding: 60px; }
        .stage-illus { max-width: 550px; filter: drop-shadow(0 10px 20px rgba(0,0,0,0.1)); }
        
        /* Typography */
        h2 { font-size: 3rem; color: var(--primary-green); margin: 0; }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="nav-container" id="main-nav">
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
            <img src="homepage.png" alt="Welcome to the Garden" class="homepage-img">
        </section>

        <section id="welcome-view" class="welcome-section view-section">
            <div class="ted-welcome">
                <div class="illustration-box">
                    <img src="ted_stage.png" alt="TED Stage" class="stage-illus">
                </div>
                <div class="cloud-shape">
                    <h2>Ideas worth<br>spreading</h2>
                </div>
            </div>
            <div style="text-align: center;">
                <button onclick="location.href='TED.php'" style="background:none; border:none; cursor:pointer;">
                    <div style="font-size: 24px; font-weight: 900; color: #e62b1e;">TED<sup>x</sup> ENTER</div>
                </button>
            </div>
        </section>
        
        <section id="ielts-view" class="ielts-selection-section view-section">
            <div class="selection-container">
                <div class="ielts-box" onclick="location.href='ielts.php?cam=19'">
                    <div class="ielts-title">Cambridge IELTS 19</div>
                </div>
                <div class="ielts-box" onclick="location.href='ielts.php?cam=20'">
                    <div class="ielts-title">Cambridge IELTS 20</div>
                </div>
            </div>
        </section>

        <section id="daily-talk-view" class="daily-talk-section view-section">
            <div class="interaction-hub">
                <div class="action-circle-btn" onclick="location.href='daily_decryption.php'">
                    <img src="static/images/headphone_icon.png" alt="Listen" style="width:80px; filter:brightness(0) invert(1);">
                </div>
                <div class="hint-bubble">Ready for today's<br>listening secret?</div>
            </div>
        </section>

    </main>

    <script src="ai-agent.js?v=<?= time() ?>"></script>

    <script>
        /**
         * Navigation Logic
         * Handles the seamless switching between different platform views.
         */
        function switchView(targetId) {
            if (!targetId) return;

            // Hide all views first
            const sections = document.querySelectorAll('.view-section');
            sections.forEach(sec => sec.style.display = 'none');
            
            // Activate the selected view
            const target = document.getElementById(targetId);
            if (target) {
                // Ensure correct flex/block display modes
                if (targetId === 'welcome-view') {
                    target.style.display = 'block';
                } else {
                    target.style.display = 'flex';
                }
            }

            // Update Navbar UI state
            const buttons = document.querySelectorAll('.nav-item');
            buttons.forEach(btn => {
                btn.classList.toggle('active', btn.getAttribute('data-target') === targetId);
            });
        }

        // Event Delegation for Navigation
        document.getElementById('main-nav').addEventListener('click', (event) => {
            const targetId = event.target.getAttribute('data-target');
            if (targetId) switchView(targetId);
        });
    </script>
</body>
</html>
