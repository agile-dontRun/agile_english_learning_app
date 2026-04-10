<?php
session_start();
require_once '../../../db_connect.php';
require_once '../includes/functions.php';

header('Content-Type: application/json; charset=UTF-8');

$userId = dressUpCurrentUserId();
if ($userId === '0') {
    echo json_encode([
        'success' => false,
        'error' => 'Please log in first.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$imageId = isset($input['image_id']) ? (int)$input['image_id'] : 0;

$result = dressUpPurchaseItem($conn, $userId, $imageId);
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
