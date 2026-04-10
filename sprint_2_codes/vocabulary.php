<?php
/**
 * Vocabulary Center - 核心业务与 AI 静默接口
 */
session_start();
// 统一使用 db_connect.php 进行数据库连接
require_once 'db_connect.php'; 

// 1. 验证用户登录状态
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = ''; $nickname = 'Student'; $db_avatar = '';

// 获取当前用户信息 (用于导航栏头像展示)
$stmt_user = $conn->prepare("SELECT username, nickname, avatar_url FROM users WHERE user_id = ?");
if ($stmt_user) {
    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $user_data = $stmt_user->get_result()->fetch_assoc();
    if ($user_data) {
        $username = $user_data['username'];
        $nickname = !empty($user_data['nickname']) ? $user_data['nickname'] : $username;
        $db_avatar = $user_data['avatar_url'];
    }
    $stmt_user->close();
}

// 构建头像 HTML
$avatar_html = '';
$first_letter = strtoupper(substr($username ? $username : 'U', 0, 1));
if (!empty($db_avatar)) {
    $avatar_html = '<img src="' . htmlspecialchars($db_avatar) . '" alt="Avatar" class="user-avatar-img" onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'flex\';">';
    $avatar_html .= '<div class="user-avatar-placeholder" style="display:none;">' . htmlspecialchars($first_letter) . '</div>';
} else {
    $avatar_html = '<div class="user-avatar-placeholder">' . htmlspecialchars($first_letter) . '</div>';
}

/**
 * 2. AJAX 接口：支持手动点击（ID）和 AI 静默（文本）加词 (全部转换为 mysqli)
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_to_notebook') {
    header('Content-Type: application/json');
    $word_id = isset($_POST['word_id']) ? intval($_POST['word_id']) : 0;
    $word_text = isset($_POST['word_text']) ? trim($_POST['word_text']) : '';

    try {
        // A. 静默模式：如果是 AI 通过单词文本请求，先查出 ID
        if ($word_id <= 0 && !empty($word_text)) {
            $stmt = $conn->prepare("SELECT word_id FROM words WHERE english_word = ? LIMIT 1");
            $stmt->bind_param("s", $word_text);
            $stmt->execute();
            $word_found = $stmt->get_result()->fetch_assoc();
            
            if ($word_found) {
                $word_id = $word_found['word_id'];
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Dictionary lookup failed']);
                exit;
            }
            $stmt->close();
        }

        // B. 确保用户有笔记本 (使用真实的 Session User ID)
        $stmt = $conn->prepare("SELECT notebook_id FROM vocabulary_notebooks WHERE user_id = ? LIMIT 1");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $notebook = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$notebook) {
            $ins = $conn->prepare("INSERT INTO vocabulary_notebooks (user_id, notebook_name, created_at) VALUES (?, 'My Vocabulary', NOW())");
            $ins->bind_param("i", $user_id);
            $ins->execute();
            $notebook_id = $conn->insert_id;
            $ins->close();
        } else {
            $notebook_id = $notebook['notebook_id'];
        }

        // C. 检查是否已在生词本中
        $check = $conn->prepare("SELECT 1 FROM notebook_word_records WHERE notebook_id = ? AND word_id = ?");
        $check->bind_param("ii", $notebook_id, $word_id);
        $check->execute();
        $is_exist = $check->get_result()->fetch_assoc();
        $check->close();

        if (!$is_exist) {
            $add = $conn->prepare("INSERT INTO notebook_word_records (notebook_id, word_id, familiarity_level, added_at) VALUES (?, ?, 'New', NOW())");
            $add->bind_param("ii", $notebook_id, $word_id);
            $add->execute();
            $add->close();
            echo json_encode(['status' => 'success', 'info' => 'Word added silently']);
        } else {
            echo json_encode(['status' => 'exists']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

/**
 * 3. 页面视图逻辑 (Search vs Notebook) (全部转换为 mysqli)
 */
$view = $_GET['view'] ?? 'search';
$query = trim($_GET['search'] ?? '');
$display_data = [];

if ($view === 'search' && !empty($query)) {
    $stmt = $conn->prepare("SELECT * FROM words WHERE english_word LIKE ? ORDER BY english_word ASC LIMIT 50");
    $like_query = $query . '%';
    $stmt->bind_param("s", $like_query);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $display_data[] = $row;
    }
    $stmt->close();
} elseif ($view === 'notebook') {
    $stmt = $conn->prepare("SELECT w.*, nwr.familiarity_level FROM notebook_word_records nwr 
                           JOIN words w ON nwr.word_id = w.word_id 
                           JOIN vocabulary_notebooks vn ON nwr.notebook_id = vn.notebook_id 
                           WHERE vn.user_id = ? ORDER BY nwr.added_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $display_data[] = $row;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vocabulary Center - Spires Academy</title>
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

        body { margin: 0; padding: 0; font-family: 'Open Sans', Arial, sans-serif; background-color: var(--bg-light); color: var(--text-dark); padding-bottom: 80px; }
        
        /* 继承自统一风格的导航栏样式 */
        .navbar { background-color: var(--oxford-blue); color: var(--white); display: flex; justify-content: space-between; align-items: center; padding: 0 40px; height: 80px; position: sticky; top: 0; z-index: 1000; box-shadow: 0 2px 10px rgba(0,0,0,0.2); }
        .navbar-left { display: flex; align-items: center; height: 100%; }
        .college-logo { height: 50px; width: auto; cursor: pointer; transition: transform 0.3s; }
        .college-logo:hover { transform: scale(1.02); }

        .navbar-links { display: flex; gap: 10px; list-style: none; margin: 0 0 0 40px; padding: 0; height: 100%; align-items: center; }
        .navbar-links > li { display: flex; align-items: center; position: relative; height: 100%; }
        .navbar-links a { color: #ffffff; text-decoration: none; font-family: 'Playfair Display', serif; font-size: 16px; font-weight: 800; padding: 0 20px; height: 100%; display: flex; align-items: center; text-transform: uppercase; letter-spacing: 1.8px; text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.6); transition: all 0.3s ease; }
        .navbar-links a:hover { color: var(--oxford-gold); background-color: rgba(255, 255, 255, 0.05); }
        
        .dropdown-menu { display: none; position: absolute; top: 80px; left: 0; background-color: var(--oxford-blue-light); min-width: 220px; box-shadow: 0 8px 16px rgba(0,0,0,0.2); list-style: none; padding: 0; margin: 0; border-top: 2px solid var(--oxford-gold); }
        .dropdown:hover .dropdown-menu { display: block; }
        .dropdown-menu li a { color: #e0e0e0 !important; padding: 15px 20px; text-transform: none; display: block; font-weight: 400; height: auto; text-shadow: none; letter-spacing: 0.5px;}
        .dropdown-menu li a:hover { background-color: var(--oxford-blue) !important; color: var(--white) !important; padding-left: 25px; }
        
        .navbar-right { display: flex; align-items: center; gap: 10px; cursor: pointer; height: 100%; position: relative; }
        .user-avatar-img, .user-avatar-placeholder { width: 40px; height: 40px; border-radius: 50%; border: 2px solid var(--oxford-gold); object-fit: cover; }
        .user-avatar-placeholder { background-color: var(--oxford-gold); color: var(--oxford-blue); display: flex; align-items: center; justify-content: center; font-weight: bold; }
        .navbar-right .dropdown-menu { background-color: var(--white); border-top: none; border-radius: 0 0 8px 8px; overflow: hidden; font-family: 'Playfair Display', serif; }
        .navbar-right .dropdown-menu li div[style*="font-size:12px"] { font-style: italic; color: #888; }
        .navbar-right .dropdown-menu li div[style*="font-size:16px"] { font-weight: 800; color: var(--oxford-blue); letter-spacing: 1px; text-transform: uppercase; }
        .navbar-right .dropdown-menu li a { font-weight: 700; font-size: 15px; color: var(--oxford-blue) !important; transition: all 0.2s ease; }
        .navbar-right .dropdown-menu li a:hover { background-color: #f8fafc !important; color: var(--oxford-gold) !important; padding-left: 25px; }

        /* Vocabulary 页面主体样式 */
        .container { padding: 40px; max-width: 1000px; margin: 20px auto 0; }
        
        /* 视图切换按钮 */
        .view-toggle { display: flex; justify-content: center; margin-bottom: 40px; }
        .toggle-btn { padding: 12px 35px; border: 2px solid var(--oxford-blue); text-decoration: none; color: var(--oxford-blue); font-weight: 700; font-family: 'Playfair Display', serif; transition: 0.3s; text-transform: uppercase; letter-spacing: 1px; font-size: 14px; }
        .toggle-btn:hover { background: rgba(0, 33, 71, 0.05); }
        .toggle-btn.active { background: var(--oxford-blue); color: var(--white); box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .first { border-radius: 8px 0 0 8px; }
        .last { border-radius: 0 8px 8px 0; border-left: none; }

        /* 搜索框 */
        .search-box { display: flex; margin-bottom: 40px; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: 1px solid var(--border-color); }
        .search-box input { flex: 1; padding: 20px 25px; border: none; outline: none; font-size: 17px; font-family: 'Open Sans', sans-serif; color: var(--text-dark); }
        .search-box input::placeholder { color: #aaa; font-style: italic; font-family: 'Lora', serif; }
        .search-box button { background: var(--oxford-blue); color: white; border: none; padding: 0 45px; cursor: pointer; font-weight: 800; font-family: 'Playfair Display', serif; text-transform: uppercase; letter-spacing: 1.5px; transition: 0.3s; font-size: 15px; }
        .search-box button:hover { background: var(--oxford-gold); color: var(--oxford-blue); }

        /* 表格样式 */
        .data-table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.05); border-top: 4px solid var(--oxford-gold); }
        .data-table th { background: #fdfaf2; color: var(--oxford-blue); text-align: left; padding: 20px 25px; font-size: 14px; font-family: 'Playfair Display', serif; text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid var(--oxford-gold-light); }
        .data-table td { padding: 20px 25px; border-bottom: 1px solid #f0f0f0; vertical-align: middle; }
        .data-table tr:last-child td { border-bottom: none; }
        .data-table tr:hover { background: #f8fafc; }
        
        .word-text { font-family: 'Lora', serif; font-size: 18px; font-weight: 700; color: var(--oxford-blue); }
        .phonetic-text { color: #888; font-size: 14px; margin-top: 5px; font-style: italic; }
        .meaning-text { font-size: 15px; color: var(--text-dark); line-height: 1.5; }

        /* 按钮与状态 */
        .btn-add { color: var(--oxford-blue); background: transparent; border: 2px solid var(--oxford-blue); border-radius: 4px; padding: 8px 20px; cursor: pointer; font-size: 12px; font-weight: 800; transition: 0.3s; text-transform: uppercase; letter-spacing: 1px; font-family: 'Playfair Display', serif; }
        .btn-add:hover { background: var(--oxford-gold); border-color: var(--oxford-gold); color: var(--oxford-blue); }
        .btn-add:disabled { border-color: #cbd5e1; color: #cbd5e1; cursor: not-allowed; background: transparent; }
        
        .tag-status { background: #fdfaf2; color: var(--oxford-gold); border: 1px solid var(--oxford-gold); padding: 6px 15px; border-radius: 20px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; font-family: 'Playfair Display', serif; display: inline-block; }
    </style>
</head>
<body>

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
                        <li><a href="writing.php">Writing</a></li>
                        <li><a href="vocabulary.php" style="color:var(--oxford-gold)!important; font-weight: bold;">Vocabulary</a></li>
                    </ul>
                </li>
                <li class="dropdown">
                    <a href="#">Games ▾</a>
                    <ul class="dropdown-menu">
                        <li><a href="galgame/galgame/index.html">Story game</a></li>
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

    <div class="container">
        <div class="view-toggle">
            <a href="?view=search&search=<?= urlencode($query) ?>" class="toggle-btn first <?= $view === 'search' ? 'active' : '' ?>">Dictionary Search</a>
            <a href="?view=notebook" class="toggle-btn last <?= $view === 'notebook' ? 'active' : '' ?>">Personal Notebook</a>
        </div>

        <?php if ($view === 'search'): ?>
            <form class="search-box" method="GET">
                <input type="hidden" name="view" value="search">
                <input type="text" name="search" placeholder="Look up an English word to add to your collection..." value="<?= htmlspecialchars($query) ?>" autofocus>
                <button type="submit">Search</button>
            </form>
        <?php endif; ?>

        <table id="word-table" class="data-table">
            <thead>
                <tr>
                    <th width="25%">Vocabulary</th>
                    <th width="50%">Definition</th>
                    <th width="25%" style="text-align: center;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($display_data): foreach ($display_data as $row): ?>
                    <tr>
                        <td>
                            <div class="word-text"><?= htmlspecialchars($row['english_word']) ?></div>
                            <div class="phonetic-text"><?= htmlspecialchars($row['phonetic']) ?></div>
                        </td>
                        <td class="meaning-text"><?= htmlspecialchars($row['chinese_meaning']) ?></td>
                        <td style="text-align: center;">
                            <?php if ($view === 'search'): ?>
                                <button class="btn-add" onclick="handleAdd(this, <?= $row['word_id'] ?>)">Add to Notebook</button>
                            <?php else: ?>
                                <span class="tag-status"><?= htmlspecialchars($row['familiarity_level']) ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr>
                        <td colspan="3" style="text-align:center; color:#888; padding:80px; font-style:italic; font-family:'Lora', serif; font-size:16px;">
                            No academic vocabulary found. Please try another search or review your spelling.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
        /**
         * 手动点击加词逻辑 (AJAX)
         */
        function handleAdd(btn, wordId) {
            if (!btn || btn.disabled) return;
            
            const originalText = btn.innerText;
            btn.disabled = true;
            btn.innerText = "Adding...";
            
            const params = new URLSearchParams();
            params.append('action', 'add_to_notebook');
            params.append('word_id', wordId);

            fetch(window.location.href, { 
                method: 'POST', 
                body: params 
            })
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success' || res.status === 'exists') {
                    btn.innerText = "✓ Saved";
                    btn.style.borderColor = "var(--oxford-gold)";
                    btn.style.color = "var(--oxford-gold)";
                    btn.style.background = "transparent";
                } else {
                    btn.disabled = false;
                    btn.innerText = originalText;
                    alert(res.message || "An error occurred while adding the word.");
                }
            })
            .catch(() => { 
                btn.disabled = false; 
                btn.innerText = originalText; 
            });
        }
    </script>

    <script src="ai-agent.js?v=1.4"></script>
</body>
</html>