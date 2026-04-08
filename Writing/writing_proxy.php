<?php
/**
 * Luna Writing AI Proxy - Academic Evaluation Middleware
 * * This script serves as the bridge between the frontend writing interface 
 * and the DeepSeek AI API. It handles task generation and essay grading.
 *
 * @package Luna_Writing_Module
 * @version 1.2.0
 */

// Set response header to JSON for frontend AJAX compatibility
header('Content-Type: application/json');

/* -------------------------------------------------------------------------
   1. INPUT PARSING & INITIALIZATION
   ------------------------------------------------------------------------- */
// Retrieve raw POST data and decode the JSON payload
$input = json_decode(file_get_contents('php://input'), true);

// Basic validation: ensure an action is specified
if (!$input || !isset($input['action'])) {
    echo json_encode(['error' => 'Invalid request: No action specified.']);
    exit;
}

// API Credentials and Endpoint
$apiKey = "sk-435980991e53466691e0f61c01909fa1"; 
$apiUrl = "https://api.deepseek.com/chat/completions";
$action = $input['action'];

/* -------------------------------------------------------------------------
   2. ACTION ROUTING & PROMPT CONSTRUCTION
   ------------------------------------------------------------------------- */
$postData = [];

if ($action === 'get_topic') {
    /**
     * TARGET: Generate a high-quality IELTS Writing Task 2 prompt.
     * Persona: Professional IELTS Examiner.
     */
    $postData = [
        "model" => "deepseek-chat",
        "messages" => [
            [
                "role" => "system", 
                "content" => "You are an IELTS writing examiner. Provide ONE academic writing task 2 prompt. Output only the topic text itself."
            ],
            [
                "role" => "user", 
                "content" => "Generate a random academic topic focused on technology, education, or the environment."
            ]
        ]
    ];
} elseif ($action === 'evaluate') {
    /**
     * TARGET: Evaluate a student's essay based on standardized criteria.
     * Persona: Luna, an encouraging yet rigorous Oxford tutor.
     */
    $topic   = $input['topic'] ?? 'General Academic Topic';
    $content = $input['content'] ?? '';
    
    // Constructing the evaluation prompt with explicit JSON formatting requirements
    $evaluationPrompt = "Writing Task Topic: $topic\nStudent Essay Content: $content\n\n"
                      . "Please evaluate this essay. You must return a valid JSON object with the following keys: "
                      . "'score' (a number 0-10), 'grammar' (analysis of accuracy/vocab), "
                      . "'logic' (analysis of structure/cohesion), 'polished' (your professional rewrite).";

    $postData = [
        "model" => "deepseek-chat",
        "messages" => [
            [
                "role" => "system", 
                "content" => "You are Luna, a professional writing tutor. Maintain a supportive but strictly academic tone. Output MUST be valid JSON."
            ],
            ["role" => "user", "content" => $evaluationPrompt]
        ],
        // Ensuring the AI returns a JSON structure for reliable frontend parsing
        "response_format" => ["type" => "json_object"]
    ];
}

/* -------------------------------------------------------------------------
   3. REMOTE API EXECUTION (CURL)
   ------------------------------------------------------------------------- */
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));

$response = curl_exec($ch);

// Error Handling for network/CURL failures
if (curl_errno($ch)) {
    echo json_encode(['error' => 'API Connection Failure: ' . curl_error($ch)]);
    curl_close($ch);
    exit;
}

$result = json_decode($response, true);
curl_close($ch);

/* -------------------------------------------------------------------------
   4. RESPONSE DELIVERY
   ------------------------------------------------------------------------- */
// Extract the generated content from the AI response object
$aiContent = $result['choices'][0]['message']['content'] ?? null;

if (!$aiContent) {
    echo json_encode(['error' => 'AI failed to generate a response.']);
    exit;
}

if ($action === 'get_topic') {
    // Wrap the plain-text topic into a JSON object for the frontend
    echo json_encode(['topic' => $aiContent]);
} else {
    // Pass-through the AI-generated JSON evaluation directly
    echo $aiContent;
}
