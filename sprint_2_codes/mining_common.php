<?php
require_once __DIR__ . '/coin_common.php';

function mining_user_id_param($userId): string {
    return (string)$userId;
}

function mining_get_map_catalog(): array {
    return [
        'map1' => [
        'key' => 'map1',
        'name' => 'COAL MINE',
        'image' => 'map1.png',
        'cost' => 0,
        'required_unlock' => null,
        'ref_id' => 1,
        ],
        'map2' => [
            'key' => 'map2',
            'name' => 'SILVER MINE',
            'image' => 'map2.png',
            'cost' => 500,
            'required_unlock' => 'map1',
            'ref_id' => 2,
        ],
        'map3' => [
            'key' => 'map3',
            'name' => 'GOLD MINE',
            'image' => 'map3.png',
            'cost' => 1200,
            'required_unlock' => 'map2',
            'ref_id' => 3,
        ],
        'map4' => [
            'key' => 'map4',
            'name' => 'DIAMOND MINE',
            'image' => 'map4.png',
            'cost' => 2500,
            'required_unlock' => 'map3',
            'ref_id' => 4,
        ],
        'map5' => [
            'key' => 'map5',
            'name' => 'FINAL',
            'image' => 'map5.png',
            'cost' => 5000,
            'required_unlock' => 'map4',
            'ref_id' => 5,
        ],
        
    ];
}

function mining_get_achievement_catalog(): array {
    return [
        'ach_first_game' => ['reward' => 100, 'ref_id' => 1001],
        'ach_5_wins' => ['reward' => 1000, 'ref_id' => 1002],
        'ach_10k_coins' => ['reward' => 500, 'ref_id' => 1003],
        'ach_level_5' => ['reward' => 800, 'ref_id' => 1004],
        'ach_diamond_fail' => ['reward' => 200, 'ref_id' => 1005],
    ];
}

function mining_ref_id_from_string(string $value): int {
    if (preg_match('/^\d+$/', $value)) {
        return (int)$value;
    }
    return (int)sprintf('%u', crc32($value));
}

function mining_ensure_progress(mysqli $conn, $userId): void {
    $userIdParam = mining_user_id_param($userId);

    foreach (mining_get_map_catalog() as $mapKey => $map) {
        $isUnlocked = $mapKey === 'map1' ? 1 : 0;

        $stmt = $conn->prepare("
            INSERT IGNORE INTO mining_map_progress
            (user_id, map_key, is_unlocked, unlocked_at, is_cleared, last_played_at)
            VALUES (?, ?, ?, IF(? = 1, NOW(), NULL), 0, NULL)
        ");
        $stmt->bind_param("ssii", $userIdParam, $mapKey, $isUnlocked, $isUnlocked);
        $stmt->execute();
        $stmt->close();
    }
}

function mining_get_progress_map(mysqli $conn, $userId): array {
    mining_ensure_progress($conn, $userId);
    $userIdParam = mining_user_id_param($userId);

    $stmt = $conn->prepare("
        SELECT map_key, is_unlocked, unlocked_at, is_cleared, cleared_at, last_played_at
        FROM mining_map_progress
        WHERE user_id = ?
    ");
    $stmt->bind_param("s", $userIdParam);
    $stmt->execute();
    $res = $stmt->get_result();

    $progress = [];
    while ($row = $res->fetch_assoc()) {
        $progress[$row['map_key']] = [
            'is_unlocked' => (int)$row['is_unlocked'] === 1,
            'is_cleared' => (int)$row['is_cleared'] === 1,
            'unlocked_at' => $row['unlocked_at'],
            'cleared_at' => $row['cleared_at'],
            'last_played_at' => $row['last_played_at'],
        ];
    }
    $stmt->close();

    foreach (mining_get_map_catalog() as $mapKey => $map) {
        if (!isset($progress[$mapKey])) {
            $progress[$mapKey] = [
                'is_unlocked' => $mapKey === 'map1',
                'is_cleared' => false,
                'unlocked_at' => null,
                'cleared_at' => null,
                'last_played_at' => null,
            ];
        }
    }

    return $progress;
}

function mining_get_bootstrap(mysqli $conn, $userId): array {
    $catalog = array_values(mining_get_map_catalog());
    $progressMap = mining_get_progress_map($conn, $userId);

    foreach ($catalog as &$map) {
        $state = $progressMap[$map['key']] ?? [];
        $map['is_unlocked'] = (bool)($state['is_unlocked'] ?? false);
        $map['is_cleared'] = (bool)($state['is_cleared'] ?? false);
    }
    unset($map);

    return [
        'balance' => coin_get_balance($conn, $userId),
        'maps' => $catalog,
        'display_name' => coin_get_user_display_name($conn, $userId),
    ];
}

function mining_unlock_map(mysqli $conn, $userId, string $mapKey): array {
    $catalog = mining_get_map_catalog();
    if (!isset($catalog[$mapKey])) {
        return ['success' => false, 'message' => 'Unknown map.'];
    }

    $map = $catalog[$mapKey];
    $progress = mining_get_progress_map($conn, $userId);
    $state = $progress[$mapKey];

    if ($state['is_unlocked']) {
        return [
            'success' => true,
            'message' => 'Map already unlocked.',
            'balance' => coin_get_balance($conn, $userId),
            'map_key' => $mapKey,
        ];
    }

    $requiredUnlock = $map['required_unlock'] ?? null;
    if ($requiredUnlock !== null && empty($progress[$requiredUnlock]['is_unlocked'])) {
        $requiredName = $catalog[$requiredUnlock]['name'];
        return [
            'success' => false,
            'message' => "You must unlock {$requiredName} first.",
            'balance' => coin_get_balance($conn, $userId),
        ];
    }

    try {
        $conn->begin_transaction();
        $userIdParam = mining_user_id_param($userId);

        $transaction = coin_add_transaction_internal(
            $conn,
            $userId,
            'mining',
            'map_unlock',
            $map['ref_id'],
            -$map['cost'],
            "unlock_{$mapKey}",
            [
                'map_key' => $mapKey,
                'map_name' => $map['name'],
                'cost' => $map['cost'],
            ]
        );

        $stmt = $conn->prepare("
            UPDATE mining_map_progress
            SET is_unlocked = 1,
                unlocked_at = COALESCE(unlocked_at, NOW()),
                updated_at = NOW()
            WHERE user_id = ? AND map_key = ?
        ");
        $stmt->bind_param("ss", $userIdParam, $mapKey);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("
            INSERT IGNORE INTO user_game_unlocks
            (user_id, game_code, unlock_type, unlock_key, cost_coins, unlocked_at)
            VALUES (?, 'mining', 'map', ?, ?, NOW())
        ");
        $stmt->bind_param("ssi", $userIdParam, $mapKey, $map['cost']);
        $stmt->execute();
        $stmt->close();

        $conn->commit();

        return [
            'success' => true,
            'message' => "Unlocked {$map['name']}.",
            'balance' => (int)$transaction['balance'],
            'map_key' => $mapKey,
        ];
    } catch (Throwable $e) {
        $conn->rollback();
        return [
            'success' => false,
            'message' => $e->getMessage() === 'Insufficient coin balance.'
                ? 'Not enough coins.'
                : 'Failed to unlock map.',
            'balance' => coin_get_balance($conn, $userId),
        ];
    }
}

function mining_mark_map_cleared(mysqli $conn, $userId, string $mapKey): array {
    $catalog = mining_get_map_catalog();
    if (!isset($catalog[$mapKey])) {
        return ['success' => false, 'message' => 'Unknown map.'];
    }

    mining_ensure_progress($conn, $userId);
    $userIdParam = mining_user_id_param($userId);

    $stmt = $conn->prepare("
        UPDATE mining_map_progress
        SET is_unlocked = 1,
            unlocked_at = COALESCE(unlocked_at, NOW()),
            is_cleared = 1,
            cleared_at = COALESCE(cleared_at, NOW()),
            last_played_at = NOW(),
            updated_at = NOW()
        WHERE user_id = ? AND map_key = ?
    ");
    $stmt->bind_param("ss", $userIdParam, $mapKey);
    $stmt->execute();
    $stmt->close();

    return [
        'success' => true,
        'message' => "{$catalog[$mapKey]['name']} cleared.",
        'balance' => coin_get_balance($conn, $userId),
    ];
}

function mining_record_map_play(mysqli $conn, $userId, string $mapKey): void {
    mining_ensure_progress($conn, $userId);
    $userIdParam = mining_user_id_param($userId);
    $stmt = $conn->prepare("
        UPDATE mining_map_progress
        SET last_played_at = NOW(), updated_at = NOW()
        WHERE user_id = ? AND map_key = ?
    ");
    $stmt->bind_param("ss", $userIdParam, $mapKey);
    $stmt->execute();
    $stmt->close();
}

function mining_award_single_ore(
    mysqli $conn,
    $userId,
    string $runId,
    int $sequence,
    string $mapKey,
    string $oreType,
    int $amount
): array {
    $sourceRefId = ((int)preg_replace('/\D+/', '', $runId) * 100) + max(1, $sequence);

    try {
        $conn->begin_transaction();
        $result = coin_add_transaction_internal(
            $conn,
            $userId,
            'mining',
            'single_ore',
            $sourceRefId,
            $amount,
            "single_ore_{$oreType}",
            [
                'map_key' => $mapKey,
                'ore_type' => $oreType,
                'sequence' => $sequence,
                'run_id' => $runId,
            ]
        );
        $conn->commit();

        return [
            'success' => true,
            'balance' => (int)$result['balance'],
            'applied' => (bool)$result['applied'],
        ];
    } catch (Throwable $e) {
        $conn->rollback();
        return [
            'success' => false,
            'message' => 'Failed to update mining coins.',
            'balance' => coin_get_balance($conn, $userId),
        ];
    }
}

function mining_award_pvp_win(mysqli $conn, $userId, string $roomId): array {
    $reward = coin_get_reward_amount($conn, 'mining', 'pvp', null, 'win');
    if ($reward <= 0) {
        $reward = 20;
    }

    try {
        $conn->begin_transaction();
        $result = coin_add_transaction_internal(
            $conn,
            $userId,
            'mining',
            'pvp_win',
            mining_ref_id_from_string($roomId),
            $reward,
            'mining_pvp_win',
            ['room_id' => $roomId]
        );
        $conn->commit();

        return [
            'success' => true,
            'granted' => (bool)$result['applied'],
            'reward_amount' => $result['applied'] ? $reward : 0,
            'balance' => (int)$result['balance'],
        ];
    } catch (Throwable $e) {
        $conn->rollback();
        return [
            'success' => false,
            'message' => 'Failed to settle PvP reward.',
            'balance' => coin_get_balance($conn, $userId),
        ];
    }
}

function mining_get_claimed_achievement_ids(mysqli $conn, $userId): array {
    $userIdParam = mining_user_id_param($userId);
    $catalog = mining_get_achievement_catalog();
    $refToId = [];
    foreach ($catalog as $achievementId => $achievement) {
        $refToId[(int)$achievement['ref_id']] = $achievementId;
    }

    $stmt = $conn->prepare("
        SELECT source_ref_id
        FROM coin_ledger
        WHERE user_id = ?
          AND source_game = 'mining'
          AND source_type = 'achievement_reward'
    ");
    $stmt->bind_param("s", $userIdParam);
    $stmt->execute();
    $res = $stmt->get_result();

    $claimed = [];
    while ($row = $res->fetch_assoc()) {
        $refId = (int)$row['source_ref_id'];
        if (isset($refToId[$refId])) {
            $claimed[] = $refToId[$refId];
        }
    }
    $stmt->close();

    return array_values(array_unique($claimed));
}

function mining_claim_achievement_reward(mysqli $conn, $userId, string $achievementId): array {
    $catalog = mining_get_achievement_catalog();
    if (!isset($catalog[$achievementId])) {
        return [
            'success' => false,
            'message' => 'Unknown achievement reward.',
            'balance' => coin_get_balance($conn, $userId),
        ];
    }

    $reward = (int)$catalog[$achievementId]['reward'];
    $refId = (int)$catalog[$achievementId]['ref_id'];

    try {
        $conn->begin_transaction();
        $result = coin_add_transaction_internal(
            $conn,
            $userId,
            'mining',
            'achievement_reward',
            $refId,
            $reward,
            "mining_achievement_{$achievementId}",
            ['achievement_id' => $achievementId]
        );
        $conn->commit();

        return [
            'success' => true,
            'granted' => (bool)$result['applied'],
            'reward_amount' => $result['applied'] ? $reward : 0,
            'balance' => (int)$result['balance'],
            'message' => $result['applied']
                ? "Achievement reward claimed: +{$reward} coins."
                : 'This achievement reward has already been claimed.',
        ];
    } catch (Throwable $e) {
        $conn->rollback();
        return [
            'success' => false,
            'message' => 'Failed to claim achievement reward.',
            'balance' => coin_get_balance($conn, $userId),
        ];
    }
}
