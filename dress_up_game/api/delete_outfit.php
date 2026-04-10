<?php
require_once '../../../db_connect.php';

header('Content-Type: application/json');
$id = $_POST['id'] ?? 0;

$stmt = $conn->prepare("DELETE FROM outfits WHERE id = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => $conn->error]);
    exit;
}

$id = (int) $id;
$stmt->bind_param("i", $id);
$success = $stmt->execute();
$stmt->close();

echo json_encode(['success' => $success]);
?>
