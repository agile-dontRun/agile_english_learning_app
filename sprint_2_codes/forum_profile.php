<?php
require_once 'forum_common.php';

$viewerId = current_user_id();
$userId = (int)($_GET['user_id'] ?? $viewerId);
$user = find_user($conn, $userId);

if (!$user) {
    die('User not found.');
}

$isSelf = ($viewerId === $userId);
$isFollowing = is_following($conn, $viewerId, $userId);
$isFriend = are_friends($conn, $viewerId, $userId);

$sql = "SELECT COUNT(*) AS total FROM user_follows WHERE followed_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$followersCount = (int)($result->fetch_assoc()['total'] ?? 0);
$stmt->close();

$sql = "SELECT COUNT(*) AS total FROM user_follows WHERE follower_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$followingCount = (int)($result->fetch_assoc()['total'] ?? 0);
$stmt->close();

$sql = "SELECT COUNT(*) AS total
        FROM user_friendships
        WHERE user_id_1 = ? OR user_id_2 = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $userId, $userId);
$stmt->execute();
$result = $stmt->get_result();
$friendsCount = (int)($result->fetch_assoc()['total'] ?? 0);
$stmt->close();

$sql = "SELECT *
        FROM forum_posts
        WHERE user_id = ? AND is_deleted = 0
        ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$posts = [];
while ($row = $result->fetch_assoc()) {
    if (can_view_post($conn, $row, $viewerId)) {
        $posts[] = $row;
    }
}
$stmt->close();

$pageTitle = $isSelf ? 'My Profile' : 'User Profile';
include 'forum_header.php';
?>

<div class="card">
    <div class="card-header">
        <div>
            <h1><?= h($user['nickname'] ?: $user['username']) ?></h1>
            <div class="meta meta-line">
                <span>@<?= h($user['username']) ?></span>
                <?php if (!empty($user['student_level'])): ?>
                    <span class="separator">&middot;</span>
                    <span><?= h($user['student_level']) ?></span>
                <?php endif; ?>
            </div>

            <?php if ($isSelf): ?>
                <p class="section-intro">This is your community profile and posting hub.</p>
            <?php elseif ($isFriend): ?>
                <div class="alert alert-info" style="margin-top:14px; margin-bottom:0;">You and this learner are friends.</div>
            <?php elseif ($isFollowing): ?>
                <p class="section-intro">You are already following this learner.</p>
            <?php endif; ?>
        </div>

        <?php if (!$isSelf): ?>
            <div class="actions-row">
                <?php if ($isFollowing): ?>
                    <a class="btn btn-secondary" href="forum_follow_action.php?user_id=<?= $userId ?>&action=unfollow">Unfollow</a>
                <?php else: ?>
                    <a class="btn btn-primary" href="forum_follow_action.php?user_id=<?= $userId ?>&action=follow">Follow</a>
                <?php endif; ?>

                <a class="btn btn-dark" href="forum_inbox.php?user_id=<?= $userId ?>">Message</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="stat-grid">
        <div class="stat-item">
            <div class="stat-value"><?= $followersCount ?></div>
            <div class="stat-label">Followers</div>
        </div>
        <div class="stat-item">
            <div class="stat-value"><?= $followingCount ?></div>
            <div class="stat-label">Following</div>
        </div>
        <div class="stat-item">
            <div class="stat-value"><?= $friendsCount ?></div>
            <div class="stat-label">Friends</div>
        </div>
    </div>

    <div class="actions-row" style="margin-top:18px;">
        <a class="btn btn-secondary" href="forum_following.php?user_id=<?= $userId ?>">Following & Followers</a>
        <a class="btn btn-secondary" href="forum_friends.php">Friends</a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div>
            <h2><?= $isSelf ? 'My Posts' : h($user['nickname'] ?: $user['username']) . '\'s Posts' ?></h2>
            <p class="section-intro">A clean archive of the posts currently visible to you.</p>
        </div>

        <?php if ($isSelf): ?>
            <a class="btn btn-primary" href="forum_post_create.php">New Post</a>
        <?php endif; ?>
    </div>

    <?php if (empty($posts)): ?>
        <div class="empty-state"><?= $isSelf ? 'You have not posted anything yet.' : 'No visible posts from this learner yet.' ?></div>
    <?php else: ?>
        <div class="list-stack">
            <?php foreach ($posts as $post): ?>
                <div class="list-item">
                    <div class="row-between">
                        <div>
                            <h3>
                                <a class="post-title-link" href="forum_post_view.php?post_id=<?= (int)$post['post_id'] ?>">
                                    <?= h($post['title'] ?: 'Untitled Post') ?>
                                </a>
                            </h3>
                            <div class="meta meta-line">
                                <span><?= h($post['visibility']) ?></span>
                                <span class="separator">&middot;</span>
                                <span><?= h($post['created_at']) ?></span>
                            </div>
                        </div>

                        <?php if ((int)$post['user_id'] === $viewerId): ?>
                            <a class="btn btn-danger" href="forum_delete_post.php?post_id=<?= (int)$post['post_id'] ?>">Delete</a>
                        <?php endif; ?>
                    </div>

                    <div class="post-content"><?= h(mb_substr($post['content'], 0, 260)) ?><?= mb_strlen($post['content']) > 260 ? '...' : '' ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'forum_footer.php'; ?>
