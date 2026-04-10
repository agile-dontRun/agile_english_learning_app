<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'db_connect.php';

$user_id = intval($_SESSION['user_id']);
$nickname = $_SESSION['nickname'] ?? 'Learner';

$category = isset($_GET['category']) ? $_GET['category'] : '';
$difficulty = isset($_GET['difficulty']) ? $_GET['difficulty'] : '';

$sql = "SELECT a.*, 
        (SELECT COUNT(*) FROM user_favorites WHERE article_id = a.article_id AND user_id = $user_id) as is_favorited
        FROM articles a 
        WHERE 1=1";
if ($category) $sql .= " AND a.category = '$category'";
if ($difficulty) $sql .= " AND a.difficulty = '$difficulty'";
$sql .= " ORDER BY a.created_at DESC";
$articles_result = $conn->query($sql);

$fav_sql = "SELECT a.article_id, a.title FROM articles a 
            JOIN user_favorites f ON a.article_id = f.article_id 
            WHERE f.user_id = $user_id ORDER BY f.created_at DESC";
$fav_result = $conn->query($fav_sql);

$selected_id = isset($_GET['id']) ? intval($_GET['id']) : null;
$current_article = null;
$annotations = [];
$is_favorited = false;

if ($selected_id) {
    $stmt = $conn->prepare("SELECT * FROM articles WHERE article_id = ?");
    $stmt->bind_param("i", $selected_id);
    $stmt->execute();
    $current_article = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    $fav_check = $conn->query("SELECT * FROM user_favorites WHERE user_id = $user_id AND article_id = $selected_id");
    $is_favorited = $fav_check->num_rows > 0;
    
    $anno_sql = "SELECT * FROM user_annotations WHERE user_id = $user_id AND article_id = $selected_id ORDER BY created_at DESC";
    $anno_result = $conn->query($anno_sql);
    while ($row = $anno_result->fetch_assoc()) {
        $annotations[] = $row;
    }
    
    $conn->query("INSERT INTO user_reading_progress (user_id, article_id, last_read_at) 
                  VALUES ($user_id, $selected_id, NOW()) 
                  ON DUPLICATE KEY UPDATE last_read_at = NOW()");
} else {
    if ($articles_result && $articles_result->num_rows > 0) {
        $first = $articles_result->fetch_assoc();
        $selected_id = $first['article_id'];
        $current_article = $first;
        $articles_result->data_seek(0);
        
        $fav_check = $conn->query("SELECT * FROM user_favorites WHERE user_id = $user_id AND article_id = $selected_id");
        $is_favorited = $fav_check->num_rows > 0;
        
        $anno_sql = "SELECT * FROM user_annotations WHERE user_id = $user_id AND article_id = $selected_id";
        $anno_result = $conn->query($anno_sql);
        while ($row = $anno_result->fetch_assoc()) {
            $annotations[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <title>文章花园 - Word Garden</title>
    <style>
        :root {
            --primary-green: #1b4332;
            --accent-green: #40916c;
            --soft-green-bg: #f2f7f5;
            --card-shadow: 0 10px 30px rgba(27, 67, 50, 0.08);
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: var(--soft-green-bg);
            margin: 0;
            color: #2d3436;
        }
        .nav-header {
            width: 100%; height: 70px; background: white;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 50px; box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            position: fixed; top: 0; z-index: 1000;
        }
        .nav-logo { font-size: 22px; font-weight: bold; color: var(--primary-green); text-decoration: none; }
        .nav-links { display: flex; gap: 20px; }
        .nav-links a { text-decoration: none; color: #666; font-size: 14px; padding: 5px 12px; border-radius: 8px; transition: 0.3s; }
        .nav-links a:hover, .nav-links a.active { color: var(--primary-green); background: #f0f7f4; }
        
        .hero-mini {
            background: linear-gradient(135deg, #081c15 0%, #1b4332 100%);
            color: white; padding: 110px 20px 70px; text-align: center;
        }
        .hero-mini h1 { margin: 0; font-size: 2.4rem; }
        
        .main-content { max-width: 1400px; margin: -50px auto 60px; padding: 0 20px; position: relative; z-index: 10; }
        .reading-layout { display: flex; gap: 30px; }
        
        .sidebar { width: 280px; flex-shrink: 0; background: white; border-radius: 24px; padding: 20px; box-shadow: var(--card-shadow); height: fit-content; }
        .sidebar h3 { color: var(--primary-green); font-size: 1.2rem; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid var(--soft-green-bg); }
        
        .filter-group { margin-bottom: 25px; }
        .filter-group select { width: 100%; padding: 10px; border: 1px solid #e0e0e0; border-radius: 12px; }
        
        .article-list, .favorite-list { list-style: none; padding: 0; margin: 15px 0 0; max-height: 300px; overflow-y: auto; }
        .article-list li, .favorite-list li { margin-bottom: 8px; }
        .article-list a, .favorite-list a { display: block; padding: 8px 12px; background: var(--soft-green-bg); border-radius: 12px; text-decoration: none; color: #2d3436; font-size: 13px; transition: 0.2s; }
        .article-list a:hover, .favorite-list a:hover { background: #e0f0e8; }
        .article-list a.active { background: var(--primary-green); color: white; }
        .favorite-list a { background: #fff8e7; border-left: 3px solid #ffb74d; }
        
        .article-panel { flex: 1; background: white; border-radius: 24px; padding: 35px 45px; box-shadow: var(--card-shadow); min-height: 500px; }
        .article-title { font-size: 1.8rem; color: var(--primary-green); margin-bottom: 20px; border-left: 4px solid var(--accent-green); padding-left: 20px; }
        .article-meta { display: flex; gap: 15px; margin-bottom: 25px; color: #8ba89a; font-size: 13px; flex-wrap: wrap; }
        .difficulty-badge { padding: 4px 12px; border-radius: 20px; }
        .difficulty-badge.beginner { background: #d4edda; color: #155724; }
        .difficulty-badge.intermediate { background: #fff3cd; color: #856404; }
        .difficulty-badge.advanced { background: #f8d7da; color: #721c24; }
        
        .article-content { line-height: 1.9; font-size: 1.05rem; color: #2d3e36; }
        .article-content p { margin-bottom: 1.2em; }
        
        .annotation-highlight {
            background-color: #fff3cd;
            cursor: pointer;
            border-radius: 4px;
            padding: 2px 0;
            transition: background 0.2s;
        }
        .annotation-highlight:hover { background-color: #ffe0a3; }
        
        .action-bar { display: flex; gap: 15px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0e0e0; }
        .action-btn {
            background: none;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            background: #e9ecef;
            transition: all 0.3s;
            color: #495057;
            font-size: 14px;
        }
        .action-btn.favorite.active {
            background: #e67e22;
            color: white;
        }
        .action-btn:hover { transform: translateY(-2px); }
        
        .annotation-popup, .view-annotation-popup {
            position: absolute;
            background: white;
            border: 2px solid var(--accent-green);
            border-radius: 16px;
            padding: 15px;
            width: 300px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            z-index: 1001;
        }
        .annotation-popup textarea { width: 100%; height: 80px; margin: 10px 0; padding: 8px; border: 1px solid #ddd; border-radius: 8px; resize: vertical; }
        .annotation-popup button, .view-annotation-popup button { background: var(--accent-green); color: white; border: none; padding: 6px 12px; border-radius: 8px; cursor: pointer; margin-right: 8px; }
        .delete-btn { background: #dc3545 !important; }
        
        .word-popup {
            position: absolute;
            background: white;
            border: 2px solid var(--accent-green);
            border-radius: 16px;
            padding: 12px;
            max-width: 300px;
            z-index: 1000;
            display: none;
        }
        
        /* 弹窗样式 */
        .resources-modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.7);
            overflow: auto;
        }
        .resources-modal-content {
            background-color: #fffef7;
            margin: 40px auto;
            padding: 0;
            width: 85%;
            max-width: 900px;
            border-radius: 28px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            animation: modalFadeIn 0.3s ease;
        }
        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .resources-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 30px;
            border-bottom: 2px solid var(--soft-green-bg);
            background: linear-gradient(135deg, #1b4332, #2d6a4f);
            border-radius: 28px 28px 0 0;
            color: white;
        }
        .resources-modal-header h2 { margin: 0; font-size: 1.6rem; }
        .resources-close { font-size: 32px; font-weight: bold; cursor: pointer; transition: 0.2s; line-height: 1; }
        .resources-close:hover { color: #ffb74d; }
        .resources-modal-body { padding: 25px 30px; max-height: 60vh; overflow-y: auto; }
        .resource-category { margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid #e0e0e0; }
        .resource-category:last-child { border-bottom: none; }
        .resource-category h3 { color: var(--primary-green); font-size: 1.2rem; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
        .resource-category ul { list-style: none; padding: 0; margin: 0; display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 10px; }
        .resource-category li { padding: 8px 12px; background: var(--soft-green-bg); border-radius: 12px; font-size: 14px; }
        .resource-category li a { color: var(--accent-green); text-decoration: none; font-weight: 600; margin-right: 8px; }
        .resource-category li a:hover { text-decoration: underline; color: var(--primary-green); }
        .resources-modal-footer { padding: 15px 30px 25px; text-align: center; border-top: 1px solid #e0e0e0; }
        .resources-close-btn { background: var(--accent-green); color: white; border: none; padding: 10px 30px; border-radius: 30px; cursor: pointer; font-size: 14px; transition: 0.2s; }
        .resources-close-btn:hover { background: var(--primary-green); transform: translateY(-2px); }
        
        .side-controls { position: fixed; bottom: 40px; right: 40px; z-index: 100; }
        .ai-assistant { display: flex; align-items: center; gap: 15px; }
        .chat-bubble { background: white; padding: 12px 20px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); font-size: 14px; }
        .ai-icon-circle { width: 60px; height: 60px; background: linear-gradient(135deg, var(--accent-green), var(--primary-green)); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; cursor: pointer; }
        
        @media (max-width: 900px) { .reading-layout { flex-direction: column; } .sidebar { width: 100%; } .article-panel { padding: 25px; } }
        @media (max-width: 700px) {
            .resources-modal-content { width: 95%; margin: 20px auto; }
            .resource-category ul { grid-template-columns: 1fr; }
            .resources-modal-header h2 { font-size: 1.2rem; }
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
        <a href="daily_talk.php">Daily Talk</a>
        <a href="reading.php" class="active">Reading</a>
        <a href="vocabulary.php">Vocabulary</a>
        <a href="calendar.php">Calendar</a>
        <a href="profile.php">Profile</a>
    </div>
</nav>

<header class="hero-mini">
    <h1>📖 文章花园</h1>
    <p>双击查词 · 选中文字右键批注 · 点击高亮文字查看批注</p>
</header>

<main class="main-content">
    <div class="reading-layout">
        <div class="sidebar">
            <h3>📚 筛选</h3>
            <div class="filter-group">
                <select id="category-filter">
                    <option value="">所有题材</option>
                    <option value="Psychology">心理学</option>
                    <option value="Science">科学</option>
                    <option value="Technology">科技</option>
                    <option value="Literature">文学</option>
                    <option value="Communication">沟通</option>
                    <option value="Health">健康</option>
                </select>
            </div>
            <div class="filter-group">
                <select id="difficulty-filter">
                    <option value="">所有难度</option>
                    <option value="beginner">🌱 入门</option>
                    <option value="intermediate">🌿 中级</option>
                    <option value="advanced">🌳 高级</option>
                </select>
            </div>
            <button id="apply-filter" style="width:100%; padding:10px; background:var(--accent-green); color:white; border:none; border-radius:12px; cursor:pointer;">应用筛选</button>
            
            <h3>📋 文章列表</h3>
            <ul class="article-list" id="article-list">
                <?php while ($row = $articles_result->fetch_assoc()): ?>
                    <li data-id="<?php echo $row['article_id']; ?>">
                        <a href="reading.php?id=<?php echo $row['article_id']; ?>&category=<?php echo urlencode($category); ?>&difficulty=<?php echo urlencode($difficulty); ?>" class="<?php echo ($selected_id == $row['article_id']) ? 'active' : ''; ?>"><?php echo htmlspecialchars($row['title']); ?></a>
                    </li>
                <?php endwhile; ?>
            </ul>
            
            <h3>❤️ 我的收藏</h3>
            <ul class="favorite-list" id="favorite-list">
                <?php while ($row = $fav_result->fetch_assoc()): ?>
                    <li data-id="<?php echo $row['article_id']; ?>">
                        <a href="reading.php?id=<?php echo $row['article_id']; ?>"><?php echo htmlspecialchars($row['title']); ?></a>
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>
        
        <div class="article-panel">
            <?php if ($current_article): ?>
                <div class="article-title"><?php echo htmlspecialchars($current_article['title']); ?></div>
                <div class="article-meta">
                    <span>✍️ <?php echo htmlspecialchars($current_article['author'] ?? 'Anonymous'); ?></span>
                    <span class="difficulty-badge <?php echo $current_article['difficulty']; ?>"><?php echo $current_article['difficulty']; ?></span>
                    <span>📂 <?php echo $current_article['category']; ?></span>
                </div>
                <div class="article-content" id="article-content">
                    <?php echo $current_article['content']; ?>
                </div>
                <div class="action-bar">
                    <button class="action-btn favorite <?php echo $is_favorited ? 'active' : ''; ?>" id="favorite-btn" data-id="<?php echo $current_article['article_id']; ?>">
                        <?php echo $is_favorited ? '❤️ 已收藏' : '❤️ 收藏'; ?>
                    </button>
                    <button class="action-btn resources-btn" id="resources-btn">📚 更多阅读资源</button>
                </div>
            <?php else: ?>
                <div style="text-align:center; padding:60px;">🌱 请从左侧选择一篇文章开始阅读～</div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- 更多阅读资源弹窗 -->
<div id="resources-modal" class="resources-modal">
    <div class="resources-modal-content">
        <div class="resources-modal-header">
            <h2>📚 更多阅读资源</h2>
            <span class="resources-close">&times;</span>
        </div>
        <div class="resources-modal-body">
            <div class="resource-category">
                <h3>📖 学术期刊与论文</h3>
                <ul>
                    <li><a href="https://www.nature.com/" target="_blank">Nature</a> - 国际顶级科学期刊</li>
                    <li><a href="https://www.science.org/" target="_blank">Science</a> - 美国科学促进会期刊</li>
                    <li><a href="https://www.plos.org/" target="_blank">PLOS ONE</a> - 开放获取科学期刊</li>
                    <li><a href="https://doaj.org/" target="_blank">DOAJ</a> - 开放获取期刊目录</li>
                    <li><a href="https://www.jstor.org/" target="_blank">JSTOR</a> - 学术期刊数据库</li>
                </ul>
            </div>
            <div class="resource-category">
                <h3>📚 免费电子书与经典文学</h3>
                <ul>
                    <li><a href="https://www.gutenberg.org/" target="_blank">Project Gutenberg</a> - 6万+免费公版书</li>
                    <li><a href="https://standardebooks.org/" target="_blank">Standard Ebooks</a> - 精美排版经典文学</li>
                    <li><a href="https://openlibrary.org/" target="_blank">Open Library</a> - 开放图书馆</li>
                    <li><a href="https://www.literature.org/" target="_blank">The Literature Network</a> - 文学资源库</li>
                </ul>
            </div>
            <div class="resource-category">
                <h3>🎓 大学公开课与教育资源</h3>
                <ul>
                    <li><a href="https://oyc.yale.edu/" target="_blank">Yale Open Courses</a> - 耶鲁大学公开课</li>
                    <li><a href="https://ocw.mit.edu/" target="_blank">MIT OpenCourseWare</a> - 麻省理工公开课</li>
                    <li><a href="https://www.coursera.org/" target="_blank">Coursera</a> - 在线课程平台</li>
                    <li><a href="https://www.edx.org/" target="_blank">edX</a> - 大学合作在线课程</li>
                    <li><a href="https://www.khanacademy.org/" target="_blank">Khan Academy</a> - 可汗学院</li>
                </ul>
            </div>
            <div class="resource-category">
                <h3>📰 优质新闻与杂志</h3>
                <ul>
                    <li><a href="https://www.newyorker.com/" target="_blank">The New Yorker</a> - 深度报道与文学</li>
                    <li><a href="https://www.theatlantic.com/" target="_blank">The Atlantic</a> - 思想与文化</li>
                    <li><a href="https://www.bbc.com/news" target="_blank">BBC News</a> - 国际新闻</li>
                    <li><a href="https://www.economist.com/" target="_blank">The Economist</a> - 经济与时事</li>
                </ul>
            </div>
            <div class="resource-category">
                <h3>🌱 英语学习专属资源</h3>
                <ul>
                    <li><a href="https://www.ted.com/" target="_blank">TED Talks</a> - 演讲与听力练习</li>
                    <li><a href="https://learningenglish.voanews.com/" target="_blank">VOA Learning English</a> - 慢速英语新闻</li>
                    <li><a href="https://www.bbc.co.uk/learningenglish/" target="_blank">BBC Learning English</a> - 英式英语学习</li>
                    <li><a href="https://www.linguahouse.com/" target="_blank">Linguahouse</a> - ESL学习材料</li>
                </ul>
            </div>
        </div>
        <div class="resources-modal-footer">
            <button class="resources-close-btn">关闭</button>
        </div>
    </div>
</div>

<aside class="side-controls">
    <div class="ai-assistant">
        <div class="chat-bubble">Hi <?php echo htmlspecialchars($nickname); ?>, 选中文字右键批注</div>
        <div class="ai-icon-circle">AI</div>
    </div>
</aside>

<script>
const articleId = <?php echo $selected_id ?: 0; ?>;
const existingAnnotations = <?php echo json_encode($annotations); ?>;

function escapeRegex(str) {
    return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

function applyAnnotations() {
    const contentDiv = document.getElementById('article-content');
    if (!contentDiv) return;
    let html = contentDiv.innerHTML;
    const sorted = [...existingAnnotations].sort((a, b) => b.selected_text.length - a.selected_text.length);
    sorted.forEach(anno => {
        const pattern = new RegExp(`(${escapeRegex(anno.selected_text)})`, 'g');
        html = html.replace(pattern, `<span class="annotation-highlight" data-annotation-id="${anno.annotation_id}" data-text="${escapeHtml(anno.selected_text)}" data-note="${escapeHtml(anno.note)}">$1</span>`);
    });
    contentDiv.innerHTML = html;
}

let annotationPopup = null;
function createAnnotationPopup() {
    const div = document.createElement('div');
    div.className = 'annotation-popup';
    div.style.display = 'none';
    document.body.appendChild(div);
    return div;
}
annotationPopup = createAnnotationPopup();

function showAnnotationPopup(selectedText, x, y) {
    annotationPopup.innerHTML = `
        <strong>📝 批注</strong>
        <div style="font-size:12px; color:#666; margin:8px 0; background:#f5f5f5; padding:6px; border-radius:6px;">"${escapeHtml(selectedText.substring(0, 80))}${selectedText.length > 80 ? '...' : ''}"</div>
        <textarea id="annotation-note" placeholder="写下你的想法..."></textarea>
        <button onclick="saveAnnotation('${selectedText.replace(/'/g, "\\'")}')">保存批注</button>
        <button onclick="closeAnnotationPopup()">取消</button>
    `;
    annotationPopup.style.left = x + 'px';
    annotationPopup.style.top = y + 'px';
    annotationPopup.style.display = 'block';
}

function saveAnnotation(selectedText) {
    const note = document.getElementById('annotation-note')?.value || '';
    fetch('save_annotation.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `article_id=${articleId}&selected_text=${encodeURIComponent(selectedText)}&note=${encodeURIComponent(note)}`
    }).then(response => response.json()).then(data => {
        if (data.success) {
            closeAnnotationPopup();
            location.reload();
        } else {
            alert('保存失败：' + (data.error || '未知错误'));
        }
    });
}

function closeAnnotationPopup() { if (annotationPopup) annotationPopup.style.display = 'none'; }

let viewPopup = null;
function createViewPopup() {
    const div = document.createElement('div');
    div.className = 'view-annotation-popup';
    div.style.display = 'none';
    document.body.appendChild(div);
    return div;
}
viewPopup = createViewPopup();

function showAnnotationDetail(text, note, annotationId, x, y) {
    viewPopup.innerHTML = `
        <strong>📝 批注内容</strong>
        <div style="font-size:12px; color:#666; margin:8px 0; background:#f5f5f5; padding:6px; border-radius:6px;">"${escapeHtml(text.substring(0, 100))}"</div>
        <div style="margin:10px 0; padding:8px; background:#f9f9f9; border-radius:8px;">${escapeHtml(note) || '<em>无批注内容</em>'}</div>
        <button onclick="deleteAnnotation(${annotationId})" class="delete-btn">删除批注</button>
        <button onclick="closeViewPopup()">关闭</button>
    `;
    viewPopup.style.left = x + 'px';
    viewPopup.style.top = y + 'px';
    viewPopup.style.display = 'block';
}

function deleteAnnotation(annotationId) {
    if (!confirm('确定要删除这条批注吗？')) return;
    fetch('delete_annotation.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `annotation_id=${annotationId}`
    }).then(response => response.json()).then(data => {
        if (data.success) { closeViewPopup(); location.reload(); }
        else alert('删除失败');
    });
}
function closeViewPopup() { if (viewPopup) viewPopup.style.display = 'none'; }

function updateFavoriteList(articleId, title, action) {
    const favList = document.getElementById('favorite-list');
    const existingItem = favList.querySelector(`li[data-id="${articleId}"]`);
    if (action === 'added' && !existingItem) {
        const newItem = document.createElement('li');
        newItem.setAttribute('data-id', articleId);
        newItem.innerHTML = `<a href="reading.php?id=${articleId}">${escapeHtml(title)}</a>`;
        favList.appendChild(newItem);
    } else if (action === 'removed' && existingItem) {
        existingItem.remove();
    }
}

function getArticleTitle(articleId) {
    const articleLink = document.querySelector(`.article-list li[data-id="${articleId}"] a`);
    return articleLink ? articleLink.innerText : '';
}

document.getElementById('favorite-btn')?.addEventListener('click', function() {
    const articleId = this.dataset.id;
    const btn = this;
    const title = getArticleTitle(articleId);
    fetch('favorite_article.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `article_id=${articleId}`
    }).then(response => response.json()).then(data => {
        if (data.success) {
            if (data.action === 'added') {
                btn.classList.add('active');
                btn.innerHTML = '❤️ 已收藏';
                updateFavoriteList(articleId, title, 'added');
            } else {
                btn.classList.remove('active');
                btn.innerHTML = '❤️ 收藏';
                updateFavoriteList(articleId, title, 'removed');
            }
        }
    });
});

let wordPopup = null;
function createWordPopup() {
    const div = document.createElement('div');
    div.className = 'word-popup';
    div.style.cssText = 'position:absolute; background:white; border:2px solid #40916c; border-radius:16px; padding:12px; max-width:300px; z-index:1000; display:none;';
    document.body.appendChild(div);
    return div;
}
wordPopup = createWordPopup();

async function lookupWord(word) {
    try {
        const response = await fetch(`vocabulary.php?view=search&search=${encodeURIComponent(word)}&ajax=1`);
        const text = await response.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(text, 'text/html');
        const row = doc.querySelector('.data-table tbody tr');
        if (row && row.cells[2]) return { definition: row.cells[2].innerText.trim() };
        return { definition: null };
    } catch (err) { return { definition: null }; }
}
function showWordPopup(word, x, y, definition) {
    wordPopup.innerHTML = `<strong>${word}</strong><br>${definition || '暂未收录'}`;
    wordPopup.style.left = (x + 10) + 'px';
    wordPopup.style.top = (y - 50) + 'px';
    wordPopup.style.display = 'block';
    setTimeout(() => wordPopup.style.display = 'none', 3000);
}

document.getElementById('apply-filter')?.addEventListener('click', () => {
    const category = document.getElementById('category-filter').value;
    const difficulty = document.getElementById('difficulty-filter').value;
    window.location.href = `reading.php?category=${category}&difficulty=${difficulty}`;
});

const modal = document.getElementById('resources-modal');
const openBtn = document.getElementById('resources-btn');
const closeBtn = document.querySelector('.resources-close');
const closeFooterBtn = document.querySelector('.resources-close-btn');
if (openBtn) {
    openBtn.onclick = function() { modal.style.display = 'block'; document.body.style.overflow = 'hidden'; };
}
function closeModal() { modal.style.display = 'none'; document.body.style.overflow = 'auto'; }
if (closeBtn) closeBtn.onclick = closeModal;
if (closeFooterBtn) closeFooterBtn.onclick = closeModal;
window.onclick = function(event) { if (event.target == modal) closeModal(); };

document.addEventListener('DOMContentLoaded', () => {
    applyAnnotations();
    const contentDiv = document.getElementById('article-content');
    if (contentDiv) {
        contentDiv.addEventListener('contextmenu', (e) => {
            if (e.target.closest('.annotation-highlight')) return;
            const selection = window.getSelection();
            const selectedText = selection.toString().trim();
            if (selectedText && selectedText.length > 0) {
                e.preventDefault();
                const rect = selection.getRangeAt(0).getBoundingClientRect();
                showAnnotationPopup(selectedText, rect.left + window.scrollX + 10, rect.top + window.scrollY - 80);
            }
        });
        contentDiv.addEventListener('click', (e) => {
            const highlight = e.target.closest('.annotation-highlight');
            if (highlight) {
                e.preventDefault();
                e.stopPropagation();
                const text = highlight.dataset.text;
                const note = highlight.dataset.note;
                const annoId = highlight.dataset.annotationId;
                const rect = highlight.getBoundingClientRect();
                showAnnotationDetail(text, note, annoId, rect.left + window.scrollX + 10, rect.top + window.scrollY - 60);
            } else closeViewPopup();
        });
        contentDiv.addEventListener('dblclick', async (e) => {
            const selection = window.getSelection();
            const selectedText = selection.toString().trim();
            if (selectedText && /^[a-zA-Z]+$/.test(selectedText)) {
                const word = selectedText.toLowerCase();
                const range = selection.getRangeAt(0);
                const rect = range.getBoundingClientRect();
                const { definition } = await lookupWord(word);
                showWordPopup(word, rect.left + window.scrollX, rect.top + window.scrollY, definition);
            }
        });
    }
});
document.addEventListener('click', (e) => {
    if (wordPopup && !wordPopup.contains(e.target)) wordPopup.style.display = 'none';
    if (viewPopup && !viewPopup.contains(e.target)) closeViewPopup();
});
</script>
</body>
</html>