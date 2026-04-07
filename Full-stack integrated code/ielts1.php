<?php
session_start();
require_once 'db_connect.php';

// 1. 严格登录验证
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
$avatar_html = (!empty($db_avatar)) ? '<img src="'.htmlspecialchars($db_avatar).'" class="user-avatar-img">' : '<div class="user-avatar-placeholder">'.strtoupper(substr($username,0,1)).'</div>';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>IELTS Practice - Spires Academy</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
    <style>
        :root { --oxford-blue: #002147; --oxford-gold: #c4a661; --white: #ffffff; }
        body { margin: 0; font-family: 'Open Sans', sans-serif; background-color: #f4f7f6; }
        .navbar { background-color: var(--oxford-blue); color: white; display: flex; justify-content: space-between; align-items: center; padding: 0 40px; height: 80px; position: sticky; top: 0; z-index: 1000; }
        .navbar-links a { color: white; text-decoration: none; font-family: 'Playfair Display'; font-weight: 800; text-transform: uppercase; padding: 0 20px; }
    </style>
</head>
<body>
    <nav class="navbar">
        </nav>
    <header class="hero" style="background: url('hero_bg2.png') center/cover; text-align: center; padding: 140px 20px; color: white;">
        <h1 style="font-family: 'Playfair Display'; font-size: 5rem; text-transform: uppercase;">IELTS Listening</h1>
    </header>
</body>
</html>