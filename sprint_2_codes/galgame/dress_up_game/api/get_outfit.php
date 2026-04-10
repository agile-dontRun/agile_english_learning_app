<?php
session_start();
require_once '../../../db_connect.php';

header('Content-Type: application/json');
$id = $_GET['id'] ?? 0;
$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

$id = (int) $id;
$items = [];
$hasUserIdColumn = false;
$columnCheck = $conn->query("SHOW COLUMNS FROM outfits LIKE 'user_id'");
if ($columnCheck instanceof mysqli_result) {
    $hasUserIdColumn = $columnCheck->num_rows > 0;
    $columnCheck->free();
}

if ($hasUserIdColumn) {
    $stmt = $conn->prepare("
        SELECT oi.layer_code, oi.image_id
        FROM outfit_items oi
        INNER JOIN outfits o ON o.id = oi.outfit_id
        WHERE oi.outfit_id = ? AND o.user_id = ?
    ");
    if ($stmt) {
        $stmt->bind_param("ii", $id, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $items = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
} else {
    $stmt = $conn->prepare("SELECT layer_code, image_id FROM outfit_items WHERE outfit_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $items = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

$outfit = [];
foreach ($items as $item) {
    $outfit[$item['layer_code']] = $item['image_id'];
}

echo json_encode(['success' => true, 'data' => $outfit]);
?>
