<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];
$current_nickname = $_SESSION['nickname'] ?? $_SESSION['username'] ?? 'Learner';

function h($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function find_user(mysqli $conn, int $user_id): ?array {
    $stmt = $conn->prepare("SELECT user_id, username, nickname, avatar_url, student_level FROM users WHERE user_id = ? LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    return $res->fetch_assoc() ?: null;
}

function is_following(mysqli $conn, int $follower_id, int $followed_id): bool {
    $stmt = $conn->prepare("SELECT 1 FROM user_follows WHERE follower_id = ? AND followed_id = ? LIMIT 1");
    $stmt->bind_param("ii", $follower_id, $followed_id);
    $stmt->execute();
    return (bool)$stmt->get_result()->fetch_row();
}

function sorted_pair(int $a, int $b): array {
    return $a < $b ? [$a, $b] : [$b, $a];
}

function are_friends(mysqli $conn, int $a, int $b): bool {
    [$u1, $u2] = sorted_pair($a, $b);
    $stmt = $conn->prepare("SELECT 1 FROM user_friendships WHERE user_id_1 = ? AND user_id_2 = ? LIMIT 1");
    $stmt->bind_param("ii", $u1, $u2);
    $stmt->execute();
    return (bool)$stmt->get_result()->fetch_row();
}

function sync_friendship(mysqli $conn, int $a, int $b): void {
    if ($a === $b) return;

    $follow_ab = is_following($conn, $a, $b);
    $follow_ba = is_following($conn, $b, $a);
    [$u1, $u2] = sorted_pair($a, $b);

    if ($follow_ab && $follow_ba) {
        $stmt = $conn->prepare("INSERT IGNORE INTO user_friendships (user_id_1, user_id_2) VALUES (?, ?)");
        $stmt->bind_param("ii", $u1, $u2);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("DELETE FROM user_friendships WHERE user_id_1 = ? AND user_id_2 = ?");
        $stmt->bind_param("ii", $u1, $u2);
        $stmt->execute();
    }
}

function can_view_post(mysqli $conn, array $post, int $viewer_id): bool {
    $owner_id = (int)$post['user_id'];
    if ($owner_id === $viewer_id) return true;

    switch ($post['visibility']) {
        case 'public':
            return true;
        case 'followers_only':
            return is_following($conn, $viewer_id, $owner_id);
        case 'friends_only':
            return are_friends($conn, $viewer_id, $owner_id);
        case 'private':
        default:
            return false;
    }
}

function message_limit_reached(mysqli $conn, int $sender_id, int $receiver_id): bool {
    if (are_friends($conn, $sender_id, $receiver_id)) {
        return false;
    }

    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM private_messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)");
    $stmt->bind_param("iiii", $sender_id, $receiver_id, $receiver_id, $sender_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    return ((int)$res['total']) >= 1;
}
