<?php
require_once 'memory_common.php';

header('Content-Type: application/json');

$userId = mm_current_user_id();
$matchId = (int)($_GET['match_id'] ?? 0);

$match = mm_get_match($conn, $matchId);
if (!$match) {
    echo json_encode(['ok' => false, 'message' => 'Match not found.']);
    exit;
}

$player = mm_get_match_player($conn, $matchId, $userId);
if (!$player) {
    echo json_encode(['ok' => false, 'message' => 'You are not in this match.']);
    exit;
}

$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM memory_match_players WHERE match_id = ?");
$stmt->bind_param("i", $matchId);
$stmt->execute();
$count = $stmt->get_result()->fetch_assoc();
$stmt->close();

echo json_encode([
    'ok' => true,
    'status' => $match['status'],
    'player_count' => (int)$count['total']
]);
