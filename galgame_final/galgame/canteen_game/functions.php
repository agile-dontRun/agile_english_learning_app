<?php
// Initialize and return the default game state
function initGameState() {
    return [
        // Number of chances the player has in one round
        'chances_left' => 5,

        // Current number of consecutive correct answers
        'combo' => 0,

        // Total number of correct answers in the current game
        'correct_count' => 0,

        // Whether the game is currently active
        'game_active' => true,

        // Final game result message, filled in when the game ends
        'game_result' => null,

        // Whether the game is currently waiting for a video to finish
        'waiting_for_video' => false,

        // Store a pending answer if needed during video playback
        'pending_answer' => null
    ];
}
?>