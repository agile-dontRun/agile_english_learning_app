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

$player = mm_get_match_player($conn, $matchId, $userId);
$match = mm_get_match($conn, $matchId);
$mode = $match ? mm_get_mode($conn, (int)$match['mode_id']) : null;

if ($player && $mode && (int)$player['matched_pairs_count'] >= (int)$mode['pair_count']) {
    if (!(int)$player['finished_all']) {
        $stmt = $conn->prepare("
            UPDATE memory_match_players
            SET finished_all = 1, finished_at = NOW()
            WHERE match_id = ? AND user_id = ?
        ");
        $stmt->bind_param("ii", $matchId, $userId);
        $stmt->execute();
        $stmt->close();
    }

    mm_settle_single_match($conn, $matchId, $userId);
    $result['finished'] = true;
}

echo json_encode($result);