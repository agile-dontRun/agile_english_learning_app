<?php
require_once 'forum_common.php';

$pageTitle = 'Search';
$currentId = current_user_id();
$keyword = trim($_GET['keyword'] ?? '');

$users = [];
$posts = [];

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

    $sql = "SELECT p.*, u.username, u.nickname
            FROM forum_posts p
            JOIN users u ON p.user_id = u.user_id
            WHERE p.is_deleted = 0
              AND p.title LIKE ?
            ORDER BY p.created_at DESC
            LIMIT 50";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        if (can_view_post($conn, $row, $currentId)) {
            $posts[] = $row;
        }
    }
    $stmt->close();
}

include 'forum_header.php';
?>

<div class="card">
    <div class="card-header">
        <div>
            <h2>Search Community</h2>
            <p class="section-intro">Search by username, nickname, or post title and jump straight to people or discussions.</p>
        </div>
    </div>

    <form method="get" action="forum_search.php">
        <input type="text" name="keyword" placeholder="Search users or post titles..." value="<?= h($keyword) ?>">
        <button class="btn btn-primary" type="submit">Search</button>
    </form>
</div>

<?php if ($keyword !== ''): ?>
    <div class="two-column">
        <div class="card">
            <div class="card-header">
                <div>
                    <h3>User Results</h3>
                    <p class="section-intro">People matching "<?= h($keyword) ?>".</p>
                </div>
            </div>

            <?php if (empty($users)): ?>
                <div class="empty-state">No users found.</div>
            <?php else: ?>
                <div class="list-stack">
                    <?php foreach ($users as $user): ?>
                        <?php $alreadyFollowing = is_following($conn, $currentId, (int)$user['user_id']); ?>
                        <div class="list-item">
                            <div class="row-between">
                                <div>
                                    <a class="link-user" href="forum_profile.php?user_id=<?= (int)$user['user_id'] ?>">
                                        <?= h($user['nickname'] ?: $user['username']) ?>
                                    </a>
                                    <div class="meta meta-line">
                                        <span>@<?= h($user['username']) ?></span>
                                        <?php if (!empty($user['student_level'])): ?>
                                            <span class="separator">&middot;</span>
                                            <span><?= h($user['student_level']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="actions-row">
                                    <a class="btn btn-secondary" href="forum_profile.php?user_id=<?= (int)$user['user_id'] ?>">View Profile</a>
                                    <a class="btn btn-dark" href="forum_inbox.php?user_id=<?= (int)$user['user_id'] ?>">Message</a>
                                    <?php if ($alreadyFollowing): ?>
                                        <a class="btn btn-secondary" href="forum_follow_action.php?user_id=<?= (int)$user['user_id'] ?>&action=unfollow&redirect=search&keyword=<?= urlencode($keyword) ?>">Unfollow</a>
                                    <?php else: ?>
                                        <a class="btn btn-primary" href="forum_follow_action.php?user_id=<?= (int)$user['user_id'] ?>&action=follow&redirect=search&keyword=<?= urlencode($keyword) ?>">Follow</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <h3>Post Results</h3>
                    <p class="section-intro">Discussion titles matching "<?= h($keyword) ?>".</p>
                </div>
            </div>

            <?php if (empty($posts)): ?>
                <div class="empty-state">No posts found.</div>
            <?php else: ?>
                <div class="list-stack">
                    <?php foreach ($posts as $post): ?>
                        <div class="list-item">
                            <div class="row-between">
                                <div style="flex:1;">
                                    <h4>
                                        <a class="post-title-link" href="forum_post_view.php?post_id=<?= (int)$post['post_id'] ?>">
                                            <?= h($post['title'] ?: 'Untitled Post') ?>
                                        </a>
                                    </h4>

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

                                    <?php if (!empty($post['content'])): ?>
                                        <div class="post-content"><?= h(mb_substr($post['content'], 0, 180)) ?><?= mb_strlen($post['content']) > 180 ? '...' : '' ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="actions-row">
                                    <a class="btn btn-secondary" href="forum_post_view.php?post_id=<?= (int)$post['post_id'] ?>">View Post</a>
                                    <a class="btn btn-secondary" href="forum_profile.php?user_id=<?= (int)$post['user_id'] ?>">View Author</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php include 'forum_footer.php'; ?>
