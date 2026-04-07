<?php
// writing_proxy.php
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$apiKey = ""; 
$apiUrl = "https://api.deepseek.com/chat/completions";

$action = $input['action'] ?? '';

if ($action === 'get_topic') {
    $postData = [
        "model" => "deepseek-chat",
        "messages" => [
            ["role" => "system", "content" => "You are an IELTS examiner. Give one random Academic Task 2 question. Plain text only."],
            ["role" => "user", "content" => "Give me a new topic."]
        ]
    ];
} elseif ($action === 'evaluate') {
    $topic = $input['topic'];
    $content = $input['content'];
    
    $prompt = "Topic: $topic\nStudent Essay: $content\nEvaluate in JSON: {score, grammar, logic, polished}";

    $postData = [
        "model" => "deepseek-chat",
        "messages" => [
            ["role" => "system", "content" => "You are Luna, an Oxford tutor. Provide feedback in JSON format."],
            ["role" => "user", "content" => $prompt]
        ],
        "response_format" => ["type" => "json_object"]
    ];
}

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . $apiKey]);
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
