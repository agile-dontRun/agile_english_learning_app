<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Luna Academic Writing Center - Dual Engine</title>
    <style>
        /* Visual Identity: Oxford Blue & Academic White
           Designed for focus-heavy environments.
        */
        :root {
            --primary: #2563EB;
            --dark: #1E3A8A;
            --bg: #F8FAFC;
            --text-slate: #1E293B;
            --border-gray: #E2E8F0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            background: var(--bg);
            overflow: hidden; /* Prevent body scroll to keep the layout static */
        }

        /* Top Navigation Bar */
        .main-nav {
            background: #fff;
            border-bottom: 1px solid var(--border-gray);
            display: flex;
            padding: 0 40px;
            gap: 20px;
            height: 60px;
            align-items: center;
        }

        .nav-item {
            text-decoration: none;
            color: #64748B;
            font-weight: 500;
            font-size: 14px;
            padding: 5px 0;
            transition: color 0.3s;
        }

        .nav-item.active {
            color: var(--primary);
            border-bottom: 2px solid var(--primary);
        }

        /* 45/55 Split Layout */
        #writing-wrapper {
            display: flex;
            width: 100vw;
            height: calc(100vh - 60px);
        }

        /* Left Side: Luna Tutor (45% Width) */
        #luna-side {
            flex: 0 0 45%;
            background: linear-gradient(135deg, var(--dark) 0%, var(--primary) 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;
            position: relative;
            color: white;
        }

        #luna-avatar {
            font-size: 350px;
            line-height: 1;
            margin-bottom: -15px;
            filter: drop-shadow(0 20px 40px rgba(0, 0, 0, 0.4));
            user-select: none;
        }

        #luna-bubble {
            position: absolute;
            top: 50px; left: 40px; right: 40px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(12px);
            padding: 30px;
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            font-size: 17px;
            line-height: 1.6;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }

        /* Right Side: Working Area (Remaining Width) */
        #work-side {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: white;
            padding: 40px;
            box-sizing: border-box;
        }

        /* Tab Switchers */
        .mode-tabs {
            display: flex;
            gap: 12px;
            margin-bottom: 25px;
        }

        .tab-btn {
            padding: 10px 24px;
            border: 1.5px solid var(--primary);
            border-radius: 10px;
            cursor: pointer;
            background: white;
            color: var(--primary);
            font-weight: 600;
            transition: 0.3s ease;
        }

        .tab-btn.active {
            background: var(--primary);
            color: white;
        }

        #paper-select {
            width: 100%;
            padding: 14px;
            margin-bottom: 20px;
            border-radius: 10px;
            border: 1px solid var(--border-gray);
            font-size: 15px;
            color: var(--text-slate);
            display: none; /* Only visible in Past Paper mode */
            outline: none;
        }

        #topic-container {
            background: #EFF6FF;
            border-left: 6px solid var(--primary);
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 25px;
        }

        #topic-text {
            font-weight: 600;
            color: var(--text-slate);
            margin: 0;
            font-size: 18px;
            line-height: 1.5;
        }

        textarea {
            flex: 1;
            border: 1px solid var(--border-gray);
            border-radius: 16px;
            padding: 30px;
            font-size: 19px;
            line-height: 1.8;
            font-family: 'Georgia', serif; /* Academic Standard Font */
            outline: none;
            resize: none;
            background: #FCFDFF;
            transition: border 0.3s;
        }

        textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.05);
        }

        .footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 25px;
        }

        .btn-submit {
            background: var(--primary);
            color: white;
            border: none;
            padding: 14px 45px;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(37, 99, 235, 0.3);
        }

        /* Result Modal System */
        #result-overlay {
            position: fixed; inset: 0;
            background: rgba(15, 23, 42, 0.85);
            display: none; align-items: center; justify-content: center;
            z-index: 1000; backdrop-filter: blur(8px);
        }

        #result-card {
            background: white;
            width: 800px;
            padding: 50px;
            border-radius: 24px;
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: 0 30px 60px rgba(0,0,0,0.3);
        }

        .score-display {
            font-size: 64px;
            color: var(--primary);
            text-align: center;
            font-weight: 800;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>

    <nav class="main-nav">
        <a href="vocabulary.php" class="nav-item">VOCABULARY</a>
        <a href="writing.php" class="nav-item active">ACADEMIC WRITING</a>
        <a href="home.php" class="nav-item">DASHBOARD</a>
    </nav>

    <div id="writing-wrapper">
        <div id="luna-side">
            <div id="luna-bubble">
                Hello! I am your mentor, Luna. Shall we focus on <b>AI-Generated</b> topics or practice with <b>Official IELTS</b> papers today?
            </div>
            <div id="luna-avatar">👩‍🏫</div>
        </div>

        <div id="work-side">
            <div class="mode-tabs">
                <button class="tab-btn active" id="tab-ai" onclick="switchMode('ai', this)">AI Generation</button>
                <button class="tab-btn" id="tab-paper" onclick="switchMode('pastpaper', this)">IELTS Past Papers</button>
            </div>

            <select id="paper-select" onchange="loadLocalPaper(this.value)">
                <option value="">-- Select a Cambridge IELTS 20 Paper --</option>
                <option value="data/ielts_cambridge_20_t1.json">Cambridge 20 - Test 1 (Latest)</option>
                <option value="data/ielts_cambridge_20_t2.json">Cambridge 20 - Test 2 (Latest)</option>
                <option value="data/ielts_cambridge_20_t3.json">Cambridge 20 - Test 3 (Latest)</option>
                <option value="data/ielts_1.json">Cambridge 18 - Test 1</option>
            </select>

            <div id="topic-container">
                <p id="topic-text">Welcome back. Please select a mode to display your writing prompt.</p>
            </div>

            <textarea id="essay-input" placeholder="Start typing your academic essay here..."></textarea>

            <div class="footer">
                <div style="color: #64748B; font-weight: 500;">Word Count: <span id="word-count" style="color:var(--primary); font-weight:700;">0</span></div>
                <div style="display:flex; gap:12px;">
                    <button class="tab-btn" id="gen-btn" onclick="generateAITopic()">Generate Topic</button>
                    <button class="btn-submit" onclick="submitEvaluation()">Submit for Evaluation</button>
                </div>
            </div>
        </div>
    </div>

    <div id="result-overlay">
        <div id="result-card">
            <div class="score-display" id="res-score">8.5</div>
            <div id="res-feedback" style="line-height: 1.7; color: #475569; font-size: 16px;"></div>
            <button class="btn-submit" style="width: 100%; margin-top:30px;"
                onclick="document.getElementById('result-overlay').style.display='none'">Thank you, Luna</button>
        </div>
    </div>

    <script>
        /**
         * GLOBAL STATE & CONFIG
         */
        let currentMode = 'ai';
        let activeTopic = "";

        /**
         * UI Logic: Toggle between AI-Gen mode and Past Paper mode.
         */
        function switchMode(mode, btn) {
            currentMode = mode;
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const select = document.getElementById('paper-select');
            const genBtn = document.getElementById('gen-btn');
            const topicDisplay = document.getElementById('topic-text');

            if (mode === 'ai') {
                select.style.display = 'none';
                genBtn.style.display = 'block';
                topicDisplay.innerText = "Mode: AI Intelligence. Click 'Generate' to fetch a new academic prompt.";
            } else {
                select.style.display = 'block';
                genBtn.style.display = 'none';
                topicDisplay.innerText = "Mode: Past Papers. Please select a specific test from the dropdown above.";
            }
        }

        /**
         * Data Logic: Load static JSON files from the /data directory.
         */
        async function loadLocalPaper(file) {
            if (!file) return;
            try {
                const res = await fetch(file);
                const data = await res.json();
                activeTopic = data.question;
                document.getElementById('topic-text').innerText = activeTopic;
                document.getElementById('luna-bubble').innerText = `You are now practicing ${data.id}. This is a real ${data.type}. Focus on the task requirements!`;
            } catch (err) {
                console.error("Failed to load paper:", err);
                alert("Error loading the requested paper.");
            }
        }

        /**
         * API Logic: Fetch a dynamic prompt from the AI backend.
         */
        async function generateAITopic() {
            const topicDisplay = document.getElementById('topic-text');
            topicDisplay.innerText = "Luna is formulating a topic... please hold.";
            
            try {
                const res = await fetch('writing_proxy.php', { 
                    method: 'POST', 
                    body: JSON.stringify({ action: 'get_topic' }) 
                });
                const data = await res.json();
                activeTopic = data.topic;
                topicDisplay.innerText = activeTopic;
                document.getElementById('luna-bubble').innerText = "I've picked a challenging one for you. Let's see how you handle it!";
            } catch (err) {
                topicDisplay.innerText = "Connection error. Please try again.";
            }
        }

        /**
         * API Logic: Send essay content to Luna for grading and polishing.
         */
        async function submitEvaluation() {
            const content = document.getElementById('essay-input').value.trim();
            if (content.length < 50) return alert("Your essay is too short for a proper evaluation. (Min 50 words)");

            document.getElementById('luna-bubble').innerText = "Analyzing your structure, grammar, and lexical resource... I'll have your results shortly.";

            try {
                const res = await fetch('writing_proxy.php', {
                    method: 'POST',
                    body: JSON.stringify({ action: 'evaluate', topic: activeTopic, content: content })
                });
                const data = await res.json();

                // Populate and show the result modal
                document.getElementById('res-score').innerText = data.score;
                document.getElementById('res-feedback').innerHTML = `
                    <p><b>Grammar & Vocab:</b> ${data.grammar}</p>
                    <p><b>Structure & Logic:</b> ${data.logic}</p>
                    <div style="background:#F1F5F9; padding:20px; border-radius:12px; margin-top:20px;">
                        <b>Luna's Academic Polish:</b><br>
                        <i style="color:#334155;">${data.polished}</i>
                    </div>
                `;
                document.getElementById('result-overlay').style.display = 'flex';
            } catch (err) {
                alert("Evaluation failed. Please check your internet connection.");
            }
        }

        /**
         * Utility: Real-time Word Counter
         */
        document.getElementById('essay-input').addEventListener('input', function () {
            const words = this.value.trim().split(/\s+/).filter(x => x).length;
            document.getElementById('word-count').innerText = words;
        });
    </script>
    
    <script src="ai-agent.js?v=<?php echo time(); ?>"></script>
</body>

</html>
