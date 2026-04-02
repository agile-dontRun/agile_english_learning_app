<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once 'config/questions.php';
require_once 'includes/session.php';

initGameState();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
  if ($_POST['action'] === 'answer') {
    $question_index = intval($_POST['question_index']);  
    $answer = intval($_POST['answer']);
    
   
    if (!isset($questions[$question_index])) {
        echo json_encode([
            'success' => false,
            'error' => 'Question not found at index: ' . $question_index
        ]);
        exit;
    }
    
    $current_question = $questions[$question_index];
    $is_correct = ($answer == $current_question['correct']);
    
    if ($is_correct) {
        $_SESSION['stage_state']['correct_count']++;
    }
    
    $_SESSION['stage_state']['answers'][$question_index] = $is_correct;
    $_SESSION['stage_state']['current_question']++;
    
    $all_done = ($_SESSION['stage_state']['current_question'] >= 8);
    
    echo json_encode([
        'success' => true,
        'is_correct' => $is_correct,
        'correct_count' => $_SESSION['stage_state']['correct_count'],
        'current_question' => $_SESSION['stage_state']['current_question'],
        'all_done' => $all_done,
        'explanation' => $current_question['explanation'],
        'correct_answer' => $current_question['options'][$current_question['correct']]
    ]);
    exit;
}

    
    if ($_POST['action'] === 'reset') {
        $_SESSION['stage_state'] = [
            'current_question' => 0,
            'correct_count' => 0,
            'answers' => [],
            'game_active' => true,
            'game_finished' => false
        ];
        echo json_encode(['success' => true]);
        exit;
    }
    
    if ($_POST['action'] === 'get_question') {
        $index = intval($_POST['index']);
        $question = $questions[$index];
        echo json_encode([
            'id' => $question['id'],
            'question' => $question['question'],
            'options' => $question['options'],
            'current' => $index + 1,
            'total' => 8
        ]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>English Theater Challenge</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script id="questions-data" type="application/json"><?php echo json_encode($questions); ?></script>
    <script id="ending-messages-data" type="application/json"><?php echo json_encode($endingMessages); ?></script>
</head>
<body>
<div class="game-container">
    <div class="info-bar">
        <div class="info-card">🎭 Progress: <span id="progressText">0/8</span></div>
        <div class="progress-bar">
            <div class="progress-fill" id="progressFill"></div>
        </div>
        <div class="info-card">⭐ Score: <span id="scoreCount">0</span></div>
        <button class="reset-btn" id="resetBtn">🔄 Reset Game</button>
    </div>

    <div class="stage-container">
        <img id="stageImage" class="stage-image" src="stage.jpg" alt="Stage Background">
    </div>

    <div class="interaction-area" id="interactionArea">
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

        <div id="quizArea" class="quiz-box">
            <div class="question-header">
                <span>📖 Theater Knowledge Quiz</span>
                <span id="questionCounter">Question 1/8</span>
            </div>
            <div class="question-text" id="questionText">Loading question...</div>
            <div class="options" id="optionsContainer"></div>
            <div class="feedback-area" id="feedbackMsg"></div>
        </div>

        <div id="resultArea" class="result-area">
            <h2>🎭 Performance Complete 🎭</h2>
            <div class="score-display" id="scoreDisplay"></div>
            <div class="ending-message" id="endingMessage"></div>
            <button class="start-btn" id="playAgainBtn">🔄 Play Again</button>
        </div>
    </div>
</div>

<div id="gifOverlay" class="gif-overlay">
    <div class="gif-content">
        <img id="gifImage" src="" alt="Performance Result">
        <button class="gif-close-btn" id="closeGifBtn">Continue 🎭</button>
    </div>
</div>

<script src="assets/js/game.js"></script>
</body>
</html>