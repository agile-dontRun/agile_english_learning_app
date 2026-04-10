<?php
require_once 'forum_common.php';

$senderId = current_user_id();
$receiverId = (int)($_POST['receiver_id'] ?? 0);
$content = trim($_POST['content'] ?? '');
$recordedAudio = trim($_POST['recorded_audio_data'] ?? '');

$hasUploads = isset($_FILES['message_media_files'])
    && isset($_FILES['message_media_files']['name'])
    && is_array($_FILES['message_media_files']['name'])
    && !empty($_FILES['message_media_files']['name'][0]);

$hasRecordedAudio = ($recordedAudio !== '');

if ($receiverId <= 0 || $receiverId === $senderId) {
    header('Location: forum_inbox.php?user_id=' . $receiverId . '&error=invalid_receiver');
    exit;
}

if ($content === '' && !$hasUploads && !$hasRecordedAudio) {
    header('Location: forum_inbox.php?user_id=' . $receiverId . '&error=empty');
    exit;
}

if (message_limit_reached($conn, $senderId, $receiverId)) {
    header('Location: forum_inbox.php?user_id=' . $receiverId . '&error=limit');
    exit;
}

$allMedia = [];

if ($hasUploads) {
    $uploadedMedia = upload_forum_media_files($_FILES['message_media_files'], 'messages');
    if (!empty($uploadedMedia)) {
        $allMedia = array_merge($allMedia, $uploadedMedia);
    }
}

if ($hasRecordedAudio) {
    $audioMedia = save_base64_audio($recordedAudio, 'messages', 'message_voice');
    if ($audioMedia) {
        $allMedia[] = $audioMedia;
    }
}

if ($content === '' && empty($allMedia)) {
    header('Location: forum_inbox.php?user_id=' . $receiverId . '&error=record_failed');
    exit;
}

$sql = "INSERT INTO private_messages (sender_id, receiver_id, content, is_read)
        VALUES (?, ?, ?, 0)";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    cleanup_saved_media_files($allMedia);
    header('Location: forum_inbox.php?user_id=' . $receiverId . '&error=sql_prepare');
    exit;
}

$stmt->bind_param("iis", $senderId, $receiverId, $content);

if (!$stmt->execute()) {
    $stmt->close();
    cleanup_saved_media_files($allMedia);
    header('Location: forum_inbox.php?user_id=' . $receiverId . '&error=send_failed');
    exit;
}

$messageId = $stmt->insert_id;
$stmt->close();

if (!empty($allMedia)) {
    insert_media_records($conn, 'private_message_media', 'message_id', $messageId, $allMedia);
}

header('Location: forum_inbox.php?user_id=' . $receiverId . '&sent=1');
exit;
?>
