<?php
session_start();

// 初始化游戏状态
if (!isset($_SESSION['game_state'])) {
    $_SESSION['game_state'] = [
        'chances_left' => 5,
        'combo' => 0,
        'correct_count' => 0,
        'game_active' => true,
        'game_result' => null,
        'waiting_for_video' => false,
        'pending_answer' => null
    ];
}

// 英语谚语题库
$questions = [
    [
        'id' => 1,
        'proverb' => 'Break a leg!',
        'options' => ['A. 摔断腿', 'B. 祝你好运', 'C. 小心点', 'D. 快点走'],
        'correct' => 1,
        'meaning' => '祝你好运（常用于演出前祝福）'
    ],
    [
        'id' => 2,
        'proverb' => 'It\'s raining cats and dogs.',
        'options' => ['A. 下猫下狗', 'B. 倾盆大雨', 'C. 宠物市场', 'D. 奇怪的现象'],
        'correct' => 1,
        'meaning' => '倾盆大雨'
    ],
    [
        'id' => 3,
        'proverb' => 'Once in a blue moon.',
        'options' => ['A. 在蓝月亮上', 'B. 千载难逢', 'C. 一个月一次', 'D. 天文奇观'],
        'correct' => 1,
        'meaning' => '千载难逢，极少发生'
    ],
    [
        'id' => 4,
        'proverb' => 'Bite the bullet.',
        'options' => ['A. 咬子弹', 'B. 勇敢面对', 'C. 临阵脱逃', 'D. 快速决定'],
        'correct' => 1,
        'meaning' => '勇敢面对困难'
    ],
    [
        'id' => 5,
        'proverb' => 'Spill the beans.',
        'options' => ['A. 撒豆子', 'B. 泄露秘密', 'C. 打扫卫生', 'D. 做饭'],
        'correct' => 1,
        'meaning' => '泄露秘密'
    ],
    [
        'id' => 6,
        'proverb' => 'Hit the sack.',
        'options' => ['A. 打麻袋', 'B. 去睡觉', 'C. 去购物', 'D. 去工作'],
        'correct' => 1,
        'meaning' => '去睡觉'
    ],
    [
        'id' => 7,
        'proverb' => 'When pigs fly.',
        'options' => ['A. 猪会飞', 'B. 绝不可能', 'C. 奇迹发生', 'D. 梦想成真'],
        'correct' => 1,
        'meaning' => '绝不可能'
    ],
    [
        'id' => 8,
        'proverb' => 'Cost an arm and a leg.',
        'options' => ['A. 断手断脚', 'B. 价格昂贵', 'C. 物美价廉', 'D. 免费赠送'],
        'correct' => 1,
        'meaning' => '价格昂贵'
    ]
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
        $video_to_play = '';
        $message = '';
        
        if ($is_correct) {
            $_SESSION['game_state']['combo']++;
            $_SESSION['game_state']['correct_count']++;
            
            if ($_SESSION['game_state']['combo'] >= 3) {
                $video_to_play = 'video3';
                $message = '🎉 连续答对3题！触发超级大勺！！！ 🥄✨';
            } else {
                $video_to_play = 'video2';
                $message = '✅ 正确！阿姨给你一大勺炸鸡！';
            }
        } else {
            $_SESSION['game_state']['combo'] = 0;
            $video_to_play = 'video1';
            $message = '❌ 错误！正确答案是：' . $current_question['options'][$current_question['correct']];
        }
        
        $_SESSION['game_state']['chances_left']--;
        
        $game_over = false;
        $final_message = '';
        
        if ($_SESSION['game_state']['chances_left'] <= 0) {
            $game_over = true;
            if ($_SESSION['game_state']['correct_count'] == 0) {
                $final_message = '😢 再接再厉，明天一定可以享用隐藏菜单！';
            } elseif ($_SESSION['game_state']['correct_count'] == 5) {
                $final_message = '🎉🎊 恭喜你！全部的菜品都被你承包了！！！ 🍗🍟🎊🎉';
            } else {
                $final_message = '👍 真棒！欢迎明天再来！！！';
            }
            $_SESSION['game_state']['game_active'] = false;
            $_SESSION['game_state']['game_result'] = $final_message;
        }
        
        echo json_encode([
            'success' => true,
            'is_correct' => $is_correct,
            'video' => $video_to_play,
            'message' => $message,
            'chances_left' => $_SESSION['game_state']['chances_left'],
            'combo' => $_SESSION['game_state']['combo'],
            'correct_count' => $_SESSION['game_state']['correct_count'],
            'game_over' => $game_over,
            'final_message' => $final_message,
            'correct_answer' => $current_question['options'][$current_question['correct']],
            'explanation' => $current_question['meaning']
        ]);
        exit;
    }
    
    if ($_POST['action'] === 'reset') {
        $_SESSION['game_state'] = [
            'chances_left' => 5,
            'combo' => 0,
            'correct_count' => 0,
            'game_active' => true,
            'game_result' => null,
            'waiting_for_video' => false,
            'pending_answer' => null
        ];
        echo json_encode(['success' => true]);
        exit;
    }
    
    if ($_POST['action'] === 'get_question') {
        $random_index = array_rand($questions);
        $question = $questions[$random_index];
        echo json_encode([
            'id' => $question['id'],
            'proverb' => $question['proverb'],
            'options' => $question['options']
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
    <title>英语谚语食堂｜隐藏菜单挑战</title>
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
        <div class="info-card">🎯 剩余机会: <span id="chancesLeft">5</span></div>
        <div class="info-card">🔥 连击: <span id="comboCount">0</span></div>
        <div class="info-card">✅ 答对: <span id="correctCount">0</span></div>
        <button class="reset-btn" onclick="resetGame()">🔄 重新开始</button>
    </div>

    <div class="video-container">
        <img id="coverImage" class="cover-image" src="cover.png" style="display: block;">
        <video id="gameVideo" class="game-video" muted style="display: none;"></video>
    </div>

    <div class="interaction-area" id="interactionArea">
        <div id="dialogueArea" class="dialogue-box">
            <div class="dialogue-message user-dialogue">
                <div class="dialogue-name">👩‍🎓 我</div>
                <div class="dialogue-text">阿姨，今天的英语谚语隐藏菜单是什么？</div>
            </div>
            <div class="dialogue-message auntie-dialogue" id="auntieFirstMsg">
                <div class="dialogue-name">🧑‍🍳 阿姨</div>
                <div class="dialogue-text">今天的隐藏菜单是炸鸡薯条，必须要答题才可以吃哟，答对连续三道可以触发超级大勺！！！</div>
            </div>
            <button class="start-btn" id="startQuizBtn" onclick="startQuiz()">🍗 开始答题 🍟</button>
        </div>

        <div id="quizArea" class="quiz-box">
            <div class="proverb-text" id="proverbText">加载题目中...</div>
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
            
            const response = await fetch(window.location.pathname, {
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
            console.error('加载题目失败', error);
            document.getElementById('proverbText').innerHTML = '加载失败，请刷新页面重试';
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
            const response = await fetch(window.location.pathname, {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            pendingAnswerData = data;
            playVideo(data.video);
            
        } catch (error) {
            console.error('提交答案失败', error);
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
            console.log('视频播放失败', e);
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
            feedbackHtml = `✅ 回答正确！<br>${data.message}`;
        } else {
            feedbackHtml = `❌ 错误！正确答案是：${data.correct_answer}<br>📖 ${data.explanation}<br>${data.message}`;
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
            restartBtn.textContent = '重新开始 🔄';
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
            await fetch(window.location.pathname, {
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
            console.error('重置失败', error);
            location.reload();
        }
    }
</script>

</body>
</html>