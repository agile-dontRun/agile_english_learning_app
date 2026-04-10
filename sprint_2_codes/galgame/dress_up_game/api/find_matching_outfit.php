<?php
session_start();
require_once '../../../db_connect.php';
require_once '../includes/functions.php';

header('Content-Type: application/json; charset=UTF-8');

$input = json_decode(file_get_contents('php://input'), true);
$items = $input['items'] ?? [];
$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

if ($userId <= 0 || !is_array($items)) {
    echo json_encode(['success' => false, 'error' => 'Invalid request.']);
    exit;
}

$normalized = [];
foreach ($items as $layer => $imageId) {
    $imageId = (int)$imageId;
    if ($imageId > 0) {
        $normalized[$layer] = $imageId;
    }
}
$normalized = applyConflictRules($normalized);
ksort($normalized);

$hasUserIdColumn = false;
$userColumnCheck = $conn->query("SHOW COLUMNS FROM outfits LIKE 'user_id'");
if ($userColumnCheck instanceof mysqli_result) {
    $hasUserIdColumn = $userColumnCheck->num_rows > 0;
    $userColumnCheck->free();
}

if (!$hasUserIdColumn) {
    echo json_encode(['success' => true, 'match' => null], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$stmt = $conn->prepare("SELECT id, name, is_used FROM outfits WHERE user_id = ? ORDER BY id DESC");
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Failed to load outfits.']);
    exit;
}
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$match = null;
while ($row = $result->fetch_assoc()) {
    $outfitItems = dressUpGetOutfitItems($conn, (int)$row['id']);
    $candidate = [];
    foreach ($outfitItems as $layerCode => $item) {
        $candidate[$layerCode] = (int)$item['image_id'];
    }
    $candidate = applyConflictRules($candidate);
    ksort($candidate);

    if ($candidate === $normalized) {
        $match = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'is_used' => isset($row['is_used']) ? (int)$row['is_used'] : 0,
        ];
        break;
    }
}
$stmt->close();

echo json_encode([
    'success' => true,
    'match' => $match
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
