<?php
require_once '../config/database.php';

header('Content-Type: application/json');
$stmt = $pdo->query("SELECT * FROM outfits ORDER BY created_at DESC");
echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
?>