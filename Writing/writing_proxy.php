<?php
// writing_proxy.php
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$apiKey = "sk-435980991e53466691e0f61c01909fa1"; 
$apiUrl = "https://api.deepseek.com/chat/completions";

$action = $input['action'] ?? '';
$mode = $input['mode'] ?? 'ai'; // New: Distinguish between AI mode or Past Paper mode

// --- New logic: Handle Past Paper mode ---
if ($action === 'get_topic' && $mode === 'past') {
    $filePath = 'ielts_cambridge_19_t1.json'; // Reference the filename you mentioned
    if (file_exists($filePath)) {
        $jsonContent = file_get_contents($filePath);
        $data = json_decode($jsonContent, true);
        // To adapt to frontend's expectation for 'topic' field, assign question to topic
        echo json_encode(['topic' => $data['question']]);
        exit();
    } else {
        // If file doesn't exist, return error
        echo json_encode(['topic' => "Error: Past paper file not found."]);
        exit();
    }
}

// --- Original logic: Handle AI mode and evaluation ---
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

// --- Underlying request logic (no changes) ---
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
?>
