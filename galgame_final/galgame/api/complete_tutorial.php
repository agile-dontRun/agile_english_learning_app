<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// 检查是否登录
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Not logged in
'
    ]);
    exit();
}

// 引入根目录下的数据库连接文件
require_once __DIR__ . '/../../db_connect.php';

$user_id = $_SESSION['user_id'];

// 更新教程完成状态
$stmt = $conn->prepare("UPDATE users SET is_tutorial_completed = 1 WHERE user_id = ?");
if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'SQL prepare failed'
    ]);
    exit();
}

$stmt->bind_param("i", $user_id);
$success = $stmt->execute();
$stmt->close();

echo json_encode([
    'success' => $success
]);