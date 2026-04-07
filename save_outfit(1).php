<?php
session_start();
require_once '../../../db_connect.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true);
$name = $input['name'] ?? 'Unnamed outfit';
$items = $input['items'] ?? [];
$markAsUsed = !empty($input['is_used']);

function ensureOutfitFlagColumn(mysqli $conn, string $columnName, string $definition): bool {
    $columnCheck = $conn->query("SHOW COLUMNS FROM outfits LIKE '{$columnName}'");
    if ($columnCheck instanceof mysqli_result) {
        $exists = $columnCheck->num_rows > 0;
        $columnCheck->free();
        if ($exists) {
            return true;
        }
    }

    return (bool) $conn->query("ALTER TABLE outfits ADD COLUMN {$columnName} {$definition}");
}

try {
    $userId = dressUpCurrentUserId();
    $hasUserIdColumn = false;
    $hasIsUsedColumn = ensureOutfitFlagColumn($conn, 'is_used', 'BOOLEAN NOT NULL DEFAULT FALSE');
    $columnCheck = $conn->query("SHOW COLUMNS FROM outfits LIKE 'user_id'");
    if ($columnCheck instanceof mysqli_result) {
        $hasUserIdColumn = $columnCheck->num_rows > 0;
        $columnCheck->free();
    }

    $missingUnlocks = dressUpValidateItemOwnership($conn, $userId, is_array($items) ? $items : []);
    if ($missingUnlocks) {
        throw new Exception('This look contains items that have not been unlocked yet.');
    }

    $conn->begin_transaction();

    if ($markAsUsed && $hasIsUsedColumn) {
        if ($hasUserIdColumn) {
            $resetStmt = $conn->prepare("UPDATE outfits SET is_used = 0 WHERE user_id = ?");
            if (!$resetStmt) {
                throw new Exception($conn->error);
            }
            $resetStmt->bind_param("s", $userId);
        } else {
            $resetStmt = $conn->prepare("UPDATE outfits SET is_used = 0");
            if (!$resetStmt) {
                throw new Exception($conn->error);
            }
        }
        $resetStmt->execute();
        $resetStmt->close();
    }

    if ($hasUserIdColumn && $hasIsUsedColumn) {
        $stmt = $conn->prepare("INSERT INTO outfits (user_id, name, is_used) VALUES (?, ?, ?)");
        if (!$stmt) {
            throw new Exception($conn->error);
        }
        $isUsed = $markAsUsed ? 1 : 0;
        $stmt->bind_param("ssi", $userId, $name, $isUsed);
    } elseif ($hasUserIdColumn) {
        $stmt = $conn->prepare("INSERT INTO outfits (user_id, name) VALUES (?, ?)");
        if (!$stmt) {
            throw new Exception($conn->error);
        }
        $stmt->bind_param("ss", $userId, $name);
    } elseif ($hasIsUsedColumn) {
        $stmt = $conn->prepare("INSERT INTO outfits (name, is_used) VALUES (?, ?)");
        if (!$stmt) {
            throw new Exception($conn->error);
        }
        $isUsed = $markAsUsed ? 1 : 0;
        $stmt->bind_param("si", $name, $isUsed);
    } else {
        $stmt = $conn->prepare("INSERT INTO outfits (name) VALUES (?)");
        if (!$stmt) {
            throw new Exception($conn->error);
        }
        $stmt->bind_param("s", $name);
    }
    $stmt->execute();
    $outfitId = $conn->insert_id;
    $stmt->close();
    
    $stmt = $conn->prepare("INSERT INTO outfit_items (outfit_id, layer_code, image_id) VALUES (?, ?, ?)");
    if (!$stmt) {
        throw new Exception($conn->error);
    }
    $useCountStmt = $conn->prepare("UPDATE images SET use_count = use_count + 1 WHERE id = ?");
    if (!$useCountStmt) {
        throw new Exception($conn->error);
    }
    foreach ($items as $layer => $imageId) {
        $imageId = (int) $imageId;
        if ($imageId > 0) {
            $stmt->bind_param("isi", $outfitId, $layer, $imageId);
            $stmt->execute();
            $useCountStmt->bind_param("i", $imageId);
            $useCountStmt->execute();
        }
    }
    $stmt->close();
    $useCountStmt->close();

    $conn->commit();

    $avatarImagePath = null;
    if ($markAsUsed) {
        $avatarImagePath = dressUpGenerateAvatarForOutfit($conn, (int)$userId, (int)$outfitId, (string)$name);
    }

    echo json_encode([
        'success' => true,
        'outfit_id' => $outfitId,
        'is_used' => $markAsUsed,
        'avatar_image_path' => $avatarImagePath
    ]);
} catch(Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
