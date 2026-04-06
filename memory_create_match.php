<?php
require_once 'memory_common.php';

$userId = mm_current_user_id();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $modeId = (int)($_POST['mode_id'] ?? 0);
    $matchId = mm_create_match($conn, $userId, $modeId);

    if ($matchId) {
        header('Location: memory_wait.php?match_id=' . $matchId);
        exit;
    } else {
        $message = 'Failed to create match.';
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
    <title>Create Memory Match</title>
    <?php mm_memory_theme_styles('
        .wrap{width:min(900px, 92vw);margin:30px auto}
        .hero{margin-bottom:20px}
        .hero p{margin:14px 0 0}
        .stack{display:grid;gap:18px}
        .form-row{display:grid;gap:10px}
        .actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:20px}
        .message{color:#bf3d36;font-weight:800}
    '); ?>
</head>
<body>
<div class="wrap">
    <div class="mm-hero hero">
        <h1 class="mm-title">Create Match</h1>
        <div class="mm-subtitle">Challenge another player</div>
        <p class="mm-lead">Build a room with the same bright poster palette as the cover, then wait for another player to jump in.</p>
    </div>

    <div class="mm-card">
        <div class="stack">
            <div>
                <h2>Room Setup</h2>
                <div class="mm-muted">Choose how many pairs the battle should use. The board visuals stay the same; only the surrounding interface gets the new themed skin.</div>
            </div>

            <?php if ($message): ?><div class="message"><?= mm_h($message) ?></div><?php endif; ?>

            <form method="post">
                <div class="form-row">
                    <label for="mode_id">Choose Mode</label>
                    <select id="mode_id" name="mode_id">
                        <?php while ($mode = $modes->fetch_assoc()): ?>
                            <option value="<?= (int)$mode['mode_id'] ?>">
                                <?= (int)$mode['pair_count'] ?> pairs | <?= (int)$mode['time_limit_seconds'] ?> seconds
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="actions">
                    <button type="submit">Create Match</button>
                    <a class="mm-button alt" href="memory_home.php">Back</a>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
