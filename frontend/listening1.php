<?php
session_start();
require_once 'db_connect.php'; 

// 1. 严格登录验证逻辑
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 2. 获取用户资料用于导航栏头像
$username = ''; $nickname = 'Student'; $db_avatar = '';
$stmt = $conn->prepare("SELECT username, nickname, avatar_url FROM users WHERE user_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_data = $stmt->get_result()->fetch_assoc();
    if ($user_data) {
        $username = $user_data['username'];
        $nickname = !empty($user_data['nickname']) ? $user_data['nickname'] : $username;
        $db_avatar = $user_data['avatar_url'];
    }
    $stmt->close();
}

// 动态头像生成逻辑
$avatar_html = '';
$first_letter = strtoupper(substr($username ? $username : 'U', 0, 1));
if (!empty($db_avatar)) {
    $avatar_html = '<img src="' . htmlspecialchars($db_avatar) . '" alt="Avatar" class="user-avatar-img" onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'flex\';">';
    $avatar_html .= '<div class="user-avatar-placeholder" style="display:none;">' . htmlspecialchars($first_letter) . '</div>';
} else {
    $avatar_html = '<div class="user-avatar-placeholder">' . htmlspecialchars($first_letter) . '</div>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Listening Center - Spires Academy</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=Lora:wght@700&display=swap" rel="stylesheet">
    <style>
        :root { --oxford-blue: #002147; --oxford-gold: #c4a661; --white: #ffffff; --bg-light: #f4f7f6; }
        body { margin: 0; font-family: 'Open Sans', sans-serif; background-color: var(--bg-light); }
        /* 导航栏样式 */
        .navbar { background-color: var(--oxford-blue); color: var(--white); display: flex; justify-content: space-between; align-items: center; padding: 0 40px; height: 80px; position: sticky; top: 0; z-index: 1000; box-shadow: 0 2px 10px rgba(0,0,0,0.2); }
        .navbar-links a { color: #ffffff; text-decoration: none; font-family: 'Playfair Display', serif; font-size: 16px; font-weight: 800; padding: 0 20px; text-transform: uppercase; letter-spacing: 1.8px; }
    </style>
</head>
<body>
    <nav class="navbar">
        </nav>
</body>
</html>