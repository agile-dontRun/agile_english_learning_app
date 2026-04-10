<?php
require_once 'forum_common.php';
$target_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$action = $_GET['action'] ?? '';

if ($target_user_id <= 0 || $target_user_id === $current_user_id) {
    header('Location: forum.php');
    exit();
}

if ($action === 'follow') {
    $stmt = $conn->prepare("INSERT IGNORE INTO user_follows (follower_id, followed_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $current_user_id, $target_user_id);
    $stmt->execute();
} elseif ($action === 'unfollow') {
    $stmt = $conn->prepare("DELETE FROM user_follows WHERE follower_id = ? AND followed_id = ?");
    $stmt->bind_param("ii", $current_user_id, $target_user_id);
    $stmt->execute();
}

sync_friendship($conn, $current_user_id, $target_user_id);
header('Location: forum_profile.php?user_id=' . $target_user_id);
exit();
