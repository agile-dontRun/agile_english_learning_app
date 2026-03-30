<?php
/**
 * DB Connection
 */
$host = 'localhost';
$db = 'english_learning_app';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

try {
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (\PDOException $e) {
    die("Connection Failed: " . $e->getMessage());
}

/**
 * AJAX Handler: Add word to notebook
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_to_notebook') {
    header('Content-Type: application/json');
    $user_id = 1;
    $word_id = intval($_POST['word_id']);

    try {
        $stmt = $pdo->prepare("SELECT notebook_id FROM vocabulary_notebooks WHERE user_id = ? LIMIT 1");
        $stmt->execute([$user_id]);
        $notebook = $stmt->fetch();

        if (!$notebook) {
            $ins = $pdo->prepare("INSERT INTO vocabulary_notebooks (user_id, notebook_name, created_at) VALUES (?, 'My Vocabulary', NOW())");
            $ins->execute([$user_id]);
            $notebook_id = $pdo->lastInsertId();
        } else {
            $notebook_id = $notebook['notebook_id'];
        }

        $check = $pdo->prepare("SELECT 1 FROM notebook_word_records WHERE notebook_id = ? AND word_id = ?");
        $check->execute([$notebook_id, $word_id]);

        if (!$check->fetch()) {
            $add = $pdo->prepare("INSERT INTO notebook_word_records (notebook_id, word_id, familiarity_level, added_at) VALUES (?, ?, 'New', NOW())");
            $add->execute([$notebook_id, $word_id]);
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'exists']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

/**
 * View Logic
 */
$view = $_GET['view'] ?? 'search';
$query = trim($_GET['search'] ?? '');
$user_id = 1;
$display_data = [];

if ($view === 'search' && !empty($query)) {
    $stmt = $pdo->prepare("SELECT * FROM words WHERE english_word LIKE ? ORDER BY english_word ASC LIMIT 50");
    $stmt->execute([$query . '%']);
    $display_data = $stmt->fetchAll();
} elseif ($view === 'notebook') {
    $stmt = $pdo->prepare("SELECT w.*, nwr.familiarity_level FROM notebook_word_records nwr JOIN words w ON nwr.word_id = w.word_id JOIN vocabulary_notebooks vn ON nwr.notebook_id = vn.notebook_id WHERE vn.user_id = ? ORDER BY nwr.added_at DESC");
    $stmt->execute([$user_id]);
    $display_data = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Vocabulary Center</title>
    <style>
        :root {
            --primary-green: #4CAF50;
            --nav-bg: #90EE90;
            --row-even: #E8F5E9;
            --text-green: #2E7D32;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            background: #fdfdfd;
            padding-bottom: 100px;
        }

        .main-nav {
            background: var(--nav-bg);
            display: flex;
            padding: 15px 20px 0;
            gap: 5px;
        }

        .nav-item {
            padding: 10px 20px;
            border-radius: 10px 10px 0 0;
            text-decoration: none;
            color: var(--text-green);
            font-weight: bold;
            background: #E0F2F1;
            font-size: 14px;
        }

        .nav-item.active {
            background: var(--primary-green);
            color: white;
        }

        .container {
            padding: 30px;
            max-width: 900px;
            margin: 0 auto;
        }

        .view-toggle {
            display: flex;
            justify-content: center;
            margin-bottom: 25px;
        }

        .toggle-btn {
            padding: 8px 25px;
            border: 2px solid var(--primary-green);
            text-decoration: none;
            color: var(--primary-green);
            font-weight: bold;
        }

        .toggle-btn.active {
            background: var(--primary-green);
            color: white;
        }

        .first {
            border-radius: 20px 0 0 20px;
        }

        .last {
            border-radius: 0 20px 20px 0;
            border-left: none;
        }

        .search-box {
            display: flex;
            margin-bottom: 25px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .search-box input {
            flex: 1;
            padding: 12px;
            border: 1px solid #ddd;
            outline: none;
        }

        .search-box button {
            background: var(--primary-green);
            color: white;
            border: none;
            padding: 0 30px;
            cursor: pointer;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }

        .data-table th {
            background: var(--primary-green);
            color: white;
            text-align: left;
            padding: 15px;
        }

        .data-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        .data-table tr:nth-child(even) {
            background: var(--row-even);
        }

        .btn-add {
            color: #2196F3;
            background: none;
            border: 1px solid #2196F3;
            border-radius: 4px;
            padding: 4px 12px;
            cursor: pointer;
            font-size: 12px;
            font-weight: bold;
        }

        .tag-status {
            background: #81C784;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
        }

        /* --- AI Assistant CSS --- */
        #ai-bubble {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            background: #4CAF50;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 28px;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            z-index: 9999;
            transition: transform 0.3s;
        }

        #ai-bubble:hover {
            transform: scale(1.1);
        }

        #ai-window {
            position: fixed;
            bottom: 100px;
            right: 30px;
            width: 350px;
            height: 500px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            display: none;
            flex-direction: column;
            overflow: hidden;
            z-index: 9999;
        }

        #ai-header {
            background: var(--primary-green);
            color: white;
            padding: 15px;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
        }

        #ai-body {
            flex: 1;
            padding: 15px;
            overflow-y: auto;
            background: #f9f9f9;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .ai-msg {
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 14px;
            max-width: 85%;
            line-height: 1.4;
        }

        .ai-msg.user {
            background: #DCF8C6;
            align-self: flex-end;
            color: #333;
        }

        .ai-msg.bot {
            background: white;
            align-self: flex-start;
            border: 1px solid #eee;
            color: #333;
        }

        #ai-footer {
            padding: 15px;
            border-top: 1px solid #eee;
            display: flex;
            gap: 10px;
        }

        #ai-input {
            flex: 1;
            border: 1px solid #ddd;
            padding: 8px;
            border-radius: 5px;
            outline: none;
        }

        #ai-send {
            background: var(--primary-green);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
        }
    </style>
</head>

<body>

    <div class="main-nav">
        <a href="#" class="nav-item">TED TALK</a>
        <a href="#" class="nav-item">IELTS LISTENING</a>
        <a href="#" class="nav-item">DAILY TALK</a>
        <a href="?view=search" class="nav-item active">VOCABULARY</a>
        <a href="#" class="nav-item">CALENDAR</a>
    </div>

    <div class="container">
        <div class="view-toggle">
            <a href="?view=search&search=<?= urlencode($query) ?>"
                class="toggle-btn first <?= $view === 'search' ? 'active' : '' ?>">Search</a>
            <a href="?view=notebook" class="toggle-btn last <?= $view === 'notebook' ? 'active' : '' ?>">My Notebook</a>
        </div>

        <?php if ($view === 'search'): ?>
            <form class="search-box" method="GET">
                <input type="hidden" name="view" value="search">
                <input type="text" name="search" placeholder="Type letters (e.g. 'a')"
                    value="<?= htmlspecialchars($query) ?>" autofocus>
                <button type="submit">SEARCH</button>
            </form>
        <?php endif; ?>

        <table id="word-table" class="data-table">
            <thead>
                <tr>
                    <th>WORD</th>
                    <th>PHONETIC</th>
                    <th>MEANING</th>
                    <th>ACTION</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($display_data):
                    foreach ($display_data as $row): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($row['english_word']) ?></strong></td>
                            <td><?= htmlspecialchars($row['phonetic']) ?></td>
                            <td><?= htmlspecialchars($row['chinese_meaning']) ?></td>
                            <td>
                                <?php if ($view === 'search'): ?>
                                    <button class="btn-add" onclick="handleAdd(this, <?= $row['word_id'] ?>)">+ Add</button>
                                <?php else: ?>
                                    <span class="tag-status"><?= htmlspecialchars($row['familiarity_level']) ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                    <tr>
                        <td colspan="4" style="text-align:center; color:#999; padding:40px;">No words found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div id="ai-bubble">💬</div>
    <div id="ai-window">
        <div id="ai-header"><span>Doubao Assistant</span><span style="cursor:pointer" onclick="toggleAI()">×</span>
        </div>
        <div id="ai-body">
            <div class="ai-msg bot">Hi! I can help you with the words on this page. What's on your mind?</div>
        </div>
        <div id="ai-footer">
            <input type="text" id="ai-input" placeholder="Ask about these words...">
            <button id="ai-send">Send</button>
        </div>
    </div>

    <script>
        // 1. Existing Add Logic
        function handleAdd(btn, wordId) {
            btn.disabled = true; btn.innerText = "Adding...";
            const data = new URLSearchParams();
            data.append('action', 'add_to_notebook');
            data.append('word_id', wordId);
            fetch('vocabulary.php', { method: 'POST', body: data })
                .then(r => r.json()).then(res => {
                    if (res.status === 'success' || res.status === 'exists') {
                        btn.innerText = "✓ Added"; btn.style.borderColor = "#4CAF50";
                    }
                });
        }

        // 2. AI Assistant Logic
        const aiWindow = document.getElementById('ai-window');
        const aiInput = document.getElementById('ai-input');
        const aiBody = document.getElementById('ai-body');

        function toggleAI() { aiWindow.style.display = aiWindow.style.display === 'flex' ? 'none' : 'flex'; }
        document.getElementById('ai-bubble').onclick = toggleAI;

        document.getElementById('ai-send').onclick = async () => {
            const text = aiInput.value.trim();
            if (!text) return;

            // UI Update
            appendMsg('user', text);
            aiInput.value = '';

            // Grab page context (the words currently in the table)
            const wordsOnPage = Array.from(document.querySelectorAll('#word-table strong')).map(el => el.innerText).join(', ');

            try {
                const res = await fetch('ai_proxy.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message: text, context: { words: wordsOnPage, page: document.title } })
                });
                const data = await res.json();
                appendMsg('bot', data.reply);
            } catch (e) { appendMsg('bot', "Error connecting to AI."); }
        };

        function appendMsg(role, text) {
            const div = document.createElement('div');
            div.className = `ai-msg ${role}`;
            div.innerText = text;
            aiBody.appendChild(div);
            aiBody.scrollTop = aiBody.scrollHeight;
        }
    </script>
</body>

</html>
