<?php
function initGameState() {
    if (!isset($_SESSION['stage_state'])) {
        $_SESSION['stage_state'] = [
            'current_question' => 0,
            'correct_count' => 0,
            'answers' => [],
            'game_active' => true,
            'game_finished' => false
        ];
    }
}