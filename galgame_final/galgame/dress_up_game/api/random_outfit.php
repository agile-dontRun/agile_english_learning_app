<?php
// Load the database connection and shared helper functions
require_once '../../../db_connect.php';
require_once '../includes/functions.php';

// Return the response in JSON format
header('Content-Type: application/json');


// Store all enabled images from the database
$allImages = [];
$result = $conn->query("SELECT * FROM images WHERE is_enabled = 1");
if ($result instanceof mysqli_result) {
    $allImages = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
}


// Group images by their layer code for easier random selection
$grouped = [];
foreach ($allImages as $img) {
    $grouped[$img['layer_code']][] = $img;
}

// Return a random image ID from the given layer
function getRandomImage($layer, $grouped) {
    if (!isset($grouped[$layer]) || count($grouped[$layer]) === 0) return null;
    $randomIndex = rand(0, count($grouped[$layer]) - 1);
    return $grouped[$layer][$randomIndex]['id'];
}

// Store the generated random outfit
$newOutfit = [];

// Randomly choose one outfit mode
$mode = rand(0, 3);

// These base layers are always included if available
$baseLayers = ['background', 'body', 'shoes', 'glass', 'head'];
foreach ($baseLayers as $layer) {
    $id = getRandomImage($layer, $grouped);
    if ($id) $newOutfit[$layer] = $id;
}

// Mode 0: top + pants + face features
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

// Mode 1: dress + face features
} elseif ($mode === 1) {
    $dressId = getRandomImage('dress', $grouped);
    if ($dressId) $newOutfit['dress'] = $dressId;
    $faceLayers = ['eye', 'eyebrows', 'nose', 'mouse', 'hair'];
    foreach ($faceLayers as $layer) {
        $id = getRandomImage($layer, $grouped);
        if ($id) $newOutfit[$layer] = $id;
    }

// Mode 2: suit + face features
} elseif ($mode === 2) {
    $suitId = getRandomImage('suit', $grouped);
    if ($suitId) $newOutfit['suit'] = $suitId;
    $faceLayers = ['eye', 'eyebrows', 'nose', 'mouse', 'hair'];
    foreach ($faceLayers as $layer) {
        $id = getRandomImage($layer, $grouped);
        if ($id) $newOutfit[$layer] = $id;
    }

// Mode 3: full character layer only
} elseif ($mode === 3) {
    $characterId = getRandomImage('character', $grouped);
    if ($characterId) $newOutfit['character'] = $characterId;
}

// Labels for the different random outfit modes
$modeNames = ['Top+Pants', 'Dress', 'Suit', 'Character'];

// Return the generated outfit and its mode name
echo json_encode([
    'success' => true,
    'outfit' => $newOutfit,
    'mode' => $modeNames[$mode]
]);
?>