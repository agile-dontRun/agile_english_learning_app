<?php
// backend/config/db.php

// $host = '127.0.0.1';       // 数据库IP地址
// $db   = 'english_learning_app'; // 你同事建的数据库名
// $user = 'root';            // 数据库用户名 (根据实际情况修改)
// $pass = '123456';          // 数据库密码 (根据实际情况修改)
// $charset = 'utf8mb4';
include 'db_connect.php';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // 实际上线时不要直接输出错误，这里为了本地调试方便
    die(json_encode([
        "code" => 500,
        "message" => "数据库连接失败: " . $e->getMessage()
    ]));
}
?>