<?php
$host = 'localhost';
$username = 'root';
$password = '200504230819';
$database = 'wardrobe_game';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$pictureDir = __DIR__ . '/picture/';
$layers = [
    'background', 'body', 'shoes', 'top', 'pants', 'dress', 'suit',
    'eye', 'eyebrows', 'nose', 'mouse', 'hair',
    'earings', 'glass', 'head', 'character'
];

foreach ($layers as $layer) {
    $layerDir = $pictureDir . $layer . '/';
    if (!is_dir($layerDir)) continue;
    
    $files = scandir($layerDir);
    foreach ($files as $file) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'gif'])) continue;
        
        $name = pathinfo($file, PATHINFO_FILENAME);
        $filePath = '/picture/' . $layer . '/' . $file;
        
        $stmt = $pdo->prepare("SELECT id FROM images WHERE layer_code = ? AND file_path = ?");
        $stmt->execute([$layer, $filePath]);
        if ($stmt->fetch()) continue;
        
        try {
            $stmt = $pdo->prepare("INSERT INTO images (layer_code, name, file_path, thumbnail_path) VALUES (?, ?, ?, ?)");
            $stmt->execute([$layer, $name, $filePath, $filePath]);
        } catch(Exception $e) {}
    }
}

$stmt = $pdo->query("SELECT * FROM images WHERE is_enabled = 1");
$dbImages = $stmt->fetchAll();

$groupedImages = [];
foreach ($dbImages as $img) {
    $groupedImages[$img['layer_code']][] = $img;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dress Up Game</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            font-family: 'Segoe UI', sans-serif;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        .main-panel {
            background: rgba(255,255,255,0.95);
            border-radius: 30px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        canvas {
            background: #f0f0f0;
            background-image: linear-gradient(45deg, #ddd 25%, transparent 25%),
                              linear-gradient(-45deg, #ddd 25%, transparent 25%);
            background-size: 20px 20px;
            border-radius: 20px;
            width: 100%;
            max-width: 550px;
            height: auto;
            display: block;
            margin: 0 auto;
        }
        .layer-info {
            margin-top: 30px;
            padding: 20px;
            background: #f5f5f5;
            border-radius: 20px;
        }
        .layer-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: center;
        }
        .layer-badge {
            background: #e0e0e0;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
        }
        button {
            margin-top: 20px;
            padding: 12px 28px;
            border: none;
            border-radius: 30px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
        }
        button:hover { transform: translateY(-2px); }
        .stats {
            margin-top: 20px;
            padding: 10px;
            background: #e8e8e8;
            border-radius: 15px;
            font-size: 14px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="main-panel">
        <h1>🎨 Dress Up Game</h1>
        <canvas id="gameCanvas" width="550" height="650"></canvas>
        
        <div class="layer-info">
            <h3>📐 Layer Order (Bottom to Top)</h3>
            <div class="layer-list" id="layerList"></div>
        </div>
        
        <div class="stats" id="stats"></div>
        <button id="randomBtn">🎲 Random Outfit</button>
    </div>
</div>

<script>
    const layerOrder = [
        'background', 'body', 'shoes', 'top', 'pants', 'dress', 'suit',
        'eye', 'eyebrows', 'nose', 'mouse', 'hair',
        'character', 'glass', 'head'
    ];
    
    const layerNames = {
        'background': 'Background', 'body': 'Body', 'shoes': 'Shoes',
        'top': 'Top', 'pants': 'Pants', 'dress': 'Dress', 'suit': 'Suit',
        'eye': 'Eyes', 'eyebrows': 'Eyebrows', 'nose': 'Nose',
        'mouse': 'Mouth', 'hair': 'Hair', 'character': 'Character',
        'glass': 'Glasses', 'head': 'Headwear'
    };
    
    const phpImages = <?php echo json_encode($groupedImages); ?>;
    
    let allImages = {};
    for (const [layer, images] of Object.entries(phpImages)) {
        allImages[layer] = images.map(img => ({
            id: img.id,
            name: img.name,
            url: img.full_url || '/picture/' + img.layer_code + '/' + (img.file_path ? img.file_path.split('/').pop() : '')
        }));
    }
    
    let currentOutfit = {};
    const canvas = document.getElementById('gameCanvas');
    const ctx = canvas.getContext('2d');
    
    function renderLayerList() {
        const container = document.getElementById('layerList');
        container.innerHTML = layerOrder.map((layer, index) => 
            `<span class="layer-badge">${index}. ${layerNames[layer]}</span>`
        ).join('');
    }
    
    // ========== 您的冲突规则 ==========
    function applyConflictRules(outfit) {
        const result = { ...outfit };
        
        // 规则1: 有裙子 → 清除上衣、裤子、套装
        if (result.dress) {
            delete result.top;
            delete result.pants;
            delete result.suit;
        }
        // 规则2: 有套装 → 清除上衣、裤子、裙子
        if (result.suit) {
            delete result.top;
            delete result.pants;
            delete result.dress;
        }
        // 规则3: 有角色整体 → 清除五官
        if (result.character) {
            delete result.eye;
            delete result.eyebrows;
            delete result.nose;
            delete result.mouse;
            delete result.hair;
        }
        
        return result;
    }
    
    // 随机选择一个图片
    function getRandomImage(layer) {
        const images = allImages[layer];
        if (!images || images.length === 0) return null;
        return images[Math.floor(Math.random() * images.length)].id;
    }
    
    function randomOutfit() {
        const newOutfit = {};
        
        // 随机选择模式: 0=上衣+裤子, 1=裙子, 2=套装, 3=角色整体
        const mode = Math.floor(Math.random() * 4);
        
        // 基础图层（总是随机选）
        const baseLayers = ['background', 'body', 'shoes', 'glass', 'head'];
        for (const layer of baseLayers) {
            const id = getRandomImage(layer);
            if (id) newOutfit[layer] = id;
        }
        
        // 根据模式处理衣服和五官
        if (mode === 0) {
            // 上衣+裤子模式：随机选上衣和裤子
            const topId = getRandomImage('top');
            const pantsId = getRandomImage('pants');
            if (topId) newOutfit.top = topId;
            if (pantsId) newOutfit.pants = pantsId;
            
            // 随机选五官
            const faceLayers = ['eye', 'eyebrows', 'nose', 'mouse', 'hair'];
            for (const layer of faceLayers) {
                const id = getRandomImage(layer);
                if (id) newOutfit[layer] = id;
            }
        }
        else if (mode === 1) {
            // 裙子模式：选裙子
            const dressId = getRandomImage('dress');
            if (dressId) newOutfit.dress = dressId;
            
            // 随机选五官
            const faceLayers = ['eye', 'eyebrows', 'nose', 'mouse', 'hair'];
            for (const layer of faceLayers) {
                const id = getRandomImage(layer);
                if (id) newOutfit[layer] = id;
            }
        }
        else if (mode === 2) {
            // 套装模式：选套装
            const suitId = getRandomImage('suit');
            if (suitId) newOutfit.suit = suitId;
            
            // 随机选五官
            const faceLayers = ['eye', 'eyebrows', 'nose', 'mouse', 'hair'];
            for (const layer of faceLayers) {
                const id = getRandomImage(layer);
                if (id) newOutfit[layer] = id;
            }
        }
        else if (mode === 3) {
            // 角色整体模式：选角色整体，不选五官
            const characterId = getRandomImage('character');
            if (characterId) newOutfit.character = characterId;
        }
        
        currentOutfit = newOutfit;
        
        const modeNames = ['👕 Top + Pants 👖', '👗 Dress', '✨ Suit', '🧚 Character'];
        document.getElementById('stats').innerHTML = `🎲 ${modeNames[mode]} | Total: <?php echo count($dbImages); ?> images`;
        
        renderCanvas();
    }
    
    async function renderCanvas() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        // 应用冲突规则后渲染
        const outfitToRender = applyConflictRules(currentOutfit);
        
        for (const layer of layerOrder) {
            const imageId = outfitToRender[layer];
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
                img.src = imgData.url;
            });
        }
    }
    
    function init() {
        renderLayerList();
        randomOutfit();
        document.getElementById('randomBtn').onclick = randomOutfit;
    }
    
    init();
</script>
</body>
</html>
