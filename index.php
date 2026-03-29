<?php
// ==================== 数据库配置 ====================
$host = 'localhost';
$username = 'root';
$password = '200504230819';
$database = 'wardrobe_game';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("数据库连接失败: " . $e->getMessage());
}

// ==================== 处理请求 ====================
$action = $_GET['action'] ?? 'game';

// 获取所有图片
if ($action === 'get_all_images') {
    header('Content-Type: application/json');
    $stmt = $pdo->query("SELECT * FROM images WHERE is_enabled = 1 ORDER BY layer_code, sort_order, id DESC");
    $images = $stmt->fetchAll();
    foreach ($images as &$img) {
        $img['full_url'] = '/picture/' . $img['layer_code'] . '/' . basename($img['file_path']);
    }
    echo json_encode(['success' => true, 'data' => $images]);
    exit;
}

// 自动导入图片（页面加载时调用）
if ($action === 'auto_import') {
    header('Content-Type: application/json');
    
    $pictureDir = __DIR__ . '/picture/';
    $layers = [
        'background', 'body', 'shoes', 'top', 'pants', 'dress', 'suit',
        'eye', 'eyebrows', 'nose', 'mouse', 'hair',
        'earings', 'glass', 'head', 'character'
    ];
    
    $imported = 0;
    $errors = [];
    
    foreach ($layers as $layer) {
        $layerDir = $pictureDir . $layer . '/';
        if (!is_dir($layerDir)) {
            continue;
        }
        
        $files = scandir($layerDir);
        foreach ($files as $file) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'gif'])) {
                continue;
            }
            
            $name = pathinfo($file, PATHINFO_FILENAME);
            $filePath = '/picture/' . $layer . '/' . $file;
            
            $stmt = $pdo->prepare("SELECT id FROM images WHERE layer_code = ? AND file_path = ?");
            $stmt->execute([$layer, $filePath]);
            if ($stmt->fetch()) {
                continue;
            }
            
            try {
                $stmt = $pdo->prepare("INSERT INTO images (layer_code, name, file_path, thumbnail_path) VALUES (?, ?, ?, ?)");
                $stmt->execute([$layer, $name, $filePath, $filePath]);
                $imported++;
            } catch(Exception $e) {
                $errors[] = "$layer/$file: " . $e->getMessage();
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'imported' => $imported,
        'errors' => $errors
    ]);
    exit;
}

// 保存穿搭
if ($action === 'save_outfit') {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    $name = $input['name'] ?? '未命名穿搭';
    $items = $input['items'] ?? [];
    
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO outfits (name) VALUES (?)");
        $stmt->execute([$name]);
        $outfitId = $pdo->lastInsertId();
        
        $stmt = $pdo->prepare("INSERT INTO outfit_items (outfit_id, layer_code, image_id) VALUES (?, ?, ?)");
        foreach ($items as $layer => $imageId) {
            if ($imageId) {
                $stmt->execute([$outfitId, $layer, $imageId]);
                $pdo->prepare("UPDATE images SET use_count = use_count + 1 WHERE id = ?")->execute([$imageId]);
            }
        }
        $pdo->commit();
        echo json_encode(['success' => true, 'outfit_id' => $outfitId]);
    } catch(Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// 获取保存的穿搭列表
if ($action === 'get_outfits') {
    header('Content-Type: application/json');
    $stmt = $pdo->query("SELECT * FROM outfits ORDER BY created_at DESC");
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    exit;
}

// 获取单个穿搭详情
if ($action === 'get_outfit') {
    header('Content-Type: application/json');
    $id = $_GET['id'] ?? 0;
    $stmt = $pdo->prepare("SELECT layer_code, image_id FROM outfit_items WHERE outfit_id = ?");
    $stmt->execute([$id]);
    $items = $stmt->fetchAll();
    $outfit = [];
    foreach ($items as $item) {
        $outfit[$item['layer_code']] = $item['image_id'];
    }
    echo json_encode(['success' => true, 'data' => $outfit]);
    exit;
}

// 删除穿搭
if ($action === 'delete_outfit') {
    header('Content-Type: application/json');
    $id = $_POST['id'] ?? 0;
    $stmt = $pdo->prepare("DELETE FROM outfits WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}

// ==================== 显示游戏界面 ====================
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>幻梦衣橱 - 换装游戏</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .game-panel {
            background: rgba(255,255,255,0.95);
            border-radius: 30px;
            padding: 25px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        .game-area {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
        }
        
        .canvas-area {
            flex: 1.2;
            min-width: 400px;
            text-align: center;
        }
        
        canvas {
            background: #f0f0f0;
            background-image: 
                linear-gradient(45deg, #ddd 25%, transparent 25%),
                linear-gradient(-45deg, #ddd 25%, transparent 25%);
            background-size: 20px 20px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            width: 100%;
            height: auto;
            max-width: 550px;
            cursor: pointer;
        }
        
        .controls-area {
            flex: 1.8;
            min-width: 450px;
        }
        
        .main-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .main-nav-btn {
            padding: 12px 24px;
            border: none;
            border-radius: 40px;
            background: #f0f0f0;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 15px;
            font-weight: bold;
            color: #555;
        }
        
        .main-nav-btn.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 4px 15px rgba(102,126,234,0.4);
        }
        
        .main-nav-btn:hover {
            transform: translateY(-2px);
        }
        
        .sub-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 20px;
            padding: 10px 15px;
            background: #f5f5f5;
            border-radius: 50px;
            justify-content: center;
            min-height: 55px;
        }
        
        .sub-nav-btn {
            padding: 8px 20px;
            border: none;
            border-radius: 30px;
            background: white;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 13px;
            font-weight: 500;
            color: #666;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .sub-nav-btn.active {
            background: #764ba2;
            color: white;
        }
        
        .sub-nav-btn:hover {
            transform: translateY(-1px);
            background: #e0e0e0;
        }
        
        .images-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
            gap: 15px;
            max-height: 420px;
            overflow-y: auto;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 20px;
            min-height: 350px;
        }
        
        .image-card {
            background: white;
            border-radius: 15px;
            padding: 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            border: 2px solid transparent;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .image-card.selected {
            border-color: #764ba2;
            box-shadow: 0 0 0 3px rgba(118,75,162,0.3), 0 4px 12px rgba(0,0,0,0.15);
            transform: scale(1.02);
        }
        
        .image-card img {
            width: 100%;
            height: 100px;
            object-fit: contain;
            border-radius: 10px;
            background: #f5f5f5;
        }
        
        .image-name {
            font-size: 12px;
            margin-top: 8px;
            color: #666;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .toolbar {
            margin-top: 25px;
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        
        button {
            padding: 12px 28px;
            border: none;
            border-radius: 40px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            cursor: pointer;
            font-size: 15px;
            font-weight: bold;
            transition: all 0.2s;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }
        
        .saved-outfits {
            margin-top: 25px;
            padding: 20px;
            background: #f0f0f0;
            border-radius: 20px;
        }
        
        .saved-list {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 12px;
        }
        
        .saved-item {
            background: white;
            padding: 8px 18px;
            border-radius: 30px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
        }
        
        .saved-item:hover {
            background: #764ba2;
            color: white;
        }
        
        .delete-saved {
            background: #ff6b6b;
            padding: 2px 8px;
            border-radius: 50%;
            font-size: 12px;
            cursor: pointer;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        .stats {
            margin-top: 10px;
            padding: 8px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        
        h3 {
            margin-bottom: 12px;
            color: #764ba2;
        }
        
        .images-grid::-webkit-scrollbar {
            width: 8px;
        }
        
        .images-grid::-webkit-scrollbar-track {
            background: #e0e0e0;
            border-radius: 10px;
        }
        
        .images-grid::-webkit-scrollbar-thumb {
            background: #764ba2;
            border-radius: 10px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="game-panel">
        <div class="game-area">
            <div class="canvas-area">
                <canvas id="gameCanvas" width="550" height="650"></canvas>
            </div>
            <div class="controls-area">
                <div class="main-nav" id="mainNav"></div>
                <div class="sub-nav" id="subNav"></div>
                <div class="images-grid" id="imagesGrid">
                    <div class="loading">加载图片中...</div>
                </div>
            </div>
        </div>
        
        <div class="toolbar">
            <button id="saveBtn">💾 保存穿搭</button>
            <button id="resetBtn">🔄 重置全部</button>
        </div>
        
        <div class="saved-outfits">
            <h3>📁 我的穿搭</h3>
            <div class="saved-list" id="savedList"></div>
        </div>
        <div class="stats" id="stats"></div>
    </div>
</div>

<script>
    // ==================== 全局变量 ====================
    let currentOutfit = {};
    let allImages = {};
    let allImagesList = [];
    let savedOutfits = [];
    let currentMain = 'body';
    let currentSub = 'body';
    
    const canvas = document.getElementById('gameCanvas');
    const ctx = canvas.getContext('2d');
    
    const mainNavConfig = {
        body: { name: '💃 人体', layers: ['body'] },
        face: { name: '😊 五官', subLayers: ['eye', 'eyebrows', 'nose', 'mouse'] },
        hair: { name: '💇 头发', layers: ['hair'] },
        clothes: { name: '👔 衣服', subLayers: ['top', 'pants', 'dress', 'suit'] },
        shoes: { name: '👠 鞋子', layers: ['shoes'] },
        sunglasses: { name: '🕶️ 墨镜', layers: ['glass'] },
        headwear: { name: '👑 头饰', layers: ['head'] },
        character: { name: '🧚 角色整体', layers: ['character'] },
        background: { name: '🎨 背景', layers: ['background'] }
    };
    
    const subNames = {
        body: '💃 人体', eye: '👀 眼睛', eyebrows: '✏️ 眉毛', nose: '👃 鼻子',
        mouse: '👄 嘴巴', hair: '💇 头发', top: '👕 上衣', pants: '👖 裤子',
        dress: '👗 裙子', suit: '✨ 套装', shoes: '👠 鞋子', glass: '🕶️ 墨镜',
        head: '👑 头饰', character: '🧚 角色整体', background: '🎨 背景'
    };
    
    const layerOrder = [
        'background', 'body', 'shoes', 'top', 'pants', 'dress', 'suit',
        'eye', 'eyebrows', 'nose', 'mouse', 'hair',
        'character', 'glass', 'head'
    ];
    
    // ==================== 自动导入图片 ====================
    async function autoImport() {
        try {
            const res = await fetch('?action=auto_import');
            const data = await res.json();
            if (data.success && data.imported > 0) {
                console.log(`✅ 自动导入 ${data.imported} 张新图片`);
                const statsDiv = document.getElementById('stats');
                statsDiv.innerHTML = `📥 自动导入 ${data.imported} 张图片！`;
                statsDiv.style.color = '#28a745';
                setTimeout(() => {
                    statsDiv.style.color = '#666';
                }, 3000);
            }
            return data;
        } catch (error) {
            console.error('自动导入失败:', error);
            return null;
        }
    }
    
    // ==================== 加载所有图片 ====================
    async function loadAllImages() {
        try {
            // 先自动导入新图片
            await autoImport();
            
            // 再加载图片
            const res = await fetch('?action=get_all_images');
            const data = await res.json();
            if (data.success) {
                allImagesList = data.data;
                allImages = {};
                for (const img of allImagesList) {
                    if (!allImages[img.layer_code]) {
                        allImages[img.layer_code] = [];
                    }
                    allImages[img.layer_code].push(img);
                }
                console.log('✅ 加载图片完成:', Object.keys(allImages).length, '个图层, 共', allImagesList.length, '张图片');
                
                renderMainNav();
                renderSubNav();
                renderImagesGrid();
                renderCanvas();
                
                const statsDiv = document.getElementById('stats');
                statsDiv.innerHTML = `📸 共加载 ${allImagesList.length} 张图片`;
            }
        } catch (error) {
            console.error('加载图片失败:', error);
            document.getElementById('imagesGrid').innerHTML = '<div class="loading">加载失败，请检查数据库</div>';
        }
    }
    
    // ==================== 渲染主导航 ====================
    function renderMainNav() {
        const container = document.getElementById('mainNav');
        container.innerHTML = Object.entries(mainNavConfig).map(([key, config]) => `
            <button class="main-nav-btn ${currentMain === key ? 'active' : ''}" data-main="${key}">
                ${config.name}
            </button>
        `).join('');
        
        document.querySelectorAll('.main-nav-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                currentMain = btn.dataset.main;
                if (currentMain === 'face') {
                    currentSub = 'eye';
                } else if (currentMain === 'clothes') {
                    currentSub = 'top';
                } else {
                    const config = mainNavConfig[currentMain];
                    if (config.layers && config.layers.length > 0) {
                        currentSub = config.layers[0];
                    }
                }
                renderMainNav();
                renderSubNav();
                renderImagesGrid();
            });
        });
    }
    
    // ==================== 渲染子导航 ====================
    function renderSubNav() {
        const container = document.getElementById('subNav');
        const config = mainNavConfig[currentMain];
        
        let subLayers = [];
        if (config.subLayers) {
            subLayers = config.subLayers;
        } else if (config.layers) {
            subLayers = config.layers;
        }
        
        if (subLayers.length <= 1) {
            container.style.display = 'none';
            return;
        }
        
        container.style.display = 'flex';
        container.innerHTML = subLayers.map(layer => `
            <button class="sub-nav-btn ${currentSub === layer ? 'active' : ''}" data-layer="${layer}">
                ${subNames[layer] || layer}
            </button>
        `).join('');
        
        document.querySelectorAll('.sub-nav-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                currentSub = btn.dataset.layer;
                renderSubNav();
                renderImagesGrid();
            });
        });
    }
    
    // ==================== 获取当前图层 ====================
    function getCurrentLayer() {
        const config = mainNavConfig[currentMain];
        if (config.subLayers) {
            return currentSub;
        } else if (config.layers) {
            return config.layers[0];
        }
        return 'body';
    }
    
    // ==================== 渲染图片网格 ====================
    function renderImagesGrid() {
        const container = document.getElementById('imagesGrid');
        const currentLayer = getCurrentLayer();
        const images = allImages[currentLayer] || [];
        
        if (images.length === 0) {
            container.innerHTML = `<div class="loading">暂无图片，请将图片放入 picture/${currentLayer}/ 文件夹后刷新页面</div>`;
            return;
        }
        
        container.innerHTML = images.map(img => {
            const isSelected = currentOutfit[currentLayer] === img.id;
            return `
                <div class="image-card ${isSelected ? 'selected' : ''}" data-layer="${currentLayer}" data-id="${img.id}">
                    <img src="${img.full_url}" alt="${img.name}" onerror="this.src='data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22100%22%20height%3D%22100%22%20viewBox%3D%220%200%20100%20100%22%3E%3Crect%20width%3D%22100%22%20height%3D%22100%22%20fill%3D%22%23ddd%22%2F%3E%3Ctext%20x%3D%2250%22%20y%3D%2255%22%20text-anchor%3D%22middle%22%20fill%3D%22%23999%22%3E%3F%3C%2Ftext%3E%3C%2Fsvg%3E'">
                    <div class="image-name">${img.name}</div>
                </div>
            `;
        }).join('');
        
        document.querySelectorAll('.image-card').forEach(card => {
            card.addEventListener('click', () => {
                const layer = card.dataset.layer;
                const imageId = parseInt(card.dataset.id);
                if (currentOutfit[layer] === imageId) {
                    updateOutfit(layer, null);
                } else {
                    updateOutfit(layer, imageId);
                }
            });
        });
    }
    
    // ==================== 更新穿搭 ====================
    function updateOutfit(layer, imageId) {
        if (imageId === null) {
            delete currentOutfit[layer];
        } else {
            currentOutfit[layer] = imageId;
            
            if (layer === 'dress') {
                delete currentOutfit.top;
                delete currentOutfit.pants;
                delete currentOutfit.suit;
            }
            if (layer === 'suit') {
                delete currentOutfit.top;
                delete currentOutfit.pants;
                delete currentOutfit.dress;
            }
            if (layer === 'character') {
                delete currentOutfit.eye;
                delete currentOutfit.eyebrows;
                delete currentOutfit.nose;
                delete currentOutfit.mouse;
                delete currentOutfit.hair;
            }
            const faceParts = ['eye', 'eyebrows', 'nose', 'mouse', 'hair'];
            if (faceParts.includes(layer)) {
                delete currentOutfit.character;
            }
            if (layer === 'top' || layer === 'pants') {
                delete currentOutfit.dress;
                delete currentOutfit.suit;
            }
        }
        
        renderCanvas();
        renderImagesGrid();
    }
    
    // ==================== 渲染画布 ====================
    async function renderCanvas() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        for (const layer of layerOrder) {
            const imageId = currentOutfit[layer];
            if (!imageId) continue;
            
            const imgData = allImages[layer]?.find(i => i.id === imageId);
            if (!imgData) continue;
            
            const img = new Image();
            await new Promise((resolve) => {
                img.onload = () => {
                    const scale = Math.min(canvas.width / img.width, canvas.height / img.height);
                    const w = img.width * scale;
                    const h = img.height * scale;
                    const x = (canvas.width - w) / 2;
                    const y = (canvas.height - h) / 2;
                    ctx.drawImage(img, x, y, w, h);
                    resolve();
                };
                img.onerror = () => resolve();
                img.src = imgData.full_url;
            });
        }
    }
    
    // ==================== 保存穿搭 ====================
    async function saveOutfit() {
        const name = prompt('给这套穿搭起个名字吧', `穿搭${savedOutfits.length + 1}`);
        if (!name) return;
        
        const res = await fetch('?action=save_outfit', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name, items: currentOutfit })
        });
        
        const data = await res.json();
        if (data.success) {
            showMessage('保存成功！');
            loadSavedOutfits();
        } else {
            showMessage('保存失败：' + data.error, true);
        }
    }
    
    // ==================== 加载保存的穿搭 ====================
    async function loadSavedOutfits() {
        const res = await fetch('?action=get_outfits');
        const data = await res.json();
        if (data.success) {
            savedOutfits = data.data;
            renderSavedList();
        }
    }
    
    function renderSavedList() {
        const container = document.getElementById('savedList');
        if (savedOutfits.length === 0) {
            container.innerHTML = '<div style="color:#999;">暂无保存的穿搭</div>';
            return;
        }
        
        container.innerHTML = savedOutfits.map(outfit => `
            <div class="saved-item" data-id="${outfit.id}">
                ${outfit.name}
                <span class="delete-saved" data-id="${outfit.id}">✕</span>
            </div>
        `).join('');
        
        document.querySelectorAll('.saved-item').forEach(item => {
            item.addEventListener('click', async (e) => {
                if (e.target.classList.contains('delete-saved')) {
                    e.stopPropagation();
                    await fetch('?action=delete_outfit', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `id=${item.dataset.id}`
                    });
                    loadSavedOutfits();
                } else {
                    const res = await fetch(`?action=get_outfit&id=${item.dataset.id}`);
                    const data = await res.json();
                    if (data.success) {
                        currentOutfit = data.data;
                        renderCanvas();
                        renderImagesGrid();
                        showMessage('已加载穿搭：' + outfit.textContent.trim());
                    }
                }
            });
        });
    }
    
    function reset() {
        currentOutfit = {};
        renderCanvas();
        renderImagesGrid();
        showMessage('已重置所有装扮');
    }
    
    function showMessage(msg, isError = false) {
        const statsDiv = document.getElementById('stats');
        statsDiv.innerHTML = msg;
        statsDiv.style.color = isError ? '#dc3545' : '#28a745';
        setTimeout(() => {
            statsDiv.innerHTML = `📸 共加载 ${allImagesList.length} 张图片`;
            statsDiv.style.color = '#666';
        }, 3000);
    }
    
    // ==================== 初始化 ====================
    async function init() {
        await loadAllImages();
        loadSavedOutfits();
        
        document.getElementById('saveBtn').onclick = saveOutfit;
        document.getElementById('resetBtn').onclick = reset;
    }
    
    init();
</script>
</body>
</html>