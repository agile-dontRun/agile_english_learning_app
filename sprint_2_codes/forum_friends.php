<?php
require_once 'forum_common.php';
$userId = current_user_id();

$sql = "SELECT f.*, u.user_id, u.username, u.nickname, u.student_level
        FROM user_friendships f
        JOIN users u
          ON u.user_id = CASE
                WHEN f.user_id_1 = ? THEN f.user_id_2
                ELSE f.user_id_1
             END
        WHERE f.user_id_1 = ? OR f.user_id_2 = ?
        ORDER BY f.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $userId, $userId, $userId);
$stmt->execute();
$result = $stmt->get_result();

$friends = [];
while ($row = $result->fetch_assoc()) {
    $friends[] = $row;
}
$stmt->close();

$pageTitle = 'Friends';
include 'forum_header.php';
?>

<div class="card">
    <div class="card-header">
        <div>
            <h1>My Friends</h1>
            <p class="section-intro">Mutual following turns into friendship, making it easier to stay in touch and message freely.</p>
        </div>
    </div>

    <?php if (empty($friends)): ?>
        <div class="empty-state">You do not have any friends yet. Mutual following creates friendship.</div>
    <?php else: ?>
        <div class="list-stack">
            <?php foreach ($friends as $friend): ?>
                <div class="list-item">
                    <div class="row-between">
                        <div>
                            <a class="link-user" href="forum_profile.php?user_id=<?= (int)$friend['user_id'] ?>">
                                <?= h($friend['nickname'] ?: $friend['username']) ?>
                            </a>
                            <div class="meta meta-line">
                                <span>@<?= h($friend['username']) ?></span>
                                <span class="separator">&middot;</span>
                                <span><?= h($friend['student_level'] ?: 'unknown') ?></span>
                            </div>
                        </div>
                        <div class="actions-row">
                            <a class="btn btn-secondary" href="forum_profile.php?user_id=<?= (int)$friend['user_id'] ?>">Profile</a>
                            <a class="btn btn-dark" href="forum_inbox.php?user_id=<?= (int)$friend['user_id'] ?>">Message</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'forum_footer.php'; ?>
