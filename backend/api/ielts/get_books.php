<?php
// 允许跨域请求 (因为你的前端和后端分开了，浏览器会拦截，必须加这两行)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// 1. 引入你同学写的数据库连接文件
// 注意路径：如果 get_books.php 和 db_connect.php 在同一个文件夹，直接这样写：
include 'db_connect.php'; 

// 如果连不上，说明这里报错了退出
if ($conn->connect_error) {
    die(json_encode(["code" => 500, "message" => "数据库连接失败"]));
}

$books = [];

try {
    // 查询数据库中存在哪些剑桥雅思书本编号 (去重)，并倒序排列 (20, 19, 18...)
    $stmt = $pdo->query('SELECT DISTINCT cambridge_no FROM ielts_listening_parts ORDER BY cambridge_no DESC');
    $books = $stmt->fetchAll();

    // 如果数据库是空的，为了不让前端白屏，我们给点假数据兜底
    if (empty($books)) {
        for ($i = 20; $i >= 11; $i--) {
            $books[] = ['cambridge_no' => $i];
        }
    }

    echo json_encode([
        "code" => 200,
        "message" => "success",
        "data" => $books
    ]);

} catch (Exception $e) {
    echo json_encode(["code" => 500, "message" => "查询失败: " . $e->getMessage()]);
}
?>