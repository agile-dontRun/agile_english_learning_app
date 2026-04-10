<?php
require_once 'memory_common.php';

$userId = mm_current_user_id();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matchId = (int)($_POST['match_id'] ?? 0);

    if (mm_join_match($conn, $matchId, $userId)) {
        header('Location: memory_match_play.php?match_id=' . $matchId);
        exit;
    } else {
        $message = 'Failed to join that match.';
    }
}

$openMatches = $conn->query("
    SELECT m.match_id, gm.mode_name
    FROM memory_matches m
    JOIN memory_game_modes gm ON m.mode_id = gm.mode_id
    WHERE m.status = 'waiting'
    ORDER BY m.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Memory Match</title>
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
        <h1 class="mm-title">Join Room</h1>
        <div class="mm-subtitle">Pick a waiting match</div>
        <p class="mm-lead">Choose an open battle room and move straight into the shared board when the pairing succeeds.</p>
    </div>

    <div class="mm-card">
        <div class="stack">
            <div>
                <h2>Available Rooms</h2>
                <div class="mm-muted">Only rooms that are still waiting for a second player appear here.</div>
            </div>

            <?php if ($message): ?><div class="message"><?= mm_h($message) ?></div><?php endif; ?>

            <form method="post">
                <div class="form-row">
                    <label for="match_id">Select Match</label>
                    <select id="match_id" name="match_id">
                        <?php while ($row = $openMatches->fetch_assoc()): ?>
                            <option value="<?= (int)$row['match_id'] ?>">
                                #<?= (int)$row['match_id'] ?> | <?= mm_h($row['mode_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="actions">
                    <button type="submit">Join Match</button>
                    <a class="mm-button alt" href="memory_home.php">Back</a>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
