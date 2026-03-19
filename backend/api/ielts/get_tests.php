<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include 'db_connect.php'; 

// 接收前端传来的参数：?cambridge_no=20
$cambridge_no = isset($_GET['cambridge_no']) ? (int)$_GET['cambridge_no'] : 0;

if ($cambridge_no === 0) {
    die(json_encode(["code" => 400, "message" => "缺少 cambridge_no 参数"]));
}

try {
    // 安全地预处理 SQL 查询，防止 SQL 注入
    $stmt = $pdo->prepare('SELECT DISTINCT test_no FROM ielts_listening_parts WHERE cambridge_no = ? ORDER BY test_no ASC');
    $stmt->execute([$cambridge_no]);
    $tests = $stmt->fetchAll();

    // 兜底假数据
    if (empty($tests)) {
        $tests = [['test_no' => 1], ['test_no' => 2], ['test_no' => 3], ['test_no' => 4]];
    }

    echo json_encode([
        "code" => 200,
        "data" => $tests
    ]);
} catch (Exception $e) {
    echo json_encode(["code" => 500, "message" => $e->getMessage()]);
}
?>