<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Luna Writing Center - Dual Mode</title>
    <style>
        :root {
            --primary: #2563EB;
            --dark: #1E3A8A;
            --bg: #F8FAFC;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            background: var(--bg);
            overflow: hidden;
        }

        .main-nav {
            background: #fff;
            border-bottom: 1px solid #E2E8F0;
            display: flex;
            padding: 15px 40px;
            gap: 20px;
            height: 50px;
            align-items: center;
        }

        .nav-item {
            text-decoration: none;
            color: #64748B;
            font-weight: 500;
            font-size: 14px;
        }

        .nav-item.active {
            color: var(--primary);
            border-bottom: 2px solid var(--primary);
        }

        #writing-wrapper {
            display: flex;
            width: 100vw;
            height: calc(100vh - 50px);
        }

        /* Luna 老师占 45% */
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
        }

        #luna-bubble {
            position: absolute;
            top: 50px;
            left: 40px;
            right: 40px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(12px);
            padding: 25px;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            font-size: 16px;
            line-height: 1.6;
        }

        /* 写作区域 */
        #work-side {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: white;
            padding: 40px;
            box-sizing: border-box;
        }

        .mode-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .tab-btn {
            padding: 10px 20px;
            border: 1.5px solid var(--primary);
            border-radius: 8px;
            cursor: pointer;
            background: white;
            color: var(--primary);
            font-weight: bold;
        }

        .tab-btn.active {
            background: var(--primary);
            color: white;
        }

        #paper-select {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 8px;
            border: 1px solid #E2E8F0;
            display: none;
        }

        #topic-container {
            background: #EFF6FF;
            border-left: 5px solid var(--primary);
            padding: 20px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        #topic-text {
            font-weight: 600;
            color: #1E293B;
            margin: 0;
            font-size: 17px;
        }

        textarea {
            flex: 1;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            padding: 25px;
            font-size: 18px;
            line-height: 1.8;
            font-family: 'Georgia', serif;
            outline: none;
            resize: none;
            background: #FCFDFF;
        }

        .footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
        }

        .btn-submit {
            background: var(--primary);
            color: white;
            border: none;
            padding: 14px 40px;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
        }

        /* 弹窗 */
        #result-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.7);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 100;
        }

        #result-card {
            background: white;
            width: 700px;
            padding: 40px;
            border-radius: 20px;
            max-height: 80vh;
            overflow-y: auto;
        }
    </style>
</head>

<body>

    <nav class="main-nav">
        <a href="vocabulary.php" class="nav-item">VOCABULARY</a>
        <a href="writing.php" class="nav-item active">ACADEMIC WRITING</a>
    </nav>

    <div id="writing-wrapper">
        <div id="luna-side">
            <div id="luna-bubble">Hello! I'm Luna. You can choose to challenge an <b>AI-generated</b> topic or practice
                with <b>Real Exam</b> papers.</div>
            <div id="luna-avatar">👩‍🏫</div>
        </div>

        <div id="work-side">
            <div class="mode-tabs">
                <button class="tab-btn active" onclick="switchMode('ai', this)">AI Generation</button>
                <button class="tab-btn" onclick="switchMode('pastpaper', this)">Real Past Papers</button>
            </div>

            <select id="paper-select" onchange="loadLocalPaper(this.value)">
                <option value="">-- Choose a Cambridge/Real Paper --</option>
                <option value="data/ielts_1.json">Cambridge 18 - Test 1</option>
                <option value="data/ielts_2.json">Cambridge 18 - Test 2</option>
            </select>

            <div id="topic-container">
                <p id="topic-text">Welcome! Click "Generate Topic" or select a past paper to start.</p>
            </div>

            <textarea id="essay-input" placeholder="Start your academic essay here..."></textarea>

            <div class="footer">
                <div style="color: #64748B;">Words: <span id="word-count">0</span></div>
                <div style="display:flex; gap:10px;">
                    <button class="tab-btn" id="gen-btn" onclick="generateAITopic()">Generate AI Topic</button>
                    <button class="btn-submit" onclick="submitEvaluation()">Submit for Evaluation</button>
                </div>
            </div>
        </div>
    </div>

    <div id="result-overlay">
        <div id="result-card">
            <h2 id="res-score" style="font-size: 40px; color: var(--primary); text-align: center;">8.0</h2>
            <div id="res-feedback"></div>
            <button class="btn-submit" style="width: 100%; margin-top:20px;"
                onclick="document.getElementById('result-overlay').style.display='none'">Got it, Luna!</button>
        </div>
    </div>

    <script>
        let currentMode = 'ai';
        let activeTopic = "";

        // 切换模式
        function switchMode(mode, btn) {
            currentMode = mode;
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const select = document.getElementById('paper-select');
            const genBtn = document.getElementById('gen-btn');

            if (mode === 'ai') {
                select.style.display = 'none';
                genBtn.style.display = 'block';
                document.getElementById('topic-text').innerText = "AI Mode: Click 'Generate' to start.";
            } else {
                select.style.display = 'block';
                genBtn.style.display = 'none';
                document.getElementById('topic-text').innerText = "Real Paper Mode: Select a paper from the list.";
            }
        }

        // 加载本地真题 JSON
        async function loadLocalPaper(file) {
            if (!file) return;
            const res = await fetch(file);
            const data = await res.json();
            activeTopic = data.question;
            document.getElementById('topic-text').innerText = activeTopic;
            document.getElementById('luna-bubble').innerText = `This is a real ${data.type} task. Good luck!`;
        }

        // AI 出题
        async function generateAITopic() {
            document.getElementById('topic-text').innerText = "Luna is thinking...";
            const res = await fetch('writing_proxy.php', { method: 'POST', body: JSON.stringify({ action: 'get_topic' }) });
            const data = await res.json();
            activeTopic = data.topic;
            document.getElementById('topic-text').innerText = activeTopic;
        }

        // 提交研判
        async function submitEvaluation() {
            const content = document.getElementById('essay-input').value;
            if (content.length < 50) return alert("Please write at least 50 words.");

            document.getElementById('luna-bubble').innerText = "Analyzing... I'm looking at your logic and vocabulary.";

            const res = await fetch('writing_proxy.php', {
                method: 'POST',
                body: JSON.stringify({ action: 'evaluate', topic: activeTopic, content: content })
            });
            const data = await res.json();

            document.getElementById('res-score').innerText = data.score;
            document.getElementById('res-feedback').innerHTML = `<p><b>Grammar:</b> ${data.grammar}</p><p><b>Logic:</b> ${data.logic}</p><hr><p><b>Luna's Polish:</b><br><i>${data.polished}</i></p>`;
            document.getElementById('result-overlay').style.display = 'flex';
        }

        // 词数统计
        document.getElementById('essay-input').addEventListener('input', function () {
            const words = this.value.trim().split(/\s+/).filter(x => x).length;
            document.getElementById('word-count').innerText = words;
        });
    </script>
    <script src="ai-agent.js?v=<?php echo time(); ?>"></script>
</body>

</html>
