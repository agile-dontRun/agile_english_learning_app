<?php
require_once 'forum_common.php';

$messageId = (int)($_GET['message_id'] ?? 0);
$otherId = (int)($_GET['user_id'] ?? 0);
$currentId = current_user_id();

if ($messageId <= 0) {
    header('Location: forum_inbox.php');
    exit;
}

/* 只允许删除自己发出的消息 */
$sql = "SELECT sender_id FROM private_messages WHERE message_id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $messageId);
$stmt->execute();
$result = $stmt->get_result();
$message = $result->fetch_assoc();
$stmt->close();

if (!$message || (int)$message['sender_id'] !== $currentId) {
    header('Location: forum_inbox.php?user_id=' . $otherId);
    exit;
}

/* 先删附件 */
delete_media_records_by_owner($conn, 'private_message_media', 'message_id', $messageId);

/* 再删消息 */
$sql = "DELETE FROM private_messages WHERE message_id = ? AND sender_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $messageId, $currentId);
$stmt->execute();
$stmt->close();

header('Location: forum_inbox.php?user_id=' . $otherId . '&message_deleted=1');
exit;
?>