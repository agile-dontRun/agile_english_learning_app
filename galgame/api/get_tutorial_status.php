<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => '未登录'
    ]);
    exit;
}
$userId = $_SESSION['user_id'];
require_once __DIR__ . '/../config/db.php';
try {
    // 6.1 预处理SQL语句（防SQL注入，安全）
    $stmt = $pdo->prepare("SELECT is_tutorial_completed FROM user WHERE id = ?");
    
    // 6.2 执行SQL查询，代入用户ID
    $stmt->execute([$userId]);
    
    // 6.3 获取查询结果（关联数组格式）
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // 6.4 判断用户是否存在
    if (!$user) {
        echo json_encode([
            'success' => false,
            'message' => '用户不存在'
        ]);
        exit;
    }

    // 6.5 返回成功结果：新手引导完成状态
    echo json_encode([
        'success' => true,
        'is_tutorial_completed' => (bool)$user['is_tutorial_completed']
    ]);

    } catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '服务器错误：' . $e->getMessage()
    ]);
}