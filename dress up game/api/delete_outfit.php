<?php
require_once '../config/database.php';

header('Content-Type: application/json');
$id = $_POST['id'] ?? 0;

$stmt = $pdo->prepare("DELETE FROM outfits WHERE id = ?");
$stmt->execute([$id]);

echo json_encode(['success' => true]);
?>