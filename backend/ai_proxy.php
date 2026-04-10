<?php
// ai_proxy.php
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['message'])) {
    echo json_encode(['reply' => 'No message received.']);
    exit;
}

$apiKey = "";
$apiUrl = "https://api.deepseek.com/chat/completions";

// --- 1. Define Tools ---
$tools = [
    [
        "type" => "function",
        "function" => [
            "name" => "add_by_name",
            "description" => "Call this when the user wants to save, record, or favorite an English vocabulary word.",
            "parameters" => [
                "type" => "object",
                "properties" => [
                    "word" => ["type" => "string", "description" => "The English word to save"]
                ],
                "required" => ["word"]
            ]
        ]
    ],
    [
        "type" => "function",
        "function" => [
            "name" => "navigate_to_page",
            "description" => "Call this to redirect the user to a specific learning page based on their intent.",
            "parameters" => [
                "type" => "object",
                "properties" => [
                    "page_type" => [
                        "type" => "string",
                        "enum" => ["fun_learning", "writing_practice"],
                        "description" => "Target page: fun_learning (games/stories) or writing_practice (essays/writing)"
                    ]
                ],
                "required" => ["page_type"]
            ]
        ]
    ],
    // NEW TOOL: Analyze mistakes / summarize current work
    [
        "type" => "function",
        "function" => [
            "name" => "analyze_mistakes",
            "description" => "Call this when the user asks to summarize their current questions, evaluate their ongoing task, or asks 'where did I make mistakes?'.",
            "parameters" => [
                "type" => "object",
                "properties" => [], // No specific parameters needed, relies on system context
            ]
        ]
    ]
];

// --- Extract dynamic context from the frontend ---
$context_words = $input['context']['words'] ?? 'Unknown';
// NEW: Extract page content or quiz data sent by the frontend
$page_content = $input['context']['page_content'] ?? 'No specific page text provided.';
$quiz_data = $input['context']['quiz_data'] ?? 'No current quiz or mistake data provided.';

// --- 2. Define Luna's Persona and Constraints ---
$system_prompt = "You are Luna, a senior tutor at Spires Academy.
[Persona]: Elegant, knowledgeable, and concise.
[Word Count Limit]: Your reply MUST be around 50-80 words. STRICTLY DO NOT exceed 100 words.

[Tool Calling Logic]:
1. If the user wants fun/game/story learning, call `Maps_to_page(page_type='fun_learning')`.
2. If the user expresses a desire to practice writing, call `Maps_to_page(page_type='writing_practice')`.
3. After explaining a vocabulary word, politely ask if they want to save it and call `add_by_name`.
4. If the user asks to summarize their current task, evaluate their answers, or find their mistakes, call `analyze_mistakes` AND provide a brief, encouraging analysis in your text reply based on the [Frontend Context] below.

[Frontend Context]:
- Visible Words on screen: [{$context_words}]
- Current Page Content/Article: [{$page_content}]
- User's Quiz Answers/Mistakes: [{$quiz_data}]";

$history = $input['history'] ?? [];
$messages = [["role" => "system", "content" => $system_prompt]];
foreach ($history as $msg) {
    $messages[] = $msg;
}
$messages[] = ["role" => "user", "content" => $input['message']];

// --- 3. Send Request to DeepSeek API ---
$postData = [
    "model" => "deepseek-chat",
    "messages" => $messages,
    "tools" => $tools,
    "tool_choice" => "auto",
    "max_tokens" => 250
];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . $apiKey]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
// Disable SSL verification for local development compatibility
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
$result = json_decode($response, true);
curl_close($ch);

// --- 4. Process AI Reply and Tool Calls ---
$aiMessage = $result['choices'][0]['message'] ?? [];
$reply = $aiMessage['content'] ?? "";
$action = null;

if (isset($aiMessage['tool_calls'])) {
    foreach ($aiMessage['tool_calls'] as $toolCall) {
        $funcName = $toolCall['function']['name'];
        $args = json_decode($toolCall['function']['arguments'], true);

        if ($funcName === 'navigate_to_page') {
            $dest = $args['page_type'] ?? '';
            $url = ($dest === 'fun_learning') ? 'galgame/galgame/index.html' : 'writing.php';
            $action = ['type' => 'redirect', 'url' => $url];
            if (empty($reply)) {
                $reply = "Certainly. The path of academia requires both inspiration and practice. Opening your exclusive portal now...";
            }
        } elseif ($funcName === 'add_by_name') {
            $action = ['type' => 'call_frontend_function', 'function_name' => 'add_by_name', 'word' => $args['word'] ?? ''];
            if (empty($reply)) {
                $reply = "I have recorded this word in your notebook.";
            }
        } elseif ($funcName === 'analyze_mistakes') {
            // NEW: Pass an action back to the frontend in case the UI wants to highlight mistakes
            $action = ['type' => 'analyze_mistakes'];
            if (empty($reply)) {
                $reply = "Let me take a look at your current progress and analyze your work...";
            }
        }
    }
}

// Return the final payload to the frontend
echo json_encode(['reply' => $reply, 'action' => $action]);
