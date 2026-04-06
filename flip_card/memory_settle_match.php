<?php
require_once 'memory_common.php';

$userId = mm_current_user_id();
$matchId = (int)($_GET['match_id'] ?? 0);
$match = mm_get_match($conn, $matchId);
if (!$match) {
    die('Match not found.');
}
$matchPlayer = mm_get_match_player($conn, $matchId, $userId);
if (!$matchPlayer) {
    die('You are not in this match.');
}

mm_settle_match($conn, $matchId);
header('Location: memory_match_result.php?match_id=' . $matchId);
exit;
?>
