<?php
require_once 'forum_common.php';

$viewerId = current_user_id();
$userId = (int)($_GET['user_id'] ?? $viewerId);
$user = find_user($conn, $userId);

if (!$user) {
    die('User not found.');
}

$sql = "SELECT u.user_id, u.username, u.nickname, u.student_level
        FROM user_follows f
        JOIN users u ON f.followed_id = u.user_id
        WHERE f.follower_id = ?
        ORDER BY f.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$following = [];
while ($row = $result->fetch_assoc()) {
    $following[] = $row;
}
$stmt->close();

$sql = "SELECT u.user_id, u.username, u.nickname, u.student_level
        FROM user_follows f
        JOIN users u ON f.follower_id = u.user_id
        WHERE f.followed_id = ?
        ORDER BY f.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$followers = [];
while ($row = $result->fetch_assoc()) {
    $followers[] = $row;
}
$stmt->close();

$pageTitle = 'Following';
include 'forum_header.php';
?>

<div class="card">
    <div class="card-header">
        <div>
            <h1><?= h($user['nickname'] ?: $user['username']) ?>'s Network</h1>
            <p class="section-intro">Track who this learner follows and who follows them back.</p>
        </div>
    </div>
</div>

<div class="two-column">
    <div class="card">
        <div class="card-header">
            <div>
                <h2>Following</h2>
                <p class="section-intro">People this learner has chosen to keep up with.</p>
            </div>
        </div>

        <?php if (empty($following)): ?>
            <div class="empty-state">No following users yet.</div>
        <?php else: ?>
            <div class="list-stack">
                <?php foreach ($following as $item): ?>
                    <div class="list-item">
                        <div class="row-between">
                            <div>
                                <a class="link-user" href="forum_profile.php?user_id=<?= (int)$item['user_id'] ?>">
                                    <?= h($item['nickname'] ?: $item['username']) ?>
                                </a>
                                <div class="meta meta-line">
                                    <span>@<?= h($item['username']) ?></span>
                                    <span class="separator">&middot;</span>
                                    <span><?= h($item['student_level'] ?: 'unknown') ?></span>
                                </div>
                            </div>
                            <a class="btn btn-secondary" href="forum_profile.php?user_id=<?= (int)$item['user_id'] ?>">View</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-header">
            <div>
                <h2>Followers</h2>
                <p class="section-intro">People who are following this learner's updates.</p>
            </div>
        </div>

        <?php if (empty($followers)): ?>
            <div class="empty-state">No followers yet.</div>
        <?php else: ?>
            <div class="list-stack">
                <?php foreach ($followers as $item): ?>
                    <div class="list-item">
                        <div class="row-between">
                            <div>
                                <a class="link-user" href="forum_profile.php?user_id=<?= (int)$item['user_id'] ?>">
                                    <?= h($item['nickname'] ?: $item['username']) ?>
                                </a>
                                <div class="meta meta-line">
                                    <span>@<?= h($item['username']) ?></span>
                                    <span class="separator">&middot;</span>
                                    <span><?= h($item['student_level'] ?: 'unknown') ?></span>
                                </div>
                            </div>
                            <a class="btn btn-secondary" href="forum_profile.php?user_id=<?= (int)$item['user_id'] ?>">View</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'forum_footer.php'; ?>
