<?php
// Start the session so the current user and related session data can be accessed
session_start();

// Load the database connection and shared helper functions
require_once '../../../db_connect.php';
require_once '../includes/functions.php';

// Return the response in JSON format
header('Content-Type: application/json');

// Read and decode the JSON request body
$input = json_decode(file_get_contents('php://input'), true);

// Get the outfit name, selected items, and whether it should be marked as active
$name = $input['name'] ?? 'Unnamed outfit';
$items = $input['items'] ?? [];
$markAsUsed = !empty($input['is_used']);

// Make sure a specific flag column exists in the outfits table
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
    // Get the current user ID from the dress-up helper
    $userId = dressUpCurrentUserId();

    // Track whether the outfits table supports user-specific outfits
    $hasUserIdColumn = false;

    // Make sure the is_used column exists
    $hasIsUsedColumn = ensureOutfitFlagColumn($conn, 'is_used', 'BOOLEAN NOT NULL DEFAULT FALSE');

    // Check whether the outfits table has a user_id column
    $columnCheck = $conn->query("SHOW COLUMNS FROM outfits LIKE 'user_id'");
    if ($columnCheck instanceof mysqli_result) {
        $hasUserIdColumn = $columnCheck->num_rows > 0;
        $columnCheck->free();
    }

    // Verify that all selected items have been unlocked by the user
    $missingUnlocks = dressUpValidateItemOwnership($conn, $userId, is_array($items) ? $items : []);
    if ($missingUnlocks) {
        throw new Exception('This look contains items that have not been unlocked yet.');
    }

    // Start a transaction so outfit creation stays consistent
    $conn->begin_transaction();

    // If this new outfit should be marked as active, reset other active outfits first
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

    // Insert the new outfit record, depending on which columns are available
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
    
    // Prepare statements for saving outfit items and updating image usage count
    $stmt = $conn->prepare("INSERT INTO outfit_items (outfit_id, layer_code, image_id) VALUES (?, ?, ?)");
    if (!$stmt) {
        throw new Exception($conn->error);
    }
    $useCountStmt = $conn->prepare("UPDATE images SET use_count = use_count + 1 WHERE id = ?");
    if (!$useCountStmt) {
        throw new Exception($conn->error);
    }

    // Save each selected item into the outfit and update its usage count
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

    // Commit all database changes
    $conn->commit();

    // Generate an avatar image if this outfit was marked as active
    $avatarImagePath = null;
    if ($markAsUsed) {
        $avatarImagePath = dressUpGenerateAvatarForOutfit($conn, (int)$userId, (int)$outfitId, (string)$name);
    }

    // Return success response with the new outfit information
    echo json_encode([
        'success' => true,
        'outfit_id' => $outfitId,
        'is_used' => $markAsUsed,
        'avatar_image_path' => $avatarImagePath
    ]);
} catch(Exception $e) {
    // Roll back all changes if anything fails
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>