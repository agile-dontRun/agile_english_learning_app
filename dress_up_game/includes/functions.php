<?php

function applyConflictRules($outfit) {
    $result = $outfit;
    if (isset($result['dress'])) {
        unset($result['top']);
        unset($result['pants']);
        unset($result['suit']);
    }
    if (isset($result['suit'])) {
        unset($result['top']);
        unset($result['pants']);
        unset($result['dress']);
    }
    if (isset($result['character'])) {
        unset($result['eye']);
        unset($result['eyebrows']);
        unset($result['nose']);
        unset($result['mouse']);
        unset($result['hair']);
    }
    return $result;
}


function getLayerOrder() {
    return [
        'background', 'body', 'shoes', 'top', 'pants', 'dress', 'suit',
        'eye', 'eyebrows', 'nose', 'mouse', 'hair',
        'character', 'glass', 'head'
    ];
}
?>