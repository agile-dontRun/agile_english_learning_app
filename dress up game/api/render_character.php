<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

session_start();
$outfit = $_SESSION['current_outfit'] ?? [];

if (isset($outfit['body'])) {
    $stmt = $pdo->prepare("SELECT file_path FROM images WHERE id = ?");
    $stmt->execute([$outfit['body']]);
    $imgData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($imgData && !empty($imgData['file_path'])) {
        $filePath = __DIR__ . '/..' . $imgData['file_path'];
        if (file_exists($filePath)) {
            $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            if ($ext === 'png') header('Content-Type: image/png');
            elseif ($ext === 'jpg' || $ext === 'jpeg') header('Content-Type: image/jpeg');
            readfile($filePath);
            exit;
        }
    }
}

header('Content-Type: image/png');
readfile(__DIR__ . '/../people.png');
exit;
?>