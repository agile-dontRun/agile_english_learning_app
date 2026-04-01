<?php
require_once '../config/database.php';

header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true);
$name = $input['name'] ?? 'Unnamed outfit';
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
?>