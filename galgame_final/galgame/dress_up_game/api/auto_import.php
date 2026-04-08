<?php
// Load the database connection
require_once '../../../db_connect.php';

// Return the result as JSON
header('Content-Type: application/json');

// Base folder that contains all image layer subfolders
$pictureDir = __DIR__ . '/../picture/';

// List of supported avatar/image layers to import
$layers = [
    'background', 'body', 'shoes', 'top', 'pants', 'dress', 'suit',
    'eye', 'eyebrows', 'nose', 'mouse', 'hair',
    'earings', 'glass', 'head', 'character'
];

// Counter for successfully imported images
$imported = 0;

// Store any errors that happen during import
$errors = [];

// Loop through each layer folder
foreach ($layers as $layer) {
    $layerDir = $pictureDir . $layer . '/';

    // Skip this layer if its folder does not exist
    if (!is_dir($layerDir)) continue;
    
    // Read all files inside the current layer folder
    $files = scandir($layerDir);

    foreach ($files as $file) {
        // Get the file extension in lowercase
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        // Only allow common image formats
        if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'gif'])) continue;
        
        // Use the file name (without extension) as the image name
        $name = pathinfo($file, PATHINFO_FILENAME);

        // Build the relative file path stored in the database
        $filePath = '/picture/' . $layer . '/' . $file;
        
        // Check whether this image has already been imported
        $stmt = $conn->prepare("SELECT id FROM images WHERE layer_code = ? AND file_path = ?");
        if (!$stmt) {
            $errors[] = "$layer/$file: " . $conn->error;
            continue;
        }

        $stmt->bind_param("ss", $layer, $filePath);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Skip duplicate records
        if ($existing) continue;
        
        try {
            // Insert the image into the database
            $stmt = $conn->prepare("INSERT INTO images (layer_code, name, file_path, thumbnail_path) VALUES (?, ?, ?, ?)");
            if (!$stmt) {
                throw new Exception($conn->error);
            }

            $stmt->bind_param("ssss", $layer, $name, $filePath, $filePath);
            $stmt->execute();
            $stmt->close();

            // Count this image as successfully imported
            $imported++;
        } catch(Exception $e) {
            // Save the error but continue importing other files
            $errors[] = "$layer/$file: " . $e->getMessage();
        }
    }
}

// Return the final import result
echo json_encode(['success' => true, 'imported' => $imported, 'errors' => $errors]);
?>