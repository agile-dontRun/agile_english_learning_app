<?php
require_once 'forum_common.php';
$pageTitle = 'Community Forum';
$viewerId = current_user_id();

$sql = "SELECT p.*, u.username, u.nickname
        FROM forum_posts p
        JOIN users u ON p.user_id = u.user_id
        WHERE p.is_deleted = 0
        ORDER BY p.created_at DESC";
$result = $conn->query($sql);

$posts = [];
while ($row = $result->fetch_assoc()) {
    if (can_view_post($conn, $row, $viewerId)) {
        $posts[] = $row;
    }
}

include 'forum_header.php';
?>

<div class="card">
    <div class="card-header">
        <div>
            <h2>Latest Discussions</h2>
            <p class="section-intro">Browse recent posts from the community and join the conversations in a calmer, more structured space.</p>
        </div>
        <a class="btn btn-primary" href="forum_post_create.php">Start a Post</a>
    </div>
</div>

<?php if (empty($posts)): ?>
    <div class="card">
        <div class="empty-state">No community posts are visible yet. Start the first discussion.</div>
    </div>
<?php endif; ?>

<?php foreach ($posts as $post): ?>
    <div class="card">
        <div class="row-between">
            <div>
                <h3 style="font-size:1.55rem; font-weight:700; letter-spacing:0.01em; margin-bottom:10px;">
                    <a class="post-title-link" href="forum_post_view.php?post_id=<?= (int)$post['post_id'] ?>">
                        <?= h($post['title'] ?: 'Untitled Post') ?>
                    </a>
                </h3>
                <div class="meta meta-line">
                    <span>By</span>
                    <a class="link-user" href="forum_profile.php?user_id=<?= (int)$post['user_id'] ?>">
                        <?= h($post['nickname'] ?: $post['username']) ?>
                    </a>
                    <span class="separator">&middot;</span>
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

<?php include 'forum_footer.php'; ?>
