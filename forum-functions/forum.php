<?php
require_once 'forum_common.php';
$page_title = 'Forum Feed';

$sql = "SELECT p.*, u.username, u.nickname
        FROM forum_posts p
        JOIN users u ON p.user_id = u.user_id
        WHERE p.is_deleted = 0
        ORDER BY p.created_at DESC";
$result = $conn->query($sql);
$posts = [];
while ($row = $result->fetch_assoc()) {
    if (can_view_post($conn, $row, $current_user_id)) {
        $posts[] = $row;
    }
}

include 'forum_header.php';
?>
<div class="row-between" style="margin-bottom: 18px;">
    <div>
        <h2 style="margin:0 0 8px;">Community Forum</h2>
        <div class="meta">Post ideas, ask questions, and discuss learning topics with others.</div>
    </div>
    <a class="btn btn-primary" href="forum_post_create.php">Create Post</a>
</div>

<?php foreach ($posts as $post): ?>
    <div class="card">
        <div class="row-between">
            <div>
                <h3 style="margin:0 0 8px;">
                    <a href="forum_post_view.php?post_id=<?= (int)$post['post_id'] ?>" style="text-decoration:none;color:#111827;">
                        <?= h($post['title'] ?: 'Untitled Post') ?>
                    </a>
                </h3>
                <div class="meta">
                    By <?= h($post['nickname'] ?: $post['username']) ?> · <?= h($post['visibility']) ?> · <?= h($post['created_at']) ?>
                </div>
            </div>
            <?php if ((int)$post['user_id'] === $current_user_id): ?>
                <a class="btn btn-danger" href="forum_delete_post.php?post_id=<?= (int)$post['post_id'] ?>">Delete</a>
            <?php endif; ?>
        </div>
        <div class="post-content" style="margin-top:12px;"><?= h(mb_substr($post['content'], 0, 280)) ?><?= mb_strlen($post['content']) > 280 ? '...' : '' ?></div>
    </div>
<?php endforeach; ?>
<?php include 'forum_footer.php'; ?>
