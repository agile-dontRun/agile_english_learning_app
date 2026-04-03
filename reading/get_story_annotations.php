<?php
// get_story_annotations.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Please login first', 'annotations' => []]);
    exit;
}

include 'db_connect.php';

$user_id = intval($_SESSION['user_id']);
$story_id = isset($_GET['story_id']) ? intval($_GET['story_id']) : 0;

if (!$story_id) {
    echo json_encode(['annotations' => []]);
    exit;
}

$story_article_id = -$story_id;
$sql = "SELECT * FROM user_annotations WHERE user_id = $user_id AND article_id = $story_article_id ORDER BY created_at DESC";
$result = $conn->query($sql);

$annotations = [];
while ($row = $result->fetch_assoc()) {
    $annotations[] = $row;
}

echo json_encode(['annotations' => $annotations]);
?>