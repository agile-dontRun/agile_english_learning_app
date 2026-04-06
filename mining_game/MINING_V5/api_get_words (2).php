<?php
// api_get_words.php
header('Content-Type: application/json; charset=utf-8');

// ==================== 1. 引入数据库连接 ====================
// 引入你组员写好的文件 (请确保路径正确，如果和当前文件同级直接这样写)
require_once 'dbconnect.php'; 

// ==================== 2. 获取单词逻辑 ====================
// ⚠️ 注意：这里假设你们在 dbconnect.php 里的连接变量叫做 $conn
// 并且假设你们的单词表叫做 words，字段是 en 和 cn
$sql = "SELECT en, cn FROM words ORDER BY RAND() LIMIT 50";
$result = $conn->query($sql);

if (!$result) {
    echo json_encode(['error' => '数据库查询失败: ' . $conn->error]);
    exit;
}

$wordsData = [];
while ($row = $result->fetch_assoc()) {
    $wordsData[] = $row;
}

if (count($wordsData) < 4) {
    echo json_encode(['error' => '词库单词数量太少，至少需要4个单词']);
    exit;
}

$wordBank = [];

// ==================== 3. 组装前端需要的格式 ====================
foreach ($wordsData as $word) {
    $options = [$word['cn']]; // 先把正确的中文放进数组
    
    // 随机再找 3 个不重复的错误中文意思
    while(count($options) < 4) {
        $randomWrongCn = $wordsData[array_rand($wordsData)]['cn'];
        if (!in_array($randomWrongCn, $options)) {
            $options[] = $randomWrongCn;
        }
    }
    
    // 将这 4 个选项打乱顺序
    shuffle($options);
    
    // 找到正确中文被打乱后所在的索引位置 (0, 1, 2, 或 3)
    $correctIndex = array_search($word['cn'], $options);
    
    // 压入最终数组
    $wordBank[] = [
        'en' => $word['en'],
        'cn' => $options,
        'a'  => $correctIndex
    ];
}

// 返回给 JS 的 JSON 数据
echo json_encode($wordBank);
?>
