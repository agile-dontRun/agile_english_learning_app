<?php
// Start the session so game progress can be stored between requests
session_start();

// Load shared game functions, question data, and coin reward helpers
require_once 'functions.php';
require_once 'questions.php';
require_once dirname(__DIR__, 2) . '/coin_common.php';

// Require the user to be logged in
coin_require_login();

// Get the current logged-in user ID and coin information
$userId = coin_current_user_id();
$coinBalance = coin_get_balance($conn, $userId);
$canteenRewardToday = coin_get_daily_reward_record($conn, $userId, 'canteen', coin_today_date());

// Initialize the game state if this is the first visit
if (!isset($_SESSION['game_state'])) {
    $_SESSION['game_state'] = initGameState();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <!-- Mobile-friendly viewport settings -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">

    <!-- Page title shown in the browser tab -->
    <title>English Proverb Canteen | Hidden Menu Challenge</title>

    <style>
        /* Reset default spacing and use border-box layout */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Main page background and center alignment */
        body {
            font-family: 'Segoe UI', 'PingFang SC', sans-serif;
            background: linear-gradient(135deg, #1a472a 0%, #2d5a3b 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        /* Main game card */
        .game-container {
            max-width: 900px;
            width: 100%;
            background: #fef0cf;
            border-radius: 40px;
            padding: 25px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }

        /* Top status bar showing coins, reward status, chances, combo, etc. */
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

        /* Small info block inside the top bar */
        .info-card {
            color: #ffd966;
            font-weight: bold;
            font-size: 1rem;
        }

        /* Highlighted value inside each info block */
        .info-card span {
            font-size: 1.3rem;
            color: #ffaa44;
            font-weight: bold;
            margin-left: 8px;
        }

        /* Generic reset button style */
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

        /* Video / cover display area */
        .video-container {
            background: #000;
            border-radius: 24px;
            overflow: hidden;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.4);
            aspect-ratio: 16 / 9;
            position: relative;
        }

        /* Both video and cover image fill the display area */
        .game-video, .cover-image {
            width: 100%;
            height: 100%;
            object-fit: contain;
            background: #0a0a1a;
        }

        /* Main interactive content area below the video */
        .interaction-area {
            background: rgba(255, 245, 235, 0.95);
            border-radius: 30px;
            padding: 25px;
            min-height: 320px;
        }

        /* Dialogue section layout */
        .dialogue-box {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        /* Shared message bubble style */
        .dialogue-message {
            border-radius: 20px;
            padding: 15px 20px;
            animation: fadeIn 0.3s ease;
        }

        /* Player dialogue bubble */
        .user-dialogue {
            background: #ffe6d5;
            border-left: 5px solid #ff9f4b;
            align-self: flex-end;
            max-width: 85%;
            margin-left: auto;
        }

        /* Auntie dialogue bubble */
        .auntie-dialogue {
            background: #fff0e0;
            border-left: 5px solid #b45f1b;
            align-self: flex-start;
            max-width: 85%;
        }

        /* Speaker name style */
        .dialogue-name {
            font-weight: bold;
            color: #b45f1b;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }

        /* Dialogue text style */
        .dialogue-text {
            font-size: 1rem;
            color: #4a3a1f;
            line-height: 1.5;
        }

        /* Main start button */
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

        /* Quiz area is hidden by default */
        .quiz-box {
            display: none;
        }

        /* Show quiz area when active */
        .quiz-box.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        /* Main proverb/question display */
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

        /* Answer options grid */
        .options {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }

        /* Individual answer button */
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

        /* Disabled option state after answering */
        .option-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Feedback message area */
        .feedback-area {
            background: #e9e3cf;
            border-radius: 20px;
            padding: 15px;
            margin-top: 15px;
            border-left: 5px solid #ff9f4b;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        /* Simple fade-in animation for new content */
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

        /* Responsive layout for smaller screens */
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
    <!-- Top status bar -->
    <div class="info-bar">
        <div class="info-card">Coins: <span id="coinCount"><?= (int)$coinBalance ?></span></div>
        <div class="info-card">Daily Reward: <span id="dailyRewardStatus"><?= $canteenRewardToday ? 'Claimed' : 'Available' ?></span></div>
        <div class="info-card">🎯 Chances Left: <span id="chancesLeft">5</span></div>
        <div class="info-card">🔥 Combo: <span id="comboCount">0</span></div>
        <div class="info-card">✅ Correct: <span id="correctCount">0</span></div>

        <!-- Restart the current game -->
        <button class="reset-btn" onclick="resetGame()">🔄 Restart</button>

        <!-- Leave the canteen game and go back to the main galgame page -->
        <button class="reset-btn" style="background: #dc3545;" onclick="window.location.href='../galgame/index.html'">🔙 离开食堂</button>
    </div>

    <!-- Video / animation display area -->
    <div class="video-container">
        <img id="coverImage" class="cover-image" src="cover.png" style="display: block;">
        <video id="gameVideo" class="game-video" muted style="display: none;"></video>
    </div>

    <!-- Main interaction area -->
    <div class="interaction-area" id="interactionArea">
        <!-- Opening dialogue before quiz starts -->
        <div id="dialogueArea" class="dialogue-box">
            <div class="dialogue-message user-dialogue">
                <div class="dialogue-name">👩‍🎓 Me</div>
                <div class="dialogue-text">Auntie, what's today's English proverb hidden menu?</div>
            </div>
            <div class="dialogue-message auntie-dialogue" id="auntieFirstMsg">
                <div class="dialogue-name">🧑‍🍳 Auntie</div>
                <div class="dialogue-text">Today's hidden menu is fried chicken and fries! You need to answer questions correctly to get it. Answer 3 in a row to trigger the SUPER SCOOP!!!</div>
            </div>

            <!-- Button to begin the quiz -->
            <button class="start-btn" id="startQuizBtn" onclick="startQuiz()">🍗 Start Quiz 🍟</button>
        </div>

        <!-- Quiz area shown after the player starts -->
        <div id="quizArea" class="quiz-box">
            <div class="proverb-text" id="proverbText">Loading question...</div>
            <div class="options" id="optionsContainer"></div>
            <div class="feedback-area" id="feedbackMsg"></div>
        </div>
    </div>
</div>

<script>
    // Initial values injected from PHP
    const initialCanteenCoinBalance = <?= (int)$coinBalance ?>;
    const initialCanteenRewardClaimed = <?= $canteenRewardToday ? 'true' : 'false' ?>;

    // Current game state variables on the frontend
    let currentQuestion = null;
    let gameActive = true;
    let videoElement = null;
    let coverImage = null;
    let isWaitingForVideo = false;
    let pendingAnswerData = null;
    let quizStarted = false;

    // Update the coin count shown in the UI
    function updateCoinUI(balance) {
        if (typeof balance !== 'number' || Number.isNaN(balance)) {
            return;
        }
        const coinEl = document.getElementById('coinCount');
        if (coinEl) {
            coinEl.innerText = String(balance);
        }
    }

    // Update whether today's reward is claimed or still available
    function updateDailyRewardStatus(claimed) {
        const statusEl = document.getElementById('dailyRewardStatus');
        if (statusEl) {
            statusEl.innerText = claimed ? 'Claimed' : 'Available';
        }
    }

    // Video files used for wrong / correct / combo feedback
    const videoPaths = {
        video1: 'videos/video1.mp4',
        video2: 'videos/video2.mp4',
        video3: 'videos/video3.mp4'
    };

    // Initialize DOM references and event listeners after page load
    window.onload = () => {
        videoElement = document.getElementById('gameVideo');
        coverImage = document.getElementById('coverImage');
        updateCoinUI(initialCanteenCoinBalance);
        updateDailyRewardStatus(initialCanteenRewardClaimed);
        
        coverImage.style.display = 'block';
        videoElement.style.display = 'none';
        
        videoElement.addEventListener('ended', onVideoEnded);
        videoElement.addEventListener('error', () => {
            if (isWaitingForVideo) {
                onVideoEnded();
            }
        });
    };

    // Switch from the intro dialogue to the quiz interface
    function startQuiz() {
        quizStarted = true;
        document.getElementById('dialogueArea').style.display = 'none';
        document.getElementById('quizArea').classList.add('active');
        loadQuestion();
    }

    // Load one random question from the backend
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

            // Render answer buttons for the current question
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

    // Submit the selected answer to the backend
    async function submitAnswer(answerIndex) {
        if (!gameActive || isWaitingForVideo) return;
        
        // Disable all option buttons while waiting for the response
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

    // Play the feedback video after answering
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

    // Handle the end of a feedback video
    function onVideoEnded() {
        if (!isWaitingForVideo) return;
        isWaitingForVideo = false;
        
        videoElement.style.display = 'none';
        coverImage.style.display = 'block';
        videoElement.src = '';
        
        if (!pendingAnswerData) return;
        
        const data = pendingAnswerData;
        
        // Update on-screen counters
        document.getElementById('chancesLeft').innerText = data.chances_left;
        document.getElementById('comboCount').innerText = data.combo;
        document.getElementById('correctCount').innerText = data.correct_count;
        updateCoinUI(data.coin_balance ?? initialCanteenCoinBalance);
        updateDailyRewardStatus(Boolean(data.daily_reward_claimed));
        
        // Show answer result and explanation
        let feedbackHtml = '';
        if (data.is_correct) {
            feedbackHtml = `✅ Correct!<br>${data.message}`;
        } else {
            feedbackHtml = `❌ Wrong! Correct answer: ${data.correct_answer}<br>📖 ${data.explanation}<br>${data.message}`;
        }
        document.getElementById('feedbackMsg').innerHTML = feedbackHtml;
        
        // Handle end-of-game state
        if (data.game_over) {
            gameActive = false;
            document.getElementById('feedbackMsg').innerHTML += `<div style="margin-top:15px; padding:15px; background:#2c1a10; color:#ffd966; border-radius:15px; text-align:center;">${data.final_message}</div>`;

            // Show reward result
            if (data.reward_granted) {
                document.getElementById('feedbackMsg').innerHTML += `<div style="margin-top:12px; padding:12px; background:#1f5f35; color:#fff; border-radius:15px; text-align:center;">Daily reward: +${data.reward_amount} coins</div>`;
            } else if (data.daily_reward_claimed) {
                document.getElementById('feedbackMsg').innerHTML += `<div style="margin-top:12px; padding:12px; background:#6c757d; color:#fff; border-radius:15px; text-align:center;">Today's coin reward has already been claimed.</div>`;
            }

            // Disable all option buttons after the game ends
            document.querySelectorAll('.option-btn').forEach(btn => {
                btn.disabled = true;
            });

            // Add a restart button
            const restartBtn = document.createElement('button');
            restartBtn.className = 'start-btn';
            restartBtn.textContent = 'Restart 🔄';
            restartBtn.style.marginTop = '15px';
            restartBtn.onclick = () => resetGame();
            document.getElementById('feedbackMsg').appendChild(restartBtn);
        } else {
            // Load the next question automatically after a short delay
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

    // Reset the whole game back to the initial state
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