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
$selected_text = isset($_POST['selected_text']) ? trim($_POST['selected_text']) : '';
$note = isset($_POST['note']) ? trim($_POST['note']) : '';

if (!$article_id || empty($selected_text)) {
    echo json_encode(['error' => '参数不完整']);
    exit;
}

$selected_text = $conn->real_escape_string($selected_text);
$note = $conn->real_escape_string($note);

$conn->query("INSERT INTO user_annotations (user_id, article_id, selected_text, note) 
              VALUES ($user_id, $article_id, '$selected_text', '$note')");

echo json_encode(['success' => true]);
?>