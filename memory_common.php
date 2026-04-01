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
        'display' => $card['card_type'] === 'word' ? $card['english_word'] : '🔊 Pronunciation',
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

                if ($mode['win_rule'] === 'first_finish') {
                    mm_settle_match($conn, $matchId);
                }
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
                $payload['display'] = '🔊 Pronunciation';
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
                    $payload['display'] = '🔊 Pronunciation';
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

    $mode = mm_get_mode($conn, (int)$match['mode_id']);
    if (!$mode) return false;

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

    if ($mode['win_rule'] === 'first_finish') {
        usort($players, function($a, $b) {
            $aTime = !empty($a['finished_at']) ? strtotime($a['finished_at']) : PHP_INT_MAX;
            $bTime = !empty($b['finished_at']) ? strtotime($b['finished_at']) : PHP_INT_MAX;
            return $aTime <=> $bTime;
        });

        if (!empty($players[0]['finished_at']) && strtotime($players[0]['finished_at']) < PHP_INT_MAX) {
            $winnerUserId = (int)$players[0]['user_id'];
        } else {
            $winnerUserId = null;
        }
    } else {
        usort($players, function($a, $b) {
            if ((int)$b['score'] !== (int)$a['score']) {
                return (int)$b['score'] <=> (int)$a['score'];
            }
            return (int)$a['flip_count'] <=> (int)$b['flip_count'];
        });
        $winnerUserId = (int)$players[0]['user_id'];
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
                ? ($row['card_type'] === 'word' ? $row['english_word'] : '🔊 Pronunciation')
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
                    ? ($row['card_type'] === 'word' ? $row['english_word'] : '🔊 Pronunciation')
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
?>