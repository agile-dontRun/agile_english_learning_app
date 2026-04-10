<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => '请先登录']);
    exit;
}
include 'db_connect.php';

$user_id = intval($_SESSION['user_id']);
$annotation_id = isset($_POST['annotation_id']) ? intval($_POST['annotation_id']) : 0;

if (!$annotation_id) {
    echo json_encode(['error' => '无效的批注ID']);
    exit;
}

// 确保只能删除自己的批注
$conn->query("DELETE FROM user_annotations WHERE annotation_id = $annotation_id AND user_id = $user_id");

echo json_encode(['success' => true]);
?>