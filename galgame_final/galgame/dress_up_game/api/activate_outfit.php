<?php
// Start the session so the current logged-in user can be identified
session_start();

// Load database connection and shared helper functions
require_once '../../../db_connect.php';
require_once '../includes/functions.php';

// Return all responses as JSON
header('Content-Type: application/json; charset=UTF-8');

// Read and decode the JSON request body
$input = json_decode(file_get_contents('php://input'), true);

// Get the requested outfit ID from the request body
$outfitId = isset($input['outfit_id']) ? (int)$input['outfit_id'] : 0;

// Get the current user ID from the dress-up system helper
$userId = dressUpCurrentUserId();

// Reject invalid requests
if ($userId === '0' || $outfitId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid request.']);
    exit;
}

// Make sure the outfits table has the is_used column
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

// Check whether the outfits table has a user_id column
$hasUserIdColumn = false;
$userColumnCheck = $conn->query("SHOW COLUMNS FROM outfits LIKE 'user_id'");
if ($userColumnCheck instanceof mysqli_result) {
    $hasUserIdColumn = $userColumnCheck->num_rows > 0;
    $userColumnCheck->free();
}

// Stop if the required outfit activation columns are not available
if (!$hasUserIdColumn || !ensureIsUsedColumn($conn)) {
    echo json_encode(['success' => false, 'error' => 'Outfit activation is not available.']);
    exit;
}

// Check whether the requested outfit belongs to the current user
$stmt = $conn->prepare("SELECT id, name FROM outfits WHERE id = ? AND user_id = ? LIMIT 1");
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Failed to prepare activation query.']);
    exit;
}
$stmt->bind_param("is", $outfitId, $userId);
$stmt->execute();
$outfit = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Return an error if the outfit does not exist or does not belong to this user
if (!$outfit) {
    echo json_encode(['success' => false, 'error' => 'Outfit not found.']);
    exit;
}

try {
    // Start a transaction so the outfit activation update stays consistent
    $conn->begin_transaction();

    // First, mark all outfits for this user as not active
    $stmt = $conn->prepare("UPDATE outfits SET is_used = 0 WHERE user_id = ?");
    if (!$stmt) {
        throw new Exception($conn->error);
    }
    $stmt->bind_param("s", $userId);
    $stmt->execute();
    $stmt->close();

    // Then, mark the selected outfit as the active one
    $stmt = $conn->prepare("UPDATE outfits SET is_used = 1 WHERE id = ? AND user_id = ?");
    if (!$stmt) {
        throw new Exception($conn->error);
    }
    $stmt->bind_param("is", $outfitId, $userId);
    $stmt->execute();
    $stmt->close();

    // Save the database changes
    $conn->commit();

    // Generate the avatar image for the newly activated outfit
    $avatarImagePath = dressUpGenerateAvatarForOutfit(
        $conn,
        (int)$userId,
        (int)$outfit['id'],
        (string)$outfit['name']
    );

    // Return success response with outfit and avatar info
    echo json_encode([
        'success' => true,
        'outfit_id' => (int)$outfit['id'],
        'outfit_name' => $outfit['name'],
        'avatar_image_path' => $avatarImagePath
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    // Roll back database changes if anything fails
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}