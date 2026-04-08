<?php
// Load the database connection
require_once '../../../db_connect.php';

// Return the response in JSON format
header('Content-Type: application/json');

// Store all enabled image records
$images = [];

// Query all enabled images and sort them by layer, order, and newest ID
$result = $conn->query("SELECT * FROM images WHERE is_enabled = 1 ORDER BY layer_code, sort_order, id DESC");
if ($result instanceof mysqli_result) {
    $images = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
}

// Add a full image URL for each record so the frontend can load it directly
foreach ($images as &$img) {
    $img['full_url'] = '/picture/' . $img['layer_code'] . '/' . basename($img['file_path']);
}

// Return the final image list
echo json_encode(['success' => true, 'data' => $images]);
?>