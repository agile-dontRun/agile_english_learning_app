<?php
// Load the database connection and shared helper functions
require_once '../../../db_connect.php';
require_once '../includes/functions.php';

// Start the session so the current outfit can be read
session_start();

// Get the current outfit from the session
$outfit = $_SESSION['current_outfit'] ?? [];

// If a body layer is selected, try to load its image file
if (isset($outfit['body'])) {
    $imageId = (int) $outfit['body'];

    // Query the database for the image file path
    $stmt = $conn->prepare("SELECT file_path FROM images WHERE id = ?");
    $imgData = null;
    if ($stmt) {
        $stmt->bind_param("i", $imageId);
        $stmt->execute();
        $imgData = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
    
    // If a valid file path is found, try to serve that image
    if ($imgData && !empty($imgData['file_path'])) {
        $filePath = __DIR__ . '/..' . $imgData['file_path'];
        if (file_exists($filePath)) {
            $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

            // Send the correct content type based on file extension
            if ($ext === 'png') header('Content-Type: image/png');
            elseif ($ext === 'jpg' || $ext === 'jpeg') header('Content-Type: image/jpeg');

            // Output the image file directly
            readfile($filePath);
            exit;
        }
    }
}

// Fallback to the default image if no custom body image is available
header('Content-Type: image/png');
readfile(__DIR__ . '/../people.png');
exit;
?>