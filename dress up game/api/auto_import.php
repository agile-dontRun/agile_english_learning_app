<?php
require_once '../config/database.php';

header('Content-Type: application/json');

$pictureDir = __DIR__ . '/../picture/';
$layers = [
    'background', 'body', 'shoes', 'top', 'pants', 'dress', 'suit',
    'eye', 'eyebrows', 'nose', 'mouse', 'hair',
    'earings', 'glass', 'head', 'character'
];

$imported = 0;
$errors = [];

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
            $imported++;
        } catch(Exception $e) {
            $errors[] = "$layer/$file: " . $e->getMessage();
        }
    }
}

echo json_encode(['success' => true, 'imported' => $imported, 'errors' => $errors]);
?>