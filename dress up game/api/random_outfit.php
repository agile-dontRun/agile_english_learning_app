<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');


$stmt = $pdo->query("SELECT * FROM images WHERE is_enabled = 1");
$allImages = $stmt->fetchAll();


$grouped = [];
foreach ($allImages as $img) {
    $grouped[$img['layer_code']][] = $img;
}

function getRandomImage($layer, $grouped) {
    if (!isset($grouped[$layer]) || count($grouped[$layer]) === 0) return null;
    $randomIndex = rand(0, count($grouped[$layer]) - 1);
    return $grouped[$layer][$randomIndex]['id'];
}

$newOutfit = [];
$mode = rand(0, 3);

$baseLayers = ['background', 'body', 'shoes', 'glass', 'head'];
foreach ($baseLayers as $layer) {
    $id = getRandomImage($layer, $grouped);
    if ($id) $newOutfit[$layer] = $id;
}

if ($mode === 0) {
    $topId = getRandomImage('top', $grouped);
    $pantsId = getRandomImage('pants', $grouped);
    if ($topId) $newOutfit['top'] = $topId;
    if ($pantsId) $newOutfit['pants'] = $pantsId;
    $faceLayers = ['eye', 'eyebrows', 'nose', 'mouse', 'hair'];
    foreach ($faceLayers as $layer) {
        $id = getRandomImage($layer, $grouped);
        if ($id) $newOutfit[$layer] = $id;
    }
} elseif ($mode === 1) {
    $dressId = getRandomImage('dress', $grouped);
    if ($dressId) $newOutfit['dress'] = $dressId;
    $faceLayers = ['eye', 'eyebrows', 'nose', 'mouse', 'hair'];
    foreach ($faceLayers as $layer) {
        $id = getRandomImage($layer, $grouped);
        if ($id) $newOutfit[$layer] = $id;
    }
} elseif ($mode === 2) {
    $suitId = getRandomImage('suit', $grouped);
    if ($suitId) $newOutfit['suit'] = $suitId;
    $faceLayers = ['eye', 'eyebrows', 'nose', 'mouse', 'hair'];
    foreach ($faceLayers as $layer) {
        $id = getRandomImage($layer, $grouped);
        if ($id) $newOutfit[$layer] = $id;
    }
} elseif ($mode === 3) {
    $characterId = getRandomImage('character', $grouped);
    if ($characterId) $newOutfit['character'] = $characterId;
}

$modeNames = ['Top+Pants', 'Dress', 'Suit', 'Character'];

echo json_encode([
    'success' => true,
    'outfit' => $newOutfit,
    'mode' => $modeNames[$mode]
]);
?>