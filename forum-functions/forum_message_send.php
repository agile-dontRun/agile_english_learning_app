<?php
require_once 'forum_common.php';
$receiver_id = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
$content = trim($_POST['content'] ?? '');

if ($receiver_id <= 0 || $receiver_id === $current_user_id || $content === '') {
    header('Location: forum_inbox.php?user_id=' . $receiver_id);
    exit();
}

if (message_limit_reached($conn, $current_user_id, $receiver_id)) {
    header('Location: forum_inbox.php?user_id=' . $receiver_id . '&error=limit');
    exit();
}

$stmt = $conn->prepare("INSERT INTO private_messages (sender_id, receiver_id, content) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $current_user_id, $receiver_id, $content);
$stmt->execute();
header('Location: forum_inbox.php?user_id=' . $receiver_id);
exit();
