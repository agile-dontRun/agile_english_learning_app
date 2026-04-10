<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

function mm_current_user_id(): int {
    return (int)$_SESSION['user_id'];
}

function mm_h($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function mm_get_user(mysqli $conn, int $userId): ?array {
    $stmt = $conn->prepare("SELECT user_id, username, nickname FROM users WHERE user_id = ? LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function mm_ensure_profile(mysqli $conn, int $userId): void {
    $stmt = $conn->prepare("
        INSERT IGNORE INTO memory_player_profiles
        (user_id, coins, total_matches, total_wins, best_time_seconds)
        VALUES (?, 0, 0, 0, NULL)
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();
}

function mm_get_profile(mysqli $conn, int $userId): ?array {
    mm_ensure_profile($conn, $userId);
    $stmt = $conn->prepare("SELECT * FROM memory_player_profiles WHERE user_id = ? LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function mm_get_mode(mysqli $conn, int $modeId): ?array {
    $stmt = $conn->prepare("SELECT * FROM memory_game_modes WHERE mode_id = ? LIMIT 1");
    $stmt->bind_param("i", $modeId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function mm_get_match(mysqli $conn, int $matchId): ?array {
    $stmt = $conn->prepare("SELECT * FROM memory_matches WHERE match_id = ? LIMIT 1");
    $stmt->bind_param("i", $matchId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function mm_get_match_player(mysqli $conn, int $matchId, int $userId): ?array {
    $stmt = $conn->prepare("SELECT * FROM memory_match_players WHERE match_id = ? AND user_id = ? LIMIT 1");
    $stmt->bind_param("ii", $matchId, $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function mm_get_match_player_by_id(mysqli $conn, int $matchPlayerId): ?array {
    $stmt = $conn->prepare("SELECT * FROM memory_match_players WHERE match_player_id = ? LIMIT 1");
    $stmt->bind_param("i", $matchPlayerId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function mm_create_match(mysqli $conn, int $userId, int $modeId): ?int {
    mm_ensure_profile($conn, $userId);
    $mode = mm_get_mode($conn, $modeId);
    if (!$mode) return null;

    $stmt = $conn->prepare("
        INSERT INTO memory_matches
        (mode_id, status, start_time, end_time, winner_user_id, created_at)
        VALUES (?, 'waiting', NULL, NULL, NULL, NOW())
    ");
    $stmt->bind_param("i", $modeId);
    if (!$stmt->execute()) {
        $stmt->close();
        return null;
    }
    $matchId = $stmt->insert_id;
    $stmt->close();

    $stmt = $conn->prepare("
        INSERT INTO memory_match_players
        (match_id, user_id, player_slot, score, matched_pairs_count, flip_count, finished_all, finished_at, is_winner, created_at)
        VALUES (?, ?, 1, 0, 0, 0, 0, NULL, 0, NOW())
    ");
    $stmt->bind_param("ii", $matchId, $userId);
    $stmt->execute();
    $stmt->close();

    return $matchId;
}

function mm_join_match(mysqli $conn, int $matchId, int $userId): bool {
    mm_ensure_profile($conn, $userId);

    $match = mm_get_match($conn, $matchId);
    if (!$match || $match['status'] !== 'waiting') return false;

    $existing = mm_get_match_player($conn, $matchId, $userId);
    if ($existing) return true;

    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM memory_match_players WHERE match_id = ?");
    $stmt->bind_param("i", $matchId);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ((int)$count['total'] >= 2) return false;

    $stmt = $conn->prepare("
        INSERT INTO memory_match_players
        (match_id, user_id, player_slot, score, matched_pairs_count, flip_count, finished_all, finished_at, is_winner, created_at)
        VALUES (?, ?, 2, 0, 0, 0, 0, NULL, 0, NOW())
    ");
    $stmt->bind_param("ii", $matchId, $userId);
    if (!$stmt->execute()) {
        $stmt->close();
        return false;
    }
    $stmt->close();

    if (!mm_generate_match_content($conn, $matchId)) {
        return false;
    }

    $stmt = $conn->prepare("UPDATE memory_matches SET status = 'in_progress', start_time = NOW() WHERE match_id = ?");
    $stmt->bind_param("i", $matchId);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("
        UPDATE memory_player_profiles
        SET total_matches = total_matches + 1
        WHERE user_id IN (SELECT user_id FROM memory_match_players WHERE match_id = ?)
    ");
    $stmt->bind_param("i", $matchId);
    $stmt->execute();
    $stmt->close();

    return true;
}

function mm_generate_match_content(mysqli $conn, int $matchId): bool {
    $match = mm_get_match($conn, $matchId);
    if (!$match) return false;

    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM memory_match_word_pairs WHERE match_id = ?");
    $stmt->bind_param("i", $matchId);
    $stmt->execute();
    $exists = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ((int)$exists['total'] > 0) return true;

    $mode = mm_get_mode($conn, (int)$match['mode_id']);
    if (!$mode) return false;

    $pairCount = (int)$mode['pair_count'];
    $difficulty = $mode['difficulty_level'];

    $stmt = $conn->prepare("
        SELECT word_id
        FROM words
        WHERE audio_url IS NOT NULL
          AND audio_url <> ''
          AND difficulty_level = ?
        ORDER BY RAND()
        LIMIT ?
    ");
    $stmt->bind_param("si", $difficulty, $pairCount);
    $stmt->execute();
    $res = $stmt->get_result();

    $wordIds = [];
    while ($row = $res->fetch_assoc()) {
        $wordIds[] = (int)$row['word_id'];
    }
    $stmt->close();

    if (count($wordIds) < $pairCount) {
        $stmt = $conn->prepare("
            SELECT word_id
            FROM words
            WHERE audio_url IS NOT NULL
              AND audio_url <> ''
            ORDER BY RAND()
            LIMIT ?
        ");
        $stmt->bind_param("i", $pairCount);
        $stmt->execute();
        $res = $stmt->get_result();

        $wordIds = [];
        while ($row = $res->fetch_assoc()) {
            $wordIds[] = (int)$row['word_id'];
        }
        $stmt->close();
    }

    if (count($wordIds) < $pairCount) return false;

    $pairIds = [];
    $pairNo = 1;
    foreach ($wordIds as $wordId) {
        $stmt = $conn->prepare("
            INSERT INTO memory_match_word_pairs (match_id, pair_no, word_id, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->bind_param("iii", $matchId, $pairNo, $wordId);
        $stmt->execute();
        $pairIds[] = $stmt->insert_id;
        $stmt->close();
        $pairNo++;
    }

    $stmt = $conn->prepare("
        SELECT match_player_id
        FROM memory_match_players
        WHERE match_id = ?
        ORDER BY player_slot ASC
    ");
    $stmt->bind_param("i", $matchId);
    $stmt->execute();
    $res = $stmt->get_result();

    $matchPlayerIds = [];
    while ($row = $res->fetch_assoc()) {
        $matchPlayerIds[] = (int)$row['match_player_id'];
    }
    $stmt->close();

    foreach ($matchPlayerIds as $matchPlayerId) {
        $deck = [];
        foreach ($pairIds as $pairId) {
            $deck[] = ['pair_id' => $pairId, 'card_type' => 'word'];
            $deck[] = ['pair_id' => $pairId, 'card_type' => 'audio'];
        }

        shuffle($deck);

        $position = 1;
        foreach ($deck as $card) {
            $stmt = $conn->prepare("
                INSERT INTO memory_player_cards
                (match_player_id, pair_id, card_type, position_no, is_face_up, is_matched, created_at, updated_at)
                VALUES (?, ?, ?, ?, 0, 0, NOW(), NOW())
            ");
            $stmt->bind_param("iisi", $matchPlayerId, $card['pair_id'], $card['card_type'], $position);
            $stmt->execute();
            $stmt->close();
            $position++;
        }
    }

    return true;
}

function mm_match_expired(mysqli $conn, int $matchId): bool {
    $match = mm_get_match($conn, $matchId);
    if (!$match || $match['status'] !== 'in_progress' || !$match['start_time']) return false;

    $mode = mm_get_mode($conn, (int)$match['mode_id']);
    if (!$mode) return false;

    $start = strtotime($match['start_time']);
    if ($start === false) return false;

    return time() >= ($start + (int)$mode['time_limit_seconds']);
}

function mm_remaining_seconds(mysqli $conn, int $matchId): ?int {
    $match = mm_get_match($conn, $matchId);
    if (!$match || !$match['start_time']) return null;

    $mode = mm_get_mode($conn, (int)$match['mode_id']);
    if (!$mode) return null;

    $deadline = strtotime($match['start_time']) + (int)$mode['time_limit_seconds'];
    return max(0, $deadline - time());
}

function mm_get_open_turn(mysqli $conn, int $matchPlayerId): ?array {
    $stmt = $conn->prepare("
        SELECT *
        FROM memory_player_turns
        WHERE match_player_id = ?
          AND completed_at IS NULL
        ORDER BY turn_no DESC
        LIMIT 1
    ");
    $stmt->bind_param("i", $matchPlayerId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function mm_create_turn(mysqli $conn, int $matchPlayerId): ?int {
    $stmt = $conn->prepare("
        SELECT COALESCE(MAX(turn_no), 0) AS max_turn
        FROM memory_player_turns
        WHERE match_player_id = ?
    ");
    $stmt->bind_param("i", $matchPlayerId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $turnNo = (int)$row['max_turn'] + 1;

    $stmt = $conn->prepare("
        INSERT INTO memory_player_turns
        (match_player_id, turn_no, first_card_id, second_card_id, is_match, score_gained, started_at, completed_at)
        VALUES (?, ?, NULL, NULL, 0, 0, NOW(), NULL)
    ");
    $stmt->bind_param("ii", $matchPlayerId, $turnNo);
    if (!$stmt->execute()) {
        $stmt->close();
        return null;
    }
    $turnId = $stmt->insert_id;
    $stmt->close();

    return $turnId;
}

function mm_get_card_for_player(mysqli $conn, int $matchPlayerId, int $cardId): ?array {
    $sql = "
        SELECT c.*, p.word_id, w.english_word, w.audio_url
        FROM memory_player_cards c
        JOIN memory_match_word_pairs p ON c.pair_id = p.pair_id
        JOIN words w ON p.word_id = w.word_id
        WHERE c.match_player_id = ? AND c.card_id = ?
        LIMIT 1
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $matchPlayerId, $cardId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function mm_render_card_payload(array $card): array {
    return [
        'card_id' => (int)$card['card_id'],
        'pair_id' => (int)$card['pair_id'],
        'card_type' => $card['card_type'],
        'position_no' => (int)$card['position_no'],
        'is_face_up' => (bool)$card['is_face_up'],
        'is_matched' => (bool)$card['is_matched'],
        'display' => $card['card_type'] === 'word' ? $card['english_word'] : '🔊 ',
        'audio_url' => $card['card_type'] === 'audio' ? $card['audio_url'] : null
    ];
}

function mm_submit_flip(mysqli $conn, int $matchId, int $userId, int $cardId): array {
    $match = mm_get_match($conn, $matchId);
    if (!$match) return ['ok' => false, 'message' => 'Match not found.'];
    if ($match['status'] !== 'in_progress') return ['ok' => false, 'message' => 'Match is not active.'];

    if (mm_match_expired($conn, $matchId)) {
        mm_settle_match($conn, $matchId);
        return ['ok' => false, 'message' => 'Time is up.'];
    }

    $mode = mm_get_mode($conn, (int)$match['mode_id']);
    $matchPlayer = mm_get_match_player($conn, $matchId, $userId);
    if (!$matchPlayer) return ['ok' => false, 'message' => 'You are not in this match.'];
    if ((int)$matchPlayer['finished_all'] === 1) return ['ok' => false, 'message' => 'You already finished your board.'];

    $card = mm_get_card_for_player($conn, (int)$matchPlayer['match_player_id'], $cardId);
    if (!$card) return ['ok' => false, 'message' => 'Card not found.'];
    if ((int)$card['is_matched'] === 1) return ['ok' => false, 'message' => 'Card already matched.'];

    $openTurn = mm_get_open_turn($conn, (int)$matchPlayer['match_player_id']);

    if (!$openTurn) {
        $turnId = mm_create_turn($conn, (int)$matchPlayer['match_player_id']);
        if (!$turnId) return ['ok' => false, 'message' => 'Failed to create turn.'];

        $stmt = $conn->prepare("
            UPDATE memory_player_cards
            SET is_face_up = 1, updated_at = NOW()
            WHERE card_id = ?
        ");
        $stmt->bind_param("i", $cardId);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("UPDATE memory_player_turns SET first_card_id = ? WHERE turn_id = ?");
        $stmt->bind_param("ii", $cardId, $turnId);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("
            INSERT INTO memory_flip_logs (turn_id, match_player_id, card_id, flip_order, flipped_at)
            VALUES (?, ?, ?, 1, NOW())
        ");
        $stmt->bind_param("iii", $turnId, $matchPlayer['match_player_id'], $cardId);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("UPDATE memory_match_players SET flip_count = flip_count + 1 WHERE match_player_id = ?");
        $stmt->bind_param("i", $matchPlayer['match_player_id']);
        $stmt->execute();
        $stmt->close();

        $card = mm_get_card_for_player($conn, (int)$matchPlayer['match_player_id'], $cardId);

        return [
            'ok' => true,
            'message' => 'First card flipped.',
            'phase' => 'first',
            'card' => mm_render_card_payload($card)
        ];
    }

    if (!empty($openTurn['first_card_id']) && empty($openTurn['second_card_id'])) {
        if ((int)$openTurn['first_card_id'] === $cardId) {
            return ['ok' => false, 'message' => 'You cannot choose the same card twice.'];
        }

        $firstCard = mm_get_card_for_player($conn, (int)$matchPlayer['match_player_id'], (int)$openTurn['first_card_id']);
        if (!$firstCard) return ['ok' => false, 'message' => 'First card missing.'];

        $stmt = $conn->prepare("
            UPDATE memory_player_cards
            SET is_face_up = 1, updated_at = NOW()
            WHERE card_id = ?
        ");
        $stmt->bind_param("i", $cardId);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("UPDATE memory_player_turns SET second_card_id = ? WHERE turn_id = ?");
        $stmt->bind_param("ii", $cardId, $openTurn['turn_id']);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("
            INSERT INTO memory_flip_logs (turn_id, match_player_id, card_id, flip_order, flipped_at)
            VALUES (?, ?, ?, 2, NOW())
        ");
        $stmt->bind_param("iii", $openTurn['turn_id'], $matchPlayer['match_player_id'], $cardId);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("UPDATE memory_match_players SET flip_count = flip_count + 1 WHERE match_player_id = ?");
        $stmt->bind_param("i", $matchPlayer['match_player_id']);
        $stmt->execute();
        $stmt->close();

        $secondCard = mm_get_card_for_player($conn, (int)$matchPlayer['match_player_id'], $cardId);

        $isMatch = ((int)$firstCard['pair_id'] === (int)$secondCard['pair_id'])
            && ($firstCard['card_type'] !== $secondCard['card_type']);

        if ($isMatch) {
            $stmt = $conn->prepare("
                UPDATE memory_player_cards
                SET is_face_up = 1, is_matched = 1, updated_at = NOW()
                WHERE card_id IN (?, ?)
            ");
            $stmt->bind_param("ii", $firstCard['card_id'], $secondCard['card_id']);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("
                UPDATE memory_player_turns
                SET is_match = 1, score_gained = 1, completed_at = NOW()
                WHERE turn_id = ?
            ");
            $stmt->bind_param("i", $openTurn['turn_id']);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("
                UPDATE memory_match_players
                SET score = score + 1, matched_pairs_count = matched_pairs_count + 1
                WHERE match_player_id = ?
            ");
            $stmt->bind_param("i", $matchPlayer['match_player_id']);
            $stmt->execute();
            $stmt->close();

            $updatedPlayer = mm_get_match_player($conn, $matchId, $userId);
            if ($updatedPlayer && (int)$updatedPlayer['matched_pairs_count'] >= (int)$mode['pair_count']) {
    $stmt = $conn->prepare("
        UPDATE memory_match_players
        SET finished_all = 1, finished_at = NOW()
        WHERE match_player_id = ?
    ");
    $stmt->bind_param("i", $updatedPlayer['match_player_id']);
    $stmt->execute();
    $stmt->close();

    mm_settle_match($conn, $matchId);
}

            $firstCard = mm_get_card_for_player($conn, (int)$matchPlayer['match_player_id'], $firstCard['card_id']);
            $secondCard = mm_get_card_for_player($conn, (int)$matchPlayer['match_player_id'], $secondCard['card_id']);

            return [
                'ok' => true,
                'message' => 'Matched!',
                'phase' => 'second',
                'is_match' => true,
                'first_card' => mm_render_card_payload($firstCard),
                'second_card' => mm_render_card_payload($secondCard)
            ];
        }

        $stmt = $conn->prepare("
    UPDATE memory_player_turns
    SET is_match = 0, score_gained = 0, completed_at = NOW()
    WHERE turn_id = ?
");
$stmt->bind_param("i", $openTurn['turn_id']);
$stmt->execute();
$stmt->close();

$stmt = $conn->prepare("
    UPDATE memory_player_cards
    SET is_face_up = 0, updated_at = NOW()
    WHERE card_id IN (?, ?)
      AND is_matched = 0
");
$stmt->bind_param("ii", $firstCard['card_id'], $secondCard['card_id']);
$stmt->execute();
$stmt->close();

return [
    'ok' => true,
    'message' => 'Not matched.',
    'phase' => 'second',
    'is_match' => false,
    'first_card' => mm_render_card_payload($firstCard),
    'second_card' => mm_render_card_payload($secondCard)
];
    }

    return ['ok' => false, 'message' => 'Turn state invalid.'];
}

function mm_get_board_state(mysqli $conn, int $matchId, int $userId): array {
    $match = mm_get_match($conn, $matchId);
    if (!$match) {
        return ['ok' => false, 'message' => 'Match not found.'];
    }

    if ($match['status'] === 'in_progress' && mm_match_expired($conn, $matchId)) {
        mm_settle_match($conn, $matchId);
        $match = mm_get_match($conn, $matchId);
    }

    $mode = mm_get_mode($conn, (int)$match['mode_id']);
    $me = mm_get_match_player($conn, $matchId, $userId);
    if (!$me) {
        return ['ok' => false, 'message' => 'You are not in this match.'];
    }

    $stmt = $conn->prepare("
        SELECT mp.*, u.username, u.nickname
        FROM memory_match_players mp
        JOIN users u ON mp.user_id = u.user_id
        WHERE mp.match_id = ? AND mp.user_id <> ?
        LIMIT 1
    ");
    $stmt->bind_param("ii", $matchId, $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $opponent = $res->fetch_assoc();
    $stmt->close();

    $myCards = [];
    $stmt = $conn->prepare("
        SELECT c.*, p.word_id, w.english_word, w.audio_url
        FROM memory_player_cards c
        JOIN memory_match_word_pairs p ON c.pair_id = p.pair_id
        JOIN words w ON p.word_id = w.word_id
        WHERE c.match_player_id = ?
        ORDER BY c.position_no ASC
    ");
    $stmt->bind_param("i", $me['match_player_id']);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $payload = [
            'card_id' => (int)$row['card_id'],
            'position_no' => (int)$row['position_no'],
            'card_type' => $row['card_type'],
            'is_face_up' => (bool)$row['is_face_up'],
            'is_matched' => (bool)$row['is_matched'],
            'display' => null,
            'audio_url' => null
        ];

        if ((int)$row['is_face_up'] === 1 || (int)$row['is_matched'] === 1) {
            if ($row['card_type'] === 'word') {
                $payload['display'] = $row['english_word'];
            } else {
                $payload['display'] = '🔊 ';
                $payload['audio_url'] = $row['audio_url'];
            }
        }

        $myCards[] = $payload;
    }
    $stmt->close();

    $opponentCards = [];
    if ($opponent) {
        $stmt = $conn->prepare("
            SELECT c.*, p.word_id, w.english_word, w.audio_url
            FROM memory_player_cards c
            JOIN memory_match_word_pairs p ON c.pair_id = p.pair_id
            JOIN words w ON p.word_id = w.word_id
            WHERE c.match_player_id = ?
            ORDER BY c.position_no ASC
        ");
        $stmt->bind_param("i", $opponent['match_player_id']);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $payload = [
                'card_id' => (int)$row['card_id'],
                'position_no' => (int)$row['position_no'],
                'card_type' => $row['card_type'],
                'is_face_up' => (bool)$row['is_face_up'],
                'is_matched' => (bool)$row['is_matched'],
                'display' => null,
                'audio_url' => null
            ];

            if ((int)$row['is_face_up'] === 1 || (int)$row['is_matched'] === 1) {
                if ($row['card_type'] === 'word') {
                    $payload['display'] = $row['english_word'];
                } else {
                    $payload['display'] = '🔊 ';
                    $payload['audio_url'] = $row['audio_url'];
                }
            }

            $opponentCards[] = $payload;
        }
        $stmt->close();
    }

    return [
        'ok' => true,
        'match' => [
            'match_id' => (int)$match['match_id'],
            'status' => $match['status'],
            'winner_user_id' => $match['winner_user_id'] ? (int)$match['winner_user_id'] : null,
            'remaining_seconds' => mm_remaining_seconds($conn, $matchId)
        ],
        'mode' => [
            'mode_name' => $mode['mode_name'],
            'pair_count' => (int)$mode['pair_count'],
            'time_limit_seconds' => (int)$mode['time_limit_seconds'],
            'win_rule' => $mode['win_rule']
        ],
        'me' => [
            'match_player_id' => (int)$me['match_player_id'],
            'user_id' => (int)$me['user_id'],
            'score' => (int)$me['score'],
            'matched_pairs_count' => (int)$me['matched_pairs_count'],
            'flip_count' => (int)$me['flip_count'],
            'finished_all' => (bool)$me['finished_all']
        ],
        'opponent' => $opponent ? [
            'match_player_id' => (int)$opponent['match_player_id'],
            'user_id' => (int)$opponent['user_id'],
            'nickname' => $opponent['nickname'] ?: $opponent['username'],
            'score' => (int)$opponent['score'],
            'matched_pairs_count' => (int)$opponent['matched_pairs_count'],
            'flip_count' => (int)$opponent['flip_count'],
            'finished_all' => (bool)$opponent['finished_all']
        ] : null,
        'my_cards' => $myCards,
        'opponent_cards' => $opponentCards
    ];
}

function mm_settle_match(mysqli $conn, int $matchId): bool {
    $match = mm_get_match($conn, $matchId);
    if (!$match || $match['status'] === 'finished') return false;

    $stmt = $conn->prepare("
        SELECT *
        FROM memory_match_players
        WHERE match_id = ?
        ORDER BY player_slot ASC
    ");
    $stmt->bind_param("i", $matchId);
    $stmt->execute();
    $res = $stmt->get_result();

    $players = [];
    while ($row = $res->fetch_assoc()) {
        $players[] = $row;
    }
    $stmt->close();

    if (count($players) === 0) return false;

    $winnerUserId = null;

    // 1. 先看有没有人已经完成全部
    $finishedPlayers = array_filter($players, function($p) {
        return (int)$p['finished_all'] === 1 && !empty($p['finished_at']);
    });

    if (!empty($finishedPlayers)) {
        usort($finishedPlayers, function($a, $b) {
            return strtotime($a['finished_at']) <=> strtotime($b['finished_at']);
        });
        $winnerUserId = (int)$finishedPlayers[0]['user_id'];
    } else {
        // 2. 没人翻完，就比 score
        usort($players, function($a, $b) {
            if ((int)$b['score'] !== (int)$a['score']) {
                return (int)$b['score'] <=> (int)$a['score'];
            }

            // 3. score 一样，比 flip_count，少者优先
            if ((int)$a['flip_count'] !== (int)$b['flip_count']) {
                return (int)$a['flip_count'] <=> (int)$b['flip_count'];
            }

            return 0;
        });

        // 判断是否完全平局
        if (count($players) >= 2
            && (int)$players[0]['score'] === (int)$players[1]['score']
            && (int)$players[0]['flip_count'] === (int)$players[1]['flip_count']) {
            $winnerUserId = null; // 平局
        } else {
            $winnerUserId = (int)$players[0]['user_id'];
        }
    }

    foreach ($players as $player) {
        $isWinner = ($winnerUserId !== null && (int)$player['user_id'] === $winnerUserId) ? 1 : 0;

        $stmt = $conn->prepare("
            UPDATE memory_match_players
            SET is_winner = ?
            WHERE match_player_id = ?
        ");
        $stmt->bind_param("ii", $isWinner, $player['match_player_id']);
        $stmt->execute();
        $stmt->close();

        if ($isWinner) {
            $stmt = $conn->prepare("
                UPDATE memory_player_profiles
                SET total_wins = total_wins + 1
                WHERE user_id = ?
            ");
            $stmt->bind_param("i", $player['user_id']);
            $stmt->execute();
            $stmt->close();

            if (!empty($player['finished_at']) && !empty($match['start_time'])) {
                $elapsed = strtotime($player['finished_at']) - strtotime($match['start_time']);
                if ($elapsed > 0) {
                    $stmt = $conn->prepare("
                        UPDATE memory_player_profiles
                        SET best_time_seconds =
                            CASE
                                WHEN best_time_seconds IS NULL OR best_time_seconds > ? THEN ?
                                ELSE best_time_seconds
                            END
                        WHERE user_id = ?
                    ");
                    $stmt->bind_param("iii", $elapsed, $elapsed, $player['user_id']);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }
    }

    $stmt = $conn->prepare("
    UPDATE memory_matches
    SET status = 'finished',
        end_time = NOW(),
        winner_user_id = ?
    WHERE match_id = ?
");
$stmt->bind_param("ii", $winnerUserId, $matchId);
$stmt->execute();
$stmt->close();

mm_apply_memory_coin_rewards($conn, $matchId);

return true;
}
function mm_create_single_match(mysqli $conn, int $userId, int $modeId): ?int {
    mm_ensure_profile($conn, $userId);
    $mode = mm_get_mode($conn, $modeId);
    if (!$mode) return null;

    $stmt = $conn->prepare("
        INSERT INTO memory_matches
        (mode_id, status, start_time, end_time, winner_user_id, created_at)
        VALUES (?, 'in_progress', NOW(), NULL, NULL, NOW())
    ");
    $stmt->bind_param("i", $modeId);
    if (!$stmt->execute()) {
        $stmt->close();
        return null;
    }
    $matchId = $stmt->insert_id;
    $stmt->close();

    $stmt = $conn->prepare("
        INSERT INTO memory_match_players
        (match_id, user_id, player_slot, score, matched_pairs_count, flip_count, finished_all, finished_at, is_winner, created_at)
        VALUES (?, ?, 1, 0, 0, 0, 0, NULL, 0, NOW())
    ");
    $stmt->bind_param("ii", $matchId, $userId);
    if (!$stmt->execute()) {
        $stmt->close();
        return null;
    }
    $stmt->close();

    if (!mm_generate_match_content($conn, $matchId)) {
        return null;
    }

    $stmt = $conn->prepare("
        UPDATE memory_player_profiles
        SET total_matches = total_matches + 1
        WHERE user_id = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();

    return $matchId;
}

function mm_build_single_board_cells(int $matchId, array $cards, int $gridSize = 64): array {
    $slots = [];

    for ($i = 1; $i <= $gridSize; $i++) {
        $hash = md5($matchId . '_slot_' . $i);
        $slots[] = [
            'cell_no' => $i,
            'sort_key' => $hash
        ];
    }

    usort($slots, function ($a, $b) {
        return strcmp($a['sort_key'], $b['sort_key']);
    });

    $cells = [];
    foreach ($cards as $index => $card) {
        if (isset($slots[$index])) {
            $cellNo = $slots[$index]['cell_no'];
            $cells[$cellNo] = $card;
        }
    }

    $board = [];
    for ($i = 1; $i <= $gridSize; $i++) {
        $board[] = [
            'cell_no' => $i,
            'has_card' => isset($cells[$i]),
            'card' => $cells[$i] ?? null
        ];
    }

    return $board;
}

function mm_get_single_board_state(mysqli $conn, int $matchId, int $userId): array {
    $base = mm_get_board_state($conn, $matchId, $userId);
    if (!$base['ok']) return $base;

    $base['board_cells'] = mm_build_single_board_cells($matchId, $base['my_cards'], 64);
    return $base;
}

function mm_settle_single_match(mysqli $conn, int $matchId, int $userId): bool {
    $match = mm_get_match($conn, $matchId);
    if (!$match || $match['status'] === 'finished') return false;

    $player = mm_get_match_player($conn, $matchId, $userId);
    if (!$player) return false;

    $stmt = $conn->prepare("
        UPDATE memory_match_players
        SET is_winner = 1
        WHERE match_id = ? AND user_id = ?
    ");
    $stmt->bind_param("ii", $matchId, $userId);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("
        UPDATE memory_player_profiles
        SET total_wins = total_wins + 1
        WHERE user_id = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();

    $player = mm_get_match_player($conn, $matchId, $userId);
    $match = mm_get_match($conn, $matchId);

    if (!empty($player['finished_at']) && !empty($match['start_time'])) {
        $elapsed = strtotime($player['finished_at']) - strtotime($match['start_time']);
        if ($elapsed > 0) {
            $stmt = $conn->prepare("
                UPDATE memory_player_profiles
                SET best_time_seconds =
                    CASE
                        WHEN best_time_seconds IS NULL OR best_time_seconds > ? THEN ?
                        ELSE best_time_seconds
                    END
                WHERE user_id = ?
            ");
            $stmt->bind_param("iii", $elapsed, $elapsed, $userId);
            $stmt->execute();
            $stmt->close();
        }
    }

    $stmt = $conn->prepare("
    UPDATE memory_matches
    SET status = 'finished', end_time = NOW(), winner_user_id = ?
    WHERE match_id = ?
");
$stmt->bind_param("ii", $userId, $matchId);
$stmt->execute();
$stmt->close();

mm_apply_memory_coin_rewards($conn, $matchId);

return true;

    return true;
}
function mm_get_dual_board_state(mysqli $conn, int $matchId, int $userId): array {
    $match = mm_get_match($conn, $matchId);
    if (!$match) {
        return ['ok' => false, 'message' => 'Match not found.'];
    }

    if ($match['status'] === 'in_progress' && mm_match_expired($conn, $matchId)) {
        mm_settle_match($conn, $matchId);
        $match = mm_get_match($conn, $matchId);
    }

    $mode = mm_get_mode($conn, (int)$match['mode_id']);
    $me = mm_get_match_player($conn, $matchId, $userId);
    if (!$me) {
        return ['ok' => false, 'message' => 'You are not in this match.'];
    }

    $myUser = mm_get_user($conn, $userId);

    $stmt = $conn->prepare("
        SELECT mp.*, u.username, u.nickname
        FROM memory_match_players mp
        JOIN users u ON mp.user_id = u.user_id
        WHERE mp.match_id = ? AND mp.user_id <> ?
        LIMIT 1
    ");
    $stmt->bind_param("ii", $matchId, $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $opponent = $res->fetch_assoc();
    $stmt->close();

    $myCards = [];
    $stmt = $conn->prepare("
        SELECT c.*, p.word_id, w.english_word, w.audio_url
        FROM memory_player_cards c
        JOIN memory_match_word_pairs p ON c.pair_id = p.pair_id
        JOIN words w ON p.word_id = w.word_id
        WHERE c.match_player_id = ?
        ORDER BY c.position_no ASC
    ");
    $stmt->bind_param("i", $me['match_player_id']);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $myCards[] = [
            'card_id' => (int)$row['card_id'],
            'pair_id' => (int)$row['pair_id'],
            'position_no' => (int)$row['position_no'],
            'card_type' => $row['card_type'],
            'is_face_up' => (bool)$row['is_face_up'],
            'is_matched' => (bool)$row['is_matched'],
            'display' => ((int)$row['is_face_up'] === 1 || (int)$row['is_matched'] === 1)
                ? ($row['card_type'] === 'word' ? $row['english_word'] : '🔊 ')
                : null,
            'audio_url' => ($row['card_type'] === 'audio' && ((int)$row['is_face_up'] === 1 || (int)$row['is_matched'] === 1))
                ? $row['audio_url']
                : null
        ];
    }
    $stmt->close();

    $opponentCards = [];
    if ($opponent) {
        $stmt = $conn->prepare("
            SELECT c.*, p.word_id, w.english_word, w.audio_url
            FROM memory_player_cards c
            JOIN memory_match_word_pairs p ON c.pair_id = p.pair_id
            JOIN words w ON p.word_id = w.word_id
            WHERE c.match_player_id = ?
            ORDER BY c.position_no ASC
        ");
        $stmt->bind_param("i", $opponent['match_player_id']);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $opponentCards[] = [
                'card_id' => (int)$row['card_id'],
                'pair_id' => (int)$row['pair_id'],
                'position_no' => (int)$row['position_no'],
                'card_type' => $row['card_type'],
                'is_face_up' => (bool)$row['is_face_up'],
                'is_matched' => (bool)$row['is_matched'],
                'display' => ((int)$row['is_face_up'] === 1 || (int)$row['is_matched'] === 1)
                    ? ($row['card_type'] === 'word' ? $row['english_word'] : '🔊 ')
                    : null,
                'audio_url' => ($row['card_type'] === 'audio' && ((int)$row['is_face_up'] === 1 || (int)$row['is_matched'] === 1))
                    ? $row['audio_url']
                    : null
            ];
        }
        $stmt->close();
    }

    return [
        'ok' => true,
        'match' => [
            'match_id' => (int)$match['match_id'],
            'status' => $match['status'],
            'winner_user_id' => $match['winner_user_id'] ? (int)$match['winner_user_id'] : null,
            'remaining_seconds' => mm_remaining_seconds($conn, $matchId)
        ],
        'mode' => [
            'mode_name' => $mode['mode_name'],
            'pair_count' => (int)$mode['pair_count'],
            'time_limit_seconds' => (int)$mode['time_limit_seconds'],
            'win_rule' => $mode['win_rule']
        ],
        'me' => [
            'match_player_id' => (int)$me['match_player_id'],
            'user_id' => (int)$me['user_id'],
            'player_slot' => (int)$me['player_slot'],
            'nickname' => $myUser ? ($myUser['nickname'] ?: $myUser['username']) : 'You',
            'score' => (int)$me['score'],
            'matched_pairs_count' => (int)$me['matched_pairs_count'],
            'flip_count' => (int)$me['flip_count'],
            'finished_all' => (bool)$me['finished_all']
        ],
        'opponent' => $opponent ? [
            'match_player_id' => (int)$opponent['match_player_id'],
            'user_id' => (int)$opponent['user_id'],
            'player_slot' => (int)$opponent['player_slot'],
            'nickname' => $opponent['nickname'] ?: $opponent['username'],
            'score' => (int)$opponent['score'],
            'matched_pairs_count' => (int)$opponent['matched_pairs_count'],
            'flip_count' => (int)$opponent['flip_count'],
            'finished_all' => (bool)$opponent['finished_all']
        ] : null,
        'my_board_cells' => mm_build_player_board_cells($matchId, $myCards, 64),
        'opponent_board_cells' => mm_build_player_board_cells($matchId, $opponentCards, 64)
    ];
}
function mm_shared_board_slots(int $matchId, int $cardCount, int $gridSize = 64): array {
    $slots = [];

    for ($i = 1; $i <= $gridSize; $i++) {
        $slots[] = [
            'cell_no' => $i,
            'sort_key' => md5('match_' . $matchId . '_slot_' . $i)
        ];
    }

    usort($slots, function ($a, $b) {
        return strcmp($a['sort_key'], $b['sort_key']);
    });

    $picked = array_slice($slots, 0, $cardCount);

    usort($picked, function ($a, $b) {
        return $a['cell_no'] <=> $b['cell_no'];
    });

    return array_column($picked, 'cell_no');
}
function mm_build_player_board_cells(int $matchId, array $cards, int $gridSize = 64): array {
    $sharedSlots = mm_shared_board_slots($matchId, count($cards), $gridSize);

    usort($cards, function ($a, $b) {
        return $a['position_no'] <=> $b['position_no'];
    });

    $cells = [];
    foreach ($cards as $index => $card) {
        if (isset($sharedSlots[$index])) {
            $cellNo = $sharedSlots[$index];
            $cells[$cellNo] = $card;
        }
    }

    $board = [];
    for ($i = 1; $i <= $gridSize; $i++) {
        $board[] = [
            'cell_no' => $i,
            'has_card' => isset($cells[$i]),
            'card' => $cells[$i] ?? null
        ];
    }

    return $board;
}
function coin_ensure_wallet(mysqli $conn, int $userId): void {
    $stmt = $conn->prepare("
        INSERT IGNORE INTO coin_wallets (user_id, balance, total_earned, total_spent)
        VALUES (?, 0, 0, 0)
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();
}

function coin_get_balance(mysqli $conn, int $userId): int {
    coin_ensure_wallet($conn, $userId);

    $stmt = $conn->prepare("SELECT balance FROM coin_wallets WHERE user_id = ? LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    return $row ? (int)$row['balance'] : 0;
}

function coin_get_reward_amount(
    mysqli $conn,
    string $gameCode,
    string $modeCode,
    ?int $pairCount,
    string $outcomeCode,
    ?string $difficultyKey = null
): int {
    $sql = "
        SELECT reward_amount
        FROM coin_reward_rules
        WHERE game_code = ?
          AND mode_code = ?
          AND outcome_code = ?
          AND is_active = 1
          AND (pair_count = ? OR (pair_count IS NULL AND ? IS NULL))
          AND (difficulty_key = ? OR (difficulty_key IS NULL AND ? IS NULL))
        ORDER BY rule_id DESC
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "sssisss",
        $gameCode,
        $modeCode,
        $outcomeCode,
        $pairCount,
        $pairCount,
        $difficultyKey,
        $difficultyKey
    );
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    return $row ? (int)$row['reward_amount'] : 0;
}

function coin_add_transaction(
    mysqli $conn,
    int $userId,
    string $sourceGame,
    string $sourceType,
    ?int $sourceRefId,
    int $deltaAmount,
    string $reason,
    ?array $metadata = null
): bool {
    if ($deltaAmount === 0) {
        return true;
    }

    try {
        $conn->begin_transaction();

        coin_ensure_wallet($conn, $userId);

        $stmt = $conn->prepare("
            SELECT ledger_id
            FROM coin_ledger
            WHERE user_id = ?
              AND source_game = ?
              AND source_type = ?
              AND source_ref_id <=> ?
            LIMIT 1
            FOR UPDATE
        ");
        $stmt->bind_param("issi", $userId, $sourceGame, $sourceType, $sourceRefId);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($existing) {
            $conn->commit();
            return true; // 防止重复发奖
        }

        $stmt = $conn->prepare("
            SELECT balance, total_earned, total_spent
            FROM coin_wallets
            WHERE user_id = ?
            FOR UPDATE
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $wallet = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $oldBalance = (int)$wallet['balance'];
        $newBalance = $oldBalance + $deltaAmount;

        if ($newBalance < 0) {
            throw new Exception("Insufficient coin balance.");
        }

        $earnedInc = $deltaAmount > 0 ? $deltaAmount : 0;
        $spentInc = $deltaAmount < 0 ? abs($deltaAmount) : 0;

        $stmt = $conn->prepare("
            UPDATE coin_wallets
            SET balance = ?,
                total_earned = total_earned + ?,
                total_spent = total_spent + ?,
                updated_at = NOW()
            WHERE user_id = ?
        ");
        $stmt->bind_param("iiii", $newBalance, $earnedInc, $spentInc, $userId);
        $stmt->execute();
        $stmt->close();

        $metadataJson = $metadata ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : null;

        $stmt = $conn->prepare("
            INSERT INTO coin_ledger
            (user_id, source_game, source_type, source_ref_id, delta_amount, balance_after, reason, metadata_json)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "issiiiss",
            $userId,
            $sourceGame,
            $sourceType,
            $sourceRefId,
            $deltaAmount,
            $newBalance,
            $reason,
            $metadataJson
        );
        $stmt->execute();
        $stmt->close();

        /*
         * 为了兼容你现在翻牌页面还在读 memory_player_profiles.coins，
         * 这里顺手同步一份，先不强制你改前端。
         */
        mm_ensure_profile($conn, $userId);
        $stmt = $conn->prepare("
            UPDATE memory_player_profiles
            SET coins = ?
            WHERE user_id = ?
        ");
        $stmt->bind_param("ii", $newBalance, $userId);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        return true;
    } catch (Throwable $e) {
        $conn->rollback();
        error_log("coin_add_transaction failed: " . $e->getMessage());
        return false;
    }
}

function mm_apply_memory_coin_rewards(mysqli $conn, int $matchId): void {
    $match = mm_get_match($conn, $matchId);
    if (!$match) return;

    $mode = mm_get_mode($conn, (int)$match['mode_id']);
    if (!$mode) return;

    $stmt = $conn->prepare("
        SELECT *
        FROM memory_match_players
        WHERE match_id = ?
        ORDER BY player_slot ASC
    ");
    $stmt->bind_param("i", $matchId);
    $stmt->execute();
    $res = $stmt->get_result();

    $players = [];
    while ($row = $res->fetch_assoc()) {
        $players[] = $row;
    }
    $stmt->close();

    $pairCount = (int)$mode['pair_count'];
    $playerCount = count($players);

    foreach ($players as $player) {
        $userId = (int)$player['user_id'];
        $reward = 0;
        $outcomeCode = null;
        $modeCode = $playerCount >= 2 ? 'pvp' : 'solo';

        if ($playerCount === 1) {
            if ((int)$player['finished_all'] === 1 && (int)$player['is_winner'] === 1) {
                $outcomeCode = 'clear_win';
                $reward = coin_get_reward_amount($conn, 'memory', 'solo', $pairCount, $outcomeCode);
            }
        } else {
            if ($match['winner_user_id'] === null) {
                $outcomeCode = 'draw';
                $reward = coin_get_reward_amount($conn, 'memory', 'pvp', $pairCount, $outcomeCode);
            } elseif ((int)$player['is_winner'] === 1) {
                $outcomeCode = 'pvp_win';
                $reward = coin_get_reward_amount($conn, 'memory', 'pvp', $pairCount, $outcomeCode);
            } else {
                $outcomeCode = 'loss';
                $reward = 0;
            }
        }

        if ($reward > 0) {
            coin_add_transaction(
                $conn,
                $userId,
                'memory',
                'match_reward',
                $matchId,
                $reward,
                "memory_{$modeCode}_{$outcomeCode}",
                [
                    'match_id' => $matchId,
                    'pair_count' => $pairCount,
                    'mode_code' => $modeCode,
                    'outcome_code' => $outcomeCode
                ]
            );
        }
    }
}

function mm_memory_theme_styles(string $extraCss = ''): void {
    echo '<style>
        :root{
            --mm-paper:#f7f0d8;
            --mm-paper-strong:#f2e6c2;
            --mm-ink:#3b2f2a;
            --mm-muted:#705d50;
            --mm-line:rgba(113, 169, 208, .38);
            --mm-line-strong:rgba(113, 169, 208, .22);
            --mm-red:#c9483b;
            --mm-red-deep:#a92c2c;
            --mm-orange:#f29c38;
            --mm-yellow:#ffd966;
            --mm-blue:#2f8bd8;
            --mm-blue-soft:#e7f3ff;
            --mm-card:#fffaf0;
            --mm-shadow:0 16px 34px rgba(121, 82, 40, .16);
            --mm-shadow-soft:0 10px 20px rgba(121, 82, 40, .10);
            --mm-radius:26px;
        }

        *{box-sizing:border-box}

        html,body{
            margin:0;
            min-height:100%;
        }

        body{
            font-family:"Trebuchet MS","Arial Rounded MT Bold","Segoe UI",sans-serif;
            color:var(--mm-ink);
            background:
                linear-gradient(var(--mm-line-strong) 1px, transparent 1px),
                linear-gradient(90deg, var(--mm-line-strong) 1px, transparent 1px),
                linear-gradient(var(--mm-line) 1px, transparent 1px),
                linear-gradient(90deg, var(--mm-line) 1px, transparent 1px),
                radial-gradient(circle at top, rgba(255,255,255,.82), rgba(255,255,255,.18) 34%, transparent 62%),
                linear-gradient(180deg, #fffdf5 0%, var(--mm-paper) 52%, #f7efd7 100%);
            background-size: 120px 120px, 120px 120px, 40px 40px, 40px 40px, auto, auto;
            background-position: 0 0, 0 0, 0 0, 0 0, center top, center;
        }

        a{color:inherit}

        .mm-page{
            width:min(1400px, 94vw);
            margin:0 auto;
            padding:24px 0 40px;
        }

        .mm-navbar,
        .mm-topbar{
            width:min(1400px, 94vw);
            margin:18px auto 0;
            min-height:78px;
            padding:16px 24px;
            border-radius:24px;
            background:rgba(255,250,240,.9);
            border:2px solid rgba(201,72,59,.18);
            box-shadow:var(--mm-shadow-soft);
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:18px;
            position:sticky;
            top:12px;
            z-index:20;
            backdrop-filter:blur(10px);
        }

        .mm-brand{
            display:flex;
            align-items:center;
            gap:14px;
            min-width:0;
        }

        .mm-brand-badge{
            width:48px;
            height:48px;
            border-radius:16px;
            background:linear-gradient(180deg, #ffd98c 0%, #f7ae48 100%);
            border:2px solid rgba(169,44,44,.28);
            box-shadow:4px 4px 0 rgba(169,44,44,.75);
            color:#fff8e6;
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:1.25rem;
            font-weight:900;
            flex-shrink:0;
        }

        .mm-brand-title{
            font-size:1.2rem;
            font-weight:900;
            letter-spacing:.04em;
            text-transform:uppercase;
            color:#fff3cf;
            -webkit-text-stroke:1px rgba(169,44,44,.65);
            text-shadow:4px 4px 0 rgba(169,44,44,.9);
        }

        .mm-brand-subtitle{
            color:var(--mm-muted);
            font-size:.86rem;
            font-weight:700;
        }

        .mm-nav-links{
            display:flex;
            align-items:center;
            justify-content:flex-end;
            gap:10px;
            flex-wrap:wrap;
        }

        .mm-nav-links a{
            text-decoration:none;
            padding:9px 14px;
            border-radius:999px;
            color:var(--mm-ink);
            font-weight:800;
            background:rgba(255,255,255,.62);
            border:1px solid rgba(201,72,59,.12);
            transition:transform .16s ease, background .16s ease, color .16s ease;
        }

        .mm-nav-links a:hover,
        .mm-nav-links a.active{
            transform:translateY(-1px);
            background:#fff6cf;
            color:var(--mm-red-deep);
        }

        .mm-hero,
        .mm-card,
        .mm-panel{
            background:rgba(255,250,240,.9);
            border:2px solid rgba(201,72,59,.14);
            border-radius:var(--mm-radius);
            box-shadow:var(--mm-shadow);
        }

        .mm-hero{
            padding:30px 32px;
            position:relative;
            overflow:hidden;
        }

        .mm-hero::after{
            content:"";
            position:absolute;
            inset:auto 20px 18px auto;
            width:92px;
            height:24px;
            background:
                radial-gradient(circle, rgba(201,72,59,.92) 0 3px, transparent 4px) 0 0 / 18px 12px repeat-x;
            opacity:.85;
        }

        .mm-card,
        .mm-panel{
            padding:22px;
        }

        .mm-title{
            margin:0;
            font-size:clamp(1.7rem, 4vw, 3.2rem);
            line-height:1;
            letter-spacing:.04em;
            text-transform:uppercase;
            color:#fff1c9;
            -webkit-text-stroke:2px rgba(169,44,44,.66);
            text-shadow:6px 6px 0 rgba(169,44,44,.94);
        }

        .mm-subtitle{
            display:inline-block;
            margin-top:14px;
            padding:6px 14px;
            border-radius:14px;
            background:linear-gradient(180deg, #fff9d8 0%, #ffe675 100%);
            color:#111;
            font-size:1rem;
            font-weight:900;
            letter-spacing:.05em;
            text-transform:uppercase;
        }

        .mm-lead,
        .mm-muted{
            color:var(--mm-muted);
            line-height:1.65;
        }

        .mm-card h1,
        .mm-card h2,
        .mm-card h3,
        .mm-panel h1,
        .mm-panel h2,
        .mm-panel h3{
            margin:0 0 14px;
            color:var(--mm-red-deep);
            font-weight:900;
            letter-spacing:.02em;
        }

        .mm-pill,
        .timer{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            min-height:44px;
            padding:10px 18px;
            border-radius:999px;
            background:var(--mm-blue-soft);
            border:2px solid rgba(47,139,216,.16);
            color:#185487;
            font-weight:900;
            box-shadow:inset 0 -2px 0 rgba(47,139,216,.12);
        }

        .mm-button,
        .btn,
        button[type="submit"]{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            gap:8px;
            min-height:46px;
            padding:12px 20px;
            border:none;
            border-radius:16px;
            text-decoration:none;
            font-weight:900;
            letter-spacing:.02em;
            cursor:pointer;
            color:#fffdf3;
            background:linear-gradient(180deg, #f7a84c 0%, var(--mm-red) 100%);
            box-shadow:0 8px 0 rgba(169,44,44,.95), 0 16px 24px rgba(169,44,44,.18);
            transition:transform .16s ease, box-shadow .16s ease, filter .16s ease;
        }

        .mm-button:hover,
        .btn:hover,
        button[type="submit"]:hover{
            transform:translateY(-2px);
            filter:brightness(1.03);
        }

        .mm-button.alt,
        .btn2,
        .mm-button.secondary{
            color:var(--mm-red-deep);
            background:linear-gradient(180deg, #fffdf4 0%, #ffeebf 100%);
            box-shadow:0 8px 0 rgba(242,173,63,.95), 0 14px 22px rgba(242,173,63,.18);
        }

        .mm-button.ghost{
            color:var(--mm-blue);
            background:linear-gradient(180deg, #ffffff 0%, #e7f3ff 100%);
            box-shadow:0 8px 0 rgba(47,139,216,.85), 0 14px 22px rgba(47,139,216,.18);
        }

        input,
        select{
            width:100%;
            min-height:48px;
            padding:12px 14px;
            border-radius:16px;
            border:2px solid rgba(47,139,216,.16);
            background:rgba(255,255,255,.85);
            color:var(--mm-ink);
            font:inherit;
            box-shadow:inset 0 1px 0 rgba(255,255,255,.72);
        }

        label{
            display:block;
            margin-bottom:8px;
            color:var(--mm-red-deep);
            font-weight:900;
        }

        .mm-stat{
            background:linear-gradient(180deg, rgba(255,255,255,.8) 0%, rgba(255,239,191,.9) 100%);
            border:1px solid rgba(201,72,59,.12);
            border-radius:18px;
            padding:16px;
        }

        .mm-stat-label{
            color:var(--mm-muted);
            font-size:.88rem;
            margin-bottom:8px;
            font-weight:700;
        }

        .mm-stat-value{
            color:var(--mm-red-deep);
            font-weight:900;
            font-size:1.45rem;
        }

        .mm-status,
        .status,
        .status-bar{
            background:rgba(255,250,240,.96);
            border:2px solid rgba(201,72,59,.12);
            border-radius:20px;
            color:var(--mm-muted);
            box-shadow:var(--mm-shadow-soft);
        }

        table{
            width:100%;
            border-collapse:collapse;
        }

        th,td{
            padding:12px 10px;
            border-bottom:1px dashed rgba(113,169,208,.28);
            text-align:left;
        }

        th{
            color:var(--mm-red-deep);
            font-weight:900;
        }

        .mm-badge{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            padding:6px 12px;
            border-radius:999px;
            font-size:.8rem;
            font-weight:900;
            background:#fff6cf;
            color:var(--mm-red-deep);
            border:1px solid rgba(201,72,59,.12);
        }

        .good{color:#16804d;font-weight:900}
        .bad{color:#bf3d36;font-weight:900}

        @media (max-width: 860px){
            .mm-navbar,
            .mm-topbar{
                width:94vw;
                padding:16px 18px;
                align-items:flex-start;
                flex-direction:column;
            }

            .mm-page{
                width:94vw;
            }

            .mm-hero{
                padding:24px 20px;
            }
        }
    ' . $extraCss . '</style>';
}
?>
