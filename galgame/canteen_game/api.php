<?php
session_start();
require_once 'functions.php';
require_once 'questions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'])) {
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$action = $_POST['action'];

switch ($action) {
    case 'answer':
        handleAnswer();
        break;
    
    case 'reset':
        handleReset();
        break;
    
    case 'get_question':
        handleGetQuestion();
        break;
    
    default:
        echo json_encode(['error' => 'Unknown action']);
        break;
}

function handleAnswer() {
    global $questions;
    
    $question_id = intval($_POST['question_id']);
    $answer = intval($_POST['answer']);
    
    $current_question = null;
    foreach ($questions as $q) {
        if ($q['id'] == $question_id) {
            $current_question = $q;
            break;
        }
    }
    
    $is_correct = ($answer == $current_question['correct']);
    $video_to_play = '';
    $message = '';
    
    if ($is_correct) {
        $_SESSION['game_state']['combo']++;
        $_SESSION['game_state']['correct_count']++;
        
        if ($_SESSION['game_state']['combo'] >= 3) {
            $video_to_play = 'video3';
            $message = '🎉 3 in a row! SUPER SCOOP activated!!! 🥄✨';
        } else {
            $video_to_play = 'video2';
            $message = '✅ Correct! Auntie gives you a big scoop of fried chicken!';
        }
    } else {
        $_SESSION['game_state']['combo'] = 0;
        $video_to_play = 'video1';
        $message = '❌ Wrong! Correct answer: ' . $current_question['options'][$current_question['correct']];
    }
    
    $_SESSION['game_state']['chances_left']--;
    
    $game_over = false;
    $final_message = '';
    
    if ($_SESSION['game_state']['chances_left'] <= 0) {
        $game_over = true;
        $correct_count = $_SESSION['game_state']['correct_count'];
        
        if ($correct_count == 0) {
            $final_message = '😢 Keep trying! Come back tomorrow for the hidden menu!';
        } elseif ($correct_count == 5) {
            $final_message = '🎉🎊 Congratulations! You got ALL the dishes!!! 🍗🍟🎊🎉';
        } else {
            $final_message = '👍 Great job! Come back tomorrow!!!';
        }
        $_SESSION['game_state']['game_active'] = false;
        $_SESSION['game_state']['game_result'] = $final_message;
    }
    
    echo json_encode([
        'success' => true,
        'is_correct' => $is_correct,
        'video' => $video_to_play,
        'message' => $message,
        'chances_left' => $_SESSION['game_state']['chances_left'],
        'combo' => $_SESSION['game_state']['combo'],
        'correct_count' => $_SESSION['game_state']['correct_count'],
        'game_over' => $game_over,
        'final_message' => $final_message,
        'correct_answer' => $current_question['options'][$current_question['correct']],
        'explanation' => $current_question['meaning']
    ]);
}

function handleReset() {
    $_SESSION['game_state'] = initGameState();
    echo json_encode(['success' => true]);
}

function handleGetQuestion() {
    global $questions;
    $random_index = array_rand($questions);
    $question = $questions[$random_index];
    echo json_encode([
        'id' => $question['id'],
        'proverb' => $question['proverb'],
        'options' => $question['options']
    ]);
}
?>