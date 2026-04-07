<?php
session_start();
// 1. 严格登录验证与数据库连接
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }
require_once 'db_connect.php'; 

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Academic Integrity - Spires Academy</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=Lora:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root { --oxford-blue: #002147; --oxford-gold: #c4a661; --white: #ffffff; --bg-light: #f4f7f6; }
        body { margin: 0; font-family: 'Open Sans', sans-serif; background-color: var(--bg-light); }
        .navbar { background-color: var(--oxford-blue); color: var(--white); display: flex; justify-content: space-between; align-items: center; padding: 0 40px; height: 80px; position: sticky; top: 0; z-index: 1000; }
        .hero { background: linear-gradient(rgba(0, 33, 71, 0.7), rgba(0, 33, 71, 0.8)), url('hero_bg2.png') center/cover; color: var(--white); text-align: center; padding: 120px 20px 100px; border-bottom: 5px solid var(--oxford-gold); }
        .hero h1 { font-family: 'Playfair Display', serif; font-size: 4.5rem; text-transform: uppercase; letter-spacing: 5px; margin: 0; }
    </style>
</head>
<body>
    <nav class="navbar"> </nav>
    <header class="hero">
        <h1>Academic Integrity</h1>
        <p>Understanding Plagiarism & Upholding Standards.</p>
    </header>
</body>
</html>