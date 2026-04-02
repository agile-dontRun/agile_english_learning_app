<?php
require_once 'memory_common.php';

header('Content-Type: application/json');

$userId = mm_current_user_id();
$matchId = (int)($_GET['match_id'] ?? 0);

$state = mm_get_single_board_state($conn, $matchId, $userId);
echo json_encode($state);