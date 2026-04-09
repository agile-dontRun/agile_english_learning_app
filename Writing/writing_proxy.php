<?php
// writing_proxy.php
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$apiKey = ""; 
$apiUrl = "https://api.deepseek.com/chat/completions";

$action = $input['action'] ?? '';
$mode = $input['mode'] ?? 'ai'; // 新增：区分 ai 模式或 past 模式

// --- 新增逻辑：处理 Past Paper 模式 ---
if ($action === 'get_topic' && $mode === 'past') {
    $filePath = 'ielts_cambridge_19_t1.json'; // 引用你提到的文件名
    if (file_exists($filePath)) {
        $jsonContent = file_get_contents($filePath);
        $data = json_decode($jsonContent, true);
        // 为了适配前端对 'topic' 字段的接收，将 question 赋值给 topic
        echo json_encode(['topic' => $data['question']]);
        exit();
    } else {
        // 如果文件不存在，反馈错误
        echo json_encode(['topic' => "Error: Past paper file not found."]);
        exit();
    }
}

// --- 原有逻辑：处理 AI 模式和评估 ---
if ($action === 'get_topic') {
    $postData = [
        "model" => "deepseek-chat",
        "messages" => [
            ["role" => "system", "content" => "You are an IELTS writing examiner. Provide ONE academic writing task 2 prompt. Only return the topic text, no other chat."],
            ["role" => "user", "content" => "Give me a random academic topic about technology, education or environment."]
        ]
    ];
} elseif ($action === 'evaluate') {
    $topic = $input['topic'];
    $content = $input['content'];
    
    $prompt = "Topic: $topic\nStudent Essay: $content\n
    Please evaluate this essay. Return a JSON format with keys: 
    'score' (0-10), 'grammar' (feedback on grammar/vocab), 'logic' (feedback on structure), 'polished' (a high-quality rewritten version of the essay).";

    $postData = [
        "model" => "deepseek-chat",
        "messages" => [
            ["role" => "system", "content" => "You are a professional writing tutor Luna. Provide feedback in a supportive but strict academic tone. Output MUST be a valid JSON."],
            ["role" => "user", "content" => $prompt]
        ],
        "response_format" => ["type" => "json_object"]
    ];
}

// --- 底层请求逻辑（不做更改） ---
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
$response = curl_exec($ch);
$result = json_decode($response, true);
curl_close($ch);

$aiContent = $result['choices'][0]['message']['content'];

if ($action === 'get_topic') {
    echo json_encode(['topic' => $aiContent]);
} else {
    echo $aiContent;
}
