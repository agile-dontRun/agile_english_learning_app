<?php
require_once '../config/database.php';

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
?>