<?php
require_once 'memory_common.php';

$userId = mm_current_user_id();
$profile = mm_get_profile($conn, $userId);
$user = mm_get_user($conn, $userId);

$nickname = $user ? ($user['nickname'] ?: $user['username']) : 'User';

/*
 * 历史战绩：
 * - 当前用户参加过的所有 Memory Match
 * - 用 player_count 来区分 solo / pvp
 */
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
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: linear-gradient(135deg, #f5faf7 0%, #edf5f1 100%);
            color: #2d3436;
        }

        .navbar {
            height: 72px;
            background: rgba(255,255,255,0.96);
            backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 36px;
            box-shadow: 0 2px 18px rgba(0,0,0,.05);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 800;
            color: #1b4332;
            font-size: 1.15rem;
        }

        .brand-badge {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            background: linear-gradient(135deg, #1b4332, #40916c);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .nav-links a {
            text-decoration: none;
            color: #5f6f68;
            padding: 9px 14px;
            border-radius: 12px;
            transition: all .18s ease;
            font-weight: 600;
            font-size: .95rem;
        }

        .nav-links a:hover,
        .nav-links a.active {
            background: #eef6f1;
            color: #1b4332;
        }

        .page {
            width: min(1400px, 96vw);
            margin: 26px auto 40px;
        }

        .hero {
            background: linear-gradient(135deg, #081c15 0%, #1b4332 100%);
            color: white;
            border-radius: 28px;
            padding: 30px 34px;
            box-shadow: 0 20px 40px rgba(8,28,21,.18);
            margin-bottom: 22px;
        }

        .hero-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            flex-wrap: wrap;
        }

        .hero h1 {
            margin: 0 0 10px;
            font-size: 2rem;
            letter-spacing: .2px;
        }

        .hero p {
            margin: 0;
            color: #d7efe3;
            line-height: 1.6;
            max-width: 780px;
        }

        .hero-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 22px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 18px;
            border: none;
            border-radius: 999px;
            text-decoration: none;
            font-weight: 700;
            cursor: pointer;
            transition: transform .15s ease, box-shadow .2s ease;
        }

        .btn:hover { transform: translateY(-1px); }

        .btn-primary {
            background: #2563eb;
            color: white;
            box-shadow: 0 10px 24px rgba(37,99,235,.22);
        }

        .btn-muted {
            background: rgba(255,255,255,.12);
            color: white;
            border: 1px solid rgba(255,255,255,.18);
        }

        .grid {
            display: grid;
            grid-template-columns: 340px 1fr;
            gap: 20px;
        }

        .card {
            background: white;
            border-radius: 24px;
            padding: 22px;
            box-shadow: 0 12px 30px rgba(27,67,50,.08);
        }

        .card h2 {
            margin: 0 0 14px;
            color: #1b4332;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 14px;
        }

        .stat-box {
            background: linear-gradient(180deg, #f8fbf9 0%, #f1f7f4 100%);
            border: 1px solid #e1ede7;
            border-radius: 18px;
            padding: 16px;
        }

        .stat-label {
            color: #6d7d76;
            font-size: .9rem;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 1.35rem;
            font-weight: 800;
            color: #1b4332;
        }

        .history-table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 940px;
        }

        th, td {
            text-align: left;
            padding: 12px 10px;
            border-bottom: 1px solid #edf2f0;
            vertical-align: middle;
        }

        th {
            color: #6d7d76;
            font-size: .9rem;
            font-weight: 700;
            background: #fbfdfc;
            position: sticky;
            top: 0;
        }

        td {
            color: #2d3436;
            font-size: .95rem;
        }

        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 999px;
            font-size: .78rem;
            font-weight: 800;
            letter-spacing: .2px;
        }

        .badge.solo {
            background: #eef2ff;
            color: #1d4ed8;
        }

        .badge.pvp {
            background: #fff7ed;
            color: #b45309;
        }

        .badge.waiting {
            background: #f8fafc;
            color: #475569;
        }

        .badge.live {
            background: #eef2ff;
            color: #1d4ed8;
        }

        .badge.win {
            background: #ecfdf3;
            color: #15803d;
        }

        .badge.lose {
            background: #fef2f2;
            color: #b91c1c;
        }

        .badge.draw {
            background: #f8fafc;
            color: #475569;
        }

        .action-link {
            text-decoration: none;
            color: #2563eb;
            font-weight: 700;
        }

        .empty {
            color: #6d7d76;
            font-size: .95rem;
            padding: 10px 2px;
        }

        .muted {
            color: #6d7d76;
            font-size: .92rem;
        }

        @media (max-width: 1080px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 760px) {
            .navbar {
                height: auto;
                padding: 16px 18px;
                align-items: flex-start;
                flex-direction: column;
                gap: 12px;
            }

            .page {
                width: 95vw;
            }

            .hero {
                padding: 24px 20px;
            }

            .hero h1 {
                font-size: 1.6rem;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="brand">
            <div class="brand-badge">🃏</div>
            <div>Memory Match</div>
        </div>

        <div class="nav-links">
            <a href="home.php">Home</a>
            <a href="memory_home.php">Memory Match</a>
            <a href="memory_history.php" class="active">History</a>
            <a href="TED.php">TED</a>
            <a href="ielts.php">IELTS</a>
            <a href="daily_talk.php">Daily Talk</a>
            <a href="forum.php">Community</a>
            <a href="profile.php"><?= mm_h($nickname) ?></a>
        </div>
    </div>

    <div class="page">
        <div class="hero">
            <div class="hero-top">
                <div>
                    <h1>Battle History</h1>
                    <p>
                        Review all of your Memory Match sessions here, including solo practice and PvP battles.
                        Finished matches show results, while ongoing matches can still be reopened and continued.
                    </p>
                </div>

                <div style="min-width:220px;">
                    <div style="font-size:.95rem;color:#d7efe3;margin-bottom:8px;">Player</div>
                    <div style="font-size:1.2rem;font-weight:800;"><?= mm_h($nickname) ?></div>
                </div>
            </div>

            <div class="hero-actions">
                <a class="btn btn-primary" href="memory_single_start.php">🧠 Start Solo Game</a>
                <a class="btn btn-muted" href="memory_create_match.php">⚔️ Create PvP Match</a>
                <a class="btn btn-muted" href="memory_join_match.php">🚪 Join Room</a>
            </div>
        </div>

        <div class="grid">
            <div class="card">
                <h2>Your Summary</h2>

                <div class="stats-grid">
                    <div class="stat-box">
                        <div class="stat-label">Coins</div>
                        <div class="stat-value"><?= (int)$profile['coins'] ?></div>
                    </div>

                    <div class="stat-box">
                        <div class="stat-label">Total Matches</div>
                        <div class="stat-value"><?= (int)$profile['total_matches'] ?></div>
                    </div>

                    <div class="stat-box">
                        <div class="stat-label">Total Wins</div>
                        <div class="stat-value"><?= (int)$profile['total_wins'] ?></div>
                    </div>

                    <div class="stat-box">
                        <div class="stat-label">Best Time</div>
                        <div class="stat-value">
                            <?= $profile['best_time_seconds'] === null ? 'N/A' : (int)$profile['best_time_seconds'] . 's' ?>
                        </div>
                    </div>

                    <div class="stat-box">
                        <div class="stat-label">History Records</div>
                        <div class="stat-value"><?= count($rows) ?></div>
                    </div>
                </div>
            </div>

            <div class="card">
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