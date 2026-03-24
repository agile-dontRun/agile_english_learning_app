<?php
require_once 'forum_common.php';
$post_id = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;
$error = '';

$stmt = $conn->prepare("SELECT p.*, u.username, u.nickname FROM forum_posts p JOIN users u ON p.user_id = u.user_id WHERE p.post_id = ? AND p.is_deleted = 0 LIMIT 1");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();

if (!$post || !can_view_post($conn, $post, $current_user_id)) {
    die('Post not found or not accessible.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = trim($_POST['content'] ?? '');
    $parent_comment_id = !empty($_POST['parent_comment_id']) ? (int)$_POST['parent_comment_id'] : null;
    $reply_to_user_id = !empty($_POST['reply_to_user_id']) ? (int)$_POST['reply_to_user_id'] : null;

    if ($content === '') {
        $error = 'Comment cannot be empty.';
    } else {
        $stmt = $conn->prepare("INSERT INTO forum_comments (post_id, user_id, parent_comment_id, reply_to_user_id, content) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiis", $post_id, $current_user_id, $parent_comment_id, $reply_to_user_id, $content);
        $stmt->execute();
        header('Location: forum_post_view.php?post_id=' . $post_id);
        exit();
    }
}

$stmt = $conn->prepare("SELECT c.*, u.username, u.nickname, ru.username AS reply_to_username, ru.nickname AS reply_to_nickname
                        FROM forum_comments c
                        JOIN users u ON c.user_id = u.user_id
                        LEFT JOIN users ru ON c.reply_to_user_id = ru.user_id
                        WHERE c.post_id = ? AND c.is_deleted = 0
                        ORDER BY c.created_at ASC");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$comments = $stmt->get_result();

$page_title = $post['title'] ?: 'Post';
include 'forum_header.php';
?>
<div class="card">
    <div class="row-between">
        <div>
            <h2 style="margin:0 0 8px;"><?= h($post['title'] ?: 'Untitled Post') ?></h2>
            <div class="meta">By <?= h($post['nickname'] ?: $post['username']) ?> · <?= h($post['visibility']) ?> · <?= h($post['created_at']) ?></div>
        </div>
        <?php if ((int)$post['user_id'] !== $current_user_id): ?>
            <a class="btn btn-outline" href="forum_profile.php?user_id=<?= (int)$post['user_id'] ?>">View Profile</a>
        <?php endif; ?>
    </div>
    <div class="post-content" style="margin-top:14px;"><?= h($post['content']) ?></div>
</div>

<div class="card">
    <h3>Leave a Comment</h3>
    <?php if ($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
    <form method="post">
        <input type="hidden" name="parent_comment_id" id="parent_comment_id">
        <input type="hidden" name="reply_to_user_id" id="reply_to_user_id">
        <div id="replying_to_box" class="alert alert-info" style="display:none;"></div>
        <textarea name="content" rows="4" required></textarea>
        <button class="btn btn-primary" type="submit">Comment</button>
    </form>
</div>

<div class="card">
    <h3>Comments</h3>
    <?php while ($comment = $comments->fetch_assoc()): ?>
        <div class="comment">
            <div class="row-between">
                <div>
                    <strong><?= h($comment['nickname'] ?: $comment['username']) ?></strong>
                    <?php if (!empty($comment['reply_to_user_id'])): ?>
                        <span class="reply-tag">@<?= h($comment['reply_to_nickname'] ?: $comment['reply_to_username']) ?></span>
                    <?php endif; ?>
                    <div class="meta"><?= h($comment['created_at']) ?></div>
                </div>
                <div>
                    <button class="btn btn-secondary reply-btn"
                            data-comment-id="<?= (int)$comment['comment_id'] ?>"
                            data-user-id="<?= (int)$comment['user_id'] ?>"
                            data-user-name="<?= h($comment['nickname'] ?: $comment['username']) ?>">Reply</button>
                    <?php if ((int)$comment['user_id'] === $current_user_id): ?>
                        <a class="btn btn-danger" href="forum_delete_comment.php?comment_id=<?= (int)$comment['comment_id'] ?>&post_id=<?= (int)$post_id ?>">Delete</a>
                    <?php endif; ?>
                </div>
            </div>
            <div style="margin-top:8px;"><?= h($comment['content']) ?></div>
        </div>
    <?php endwhile; ?>
</div>
<?php include 'forum_footer.php'; ?>
