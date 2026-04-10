<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include 'db_connect.php'; 

$cambridge_no = isset($_GET['cambridge_no']) ? (int)$_GET['cambridge_no'] : 0;
$test_no = isset($_GET['test_no']) ? (int)$_GET['test_no'] : 0;

if ($cambridge_no === 0 || $test_no === 0) {
    die(json_encode(["code" => 400, "message" => "参数不全"]));
}

try {
    // 查出该 Test 下的所有 Part (包括音频链接和题目文本)
    $stmt = $pdo->prepare('
        SELECT part_id, part_no, title, audio_url, transcript_text 
        FROM ielts_listening_parts 
        WHERE cambridge_no = ? AND test_no = ? 
        ORDER BY part_no ASC
    ');
    $stmt->execute([$cambridge_no, $test_no]);
    $parts = $stmt->fetchAll();

    // 兜底假数据
    if (empty($parts)) {
        for ($i=1; $i<=4; $i++) {
            $parts[] = [
                'part_no' => $i, 
                'title' => 'Part ' . $i, 
                'audio_url' => 'dummy_audio.mp3'
            ];
        }
    }

    echo json_encode([
        "code" => 200,
        "data" => $parts
    ]);
} catch (Exception $e) {
    echo json_encode(["code" => 500, "message" => $e->getMessage()]);
}
?>