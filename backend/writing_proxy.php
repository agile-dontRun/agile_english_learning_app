<?php
// writing_proxy.php
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$apiKey = ""; 
$apiUrl = "https://api.deepseek.com/chat/completions";

$action = $input['action'] ?? '';

if ($action === 'get_topic') {
    // Logic: ask AI to generate an academic writing topic
    $postData = [
        "model" => "deepseek-chat",
        "messages" => [
            ["role" => "system", "content" => "You are an IELTS writing examiner. Provide ONE academic writing task 2 prompt. Only return the topic text, no other chat."],
            ["role" => "user", "content" => "Give me a random academic topic about technology, education or environment."]
        ]
    ];
} elseif ($action === 'evaluate') {
    // Logic: evaluate the student's essay
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
    // Pass the AI's JSON string directly to the frontend
    echo $aiContent;
}
