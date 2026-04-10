<?php
require_once 'memory_common.php';

$userId = mm_current_user_id();
$matchId = (int)($_GET['match_id'] ?? 0);

$match = mm_get_match($conn, $matchId);
if (!$match) {
    die('Match not found.');
}

if ($match['status'] !== 'finished') {
    mm_settle_single_match($conn, $matchId, $userId);
    $match = mm_get_match($conn, $matchId);
}

$mode = mm_get_mode($conn, (int)$match['mode_id']);
$player = mm_get_match_player($conn, $matchId, $userId);
$profile = mm_get_profile($conn, $userId);
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

$timeUsed = 'N/A';
if (!empty($match['start_time']) && !empty($match['end_time'])) {
    $timeUsed = max(0, strtotime($match['end_time']) - strtotime($match['start_time'])) . ' sec';
}
$didPass = !empty($player['finished_all']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solo Game Result</title>
    <?php mm_memory_theme_styles('
        .wrap{width:min(980px, 92vw);margin:30px auto}
        .hero{margin-bottom:20px}
        .summary{display:grid;grid-template-columns:repeat(3, 1fr);gap:14px;margin-bottom:20px}
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
        @media (max-width: 860px){.summary{grid-template-columns:1fr}}
    '); ?>
</head>
<body>
<div class="reward-modal" id="reward-modal">
    <div class="reward-dialog">
        <div class="reward-badge">+</div>
        <h2>Coin Reward</h2>
        <p class="reward-copy">Solo round finished. You received <strong><?= (int)$rewardAmount ?> coins</strong>.</p>
        <button class="mm-button" type="button" id="reward-modal-close">OK</button>
    </div>
</div>
<div class="wrap">
    <div class="mm-hero hero">
        <h1 class="mm-title">Solo Result</h1>
        <div class="mm-subtitle"><?= mm_h($mode['mode_name']) ?></div>
    </div>

    <div class="summary">
        <div class="mm-stat"><div class="mm-stat-label">Score</div><div class="mm-stat-value"><?= (int)$player['score'] ?></div></div>
        <div class="mm-stat"><div class="mm-stat-label">Matched Pairs</div><div class="mm-stat-value"><?= (int)$player['matched_pairs_count'] ?></div></div>
        <div class="mm-stat"><div class="mm-stat-label">Flip Count</div><div class="mm-stat-value"><?= (int)$player['flip_count'] ?></div></div>
    </div>

    <div class="mm-card" style="margin-bottom:20px;">
        <h2>Coin Reward</h2>
        <p class="mm-lead">This round reward: <strong><?= (int)$rewardAmount ?></strong> coins.</p>
    </div>

    <div class="mm-card">
        <h2>Round Details</h2>
        <table>
            <tr><th>Finished</th><td><?= (int)$player['finished_all'] ? 'Yes' : 'No' ?></td></tr>
            <tr><th>Time Used</th><td><?= mm_h($timeUsed) ?></td></tr>
            <tr><th>Best Time</th><td><?= $profile['best_time_seconds'] === null ? 'N/A' : (int)$profile['best_time_seconds'] . ' sec' ?></td></tr>
        </table>

        <div class="actions">
            <a class="mm-button" href="memory_single_start.php">Play Again</a>
            <a class="mm-button alt" href="memory_home.php">Back</a>
        </div>
    </div>
</div>
<script>
    const MEMORY_RESULT_SOUND = <?= json_encode($didPass ? '/y2209.wav' : '/xm3411.wav') ?>;

    function playResultSound(path) {
        if (!path) return;
        const audio = new Audio(path);
        audio.preload = 'auto';
        audio.play().catch(() => {});
    }

    window.addEventListener('load', () => {
        playResultSound(MEMORY_RESULT_SOUND);
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
