<?php
// Load the database connection
require_once '../../../db_connect.php';

// Return the response in JSON format
header('Content-Type: application/json');

// Get the outfit ID from the POST request
$id = $_POST['id'] ?? 0;

// Prepare the delete query
$stmt = $conn->prepare("DELETE FROM outfits WHERE id = ?");
if (!$stmt) {
    // Return an error if the query preparation fails
    echo json_encode(['success' => false, 'error' => $conn->error]);
    exit;
}

// Convert the ID to an integer before binding
$id = (int) $id;
$stmt->bind_param("i", $id);

// Execute the delete operation
$success = $stmt->execute();
$stmt->close();

// Return whether the deletion was successful
echo json_encode(['success' => $success]);
?>