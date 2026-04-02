<?php
require_once 'memory_common.php';

header('Content-Type: application/json');

$userId = mm_current_user_id();

$matchId = (int)($_POST['match_id'] ?? 0);
$cardId = (int)($_POST['card_id'] ?? 0);

if ($matchId <= 0 || $cardId <= 0) {
    echo json_encode(['ok' => false, 'message' => 'Invalid request.']);
    exit;
}

$result = mm_submit_flip($conn, $matchId, $userId, $cardId);
echo json_encode($result);