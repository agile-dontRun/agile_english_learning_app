<?php
require_once 'forum_common.php';

$currentId = current_user_id();
$targetId = (int)($_GET['user_id'] ?? 0);
$action = $_GET['action'] ?? '';
$redirect = $_GET['redirect'] ?? '';
$keyword = $_GET['keyword'] ?? '';

if ($targetId <= 0 || $targetId === $currentId) {
    header('Location: forum.php');
    exit;
}

if ($action === 'follow') {
    $sql = "INSERT IGNORE INTO user_follows (follower_id, followed_id) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $currentId, $targetId);
    $stmt->execute();
    $stmt->close();
} elseif ($action === 'unfollow') {
    $sql = "DELETE FROM user_follows WHERE follower_id = ? AND followed_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $currentId, $targetId);
    $stmt->execute();
    $stmt->close();
}

sync_friendship($conn, $currentId, $targetId);

if ($redirect === 'inbox') {
    header('Location: forum_inbox.php?user_id=' . $targetId);
    exit;
}

if ($redirect === 'search') {
    header('Location: forum_search.php?keyword=' . urlencode($keyword));
    exit;
}

header('Location: forum_profile.php?user_id=' . $targetId);
exit;
?>