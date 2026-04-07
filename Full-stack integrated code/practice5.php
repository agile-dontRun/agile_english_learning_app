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

/**
 * Issue 3: 填空引擎渲染逻辑
 * 使用 preg_split 处理下划线并动态生成 Input
 */
function render_practice_exercise($questions, $blankLimit = 10) {
    $blankCount = 0;
    $stopRendering = false;
    
    foreach ($questions as $q) {
        // 使用正则拆分文本
        $parts = preg_split('/_{3,}/', $q['text']);
        $answers = $q['answers'];
        ?>
        <p>
            <?php for ($i = 0; $i < count($parts); $i++): 
                if ($stopRendering) break; // 熔断机制
                
                echo htmlspecialchars($parts[$i]);

                if ($i < count($parts) - 1 && isset($answers[$i])) {
                    if ($blankCount < $blankLimit) {
                        ?>
                        <input type="text" class="blank-input" 
                               data-answer="<?= htmlspecialchars($answers[$i]) ?>" 
                               placeholder="..." />
                        <?php
                        $blankCount++;
                        if ($blankCount >= $blankLimit) { $stopRendering = true; break; }
                    }
                }
            endfor; ?>
        </p>
        <?php 
        if ($stopRendering) break;
    }
/**
 * Issue 4: JavaScript 评分逻辑
 */
function checkAnswers() {
    const inputs = document.querySelectorAll('.blank-input');
    let correctCount = 0;
    let wrongDetails = [];

    inputs.forEach((input, index) => {
        const correct = input.dataset.answer.trim().toLowerCase();
        const user = input.value.trim().toLowerCase();

        if (user === correct && user !== "") {
            input.style.borderBottom = "3px solid #2d6a4f"; // 正确样式
            input.style.background = "#f0f7f4";
            correctCount++;
        } else {
            input.style.borderBottom = "3px solid #c0392b"; // 错误样式
            input.style.background = "#fff5f5";
            // 记录错题详情
            wrongDetails.push(`
                <div style="text-align:left; margin-bottom:10px;">
                    <b>Blank ${index + 1}:</b> 
                    <span style="color:#c0392b; text-decoration:line-through;">${user || "(empty)"}</span> → 
                    <span style="color:#2d6a4f; font-weight:800;">${correct}</span>
                </div>
            `);
        }
    });

    const accuracy = correctCount / inputs.length;
    // 调用 SweetAlert2 显示结果
    Swal.fire({
        title: accuracy >= 0.8 ? 'Excellent Work!' : 'Keep Practicing!',
        html: `Score: <b>${correctCount} / ${inputs.length}</b><br>${wrongDetails.join('')}`,
        icon: accuracy >= 0.8 ? 'success' : 'warning',
        confirmButtonText: 'Continue',
        confirmButtonColor: '#002147'
    });
}

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

<main class="main-content">
    <?php render_practice_media_column($talk); ?>

    <div class="exercise-column">
        <div class="exercise-header">
            <h3>Fill in the Blanks</h3>
            <a href="TED.php" class="back-link">« Return to Library</a>
        </div>
        <div class="transcript-content">
            <?php 
                $limited_qs = get_limited_practice_questions($conn, $ted_id);
                render_practice_exercise($limited_qs); 
            ?>
        </div>
        <button class="check-btn" onclick="checkAnswers()">Evaluate Performance</button>
    </div>
</main>