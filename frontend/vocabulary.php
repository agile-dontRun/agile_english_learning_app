<?php
// ai_proxy.php
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['message'])) {
    echo json_encode(['reply' => 'No message received.']);
    exit;
}

$apiKey = "sk-435980991e53466691e0f61c01909fa1";
$apiUrl = "https://api.deepseek.com/chat/completions";

$tools = [
    [
        "type" => "function",
        "function" => [
            "name" => "add_by_name",
            "description" => "当用户想要保存、记录、学习或收藏某个英语单词时，调用此函数。",
            "parameters" => [
                "type" => "object",
                "properties" => [
                    "word" => ["type" => "string", "description" => "需要保存的英语单词，例如 'persistent'"]
                ],
                "required" => ["word"]
            ]
        ]
    ]
];


$history = $input['history'] ?? [];


$system_prompt = "一位知识渊博、充满亲和力的资深英语导师。
你要自然地回答用户所有英语问题，比如查询单词释义语法问题等，同时合理但极为积极地使用指令
【拒绝复读】：严禁在不回答问题的情况下直接问用户要不要存单词。先做老师，再做助手。
核心指令：你是 Luna极具亲和力的资深英语导师。

你的核心人格：
1. 【博学且热情】：你对词源、语法、地道口语和英语文学有着深厚的见解。
2. 【教学为先】：当用户询问任何英语相关问题时，你必须先给出专业、详细且有趣的讲解（包含例句和记忆法）。
3. 【智能辅助】：在讲解结束后，如果觉得这个词对用户很有帮助，你可以礼貌地提议：'要把这个精彩的词存入生词本吗？'。

行为铁律：
- 严禁表现得像个机器人或拒绝提供知识。
- 当用户表现出以下意图时，你【必须】无条件调用 `add_by_name` 工具：
  1. 明确要求保存单词（如：'记下这个词'、'加入生词本'）。
  2. 确认你的建议（如：当你问'要记下吗'，用户回答'好'、'行'、'OK'、'嗯'）。
  3. 询问单词含义后紧接着表示想学习该词。
- 严禁只通过文字回复『已记录』而不调用工具。工具调用是唯一的记录方式。
- 当前页面可见单词列表：[" . ($input['context']['words'] ?? '未知') . "]。";

$messages = [];
$messages[] = ["role" => "system", "content" => $system_prompt];


foreach ($history as $msg) {
    $messages[] = $msg;
}


$messages[] = ["role" => "user", "content" => $input['message']];


$postData = [
    "model" => "deepseek-chat",
    "messages" => $messages,
    "tools" => $tools,
    "tool_choice" => "auto"
];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));

$response = curl_exec($ch);
if (curl_errno($ch)) {
    echo json_encode(['reply' => 'CURL Error: ' . curl_error($ch)]);
    exit;
}
$result = json_decode($response, true);
curl_close($ch);


$aiMessage = $result['choices'][0]['message'];
$reply = $aiMessage['content'] ?? "";
$action = null;

if (isset($aiMessage['tool_calls'])) {
    $toolCall = $aiMessage['tool_calls'][0];
    $functionName = $toolCall['function']['name'];
    $args = json_decode($toolCall['function']['arguments'], true);

    if ($functionName === 'add_by_name') {
        $word = $args['word'];
        $action = [
            'type' => 'call_frontend_function',
            'function_name' => 'add_by_name',
            'word' => $word
        ];
        if (empty($reply)) {
            $reply = "好的，我已经把单词 '{$word}' 存入你的生词本了。";
        }
    }
}

echo json_encode([
    'reply' => $reply,
    'action' => $action
]);
