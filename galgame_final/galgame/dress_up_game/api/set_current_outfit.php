<?php
// Start the session so the current outfit can be stored in session data
session_start();

// Load shared dress-up helper functions
require_once '../includes/functions.php';

// Return the response in JSON format
header('Content-Type: application/json');

// Read and decode the JSON request body
$input = json_decode(file_get_contents('php://input'), true);

// Get the selected outfit items from the request
$items = $input['items'] ?? [];

// Validate that the incoming outfit data is an array
if (!is_array($items)) {
    echo json_encode(['success' => false, 'error' => 'Invalid outfit data']);
    exit;
}

// Normalize the selected items and keep only valid image IDs
$normalized = [];
foreach ($items as $layer => $imageId) {
    $imageId = (int) $imageId;
    if ($imageId > 0) {
        $normalized[$layer] = $imageId;
    }
}

// Apply conflict rules and save the current outfit into the session
$_SESSION['current_outfit'] = applyConflictRules($normalized);

// Return the saved outfit data
echo json_encode([
    'success' => true,
    'data' => $_SESSION['current_outfit']
]);
?>