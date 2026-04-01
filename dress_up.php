<?php
/**
 * 强制导入并显示所有图片脚本
 * 会删除已存在的记录，重新导入所有图片
 */

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

$pictureDir = __DIR__ . '/picture/';
$layers = [
    'background', 'body', 'shoes', 'top', 'pants', 'dress', 'suit',
    'eye', 'eyebrows', 'nose', 'mouse', 'hair',
    'earings', 'glass', 'head', 'character'
];

$imported = 0;
$allImages = [];  // 存储所有扫描到的图片
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
        
        // 先删除已存在的记录
        $stmt = $pdo->prepare("DELETE FROM images WHERE layer_code = ? AND file_path = ?");
        $stmt->execute([$layer, $filePath]);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO images (layer_code, name, file_path, thumbnail_path) VALUES (?, ?, ?, ?)");
            $stmt->execute([$layer, $name, $filePath, $filePath]);
            $imported++;
            // 记录所有导入的图片
            $allImages[] = [
                'layer' => $layer,
                'file' => $file,
                'name' => $name,
                'path' => $filePath
            ];
        } catch(Exception $e) {
            $errors[] = "$layer/$file: " . $e->getMessage();
        }
    }
}

// 输出HTML页面，显示所有图片
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>图片导入结果</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            margin: 0;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #764ba2;
            margin-bottom: 20px;
        }
        .stats {
            background: #f0f0f0;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .success {
            color: #28a745;
            font-weight: bold;
        }
        .error {
            color: #dc3545;
        }
        .images-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 20px;
            max-height: 600px;
            overflow-y: auto;
        }
        .image-card {
            background: #f9f9f9;
            border-radius: 10px;
            padding: 10px;
            text-align: center;
            border: 1px solid #e0e0e0;
            transition: transform 0.2s;
        }
        .image-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .image-card img {
            width: 100%;
            height: 120px;
            object-fit: contain;
            border-radius: 5px;
            background: #fff;
        }
        .image-info {
            margin-top: 8px;
            font-size: 12px;
            color: #666;
        }
        .layer-badge {
            background: #764ba2;
            color: white;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 10px;
            display: inline-block;
            margin-top: 5px;
        }
        .no-images {
            text-align: center;
            color: #999;
            padding: 40px;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #764ba2;
            color: white;
            text-decoration: none;
            border-radius: 8px;
        }
        .back-link:hover {
            background: #5a3780;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>📸 图片导入结果</h1>
    
    <div class="stats">
        <p>✅ 共导入: <span class="success"><?php echo $imported; ?></span> 张图片</p>
        <?php if (!empty($errors)): ?>
        <p>❌ 导入失败: <span class="error"><?php echo count($errors); ?></span> 张图片</p>
        <div class="error">
            <?php foreach ($errors as $err): ?>
            <div>• <?php echo htmlspecialchars($err); ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if ($imported > 0): ?>
    <h2>✨ 所有图片列表 (<?php echo $imported; ?>张)</h2>
    <div class="images-grid">
        <?php foreach ($allImages as $img): ?>
        <div class="image-card">
            <img src="<?php echo htmlspecialchars($img['path']); ?>" 
                 alt="<?php echo htmlspecialchars($img['file']); ?>"
                 onerror="this.src='data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22100%22%20height%3D%22100%22%20viewBox%3D%220%200%20100%20100%22%3E%3Crect%20width%3D%22100%22%20height%3D%22100%22%20fill%3D%22%23ddd%22%2F%3E%3Ctext%20x%3D%2250%22%20y%3D%2255%22%20text-anchor%3D%22middle%22%20fill%3D%22%23999%22%3E%3F%3C%2Ftext%3E%3C%2Fsvg%3E'">
            <div class="image-info">
                <div><?php echo htmlspecialchars($img['name']); ?></div>
                <span class="layer-badge"><?php echo htmlspecialchars($img['layer']); ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="no-images">
        <p>📭 picture 目录中没有找到图片文件</p>
        <p>请确保 picture 目录下有对应的图层文件夹，并在文件夹中放入图片</p>
        <p>支持的图层: background, body, shoes, top, pants, dress, suit, eye, eyebrows, nose, mouse, hair, earings, glass, head, character</p>
    </div>
    <?php endif; ?>
    
    <a href="?action=game" class="back-link">← 返回游戏</a>
</div>
</body>
</html>