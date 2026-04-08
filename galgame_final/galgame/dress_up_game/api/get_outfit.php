<?php
// Start the session so the current user can be identified
session_start();

// Load the database connection
require_once '../../../db_connect.php';

// Return the response in JSON format
header('Content-Type: application/json');

// Get the outfit ID from the query string
$id = $_GET['id'] ?? 0;

// Get the current user ID from the session
$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

// Convert the outfit ID to an integer
$id = (int) $id;

// Store the outfit items loaded from the database
$items = [];

// Track whether the outfits table has a user_id column
$hasUserIdColumn = false;

// Check whether the outfits table supports user-specific outfit ownership
$columnCheck = $conn->query("SHOW COLUMNS FROM outfits LIKE 'user_id'");
if ($columnCheck instanceof mysqli_result) {
    $hasUserIdColumn = $columnCheck->num_rows > 0;
    $columnCheck->free();
}

// If user_id exists, only load outfit items that belong to the current user
if ($hasUserIdColumn) {
    $stmt = $conn->prepare("
        SELECT oi.layer_code, oi.image_id
        FROM outfit_items oi
        INNER JOIN outfits o ON o.id = oi.outfit_id
        WHERE oi.outfit_id = ? AND o.user_id = ?
    ");
    if ($stmt) {
        $stmt->bind_param("ii", $id, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $items = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }

// Otherwise, fall back to loading the outfit without ownership filtering
} else {
    $stmt = $conn->prepare("SELECT layer_code, image_id FROM outfit_items WHERE outfit_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $items = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

// Convert the item list into a layer => image_id map
$outfit = [];
foreach ($items as $item) {
    $outfit[$item['layer_code']] = $item['image_id'];
}

// Return the final outfit data
echo json_encode(['success' => true, 'data' => $outfit]);
?>