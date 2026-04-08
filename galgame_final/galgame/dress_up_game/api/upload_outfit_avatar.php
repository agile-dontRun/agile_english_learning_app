<?php
// Start the session so the current user can be identified
session_start();

// Load the database connection and shared dress-up helper functions
require_once '../../../db_connect.php';
require_once '../includes/functions.php';

// Return the response as JSON
header('Content-Type: application/json; charset=UTF-8');

// Read and decode the JSON request body
$input = json_decode(file_get_contents('php://input'), true);

// Get the outfit ID and uploaded image data from the request
$outfitId = isset($input['outfit_id']) ? (int)$input['outfit_id'] : 0;
$imageData = isset($input['image_data']) ? (string)$input['image_data'] : '';

// Get the current user ID from the session
$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

// Reject invalid upload requests
if ($userId <= 0 || $outfitId <= 0 || $imageData === '') {
    echo json_encode(['success' => false, 'error' => 'Invalid upload request.']);
    exit;
}

// Verify that the requested outfit belongs to the current user
$stmt = $conn->prepare("SELECT id, name FROM outfits WHERE id = ? AND user_id = ? LIMIT 1");
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Failed to verify outfit.']);
    exit;
}
$stmt->bind_param("ii", $outfitId, $userId);
$stmt->execute();
$outfit = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Return an error if the outfit does not exist or does not belong to this user
if (!$outfit) {
    echo json_encode(['success' => false, 'error' => 'Outfit not found.']);
    exit;
}

// Only allow base64-encoded PNG image uploads
if (!preg_match('#^data:image/png;base64,#', $imageData)) {
    echo json_encode(['success' => false, 'error' => 'Only PNG avatar uploads are supported.']);
    exit;
}

// Remove the data URL prefix and decode the image data
$rawBase64 = substr($imageData, strlen('data:image/png;base64,'));
$binary = base64_decode(str_replace(' ', '+', $rawBase64), true);
if ($binary === false) {
    echo json_encode(['success' => false, 'error' => 'Failed to decode avatar image.']);
    exit;
}

// Get the directory where generated avatar images should be stored
$avatarDir = dressUpGeneratedAvatarDirAbsolute();

// Create the avatar directory if it does not already exist
if (!is_dir($avatarDir) && !@mkdir($avatarDir, 0777, true) && !is_dir($avatarDir)) {
    echo json_encode(['success' => false, 'error' => 'Failed to create avatar directory.']);
    exit;
}

// Build the output file name and both absolute/web paths
$filename = $userId . '_' . $outfitId . '_' . dressUpSlugify((string)$outfit['name']) . '.png';
$absolutePath = $avatarDir . '/' . $filename;
$webPath = dressUpGeneratedAvatarWebBase() . '/' . rawurlencode($filename);

// Remove old avatar files for this user/outfit before saving the new one
dressUpCleanupUserAvatarFiles($conn, $userId, $outfitId);

// Save the uploaded PNG file to disk
if (@file_put_contents($absolutePath, $binary) === false) {
    echo json_encode(['success' => false, 'error' => 'Failed to save avatar image.']);
    exit;
}

// Update the saved avatar path in the database for this outfit
dressUpWriteOutfitAvatarPath($conn, $outfitId, $webPath);

// Return success response with the new avatar path
echo json_encode([
    'success' => true,
    'avatar_image_path' => $webPath,
    'outfit_id' => $outfitId,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);