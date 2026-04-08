<?php
/**
 * Luna Writing Proxy - Academic Gateway v2.5
 * * This script acts as a secure middleware between the Spires Academy frontend 
 * and the DeepSeek Inference Engine. It handles both dynamic topic generation 
 * and multi-criteria academic evaluation.
 * * @package Luna_AI_Middleware
 * @version 2.5.0 (Dual-Mode Supported)
 */

header('Content-Type: application/json');

/* -------------------------------------------------------------------------
   1. REQUEST INTERCEPTION
   ------------------------------------------------------------------------- */
$input = json_decode(file_get_contents('php://input'), true);
$apiKey = "sk-435980991e53466691e0f61c01909fa1"; 
$apiUrl = "https://api.deepseek.com/chat/completions";

if (!$input) {
    echo json_encode(['error' => 'Empty request payload. Orchestrator terminated.']);
    exit;
}

$action = $input['action'] ?? '';

/* -------------------------------------------------------------------------
   2. OPERATIONAL ROUTING
   ------------------------------------------------------------------------- */
$postData = [];

if ($action === 'get_topic') {
    /**
     * TARGET: Dynamic Prompt Generation
     * Used when the student selects "AI Generation" mode.
     */
    $postData = [
        "model" => "deepseek-chat",
        "messages" => [
            [
                "role" => "system", 
                "content" => "You are an elite IELTS Writing Examiner. Provide ONE formal Academic Task 2 prompt. Output only the prompt text. No preamble."
            ],
            [
                "role" => "user", 
                "content" => "Generate a challenging academic prompt regarding social trends, technology, or global education."
            ]
        ],
        "temperature" => 0.8 // Slightly higher temperature for creative topic variety
    ];

} elseif ($action === 'evaluate') {
    /**
     * TARGET: Multi-Dimensional Evaluation
     * Works for both AI-generated topics and Past Paper JSON topics.
     */
    $topic   = $input['topic'] ?? 'Unknown Academic Subject';
    $content = $input['content'] ?? '';
    
    // Constructing a high-precision prompt for the scoring engine
    $evaluationSystemPrompt = "You are Professor Luna, a senior academic writing tutor from Oxford. "
                            . "Your tone is scholarly, encouraging, yet critically rigorous. "
                            . "You must output a valid JSON object.";

    $evaluationUserPrompt = "### TASK DESCRIPTION\nTopic: $topic\n\n"
                          . "### STUDENT MANUSCRIPT\n$content\n\n"
                          . "### INSTRUCTIONS\n"
                          . "Analyze the manuscript based on IELTS criteria. Return JSON with keys: "
                          . "'score' (0-10), 'grammar' (accuracy feedback), 'logic' (cohesion feedback), 'polished' (full scholarly rewrite).";

    $postData = [
        "model" => "deepseek-chat",
        "messages" => [
            ["role" => "system", "content" => $evaluationSystemPrompt],
            ["role" => "user", "content" => $evaluationUserPrompt]
        ],
        // Force the model to output a strictly formatted JSON object
        "response_format" => ["type" => "json_object"]
    ];
}

/* -------------------------------------------------------------------------
   3. REMOTE INFERENCE EXECUTION (CURL)
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

// Log network-level errors for infrastructure auditing
if (curl_errno($ch)) {
    echo json_encode(['error' => 'Inference Engine Connection Timeout: ' . curl_error($ch)]);
    curl_close($ch);
    exit;
}

$result = json_decode($response, true);
curl_close($ch);

/* -------------------------------------------------------------------------
   4. PAYLOAD DELIVERY
   ------------------------------------------------------------------------- */
if (!isset($result['choices'][0]['message']['content'])) {
    echo json_encode(['error' => 'Inference Engine returned an empty payload. Please verify API quotas.']);
    exit;
}

$aiContent = $result['choices'][0]['message']['content'];

if ($action === 'get_topic') {
    echo json_encode(['topic' => $aiContent]);
} else {
    // Forward the structured evaluation JSON back to the Spires Academy UI
    echo $aiContent;
}
