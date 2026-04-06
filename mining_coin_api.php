<?php
require_once __DIR__ . '/mining_common.php';

coin_require_login(true);

$userId = coin_current_user_id();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $method === 'POST' ? ($_POST['action'] ?? '') : ($_GET['action'] ?? 'status');

if ($action === 'status') {
    coin_json_response([
        'success' => true,
        'data' => mining_get_bootstrap($conn, $userId),
    ]);
}

if ($method !== 'POST') {
    coin_json_response([
        'success' => false,
        'error' => 'Invalid request method.',
    ], 405);
}

if ($action === 'unlock_map') {
    $mapKey = trim((string)($_POST['map_key'] ?? ''));
    $result = mining_unlock_map($conn, $userId, $mapKey);
    coin_json_response($result, $result['success'] ? 200 : 400);
}

if ($action === 'record_map_play') {
    $mapKey = trim((string)($_POST['map_key'] ?? ''));
    mining_record_map_play($conn, $userId, $mapKey);
    coin_json_response([
        'success' => true,
        'balance' => coin_get_balance($conn, $userId),
    ]);
}

if ($action === 'clear_map') {
    $mapKey = trim((string)($_POST['map_key'] ?? ''));
    $result = mining_mark_map_cleared($conn, $userId, $mapKey);
    coin_json_response($result, $result['success'] ? 200 : 400);
}

if ($action === 'award_single_ore') {
    $runId = trim((string)($_POST['run_id'] ?? ''));
    $sequence = max(1, (int)($_POST['sequence'] ?? 1));
    $mapKey = trim((string)($_POST['map_key'] ?? ''));
    $oreType = trim((string)($_POST['ore_type'] ?? 'ore'));
    $amount = (int)($_POST['amount'] ?? 0);

    if ($runId === '' || $mapKey === '' || $amount <= 0) {
        coin_json_response([
            'success' => false,
            'error' => 'Missing mining reward parameters.',
        ], 400);
    }

    $result = mining_award_single_ore($conn, $userId, $runId, $sequence, $mapKey, $oreType, $amount);
    coin_json_response($result, $result['success'] ? 200 : 400);
}

if ($action === 'award_pvp_win') {
    $roomId = trim((string)($_POST['room_id'] ?? ''));
    if ($roomId === '') {
        coin_json_response([
            'success' => false,
            'error' => 'Missing room id.',
        ], 400);
    }

    $result = mining_award_pvp_win($conn, $userId, $roomId);
    coin_json_response($result, $result['success'] ? 200 : 400);
}

if ($action === 'achievement_status') {
    coin_json_response([
        'success' => true,
        'balance' => coin_get_balance($conn, $userId),
        'claimed_achievements' => mining_get_claimed_achievement_ids($conn, $userId),
    ]);
}

if ($action === 'claim_achievement') {
    $achievementId = trim((string)($_POST['achievement_id'] ?? ''));
    if ($achievementId === '') {
        coin_json_response([
            'success' => false,
            'error' => 'Missing achievement id.',
        ], 400);
    }

    $result = mining_claim_achievement_reward($conn, $userId, $achievementId);
    coin_json_response($result, $result['success'] ? 200 : 400);
}

coin_json_response([
    'success' => false,
    'error' => 'Unknown action.',
], 400);
