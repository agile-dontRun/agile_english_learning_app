<?php
// ai_proxy.php
header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true);
$apiKey = "sk-435980991e53466691e0f61c01909fa1"; 

// 1. 获取历史记录
$history = $input['history'] ?? [];
$isFirstTurn = empty($history); // 判断是否是刷新后的第一次开口

// 2. 动态 System Prompt
$system_content = "你名为 Luna，是一位博学的牛津英语导师。";

if ($isFirstTurn) {
    // 第一轮：可以有简短、优雅的开场白
    $system_content .= "当前是新会话的开始。请在回答用户第一个问题时，表现得热情且专业，可以简短提及你的牛津背景。";
} else {
    // 后续轮次：严禁自我介绍，直接进入教学模式
    $system_content .= "当前对话正在进行中。请严禁再次进行自我介绍或礼貌性寒暄。请直接针对用户的问题给出深度解析，并保持上下文连贯。";
}

$system_content .= "\n技能指令：
- 讲解词义时，必须包含词源、1个地道例句和记忆点。
- 只有在用户明确表示『好』、『存一下』或同意你的建议时，才调用 add_by_name 工具。";

$messages = [["role" => "system", "content" => $system_content]];

// 将历史记录压入消息流
foreach ($history as $msg) {
    $messages[] = $msg;
}
$messages[] = ["role" => "user", "content" => $input['message']];

// 3. 发送请求
$postData = [
    "model" => "deepseek-chat",
    "messages" => $messages,
    "tools" => [[
        "type" => "function",
        "function" => [
            "name" => "add_by_name",
            "description" => "Save word to notebook.",
            "parameters" => [
                "type" => "object",
                "properties" => ["word" => ["type" => "string"]],
                "required" => ["word"]
            ]
        ]
    ]],
    "tool_choice" => "auto"
];

// ... (CURL 请求逻辑保持不变) ...
$ch = curl_init("https://api.deepseek.com/chat/completions");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . $apiKey]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
$response = curl_exec($ch);
$result = json_decode($response, true);
curl_close($ch);

$aiMsg = $result['choices'][0]['message'] ?? [];
$reply = $aiMsg['content'] ?? "";
$action = null;

if (!empty($aiMsg['tool_calls'])) {
    $args = json_decode($aiMsg['tool_calls'][0]['function']['arguments'], true);
    $action = ['function_name' => 'add_by_name', 'word' => $args['word']];
}

echo json_encode(['reply' => $reply, 'action' => $action]);
