<?php
session_start();
require_once '../includes/functions.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$items = $input['items'] ?? [];

if (!is_array($items)) {
    echo json_encode(['success' => false, 'error' => 'Invalid outfit data']);
    exit;
}

$normalized = [];
foreach ($items as $layer => $imageId) {
    $imageId = (int) $imageId;
    if ($imageId > 0) {
        $normalized[$layer] = $imageId;
    }
}

$_SESSION['current_outfit'] = applyConflictRules($normalized);

echo json_encode([
    'success' => true,
    'data' => $_SESSION['current_outfit']
]);
?>
