<?php
/**
 * Spires Academy - Academic Writing Laboratory
 * * An advanced immersive writing environment integrated with Professor Luna (AI).
 * Features: Real-time context-aware feedback, Academic tone scoring, 
 * and Seamless User Session integration.
 * * @package Spires_Learning_System
 * @version 2.4.0
 */

session_start();
require_once 'db_connect.php'; 

/* -------------------------------------------------------------------------
   1. SESSION & SECURITY PROTOCOL
   ------------------------------------------------------------------------- */
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

/* -------------------------------------------------------------------------
   2. DATA ACQUISITION: USER PROFILE
   ------------------------------------------------------------------------- */
$username = ''; $nickname = 'Scholar'; $db_avatar = '';
$stmt = $conn->prepare("SELECT username, nickname, avatar_url FROM users WHERE user_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_data = $stmt->get_result()->fetch_assoc();
    if ($user_data) {
        $username = $user_data['username'];
        $nickname = !empty($user_data['nickname']) ? $user_data['nickname'] : $username;
        $db_avatar = $user_data['avatar_url'];
    }
    $stmt->close();
}

// Dynamic Avatar Rendering Logic
$avatar_html = '';
$first_letter = strtoupper(substr($username ? $username : 'U', 0, 1));
if (!empty($db_avatar)) {
    $avatar_html = '<img src="' . htmlspecialchars($db_avatar) . '" alt="Avatar" class="user-avatar-img" onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'flex\';">';
    $avatar_html .= '<div class="user-avatar-placeholder" style="display:none;">' . htmlspecialchars($first_letter) . '</div>';
} else {
    $avatar_html = '<div class="user-avatar-placeholder">' . htmlspecialchars($first_letter) . '</div>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Writing Laboratory | Spires Academy</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=Lora:ital,wght@0,400;0,700;1,400&family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --oxford-blue: #002147;
            --oxford-gold: #c4a661;
            --ivory-white: #ffffff;
            --paper-bg: #FCFDFF;
            --shadow-subtle: 0 10px 40px rgba(0,33,71,0.1);
        }

        body { 
            font-family: 'Montserrat', sans-serif; 
            margin: 0; background: #f0f2f5; 
            color: #1a1a1a; overflow-x: hidden; 
        }

        /* --- 1. NAVIGATION (Oxford Persistent Header) --- */
        .navbar { 
            background: var(--oxford-blue); 
            height: 80px; padding: 0 50px;
            display: flex; justify-content: space-between; align-items: center;
            position: sticky; top: 0; z-index: 1000;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        .nav-left { display: flex; align-items: center; gap: 40px; }
        .logo-box img { height: 45px; transition: 0.3s; }
        
        .nav-links { list-style: none; display: flex; gap: 5px; margin: 0; padding: 0; }
        .nav-links a { 
            color: white; text-decoration: none; font-family: 'Playfair Display', serif;
            font-size: 15px; font-weight: 700; padding: 10px 18px; 
            text-transform: uppercase; letter-spacing: 1.2px; transition: 0.3s;
        }
        .nav-links a:hover { color: var(--oxford-gold); }

        .user-meta { display: flex; align-items: center; gap: 12px; color: white; cursor: pointer; position: relative; }
        .user-avatar-placeholder { width: 38px; height: 38px; border-radius: 50%; background: var(--oxford-gold); color: var(--oxford-blue); display: flex; align-items: center; justify-content: center; font-weight: 800; border: 2px solid #fff; }

        /* --- 2. HERO SECTION --- */
        .hero {
            background: linear-gradient(rgba(0,33,71,0.85), rgba(0,33,71,0.85)), url('hero_bg2.png');
            background-size: cover; background-position: center;
            height: 380px; display: flex; flex-direction: column; align-items: center; justify-content: center;
            color: white; text-align: center;
        }
        .hero h1 { font-family: 'Playfair Display', serif; font-size: 4.5rem; margin: 0; letter-spacing: 4px; text-transform: uppercase; }
        .hero p { font-family: 'Lora', serif; font-style: italic; font-size: 1.3rem; opacity: 0.9; margin-top: 10px; }

        /* --- 3. WRITING LAB CORE --- */
        .writing-container {
            max-width: 1350px; margin: -100px auto 60px;
            display: flex; gap: 25px; min-height: 700px;
        }

        /* Professor Luna Panel (35% Width) */
        .luna-panel {
            flex: 0 0 32%;
            background: linear-gradient(135deg, var(--oxford-blue) 0%, #003366 100%);
            border-radius: 15px; display: flex; flex-direction: column; 
            justify-content: flex-end; align-items: center; position: relative;
            box-shadow: var(--shadow-subtle); border-bottom: 6px solid var(--oxford-gold);
        }
        .luna-speech {
            position: absolute; top: 40px; left: 30px; right: 30px;
            background: white; border-radius: 12px; padding: 25px;
            font-family: 'Playfair Display', serif; font-style: italic; font-size: 17px;
            line-height: 1.6; color: var(--oxford-blue); box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            border-left: 5px solid var(--oxford-gold);
        }
        .luna-speech::after {
            content: ''; position: absolute; bottom: -15px; left: 50%; 
            border-width: 15px 15px 0; border-style: solid; border-color: white transparent transparent; transform: translateX(-50%);
        }
        .luna-img { font-size: 300px; line-height: 1; margin-bottom: -10px; filter: drop-shadow(0 15px 30px rgba(0,0,0,0.4)); }

        /* Scholarly Workspace (65% Width) */
        .workspace {
            flex: 1; background: white; border-radius: 15px; 
            display: flex; flex-direction: column; padding: 45px;
            box-shadow: var(--shadow-subtle); border-top: 6px solid var(--oxford-blue);
        }
        .topic-box { 
            background: #f8fafc; border: 1px solid #e2e8f0; 
            padding: 25px; border-radius: 8px; margin-bottom: 30px;
            border-left: 6px solid var(--oxford-gold);
        }
        .topic-box h3 { font-family: 'Playfair Display', serif; margin: 0; color: var(--oxford-blue); font-size: 1.4rem; }

        .editor-box { flex: 1; display: flex; flex-direction: column; }
        textarea {
            flex: 1; border: 2px solid #e2e8f0; border-radius: 10px;
            padding: 35px; font-family: 'Lora', serif; font-size: 18px;
            line-height: 1.9; outline: none; transition: 0.3s;
            background: var(--paper-bg); color: #2c3e50;
        }
        textarea:focus { border-color: var(--oxford-gold); background: #fff; }

        .lab-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 30px; }
        .metric { font-family: 'Playfair Display', serif; font-weight: 800; color: var(--oxford-blue); letter-spacing: 1px; font-size: 14px; }

        .btn {
            padding: 15px 35px; border-radius: 5px; border: none;
            font-family: 'Playfair Display', serif; font-weight: 800;
            text-transform: uppercase; letter-spacing: 2px; cursor: pointer;
            transition: 0.3s; font-size: 13px;
        }
        .btn-gold { background: var(--oxford-gold); color: var(--oxford-blue); }
        .btn-gold:hover { background: var(--oxford-blue); color: white; transform: translateY(-3px); }
        .btn-blue { background: var(--oxford-blue); color: white; }
        .btn-blue:hover { background: var(--oxford-gold); color: var(--oxford-blue); transform: translateY(-3px); }

        /* --- 4. RESULT MODAL --- */
        #result-overlay {
            position: fixed; inset: 0; background: rgba(0,33,71,0.9);
            display: none; align-items: center; justify-content: center; z-index: 2000; backdrop-filter: blur(10px);
        }
        .result-card {
            background: white; width: 850px; max-height: 85vh; border-radius: 15px;
            padding: 50px; overflow-y: auto; border-top: 8px solid var(--oxford-gold);
        }
        .score-circle { font-family: 'Playfair Display', serif; font-size: 80px; color: var(--oxford-blue); text-align: center; margin-bottom: 30px; }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="nav-left">
            <div class="logo-box"><img src="college_logo.png" alt="Spires Academy"></div>
            <ul class="nav-links">
                <li><a href="home.php">Dashboard</a></li>
                <li><a href="writing.php" style="color:var(--oxford-gold);">Writing Lab</a></li>
                <li><a href="vocabulary.php">Lexicon</a></li>
                <li><a href="forum.php">Common Room</a></li>
            </ul>
        </div>
        <div class="user-meta">
            <?php echo $avatar_html; ?>
            <span style="font-family:'Playfair Display'; font-weight:800; letter-spacing:1px;"><?php echo htmlspecialchars($nickname); ?></span>
        </div>
    </nav>

    <header class="hero">
        <h1>Writing Lab</h1>
        <p>Mastering the Art of Academic Persuasion</p>
    </header>

    <div class="writing-container">
        <aside class="luna-panel">
            <div class="luna-speech" id="luna-bubble">
                Welcome back, Scholar. Are you prepared to tackle a new academic proposition today?
            </div>
            <div class="luna-img">👩‍🏫</div>
        </aside>

        <main class="workspace">
            <div class="topic-box">
                <h3 id="topic-text">Topic will be assigned upon generation...</h3>
            </div>

            <div class="editor-box">
                <textarea id="essay-input" placeholder="Draft your thesis and arguments here..."></textarea>
            </div>

            <div class="lab-footer">
                <div class="metric">WORD COUNT: <span id="word-num" style="color:var(--oxford-gold); font-size:1.4rem;">0</span></div>
                <div style="display:flex; gap:15px;">
                    <button class="btn btn-gold" onclick="generateTopic()">Generate Topic</button>
                    <button class="btn btn-blue" id="submit-btn" onclick="submitEssay()">Analyze Essay</button>
                </div>
            </div>
        </main>
    </div>

    <div id="result-overlay">
        <div class="result-card">
            <div class="score-circle" id="final-score">--</div>
            <div id="feedback-content"></div>
            <button class="btn btn-blue" style="width:100%; margin-top:30px;" onclick="closeResult()">Acknowledge Feedback</button>
        </div>
    </div>

    <script>
        const currentUserId = <?php echo json_encode($user_id); ?>;
        const essayInput = document.getElementById('essay-input');
        const lunaBubble = document.getElementById('luna-bubble');
        const topicText = document.getElementById('topic-text');
        const wordNum = document.getElementById('word-num');
        let currentTopic = "";

        // Scholarly word count implementation
        essayInput.addEventListener('input', () => {
            const words = essayInput.value.trim().split(/\s+/).filter(w => w.length > 0);
            wordNum.innerText = words.length;
        });

        // Interface Logic: Topic Retrieval
        async function generateTopic() {
            lunaBubble.innerText = "Accessing the Spires Academy prompt archives...";
            try {
                const res = await fetch('writing_proxy.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'get_topic' })
                });
                const data = await res.json();
                currentTopic = data.topic;
                topicText.innerText = currentTopic;
                lunaBubble.innerText = "A stimulating topic. Focus on logical progression and formal register.";
            } catch (e) {
                lunaBubble.innerText = "Connection lost to the archives. Please re-attempt.";
            }
        }

        // Interface Logic: AI Evaluation
        async function submitEssay() {
            const content = essayInput.value.trim();
            if (content.length < 50) return alert("Academic rigor requires a more substantial draft.");

            document.getElementById('submit-btn').disabled = true;
            lunaBubble.innerText = "Professor Luna is reviewing your manuscript. Please remain patient...";

            try {
                const res = await fetch('writing_proxy.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        action: 'evaluate', 
                        user_id: currentUserId, 
                        topic: currentTopic, 
                        content: content 
                    })
                });
                const data = await res.json();
                showResult(data);
                document.getElementById('submit-btn').disabled = false;
            } catch (e) {
                lunaBubble.innerText = "The evaluation was interrupted. Let us try once more.";
                document.getElementById('submit-btn').disabled = false;
            }
        }

        function showResult(data) {
            document.getElementById('final-score').innerText = data.score;
            document.getElementById('feedback-content').innerHTML = `
                <div style="margin-bottom:25px;">
                    <h4 style="font-family:'Playfair Display'; border-bottom:2px solid var(--oxford-gold); padding-bottom:5px;">GRAMMATICAL PRECISION</h4>
                    <p style="font-family:'Lora'; line-height:1.7;">${data.grammar}</p>
                </div>
                <div style="margin-bottom:25px;">
                    <h4 style="font-family:'Playfair Display'; border-bottom:2px solid var(--oxford-gold); padding-bottom:5px;">STRUCTURAL COHERENCE</h4>
                    <p style="font-family:'Lora'; line-height:1.7;">${data.logic}</p>
                </div>
                <div style="background:#f8fafc; padding:30px; border-radius:10px; border-left:5px solid var(--oxford-blue);">
                    <h4 style="font-family:'Playfair Display'; margin-top:0;">LUNA'S REFINED MANUSCRIPT</h4>
                    <p style="font-family:'Lora'; font-style:italic;">${data.polished}</p>
                </div>
            `;
            document.getElementById('result-overlay').style.display = 'flex';
        }

        function closeResult() {
            document.getElementById('result-overlay').style.display = 'none';
        }
    </script>
    
    <script src="ai-agent.js?v=<?php echo time(); ?>"></script>
</body>
</html>
