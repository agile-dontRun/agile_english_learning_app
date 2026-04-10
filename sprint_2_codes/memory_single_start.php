<?php
require_once 'memory_common.php';

$userId = mm_current_user_id();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $modeId = (int)($_POST['mode_id'] ?? 1);

    $matchId = mm_create_single_match($conn, $userId, $modeId);
    if ($matchId) {
        header('Location: memory_single_play.php?match_id=' . $matchId);
        exit;
    } else {
        $message = 'Failed to start solo game.';
    }
}

$modes = $conn->query("
    SELECT MIN(mode_id) AS mode_id, pair_count, MIN(time_limit_seconds) AS time_limit_seconds
    FROM memory_game_modes
    GROUP BY pair_count
    ORDER BY pair_count ASC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Start Solo Memory Game</title>
    <?php mm_memory_theme_styles('
        .wrap{width:min(980px, 92vw);margin:30px auto}
        .hero{margin-bottom:20px}
        .hero p{margin:14px 0 0}
        .grid{display:grid;grid-template-columns:1.15fr .85fr;gap:20px}
        .stats{display:grid;gap:14px}
        .form-row{display:grid;gap:10px}
        .actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:20px}
        .message{color:#bf3d36;font-weight:800}
        @media (max-width: 860px){.grid{grid-template-columns:1fr}}
    '); ?>
</head>
<body>
<div class="wrap">
    <div class="mm-hero hero">
        <h1 class="mm-title">Solo Game</h1>
        <div class="mm-subtitle">Train your ears</div>
        <p class="mm-lead">Start immediately and play on the same 8x8 board, now wrapped in a warmer paper-grid interface inspired by the cover image.</p>
    </div>

    <div class="grid">
        <div class="mm-card">
            <h2>Start Game</h2>
            <div class="mm-muted">Pick a pair count and jump straight into the board. Logic stays the same; only the presentation is refreshed.</div>
            <?php if ($message): ?><p class="message"><?= mm_h($message) ?></p><?php endif; ?>

            <form method="post">
                <div class="form-row">
                    <label for="mode_id">Mode</label>
                    <select id="mode_id" name="mode_id">
                        <?php while ($mode = $modes->fetch_assoc()): ?>
                            <option value="<?= (int)$mode['mode_id'] ?>">
                                <?= (int)$mode['pair_count'] ?> pairs | <?= (int)$mode['time_limit_seconds'] ?> seconds
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="actions">
                    <button type="submit">Start Solo Game</button>
                    <a class="mm-button alt" href="memory_home.php">Back</a>
                </div>
            </form>
        </div>

        <div class="mm-card">
            <h2>Quick Notes</h2>
            <div class="stats">
                <div class="mm-stat">
                    <div class="mm-stat-label">Board</div>
                    <div class="mm-stat-value">8 x 8</div>
                </div>
                <div class="mm-stat">
                    <div class="mm-stat-label">Goal</div>
                    <div class="mm-stat-value">Match word + audio</div>
                </div>
                <div class="mm-stat">
                    <div class="mm-stat-label">Style</div>
                    <div class="mm-stat-value">Poster-like warm colors</div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
