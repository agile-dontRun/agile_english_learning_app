<?php
/**
 * 1. DATABASE CONNECTION
 */
$host = 'localhost';
$db   = 'english_learning_app';
$user = 'root';
$pass = ''; 
$charset = 'utf8mb4';

try {
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass, array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ));
} catch (\PDOException $e) {
    die("Connection Failed: " . $e->getMessage());
}

/**
 * 2. LIGHTWEIGHT AJAX HANDLER
 * Process requests and exit immediately to save resources
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_to_notebook') {
    header('Content-Type: application/json');
    $user_id = 1; 
    $word_id = intval($_POST['word_id']);
    
    try {
        // Find or Auto-Create Notebook 
        $stmt = $pdo->prepare("SELECT notebook_id FROM vocabulary_notebooks WHERE user_id = ? LIMIT 1");
        $stmt->execute(array($user_id));
        $notebook = $stmt->fetch();
        
        if (!$notebook) {
            $ins = $pdo->prepare("INSERT INTO vocabulary_notebooks (user_id, notebook_name, created_at) VALUES (?, 'My Vocabulary', NOW())");
            $ins->execute(array($user_id));
            $notebook_id = $pdo->lastInsertId();
        } else {
            $notebook_id = $notebook['notebook_id'];
        }
        
        // Avoid Duplicate Records 
        $check = $pdo->prepare("SELECT 1 FROM notebook_word_records WHERE notebook_id = ? AND word_id = ?");
        $check->execute(array($notebook_id, $word_id));
        
        if (!$check->fetch()) {
            $add = $pdo->prepare("INSERT INTO notebook_word_records (notebook_id, word_id, familiarity_level, added_at) VALUES (?, ?, 'New', NOW())");
            $add->execute(array($notebook_id, $word_id));
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'exists']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit; // Crucial: Stop processing to prevent page freeze
}

/**
 * 3. PAGE DATA LOADING
 */
$view = isset($_GET['view']) ? $_GET['view'] : 'search'; 
$query = isset($_GET['search']) ? trim($_GET['search']) : '';
$user_id = 1;
$display_data = array();

if ($view === 'search' && !empty($query)) {
    // Optimization: LIMIT 50 to prevent browser lag 
    $stmt = $pdo->prepare("SELECT * FROM words WHERE english_word LIKE ? ORDER BY english_word ASC LIMIT 50");
    $stmt->execute(array($query . '%'));
    $display_data = $stmt->fetchAll();
} elseif ($view === 'notebook') {
    $stmt = $pdo->prepare("
        SELECT w.*, nwr.familiarity_level 
        FROM notebook_word_records nwr
        JOIN words w ON nwr.word_id = w.word_id
        JOIN vocabulary_notebooks vn ON nwr.notebook_id = vn.notebook_id
        WHERE vn.user_id = ?
        ORDER BY nwr.added_at DESC
    ");
    $stmt->execute(array($user_id));
    $display_data = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Vocabulary Center</title>
    <style>
        /* UI Design per your requirement  */
        :root {
            --primary-green: #4CAF50; 
            --nav-bg: #90EE90;        
            --row-even: #E8F5E9;      
            --text-green: #2E7D32;
        }

        body { font-family: 'Segoe UI', Arial, sans-serif; margin: 0; background-color: #fdfdfd; }
        
        /* Navigation  */
        .main-nav { background-color: var(--nav-bg); display: flex; padding: 15px 20px 0; gap: 5px; }
        .nav-item { padding: 10px 20px; border-radius: 10px 10px 0 0; text-decoration: none; color: var(--text-green); font-weight: bold; background: #E0F2F1; font-size: 14px; }
        .nav-item.active { background-color: var(--primary-green); color: white; }
        
        .container { padding: 30px; max-width: 900px; margin: 0 auto; }
        
        /* View Toggles */
        .view-toggle { display: flex; justify-content: center; margin-bottom: 25px; }
        .toggle-btn { padding: 8px 25px; border: 2px solid var(--primary-green); text-decoration: none; color: var(--primary-green); font-weight: bold; }
        .toggle-btn.active { background: var(--primary-green); color: white; }
        .first { border-radius: 20px 0 0 20px; }
        .last { border-radius: 0 20px 20px 0; border-left: none; }

        /* Search Layout */
        .search-box { display: flex; margin-bottom: 25px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .search-box input { flex: 1; padding: 12px; border: 1px solid #ddd; outline: none; }
        .search-box button { background: var(--primary-green); color: white; border: none; padding: 0 30px; cursor: pointer; }

        /* Striped Table  */
        .data-table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; }
        .data-table th { background-color: var(--primary-green); color: white; text-align: left; padding: 15px; }
        .data-table td { padding: 15px; border-bottom: 1px solid #eee; }
        .data-table tr:nth-child(even) { background-color: var(--row-even); }
        
        /* Action Buttons */
        .btn-add { color: #2196F3; background: none; border: 1px solid #2196F3; border-radius: 4px; padding: 4px 12px; cursor: pointer; font-size: 12px; font-weight: bold; }
        .btn-add:disabled { border-color: #ccc; color: #999; cursor: not-allowed; }
        .tag-status { background: #81C784; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px; }
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
            <a href="?view=search&search=<?= urlencode($query) ?>" class="toggle-btn first <?= $view === 'search' ? 'active' : '' ?>">Search</a>
            <a href="?view=notebook" class="toggle-btn last <?= $view === 'notebook' ? 'active' : '' ?>">My Notebook</a>
        </div>

        <?php if ($view === 'search'): ?>
            <form class="search-box" method="GET">
                <input type="hidden" name="view" value="search">
                <input type="text" name="search" placeholder="Type first letters..." value="<?= htmlspecialchars($query) ?>" autofocus>
                <button type="submit">SEARCH</button>
            </form>
        <?php else: ?>
            <div style="color: #5B9BD5; text-align: center; margin: 20px 0; font-style: italic; font-size: 18px;">Vocabulary Notebook</div>
        <?php endif; ?>

        <table class="data-table">
            <thead>
                <tr>
                    <th>WORD</th>
                    <th>PHONETIC</th>
                    <th>MEANING</th>
                    <th>ACTION</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($display_data)): ?>
                    <?php foreach ($display_data as $row): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($row['english_word']) ?></strong></td>
                        <td style="color: #666;"><?= htmlspecialchars($row['phonetic']) ?></td>
                        <td><?= htmlspecialchars($row['chinese_meaning']) ?></td>
                        <td>
                            <?php if ($view === 'search'): ?>
                                <button type="button" class="btn-add" onclick="handleAdd(this, <?= $row['word_id'] ?>)">+ Add</button>
                            <?php else: ?>
                                <span class="tag-status"><?= htmlspecialchars($row['familiarity_level']) ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="text-align:center; padding: 30px; color: #999;">No results found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
    /**
     * Optimized Add function using Fetch
     */
    function handleAdd(btn, wordId) {
        // Disable button to prevent multiple clicks
        btn.disabled = true;
        btn.innerText = "Adding...";

        const data = new URLSearchParams();
        data.append('action', 'add_to_notebook');
        data.append('word_id', wordId);

        fetch('vocabulary.php', {
            method: 'POST',
            body: data,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(res => {
            if (res.status === 'success' || res.status === 'exists') {
                btn.innerText = "✓ Added";
                btn.style.color = "#4CAF50";
                btn.style.borderColor = "#4CAF50";
            } else {
                alert("Error: " + res.message);
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
