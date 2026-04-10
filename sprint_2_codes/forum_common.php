<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

function current_user_id(): int {
    return (int)$_SESSION['user_id'];
}

function current_nickname(): string {
    return $_SESSION['nickname'] ?? 'Learner';
}

function h($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function find_user(mysqli $conn, int $userId): ?array {
    $sql = "SELECT user_id, username, nickname, avatar_url, student_level FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    return $user ?: null;
}

function is_following(mysqli $conn, int $followerId, int $followedId): bool {
    $sql = "SELECT 1 FROM user_follows WHERE follower_id = ? AND followed_id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $followerId, $followedId);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = (bool)$result->fetch_row();
    $stmt->close();
    return $exists;
}

function friendship_pair(int $a, int $b): array {
    return $a < $b ? [$a, $b] : [$b, $a];
}

function are_friends(mysqli $conn, int $a, int $b): bool {
    [$u1, $u2] = friendship_pair($a, $b);
    $sql = "SELECT 1 FROM user_friendships WHERE user_id_1 = ? AND user_id_2 = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $u1, $u2);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = (bool)$result->fetch_row();
    $stmt->close();
    return $exists;
}

function sync_friendship(mysqli $conn, int $a, int $b): void {
    if ($a === $b) return;

    $followAB = is_following($conn, $a, $b);
    $followBA = is_following($conn, $b, $a);
    [$u1, $u2] = friendship_pair($a, $b);

    if ($followAB && $followBA) {
        $sql = "INSERT IGNORE INTO user_friendships (user_id_1, user_id_2) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $u1, $u2);
        $stmt->execute();
        $stmt->close();
    } else {
        $sql = "DELETE FROM user_friendships WHERE user_id_1 = ? AND user_id_2 = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $u1, $u2);
        $stmt->execute();
        $stmt->close();
    }
}

function can_view_post(mysqli $conn, array $post, int $viewerId): bool {
    if ((int)$post['user_id'] === $viewerId) return true;

    switch ($post['visibility']) {
        case 'public':
            return true;
        case 'followers_only':
            return is_following($conn, $viewerId, (int)$post['user_id']);
        case 'friends_only':
            return are_friends($conn, $viewerId, (int)$post['user_id']);
        case 'private':
            return false;
        default:
            return false;
    }
}

function message_limit_reached(mysqli $conn, int $senderId, int $receiverId): bool {
    if (are_friends($conn, $senderId, $receiverId)) {
        return false;
    }

    $sql = "SELECT COUNT(*) AS total
            FROM private_messages
            WHERE (sender_id = ? AND receiver_id = ?)
               OR (sender_id = ? AND receiver_id = ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiii", $senderId, $receiverId, $receiverId, $senderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return ((int)$row['total']) >= 1;
}

function unread_message_count(mysqli $conn, int $userId): int {
    $sql = "SELECT COUNT(*) AS total
            FROM private_messages
            WHERE receiver_id = ? AND is_read = 0 AND is_deleted_by_receiver = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return (int)($row['total'] ?? 0);
}

function unread_comment_count(mysqli $conn, int $userId): int {
    $sql = "SELECT COUNT(*) AS total
            FROM forum_comments c
            JOIN forum_posts p ON c.post_id = p.post_id
            WHERE p.user_id = ?
              AND c.user_id <> ?
              AND c.is_deleted = 0
              AND c.is_seen_by_post_author = 0
              AND p.is_deleted = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $userId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return (int)($row['total'] ?? 0);
}

function ensure_private_call_tables(mysqli $conn): void {
    static $ensured = false;

    if ($ensured) {
        return;
    }

    $conn->query("
        CREATE TABLE IF NOT EXISTS private_call_sessions (
            call_id BIGINT PRIMARY KEY AUTO_INCREMENT,
            user_id_1 BIGINT NOT NULL,
            user_id_2 BIGINT NOT NULL,
            initiated_by BIGINT NOT NULL,
            call_type ENUM('audio', 'video') NOT NULL DEFAULT 'audio',
            status ENUM('ringing', 'active', 'ended', 'declined', 'missed', 'failed') NOT NULL DEFAULT 'ringing',
            started_at DATETIME NULL,
            ended_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_call_pair_status (user_id_1, user_id_2, status),
            INDEX idx_call_initiated_by (initiated_by)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $conn->query("
        CREATE TABLE IF NOT EXISTS private_call_signals (
            signal_id BIGINT PRIMARY KEY AUTO_INCREMENT,
            call_id BIGINT NOT NULL,
            sender_id BIGINT NOT NULL,
            receiver_id BIGINT NOT NULL,
            signal_type VARCHAR(20) NOT NULL,
            payload LONGTEXT NULL,
            is_delivered TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_signal_receiver (receiver_id, is_delivered, signal_id),
            INDEX idx_signal_call (call_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $ensured = true;
}

/* =========================
   上传单个文件
   目录和代码同级：posts / comments / messages
   ========================= */
function save_single_uploaded_media(array $file, string $targetFolder): ?array {
    if (!isset($file['tmp_name']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowedImageExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $allowedVideoExt = ['mp4', 'webm', 'ogg', 'mov'];
    $allowedAudioExt = ['mp3', 'wav', 'm4a', 'aac', 'ogg', 'webm'];

    $originalName = $file['name'] ?? 'file';
    $tmpName = $file['tmp_name'];
    $size = (int)($file['size'] ?? 0);

    $maxSize = 50 * 1024 * 1024;
    if ($size <= 0 || $size > $maxSize) {
        return null;
    }

    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $fileType = null;

    if (in_array($ext, $allowedImageExt, true)) {
        $fileType = 'image';
    } elseif (in_array($ext, $allowedVideoExt, true)) {
        $fileType = 'video';
    } elseif (in_array($ext, $allowedAudioExt, true)) {
        $fileType = 'audio';
    } else {
        return null;
    }

    $safeName = uniqid('media_', true) . '.' . $ext;

    $absoluteDir = __DIR__ . '/' . $targetFolder . '/';
    if (!is_dir($absoluteDir)) {
        mkdir($absoluteDir, 0755, true);
    }

    $absolutePath = $absoluteDir . $safeName;
    $relativePath = $targetFolder . '/' . $safeName;

    if (!move_uploaded_file($tmpName, $absolutePath)) {
        return null;
    }

    return [
        'file_path' => $relativePath,
        'file_type' => $fileType,
        'original_name' => $originalName
    ];
}

/* =========================
   多文件上传
   input name="media_files[]"
   ========================= */
function upload_forum_media_files(array $files, string $targetFolder): array {
    $saved = [];

    if (!isset($files['name']) || !is_array($files['name'])) {
        return $saved;
    }

    $count = count($files['name']);
    for ($i = 0; $i < $count; $i++) {
        $single = [
            'name' => $files['name'][$i] ?? '',
            'type' => $files['type'][$i] ?? '',
            'tmp_name' => $files['tmp_name'][$i] ?? '',
            'error' => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'][$i] ?? 0
        ];

        $media = save_single_uploaded_media($single, $targetFolder);
        if ($media) {
            $saved[] = $media;
        }
    }

    return $saved;
}

/* =========================
   保存浏览器录音（base64）
   ========================= */
function save_base64_audio(string $dataUrl, string $targetFolder, string $baseName = 'voice'): ?array {
    if (trim($dataUrl) === '') {
        return null;
    }

    if (!preg_match('/^data:(audio\/[a-zA-Z0-9.+-]+)(?:;[^,]*)*;base64,(.+)$/', $dataUrl, $matches)) {
        return null;
    }

    $mime = strtolower(trim($matches[1]));
    $base64 = $matches[2];

    $mimeMap = [
        'audio/webm' => 'webm',
        'audio/ogg'  => 'ogg',
        'audio/wav'  => 'wav',
        'audio/x-wav' => 'wav',
        'audio/mp3'  => 'mp3',
        'audio/mpeg' => 'mp3',
        'audio/mp4'  => 'm4a',
        'audio/aac'  => 'aac'
    ];

    if (!isset($mimeMap[$mime])) {
        return null;
    }

    $binary = base64_decode($base64, true);
    if ($binary === false || strlen($binary) === 0) {
        return null;
    }

    $ext = $mimeMap[$mime];
    $safeName = uniqid($baseName . '_', true) . '.' . $ext;

    $absoluteDir = __DIR__ . '/' . $targetFolder . '/';
    if (!is_dir($absoluteDir)) {
        mkdir($absoluteDir, 0755, true);
    }

    $absolutePath = $absoluteDir . $safeName;
    $relativePath = $targetFolder . '/' . $safeName;

    if (file_put_contents($absolutePath, $binary) === false) {
        return null;
    }

    return [
        'file_path' => $relativePath,
        'file_type' => 'audio',
        'original_name' => $safeName
    ];
}

function cleanup_saved_media_files(array $mediaItems): void {
    foreach ($mediaItems as $media) {
        $filePath = $media['file_path'] ?? '';
        if ($filePath === '') {
            continue;
        }

        $absolutePath = __DIR__ . '/' . ltrim($filePath, '/');
        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }
    }
}

/* =========================
   存媒体记录
   ========================= */
function insert_media_records(mysqli $conn, string $table, string $ownerColumn, int $ownerId, array $mediaItems): void {
    $allow = [
        'forum_post_media' => 'post_id',
        'forum_comment_media' => 'comment_id',
        'private_message_media' => 'message_id'
    ];

    if (!isset($allow[$table]) || $allow[$table] !== $ownerColumn) {
        return;
    }

    $sql = "INSERT INTO {$table} ({$ownerColumn}, file_path, file_type, original_name)
            VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    foreach ($mediaItems as $media) {
        $stmt->bind_param(
            "isss",
            $ownerId,
            $media['file_path'],
            $media['file_type'],
            $media['original_name']
        );
        $stmt->execute();
    }

    $stmt->close();
}

/* =========================
   取某条内容下的媒体
   ========================= */
function get_media_by_owner(mysqli $conn, string $table, string $ownerColumn, int $ownerId): array {
    $allow = [
        'forum_post_media' => 'post_id',
        'forum_comment_media' => 'comment_id',
        'private_message_media' => 'message_id'
    ];

    if (!isset($allow[$table]) || $allow[$table] !== $ownerColumn) {
        return [];
    }

    $sql = "SELECT * FROM {$table} WHERE {$ownerColumn} = ? ORDER BY media_id ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $ownerId);
    $stmt->execute();
    $result = $stmt->get_result();

    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    $stmt->close();

    return $items;
}

/* =========================
   删除媒体文件 + 删除媒体记录
   ========================= */
function delete_media_records_by_owner(mysqli $conn, string $table, string $ownerColumn, int $ownerId): void {
    $allow = [
        'forum_post_media' => 'post_id',
        'forum_comment_media' => 'comment_id',
        'private_message_media' => 'message_id'
    ];

    if (!isset($allow[$table]) || $allow[$table] !== $ownerColumn) {
        return;
    }

    $sql = "SELECT file_path FROM {$table} WHERE {$ownerColumn} = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $ownerId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $absolutePath = __DIR__ . '/' . $row['file_path'];
        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }
    }
    $stmt->close();

    $sql = "DELETE FROM {$table} WHERE {$ownerColumn} = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $ownerId);
    $stmt->execute();
    $stmt->close();
}
?>
