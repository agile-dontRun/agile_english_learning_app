<?php
require_once 'forum_common.php';

$commentId = (int)($_GET['comment_id'] ?? 0);
$postId = (int)($_GET['post_id'] ?? 0);
$userId = current_user_id();

if ($commentId <= 0) {
    header('Location: forum.php');
    exit;
}

$sql = "UPDATE forum_comments
        SET is_deleted = 1
        WHERE comment_id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $commentId, $userId);
$stmt->execute();
$stmt->close();

/* 删除评论附件 */
delete_media_records_by_owner($conn, 'forum_comment_media', 'comment_id', $commentId);

if ($postId > 0) {
    header('Location: forum_post_view.php?post_id=' . $postId . '&comment_deleted=1');
    exit;
}

header('Location: forum.php');
exit;
?>