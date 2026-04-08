<?php
// Start the session so the current outfit can be read from session data
session_start();

// Load the database connection and shared helper functions
require_once '../../../db_connect.php';
require_once '../includes/functions.php';

// Return the response in JSON format
header('Content-Type: application/json');

// Get the current outfit from the session and apply layer conflict rules
$outfit = applyConflictRules($_SESSION['current_outfit'] ?? []);

// Get the correct rendering order for outfit layers
$layerOrder = getLayerOrder();

// Store detailed layer information for the frontend
$layers = [];

// Loop through each layer in display order
foreach ($layerOrder as $layer) {
    // Skip this layer if no image is selected
    if (empty($outfit[$layer])) {
        continue;
    }

    $imageId = (int) $outfit[$layer];

    // Load the image record for the selected item
    $stmt = $conn->prepare("SELECT id, name, layer_code, file_path FROM images WHERE id = ?");
    if (!$stmt) {
        continue;
    }
    $stmt->bind_param("i", $imageId);
    $stmt->execute();
    $image = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Skip if the image record cannot be found
    if (!$image) {
        continue;
    }

    // Build the frontend-accessible image path
    $webPath = '../../dress up game' . $image['file_path'];
    $webPath = str_replace(' ', '%20', $webPath);

    // Save the layer data for the response
    $layers[] = [
        'layer' => $image['layer_code'],
        'image_id' => (int) $image['id'],
        'name' => $image['name'],
        'url' => $webPath
    ];
}

// Return the current outfit data and detailed layer list
echo json_encode([
    'success' => true,
    'items' => $outfit,
    'layers' => $layers
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>