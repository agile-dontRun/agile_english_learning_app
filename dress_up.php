<?php


$host = 'localhost';
$username = 'root';
$password = '200504230819';
$database = 'wardrobe_game';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}


$pictureDir = __DIR__ . '/picture/';
$layers = [
    'background', 'body', 'shoes', 'top', 'pants', 'dress', 'suit',
    'eye', 'eyebrows', 'nose', 'mouse', 'hair',
    'earings', 'glass', 'head', 'character'
];

$imported = 0;

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
         
        }
    }
}


$stmt = $pdo->query("SELECT * FROM images WHERE is_enabled = 1 ORDER BY layer_code, sort_order, id DESC");
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
    <title>Dress Up Game - Layer Rendering</title>
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
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .main-panel {
            background: rgba(255,255,255,0.95);
            border-radius: 30px;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
        }
        
        h1 {
            color: #764ba2;
            margin-bottom: 10px;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
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
            max-width: 550px;
            height: auto;
            margin: 0 auto;
            display: block;
        }
        
        .layer-info {
            margin-top: 30px;
            padding: 20px;
            background: #f5f5f5;
            border-radius: 20px;
        }
        
        .layer-info h3 {
            color: #764ba2;
            margin-bottom: 15px;
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
            color: #555;
        }
        
        .stats {
            margin-top: 20px;
            padding: 10px;
            background: #e8e8e8;
            border-radius: 15px;
            font-size: 14px;
            color: #666;
        }
        
        button {
            margin-top: 20px;
            padding: 10px 24px;
            border: none;
            border-radius: 30px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            cursor: pointer;
            font-size: 14px;
        }
        
        button:hover {
            transform: translateY(-2px);
        }
        
        .demo-images {
            margin-top: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
            max-height: 200px;
            overflow-y: auto;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 15px;
        }
        
        .demo-img {
            width: 60px;
            height: 60px;
            object-fit: contain;
            background: white;
            border-radius: 8px;
            padding: 5px;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="main-panel">
        <h1>🎨 Layer Rendering Demo</h1>
       
        
        <canvas id="gameCanvas" width="550" height="650"></canvas>
        
        <div class="layer-info">
            <h3>📐 Layer Order (Bottom to Top)</h3>
            <div class="layer-list" id="layerList"></div>
        </div>
        
        <div class="stats" id="stats">
            📸 Loaded <?php echo count($dbImages); ?> images
            <?php if ($imported > 0): ?>
            | ✅ Imported <?php echo $imported; ?> new
            <?php endif; ?>
        </div>
        
        <button id="randomBtn">🎲 Random Outfit</button>
        
        <div class="demo-images" id="demoImages"></div>
    </div>
</div>

<script>

    const layerOrder = [
        'background', 'body', 'shoes', 'top', 'pants', 'dress', 'suit',
        'eye', 'eyebrows', 'nose', 'mouse', 'hair',
        'character', 'glass', 'head'
    ];
    
    const layerNames = {
        'background': '🎨 Background',
        'body': '💃 Body',
        'shoes': '👠 Shoes',
        'top': '👕 Top',
        'pants': '👖 Pants',
        'dress': '👗 Dress',
        'suit': '✨ Suit',
        'eye': '👀 Eyes',
        'eyebrows': '✏️ Eyebrows',
        'nose': '👃 Nose',
        'mouse': '👄 Mouth',
        'hair': '💇 Hair',
        'character': '🧚 Character',
        'glass': '🕶️ Glasses',
        'head': '👑 Headwear'
    };
    

    const phpImages = <?php echo json_encode($groupedImages); ?>;
    

    let allImages = {};
    for (const [layer, images] of Object.entries(phpImages)) {
        allImages[layer] = images.map(img => ({
            id: img.id,
            name: img.name,
            full_url: img.full_url || '/picture/' + img.layer_code + '/' + (img.file_path ? img.file_path.split('/').pop() : '')
        }));
    }
    

    let currentOutfit = {};
    
    const canvas = document.getElementById('gameCanvas');
    const ctx = canvas.getContext('2d');
    

    function renderLayerList() {
        const container = document.getElementById('layerList');
        container.innerHTML = layerOrder.map((layer, index) => `
            <span class="layer-badge">${index}. ${layerNames[layer] || layer}</span>
        `).join('');
    }
   
    function renderDemoImages() {
        const container = document.getElementById('demoImages');
        let allDemoImages = [];
        
        for (const layer of layerOrder) {
            const images = allImages[layer] || [];
            for (const img of images.slice(0, 1)) {
                allDemoImages.push({ layer, ...img });
            }
        }
        
        if (allDemoImages.length === 0) {
            container.innerHTML = '<div style="color:#999;">No images found. Please add images to picture/ folder</div>';
            return;
        }
        
        container.innerHTML = allDemoImages.map(img => `
            <img class="demo-img" src="${img.full_url}" title="${layerNames[img.layer]} - ${img.name}" 
                 onerror="this.src='data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2260%22%20height%3D%2260%22%20viewBox%3D%220%200%2060%2060%22%3E%3Crect%20width%3D%2260%22%20height%3D%2260%22%20fill%3D%22%23ddd%22%2F%3E%3Ctext%20x%3D%2230%22%20y%3D%2235%22%20text-anchor%3D%22middle%22%20fill%3D%22%23999%22%3E%3F%3C%2Ftext%3E%3C%2Fsvg%3E'">
        `).join('');
    }
    
 
    function randomOutfit() {
        const newOutfit = {};
        for (const layer of layerOrder) {
            const images = allImages[layer] || [];
            if (images.length > 0) {
                const randomIndex = Math.floor(Math.random() * images.length);
                newOutfit[layer] = images[randomIndex].id;
            }
        }
        currentOutfit = newOutfit;
        renderCanvas();
        
        const statsDiv = document.getElementById('stats');
        statsDiv.innerHTML = `🎲 Random outfit generated | 📸 Loaded <?php echo count($dbImages); ?> images`;
        setTimeout(() => {
            statsDiv.innerHTML = `📸 Loaded <?php echo count($dbImages); ?> images`;
        }, 2000);
    }
    
    
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
    

    function initOutfit() {
        for (const layer of layerOrder) {
            const images = allImages[layer] || [];
            if (images.length > 0 && !currentOutfit[layer]) {
                currentOutfit[layer] = images[0].id;
            }
        }
        renderCanvas();
    }
    
   
    function init() {
        renderLayerList();
        renderDemoImages();
        initOutfit();
        
        document.getElementById('randomBtn').onclick = randomOutfit;
    }
    
    init();
</script>
</body>
</html>