<?php
session_start();
include 'db_connect.php';

// 分页设置
$limit = 6;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// 获取当前页的文章列表（只取 id 和 title 用于分页）
$sql = "SELECT article_id, title FROM articles ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

$articles = [];
while ($row = $result->fetch_assoc()) {
    $articles[] = $row;
}

// 计算总页数
$total_res = $conn->query("SELECT COUNT(*) as total FROM articles");
$total_pages = ceil($total_res->fetch_assoc()['total'] / $limit);
?>

<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <title>美文欣赏 · Word Garden</title>
    <style>
        :root { --main-green: #a3d977; --dark-green: #5a8a31; --bg: #fdfdf2; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); margin: 0; }

        /* 顶部导航栏 */
        .top-bar {
            width: 100%; height: 70px; background: white; 
            display: flex; align-items: center; padding: 0 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05); position: fixed; top: 0; z-index: 100;
            box-sizing: border-box;
        }
        .back-home-btn {
            text-decoration: none; color: var(--dark-green); font-weight: bold;
            display: flex; align-items: center; gap: 8px; font-size: 18px;
            padding: 8px 15px; border-radius: 12px; transition: 0.3s;
        }
        .back-home-btn:hover { background: #f1f8e9; }

        .main-content { display: flex; flex-direction: column; align-items: center; padding-top: 100px; min-height: 100vh; }

        /* 文章卡片网格 */
        .article-grid { 
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; 
            width: 90%; max-width: 1100px; margin: 40px auto; 
        }

        .article-card { 
            background: white; border-radius: 25px; padding: 20px; cursor: pointer; 
            border: 2px solid transparent; box-shadow: 0 10px 25px rgba(0,0,0,0.05); 
            transition: all 0.3s; text-align: center;
        }
        .article-card:hover { border-color: var(--main-green); transform: translateY(-8px); }
        .article-title { font-size: 22px; font-weight: bold; color: #333; margin: 15px 0 5px; }
        .status-tag { 
            font-size: 12px; color: #fff; background: var(--main-green); 
            padding: 4px 12px; border-radius: 8px; display: inline-block; 
        }

        /* 弹窗模态框 */
        .modal { 
            display: none; position: fixed; z-index: 1000; left: 0; top: 0; 
            width: 100%; height: 100%; background: rgba(0,0,0,0.85); 
            justify-content: center; align-items: center; flex-direction: column;
        }
        .modal-content { 
            width: 80%; max-width: 800px; max-height: 80vh; 
            background: #fffcf5; border-radius: 32px; padding: 30px;
            position: relative; overflow-y: auto; box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }
        .close-btn { 
            position: absolute; top: 15px; right: 25px; 
            font-size: 32px; cursor: pointer; color: #8b8b6e; 
            transition: 0.2s;
        }
        .close-btn:hover { color: var(--dark-green); }
        .article-content h2 { color: var(--dark-green); margin-bottom: 20px; border-left: 4px solid var(--main-green); padding-left: 15px; }
        .article-content p { line-height: 1.8; font-size: 1rem; color: #3a4a2a; margin-bottom: 20px; }
        .article-content .author { font-style: italic; color: #7f9a6f; margin-top: 20px; text-align: right; }

        /* 分页 */
        .pagination { margin: 20px 0 60px; }
        .page-link { 
            text-decoration: none; padding: 10px 20px; border-radius: 12px; 
            background: white; color: #666; margin: 0 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); 
        }
        .page-link.active { background: var(--main-green); color: white; }

        /* 加载提示 */
        .loading { text-align: center; padding: 60px; color: #8aa87d; font-style: italic; }
    </style>
</head>
<body>

<div class="top-bar">
    <a href="home.php" class="back-home-btn">← 返回首页</a>
    <div style="flex:1; text-align:center; font-weight:bold; font-size:20px; color:var(--dark-green);">📖 美文花园</div>
</div>

<div class="main-content">
    <div class="article-grid">
        <?php if (empty($articles)): ?>
            <div class="loading">✨ 暂无美文，稍后回来看看～</div>
        <?php else: ?>
            <?php foreach ($articles as $article): ?>
                <div class="article-card" onclick="openArticle(<?php echo htmlspecialchars(json_encode($article), ENT_QUOTES, 'UTF-8'); ?>)">
                    <div class="article-title"><?php echo htmlspecialchars($article['title']); ?></div>
                    <div class="status-tag">🌿 点击阅读</div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="pagination">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?php echo $i; ?>" class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>
    </div>
</div>

<!-- 文章内容弹窗 -->
<div id="articleModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeArticle()">&times;</span>
        <div id="article-detail" class="article-content">
            <!-- 动态加载文章内容 -->
            <div class="loading">加载中...</div>
        </div>
    </div>
</div>

<script>
    let currentArticleId = null;

    function openArticle(article) {
        currentArticleId = article.article_id;
        const modal = document.getElementById('articleModal');
        const detailDiv = document.getElementById('article-detail');
        
        // 显示弹窗并展示加载状态
        modal.style.display = 'flex';
        detailDiv.innerHTML = '<div class="loading">🌱 加载文章内容...</div>';
        
        // 通过 AJAX 获取文章详情
        fetch(`get_article.php?id=${article.article_id}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    detailDiv.innerHTML = `<p style="color:red;">❌ ${data.error}</p>`;
                    return;
                }
                // 将换行符转为 <br> 或段落
                let contentHtml = data.content.replace(/\n\n/g, '</p><p>').replace(/\n/g, '<br>');
                detailDiv.innerHTML = `
                    <h2>${escapeHtml(data.title)}</h2>
                    <p>${contentHtml}</p>
                    <div class="author">—— ${escapeHtml(data.author || 'Word Garden')}</div>
                `;
            })
            .catch(err => {
                detailDiv.innerHTML = '<p style="color:red;">⚠️ 加载失败，请稍后再试</p>';
                console.error(err);
            });
    }

    function closeArticle() {
        document.getElementById('articleModal').style.display = 'none';
    }

    // 简单的防 XSS 工具函数
    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/[&<>]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        }).replace(/[\uD800-\uDBFF][\uDC00-\uDFFF]/g, function(c) {
            return c;
        });
    }

    // 点击模态框外部关闭（可选）
    window.onclick = function(event) {
        const modal = document.getElementById('articleModal');
        if (event.target === modal) {
            closeArticle();
        }
    }
</script>
</body>
</html>