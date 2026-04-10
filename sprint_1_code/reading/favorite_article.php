<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => '请先登录']);
    exit;
}
include 'db_connect.php';

$user_id = intval($_SESSION['user_id']);
$article_id = isset($_POST['article_id']) ? intval($_POST['article_id']) : 0;

if (!$article_id) {
    echo json_encode(['error' => '无效的文章ID']);
    exit;
}


$check = $conn->query("SELECT * FROM user_favorites WHERE user_id = $user_id AND article_id = $article_id");

if ($check->num_rows == 0) {
    
    $conn->query("INSERT INTO user_favorites (user_id, article_id) VALUES ($user_id, $article_id)");
    echo json_encode(['success' => true, 'action' => 'added']);
} else {
    
    $conn->query("DELETE FROM user_favorites WHERE user_id = $user_id AND article_id = $article_id");
    echo json_encode(['success' => true, 'action' => 'removed']);
}
?>