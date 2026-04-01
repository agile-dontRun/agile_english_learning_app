<?php
require_once '../config/database.php';

header('Content-Type: application/json');

$stmt = $pdo->query("SELECT * FROM images WHERE is_enabled = 1 ORDER BY layer_code, sort_order, id DESC");
$images = $stmt->fetchAll();

foreach ($images as &$img) {
    $img['full_url'] = '/picture/' . $img['layer_code'] . '/' . basename($img['file_path']);
}

echo json_encode(['success' => true, 'data' => $images]);
?>