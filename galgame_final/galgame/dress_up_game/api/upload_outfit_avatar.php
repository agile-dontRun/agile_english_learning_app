<?php
session_start();
require_once '../../../db_connect.php';
require_once '../includes/functions.php';

header('Content-Type: application/json; charset=UTF-8');

$input = json_decode(file_get_contents('php://input'), true);
$outfitId = isset($input['outfit_id']) ? (int)$input['outfit_id'] : 0;
$imageData = isset($input['image_data']) ? (string)$input['image_data'] : '';
$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

if ($userId <= 0 || $outfitId <= 0 || $imageData === '') {
    echo json_encode(['success' => false, 'error' => 'Invalid upload request.']);
    exit;
}

$stmt = $conn->prepare("SELECT id, name FROM outfits WHERE id = ? AND user_id = ? LIMIT 1");
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Failed to verify outfit.']);
    exit;
}
$stmt->bind_param("ii", $outfitId, $userId);
$stmt->execute();
$outfit = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$outfit) {
    echo json_encode(['success' => false, 'error' => 'Outfit not found.']);
    exit;
}

if (!preg_match('#^data:image/png;base64,#', $imageData)) {
    echo json_encode(['success' => false, 'error' => 'Only PNG avatar uploads are supported.']);
    exit;
}

$rawBase64 = substr($imageData, strlen('data:image/png;base64,'));
$binary = base64_decode(str_replace(' ', '+', $rawBase64), true);
if ($binary === false) {
    echo json_encode(['success' => false, 'error' => 'Failed to decode avatar image.']);
    exit;
}

$avatarDir = dressUpGeneratedAvatarDirAbsolute();
if (!is_dir($avatarDir) && !@mkdir($avatarDir, 0777, true) && !is_dir($avatarDir)) {
    echo json_encode(['success' => false, 'error' => 'Failed to create avatar directory.']);
    exit;
}

$filename = $userId . '_' . $outfitId . '_' . dressUpSlugify((string)$outfit['name']) . '.png';
$absolutePath = $avatarDir . '/' . $filename;
$webPath = dressUpGeneratedAvatarWebBase() . '/' . rawurlencode($filename);

dressUpCleanupUserAvatarFiles($conn, $userId, $outfitId);

if (@file_put_contents($absolutePath, $binary) === false) {
    echo json_encode(['success' => false, 'error' => 'Failed to save avatar image.']);
    exit;
}

dressUpWriteOutfitAvatarPath($conn, $outfitId, $webPath);

echo json_encode([
    'success' => true,
    'avatar_image_path' => $webPath,
    'outfit_id' => $outfitId,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
