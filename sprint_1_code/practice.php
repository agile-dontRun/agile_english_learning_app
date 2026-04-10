<?php
session_start();

// 1. Login Security Check
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

require_once 'db_connect.php';

$ted_id = isset($_GET['ted_id']) ? (int)$_GET['ted_id'] : 0;
$nickname = $_SESSION['nickname'] ?? 'Learner';

if ($ted_id <= 0) {
    die("Invalid TED ID");
}

// Fetch Video Info
$stmt = $conn->prepare("SELECT title, video_url FROM ted_talks WHERE ted_id = ?");
$stmt->bind_param("i", $ted_id);
$stmt->execute();
$talk = $stmt->get_result()->fetch_assoc();

// Fetch Questions and Answers
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
$result = $stmt2->get_result();

// Data restructuring for the gap-fill logic
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Practice - Word Garden</title>
    <style>
        /* ===== Premium Green Theme System ===== */
        :root {
            --primary-green: #1b4332;
            --accent-green: #40916c;
            --soft-green-bg: #f2f7f5;
            --card-shadow: 0 10px 30px rgba(27, 67, 50, 0.08);
            --text-main: #2d3436;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: var(--soft-green-bg);
            margin: 0;
            color: var(--text-main);
        }

        /* ===== 1. Navigation Header ===== */
        .nav-header {
            width: 100%;
            height: 70px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 50px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            position: fixed;
            top: 0;
            z-index: 1000;
            box-sizing: border-box;
        }
        .nav-logo { font-size: 22px; font-weight: bold; color: var(--primary-green); text-decoration: none; }
        .nav-links { display: flex; gap: 20px; }
        .nav-links a {
            text-decoration: none;
            color: #666;
            font-size: 14px;
            font-weight: 500;
            padding: 5px 12px;
            border-radius: 8px;
            transition: 0.3s;
        }
        .nav-links a:hover, .nav-links a.active { color: var(--primary-green); background: #f0f7f4; }

        /* ===== 2. Content Layout ===== */
        .main-content {
            max-width: 1300px;
            margin: 100px auto 60px;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }

        /* Left Side: Video & Media */
        .media-column { display: flex; flex-direction: column; gap: 20px; }
        .video-container {
            background: white;
            border-radius: 20px;
            padding: 15px;
            box-shadow: var(--card-shadow);
        }
        .video-wrapper {
            width: 100%;
            aspect-ratio: 16 / 9;
            background: #000;
            border-radius: 12px;
            overflow: hidden;
        }
        .video-wrapper iframe { width: 100%; height: 100%; border: none; }

        /* Right Side: Exercise Sheet */
        .exercise-column {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: var(--card-shadow);
            display: flex;
            flex-direction: column;
        }
        .exercise-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 2px solid var(--soft-green-bg);
            padding-bottom: 15px;
        }
        .back-link {
            text-decoration: none;
            color: var(--accent-green);
            font-weight: 600;
            font-size: 14px;
        }

        .transcript-content {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #444;
            flex-grow: 1;
        }

        /* Input Styling */
        .blank-input {
            border: none;
            border-bottom: 2px solid #ddd;
            width: 120px;
            text-align: center;
            font-size: 1rem;
            color: var(--primary-green);
            font-weight: 600;
            outline: none;
            transition: 0.3s;
            margin: 0 5px;
            background: #fcfdfd;
        }
        .blank-input:focus { border-bottom-color: var(--accent-green); background: #f0f7f4; }

        /* Action Button */
        .check-btn {
            background: var(--primary-green);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 50px;
            font-weight: bold;
            font-size: 16px;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 30px;
            align-self: center;
        }
        .check-btn:hover { background: var(--accent-green); transform: translateY(-2px); }

        @media (max-width: 1000px) {
            .main-content { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <nav class="nav-header">
        <a href="home.php" class="nav-logo">Word Garden</a>
        <div class="nav-links">
            <a href="home.php">Home</a>
            <a href="TED.php" class="active">TED Talk</a>
            <a href="ielts.php">IELTS</a>
            <a href="daily_decryption.php">Daily Talk</a>
            <a href="vocabulary.php">Vocabulary</a>
            <a href="calendar.php">Calendar</a>
            <a href="profile.php">Profile</a>
        </div>
    </nav>

    <main class="main-content">
        
        <div class="media-column">
            <div class="video-container">
                <div class="video-wrapper">
                    <?php
                    $video_src = htmlspecialchars($talk['video_url']);
                    if (preg_match('/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/', $video_src, $m) ||
                        preg_match('/youtu\.be\/([a-zA-Z0-9_-]+)/', $video_src, $m)) {
                        $video_src = 'https://www.youtube.com/embed/' . $m[1];
                    }
                    ?>
                    <iframe src="<?= $video_src ?>" allowfullscreen></iframe>
                </div>
            </div>
            <div style="text-align: center; color: #666; font-size: 0.9rem;">
                Listening practice for: <strong><?= htmlspecialchars($talk['title']) ?></strong>
            </div>
        </div>

        <div class="exercise-column">
            <div class="exercise-header">
                <span style="font-weight: bold; color: var(--primary-green);">Fill in the Blanks</span>
                <a href="javascript:history.back()" class="back-link">← Back to Video</a>
            </div>

            <div class="transcript-content">
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
                        <input type="text" class="blank-input" 
                               data-answer="<?= htmlspecialchars($correct) ?>" 
                               placeholder="..." />
                    <?php 
                        endif;
                    endfor; ?>
                </p>
                <?php endforeach; ?>
            </div>

            <button class="check-btn" onclick="checkAnswers()">CHECK MY ANSWERS</button>
        </div>

    </main>

    <script>
    /**
     * Logic for grading the user inputs
     */
    function checkAnswers() {
        let correctCount = 0;
        let total = 0;

        document.querySelectorAll('.blank-input').forEach(input => {
            const correct = input.dataset.answer.trim().toLowerCase();
            const user = input.value.trim().toLowerCase();

            total++;

            if (user === correct && user !== "") {
                input.style.borderBottom = "2px solid #2d6a4f"; // Dark Green
                input.style.background = "#f0f7f4";
                correctCount++;
            } else {
                input.style.borderBottom = "2px solid #e63946"; // Red
                input.style.background = "#fffafa";
            }
        });

        // Translated alert message
        alert(`Assessment Complete!\nCorrect Answers: ${correctCount} out of ${total}`);
    }
    </script>
</body>
</html>