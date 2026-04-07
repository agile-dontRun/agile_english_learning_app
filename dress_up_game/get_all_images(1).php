<?php
require_once '../../../db_connect.php';

header('Content-Type: application/json');

$images = [];
$result = $conn->query("SELECT * FROM images WHERE is_enabled = 1 ORDER BY layer_code, sort_order, id DESC");
if ($result instanceof mysqli_result) {
    $images = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
}

foreach ($images as &$img) {
    $img['full_url'] = '/picture/' . $img['layer_code'] . '/' . basename($img['file_path']);
}

echo json_encode(['success' => true, 'data' => $images]);
?>
