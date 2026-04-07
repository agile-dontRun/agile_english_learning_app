<?
function render_practice_media_column($talk) {
    $video_src = htmlspecialchars($talk['video_url']);
    // YouTube URL 解析逻辑
    if (preg_match('/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/', $video_src, $m) ||
        preg_match('/youtu\.be\/([a-zA-Z0-9_-]+)/', $video_src, $m)) {
        $video_src = 'https://www.youtube.com/embed/' . $m[1] . '?rel=0&modestbranding=1';
    }

    /**
 * Issue 2: 获取填空题目数据并执行 10 题切片逻辑
 */
function get_limited_practice_questions($conn, $ted_id, $limit = 10) {
    $stmt = $conn->prepare("
        SELECT t.gapfill_text_id, t.blanked_text_en, a.question_no, a.correct_answer
        FROM (
            SELECT gapfill_text_id, blanked_text_en 
            FROM ted_gapfill_texts 
            WHERE transcript_id = ? 
            ORDER BY gapfill_text_id ASC
            LIMIT 10
        ) t
        LEFT JOIN ted_blank_answers a ON t.gapfill_text_id = a.gapfill_text_id
        ORDER BY t.gapfill_text_id, a.question_no
    ");
    
    $stmt->bind_param("i", $ted_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $questions = [];
    while ($row = $result->fetch_assoc()) {
        $id = $row['gapfill_text_id'];
        if (!isset($questions[$id])) {
            $questions[$id] = ['text' => $row['blanked_text_en'], 'answers' => []];
        }
        if (!empty($row['correct_answer'])) {
            $questions[$id]['answers'][] = $row['correct_answer'];
        }
    }

    // 强制切片逻辑：确保最终只有 10 道题
    $keys = array_keys($questions);
    if (count($keys) > $limit) {
        $selected_keys = array_slice($keys, 0, $limit);
        $limited = [];
        foreach ($selected_keys as $key) {
            $limited[$key] = $questions[$key];
        }
        return $limited;
    }
    return $questions;
}

 ?>
    <div class="media-column">
        <div class="video-card">
            <div class="video-wrapper">
                <iframe src="<?= $video_src ?>" allowfullscreen></iframe>
            </div>
        </div>
        <div class="video-info">
            <h2><?= htmlspecialchars($talk['title']) ?></h2>
            <p style="margin-top:10px; color:var(--text-light); font-family:'Playfair Display', serif; font-style:italic; font-size: 1.1rem;">
                Academic Listening Comprehension
            </p>
        </div>
    </div>
    <?php
}