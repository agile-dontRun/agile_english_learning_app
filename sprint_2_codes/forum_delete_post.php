<?php
require_once 'forum_common.php';

$postId = (int)($_GET['post_id'] ?? 0);
$userId = current_user_id();

if ($postId <= 0) {
    header('Location: forum.php');
    exit;
}

$sql = "UPDATE forum_posts
        SET is_deleted = 1
        WHERE post_id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $postId, $userId);
$stmt->execute();
$stmt->close();

header('Location: forum.php?deleted=1');
exit;
?>
