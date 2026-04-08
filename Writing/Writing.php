<?php
/**
 * Spires Academy - Academic Writing Laboratory (Dual-Mode Edition)
 * * Integrating AI Content Generation and Static Past Paper Repositories.
 * @package Spires_Learning_System
 * @version 2.5.0
 */

session_start();
require_once 'db_connect.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
$user_id = $_SESSION['user_id'];
$nickname = $_SESSION['nickname'] ?? 'Scholar';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Writing Lab | Spires Academy</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=Lora:ital,wght@0,400;0,700;1,400&family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --oxford-blue: #002147;
            --oxford-gold: #c4a661;
            --paper-bg: #FCFDFF;
            --shadow-subtle: 0 10px 40px rgba(0,33,71,0.1);
        }

        body { font-family: 'Montserrat', sans-serif; margin: 0; background: #f0f2f5; overflow-x: hidden; }

        /* --- NAVIGATION --- */
        .navbar { 
            background: var(--oxford-blue); height: 80px; padding: 0 50px;
            display: flex; justify-content: space-between; align-items: center;
            position: sticky; top: 0; z-index: 1000; box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        .nav-links { list-style: none; display: flex; gap: 5px; margin: 0; padding: 0; }
        .nav-links a { 
            color: white; text-decoration: none; font-family: 'Playfair Display', serif;
            font-size: 15px; font-weight: 700; padding: 10px 18px; text-transform: uppercase;
        }

        /* --- HERO --- */
        .hero {
            background: linear-gradient(rgba(0,33,71,0.85), rgba(0,33,71,0.85)), url('hero_bg2.png');
            background-size: cover; height: 320px; display: flex; flex-direction: column; align-items: center; justify-content: center; color: white;
        }
        .hero h1 { font-family: 'Playfair Display', serif; font-size: 3.5rem; margin: 0; letter-spacing: 4px; }

        /* --- WRITING LAB CORE --- */
        .writing-container { max-width: 1350px; margin: -80px auto 60px; display: flex; gap: 25px; min-height: 700px; }

        /* Professor Luna Panel (35%) */
        .luna-panel {
            flex: 0 0 32%; background: linear-gradient(135deg, var(--oxford-blue) 0%, #003366 100%);
            border-radius: 15px; display: flex; flex-direction: column; justify-content: flex-end; align-items: center; 
            position: relative; box-shadow: var(--shadow-subtle); border-bottom: 6px solid var(--oxford-gold);
        }
        .luna-speech {
            position: absolute; top: 40px; left: 30px; right: 30px;
            background: white; border-radius: 12px; padding: 25px;
            font-family: 'Playfair Display', serif; font-style: italic; font-size: 16px; color: var(--oxford-blue);
            border-left: 5px solid var(--oxford-gold); box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        /* Workspace (65%) */
        .workspace {
            flex: 1; background: white; border-radius: 15px; display: flex; flex-direction: column; padding: 45px;
            box-shadow: var(--shadow-subtle); border-top: 6px solid var(--oxford-blue);
        }

        /* Mode Selector & Paper Dropdown */
        .mode-tabs { display: flex; gap: 15px; margin-bottom: 25px; }
        .btn-mode {
            padding: 10px 20px; border: 2px solid var(--oxford-blue); border-radius: 5px;
            font-family: 'Playfair Display', serif; font-weight: 800; cursor: pointer; transition: 0.3s;
            background: white; color: var(--oxford-blue); font-size: 12px;
        }
        .btn-mode.active { background: var(--oxford-blue); color: white; }

        #paper-select {
            width: 100%; padding: 12px; border-radius: 5px; border: 2px solid #e2e8f0;
            font-family: 'Montserrat', sans-serif; margin-bottom: 20px; display: none;
        }

        .topic-box { 
            background: #f8fafc; border: 1px solid #e2e8f0; padding: 25px; border-radius: 8px; margin-bottom: 30px;
            border-left: 6px solid var(--oxford-gold);
        }
        .topic-box h3 { font-family: 'Playfair Display', serif; margin: 0; color: var(--oxford-blue); font-size: 1.3rem; }

        textarea {
            flex: 1; border: 2px solid #e2e8f0; border-radius: 10px; padding: 30px;
            font-family: 'Lora', serif; font-size: 18px; line-height: 1.9; outline: none;
            background: var(--paper-bg); transition: 0.3s;
        }
        textarea:focus { border-color: var(--oxford-gold); background: #fff; }

        .lab-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 30px; }
        .btn-submit {
            background: var(--oxford-blue); color: white; border: none;
            padding: 15px 35px; border-radius: 5px; font-weight: 800;
            cursor: pointer; transition: 0.3s; text-transform: uppercase; font-family: 'Playfair Display', serif;
        }
        .btn-submit:hover { background: var(--oxford-gold); color: var(--oxford-blue); transform: translateY(-3px); }

        /* Result Modal */
        #result-overlay {
            position: fixed; inset: 0; background: rgba(0,33,71,0.9);
            display: none; align-items: center; justify-content: center; z-index: 2000; backdrop-filter: blur(10px);
        }
        .result-card { background: white; width: 800px; padding: 50px; border-radius: 15px; border-top: 8px solid var(--oxford-gold); }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="logo-box"><img src="college_logo.png" alt="Spires Academy" style="height:45px;"></div>
        <ul class="nav-links">
            <li><a href="home.php">Dashboard</a></li>
            <li><a href="writing.php" style="color:var(--oxford-gold);">Writing Lab</a></li>
            <li><a href="vocabulary.php">Lexicon</a></li>
        </ul>
    </nav>

    <header class="hero">
        <h1>Writing Lab</h1>
        <p>Scholarly Excellence Through Rigorous Practice</p>
    </header>

    <div class="writing-container">
        <aside class="luna-panel">
            <div class="luna-speech" id="luna-bubble">
                Greetings, Scholar. Choose your mode of study. I can generate a <b>New Challenge</b> or we can review <b>Official Exams</b>.
            </div>
            <div class="luna-img" style="font-size: 280px; line-height: 1; margin-bottom: -15px;">👩‍🏫</div>
        </aside>

        <main class="workspace">
            <div class="mode-tabs">
                <button class="btn-mode active" onclick="switchMode('ai', this)">AI Generation</button>
                <button class="btn-mode" onclick="switchMode('pastpaper', this)">IELTS Past Papers</button>
            </div>

            <select id="paper-select" onchange="loadLocalPaper(this.value)">
                <option value="">-- Select a Cambridge IELTS 20 Paper --</option>
                <option value="data/ielts_cambridge_20_t1.json">Cambridge 20 - Test 1 (New!)</option>
                <option value="data/ielts_cambridge_20_t2.json">Cambridge 20 - Test 2 (New!)</option>
                <option value="data/ielts_cambridge_20_t3.json">Cambridge 20 - Test 3 (New!)</option>
                <option value="data/ielts_1.json">Cambridge 18 - Test 1</option>
            </select>

            <div class="topic-box">
                <h3 id="topic-text">Topic will be assigned...</h3>
            </div>

            <textarea id="essay-input" placeholder="Commence drafting your manuscript..."></textarea>

            <div class="lab-footer">
                <div style="font-family:'Playfair Display'; font-weight:800;">WORDS: <span id="word-num" style="color:var(--oxford-gold);">0</span></div>
                <div style="display:flex; gap:15px;">
                    <button class="btn-mode" id="gen-btn" onclick="generateTopic()">Generate Topic</button>
                    <button class="btn-submit" onclick="submitEssay()">Analyze Essay</button>
                </div>
            </div>
        </main>
    </div>

    <div id="result-overlay">
        <div class="result-card">
            <div id="final-score" style="font-size: 80px; color: var(--oxford-blue); text-align: center; font-family:'Playfair Display';">--</div>
            <div id="feedback-content" style="font-family:'Lora'; line-height:1.7;"></div>
            <button class="btn-submit" style="width:100%; margin-top:30px;" onclick="document.getElementById('result-overlay').style.display='none'">Dismiss</button>
        </div>
    </div>

    <script>
        let currentMode = 'ai';
        let activeTopic = "";

        function switchMode(mode, btn) {
            currentMode = mode;
            document.querySelectorAll('.btn-mode').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
            const select = document.getElementById('paper-select');
            const genBtn = document.getElementById('gen-btn');
            
            if(mode === 'ai') {
                select.style.display = 'none';
                genBtn.style.display = 'block';
                document.getElementById('topic-text').innerText = "AI Mode: Ready to generate.";
            } else {
                select.style.display = 'block';
                genBtn.style.display = 'none';
                document.getElementById('topic-text').innerText = "Paper Mode: Please select a test.";
            }
        }

        async function loadLocalPaper(file) {
            if(!file) return;
            const res = await fetch(file);
            const data = await res.json();
            activeTopic = data.question;
            document.getElementById('topic-text').innerText = activeTopic;
            document.getElementById('luna-bubble').innerText = `You are practicing ${data.id}. Focus on your academic register.`;
        }

        async function generateTopic() {
            document.getElementById('topic-text').innerText = "Luna is formulating a prompt...";
            const res = await fetch('writing_proxy.php', { method: 'POST', body: JSON.stringify({ action: 'get_topic' }) });
            const data = await res.json();
            activeTopic = data.topic;
            document.getElementById('topic-text').innerText = activeTopic;
        }

        async function submitEssay() {
            const content = document.getElementById('essay-input').value;
            if(content.length < 50) return alert("Insufficient length.");
            
            const res = await fetch('writing_proxy.php', {
                method: 'POST',
                body: JSON.stringify({ action: 'evaluate', topic: activeTopic, content: content })
            });
            const data = await res.json();
            document.getElementById('final-score').innerText = data.score;
            document.getElementById('feedback-content').innerHTML = `<p>${data.grammar}</p><p>${data.logic}</p>`;
            document.getElementById('result-overlay').style.display = 'flex';
        }

        document.getElementById('essay-input').addEventListener('input', function() {
            const words = this.value.trim().split(/\s+/).filter(x => x).length;
            document.getElementById('word-num').innerText = words;
        });
    </script>
    <script src="ai-agent.js?v=<?= time() ?>"></script>
</body>
</html>
