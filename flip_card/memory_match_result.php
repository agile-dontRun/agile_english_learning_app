<?php
require_once 'memory_common.php';

$userId = mm_current_user_id();
$matchId = (int)($_GET['match_id'] ?? 0);

$match = mm_get_match($conn, $matchId);
if (!$match) {
    die('Match not found.');
}

if ($match['status'] !== 'finished') {
    mm_settle_match($conn, $matchId);
    $match = mm_get_match($conn, $matchId);
}

$mode = mm_get_mode($conn, (int)$match['mode_id']);
$rewardAmount = 0;

$stmt = $conn->prepare("
    SELECT delta_amount
    FROM coin_ledger
    WHERE user_id = ?
      AND source_game = 'memory'
      AND source_type = 'match_reward'
      AND source_ref_id = ?
    ORDER BY ledger_id DESC
    LIMIT 1
");
$stmt->bind_param("ii", $userId, $matchId);
$stmt->execute();
$rewardRow = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($rewardRow) {
    $rewardAmount = (int)$rewardRow['delta_amount'];
}

$stmt = $conn->prepare("
    SELECT mp.*, u.username, u.nickname
    FROM memory_match_players mp
    JOIN users u ON mp.user_id = u.user_id
    WHERE mp.match_id = ?
    ORDER BY mp.player_slot ASC
");
$stmt->bind_param("i", $matchId);
$stmt->execute();
$res = $stmt->get_result();

$rows = [];
while ($row = $res->fetch_assoc()) {
    $rows[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Memory Match Result</title>
    <?php mm_memory_theme_styles('
        .wrap{width:min(1040px, 92vw);margin:30px auto}
        .hero{margin-bottom:20px}
        .actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:18px}
        .reward-modal{
            position:fixed;
            inset:0;
            display:flex;
            align-items:center;
            justify-content:center;
            padding:20px;
            background:rgba(59,47,42,.32);
            backdrop-filter:blur(6px);
            z-index:80;
        }
        .reward-modal[hidden]{display:none}
        .reward-dialog{
            width:min(460px, 92vw);
            background:linear-gradient(180deg, #fffdf6 0%, #f6efd9 100%);
            border:2px solid rgba(201,72,59,.16);
            border-radius:28px;
            box-shadow:0 22px 50px rgba(121,82,40,.22);
            padding:28px 26px 24px;
            text-align:center;
        }
        .reward-badge{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            min-width:72px;
            min-height:72px;
            border-radius:22px;
            margin-bottom:16px;
            background:linear-gradient(180deg, #ffe79a 0%, #f7b44c 100%);
            color:#8f3d15;
            font-size:2rem;
            font-weight:900;
            box-shadow:0 10px 24px rgba(242,173,63,.28);
        }
        .reward-copy{
            margin:0 0 18px;
            color:var(--mm-ink);
            line-height:1.65;
        }
        .reward-copy strong{
            color:var(--mm-red-deep);
        }
    '); ?>
</head>
<body>
<div class="reward-modal" id="reward-modal">
    <div class="reward-dialog">
        <div class="reward-badge">+</div>
        <h2>Coin Reward</h2>
        <p class="reward-copy">Match finished. You received <strong><?= (int)$rewardAmount ?> coins</strong>.</p>
        <button class="mm-button" type="button" id="reward-modal-close">OK</button>
    </div>
</div>
<div class="wrap">
    <div class="mm-hero hero">
        <h1 class="mm-title">Match Result</h1>
        <div class="mm-subtitle">Match #<?= (int)$matchId ?></div>
        <p class="mm-lead"><?= (int)$mode['pair_count'] ?> pairs | first to clear all wins, otherwise score then fewer flips.</p>
    </div>

    <div class="mm-card">
        <h2>Coin Reward</h2>
        <p class="mm-lead">This round reward: <strong><?= (int)$rewardAmount ?></strong> coins.</p>
    </div>

    <div class="mm-card" style="margin-top:20px;">
        <h2>Battle Summary</h2>
        <table>
            <tr>
                <th>Player</th>
                <th>Score</th>
                <th>Matched Pairs</th>
                <th>Flip Count</th>
                <th>Finished At</th>
                <th>Winner</th>
            </tr>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= mm_h($row['nickname'] ?: $row['username']) ?></td>
                    <td><?= (int)$row['score'] ?></td>
                    <td><?= (int)$row['matched_pairs_count'] ?></td>
                    <td><?= (int)$row['flip_count'] ?></td>
                    <td><?= $row['finished_at'] ? mm_h($row['finished_at']) : 'N/A' ?></td>
                    <td><?= (int)$row['is_winner'] ? 'Yes' : 'No' ?></td>
                </tr>
            <?php endforeach; ?>
        </table>

        <div class="actions">
            <a class="mm-button alt" href="memory_home.php">Back to Memory Home</a>
            <a class="mm-button" href="memory_create_match.php">Create New Match</a>
        </div>
    </div>
</div>
<script>
    window.addEventListener('load', () => {
        const modal = document.getElementById('reward-modal');
        const closeBtn = document.getElementById('reward-modal-close');
        if (closeBtn && modal) {
            closeBtn.addEventListener('click', () => {
                modal.hidden = true;
            });
        }
    });
</script>
</body>
</html>
