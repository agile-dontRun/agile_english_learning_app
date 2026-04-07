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

/* --- 包含 #351 代码，新增以下业务逻辑 --- */

// A. 获取 URL 参数
$cam = isset($_GET['cam']) ? (int)$_GET['cam'] : 0;
$test = isset($_GET['test']) ? (int)$_GET['test'] : 0;
$part_id = isset($_GET['part_id']) ? (int)$_GET['part_id'] : 0;

// B. 数据库层级查询逻辑
$books = [];
$res_books = $conn->query("SELECT DISTINCT cambridge_no FROM ielts_listening_parts ORDER BY cambridge_no DESC");
while($row = $res_books->fetch_assoc()) { $books[] = $row['cambridge_no']; }

// 如果选择了书，获取对应的 Test 列表
$tests = [];
if ($cam > 0) {
    $stmt = $conn->prepare("SELECT DISTINCT test_no FROM ielts_listening_parts WHERE cambridge_no = ? ORDER BY test_no ASC");
    $stmt->bind_param("i", $cam); $stmt->execute();
    $res_tests = $stmt->get_result();
    while($row = $res_tests->fetch_assoc()) { $tests[] = $row['test_no']; }
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

<style>
    /* ... 之前样式 ... */
    .grid-view { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 25px; max-width: 1200px; margin: -50px auto; padding: 0 20px; }
    .card { background: white; border-radius: 8px; padding: 40px 20px; text-align: center; cursor: pointer; border-top: 4px solid var(--oxford-gold); box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
    .card:hover { transform: translateY(-8px); border-top-color: var(--oxford-blue); }
    .btn-back { background: var(--oxford-blue); color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; font-size: 13px; }
</style>

<main class="main-content">
    <?php if ($cam === 0): ?>
        <div class="grid-view">
            <?php foreach ($books as $b): ?>
                <div class="card" onclick="location.href='?cam=<?= $b ?>'">CAMBRIDGE <?= $b ?></div>
            <?php endforeach; ?>
        </div>
    <?php elseif ($test === 0): ?>
        <div class="breadcrumb-nav"><a href="ielts.php" class="btn-back">← Back</a></div>
        <div class="grid-view">
            <?php foreach ($tests as $t): ?>
                <div class="card" onclick="location.href='?cam=<?= $cam ?>&test=<?= $t ?>'">TEST <?= $t ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>