<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// 检查是否登录
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => '未登录'
    ]);
    exit();
}

// 引入根目录下的数据库连接文件
require_once __DIR__ . '/../../db_connect.php';

$user_id = $_SESSION['user_id'];

// 查询当前用户是否完成教程
$stmt = $conn->prepare("SELECT is_tutorial_completed FROM users WHERE user_id = ?");
if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'SQL prepare 失败'
    ]);
    exit();
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    echo json_encode([
        'success' => false,
        'message' => 'User does not exit.'
    ]);
    exit();
}

echo json_encode([
    'success' => true,
    'is_tutorial_completed' => (bool)$user['is_tutorial_completed']
]);