<?php
session_start();

// 初始化游戏状态
if (!isset($_SESSION['stage_state'])) {
    $_SESSION['stage_state'] = [
        'current_question' => 0,
        'correct_count' => 0,
        'answers' => [],
        'game_active' => true,
        'game_finished' => false
    ];
}

// 英语情景剧题库（8道题）
$questions = [
    [
        'id' => 1,
        'question' => 'What does "break a leg" mean in theater?',
        'options' => ['A. 摔断腿', 'B. 祝你好运', 'C. 小心点', 'D. 快点结束'],
        'correct' => 1,
        'explanation' => '在戏剧界，"break a leg"是祝福演员演出成功的意思！'
    ],
    [
        'id' => 2,
        'question' => 'When an actor forgets their lines, they say:',
        'options' => ['A. I forgot', 'B. I went blank', 'C. I drew a blank', 'D. I lost it'],
        'correct' => 2,
        'explanation' => '"draw a blank" 意思是脑子一片空白，忘记台词了。'
    ],
    [
        'id' => 3,
        'question' => 'What does "curtain call" mean?',
        'options' => ['A. 电话铃响', 'B. 谢幕', 'C. 开场白', 'D. 幕布落下'],
        'correct' => 1,
        'explanation' => 'curtain call 是演出结束后演员返场谢幕。'
    ],
    [
        'id' => 4,
        'question' => 'The main character in a play is called the:',
        'options' => ['A. Protagonist', 'B. Antagonist', 'C. Sidekick', 'D. Director'],
        'correct' => 0,
        'explanation' => 'Protagonist 是主角，Antagonist 是反派。'
    ],
    [
        'id' => 5,
        'question' => 'What does "stage fright" mean?',
        'options' => ['A. 舞台恐怖片', 'B. 怯场', 'C. 舞台灯光', 'D. 舞台道具'],
        'correct' => 1,
        'explanation' => 'stage fright 是上台前的紧张害怕，也就是怯场。'
    ],
    [
        'id' => 6,
        'question' => 'What does "improv" stand for?',
        'options' => ['A. Improvement', 'B. Improvisation', 'C. Import', 'D. Important'],
        'correct' => 1,
        'explanation' => 'improv 是 improvisation 的缩写，意思是即兴表演。'
    ],
    [
        'id' => 7,
        'question' => 'What does "encore" mean?',
        'options' => ['A. 结束', 'B. 安可/返场', 'C. 鼓掌', 'D. 鞠躬'],
        'correct' => 1,
        'explanation' => 'encore 是观众要求演员返场再表演一个节目。'
    ],
    [
        'id' => 8,
        'question' => 'What does "the show must go on" mean?',
        'options' => ['A. 演出必须继续', 'B. 演出结束了', 'C. 演出很精彩', 'D. 演出取消了'],
        'correct' => 0,
        'explanation' => '这是戏剧界的经典格言：无论发生什么，演出都必须继续！'
    ]
];

// 结束语（根据答对数量不同）
$endingMessages = [
    0 => '😢 再接再厉！下次表演前多做准备，你一定可以的！',
    1 => '💪 还不错！继续加油，下次争取更好表现！',
    2 => '👍 有进步空间！多练习台词会更好！',
    3 => '🎭 挺好的！你的表演有潜力！',
    4 => '🌟 不错哦！观众们给了你掌声！',
    5 => '🎉 很棒！你的表演获得了大家的喜爱！',
    6 => '🏆 非常出色！评委们对你赞不绝口！',
    7 => '💫 太厉害了！全场为你喝彩！',
    8 => '🎭✨ 完美！你的表演获得了一致好评！你是今天的明星！'
];

// 处理答题请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'answer') {
        $question_id = intval($_POST['question_id']);
        $answer = intval($_POST['answer']);
        
        $current_question = null;
        foreach ($questions as $q) {
            if ($q['id'] == $question_id) {
                $current_question = $q;
                break;
            }
        }
        
        $is_correct = ($answer == $current_question['correct']);
        
        if ($is_correct) {
            $_SESSION['stage_state']['correct_count']++;
        }
        
        $_SESSION['stage_state']['answers'][$question_id] = $is_correct;
        $_SESSION['stage_state']['current_question']++;
        
        $all_done = ($_SESSION['stage_state']['current_question'] >= 8);
        
        echo json_encode([
            'success' => true,
            'is_correct' => $is_correct,
            'correct_count' => $_SESSION['stage_state']['correct_count'],
            'current_question' => $_SESSION['stage_state']['current_question'],
            'all_done' => $all_done,
            'explanation' => $current_question['explanation'],
            'correct_answer' => $current_question['options'][$current_question['correct']]
        ]);
        exit;
    }
    
    if ($_POST['action'] === 'reset') {
        $_SESSION['stage_state'] = [
            'current_question' => 0,
            'correct_count' => 0,
            'answers' => [],
            'game_active' => true,
            'game_finished' => false
        ];
        echo json_encode(['success' => true]);
        exit;
    }
    
    if ($_POST['action'] === 'get_question') {
        $index = intval($_POST['index']);
        $question = $questions[$index];
        echo json_encode([
            'id' => $question['id'],
            'question' => $question['question'],
            'options' => $question['options'],
            'current' => $index + 1,
            'total' => 8
        ]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>英语情景剧｜表演挑战赛</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', 'PingFang SC', sans-serif;
            background: linear-gradient(135deg, #8B4513 0%, #D2691E 50%, #CD853F 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .game-container {
            max-width: 1000px;
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

        .progress-bar {
            flex: 1;
            height: 10px;
            background: rgba(255,255,255,0.3);
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: #ff9f4b;
            width: 0%;
            transition: width 0.3s ease;
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

        .stage-container {
            background: #000;
            border-radius: 24px;
            overflow: hidden;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.4);
            aspect-ratio: 16 / 9;
            position: relative;
           
        }

        .stage-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* GIF 播放层 - 固定在stage图片正中间，无背景无光效 */
       /* GIF 播放层 - 固定在stage图片正中间 */
.gif-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: transparent;
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 100;
    visibility: hidden;
    pointer-events: none;
}

.gif-overlay.active {
    visibility: visible;
}

.gif-content {
    text-align: center;
}

.gif-content img {
    max-width: 80%;
    max-height: 80%;
    width: auto;
    height: auto;
    border-radius: 0;
    box-shadow: none;
    background: transparent;
    object-fit: contain;
}

.gif-close-btn {
    margin-top: 20px;
    background: #ff9f4b;
    border: none;
    padding: 12px 30px;
    border-radius: 50px;
    font-size: 1.1rem;
    font-weight: bold;
    color: white;
    cursor: pointer;
    pointer-events: auto;
    display: block;
    margin-left: auto;
    margin-right: auto;
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

        .judge-dialogue {
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

        .question-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            color: #b45f1b;
            font-weight: bold;
        }

        .question-text {
            font-size: 1.3rem;
            font-weight: bold;
            color: #2c5a2e;
            text-align: center;
            margin-bottom: 25px;
            padding: 20px;
            background: #fff5e6;
            border-radius: 20px;
            line-height: 1.4;
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

        .result-area {
            display: none;
            text-align: center;
        }

        .result-area.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }

        .score-display {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
            margin: 30px 0;
        }

        .score-number {
            width: auto;
            height: auto;
            background: transparent;
            border-radius: 0;
            font-size: 3rem;
            font-weight: bold;
            color: #ff3333;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
            animation: bounce 0.5s ease;
            display: inline-block;
            padding: 0 5px;
        }

        .ending-message {
            font-size: 1.2rem;
            color: #2c5a2e;
            margin: 20px 0;
            padding: 20px;
            background: #fff5e6;
            border-radius: 20px;
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

        @keyframes bounce {
            0% {
                transform: scale(0);
                opacity: 0;
            }
            60% {
                transform: scale(1.2);
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        @media (max-width: 768px) {
            .game-container {
                padding: 15px;
            }
            .options {
                grid-template-columns: 1fr;
            }
            .question-text {
                font-size: 1rem;
            }
            .score-number {
                font-size: 2rem;
            }
            .gif-content img {
                max-width: 280px;
                max-height: 200px;
            }
        }
    </style>
</head>
<body>

<div class="game-container">
    <div class="info-bar">
        <div class="info-card">🎭 答题进度: <span id="progressText">0/8</span></div>
        <div class="progress-bar">
            <div class="progress-fill" id="progressFill"></div>
        </div>
        <div class="info-card">⭐ 得分: <span id="scoreCount">0</span></div>
        <button class="reset-btn" onclick="resetGame()">🔄 重新开始</button>
    </div>

    <div class="stage-container">
        <img id="stageImage" class="stage-image" src="stage.jpg" alt="舞台背景">
    </div>

    <div class="interaction-area" id="interactionArea">
        <div id="dialogueArea" class="dialogue-box">
            <div class="dialogue-message user-dialogue">
                <div class="dialogue-name">🎭 我</div>
                <div class="dialogue-text">今天有我们学院的英语情景剧表演诶！我也要参加！</div>
            </div>
            <div class="dialogue-message judge-dialogue">
                <div class="dialogue-name">👨‍⚖️ 评委</div>
                <div class="dialogue-text">请开始你的表演。</div>
            </div>
            <button class="start-btn" id="startQuizBtn" onclick="startQuiz()">🎬 开始表演 🎭</button>
        </div>

        <div id="quizArea" class="quiz-box">
            <div class="question-header">
                <span>📖 情景剧知识问答</span>
                <span id="questionCounter">第1/8题</span>
            </div>
            <div class="question-text" id="questionText">加载题目中...</div>
            <div class="options" id="optionsContainer"></div>
            <div class="feedback-area" id="feedbackMsg"></div>
        </div>

        <div id="resultArea" class="result-area">
            <h2>🎭 表演结束 🎭</h2>
            <div class="score-display" id="scoreDisplay"></div>
            <div class="ending-message" id="endingMessage"></div>
            <button class="start-btn" onclick="resetGame()">🔄 再来一次</button>
        </div>
    </div>
</div>

<!-- GIF 播放浮层 -->
<div id="gifOverlay" class="gif-overlay">
    <div class="gif-content">
        <img id="gifImage" src="" alt="表演结果">
        <button class="gif-close-btn" onclick="closeGifAndShowResult()">继续 🎭</button>
    </div>
</div>

<script>
    let currentQuestionIndex = 0;
    let currentQuestion = null;
    let gameActive = true;
    let quizStarted = false;
    let isWaitingForGif = false;
    let correctCount = 0;
    let gifPlayCount = 0;
    let gifInterval = null;

    const questions = <?php echo json_encode($questions); ?>;
    const endingMessages = <?php echo json_encode($endingMessages); ?>;

    window.onload = () => {
        document.getElementById('stageImage').src = 'stage.jpg';
    };

    function startQuiz() {
        quizStarted = true;
        currentQuestionIndex = 0;
        correctCount = 0;
        updateScoreDisplay();
        
        document.getElementById('dialogueArea').style.display = 'none';
        document.getElementById('quizArea').classList.add('active');
        document.getElementById('resultArea').classList.remove('active');
        
        loadQuestion();
    }

    async function loadQuestion() {
        if (!gameActive) return;
        if (currentQuestionIndex >= 8) {
            finishGame();
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append('action', 'get_question');
            formData.append('index', currentQuestionIndex);
            
            const response = await fetch(window.location.pathname, {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            currentQuestion = data;
            
            document.getElementById('questionText').innerHTML = `📖 ${data.question}`;
            document.getElementById('questionCounter').innerHTML = `第${data.current}/${data.total}题`;
            
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
            document.querySelectorAll('.option-btn').forEach(btn => {
                btn.disabled = false;
            });
            
        } catch (error) {
            console.error('加载题目失败', error);
        }
    }

    async function submitAnswer(answerIndex) {
        if (!gameActive || isWaitingForGif) return;
        
        document.querySelectorAll('.option-btn').forEach(btn => {
            btn.disabled = true;
        });
        
        const formData = new FormData();
        formData.append('action', 'answer');
        formData.append('question_id', currentQuestion.id);
        formData.append('answer', answerIndex);
        
        try {
            const response = await fetch(window.location.pathname, {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            if (data.is_correct) {
                correctCount = data.correct_count;
                updateScoreDisplay();
                document.getElementById('feedbackMsg').innerHTML = `✅ 回答正确！<br>📖 ${data.explanation}`;
            } else {
                document.getElementById('feedbackMsg').innerHTML = `❌ 回答错误！正确答案是：${data.correct_answer}<br>📖 ${data.explanation}`;
            }
            
            currentQuestionIndex++;
            
            if (data.all_done) {
                finishGame();
            } else {
                setTimeout(() => {
                    loadQuestion();
                }, 1500);
            }
            
        } catch (error) {
            console.error('提交答案失败', error);
            document.querySelectorAll('.option-btn').forEach(btn => {
                btn.disabled = false;
            });
        }
    }

    function updateScoreDisplay() {
        document.getElementById('scoreCount').innerText = correctCount;
        const progress = (currentQuestionIndex / 8) * 100;
        document.getElementById('progressFill').style.width = `${progress}%`;
        document.getElementById('progressText').innerText = `${currentQuestionIndex}/8`;
    }

    function finishGame() {
        gameActive = false;
        quizStarted = false;
        playGifByScore(correctCount);
    }

    function playGifByScore(score) {
        const gifNumber = score;
        const gifPath = `GIF/GIF${gifNumber}.gif`;
        
        isWaitingForGif = true;
        gifPlayCount = 0;
        
        const gifOverlay = document.getElementById('gifOverlay');
        const gifImage = document.getElementById('gifImage');
        
        gifImage.src = gifPath;
        gifOverlay.classList.add('active');
        
        gifInterval = setInterval(() => {
            gifPlayCount++;
            if (gifPlayCount >= 3) {
                clearInterval(gifInterval);
            } else {
                gifImage.src = gifPath;
            }
        }, 2000);
    }

    function closeGifAndShowResult() {
        clearInterval(gifInterval);
        document.getElementById('gifOverlay').classList.remove('active');
        showResult();
    }

    function showResult() {
        document.getElementById('quizArea').classList.remove('active');
        document.getElementById('resultArea').classList.add('active');
        
        const scoreDisplay = document.getElementById('scoreDisplay');
        scoreDisplay.innerHTML = '';
        for (let i = 0; i < 7; i++) {
            const numDiv = document.createElement('div');
            numDiv.className = 'score-number';
            numDiv.innerText = correctCount;
            scoreDisplay.appendChild(numDiv);
        }
        
        const message = endingMessages[correctCount];
        document.getElementById('endingMessage').innerHTML = message;
        
        gameActive = false;
    }

    async function resetGame() {
        const formData = new FormData();
        formData.append('action', 'reset');
        
        try {
            await fetch(window.location.pathname, {
                method: 'POST',
                body: formData
            });
            
            gameActive = true;
            quizStarted = false;
            currentQuestionIndex = 0;
            correctCount = 0;
            isWaitingForGif = false;
            
            document.getElementById('scoreCount').innerText = '0';
            document.getElementById('progressFill').style.width = '0%';
            document.getElementById('progressText').innerText = '0/8';
            
            document.getElementById('dialogueArea').style.display = 'block';
            document.getElementById('quizArea').classList.remove('active');
            document.getElementById('resultArea').classList.remove('active');
            document.getElementById('feedbackMsg').innerHTML = '';
            
            if (gifInterval) clearInterval(gifInterval);
            document.getElementById('gifOverlay').classList.remove('active');
            
        } catch (error) {
            console.error('重置失败', error);
            location.reload();
        }
    }
</script>

</body>
</html>