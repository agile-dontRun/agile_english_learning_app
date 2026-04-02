<?php
session_start();
require_once 'functions.php';
require_once 'questions.php';

if (!isset($_SESSION['game_state'])) {
    $_SESSION['game_state'] = initGameState();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>English Proverb Canteen | Hidden Menu Challenge</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', 'PingFang SC', sans-serif;
            background: linear-gradient(135deg, #1a472a 0%, #2d5a3b 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .game-container {
            max-width: 900px;
            width: 100%;
            background: #fef0cf;
            border-radius: 40px;
            padding: 25px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }

        .info-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            background: rgba(44, 26, 16, 0.9);
            padding: 12px 20px;
            border-radius: 60px;
        }

        .info-card {
            color: #ffd966;
            font-weight: bold;
            font-size: 1rem;
        }

        .info-card span {
            font-size: 1.3rem;
            color: #ffaa44;
            font-weight: bold;
            margin-left: 8px;
        }

        .reset-btn {
            background: #6c757d;
            border: none;
            padding: 8px 20px;
            border-radius: 40px;
            font-size: 0.9rem;
            font-weight: bold;
            color: white;
            cursor: pointer;
            transition: 0.2s;
        }

        .reset-btn:hover {
            background: #5a6268;
        }

        .video-container {
            background: #000;
            border-radius: 24px;
            overflow: hidden;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.4);
            aspect-ratio: 16 / 9;
            position: relative;
        }

        .game-video, .cover-image {
            width: 100%;
            height: 100%;
            object-fit: contain;
            background: #0a0a1a;
        }

        .interaction-area {
            background: rgba(255, 245, 235, 0.95);
            border-radius: 30px;
            padding: 25px;
            min-height: 320px;
        }

        .dialogue-box {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .dialogue-message {
            border-radius: 20px;
            padding: 15px 20px;
            animation: fadeIn 0.3s ease;
        }

        .user-dialogue {
            background: #ffe6d5;
            border-left: 5px solid #ff9f4b;
            align-self: flex-end;
            max-width: 85%;
            margin-left: auto;
        }

        .auntie-dialogue {
            background: #fff0e0;
            border-left: 5px solid #b45f1b;
            align-self: flex-start;
            max-width: 85%;
        }

        .dialogue-name {
            font-weight: bold;
            color: #b45f1b;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }

        .dialogue-text {
            font-size: 1rem;
            color: #4a3a1f;
            line-height: 1.5;
        }

        .start-btn {
            background: #ff9f4b;
            border: none;
            padding: 14px;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: bold;
            color: white;
            cursor: pointer;
            margin-top: 15px;
            transition: 0.2s;
        }

        .start-btn:hover {
            background: #ffb46e;
            transform: translateY(-2px);
        }

        .quiz-box {
            display: none;
        }

        .quiz-box.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        .proverb-text {
            font-size: 1.5rem;
            font-weight: bold;
            color: #2c5a2e;
            text-align: center;
            margin-bottom: 25px;
            padding: 20px;
            background: #fff5e6;
            border-radius: 20px;
            font-family: 'Courier New', monospace;
            word-break: break-word;
        }

        .options {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }

        .option-btn {
            background: #f5e7c8;
            border: 2px solid #cbae76;
            padding: 14px;
            border-radius: 50px;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 500;
        }

        .option-btn:hover:not(:disabled) {
            background: #ffe1a0;
            transform: scale(1.02);
        }

        .option-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .feedback-area {
            background: #e9e3cf;
            border-radius: 20px;
            padding: 15px;
            margin-top: 15px;
            border-left: 5px solid #ff9f4b;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .game-container {
                padding: 15px;
            }
            .options {
                grid-template-columns: 1fr;
            }
            .proverb-text {
                font-size: 1.2rem;
            }
            .user-dialogue, .auntie-dialogue {
                max-width: 95%;
            }
        }
    </style>
</head>
<body>

<div class="game-container">
    <div class="info-bar">
        <div class="info-card">🎯 Chances Left: <span id="chancesLeft">5</span></div>
        <div class="info-card">🔥 Combo: <span id="comboCount">0</span></div>
        <div class="info-card">✅ Correct: <span id="correctCount">0</span></div>
        <button class="reset-btn" onclick="resetGame()">🔄 Restart</button>
        <!-- ====== 🌟 新增：返回 Galgame 主程序的按钮 ====== -->
        <button class="reset-btn" style="background: #dc3545;" onclick="window.location.href='../frontend/index.html'">🔙 离开食堂</button>
    </div>

    <div class="video-container">
        <img id="coverImage" class="cover-image" src="cover.png" style="display: block;">
        <video id="gameVideo" class="game-video" muted style="display: none;"></video>
    </div>

    <div class="interaction-area" id="interactionArea">
        <div id="dialogueArea" class="dialogue-box">
            <div class="dialogue-message user-dialogue">
                <div class="dialogue-name">👩‍🎓 Me</div>
                <div class="dialogue-text">Auntie, what's today's English proverb hidden menu?</div>
            </div>
            <div class="dialogue-message auntie-dialogue" id="auntieFirstMsg">
                <div class="dialogue-name">🧑‍🍳 Auntie</div>
                <div class="dialogue-text">Today's hidden menu is fried chicken and fries! You need to answer questions correctly to get it. Answer 3 in a row to trigger the SUPER SCOOP!!!</div>
            </div>
            <button class="start-btn" id="startQuizBtn" onclick="startQuiz()">🍗 Start Quiz 🍟</button>
        </div>

        <div id="quizArea" class="quiz-box">
            <div class="proverb-text" id="proverbText">Loading question...</div>
            <div class="options" id="optionsContainer"></div>
            <div class="feedback-area" id="feedbackMsg"></div>
        </div>
    </div>
</div>

<script>
    let currentQuestion = null;
    let gameActive = true;
    let videoElement = null;
    let coverImage = null;
    let isWaitingForVideo = false;
    let pendingAnswerData = null;
    let quizStarted = false;

    const videoPaths = {
        video1: 'videos/video1.mp4',
        video2: 'videos/video2.mp4',
        video3: 'videos/video3.mp4'
    };

    window.onload = () => {
        videoElement = document.getElementById('gameVideo');
        coverImage = document.getElementById('coverImage');
        
        coverImage.style.display = 'block';
        videoElement.style.display = 'none';
        
        videoElement.addEventListener('ended', onVideoEnded);
        videoElement.addEventListener('error', () => {
            if (isWaitingForVideo) {
                onVideoEnded();
            }
        });
    };

    function startQuiz() {
        quizStarted = true;
        document.getElementById('dialogueArea').style.display = 'none';
        document.getElementById('quizArea').classList.add('active');
        loadQuestion();
    }

    async function loadQuestion() {
        if (!gameActive) return;
        
        try {
            const formData = new FormData();
            formData.append('action', 'get_question');
            
            const response = await fetch('api.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            currentQuestion = data;
            
            document.getElementById('proverbText').innerHTML = `📖 "${data.proverb}"`;
            
            const optionsDiv = document.getElementById('optionsContainer');
            optionsDiv.innerHTML = '';
            data.options.forEach((opt, idx) => {
                const btn = document.createElement('button');
                btn.className = 'option-btn';
                btn.textContent = opt;
                btn.onclick = () => submitAnswer(idx);
                optionsDiv.appendChild(btn);
            });
            
            document.getElementById('feedbackMsg').innerHTML = '';
        } catch (error) {
            console.error('Failed to load question', error);
            document.getElementById('proverbText').innerHTML = 'Load failed, please refresh';
        }
    }

    async function submitAnswer(answerIndex) {
        if (!gameActive || isWaitingForVideo) return;
        
        document.querySelectorAll('.option-btn').forEach(btn => {
            btn.disabled = true;
        });
        
        const formData = new FormData();
        formData.append('action', 'answer');
        formData.append('question_id', currentQuestion.id);
        formData.append('answer', answerIndex);
        
        try {
            const response = await fetch('api.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            pendingAnswerData = data;
            playVideo(data.video);
            
        } catch (error) {
            console.error('Failed to submit answer', error);
            document.querySelectorAll('.option-btn').forEach(btn => {
                btn.disabled = false;
            });
        }
    }

    function playVideo(videoType) {
        const videoPath = videoPaths[videoType];
        if (!videoPath) return;
        
        isWaitingForVideo = true;
        
        coverImage.style.display = 'none';
        videoElement.style.display = 'block';
        
        videoElement.src = videoPath;
        videoElement.load();
        videoElement.play().catch(e => {
            console.log('Video playback failed', e);
            onVideoEnded();
        });
    }

    function onVideoEnded() {
        if (!isWaitingForVideo) return;
        isWaitingForVideo = false;
        
        videoElement.style.display = 'none';
        coverImage.style.display = 'block';
        videoElement.src = '';
        
        if (!pendingAnswerData) return;
        
        const data = pendingAnswerData;
        
        document.getElementById('chancesLeft').innerText = data.chances_left;
        document.getElementById('comboCount').innerText = data.combo;
        document.getElementById('correctCount').innerText = data.correct_count;
        
        let feedbackHtml = '';
        if (data.is_correct) {
            feedbackHtml = `✅ Correct!<br>${data.message}`;
        } else {
            feedbackHtml = `❌ Wrong! Correct answer: ${data.correct_answer}<br>📖 ${data.explanation}<br>${data.message}`;
        }
        document.getElementById('feedbackMsg').innerHTML = feedbackHtml;
        
        if (data.game_over) {
            gameActive = false;
            document.getElementById('feedbackMsg').innerHTML += `<div style="margin-top:15px; padding:15px; background:#2c1a10; color:#ffd966; border-radius:15px; text-align:center;">${data.final_message}</div>`;
            document.querySelectorAll('.option-btn').forEach(btn => {
                btn.disabled = true;
            });
            const restartBtn = document.createElement('button');
            restartBtn.className = 'start-btn';
            restartBtn.textContent = 'Restart 🔄';
            restartBtn.style.marginTop = '15px';
            restartBtn.onclick = () => resetGame();
            document.getElementById('feedbackMsg').appendChild(restartBtn);
        } else {
            setTimeout(() => {
                if (gameActive) {
                    loadQuestion();
                    document.querySelectorAll('.option-btn').forEach(btn => {
                        btn.disabled = false;
                    });
                }
            }, 1000);
        }
        
        pendingAnswerData = null;
    }

    async function resetGame() {
        const formData = new FormData();
        formData.append('action', 'reset');
        
        try {
            await fetch('api.php', {
                method: 'POST',
                body: formData
            });
            
            gameActive = true;
            quizStarted = false;
            isWaitingForVideo = false;
            pendingAnswerData = null;
            
            document.getElementById('chancesLeft').innerText = '5';
            document.getElementById('comboCount').innerText = '0';
            document.getElementById('correctCount').innerText = '0';
            
            document.getElementById('dialogueArea').style.display = 'block';
            document.getElementById('quizArea').classList.remove('active');
            document.getElementById('feedbackMsg').innerHTML = '';
            
            coverImage.style.display = 'block';
            videoElement.style.display = 'none';
            videoElement.src = '';
            
        } catch (error) {
            console.error('Reset failed', error);
            location.reload();
        }
    }
</script>

</body>
</html>