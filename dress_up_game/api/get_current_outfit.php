<?php
session_start();
require_once '../../../db_connect.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$outfit = applyConflictRules($_SESSION['current_outfit'] ?? []);
$layerOrder = getLayerOrder();
$layers = [];

foreach ($layerOrder as $layer) {
    if (empty($outfit[$layer])) {
        continue;
    }

    $imageId = (int) $outfit[$layer];
    $stmt = $conn->prepare("SELECT id, name, layer_code, file_path FROM images WHERE id = ?");
    if (!$stmt) {
        continue;
    }
    $stmt->bind_param("i", $imageId);
    $stmt->execute();
    $image = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$image) {
        continue;
    }

    $webPath = '../../dress up game' . $image['file_path'];
    $webPath = str_replace(' ', '%20', $webPath);

    $layers[] = [
        'layer' => $image['layer_code'],
        'image_id' => (int) $image['id'],
        'name' => $image['name'],
        'url' => $webPath
    ];
}

echo json_encode([
    'success' => true,
    'items' => $outfit,
    'layers' => $layers
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
