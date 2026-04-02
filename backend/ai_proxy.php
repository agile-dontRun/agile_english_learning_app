<?php
// ai_proxy.php
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['message']))
    exit;

$apiKey = "sk-435980991e53466691e0f61c01909fa1";
$apiUrl = "https://api.deepseek.com/chat/completions";

// ---tool calling ---
$tools = [
    [
        "type" => "function",
        "function" => [
            "name" => "add_to_vocabulary",
            "description" => "当用户明确表示想把某个单词存入生词本，或者回答好、是的、记录下来时调用此工具。",
            "parameters" => [
                "type" => "object",
                "properties" => [
                    "word" => ["type" => "string", "description" => "要保存的英语单词"],
                    "word_id" => ["type" => "integer", "description" => "单词的ID（如果已知）"]
                ],
                "required" => ["word"]
            ]
        ]
    ]
];

// 构建消息，包含历史记录
$messages = $input['history'] ?? [];
$messages[] = ["role" => "user", "content" => $input['message']];

// 注入 System Prompt
array_unshift($messages, [
    "role" => "system",
    "content" => "你是一个亲切的英语助教。当用户询问单词意思后，你可以主动问他：'需要我帮你把这个词加入生词本吗？'。如果用户同意，请调用 add_to_vocabulary 工具。当前页面上的词汇 ID 信息: " . ($input['context']['words_info'] ?? '未知')
]);

$postData = [
    "model" => "deepseek-chat", // V3 模型对工具调用支持更稳
    "messages" => $messages,
    "tools" => $tools,
    "tool_choice" => "auto"
];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . $apiKey]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
$response = curl_exec($ch);
$result = json_decode($response, true);
curl_close($ch);

$message = $result['choices'][0]['message'];
$action = null;

// --- 逻辑判断：AI 是否决定调用工具？ ---
if (isset($message['tool_calls'])) {
    $toolCall = $message['tool_calls'][0];
    $args = json_decode($toolCall['function']['arguments'], true);

    // 我们不需要在后端真的去操作数据库，我们把指令发给前端去执行
    // 这样前端界面会有对应的“已添加”反馈
    $action = [
        'type' => 'call_frontend_function',
        'function_name' => 'add_to_notebook',
        'word' => $args['word'],
        'word_id' => $args['word_id'] ?? null
    ];
    $reply = "没问题，已经帮你把 '" . $args['word'] . "' 记在生词本里啦！";
} else {
    $reply = $message['content'];
}

echo json_encode([
    'reply' => $reply,
    'action' => $action
]);
