<?php
session_start();
require_once '../../../db_connect.php';
require_once '../includes/functions.php';

header('Content-Type: application/json; charset=UTF-8');

$input = json_decode(file_get_contents('php://input'), true);
$outfitId = isset($input['outfit_id']) ? (int)$input['outfit_id'] : 0;
$userId = dressUpCurrentUserId();

if ($userId === '0' || $outfitId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid request.']);
    exit;
}

function ensureIsUsedColumn(mysqli $conn): bool {
    $columnCheck = $conn->query("SHOW COLUMNS FROM outfits LIKE 'is_used'");
    if ($columnCheck instanceof mysqli_result) {
        $exists = $columnCheck->num_rows > 0;
        $columnCheck->free();
        if ($exists) {
            return true;
        }
    }

    return (bool)$conn->query("ALTER TABLE outfits ADD COLUMN is_used BOOLEAN NOT NULL DEFAULT FALSE");
}

$hasUserIdColumn = false;
$userColumnCheck = $conn->query("SHOW COLUMNS FROM outfits LIKE 'user_id'");
if ($userColumnCheck instanceof mysqli_result) {
    $hasUserIdColumn = $userColumnCheck->num_rows > 0;
    $userColumnCheck->free();
}

if (!$hasUserIdColumn || !ensureIsUsedColumn($conn)) {
    echo json_encode(['success' => false, 'error' => 'Outfit activation is not available.']);
    exit;
}

$stmt = $conn->prepare("SELECT id, name FROM outfits WHERE id = ? AND user_id = ? LIMIT 1");
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Failed to prepare activation query.']);
    exit;
}
$stmt->bind_param("is", $outfitId, $userId);
$stmt->execute();
$outfit = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$outfit) {
    echo json_encode(['success' => false, 'error' => 'Outfit not found.']);
    exit;
}

try {
    $conn->begin_transaction();

    $stmt = $conn->prepare("UPDATE outfits SET is_used = 0 WHERE user_id = ?");
    if (!$stmt) {
        throw new Exception($conn->error);
    }
    $stmt->bind_param("s", $userId);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("UPDATE outfits SET is_used = 1 WHERE id = ? AND user_id = ?");
    if (!$stmt) {
        throw new Exception($conn->error);
    }
    $stmt->bind_param("is", $outfitId, $userId);
    $stmt->execute();
    $stmt->close();

    $conn->commit();

    $avatarImagePath = dressUpGenerateAvatarForOutfit(
        $conn,
        (int)$userId,
        (int)$outfit['id'],
        (string)$outfit['name']
    );

    echo json_encode([
        'success' => true,
        'outfit_id' => (int)$outfit['id'],
        'outfit_name' => $outfit['name'],
        'avatar_image_path' => $avatarImagePath
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
