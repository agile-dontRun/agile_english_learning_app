<?php
/**
 * Vocabulary Center - Core Logic & AI Silent Integration
 * This file handles both the UI and the backend API for word management.
 */

// --- Database Configuration ---
$host = 'localhost';
$db = 'english_learning_app';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

// Phase 1: Initialize Database Connection
try {
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (\PDOException $e) {
    // Better to fail early if the DB is down
    die("Database Connection Failed: " . $e->getMessage());
}

/**
 * Phase 2: AJAX API Endpoint
 * Handles adding words. Supports manual clicks (ID-based) 
 * and AI-driven background requests (Text-based).
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_to_notebook') {
    header('Content-Type: application/json');
    
    $user_id = 1; // Temporary: hardcoded for demo purposes
    $word_id = isset($_POST['word_id']) ? intval($_POST['word_id']) : 0;
    $word_text = isset($_POST['word_text']) ? trim($_POST['word_text']) : '';

    try {
        // A. Silent Mode Logic: 
        // If the AI agent sends just the word text, we need to find its ID in our dictionary.
        if ($word_id <= 0 && !empty($word_text)) {
            $stmt = $pdo->prepare("SELECT word_id FROM words WHERE english_word = ? LIMIT 1");
            $stmt->execute([$word_text]);
            $word_found = $stmt->fetch();
            
            if ($word_found) {
                $word_id = $word_found['word_id'];
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Word not found in dictionary']);
                exit;
            }
        }

        // B. Personal Notebook Check:
        // Ensure the user actually has a notebook. If not, create a default one.
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

        // C. Duplicate Prevention:
        // Don't add the same word twice to the same notebook.
        $check = $pdo->prepare("SELECT 1 FROM notebook_word_records WHERE notebook_id = ? AND word_id = ?");
        $check->execute([$notebook_id, $word_id]);

        if (!$check->fetch()) {
            $add = $pdo->prepare("INSERT INTO notebook_word_records (notebook_id, word_id, familiarity_level, added_at) VALUES (?, ?, 'New', NOW())");
            $add->execute([$notebook_id, $word_id]);
            echo json_encode(['status' => 'success', 'info' => 'Word added successfully']);
        } else {
            echo json_encode(['status' => 'exists']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
    }
    exit;
}

/**
 * Phase 3: View Logic (Search vs. Notebook)
 * Determines which dataset to pull based on the current active tab.
 */
$view = $_GET['view'] ?? 'search';
$query = trim($_GET['search'] ?? '');
$display_data = [];

if ($view === 'search' && !empty($query)) {
    // Perform a fuzzy search on the dictionary
    $stmt = $pdo->prepare("SELECT * FROM words WHERE english_word LIKE ? ORDER BY english_word ASC LIMIT 50");
    $stmt->execute([$query . '%']);
    $display_data = $stmt->fetchAll();
} elseif ($view === 'notebook') {
    // Fetch user's saved words with their current learning status
    $stmt = $pdo->prepare("SELECT w.*, nwr.familiarity_level FROM notebook_word_records nwr 
                            JOIN words w ON nwr.word_id = w.word_id 
                            JOIN vocabulary_notebooks vn ON nwr.notebook_id = vn.notebook_id 
                            WHERE vn.user_id = ? ORDER BY nwr.added_at DESC");
    $stmt->execute([1]); // Hardcoded user 1
    $display_data = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vocabulary Center</title>
    <style>
        /* General Theme Constants */
        :root { --primary: #2563EB; --bg: #F8FAFC; --text: #1E293B; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; margin: 0; background: var(--bg); color: var(--text); padding-bottom: 80px; }
        
        /* Navigation Styling */
        .main-nav { background: #fff; border-bottom: 1px solid #E2E8F0; display: flex; padding: 15px 40px; gap: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .nav-item { text-decoration: none; color: #64748B; font-weight: 500; font-size: 14px; padding: 8px 12px; border-radius: 6px; transition: 0.2s; }
        .nav-item:hover { background: #F1F5F9; color: var(--primary); }
        .nav-item.active { color: var(--primary); background: #EFF6FF; }

        .container { padding: 40px; max-width: 1000px; margin: 0 auto; }
        
        /* View Toggle (Switcher) */
        .view-toggle { display: flex; justify-content: center; margin-bottom: 30px; }
        .toggle-btn { padding: 10px 30px; border: 2px solid var(--primary); text-decoration: none; color: var(--primary); font-weight: bold; transition: 0.3s; }
        .toggle-btn.active { background: var(--primary); color: white; }
        .first { border-radius: 12px 0 0 12px; }
        .last { border-radius: 0 12px 12px 0; border-left: none; }

        /* Search Interface */
        .search-box { display: flex; margin-bottom: 30px; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .search-box input { flex: 1; padding: 15px 20px; border: none; outline: none; font-size: 16px; }
        .search-box button { background: var(--primary); color: white; border: none; padding: 0 40px; cursor: pointer; font-weight: bold; }

        /* Results Table */
        .data-table { width: 100%; border-collapse: collapse; background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); }
        .data-table th { background: #F1F5F9; color: #475569; text-align: left; padding: 18px; font-size: 13px; text-transform: uppercase; letter-spacing: 0.05em; }
        .data-table td { padding: 18px; border-bottom: 1px solid #F1F5F9; }
        .data-table tr:hover { background: #F8FAFC; }

        /* UI Elements */
        .btn-add { color: var(--primary); background: white; border: 1.5px solid var(--primary); border-radius: 8px; padding: 6px 16px; cursor: pointer; font-size: 12px; font-weight: bold; transition: 0.2s; }
        .btn-add:hover { background: var(--primary); color: white; }
        .btn-add:disabled { border-color: #94A3B8; color: #94A3B8; cursor: default; }
        .tag-status { background: #DCFCE7; color: #15803D; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    </style>
</head>
<body>

    <nav class="main-nav">
        <a href="#" class="nav-item">TED TALK</a>
        <a href="#" class="nav-item">IELTS</a>
        <a href="?view=search" class="nav-item active">VOCABULARY</a>
        <a href="#" class="nav-item">CALENDAR</a>
    </nav>

    <div class="container">
        <div class="view-toggle">
            <a href="?view=search&search=<?= urlencode($query) ?>" class="toggle-btn first <?= $view === 'search' ? 'active' : '' ?>">Search Dictionary</a>
            <a href="?view=notebook" class="toggle-btn last <?= $view === 'notebook' ? 'active' : '' ?>">My Notebook</a>
        </div>

        <?php if ($view === 'search'): ?>
            <form class="search-box" method="GET">
                <input type="hidden" name="view" value="search">
                <input type="text" name="search" placeholder="Enter English word..." value="<?= htmlspecialchars($query) ?>" autofocus>
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
                <?php if ($display_data): foreach ($display_data as $row): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($row['english_word']) ?></strong></td>
                        <td><small style="color:#64748B"><?= htmlspecialchars($row['phonetic']) ?></small></td>
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
                        <td colspan="4" style="text-align:center; color:#94A3B8; padding:60px;">
                            No results found. Start by searching above!
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
        /**
         * AJAX Word Addition
         * Triggered when a user clicks the "Add" button in search results.
         */
        function handleAdd(btn, wordId) {
            if (!btn || btn.disabled) return;
            
            // UI Feedback: disable button while processing
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
                    // Update UI to show completion
                    btn.innerText = "✓ Added";
                    btn.style.borderColor = "#10B981";
                    btn.style.color = "#10B981";
                } else {
                    // Re-enable on error so user can try again
                    btn.disabled = false;
                    btn.innerText = "+ Add";
                    alert(res.message || "Something went wrong. Please try again.");
                }
            })
            .catch(() => { 
                btn.disabled = false; 
                btn.innerText = "+ Add"; 
            });
        }
    </script>

    <script src="ai-agent.js?v=1.3"></script>
</body>
</html>
