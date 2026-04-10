<?php
require_once 'forum_common.php';
$page_title = 'My Friends';

$stmt = $conn->prepare("SELECT f.*, u.user_id, u.username, u.nickname, u.student_level
                        FROM user_friendships f
                        JOIN users u ON u.user_id = CASE WHEN f.user_id_1 = ? THEN f.user_id_2 ELSE f.user_id_1 END
                        WHERE f.user_id_1 = ? OR f.user_id_2 = ?
                        ORDER BY f.created_at DESC");
$stmt->bind_param("iii", $current_user_id, $current_user_id, $current_user_id);
$stmt->execute();
$friends = $stmt->get_result();

include 'forum_header.php';
?>
<div class="card">
    <h2>My Friends</h2>
    <?php while ($friend = $friends->fetch_assoc()): ?>
        <div class="row-between" style="padding: 14px 0; border-bottom: 1px solid #eef5f2;">
            <div>
                <strong><?= h($friend['nickname'] ?: $friend['username']) ?></strong>
                <div class="meta">@<?= h($friend['username']) ?> · <?= h($friend['student_level'] ?: 'unknown') ?></div>
            </div>
            <div>
                <a class="btn btn-secondary" href="forum_profile.php?user_id=<?= (int)$friend['user_id'] ?>">Profile</a>
                <a class="btn btn-dark" href="forum_inbox.php?user_id=<?= (int)$friend['user_id'] ?>">Message</a>
            </div>
        </div>
    <?php endwhile; ?>
</div>
<?php include 'forum_footer.php'; ?>
