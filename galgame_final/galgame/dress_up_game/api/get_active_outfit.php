<?php
// Start the session so the current user can be identified
session_start();

// Load the database connection and shared helper functions
require_once '../../../db_connect.php';
require_once '../includes/functions.php';

// Return the response as JSON
header('Content-Type: application/json; charset=UTF-8');

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

    return (bool) $conn->query("ALTER TABLE outfits ADD COLUMN is_used BOOLEAN NOT NULL DEFAULT FALSE");
}

// Track whether the outfits table supports user-specific and active outfit fields
$hasUserIdColumn = false;
$hasIsUsedColumn = ensureIsUsedColumn($conn);

// Check whether the outfits table has a user_id column
$userColumnCheck = $conn->query("SHOW COLUMNS FROM outfits LIKE 'user_id'");
if ($userColumnCheck instanceof mysqli_result) {
    $hasUserIdColumn = $userColumnCheck->num_rows > 0;
    $userColumnCheck->free();
}

// Get the user ID from session or from the optional query parameter
$sessionUserId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
$requestedUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$userId = $requestedUserId > 0 ? $requestedUserId : $sessionUserId;

// Will store the active outfit row if found
$outfitRow = null;

// If both user_id and is_used are available, fetch the active outfit for the selected user
if ($hasIsUsedColumn && $hasUserIdColumn) {
    $stmt = $conn->prepare("SELECT id, name FROM outfits WHERE user_id = ? AND is_used = 1 ORDER BY id DESC LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $outfitRow = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }

// If only is_used exists, fall back to the latest active outfit globally
} elseif ($hasIsUsedColumn) {
    $stmt = $conn->prepare("SELECT id, name FROM outfits WHERE is_used = 1 ORDER BY id DESC LIMIT 1");
    if ($stmt) {
        $stmt->execute();
        $outfitRow = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

// If no active outfit is found, return an empty result
if (!$outfitRow) {
    echo json_encode([
        'success' => true,
        'outfit' => [],
        'layers' => [],
        'name' => null
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Load all items that belong to the selected outfit
$items = [];
$itemStmt = $conn->prepare("
    SELECT oi.layer_code, oi.image_id, i.name, i.file_path
    FROM outfit_items oi
    INNER JOIN images i ON i.id = oi.image_id
    WHERE oi.outfit_id = ?
");
if ($itemStmt) {
    $itemStmt->bind_param("i", $outfitRow['id']);
    $itemStmt->execute();
    $result = $itemStmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    $itemStmt->close();
}

// Re-index outfit items by layer code for easier lookup
$indexed = [];
foreach ($items as $item) {
    $indexed[$item['layer_code']] = $item;
}

// Build the final outfit data in the correct layer order
$outfit = [];
$layers = [];
foreach (getLayerOrder() as $layerCode) {
    if (!isset($indexed[$layerCode])) {
        continue;
    }

    $item = $indexed[$layerCode];
    $imageId = (int) $item['image_id'];

    // Store a simple layer => image_id mapping
    $outfit[$layerCode] = $imageId;

    // Store detailed layer information for frontend rendering
    $layers[] = [
        'layer' => $layerCode,
        'image_id' => $imageId,
        'name' => $item['name'],
        'url' => '/galgame/dress_up_game' . $item['file_path'],
        'file_path' => $item['file_path']
    ];
}

// Return the active outfit data as JSON
echo json_encode([
    'success' => true,
    'outfit' => applyConflictRules($outfit),
    'layers' => $layers,
    'name' => $outfitRow['name']
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>