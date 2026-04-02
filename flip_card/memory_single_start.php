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
    SELECT *
    FROM memory_game_modes
    WHERE pair_count = 8
    ORDER BY mode_id ASC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Start Solo Memory Game</title>
    <style>
        body{font-family:Segoe UI,Tahoma,sans-serif;background:#f2f7f5;margin:0;padding:30px;color:#2d3436}
        .wrap{max-width:760px;margin:0 auto}
        .hero{background:linear-gradient(135deg,#081c15 0%,#1b4332 100%);color:#fff;border-radius:24px;padding:28px;margin-bottom:20px}
        .card{background:#fff;border-radius:20px;padding:24px;box-shadow:0 10px 30px rgba(27,67,50,.08)}
        h1,h2{margin-top:0}
        select,button{width:100%;padding:12px;border-radius:12px;border:1px solid #cbd5e1;box-sizing:border-box}
        button{background:#2563eb;color:#fff;border:none;font-weight:700;cursor:pointer;margin-top:16px}
        .meta{color:#6d7d76}
        a{display:inline-block;margin-top:14px;color:#2563eb;text-decoration:none}
    </style>
</head>
<body>
<div class="wrap">
    <div class="hero">
        <h1 style="margin:0 0 8px;">Solo Memory Match</h1>
        <div>Start immediately and play on a real 8×8 board with 8 hidden pairs.</div>
    </div>

    <div class="card">
        <h2>Start Game</h2>
        <?php if ($message): ?><div class="meta" style="color:#b91c1c;"><?= mm_h($message) ?></div><?php endif; ?>

        <form method="post">
            <label>Mode</label>
            <select name="mode_id">
                <?php while ($mode = $modes->fetch_assoc()): ?>
                    <option value="<?= (int)$mode['mode_id'] ?>">
                        <?= mm_h($mode['mode_name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <button type="submit">Start Solo Game</button>
        </form>

        <a href="memory_home.php">← Back</a>
    </div>
</div>
</body>
</html>
