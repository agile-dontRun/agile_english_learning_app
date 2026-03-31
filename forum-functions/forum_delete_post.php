<?php
require_once 'forum_common.php';
$post_id = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;
$stmt = $conn->prepare("UPDATE forum_posts SET is_deleted = 1 WHERE post_id = ? AND user_id = ?");
$stmt->bind_param("ii", $post_id, $current_user_id);
$stmt->execute();
header('Location: forum.php');
exit();
