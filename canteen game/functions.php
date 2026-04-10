<?php
function initGameState() {
    return [
        'chances_left' => 5,
        'combo' => 0,
        'correct_count' => 0,
        'game_active' => true,
        'game_result' => null,
        'waiting_for_video' => false,
        'pending_answer' => null
    ];
}
?>