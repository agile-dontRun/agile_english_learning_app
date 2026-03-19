<?php
require_once 'db_connect.php';

$ted_id = isset($_GET['ted_id']) ? (int)$_GET['ted_id'] : 0;

if ($ted_id <= 0) {
    die("Invalid TED ID");
}

// get video
$stmt = $conn->prepare("SELECT title, video_url FROM ted_talks WHERE ted_id = ?");
$stmt->bind_param("i", $ted_id);
$stmt->execute();
$talk = $stmt->get_result()->fetch_assoc();

// get question
$stmt2 = $conn->prepare("
    SELECT 
        t.gapfill_text_id,
        t.blanked_text_en,
        a.question_no,
        a.correct_answer
    FROM ted_gapfill_texts t
    LEFT JOIN ted_blank_answers a 
        ON t.gapfill_text_id = a.gapfill_text_id
    WHERE t.transcript_id = ?
    ORDER BY t.gapfill_text_id, a.question_no
");

if (!$stmt2) {
    die("SQL ERROR: " . $conn->error);
}

$stmt2->bind_param("i", $ted_id);
$stmt2->execute();
$questions = $stmt2->get_result();
$questions = [];
while ($row = $result->fetch_assoc()) {
    $id = $row['gapfill_text_id'];
    if (!isset($questions[$id])) {
        $questions[$id] = [
            'text'    => $row['blanked_text_en'],
            'answers' => []
        ];
    }
    if (!empty($row['correct_answer'])) {
        $questions[$id]['answers'][] = $row['correct_answer'];
    }
}
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Word Garden - Practice</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="practice-page">
    <nav class="navbar">
        <div class="nav-container">
            <button class="nav-item active">TED TALK</button>
            <button class="nav-item">IELTS LISTENING</button>
            <button class="nav-item">DAILY TALK</button>
            <button class="nav-item">VOCABULARY</button>
            <button class="nav-item">CALENDAR</button>
            <button class="nav-item">GROUP</button>
            <button class="nav-item">PROFILE</button>
        </div>
    </nav>

    <main class="practice-container">
        <div class="practice-layout">
            
            <div class="practice-left">
                <div class="practice-video-box">
                    <?php
                    $video_src = htmlspecialchars($talk['video_url']);
                    if (preg_match('/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/', $video_src, $m) ||
                        preg_match('/youtu\.be\/([a-zA-Z0-9_-]+)/', $video_src, $m)) {
                        $video_src = 'https://www.youtube.com/embed/' . $m[1];
                    }
                        ?>
                    <iframe src="<?= $video_src ?>" frameborder="0" allowfullscreen></iframe>
                </div>
                <div class="practice-images">
                    <div class="img-box"><img src="ted 1.png" alt="TED Talk"></div>
                    <div class="img-box"><img src="ted 0.png" alt="TED Headphone"></div>
                    <div class="img-box"><img src="ted 2.png" alt="Ideas Worth Spreading"></div>
                </div>
            </div>

            <div class="practice-right">
                <div class="practice-right-header">
                    <button class="practice-back-btn" onclick="window.history.back()">BACK</button>
                </div>
                
                <div class="transcript-box">
                    
                    <?php foreach ($questions as $q): 
                        $parts = explode('______', $q['text']);
                        $answers = $q['answers'];
                    ?>
                    <p>
                        <?php for ($i = 0; $i < count($parts); $i++): 
                            echo htmlspecialchars($parts[$i]);
                            if ($i < count($parts) - 1): 
                                $correct = $answers[$i] ?? ''; 
                        ?>
                            <input type="text" class="blank-input-box" 
                                data-answer="<?= htmlspecialchars($correct) ?>" 
                                placeholder="..." />
                        <?php 
                            endif;
                        endfor; ?>
                    </p>
                </div>
                
                <div class="fill-blank-box" style="text-align: center;">
                    
                    <button class="check-btn" onclick="checkAnswers()">✅ CHECK ANSWERS</button>
                </div>
            </div>
            
        </div>
    </main>
    <script>
    function checkAnswers() {
        let correctCount = 0;
        let total = 0;

        document.querySelectorAll('.blank-input-box').forEach(input => {
            const correct = input.dataset.answer.trim().toLowerCase();
            const user = input.value.trim().toLowerCase();

            total++;

            if (user === correct) {
                input.style.border = "2px solid green";
                correctCount++;
            } else {
                input.style.border = "2px solid red";
            }
        });

        alert(`✅ 正确数量：${correctCount} / ${total}`);
    }
    </script>
</body>
</html>