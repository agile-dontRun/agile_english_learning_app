<?php
require_once 'forum_common.php';
$page_title = 'Create Post';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $visibility = $_POST['visibility'] ?? 'public';
    $allowed = ['public', 'followers_only', 'friends_only', 'private'];

    if ($content === '') {
        $error = 'Content cannot be empty.';
    } elseif (!in_array($visibility, $allowed, true)) {
        $error = 'Invalid visibility.';
    } else {
        $stmt = $conn->prepare("INSERT INTO forum_posts (user_id, title, content, visibility) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $current_user_id, $title, $content, $visibility);
        $stmt->execute();
        header('Location: forum.php');
        exit();
    }
}

include 'forum_header.php';
?>
<div class="card">
    <h2>Create a New Post</h2>
    <?php if ($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
    <form method="post">
        <label>Title</label>
        <input type="text" name="title" maxlength="255">

        <label>Content</label>
        <textarea name="content" rows="10" required></textarea>

        <label>Visibility</label>
        <select name="visibility">
            <option value="public">Public</option>
            <option value="followers_only">Followers only</option>
            <option value="friends_only">Friends only</option>
            <option value="private">Private</option>
        </select>

        <button class="btn btn-primary" type="submit">Publish</button>
    </form>
</div>
<?php include 'forum_footer.php'; ?>
