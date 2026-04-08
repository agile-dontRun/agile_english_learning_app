<?php
// Start the session so the current logged-in user can be identified
session_start();

// Load the database connection and shared dress-up helper functions
require_once '../../../db_connect.php';
require_once '../includes/functions.php';

// Return the response as JSON
header('Content-Type: application/json; charset=UTF-8');

// Get the current user ID from the dress-up helper
$userId = dressUpCurrentUserId();

// Reject the request if the user is not logged in
if ($userId === '0') {
    echo json_encode([
        'success' => false,
        'error' => 'Please log in first.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Read and decode the JSON request body
$input = json_decode(file_get_contents('php://input'), true);

// Get the requested image/item ID from the request
$imageId = isset($input['image_id']) ? (int)$input['image_id'] : 0;

// Try to purchase the selected item for the current user
$result = dressUpPurchaseItem($conn, $userId, $imageId);

// Return the purchase result
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>