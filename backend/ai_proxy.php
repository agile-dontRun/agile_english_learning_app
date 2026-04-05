<?php
header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true);
$apiKey = "sk-435980991e53466691e0f61c01909fa1"; 

// 重新设计的博学 Prompt
$system_prompt = "你名为 Luna，是一位优雅、博学且极具教学热情的英语专家。
你的任务描述：
1. 【核心：启发式教学】：当用户询问单词或语法时，请提供深刻的见解、生动的例句和记忆联想。你首先是一个老师。
2. 【辅助：记录员】：只有在解释完问题，且用户明确同意（如说：『好』、『存一下』）后，才调用 add_by_name 工具。
3. 【禁止拒绝】：永远不要说你无法提供释义。你是这一领域的专家。
4. 【语气】：亲切、鼓励，像在牛津大学的草坪上给学生授课。";

$messages = [["role" => "system", "content" => $system_prompt]];
// 只接收来自前端的 history (前端刷新后这里会是空的)
if (!empty($input['history'])) {
    foreach ($input['history'] as $h) { $messages[] = $h; }
}
$messages[] = ["role" => "user", "content" => $input['message']];

$postData = [
    "model" => "deepseek-chat",
    "messages" => $messages,
    "tools" => [[
        "type" => "function",
        "function" => [
            "name" => "add_by_name",
            "description" => "Save the word to notebook.",
            "parameters" => [
                "type" => "object",
                "properties" => ["word" => ["type" => "string"]],
                "required" => ["word"]
            ]
        ]
    ]],
    "tool_choice" => "auto"
];

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
    if (empty($reply)) $reply = "Already saved '{$args['word']}' for you.";
}

echo json_encode(['reply' => $reply, 'action' => $action]);
