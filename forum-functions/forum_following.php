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
    <h1 style="margin-top:0;"><?= h($user['nickname'] ?: $user['username']) ?> - Following & Followers</h1>
</div>

<div class="card">
    <h2>Following</h2>
    <?php if (empty($following)): ?>
        <div class="meta">No following users yet.</div>
    <?php endif; ?>

    <?php foreach ($following as $item): ?>
        <div class="row-between" style="padding: 14px 0; border-bottom: 1px solid #e5e7eb;">
            <div>
                <a class="link-user" href="forum_profile.php?user_id=<?= (int)$item['user_id'] ?>">
                    <?= h($item['nickname'] ?: $item['username']) ?>
                </a>
                <div class="meta">@<?= h($item['username']) ?> · <?= h($item['student_level'] ?: 'unknown') ?></div>
            </div>
            <a class="btn btn-secondary" href="forum_profile.php?user_id=<?= (int)$item['user_id'] ?>">View</a>
        </div>
    <?php endforeach; ?>
</div>

<div class="card">
    <h2>Followers</h2>
    <?php if (empty($followers)): ?>
        <div class="meta">No followers yet.</div>
    <?php endif; ?>

    <?php foreach ($followers as $item): ?>
        <div class="row-between" style="padding: 14px 0; border-bottom: 1px solid #e5e7eb;">
            <div>
                <a class="link-user" href="forum_profile.php?user_id=<?= (int)$item['user_id'] ?>">
                    <?= h($item['nickname'] ?: $item['username']) ?>
                </a>
                <div class="meta">@<?= h($item['username']) ?> · <?= h($item['student_level'] ?: 'unknown') ?></div>
            </div>
            <a class="btn btn-secondary" href="forum_profile.php?user_id=<?= (int)$item['user_id'] ?>">View</a>
        </div>
    <?php endforeach; ?>
</div>

<?php include 'forum_footer.php'; ?>