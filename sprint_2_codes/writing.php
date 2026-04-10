<?php
session_start();
require_once 'db_connect.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = ''; $nickname = 'Student'; $db_avatar = '';
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
    <title>Writing Center - Spires Academy</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=Lora:wght@400;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    
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
            --border-color: #e0e0e0;
        }

        body { margin: 0; padding: 0; font-family: 'Open Sans', Arial, sans-serif; background-color: var(--bg-light); color: var(--text-dark); overflow: hidden; }
        
        /* Navbar Styles */
        .navbar { background-color: var(--oxford-blue); color: var(--white); display: flex; justify-content: space-between; align-items: center; padding: 0 40px; height: 80px; position: sticky; top: 0; z-index: 1000; box-shadow: 0 2px 10px rgba(0,0,0,0.2); }
        .navbar-left { display: flex; align-items: center; height: 100%; }
        .college-logo { height: 50px; width: auto; cursor: pointer; transition: transform 0.3s; }
        .college-logo:hover { transform: scale(1.02); }

        .navbar-links { display: flex; gap: 10px; list-style: none; margin: 0 0 0 40px; padding: 0; height: 100%; align-items: center; }
        .navbar-links > li { display: flex; align-items: center; position: relative; height: 100%; }
        .navbar-links a { 
            color: #ffffff; 
            text-decoration: none; 
            font-family: 'Playfair Display', serif;
            font-size: 16px; 
            font-weight: 800; 
            padding: 0 20px; 
            height: 100%; 
            display: flex; 
            align-items: center; 
            text-transform: uppercase; 
            letter-spacing: 1.8px; 
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.6);
            transition: all 0.3s ease; 
        }
        .navbar-links a:hover { color: var(--oxford-gold); background-color: rgba(255, 255, 255, 0.05); }

        .dropdown-menu { display: none; position: absolute; top: 80px; left: 0; background-color: var(--oxford-blue-light); min-width: 220px; box-shadow: 0 8px 16px rgba(0,0,0,0.2); list-style: none !important; padding: 0; margin: 0; border-top: 2px solid var(--oxford-gold); }
        .dropdown-menu li { list-style: none !important; margin: 0; padding: 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .dropdown-menu li a { color: #e0e0e0 !important; padding: 15px 20px; text-transform: none; justify-content: flex-start; width: 100%; box-sizing: border-box; text-decoration: none !important; display: block; font-weight: 400; height: auto; text-shadow: none; letter-spacing: 0.5px;}
        .dropdown-menu li a:hover { background-color: var(--oxford-blue) !important; color: var(--white) !important; padding-left: 25px; }
        .navbar-links li:hover .dropdown-menu, .dropdown:hover .dropdown-menu { display: block; }

        .navbar-right { display: flex; align-items: center; gap: 10px; cursor: pointer; height: 100%; position: relative; }
        .user-avatar-img { width: 40px; height: 40px; border-radius: 50%; border: 2px solid var(--oxford-gold); object-fit: cover; }
        .user-avatar-placeholder { width: 40px; height: 40px; border-radius: 50%; background-color: var(--oxford-gold); color: var(--oxford-blue); display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 18px; border: 2px solid var(--oxford-gold); line-height: 1; box-sizing: border-box; }
        .navbar-right .dropdown-menu { background-color: var(--white); border-top: none; border-radius: 0 0 8px 8px; font-family: 'Playfair Display', serif; }
        .navbar-right .dropdown-menu li div[style*="font-size:12px"] { font-family: 'Playfair Display', serif !important; font-style: italic; color: #888 !important; }
        .navbar-right .dropdown-menu li div[style*="font-size:16px"] { font-family: 'Playfair Display', serif !important; font-weight: 800; color: var(--oxford-blue) !important; text-transform: uppercase; }
        .navbar-right .dropdown-menu li a { font-family: 'Playfair Display', serif !important; font-weight: 700; font-size: 15px; color: var(--oxford-blue) !important; transition: all 0.2s ease; }
        .navbar-right .dropdown-menu li a:hover { background-color: #f8fafc !important; color: var(--oxford-gold) !important; padding-left: 25px; }


        /* Writing Interface Styles */
        #writing-wrapper { display: flex; width: 100vw; height: calc(100vh - 80px); }

        #luna-side {
            flex: 0 0 35%;
            background: linear-gradient(to bottom, var(--oxford-blue), var(--oxford-blue-light));
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            position: relative; color: white; padding: 40px; text-align: center;
        }
        
        #luna-avatar { font-size: 180px; margin-bottom: 20px; filter: drop-shadow(0 10px 20px rgba(0,0,0,0.3)); }
        
        #luna-bubble {
            background: rgba(255,255,255,0.1); backdrop-filter: blur(10px);
            padding: 20px; border-radius: 15px; border: 1px solid var(--oxford-gold);
            font-family: 'Lora', serif; font-size: 17px; line-height: 1.6;
            margin-bottom: 30px; max-width: 90%;
        }

        .mode-selector { display: flex; flex-direction: column; gap: 10px; width: 80%; }
        .btn-mode { 
            background: transparent; border: 1px solid var(--oxford-gold); color: var(--oxford-gold);
            padding: 10px; cursor: pointer; font-family: 'Playfair Display', serif; font-weight: 600;
            transition: 0.3s;
        }
        .btn-mode:hover, .btn-mode.active { background: var(--oxford-gold); color: var(--oxford-blue); }
        select.btn-mode { appearance: none; text-align: center; outline: none; }
        select.btn-mode option { background-color: var(--oxford-blue); color: var(--white); }

        #work-side { flex: 1; display: flex; flex-direction: column; background: var(--white); padding: 40px; box-sizing: border-box; }
        
        #topic-container { 
            margin-bottom: 20px; padding: 25px; background: #fdfaf2; 
            border-left: 5px solid var(--oxford-gold); border-radius: 4px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        #topic-text { font-family: 'Lora', serif; font-weight: 700; color: var(--oxford-blue); font-size: 19px; margin: 0; line-height: 1.5; }
        
        #editor-container { flex: 1; display: flex; flex-direction: column; }
        textarea {
            flex: 1; border: 1px solid var(--border-color); border-radius: 8px; padding: 30px;
            font-size: 17px; line-height: 1.8; color: var(--text-dark); outline: none;
            resize: none; font-family: 'Open Sans', sans-serif; background: #fff;
            box-shadow: inset 0 2px 5px rgba(0,0,0,0.02);
        }
        textarea:focus { border-color: var(--oxford-gold); }

        #writing-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 25px; }
        .word-count { color: var(--text-light); font-weight: 600; font-family: 'Playfair Display', serif; }
        
        .btn-submit {
            background: var(--oxford-blue); color: white; border: none;
            padding: 14px 35px; border-radius: 4px; font-weight: bold;
            font-family: 'Playfair Display', serif; text-transform: uppercase;
            cursor: pointer; transition: 0.3s; letter-spacing: 1px;
        }
        .btn-submit:hover { background: var(--oxford-gold); color: var(--oxford-blue); }

        /* Modal Overlays */
        .overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 33, 71, 0.9); backdrop-filter: blur(5px);
            display: none; align-items: center; justify-content: center; z-index: 2000;
        }
        .modal-card { background: white; width: 850px; max-height: 85vh; border-radius: 8px; padding: 40px; overflow-y: auto; border-top: 8px solid var(--oxford-gold); }
        .score-box { font-family: 'Playfair Display', serif; font-size: 60px; color: var(--oxford-blue); font-weight: 800; text-align: center; margin-bottom: 20px; }
        .feedback-title { font-family: 'Playfair Display', serif; font-weight: 800; color: var(--oxford-blue); border-bottom: 1px solid var(--oxford-gold); padding-bottom: 10px; margin: 20px 0 10px; text-transform: uppercase; font-size: 14px; }
        
        /* History Card Hover Effect */
        .history-card-item { transition: 0.3s; box-shadow: 0 2px 5px rgba(0,0,0,0.02); }
        .history-card-item:hover { box-shadow: 0 8px 20px rgba(0,0,0,0.1); border-color: var(--oxford-gold); transform: translateY(-2px); }

        /* Global Loading Spinner Styles */
        #global-loading {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            background: rgba(0, 33, 71, 0.85); backdrop-filter: blur(8px);
            display: none; flex-direction: column; align-items: center; justify-content: center;
            z-index: 9999; /* Ensure it covers everything */
            color: var(--white); text-align: center;
        }
        .spinner {
            width: 70px; height: 70px;
            border: 6px solid rgba(196, 166, 97, 0.2); /* Faded Gold */
            border-top: 6px solid var(--oxford-gold); /* Solid Gold */
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 25px;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        #loading-message {
            font-family: 'Playfair Display', serif;
            font-size: 22px; letter-spacing: 1px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.5);
        }
    </style>
</head>
<body>

    <div id="global-loading">
        <div class="spinner"></div>
        <div id="loading-message">Loading...</div>
    </div>

    <nav class="navbar">
        <div class="navbar-left">
            <a href="home.php"><img src="college_logo.png" alt="Spires Academy Logo" class="college-logo"></a>
            <ul class="navbar-links">
                <li><a href="home.php">Home</a></li>
                <li class="dropdown">
                    <a href="#" style="color:var(--oxford-gold);">Study ▾</a>
                    <ul class="dropdown-menu">
                        <li><a href="listening.php">Listening</a></li>
                        <li><a href="reading.php">Reading</a></li>
                        <li><a href="emma_server/speakAI.php">Speaking</a></li>
                        <li><a href="writing.php" style="color:var(--oxford-gold)!important; font-weight: bold;">Writing</a></li>
                        <li><a href="vocabulary.php">Vocabulary</a></li>
                    </ul>
                </li>
                <li class="dropdown">
                    <a href="#">Games ▾</a>
                    <ul class="dropdown-menu">
                        <li><a href="galgame/galgame/index.html">Story Game</a></li>
                    </ul>
                </li>
                <li><a href="forum.php">Community</a></li>
            </ul>
        </div>

        <div class="navbar-right dropdown">
            <?php echo $avatar_html; ?>
            <span style="font-size:14px; font-weight:600; color:#e0e0e0;"><?php echo htmlspecialchars($nickname); ?> ▾</span>
            <ul class="dropdown-menu" style="right:0; left:auto; margin-top:0; min-width:220px;">
                <li style="padding: 20px; background: #f8fafc; cursor:default;">
                    <div style="color:var(--text-light); font-size:12px; margin-bottom:5px;">Signed in as</div>
                    <div style="color:var(--oxford-blue); font-weight:bold; font-size:16px;"><?php echo htmlspecialchars($nickname); ?></div>
                </li>
                <li><a href="profile.php">My Profile</a></li>
                <li><a href="logout.php" style="color:#dc3545 !important; font-weight: 600;">Sign Out</a></li>
            </ul>
        </div>
    </nav>

    <div id="writing-wrapper">
        <div id="luna-side">
            <div id="luna-avatar">👩‍🏫</div>
            <div id="luna-bubble">
                Welcome to the Writing Center. Shall we generate a new AI topic, or would you like to practice with a Past Paper?
            </div>
            
            <div class="mode-selector">
                <button class="btn-mode active" id="btn-ai" onclick="setMode('ai')">AI Dynamic Topic</button>
                
                <select class="btn-mode" id="past-paper-select" onchange="if(this.value) setMode('past')">
                    <option value="" disabled selected>-- Select a Past Paper --</option>
                    <option value="ielts_cambridge_19_t1">IELTS Cambridge 19 - Test 1</option>
                    <option value="ielts_cambridge_19_t2">IELTS Cambridge 19 - Test 2</option>
                    <option value="ielts_cambridge_19_t3">IELTS Cambridge 19 - Test 3</option>
                    <option value="ielts_cambridge_19_t4">IELTS Cambridge 19 - Test 4</option>
                    <option value="ielts_cambridge_20_t1">IELTS Cambridge 20 - Test 1</option>
                    <option value="ielts_cambridge_20_t2">IELTS Cambridge 20 - Test 2</option>
                    <option value="ielts_cambridge_20_t3">IELTS Cambridge 20 - Test 3</option>
                </select>
                
                <div style="border-top: 1px solid rgba(196, 166, 97, 0.3); margin: 10px 0;"></div>
                <button class="btn-mode" style="background: rgba(255,255,255,0.05); border-style: dashed;" onclick="viewHistory()">📚 View My Archives</button>
            </div>
        </div>

        <div id="work-side">
            <div id="topic-container">
                <p id="topic-text">Please select a mode or click "Get Question" to begin...</p>
            </div>

            <div id="editor-container">
                <textarea id="essay-input" placeholder="Type your response here... Luna will evaluate your grammar, logic, and style once submitted."></textarea>
            </div>

            <div id="writing-footer">
                <div class="word-count">WORD COUNT: <span id="word-num">0</span></div>
                <div style="display:flex; gap: 15px;">
                    <button class="btn-submit" style="background:#666" onclick="refreshTopic()">Get Question</button>
                    <button class="btn-submit" id="submit-btn" onclick="submitEssay()">Submit for Review</button>
                </div>
            </div>
        </div>
    </div>

    <div id="result-overlay" class="overlay">
        <div class="modal-card">
            <div style="text-align: right;"><button onclick="closeResult()" style="cursor:pointer; background:none; border:none; font-size:30px; color:#888;">&times;</button></div>
            <div class="score-box" id="final-score">0.0</div>
            <div id="feedback-content"></div>
            <button class="btn-submit" style="width:100%; margin-top:30px;" onclick="closeResult()">Return to Desk</button>
        </div>
    </div>
    
    <div id="history-overlay" class="overlay">
        <div class="modal-card" style="background: #fdfaf2;">
            <div style="text-align: right;"><button onclick="closeHistory()" style="cursor:pointer; background:none; border:none; font-size:30px; color:#888;">&times;</button></div>
            <h2 style="font-family: 'Playfair Display', serif; color: var(--oxford-blue); font-weight: 800; margin-top: 0; border-bottom: 2px solid var(--oxford-gold); padding-bottom: 10px; text-transform: uppercase;">My Writing Archives</h2>
            <div id="history-list" style="margin-top: 20px;">
                </div>
        </div>
    </div>

    <script>
        const essayInput = document.getElementById('essay-input');
        const lunaBubble = document.getElementById('luna-bubble');
        const topicText = document.getElementById('topic-text');
        const wordNum = document.getElementById('word-num');
        const loadingOverlay = document.getElementById('global-loading');
        const loadingMessage = document.getElementById('loading-message');
        
        let currentTopic = "";
        let currentMode = "ai"; 
        let userHistory = []; 

        // Helper functions for loading state
        function showLoading(msg) {
            loadingMessage.innerText = msg;
            loadingOverlay.style.display = 'flex';
        }
        function hideLoading() {
            loadingOverlay.style.display = 'none';
        }

        // Handle Mode Changes
        function setMode(mode) {
            currentMode = mode;
            document.getElementById('btn-ai').classList.toggle('active', mode === 'ai');
            document.getElementById('past-paper-select').classList.toggle('active', mode === 'past');
            
            if (mode === 'ai') {
                document.getElementById('past-paper-select').value = ""; 
                lunaBubble.innerText = "I'll generate a fresh topic using AI for you.";
            } else {
                lunaBubble.innerText = "Excellent choice. We'll use official Cambridge past papers.";
            }
        }

        async function refreshTopic() {
            if (currentMode === 'ai') {
                generateAITopic();
            } else {
                const selectedFile = document.getElementById('past-paper-select').value;
                if (!selectedFile) {
                    alert("Please select a Past Paper from the dropdown menu first.");
                    return;
                }
                loadPastPaper(selectedFile);
            }
        }

        // 1. AI Logic
        async function generateAITopic() {
            showLoading("Consulting Luna for a challenging topic...");
            try {
                const res = await fetch('writing_proxy.php', {
                    method: 'POST',
                    body: JSON.stringify({ action: 'get_topic' })
                });
                const data = await res.json();
                currentTopic = data.topic;
                topicText.innerText = currentTopic;
                lunaBubble.innerText = "Topic ready! Focus on academic cohesion and signposting words.";
            } catch (e) {
                alert("Failed to connect to Luna's server.");
            } finally {
                hideLoading();
            }
        }

        // 2. Past Paper Logic
        async function loadPastPaper(fileId) {
            showLoading("Retrieving the requested past paper archives...");
            try {
                const res = await fetch('writing_proxy.php', {
                    method: 'POST',
                    body: JSON.stringify({ 
                        action: 'get_topic',
                        mode: 'past',
                        file_id: fileId 
                    })
                });
                const data = await res.json();
                currentTopic = data.topic;
                topicText.innerText = `[Past Paper] ${currentTopic}`;
                lunaBubble.innerText = "This is an official Cambridge task. Remember to address all parts of the prompt clearly.";
            } catch (e) {
                alert("Failed to load the past paper. Ensure the JSON files exist on the server.");
            } finally {
                hideLoading();
            }
        }

        // Word count event listener
        essayInput.addEventListener('input', () => {
            const words = essayInput.value.trim().split(/\s+/).filter(w => w.length > 0);
            wordNum.innerText = words.length;
        });

        // 3. Submission Logic
        async function submitEssay() {
            const content = essayInput.value.trim();
            if (content.length < 50) return alert("Your essay is a bit too short for a proper evaluation.");

            showLoading("Luna is evaluating your essay. This might take a moment...");
            
            try {
                const res = await fetch('writing_proxy.php', {
                    method: 'POST',
                    body: JSON.stringify({ 
                        action: 'evaluate', 
                        topic: currentTopic, 
                        content: content 
                    })
                });
                const data = await res.json();
                showResult(data);
            } catch (e) {
                alert("Evaluation server is busy. Please try again.");
            } finally {
                hideLoading();
            }
        }

        // 4. Display Feedback Results
        function showResult(data, isHistory = false) {
            document.getElementById('final-score').innerText = data.score;
            
            let feedbackHTML = `
                ${isHistory ? `<div style="background:#f8fafc; padding: 15px; border-radius: 4px; margin-bottom: 20px; font-size: 14px; border-left: 4px solid var(--oxford-blue);"><strong>Topic:</strong> ${data.topic || currentTopic}</div>` : ''}
                
                <div class="feedback-title">Criterion 1: Lexical Resource & Grammar</div>
                <p style="color:var(--text-light); line-height:1.6;">${data.grammar || data.grammar_feedback || 'N/A'}</p>
                
                <div class="feedback-title">Criterion 2: Coherence & Task Response</div>
                <p style="color:var(--text-light); line-height:1.6;">${data.logic || data.logic_feedback || 'N/A'}</p>
                
                <div style="background:#f4f7f6; padding:20px; border-radius:4px; margin-top:20px; border:1px dashed var(--oxford-gold);">
                    <div class="feedback-title" style="margin-top:0; border:none; color:var(--oxford-gold);">Luna's Polished Exemplar</div>
                    <p style="font-family:'Lora', serif; font-style:italic; color:var(--oxford-blue);">${data.polished || data.polished_content || 'N/A'}</p>
                </div>
            `;
            
            if (isHistory && data.content) {
                feedbackHTML += `
                <div style="margin-top: 30px;">
                    <div class="feedback-title" style="font-size: 12px; color: #888; border-bottom: none;">Your Original Submission</div>
                    <div style="background: #fff; border: 1px solid var(--border-color); padding: 15px; border-radius: 4px; color: var(--text-dark); font-size: 14px; max-height: 150px; overflow-y: auto; white-space: pre-wrap;">${data.content}</div>
                </div>`;
            }

            document.getElementById('feedback-content').innerHTML = feedbackHTML;
            document.getElementById('result-overlay').style.display = 'flex';
            
            lunaBubble.innerText = isHistory 
                ? `Reviewing your past submission. You scored ${data.score}. Always good to reflect on past work!` 
                : `Evaluation complete. You scored a ${data.score}. Look through my suggestions carefully!`;
        }

        // 5. Fetch and Display History
        async function viewHistory() {
            showLoading("Accessing your personal writing archives...");
            
            try {
                const res = await fetch('writing_proxy.php', {
                    method: 'POST',
                    body: JSON.stringify({ action: 'get_history' })
                });
                const data = await res.json();
                userHistory = data.history || [];
                
                const listDiv = document.getElementById('history-list');
                if (userHistory.length === 0) {
                    listDiv.innerHTML = "<p style='color:var(--text-light); font-style:italic;'>No writing records found yet. Submit your first essay to start building your archive!</p>";
                } else {
                    listDiv.innerHTML = userHistory.map((item, index) => `
                        <div class="history-card-item" style="background: white; border: 1px solid var(--border-color); padding: 20px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid var(--oxford-blue);">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 10px;">
                                <div style="color: var(--oxford-blue); font-size: 18px; font-weight: 800; font-family:'Playfair Display', serif;">Score: ${item.score}</div>
                                <div style="color: #888; font-size: 13px;">${new Date(item.created_at).toLocaleString()}</div>
                            </div>
                            <div style="font-family:'Lora', serif; font-weight:bold; color:var(--text-dark); margin-bottom: 15px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.5;">
                                ${item.topic}
                            </div>
                            <button onclick="openHistoricalResult(${index})" style="background:transparent; border:1px solid var(--oxford-gold); color:var(--oxford-gold); padding:6px 15px; border-radius:4px; cursor:pointer; font-weight:600; font-size:12px; transition:0.2s;">View Full Feedback</button>
                        </div>
                    `).join('');
                }
                document.getElementById('history-overlay').style.display = 'flex';
                lunaBubble.innerText = "Here are your past writing records.";
            } catch (e) {
                alert("Failed to load history.");
                lunaBubble.innerText = "I had trouble fetching your archives. Please try again.";
            } finally {
                hideLoading();
            }
        }

        // Open a single historical record for detailed view
        function openHistoricalResult(index) {
            closeHistory();
            const item = userHistory[index];
            showResult(item, true);
        }

        // Close modal functions
        function closeResult() {
            document.getElementById('result-overlay').style.display = 'none';
        }
        function closeHistory() {
            document.getElementById('history-overlay').style.display = 'none';
        }
    </script>
     <script src="ai-agent.js?v=1.4"></script>
</body>
</html>