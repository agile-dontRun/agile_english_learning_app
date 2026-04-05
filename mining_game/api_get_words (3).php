<?php
// api_get_words.php
header('Content-Type: application/json; charset=utf-8');

// ==================== 1. 引入数据库连接 ====================
// 引入你组员写好的连接文件 (请确保文件名就是 dbconnect.php)
require_once 'db_connect.php'; 

// ==================== 2. 获取单词数据 ====================
// ⚠️ 唯一需要你确认的地方：请检查你们数据库里的“单词表”叫什么名字？
// 下面的 SQL 语句假设表名叫 words，英文字段叫 en，中文字段叫 cn。
// 如果名字不一样（比如叫 word_list，字段叫 word_en, word_cn），请修改下面这行双引号里的名字！
$sql = "SELECT english_word, chinese_meaning FROM words ORDER BY RAND() LIMIT 50";
$result = $conn->query($sql);

// 检查查询是否出错
if (!$result) {
    echo json_encode(['error' => '数据库查询失败: ' . $conn->error]);
    exit;
}

// 将查询结果存入数组
$wordsData = [];
while ($row = $result->fetch_assoc()) {
    $wordsData[] = $row;
}

// 检查单词数量是否够生成4个选项
if (count($wordsData) < 4) {
    echo json_encode(['error' => '词库单词数量太少，至少需要4个不同的单词来生成选项']);
    exit;
}

$wordBank = [];

// ==================== 3. 组装成游戏需要的格式 ====================
foreach ($wordsData as $word) {
    $options = [$word['chinese_meaning']]; // 先把正确的中文放进数组
    
    // 随机再找 3 个不重复的错误中文意思
    while(count($options) < 4) {
        $randomWrongCn = $wordsData[array_rand($wordsData)]['chinese_meaning'];
        if (!in_array($randomWrongCn, $options)) {
            $options[] = $randomWrongCn;
        }
    }
    
    // 将这 4 个选项打乱顺序，防止正确答案总是在第一个 (A选项)
    shuffle($options);
    
    // 找到正确中文被打乱后所在的索引位置 (0, 1, 2, 或 3)
    $correctIndex = array_search($word['chinese_meaning'], $options);
    
    // 把数据库真实的字段 $word['english_word'] 拿出来，贴上 'en' 的标签发给前端
    $wordBank[] = [
        'en' => $word['english_word'], 
        'cn' => $options,               
        'a'  => $correctIndex
    ];
}

// ==================== 4. 输出并关闭连接 ====================
echo json_encode($wordBank);
$conn->close();
?>