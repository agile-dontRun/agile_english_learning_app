<?php
require_once '../../../db_connect.php';

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
        
        $stmt = $conn->prepare("SELECT id FROM images WHERE layer_code = ? AND file_path = ?");
        if (!$stmt) {
            $errors[] = "$layer/$file: " . $conn->error;
            continue;
        }
        $stmt->bind_param("ss", $layer, $filePath);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($existing) continue;
        
        try {
            $stmt = $conn->prepare("INSERT INTO images (layer_code, name, file_path, thumbnail_path) VALUES (?, ?, ?, ?)");
            if (!$stmt) {
                throw new Exception($conn->error);
            }
            $stmt->bind_param("ssss", $layer, $name, $filePath, $filePath);
            $stmt->execute();
            $stmt->close();
            $imported++;
        } catch(Exception $e) {
            $errors[] = "$layer/$file: " . $e->getMessage();
        }
    }
}

echo json_encode(['success' => true, 'imported' => $imported, 'errors' => $errors]);
?>
