<?php
require_once 'forum_common.php';
$comment_id = isset($_GET['comment_id']) ? (int)$_GET['comment_id'] : 0;
$post_id = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;
$stmt = $conn->prepare("UPDATE forum_comments SET is_deleted = 1 WHERE comment_id = ? AND user_id = ?");
$stmt->bind_param("ii", $comment_id, $current_user_id);
$stmt->execute();
header('Location: forum_post_view.php?post_id=' . $post_id);
exit();
