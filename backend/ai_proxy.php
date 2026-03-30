<?php
// ai_proxy.php
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['message'])) {
    echo json_encode(['reply' => 'No input provided.']);
    exit;
}

// --- DeepSeek 配置区 ---
// 1. 去 https://platform.deepseek.com/ 创建 API Key
$apiKey = "sk-45f885002b4141dfa9ca6754ddb19900";
// 2. 模型直接写名字就行：deepseek-chat (V3版本) 或 deepseek-reasoner (R1深度思考版本)
$modelName = "deepseek-chat";
// ----------------------

$userMessage = $input['message'];
$contextWords = $input['context']['words'] ?? 'None';

$systemPrompt = "你是一个亲切的英语学习助教。
当前页面单词: " . $contextWords . "。
请根据这些单词或用户的疑问提供帮助。回答要幽默、专业且简短。";

$postData = [
    "model" => $modelName,
    "messages" => [
        ["role" => "system", "content" => $systemPrompt],
        ["role" => "user", "content" => $userMessage]
    ],
    "stream" => false
];

$ch = curl_init("https://api.deepseek.com/chat/completions");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo json_encode(['reply' => '连接 DeepSeek 失败: ' . curl_error($ch)]);
} else {
    $result = json_decode($response, true);
    // DeepSeek 的返回结构和 OpenAI 完全一致
    $reply = $result['choices'][0]['message']['content'] ?? "DeepSeek 暂时没能给出回复，请检查 Key 或余额。";
    echo json_encode(['reply' => $reply]);
}
curl_close($ch);