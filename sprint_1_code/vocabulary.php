<?php
session_start();
// 1. Login Security Check
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

require_once 'db_connect.php'; 

// User context
$user_id = intval($_SESSION['user_id']); 
$nickname = isset($_SESSION['nickname']) ? $_SESSION['nickname'] : 'Learner';

/**
 * 2. AJAX Handler (Add to Notebook)
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_to_notebook') {
    header('Content-Type: application/json');
    $word_id = intval($_POST['word_id']);
    
    // Check/Create Notebook
    $nb_stmt = $conn->prepare("SELECT notebook_id FROM vocabulary_notebooks WHERE user_id = ? LIMIT 1");
    $nb_stmt->bind_param("i", $user_id);
    $nb_stmt->execute();
    $nb_res = $nb_stmt->get_result();
    $notebook = $nb_res->fetch_assoc();
    
    if (!$notebook) {
        $ins_nb = $conn->prepare("INSERT INTO vocabulary_notebooks (user_id, notebook_name, created_at) VALUES (?, 'My Vocabulary', NOW())");
        $ins_nb->bind_param("i", $user_id);
        $ins_nb->execute();
        $notebook_id = $conn->insert_id;
    } else {
        $notebook_id = $notebook['notebook_id'];
    }
    
    // Check Duplicate
    $check_stmt = $conn->prepare("SELECT 1 FROM notebook_word_records WHERE notebook_id = ? AND word_id = ?");
    $check_stmt->bind_param("ii", $notebook_id, $word_id);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows === 0) {
        $add_stmt = $conn->prepare("INSERT INTO notebook_word_records (notebook_id, word_id, familiarity_level, added_at) VALUES (?, ?, 'new', NOW())");
        $add_stmt->bind_param("ii", $notebook_id, $word_id);
        $add_stmt->execute();
        echo json_encode(array('status' => 'success'));
    } else {
        echo json_encode(array('status' => 'exists'));
    }
    exit; 
}

/**
 * 3. Page Data Loading
 */
$view = isset($_GET['view']) ? $_GET['view'] : 'search'; 
$query = isset($_GET['search']) ? trim($_GET['search']) : '';
$display_data = array();

if ($view === 'search' && !empty($query)) {
    $search_param = $query . '%';
    $stmt = $conn->prepare("SELECT * FROM words WHERE english_word LIKE ? ORDER BY english_word ASC LIMIT 50");
    $stmt->bind_param("s", $search_param);
    $stmt->execute();
    $display_data = $stmt->get_result();
} elseif ($view === 'notebook') {
    $stmt = $conn->prepare("
        SELECT w.*, nwr.familiarity_level 
        FROM notebook_word_records nwr
        JOIN words w ON nwr.word_id = w.word_id
        JOIN vocabulary_notebooks vn ON nwr.notebook_id = vn.notebook_id
        WHERE vn.user_id = ?
        ORDER BY nwr.added_at DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $display_data = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Vocabulary - Word Garden</title>
    <style>
        /* ===== Premium Green Theme System ===== */
        :root {
            --primary-green: #1b4332;
            --accent-green: #40916c;
            --soft-green-bg: #f2f7f5;
            --card-shadow: 0 10px 30px rgba(27, 67, 50, 0.08);
            --card-shadow-hover: 0 20px 40px rgba(27, 67, 50, 0.15);
            --text-main: #2d3436;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: var(--soft-green-bg);
            margin: 0;
            color: var(--text-main);
        }

        /* ===== 1. Navigation Header ===== */
        .nav-header {
            width: 100%;
            height: 70px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 50px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            position: fixed;
            top: 0;
            z-index: 1000;
            box-sizing: border-box;
        }
        .nav-logo { font-size: 22px; font-weight: bold; color: var(--primary-green); text-decoration: none; }
        .nav-links { display: flex; gap: 20px; }
        .nav-links a {
            text-decoration: none;
            color: #666;
            font-size: 14px;
            font-weight: 500;
            padding: 5px 12px;
            border-radius: 8px;
            transition: 0.3s;
        }
        .nav-links a:hover, .nav-links a.active { color: var(--primary-green); background: #f0f7f4; }

        /* ===== 2. Hero Banner ===== */
        .hero-mini {
            background: linear-gradient(135deg, #081c15 0%, #1b4332 100%);
            color: white;
            padding: 110px 20px 70px;
            text-align: center;
        }
        .hero-mini h1 { margin: 0; font-size: 2.4rem; letter-spacing: 1px; }
        .hero-mini p { opacity: 0.8; margin-top: 10px; font-weight: 300; text-transform: uppercase; letter-spacing: 2px; }

        /* ===== 3. Main Content Container ===== */
        .main-content {
            max-width: 1100px;
            margin: -50px auto 60px;
            padding: 0 20px;
            position: relative;
            z-index: 10;
        }

        /* Tabs Styling */
        .view-tabs { 
            display: flex; 
            justify-content: center; 
            gap: 15px; 
            margin-bottom: 30px; 
        }
        .tab-link {
            padding: 12px 25px;
            background: white;
            color: var(--primary-green);
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            box-shadow: var(--card-shadow);
            transition: 0.3s;
        }
        .tab-link.active {
            background: var(--primary-green);
            color: white;
        }
        .tab-link:hover:not(.active) { transform: translateY(-3px); background: #f0f7f4; }

        /* Search Section */
        .search-area {
            background: white;
            padding: 25px;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
        }
        .search-area input {
            flex: 1;
            padding: 15px 20px;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            font-size: 16px;
            outline: none;
        }
        .search-area input:focus { border-color: var(--accent-green); }
        .search-area button {
            padding: 0 35px;
            background: var(--primary-green);
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            transition: 0.3s;
        }
        .search-area button:hover { background: var(--accent-green); }

        /* Table Styling */
        .table-container {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-table th {
            background: #f8fbfa;
            color: var(--primary-green);
            padding: 20px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid var(--soft-green-bg);
        }
        .data-table td {
            padding: 20px;
            border-bottom: 1px solid #f0f0f0;
        }
        .data-table tr:hover { background: #fcfdfd; }

        /* Component Styles */
        .btn-add {
            background: var(--accent-green);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 50px;
            cursor: pointer;
            font-weight: 600;
            transition: 0.3s;
        }
        .btn-add:disabled { background: #d1d8d5; cursor: not-allowed; }
        .badge {
            background: #e9f5ef;
            color: var(--accent-green);
            padding: 5px 15px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        /* ===== 4. Side AI Assistant ===== */
        .side-controls { position: fixed; bottom: 40px; right: 40px; z-index: 100; }
        .ai-assistant { display: flex; align-items: center; gap: 15px; }
        .chat-bubble {
            background: white; color: var(--primary-green);
            padding: 12px 20px; border-radius: 20px 20px 5px 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05); font-size: 14px;
            border: 1px solid #eef5f2;
        }
        .ai-icon-circle {
            width: 60px; height: 60px;
            background: linear-gradient(135deg, var(--accent-green), var(--primary-green));
            border-radius: 50%; display: flex; justify-content: center; align-items: center;
            box-shadow: 0 8px 20px rgba(27, 67, 50, 0.2); color: white; font-weight: bold;
        }
    </style>
</head>
<body>

    <nav class="nav-header">
        <a href="home.php" class="nav-logo">Word Garden</a>
        <div class="nav-links">
            <a href="home.php">Home</a>
            <a href="TED.php">TED Talk</a>
            <a href="ielts.php">IELTS</a>
            <a href="daily_decryption.php">Daily Talk</a>
            <a href="vocabulary.php" class="active">Vocabulary</a>
            <a href="calendar.php">Calendar</a>
            <a href="profile.php">Profile</a>
        </div>
    </nav>

    <header class="hero-mini">
        <h1>Vocabulary</h1>
        <p>Cultivate your digital word garden</p>
    </header>

    <main class="main-content">
        <div class="view-tabs">
            <a href="?view=search&search=<?= urlencode($query) ?>" class="tab-link <?= $view === 'search' ? 'active' : '' ?>">Dictionary Search</a>
            <a href="?view=notebook" class="tab-link <?= $view === 'notebook' ? 'active' : '' ?>">My Notebook</a>
        </div>

        <?php if ($view === 'search'): ?>
            <form class="search-area" method="GET">
                <input type="hidden" name="view" value="search">
                <input type="text" name="search" placeholder="Enter word or first letters..." value="<?= htmlspecialchars($query) ?>" autofocus>
                <button type="submit">SEARCH</button>
            </form>
        <?php endif; ?>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>WORD</th>
                        <th>PHONETIC</th>
                        <th>MEANING</th>
                        <th style="text-align: center;">STATUS / ACTION</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($display_data && $display_data->num_rows > 0): ?>
                        <?php while ($row = $display_data->fetch_assoc()): ?>
                        <tr>
                            <td><strong style="color: var(--primary-green);"><?= htmlspecialchars($row['english_word']) ?></strong></td>
                            <td style="color: #666;">[<?= htmlspecialchars($row['phonetic']) ?>]</td>
                            <td style="font-size: 0.95rem;"><?= htmlspecialchars($row['chinese_meaning']) ?></td>
                            <td style="text-align: center;">
                                <?php if ($view === 'search'): ?>
                                    <button type="button" class="btn-add" onclick="submitAdd(this, <?= $row['word_id'] ?>)">+ Add</button>
                                <?php else: ?>
                                    <span class="badge"><?= htmlspecialchars($row['familiarity_level']) ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align:center; padding: 60px; color: #999;">No vocabulary data found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <aside class="side-controls">
        <div class="ai-assistant">
            <div class="chat-bubble">Hi <?= htmlspecialchars($nickname) ?>, search and grow your words!</div>
            <div class="ai-icon-circle">AI</div>
        </div>
    </aside>

    <script>
    /**
     * AJAX Logic
     */
    function submitAdd(btn, wordId) {
        btn.disabled = true;
        btn.innerText = "Adding...";

        const params = new URLSearchParams();
        params.append('action', 'add_to_notebook');
        params.append('word_id', wordId);

        fetch('vocabulary.php', {
            method: 'POST',
            body: params,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(res => {
            if (res.status === 'success' || res.status === 'exists') {
                btn.innerText = "Added";
                btn.style.backgroundColor = "#1b4332";
            } else {
                alert("Error adding word.");
                btn.disabled = false;
                btn.innerText = "+ Add";
            }
        })
        .catch(err => {
            console.error(err);
            btn.disabled = false;
            btn.innerText = "+ Add";
        });
    }
    </script>
</body>
</html>