<?php
// Start the session so the current logged-in user can be identified
session_start();

// Load the database connection and shared dress-up helper functions
require_once '../../../db_connect.php';
require_once '../includes/functions.php';

// Return the response as JSON
header('Content-Type: application/json; charset=UTF-8');

// Default avatar used when no generated outfit avatar is available
$defaultAvatarUrl = '../frontend/assets/player.jpg';

// Get the current user ID from the session
$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

// If the user is not logged in, return the default avatar
if ($userId <= 0) {
    echo json_encode([
        'success' => true,
        'avatar_url' => $defaultAvatarUrl,
        'generated' => false,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Track whether required columns exist in the outfits table
$hasUserIdColumn = false;
$hasIsUsedColumn = false;

// Make sure the avatar image path column exists
$hasAvatarColumn = dressUpEnsureAvatarColumn($conn);

// Check whether the outfits table has a user_id column
$columnCheck = $conn->query("SHOW COLUMNS FROM outfits LIKE 'user_id'");
if ($columnCheck instanceof mysqli_result) {
    $hasUserIdColumn = $columnCheck->num_rows > 0;
    $columnCheck->free();
}

// Check whether the outfits table has an is_used column
$columnCheck = $conn->query("SHOW COLUMNS FROM outfits LIKE 'is_used'");
if ($columnCheck instanceof mysqli_result) {
    $hasIsUsedColumn = $columnCheck->num_rows > 0;
    $columnCheck->free();
}

// If the required columns are missing, fall back to the default avatar
if (!$hasUserIdColumn || !$hasIsUsedColumn || !$hasAvatarColumn) {
    echo json_encode([
        'success' => true,
        'avatar_url' => $defaultAvatarUrl,
        'generated' => false,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Find the user's currently active outfit
$stmt = $conn->prepare("
    SELECT id, name, avatar_image_path
    FROM outfits
    WHERE user_id = ? AND is_used = 1
    ORDER BY id DESC
    LIMIT 1
");

if (!$stmt) {
    // Fall back to the default avatar if the query cannot be prepared
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

// If the user has no active outfit, return the default avatar
if (!$outfit) {
    echo json_encode([
        'success' => true,
        'avatar_url' => $defaultAvatarUrl,
        'generated' => false,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Read the saved avatar image path from the active outfit
$avatarUrl = (string)($outfit['avatar_image_path'] ?? '');
$generated = false;

// If the avatar image has not been generated yet, generate it now
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

// If generation still did not produce a valid avatar, use the default one
if ($avatarUrl === '') {
    $avatarUrl = $defaultAvatarUrl;
}

// Return the final avatar information
echo json_encode([
    'success' => true,
    'avatar_url' => $avatarUrl,
    'generated' => $generated,
    'outfit_id' => (int)$outfit['id'],
    'outfit_name' => $outfit['name'],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);