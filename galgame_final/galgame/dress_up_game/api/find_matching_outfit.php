<?php
// Start the session so the current user can be identified
session_start();

// Load the database connection and shared helper functions
require_once '../../../db_connect.php';
require_once '../includes/functions.php';

// Return the response as JSON
header('Content-Type: application/json; charset=UTF-8');

// Read and decode the JSON request body
$input = json_decode(file_get_contents('php://input'), true);

// Get the selected dress-up items from the request
$items = $input['items'] ?? [];

// Get the current user ID from the session
$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

// Reject invalid requests
if ($userId <= 0 || !is_array($items)) {
    echo json_encode(['success' => false, 'error' => 'Invalid request.']);
    exit;
}

// Normalize the incoming item list and keep only valid image IDs
$normalized = [];
foreach ($items as $layer => $imageId) {
    $imageId = (int)$imageId;
    if ($imageId > 0) {
        $normalized[$layer] = $imageId;
    }
}

// Apply layer conflict rules and sort for stable comparison
$normalized = applyConflictRules($normalized);
ksort($normalized);

// Check whether the outfits table has a user_id column
$hasUserIdColumn = false;
$userColumnCheck = $conn->query("SHOW COLUMNS FROM outfits LIKE 'user_id'");
if ($userColumnCheck instanceof mysqli_result) {
    $hasUserIdColumn = $userColumnCheck->num_rows > 0;
    $userColumnCheck->free();
}

// If user-specific outfits are not supported, return no match
if (!$hasUserIdColumn) {
    echo json_encode(['success' => true, 'match' => null], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Load all outfits saved by the current user, newest first
$stmt = $conn->prepare("SELECT id, name, is_used FROM outfits WHERE user_id = ? ORDER BY id DESC");
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Failed to load outfits.']);
    exit;
}
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

// Try to find an outfit whose items exactly match the current selection
$match = null;
while ($row = $result->fetch_assoc()) {
    $outfitItems = dressUpGetOutfitItems($conn, (int)$row['id']);
    $candidate = [];

    // Build a comparable layer => image_id map for this saved outfit
    foreach ($outfitItems as $layerCode => $item) {
        $candidate[$layerCode] = (int)$item['image_id'];
    }

    // Apply the same conflict rules and sorting before comparison
    $candidate = applyConflictRules($candidate);
    ksort($candidate);

    // If the saved outfit matches the current selection, return it
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

// Return the matching outfit information, or null if none matched
echo json_encode([
    'success' => true,
    'match' => $match
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);