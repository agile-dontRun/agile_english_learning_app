<?php
// api_get_words.php
header('Content-Type: application/json; charset=utf-8');

// ==================== 1. 数据库配置 ====================
$host = '127.0.0.1';
$db   = '你的数据库名字';      // ⚠️ 修改这里
$user = '你的数据库用户名';    // ⚠️ 修改这里
$pass = '你的数据库密码';      // ⚠️ 修改这里
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    echo json_encode(['error' => '数据库连接失败']);
    exit;
}

// ==================== 2. 获取单词逻辑 ====================
// 从 words 表中随机抽取 50 个单词（你可以根据需要修改表名和数量）
$stmt = $pdo->query("SELECT en, cn FROM words ORDER BY RAND() LIMIT 50");
$wordsData = $stmt->fetchAll();

if (count($wordsData) < 4) {
    echo json_encode(['error' => '词库单词数量太少，至少需要4个单词']);
    exit;
}

$wordBank = [];

// 将数据库的数据格式化为前端需要的格式
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
