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

<style>
    /* ... 之前样式 ... */
    .content-container { max-width: 1200px; margin: -40px auto 60px; padding: 0 20px; position: relative; z-index: 10; }
    .intro-card { background: var(--white); padding: 50px; border-radius: 8px; box-shadow: 0 15px 40px rgba(0,0,0,0.08); text-align: center; margin-bottom: 60px; }
    .type-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; }
    .type-card { background: var(--white); padding: 40px 30px; border-radius: 8px; border-top: 4px solid var(--oxford-blue); transition: 0.3s; }
    .type-card:hover { transform: translateY(-5px); border-top-color: var(--oxford-gold); }
</style>

<main class="content-container">
    <div class="intro-card">
        <h2>What is Plagiarism?</h2>
        <p>At university level, plagiarism is a fundamental breach of academic trust.</p>
    </div>
    <h2 style="text-align: center;">Common Types of Plagiarism</h2>
    <div class="type-grid">
        <div class="type-card"><h3>Direct Plagiarism</h3><p>Word-for-word copying without citation.</p></div>
        </div>
</main>

<style>
    /* ... 之前样式 ... */
    .consequences-section { background: #8B0000; color: var(--white); padding: 60px; border-radius: 8px; border-left: 10px solid var(--oxford-gold); margin: 60px 0; }
    .prevention-section { background: var(--white); padding: 60px; border-radius: 8px; border: 1px solid #e0e0e0; }
    .tip-row { display: flex; gap: 20px; padding-bottom: 20px; border-bottom: 1px dashed #e0e0e0; }
    .tip-number { background: var(--oxford-blue); color: white; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; }
</style>

<div class="consequences-section">
    <h2>The Consequences</h2>
    <p>Universities maintain a Zero-Tolerance Policy. Penalties include grade penalties, probation, or expulsion.</p>
</div>

<div class="prevention-section">
    <h2>How to Avoid Plagiarism</h2>
    <div class="tip-row">
        <div class="tip-number">1</div>
        <div class="tip-content"><h4>Cite Your Sources Properly</h4><p>Use APA, MLA, or Harvard formats.</p></div>
    </div>
    </div>

    <style>
    /* ... 之前样式 ... */
    .pledge-area { text-align: center; margin-top: 50px; padding-top: 40px; border-top: 2px solid #e0e0e0; }
    .btn-pledge { background: var(--oxford-blue); color: white; padding: 18px 50px; font-weight: 800; text-transform: uppercase; cursor: pointer; transition: 0.3s; border-radius: 4px; border: none; }
    .btn-pledge:hover { background: var(--oxford-gold); color: var(--oxford-blue); }
    .footer { background-color: var(--oxford-blue); color: var(--white); padding: 60px 40px 30px; margin-top: 60px; border-top: 5px solid var(--oxford-gold); }
</style>

<div class="pledge-area">
    <h3>The Scholar's Pledge</h3>
    <button class="btn-pledge" onclick="window.location.href='home.php'">I Understand & Agree</button>
</div>

<footer class="footer">
    <div class="footer-content">
        <h3>Spires Academy</h3>
        <p>Dedicated to fostering intellectual curiosity and academic excellence.</p>
    </div>
</footer>