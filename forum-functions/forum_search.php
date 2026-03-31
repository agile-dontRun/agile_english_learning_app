<?php
require_once 'forum_common.php';

$pageTitle = 'Search Users';
$currentId = current_user_id();
$keyword = trim($_GET['keyword'] ?? '');
$users = [];

if ($keyword !== '') {
    $like = '%' . $keyword . '%';
    $sql = "SELECT user_id, username, nickname, student_level
            FROM users
            WHERE user_id != ?
              AND (username LIKE ? OR nickname LIKE ?)
            ORDER BY nickname, username
            LIMIT 50";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $currentId, $like, $like);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $stmt->close();
}

include 'forum_header.php';
?>

<div class="card">
    <h2 style="margin-top:0;">Search Users</h2>
    <form method="get" action="forum_search.php">
        <input type="text" name="keyword" placeholder="Enter username or nickname..." value="<?= h($keyword) ?>">
        <button class="btn btn-primary" type="submit">Search</button>
    </form>
</div>

<?php if ($keyword !== ''): ?>
    <div class="card">
        <h3 style="margin-top:0;">Search Results</h3>

        <?php if (empty($users)): ?>
            <div class="meta">No users found.</div>
        <?php endif; ?>

        <?php foreach ($users as $user): ?>
            <?php $alreadyFollowing = is_following($conn, $currentId, (int)$user['user_id']); ?>
            <div class="row-between" style="padding: 14px 0; border-bottom: 1px solid #e5e7eb;">
                <div>
                    <a class="link-user" href="forum_profile.php?user_id=<?= (int)$user['user_id'] ?>">
                        <?= h($user['nickname'] ?: $user['username']) ?>
                    </a>
                    <div class="meta">@<?= h($user['username']) ?> · <?= h($user['student_level'] ?: 'unknown') ?></div>
                </div>

                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <a class="btn btn-secondary" href="forum_profile.php?user_id=<?= (int)$user['user_id'] ?>">View Profile</a>
                    <a class="btn btn-dark" href="forum_inbox.php?user_id=<?= (int)$user['user_id'] ?>">Message</a>

                    <?php if ($alreadyFollowing): ?>
                        <a class="btn btn-secondary" href="forum_follow_action.php?user_id=<?= (int)$user['user_id'] ?>&action=unfollow&redirect=search&keyword=<?= urlencode($keyword) ?>">
                            Unfollow
                        </a>
                    <?php else: ?>
                        <a class="btn btn-primary" href="forum_follow_action.php?user_id=<?= (int)$user['user_id'] ?>&action=follow&redirect=search&keyword=<?= urlencode($keyword) ?>">
                            Follow
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include 'forum_footer.php'; ?>