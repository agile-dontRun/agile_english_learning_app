<?php
session_start();
require_once '../db_connect.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
$user_id = $_SESSION['user_id'];

$username = ''; $nickname = 'Student'; $db_avatar = '';
if (isset($conn)) {
    $stmt = $conn->prepare("SELECT username, nickname, avatar_url FROM users WHERE user_id = ?");
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

$avatar_html = '';
$first_letter = strtoupper(substr($username ? $username : 'U', 0, 1));
if (!empty($db_avatar)) {
    $avatar_html = '<img src="../' . htmlspecialchars($db_avatar) . '" alt="Avatar" class="user-avatar-img" onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'flex\';">';
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
    <title>Emma AI Tutor - Spires Academy</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=Lora:wght@700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --oxford-blue: #002147; 
            --oxford-blue-light: #003066;
            --oxford-gold: #c4a661; 
            --oxford-gold-light: #d4b671;
            --white: #ffffff; 
            --bg-light: #f4f7f6;
            --text-dark: #333333; 
            --text-light: #666666;
            --ai-orb-blue: #0056b3; 
        }

        body {
            font-family: 'Open Sans', Arial, sans-serif;
            margin: 0; padding: 0;
            background-color: var(--bg-light);
            display: flex; flex-direction: column;
            min-height: 100vh;
        }

        /* ===== 1. 导航栏 (完全复刻 speakAI/Listening.php) ===== */
        .navbar { background-color: var(--oxford-blue); color: var(--white); display: flex; justify-content: space-between; align-items: center; padding: 0 40px; height: 80px; position: sticky; top: 0; z-index: 1000; box-shadow: 0 2px 10px rgba(0,0,0,0.2); }
        .navbar-left { display: flex; align-items: center; height: 100%; }
        .college-logo { height: 50px; width: auto; cursor: pointer; transition: transform 0.3s; }

        .navbar-links { display: flex; gap: 10px; list-style: none; margin: 0 0 0 40px; padding: 0; height: 100%; align-items: center; }
        .navbar-links > li { display: flex; align-items: center; position: relative; height: 100%; }
        .navbar-links a { 
            color: #ffffff; text-decoration: none; font-family: 'Playfair Display', serif;
            font-size: 16px; font-weight: 800; padding: 0 20px; height: 100%; display: flex; align-items: center; 
            text-transform: uppercase; letter-spacing: 1.8px; text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.6);
            transition: all 0.3s ease; -webkit-font-smoothing: antialiased;
        }
        .navbar-links a:hover { color: var(--oxford-gold); background-color: rgba(255, 255, 255, 0.05); }

        /* 下拉菜单 */
        .dropdown-menu { display: none; position: absolute; top: 80px; left: 0; background-color: var(--oxford-blue-light); min-width: 220px; box-shadow: 0 8px 16px rgba(0,0,0,0.2); list-style: none !important; padding: 0; margin: 0; border-top: 2px solid var(--oxford-gold); }
        .dropdown-menu li { list-style: none !important; margin: 0; padding: 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .dropdown-menu li a { color: #e0e0e0 !important; padding: 15px 20px; text-transform: none; justify-content: flex-start; width: 100%; box-sizing: border-box; text-decoration: none !important; display: block; font-weight: 400; height: auto; text-shadow: none; letter-spacing: 0.5px;}
        .dropdown-menu li a:hover { background-color: var(--oxford-blue) !important; color: var(--white) !important; padding-left: 25px; }
        .navbar-links li:hover .dropdown-menu { display: block; }

        /* 右侧用户 */
        .navbar-right { display: flex; align-items: center; gap: 10px; cursor: pointer; height: 100%; position: relative; }
        .user-avatar-img { width: 40px; height: 40px; border-radius: 50%; border: 2px solid var(--oxford-gold); object-fit: cover; }
        .user-avatar-placeholder { width: 40px; height: 40px; border-radius: 50%; background-color: var(--oxford-gold); color: var(--oxford-blue); display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 18px; border: 2px solid var(--oxford-gold); line-height: 1; box-sizing: border-box; }
        .navbar-right .dropdown-menu { background-color: var(--white); border-top: none; border-radius: 0 0 8px 8px; right: 0; left: auto; overflow: hidden; font-family: 'Playfair Display', serif; }
        .navbar-right:hover .dropdown-menu { display: block; }
        .navbar-right .dropdown-menu li a { color: var(--oxford-blue) !important; font-weight: 700; font-size: 15px; letter-spacing: 0.5px; }
        .navbar-right .dropdown-menu li a:hover { background-color: #f8fafc !important; color: var(--oxford-gold) !important; }

        /* ===== 2. Hero 区域 (完全复刻 speakAI.php) ===== */
        .hero {
            background: url('../hero_bg2.png') center/cover no-repeat; 
            color: var(--white); text-align: center; padding: 100px 20px;
            text-shadow: 0 2px 8px rgba(0,0,0,0.8);
        }
        .hero h1 { 
            font-family: 'Playfair Display', serif; font-size: 5rem; font-weight: 800; 
            margin: 0 0 15px; text-transform: uppercase; letter-spacing: 5px; 
            text-shadow: 2px 4px 10px rgba(0, 0, 0, 0.8);
        }
        .hero p { font-family: 'Playfair Display', serif; font-size: 1.4rem; font-weight: 400; font-style: italic; max-width: 800px; margin: 0 auto; text-shadow: 1px 2px 5px rgba(0, 0, 0, 0.8); }

        /* ===== 3. 主布局 (Emma 工具区) ===== */
        .main-content { 
            flex: 1; display: flex; max-width: 1400px; width: 100%; margin: -40px auto 40px; 
            background: var(--white); border-radius: 8px; box-shadow: 0 20px 50px rgba(0,0,0,0.1); 
            overflow: hidden; position: relative; z-index: 10;
            min-height: 600px;
        }
        
        .tutor-panel { 
            flex: 1; background-color: var(--white); border-right: 1px solid #eee;
            display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px;
        }
        
        .chat-panel { flex: 1.5; display: flex; flex-direction: column; background-color: #fcfdfe; }

        /* AI 语音球 */
        .ai-orb-container { position: relative; margin-bottom: 40px; }
        .ai-orb {
            width: 180px; height: 180px;
            background: radial-gradient(circle at 30% 30%, var(--ai-orb-blue), var(--oxford-blue));
            border-radius: 50%; box-shadow: 0 15px 35px rgba(0, 33, 71, 0.2);
            transition: all 0.3s ease;
        }
        .ai-orb.speaking { animation: bouncePulse 1s infinite ease-in-out; border: 4px solid var(--oxford-gold); }
        
        @keyframes bouncePulse {
            0% { transform: scale(1); box-shadow: 0 0 20px rgba(196, 166, 97, 0.4); }
            50% { transform: scale(1.05); box-shadow: 0 0 40px rgba(196, 166, 97, 0.7); }
            100% { transform: scale(1); box-shadow: 0 0 20px rgba(196, 166, 97, 0.4); }
        }

        .control-btn { 
            padding: 15px 40px; font-size: 14px; font-weight: 800; border: none; 
            border-radius: 4px; cursor: pointer; text-transform: uppercase; width: 100%; max-width: 280px;
            transition: all 0.3s; font-family: 'Playfair Display', serif; letter-spacing: 1.5px;
        }
        #startBtn { background-color: var(--oxford-gold); color: var(--oxford-blue); box-shadow: 0 4px 15px rgba(196, 166, 97, 0.3); }
        #startBtn:hover { background-color: var(--oxford-gold-light); transform: translateY(-2px); }
        #endBtn { background-color: #dc3545; color: white; }

        /* 聊天气泡 */
        #chat { flex: 1; padding: 40px; overflow-y: auto; display: flex; flex-direction: column; gap: 20px; }
        .msg { max-width: 80%; padding: 18px 25px; border-radius: 8px; line-height: 1.7; font-size: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
        .user { background-color: var(--oxford-blue); color: var(--white); align-self: flex-end; border-top-right-radius: 2px; }
        .ai { background-color: var(--white); color: var(--text-dark); align-self: flex-start; border-top-left-radius: 2px; border: 1px solid #eee; }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="navbar-left">
            <a href="../home.php"><img src="../college_logo.png" alt="Spires Academy Logo" class="college-logo"></a>
            <ul class="navbar-links">
                <li><a href="../home.php">Home</a></li>
                <li class="dropdown">
                    <a href="#" style="color:var(--oxford-gold);">Study ▾</a>
                    <ul class="dropdown-menu">
                        <li><a href="../listening.php">Listening Center</a></li>
                        <li><a href="../reading.php">Reading Room</a></li>
                        <li><a href="#" style="color:var(--oxford-gold)!important; font-weight:bold;">Emma Speaking</a></li>
                        <li><a href="../writing.php">Writing Lab</a></li>
                    </ul>
                </li>
                <li class="dropdown">
                    <a href="#">Games ▾</a>
                    <ul class="dropdown-menu">
                        <li><a href="../vocabulary_game.php">Vocabulary Game</a></li>
                        <li><a href="../galgame/galgame/index.html">Story game</a></li>
                    </ul>
                </li>
                <li><a href="../forum.php">Community</a></li>
            </ul>
        </div>

        <div class="navbar-right dropdown">
            <?php echo $avatar_html; ?>
            <span style="font-size:14px; font-weight:600; color:#e0e0e0;"><?php echo htmlspecialchars($nickname); ?> ▾</span>
            <ul class="dropdown-menu" style="right:0; left:auto; margin-top:0; min-width:220px;">
                <li style="padding: 20px; background: #f8fafc; cursor:default;">
                    <div style="color:var(--text-light); font-size:12px; margin-bottom:5px; font-family:'Playfair Display', serif; font-style:italic;">Signed in as</div>
                    <div style="color:var(--oxford-blue); font-weight:bold; font-size:16px; text-transform:uppercase; font-family:'Playfair Display', serif;"><?php echo htmlspecialchars($nickname); ?></div>
                </li>
                <li><a href="../profile.php">📄 My Profile</a></li>
                <li><a href="../logout.php" style="color:#dc3545 !important; font-weight: 600;">🚪 Sign Out</a></li>
            </ul>
        </div>
    </nav>

    <header class="hero">
        <h1>Emma Speaking</h1>
        <p>Refining Articulation • Elevating Academic Fluency</p>
    </header>

    <main class="main-content">
        <div class="tutor-panel">
            <div class="ai-orb-container"><div class="ai-orb" id="aiOrb"></div></div>
            <div class="tutor-controls">
                <div id="status" style="margin-bottom: 20px; color: var(--oxford-blue); font-weight: 700; text-transform: uppercase; font-size: 12px; letter-spacing: 1px;">Ready</div>
                <button id="startBtn" class="control-btn">🎤 Start Session</button>
                <button id="endBtn" class="control-btn" style="display:none">🛑 End Session</button>
            </div>
        </div>

        <div class="chat-panel">
            <div id="chat">
                <div class="msg ai">Welcome, Scholar. I am Emma. Let's begin our practice session.</div>
            </div>
        </div>
    </main>

    <script>
        // ======= 注意：这里保留了原始 index.php 的核心功能逻辑，没有被修改 =======
        // 核心集成逻辑 (已修复噪音和 Python 3.6 兼容性)
        const SERVER_URL = 'ws://8.162.9.154:8082/ws/english_tutor';
        const aiOrb = document.getElementById('aiOrb');
        const chatBox = document.getElementById('chat');
        const startBtn = document.getElementById('startBtn');
        const endBtn = document.getElementById('endBtn');
        const statusDiv = document.getElementById('status');

        let ws = null;
        let audioCtx, playbackCtx, mediaStream, processor;
        let nextPlayTime = 0; 

        startBtn.onclick = async () => {
            chatBox.innerHTML = '';
            startBtn.style.display = 'none';
            endBtn.style.display = 'inline-block';
            
            ws = new WebSocket(SERVER_URL);
            ws.binaryType = 'arraybuffer';
            ws.onopen = () => { statusDiv.innerText = 'Syncing...'; initAudio(); };
            ws.onmessage = (e) => {
                if (typeof e.data === 'string') handleText(JSON.parse(e.data).content);
                else playAudio(e.data);
            };
            ws.onclose = () => location.reload();
        };

        async function initAudio() {
            try {
                playbackCtx = new (window.AudioContext || window.webkitAudioContext)({ sampleRate: 24000 });
                mediaStream = await navigator.mediaDevices.getUserMedia({ audio: true });
                audioCtx = new AudioContext({ sampleRate: 16000 });
                const source = audioCtx.createMediaStreamSource(mediaStream);
                processor = audioCtx.createScriptProcessor(4096, 1, 1);
                processor.onaudioprocess = (e) => {
                    if (ws && ws.readyState === 1) ws.send(floatTo16(e.inputBuffer.getChannelData(0)));
                };
                source.connect(processor);
                processor.connect(audioCtx.destination);
                statusDiv.innerText = 'Listening...';
            } catch (err) { alert('Mic blocked.'); }
        }

        function playAudio(data) {
            if (!playbackCtx) return;
            const int16 = new Int16Array(data), float32 = new Float32Array(int16.length);
            for (let i = 0; i < int16.length; i++) float32[i] = int16[i] / 32768.0;
            const buffer = playbackCtx.createBuffer(1, float32.length, 24000);
            buffer.copyToChannel(float32, 0);
            const source = playbackCtx.createBufferSource();
            source.buffer = buffer;
            source.connect(playbackCtx.destination);

            const now = playbackCtx.currentTime;
            if (nextPlayTime < now) nextPlayTime = now + 0.05;
            source.start(nextPlayTime);
            nextPlayTime += buffer.duration;

            aiOrb.classList.add('speaking');
            source.onended = () => { if (playbackCtx.currentTime >= nextPlayTime - 0.1) aiOrb.classList.remove('speaking'); };
        }

                // 在 <script> 标签内定义一个全局变量，用于跟踪当前的 AI 气泡
        let currentAiBubble = null; 
        
        function handleText(text) {
            // 1. 当检测到用户开始说话（中断信号）时，重置当前气泡
            if (text === "__INTERRUPT__") {
                currentAiBubble = null;
                return;
            }
        
            // 2. 处理用户自己的识别结果（建议在 audio_manager.py 中增加此逻辑，见下文）
            if (text.startsWith("USER_MSG:")) {
                currentAiBubble = null; // 用户说话，AI 气泡结束
                const userText = text.replace("USER_MSG:", "");
                const bubble = document.createElement('div');
                bubble.className = 'msg user';
                bubble.innerText = userText;
                chatBox.appendChild(bubble);
                chatBox.scrollTop = chatBox.scrollHeight;
                return;
            }
        
            // 3. 处理 AI 的文字碎片追加逻辑
            // 如果当前没有正在显示的 AI 气泡，则创建一个新的
            if (!currentAiBubble) {
                currentAiBubble = document.createElement('div');
                currentAiBubble.className = 'msg ai';
                currentAiBubble.innerHTML = `<b>AI:</b> `;
                currentAiBubble.setAttribute('data-raw-content', ""); // 用于存储未格式化的原始文本
                chatBox.appendChild(currentAiBubble);
            }
        
            // 获取旧内容并追加新碎片
            let fullContent = currentAiBubble.getAttribute('data-raw-content') + text;
            currentAiBubble.setAttribute('data-raw-content', fullContent);
        
            // 实时更新气泡内容，并保持 [Score: X/10] 的高亮显示
            const highlightedText = fullContent.replace(/(\[Score:.*?\])/g, '<b style="color:var(--oxford-gold)">$1</b>');
            currentAiBubble.innerHTML = `<b>AI:</b> ${highlightedText}`;
            
            // 自动滚动到底部
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        function floatTo16(input) {
            let output = new Int16Array(input.length);
            for (let i = 0; i < input.length; i++) {
                let s = Math.max(-1, Math.min(1, input[i]));
                output[i] = s < 0 ? s * 0x8000 : s * 0x7FFF;
            }
            return output.buffer;
        }

        endBtn.onclick = () => location.reload();
    </script>
</body>
</html>