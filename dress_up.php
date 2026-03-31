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