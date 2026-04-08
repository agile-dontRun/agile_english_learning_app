<?php
// Disable error output to avoid exposing warnings/notices to users
error_reporting(0);
ini_set('display_errors', 0);

// Start the session so game progress can be stored between requests
session_start();

// Load question data, session helpers, and coin reward helpers
require_once 'config/questions.php';
require_once 'includes/session.php';
require_once dirname(__DIR__, 2) . '/coin_common.php';

// Require the user to be logged in before accessing the game
coin_require_login();

// Get the current user and today's coin/reward state
$userId = coin_current_user_id();
$coinBalance = coin_get_balance($conn, $userId);
$stageRewardToday = coin_get_daily_reward_record($conn, $userId, 'stage', coin_today_date());

// Initialize the stage game session state
initGameState();


// Provide default ending messages if none were defined elsewhere
if (!isset($endingMessages)) {
    $endingMessages = [
        0 => '😢 Keep practicing! You\'ll do better next time!',
        1 => '💪 Not bad! Keep working hard!',
        2 => '👍 Room for improvement! Practice makes perfect!',
        3 => '🎭 Good job! You have potential!',
        4 => '🌟 Nice work! The audience appreciates you!',
        5 => '🎉 Great! Your performance was well received!',
        6 => '🏆 Excellent! The judges are impressed!',
        7 => '💫 Amazing! The crowd cheers for you!',
        8 => '🎭✨ Perfect! Outstanding performance! You\'re a star!',
    ];
}

// Generate a fresh set of 8 random questions if needed
if (!isset($_SESSION['stage_state']['questions']) || ($_SESSION['stage_state']['reset_flag'] ?? false)) {
    $allQuestionsData = getAllQuestions();
    $_SESSION['stage_state']['questions'] = getRandomQuestions($allQuestionsData, 8);
    $_SESSION['stage_state']['reset_flag'] = false;
}

// Use the question set stored in the current session
$questions = $_SESSION['stage_state']['questions'];




// Handle AJAX/API requests from the frontend
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    // Handle answer submission
    if ($_POST['action'] === 'answer') {
        $question_index = intval($_POST['question_index']);  
        $answer = intval($_POST['answer']);
        
        // Make sure the requested question exists
        if (!isset($questions[$question_index])) {
            echo json_encode([
                'success' => false,
                'error' => 'Question not found at index: ' . $question_index
            ]);
            exit;
        }
        
        $current_question = $questions[$question_index];
        $is_correct = ($answer == $current_question['correct']);
        
        // Increase score for correct answers
        if ($is_correct) {
            $_SESSION['stage_state']['correct_count']++;
        }
        
        // Save the result of this question and advance progress
        $_SESSION['stage_state']['answers'][$question_index] = $is_correct;
        $_SESSION['stage_state']['current_question']++;
        
        // Check whether the full quiz is finished
        $all_done = ($_SESSION['stage_state']['current_question'] >= 8);

        // Default reward state before daily reward settlement
        $rewardResult = [
            'already_claimed' => (bool)coin_get_daily_reward_record($conn, $userId, 'stage', coin_today_date()),
            'granted' => false,
            'reward_amount' => 0,
            'balance' => coin_get_balance($conn, $userId)
        ];

        // Settle the daily reward when the player finishes all questions
        if ($all_done) {
            $rewardAmount = max(0, (int)$_SESSION['stage_state']['correct_count']) * 10;
            $rewardResult = coin_claim_daily_reward(
                $conn,
                $userId,
                'stage',
                coin_today_date(),
                $rewardAmount,
                'stage_daily_first_play',
                'default',
                [
                    'correct_count' => (int)$_SESSION['stage_state']['correct_count'],
                    'total_questions' => 8
                ]
            );
        }
        
        // Return answer result and updated game state
        echo json_encode([
            'success' => true,
            'is_correct' => $is_correct,
            'correct_count' => $_SESSION['stage_state']['correct_count'],
            'current_question' => $_SESSION['stage_state']['current_question'],
            'all_done' => $all_done,
            'explanation' => $current_question['explanation'],
            'correct_answer' => $current_question['options'][$current_question['correct']],
            'reward_granted' => (bool)($rewardResult['granted'] ?? false),
            'reward_amount' => (int)($rewardResult['reward_amount'] ?? 0),
            'daily_reward_claimed' => $all_done
                ? true
                : (bool)($rewardResult['already_claimed'] ?? false),
            'coin_balance' => (int)($rewardResult['balance'] ?? coin_get_balance($conn, $userId))
        ]);
        exit;
    }
    
    // Reset the game session state with a new random question set
    if ($_POST['action'] === 'reset') {
        $allQuestionsData = getAllQuestions();
        $_SESSION['stage_state'] = [
            'current_question' => 0,
            'correct_count' => 0,
            'answers' => [],
            'game_active' => true,
            'game_finished' => false,
            'questions' => getRandomQuestions($allQuestionsData, 8),
            'reset_flag' => false
        ];
        echo json_encode(['success' => true]);
        exit;
    }
    
    // Return one question by index for the frontend
    if ($_POST['action'] === 'get_question') {
        $index = intval($_POST['index']);
        if (isset($questions[$index])) {
            $question = $questions[$index];
            echo json_encode([
                'id' => $question['id'],
                'question' => $question['question'],
                'options' => $question['options'],
                'current' => $index + 1,
                'total' => 8
            ]);
        } else {
            echo json_encode(['error' => 'Question not found']);
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <!-- Mobile-friendly viewport settings -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">

    <!-- Page title shown in the browser tab -->
    <title>English Theater Challenge</title>

    <!-- Main stylesheet -->
    <link rel="stylesheet" href="assets/css/style.css">

    <!-- Embed question and ending message data for frontend JavaScript -->
    <script id="questions-data" type="application/json"><?php echo json_encode($questions); ?></script>
    <script id="ending-messages-data" type="application/json"><?php echo json_encode($endingMessages); ?></script>

    <!-- Bootstrap values passed from PHP to JavaScript -->
    <script>
        window.STAGE_BOOTSTRAP = {
            coinBalance: <?= (int)$coinBalance ?>,
            dailyRewardClaimed: <?= $stageRewardToday ? 'true' : 'false' ?>
        };
    </script>
</head>
<body>
<div class="game-container">
    <!-- Top info bar showing coins, reward status, progress, and controls -->
    <div class="info-bar">
        <div class="info-card">Coins: <span id="coinCount"><?= (int)$coinBalance ?></span></div>
        <div class="info-card">Daily Reward: <span id="dailyRewardStatus"><?= $stageRewardToday ? 'Claimed' : 'Available' ?></span></div>
        <div class="info-card">🎭 Progress: <span id="progressText">0/8</span></div>
        <div class="progress-bar">
            <div class="progress-fill" id="progressFill"></div>
        </div>
        <div class="info-card">⭐ Score: <span id="scoreCount">0</span></div>

        <!-- Return to the 6th floor selection page -->
        <button class="reset-btn" style="background-color: #d9534f;" 
                onclick="window.location.href='../galgame/index.html?view=floor6'">
            🔙 Back to 6 floor
        </button>

        <!-- Reset the current stage game -->
        <button class="reset-btn" id="resetBtn">🔄 Reset Game</button>
    </div>

    <!-- Main stage image area -->
    <div class="stage-container">
        <img id="stageImage" class="stage-image" src="stage.jpg" alt="Stage Background">
    </div>

    <!-- Main interaction panel -->
    <div class="interaction-area" id="interactionArea">
        <!-- Opening dialogue area shown before the quiz starts -->
        <div id="dialogueArea" class="dialogue-box">
            <div class="dialogue-message user-dialogue">
                <div class="dialogue-name">🎭 Me</div>
                <div class="dialogue-text">There's an English theater performance at our school! I want to join!</div>
            </div>
            <div class="dialogue-message judge-dialogue">
                <div class="dialogue-name">👨‍⚖️ Judge</div>
                <div class="dialogue-text">Please begin your performance.</div>
            </div>
            <button class="start-btn" id="startQuizBtn">🎬 Start Performance 🎭</button>
        </div>

        <!-- Quiz area shown during gameplay -->
        <div id="quizArea" class="quiz-box">
            <div class="question-header">
                <span>📖 Theater Knowledge Quiz</span>
                <span id="questionCounter">Question 1/8</span>
            </div>
            <div class="question-text" id="questionText">Loading question...</div>
            <div class="options" id="optionsContainer"></div>
            <div class="feedback-area" id="feedbackMsg"></div>
        </div>

        <!-- Final result screen shown after the game ends -->
        <div id="resultArea" class="result-area">
            <h2>🎭 Performance Complete 🎭</h2>
            <div class="score-display" id="scoreDisplay"></div>
            <div class="ending-message" id="endingMessage"></div>
            <button class="start-btn" id="playAgainBtn">🔄 Play Again</button>
        </div>
    </div>
</div>

<!-- GIF overlay used to show performance result animations -->
<div id="gifOverlay" class="gif-overlay">
    <div class="gif-content">
        <img id="gifImage" src="" alt="Performance Result">
        <button class="gif-close-btn" id="closeGifBtn">Continue 🎭</button>
    </div>
</div>

<!-- Main frontend game logic -->
<script src="assets/js/game.js"></script>
</body>
</html>