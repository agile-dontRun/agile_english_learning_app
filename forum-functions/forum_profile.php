<?php
require_once 'forum_common.php';
$profile_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : $current_user_id;
$user = find_user($conn, $profile_user_id);
if (!$user) {
    die('User not found.');
}

$is_following = is_following($conn, $current_user_id, $profile_user_id);
$is_friend = are_friends($conn, $current_user_id, $profile_user_id);

$stmt = $conn->prepare("SELECT * FROM forum_posts WHERE user_id = ? AND is_deleted = 0 ORDER BY created_at DESC");
$stmt->bind_param("i", $profile_user_id);
$stmt->execute();
$res = $stmt->get_result();
$posts = [];
while ($row = $res->fetch_assoc()) {
    if (can_view_post($conn, $row, $current_user_id)) $posts[] = $row;
}

$page_title = 'Forum Profile';
include 'forum_header.php';
?>
<div class="card">
    <div class="row-between">
        <div>
            <h2 style="margin:0;"><?= h($user['nickname'] ?: $user['username']) ?></h2>
            <div class="meta">@<?= h($user['username']) ?> · <?= h($user['student_level'] ?: 'unknown') ?></div>
        </div>
        <?php if ($profile_user_id !== $current_user_id): ?>
            <div>
                <?php if ($is_following): ?>
                    <a class="btn btn-secondary" href="forum_follow_action.php?user_id=<?= $profile_user_id ?>&action=unfollow">Unfollow</a>
                <?php else: ?>
                    <a class="btn btn-primary" href="forum_follow_action.php?user_id=<?= $profile_user_id ?>&action=follow">Follow</a>
                <?php endif; ?>
                <a class="btn btn-dark" href="forum_inbox.php?user_id=<?= $profile_user_id ?>">Message</a>
            </div>
        <?php endif; ?>
    </div>
    <?php if ($is_friend): ?>
        <div class="alert alert-info" style="margin-top:12px;">You are friends.</div>
    <?php endif; ?>
</div>

<?php foreach ($posts as $post): ?>
    <div class="card">
        <h3 style="margin:0 0 8px;"><a href="forum_post_view.php?post_id=<?= (int)$post['post_id'] ?>" style="text-decoration:none;color:#111827;"><?= h($post['title'] ?: 'Untitled Post') ?></a></h3>
        <div class="meta"><?= h($post['created_at']) ?> · <?= h($post['visibility']) ?></div>
        <div class="post-content" style="margin-top:10px;"><?= h(mb_substr($post['content'], 0, 220)) ?><?= mb_strlen($post['content']) > 220 ? '...' : '' ?></div>
    </div>
<?php endforeach; ?>
<?php include 'forum_footer.php'; ?>
