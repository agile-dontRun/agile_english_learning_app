<?php
require_once '../../../db_connect.php';

header('Content-Type: application/json');
$id = $_GET['id'] ?? 0;

$id = (int) $id;
$items = [];
$stmt = $conn->prepare("SELECT layer_code, image_id FROM outfit_items WHERE outfit_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$outfit = [];
foreach ($items as $item) {
    $outfit[$item['layer_code']] = $item['image_id'];
}

echo json_encode(['success' => true, 'data' => $outfit]);
?>
