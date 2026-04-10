<?php
session_start();
require_once '../../../db_connect.php';
require_once '../includes/functions.php';

header('Content-Type: application/json; charset=UTF-8');

$defaultAvatarUrl = '../frontend/assets/player.jpg';
$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

if ($userId <= 0) {
    echo json_encode([
        'success' => true,
        'avatar_url' => $defaultAvatarUrl,
        'generated' => false,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$hasUserIdColumn = false;
$hasIsUsedColumn = false;
$hasAvatarColumn = dressUpEnsureAvatarColumn($conn);

$columnCheck = $conn->query("SHOW COLUMNS FROM outfits LIKE 'user_id'");
if ($columnCheck instanceof mysqli_result) {
    $hasUserIdColumn = $columnCheck->num_rows > 0;
    $columnCheck->free();
}

$columnCheck = $conn->query("SHOW COLUMNS FROM outfits LIKE 'is_used'");
if ($columnCheck instanceof mysqli_result) {
    $hasIsUsedColumn = $columnCheck->num_rows > 0;
    $columnCheck->free();
}

if (!$hasUserIdColumn || !$hasIsUsedColumn || !$hasAvatarColumn) {
    echo json_encode([
        'success' => true,
        'avatar_url' => $defaultAvatarUrl,
        'generated' => false,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$stmt = $conn->prepare("
    SELECT id, name, avatar_image_path
    FROM outfits
    WHERE user_id = ? AND is_used = 1
    ORDER BY id DESC
    LIMIT 1
");

if (!$stmt) {
    echo json_encode([
        'success' => true,
        'avatar_url' => $defaultAvatarUrl,
        'generated' => false,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$stmt->bind_param("i", $userId);
$stmt->execute();
$outfit = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$outfit) {
    echo json_encode([
        'success' => true,
        'avatar_url' => $defaultAvatarUrl,
        'generated' => false,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$avatarUrl = (string)($outfit['avatar_image_path'] ?? '');
$generated = false;

if ($avatarUrl === '') {
    $generatedPath = dressUpGenerateAvatarForOutfit(
        $conn,
        $userId,
        (int)$outfit['id'],
        (string)$outfit['name']
    );

    if ($generatedPath) {
        $avatarUrl = $generatedPath;
        $generated = true;
    }
}

if ($avatarUrl === '') {
    $avatarUrl = $defaultAvatarUrl;
}

echo json_encode([
    'success' => true,
    'avatar_url' => $avatarUrl,
    'generated' => $generated,
    'outfit_id' => (int)$outfit['id'],
    'outfit_name' => $outfit['name'],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
