<?php
session_start();
// Security check: If user is not logged in, redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
$nickname = $_SESSION['nickname'] ?? 'Student';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Word Garden - Learning Center</title>
    <style>
        /* ===== Premium Green Color System ===== */
        :root {
            --primary-green: #1b4332;    /* Deep Forest Green */
            --accent-green: #40916c;     /* Sage Green */
            --soft-green-bg: #f2f7f5;    /* Ultra Light Green Background */
            --card-shadow: 0 10px 30px rgba(27, 67, 50, 0.08);
            --card-shadow-hover: 0 20px 40px rgba(27, 67, 50, 0.15);
            --text-main: #2d3436;
        }

        body {
            margin: 0;
            padding: 0;
            background-color: var(--soft-green-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-main);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* ===== 1. Hero Section ===== */
        .hero-banner {
            background: linear-gradient(135deg, #081c15 0%, #1b4332 100%);
            color: #d8f3dc;
            padding: 100px 20px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero-banner::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background-image: radial-gradient(circle at 2px 2px, rgba(255,255,255,0.05) 1px, transparent 0);
            background-size: 30px 30px;
            opacity: 0.5;
        }

        .hero-banner h1 {
            font-size: 2.8rem;
            margin: 0 0 15px;
            font-weight: 600;
            color: #ffffff;
            letter-spacing: 2px;
        }

        .hero-banner p {
            font-size: 1.1rem;
            opacity: 0.8;
            font-weight: 300;
            text-transform: uppercase;
            letter-spacing: 3px;
        }

        /* ===== 2. Main Container ===== */
        .main-container {
            flex: 1;
            width: 100%;
            max-width: 1200px;
            margin: -60px auto 60px;
            padding: 0 20px;
            box-sizing: border-box;
            z-index: 10;
        }

        /* ===== 3. Function Grid ===== */
        .function-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
        }

        /* ===== 4. Function Card Style ===== */
        .function-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 40px 30px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            cursor: pointer;
            box-shadow: var(--card-shadow);
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            position: relative;
        }

        .function-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--card-shadow-hover);
        }

        .card-icon {
            width: 70px;
            height: 70px;
            margin-bottom: 20px;
            background-color: #f7fdfa;
            border: 1px solid #e9f5ef;
            border-radius: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 28px;
            color: var(--primary-green);
            transition: all 0.3s ease;
        }

        .function-card:hover .card-icon {
            background-color: var(--primary-green);
            color: #ffffff;
            transform: scale(1.1);
        }

        .card-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--primary-green);
            margin: 0 0 10px;
        }

        .card-desc {
            font-size: 0.9rem;
            color: #6d7d76;
            margin: 0;
            line-height: 1.6;
        }

        /* ===== 5. Floating AI Assistant ===== */
        .side-controls {
            position: fixed;
            bottom: 40px;
            right: 40px;
            z-index: 100;
        }

        .ai-assistant {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .chat-bubble {
            background: #ffffff;
            color: var(--primary-green);
            padding: 12px 20px;
            border-radius: 20px 20px 5px 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            font-size: 14px;
            border: 1px solid #eef5f2;
        }

        .ai-icon-circle {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--accent-green), var(--primary-green));
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 8px 20px rgba(27, 67, 50, 0.2);
            cursor: pointer;
            color: white;
            font-weight: bold;
            font-size: 18px;
            transition: all 0.3s ease;
        }

        .ai-icon-circle:hover {
            transform: scale(1.1);
        }

        @media (max-width: 768px) {
            .hero-banner h1 { font-size: 2rem; }
            .main-container { margin-top: -40px; }
        }
    </style>
</head>
<body>

    <header class="hero-banner">
        <div class="container">
            <h1>Welcome back, <?php echo $nickname; ?></h1>
            <p>Word Garden | Premium Learning Space</p>
        </div>
    </header>
    
    <main class="main-container">
        <div class="function-grid">
            
            <div class="function-card" onclick="window.location.href='TED.php'">
                <div class="card-icon">📺</div>
                <h3 class="card-title">TED TALK</h3>
                <p class="card-desc">Expand your horizons and sharpen <br>your deep listening skills.</p>
            </div>

            <div class="function-card" onclick="window.location.href='ielts.php'">
                <div class="card-icon">🌲</div>
                <h3 class="card-title">IELTS LISTENING</h3>
                <p class="card-desc">Practical mock exams to help <br>you master IELTS listening skills.</p>
            </div>

            <div class="function-card" onclick="window.location.href='daily_decryption.php'">
                <div class="card-icon">🌿</div>
                <h3 class="card-title">DAILY TALK</h3>
                <p class="card-desc">Immersive dialogue practice <br>for more natural expression.</p>
            </div>

            <div class="function-card" onclick="window.location.href='vocabulary.php'">
                <div class="card-icon">🍀</div>
                <h3 class="card-title">VOCABULARY</h3>
                <p class="card-desc">Manage your word garden and <br>efficiently consolidate your memory.</p>
            </div>

            <div class="function-card" onclick="window.location.href='calendar.php'">
                <div class="card-icon">🗓️</div>
                <h3 class="card-title">CALENDAR</h3>
                <p class="card-desc">Track your learning journey and <br>witness your daily progress.</p>
            </div>

            <div class="function-card" onclick="alert('GROUP feature is coming soon, stay tuned!')">
                <div class="card-icon">👥</div>
                <h3 class="card-title">COMMUNITY</h3>
                <p class="card-desc">Join study groups and grow <br>together with learners worldwide.</p>
            </div>

            <div class="function-card" onclick="window.location.href='profile.php'">
                <div class="card-icon">👤</div>
                <h3 class="card-title">PROFILE</h3>
                <p class="card-desc">View your profile, learning <br>progress, and account settings.</p>
            </div>

        </div>
    </main>

    <aside class="side-controls">
        <div class="ai-assistant">
            <div class="chat-bubble">Hello <?php echo $nickname; ?>, are you ready to start today's lesson?</div>
            <div class="ai-icon-circle">AI</div>
        </div>
    </aside>

</body>
</html>