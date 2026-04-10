<?php
require_once 'memory_common.php';

$userId = mm_current_user_id();
$matchId = (int)($_GET['match_id'] ?? 0);

$match = mm_get_match($conn, $matchId);
if (!$match) {
    die('Match not found.');
}

$player = mm_get_match_player($conn, $matchId, $userId);
if (!$player) {
    die('You are not in this match.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Waiting for Opponent</title>
    <link rel="icon" href="data:,">
    <?php mm_memory_theme_styles('
        .stage{
            min-height:100vh;
            display:flex;
            align-items:center;
            justify-content:center;
            padding:24px;
        }
        .wait-card{
            width:min(720px, 92vw);
            text-align:center;
        }
        .spinner{
            width:86px;
            height:86px;
            margin:0 auto 24px;
            border-radius:50%;
            border:8px solid rgba(47,139,216,.16);
            border-top-color:var(--mm-red);
            border-right-color:var(--mm-orange);
            animation:spin 1s linear infinite;
        }
        .wait-card p{margin:14px 0}
        .match-id{
            display:inline-flex;
            padding:8px 14px;
            border-radius:999px;
            background:#fff6cf;
            color:var(--mm-red-deep);
            font-weight:900;
        }
        .wait-title{
            margin:0 0 12px;
            font-size:clamp(1.8rem,4vw,2.8rem);
            color:var(--mm-red-deep);
            font-weight:900;
            letter-spacing:.02em;
            text-shadow:none;
        }
        @keyframes spin{to{transform:rotate(360deg)}}
    '); ?>
</head>
<body>
<div class="stage">
    <div class="mm-card wait-card">
        <div class="spinner"></div>
        <h1 class="wait-title">Waiting Room</h1>
        <p class="mm-lead">When another player joins, both of you will enter the mirrored battle board automatically.</p>
        <p><span class="match-id">Match ID #<?= (int)$matchId ?></span></p>
        <p><a class="mm-button alt" href="memory_home.php">Back to Memory Home</a></p>
    </div>
</div>

<script>
const matchId = <?= (int)$matchId ?>;
let redirecting = false;

async function pollMatch() {
    if (redirecting) return;

    try {
        const res = await fetch(`memory_poll_status.php?match_id=${matchId}&t=${Date.now()}`);
        const data = await res.json();

        if (!data.ok) return;

        if (data.player_count >= 2) {
            redirecting = true;
            window.location.href = `memory_match_play.php?match_id=${matchId}`;
            return;
        }

        if (data.status === 'in_progress' || data.status === 'finished') {
            redirecting = true;
            window.location.href = `memory_match_play.php?match_id=${matchId}`;
            return;
        }
    } catch (e) {
        console.error('pollMatch error:', e);
    }
}

setInterval(pollMatch, 1200);
pollMatch();
</script>
</body>
</html>
