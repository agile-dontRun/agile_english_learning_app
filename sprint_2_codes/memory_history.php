<?php
require_once 'memory_common.php';

$userId = mm_current_user_id();
$profile = mm_get_profile($conn, $userId);
$user = mm_get_user($conn, $userId);

$nickname = $user ? ($user['nickname'] ?: $user['username']) : 'User';

$sql = "
    SELECT
        m.match_id,
        m.status,
        m.created_at,
        m.start_time,
        m.end_time,
        m.winner_user_id,
        gm.pair_count,
        gm.time_limit_seconds,
        mp.user_id,
        mp.player_slot,
        mp.score,
        mp.flip_count,
        mp.matched_pairs_count,
        mp.finished_all,
        mp.finished_at,
        mp.is_winner,
        players.player_count
    FROM memory_matches m
    JOIN memory_game_modes gm ON m.mode_id = gm.mode_id
    JOIN memory_match_players mp ON m.match_id = mp.match_id
    JOIN (
        SELECT match_id, COUNT(*) AS player_count
        FROM memory_match_players
        GROUP BY match_id
    ) players ON m.match_id = players.match_id
    WHERE mp.user_id = ?
    ORDER BY m.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}
$stmt->close();

function mm_history_result_label(array $row, int $currentUserId): array {
    if ($row['status'] !== 'finished') {
        return ['text' => strtoupper($row['status']), 'class' => 'live'];
    }

    if ($row['winner_user_id'] === null) {
        return ['text' => 'DRAW', 'class' => 'draw'];
    }

    if ((int)$row['winner_user_id'] === $currentUserId) {
        return ['text' => 'WIN', 'class' => 'win'];
    }

    return ['text' => 'LOSE', 'class' => 'lose'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Memory Match History</title>
    <link rel="icon" href="data:,">
    <?php mm_memory_theme_styles('
        .page{width:min(1400px, 94vw);margin:24px auto 40px}
        .hero{margin-bottom:20px}
        .hero-top{display:flex;justify-content:space-between;gap:20px;flex-wrap:wrap}
        .hero-actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:20px}
        .grid{display:grid;grid-template-columns:320px 1fr;gap:20px}
        .stats-grid{display:grid;gap:14px}
        .history-table-wrap{overflow-x:auto}
        .badge{display:inline-flex;align-items:center;justify-content:center;padding:6px 12px;border-radius:999px;font-size:.8rem;font-weight:900;border:1px solid rgba(201,72,59,.12)}
        .badge.solo{background:#e7f3ff;color:#185487}
        .badge.pvp{background:#fff6cf;color:var(--mm-red-deep)}
        .badge.waiting,.badge.draw{background:#fffdf4;color:var(--mm-muted)}
        .badge.live{background:#e7f3ff;color:#185487}
        .badge.win{background:#ebfaef;color:#16804d}
        .badge.lose{background:#fff0ec;color:#bf3d36}
        .action-link{text-decoration:none;color:var(--mm-blue);font-weight:900}
        .empty{color:var(--mm-muted)}
        .muted{color:var(--mm-muted);font-size:.92rem}
        @media (max-width: 1080px){.grid{grid-template-columns:1fr}}
    '); ?>
</head>
<body>
    <div class="page">
        <div class="mm-hero hero">
            <div class="hero-top">
                <div>
                    <h1 class="mm-title">Battle History</h1>
                    <div class="mm-subtitle">Review every round</div>
                    <p class="mm-lead">Review solo practice, PvP battles, results, and rewards in the same warm cover-inspired visual style.</p>
                </div>
                <div class="mm-stat" style="min-width:220px;">
                    <div class="mm-stat-label">Player</div>
                    <div class="mm-stat-value"><?= mm_h($nickname) ?></div>
                </div>
            </div>

            <div class="hero-actions">
                <a class="mm-button" href="memory_home.php">Back to Main Page</a>
                <a class="mm-button" href="memory_single_start.php">Start Solo Game</a>
                <a class="mm-button alt" href="memory_create_match.php">Create PvP Match</a>
                <a class="mm-button ghost" href="memory_join_match.php">Join Room</a>
            </div>
        </div>

        <div class="grid">
            <div class="mm-card">
                <h2>Your Summary</h2>
                <div class="stats-grid">
                    <div class="mm-stat"><div class="mm-stat-label">Coins gained in this game</div><div class="mm-stat-value"><?= (int)$profile['coins'] ?></div></div>
                    <div class="mm-stat"><div class="mm-stat-label">Total Matches</div><div class="mm-stat-value"><?= (int)$profile['total_matches'] ?></div></div>
                    <div class="mm-stat"><div class="mm-stat-label">Total Wins</div><div class="mm-stat-value"><?= (int)$profile['total_wins'] ?></div></div>
                    <div class="mm-stat"><div class="mm-stat-label">Best Time</div><div class="mm-stat-value"><?= $profile['best_time_seconds'] === null ? 'N/A' : (int)$profile['best_time_seconds'] . 's' ?></div></div>
                    <div class="mm-stat"><div class="mm-stat-label">History Records</div><div class="mm-stat-value"><?= count($rows) ?></div></div>
                </div>
            </div>

            <div class="mm-card">
                <h2>Match Records</h2>

                <?php if (!empty($rows)): ?>
                    <div class="history-table-wrap">
                        <table>
                            <tr>
                                <th>Match ID</th>
                                <th>Type</th>
                                <th>Pairs</th>
                                <th>Score</th>
                                <th>Flips</th>
                                <th>Matched</th>
                                <th>Result</th>
                                <th>Created</th>
                                <th>Finished</th>
                                <th>Action</th>
                            </tr>

                            <?php foreach ($rows as $row): ?>
                                <?php
                                $typeBadge = ((int)$row['player_count'] >= 2)
                                    ? ['text' => 'PVP', 'class' => 'pvp']
                                    : ['text' => 'SOLO', 'class' => 'solo'];

                                $resultBadge = mm_history_result_label($row, $userId);
                                ?>
                                <tr>
                                    <td>#<?= (int)$row['match_id'] ?></td>
                                    <td><span class="badge <?= $typeBadge['class'] ?>"><?= $typeBadge['text'] ?></span></td>
                                    <td><?= (int)$row['pair_count'] ?> pairs</td>
                                    <td><?= (int)$row['score'] ?></td>
                                    <td><?= (int)$row['flip_count'] ?></td>
                                    <td><?= (int)$row['matched_pairs_count'] ?>/<?= (int)$row['pair_count'] ?></td>
                                    <td><span class="badge <?= $resultBadge['class'] ?>"><?= $resultBadge['text'] ?></span></td>
                                    <td><?= mm_h($row['created_at']) ?></td>
                                    <td><?= $row['end_time'] ? mm_h($row['end_time']) : '<span class="muted">Not finished</span>' ?></td>
                                    <td>
                                        <?php if ($row['status'] === 'finished'): ?>
                                            <a class="action-link" href="memory_match_result.php?match_id=<?= (int)$row['match_id'] ?>">View Result</a>
                                        <?php else: ?>
                                            <a class="action-link" href="memory_match_play.php?match_id=<?= (int)$row['match_id'] ?>">Open Match</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty">You do not have any memory match records yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
