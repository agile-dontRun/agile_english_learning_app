<?php
// api_get_words.php
header('Content-Type: application/json; charset=utf-8');

// ==================== 1. 数据库配置 ====================
$host = '127.0.0.1';
$db   = '你的数据库名字';      // ⚠️ 修改这里
$user = '你的数据库用户名';    // ⚠️ 修改这里
$pass = '你的数据库密码';      // ⚠️ 修改这里
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    echo json_encode(['error' => '数据库连接失败']);
    exit;
}

/