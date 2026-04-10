<?php
// writing_proxy.php

// 1. Disable native error output to prevent PHP warnings from breaking the JSON format
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['error' => 'Invalid request data.']);
    exit();
}

$apiKey = ""; 
$apiUrl = "https://api.deepseek.com/chat/completions";

$action = $input['action'] ?? '';
$mode = $input['mode'] ?? 'ai';

// --- Handle fetching writing history ---
if ($action === 'get_history') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'User not logged in']);
        exit();
    }
    
    $user_id = $_SESSION['user_id'];
    $history = [];
    
    // Fetch all writing records for the current user, ordered by newest first
    $stmt = $conn->prepare("SELECT record_id, topic, content, score, grammar_feedback, logic_feedback, polished_content, created_at FROM writing_records WHERE user_id = ? ORDER BY created_at DESC");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
        $stmt->close();
    }
    
    echo json_encode(['history' => $history]);
    exit();
}

// --- Handle Past Paper mode ---
if ($action === 'get_topic' && $mode === 'past') {
    $fileId = $input['file_id'] ?? 'ielts_cambridge_19_t1';
    $filePath = $fileId . '.json'; 
    
    if (file_exists($filePath)) {
        $jsonContent = file_get_contents($filePath);
        $data = json_decode($jsonContent, true);
        echo json_encode(['topic' => $data['question']]);
        exit();
    } else {
        echo json_encode(['error' => "Past paper file '$filePath' not found."]);
        exit();
    }
}

// --- Handle AI topic generation and evaluation ---
if ($action === 'get_topic') {
    
    // Create an array of categories and pick one randomly to ensure topic diversity
    $categories = ['technology', 'education', 'environment', 'public health', 'globalization', 'arts and culture', 'crime and punishment', 'the economy'];
    $randomCategory = $categories[array_rand($categories)];
    
    // Add a random seed to ensure the prompt is completely unique even if the category repeats
    $randomSeed = rand(1000, 9999);

    $postData = [
        "model" => "deepseek-chat",
        "messages" => [
            ["role" => "system", "content" => "You are an IELTS writing examiner. Provide ONE academic writing task 2 prompt. Only return the topic text, no other chat."],
            ["role" => "user", "content" => "Give me a completely new and random academic IELTS Task 2 topic about $randomCategory. Make it highly unique. (Random Seed: $randomSeed)"]
        ],
        "temperature" => 1.2 // Increase temperature for more creative and random outputs
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
} else {
    echo json_encode(['error' => 'Unknown action.']);
    exit();
}

// --- Request DeepSeek API ---
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));

// Disable local SSL verification to prevent cURL error 60 (SSL certificate problem)
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Set 30 seconds timeout

$response = curl_exec($ch);
$curlError = curl_error($ch);
curl_close($ch);

// If cURL fails (e.g., network error, timeout)
if ($curlError) {
    echo json_encode(['error' => 'Network error: ' . $curlError]);
    exit();
}

$result = json_decode($response, true);

// If DeepSeek API returns an error (e.g., wrong API Key, insufficient balance)
if (isset($result['error'])) {
    echo json_encode(['error' => 'DeepSeek API Error: ' . ($result['error']['message'] ?? 'Unknown error')]);
    exit();
}

$aiContent = $result['choices'][0]['message']['content'] ?? null;

// If no valid response is received
if (!$aiContent) {
    echo json_encode(['error' => 'Failed to parse AI response.', 'raw' => $response]);
    exit();
}

// --- Return response and handle database storage ---
if ($action === 'get_topic') {
    echo json_encode(['topic' => $aiContent]);
} elseif ($action === 'evaluate') {
    
    // Attempt to store the evaluation in the database
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $aiData = json_decode($aiContent, true);
        
        if ($aiData) {
            $eval_topic = $input['topic'] ?? 'Unknown Topic';
            $eval_content = $input['content'] ?? '';
            $score = isset($aiData['score']) ? (float)$aiData['score'] : 0.0;
            $grammar = $aiData['grammar'] ?? '';
            $logic = $aiData['logic'] ?? '';
            $polished = $aiData['polished'] ?? '';
            
            $stmt = $conn->prepare("INSERT INTO writing_records (user_id, topic, content, score, grammar_feedback, logic_feedback, polished_content) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("isssdss", $user_id, $eval_topic, $eval_content, $score, $grammar, $logic, $polished);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
    
    // Return the original JSON response from AI to the frontend
    echo $aiContent;
}
?>
